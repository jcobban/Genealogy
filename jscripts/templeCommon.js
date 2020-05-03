/************************************************************************
 *  templeCommon.js                                                     *
 *                                                                      *
 *  Javascript code to implement common functionality of scripts        *
 *  in the FamilyTree database management system.                       *
 *  This set of routines is shared between the following scripts:       *
 *      FamilyTree/editEvent.php                                        * 
 *      FamilyTree/editIndivid.php                                      *
 *      FamilyTree/editMarriages.php                                    *
 *      FamilyTree/editParents.php                                      *
 *      FamilyTree/Temples.php                                          *
 *      Canada/BirthRegDetail.php                                       *
 *      Ontario/DeathRegDetail.php                                      *
 *      Ontario/MarriageRegDetail.php                                   *
 *                                                                      *
 *  History:                                                            *
 *      2020/03/04      created                                         *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

var lang                = 'en';
var args                = getArgs();
if ('lang' in args)
    lang                = args.lang;

/************************************************************************
 *  global flag deferSubmit                                             *
 *                                                                      *
 *  Common global flag to prevent submit from completing until all      *
 *  required operations to resolve a temple are completed.              *
 ************************************************************************/
var deferSubmit         = false;

/************************************************************************
 *  function templeChanged                                              *
 *                                                                      *
 *  Take action when the user changes a field containing a temple       *
 *  name to implement assists such as converting to upper case,         *
 *  expanding abbreviations, and completing short form names.           *
 *  This is the onchange method of any input text field that contains   *
 *  temple text that is to be mapped to a reference to a                *
 *  temple.                                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of <input type='text'>              *
 *      ev              Javascript change Event                         *
 ************************************************************************/
function templeChanged(ev)
{
    var form                    = this.form;
    var updateButton            = document.getElementById('updEvent');
    if (updateButton)
        updateButton.disabled   = true;

    // get the name of the input field
    var name;
    var ider                    = 0;

    if (this.name)
        name                    = this.name;
    else
    if (this.id)
        name                    = this.id;
    else
        name                    = '';

    // trim off leading and trailing spaces
    var value                   = this.value.trim();

    // insert space after comma if omitted
    var commaRegex              = /,(\w)/g;
    value                       = value.replace(commaRegex, ", $1");

    // return the normalized value to the input form
    this.value                  = value;

    // if the form has a button named Submit, enable it just in case
    // it was previously disabled
    var submitButton            = document.getElementById('Submit');
    if (submitButton)
        submitButton.disabled   = false;

    // if the value is explicitly [blank] accept it
    if (value == '[' || value == '[blank]' || value == '[Blank]')
    {
        this.value              = '[Blank]';
        return;
    }

    // capitalize words in value if presentation style requires it
    var textTransform           = "";
    if (window.getComputedStyle)    // W3C API
        textTransform   = window.getComputedStyle(this, null).textTransform;
    else
    if (this.currentStyle)      // try IE API
        textTransform           = this.currentStyle.textTransform;
    if (textTransform == "capitalize")
        capitalize(this);

    // if possible display a loading indicator to the user so he/she is
    // aware that the temple lookup is being performed
    var loc             = this.value.toLowerCase();
    if (loc.length != 0 && loc != '[blank]' && loc != '[n/a]')
    {       // search only for non-blank temple
        popupLoading(this);

        // get an JSON file containing temple information from the database
        var loc         = this.value;
    var options             = {};
    options.errorHandler    = function() {alert('script getTempleJSON.php not found on the server')};
        var url = "/FamilyTree/getTempleJSON.php?name=" +
                        encodeURIComponent(loc) +
                        "&form=" + this.form.name +
                        "&field=" + this.name;
        HTTP.get(url,
                 gotTempleJSON,
                 options);
        deferSubmit             = true;
    }       // search only for non-blank temple
    else
        deferSubmit             = false;
}       // function templeChanged

/************************************************************************
 *  function gotTempleJSON                                              *
 *                                                                      *
 *  This method is called when the JSON document representing           *
 *  the temple or temples is retrieved from the database.               *
 *                                                                      *
 *  Input:                                                              *
 *      JSON object with response, for example                          *
 *                                                                      *
 *  {                                                                   *
 *      "parms" : {                                                     *
 *          "name" : "toronto"                                          *
 *      },                                                              *
 *      "count" : "1",                                                  *
 *      "cmd" : "SELECT * FROM tblTR WHERE `temple`='toronto' ...",     *
 *      "temples" : {                                                   *
 *          "TORON" :                                                   *
 *          {                                                           *
 *              "idtr": 128,                                            *
 *              "code": "TORON",                                        *
 *              "code2":    "",                                         *
 *              "temple":   "Toronto, ON, CA",                          *
 *              "address":  "10060 Bramalea Rd, Brampton, ON L6R 1A1",  *
 *              "templestart":  19900825,                               *
 *              "templeend":    0,                                      *
 *              "used":     0,                                          *
 *              "tag1":     0,                                          *
 *              "qstag":    0                                           *
 *          }                                                           *
 *      }                                                               *
 *  }                                                                   *
 *                                                                      *
 ************************************************************************/
function gotTempleJSON(response)
{
    if ('message' in response)
    {
        alert(response.message);
    }
    else
    {
        var count               = response.count;
        var cmd                 = response.cmd;
        var field               = '';       // initiating field name
        if ('field' in response.parms)
            field               = response.parms.field;
        var formname            = '';       // form containing field
        if ('form' in response.parms)
            formname            = response.parms.form;
        var name                = '';       // search argument
        if ('name' in response.parms)
            name                = response.parms.name;

        // locate the form containing the element that initiated the request
        var form                = document.forms[formname];
        if (form === undefined)
        {       // form not found
            alert("templeCommon.js: gotTempleJSON: form name='" + formname +
                    "' not found");
            return;
        }       // form not found

        // locate the element that initiated the request
        var element             = form.elements[field];
        if (element === undefined)
        {       // element not found
            alert("templeCommon.js: gotTempleJSON: element name='" + field +
                    "' not found in form");
            return;
        }       // element not found

        // if there is exactly one temple matching the request then
        // replace the text value of the element with the full temple
        // name from the database
        if (count == 1)
        {       // exactly one matching entry
            for(var code in response.temples)
            {       // loop through the one temple
                var loc         = response.temples[code];
                element.value   = loc['temple'];
            }       // loop through the one temple

            // temple field is updated
            deferSubmit             = false;
            var updateButton        = document.getElementById('updEvent');
            if (updateButton)
                updateButton.disabled   = false;

            // check for action to take after changed
            if (element.afterChange)
                element.afterChange();
            else
                focusNext(element);
        }       // exactly one matching temple
        else
        if (count == 0)
        {       // no matching entries
            var parms   = {"template"   : "",
                            "name"      : name,
                            "formname"  : formname,
                            "field"     : field};
            displayDialog('NewTempleMsg$template',
                          parms,
                          element,      // position
                          closeNewTempleDialog);    // button closes dialog
        }       // no matching entries
        else
        {       // multiple matching entries
            var parms   = { "template"  : "",
                            "name"      : name};
            var dialog  = displayDialog('ChooseTempleMsg$template',
                                        parms,
                                        element,    // position
                                        null,       // button closes dialog
                                        true);      // defer show

            // update selection list for choice
            var form        = dialog.getElementsByTagName('form')[0];
            var select      = form.templeSelect;
            select.onchange = templeChosen;
            select.setAttribute("for", field);
            select.setAttribute("formname", formname);

            for(var idlr in response.temples)
            {       // loop through the temples
                var loc         = response.temples[idlr];
                var locname     = loc['temple'];

                // create option element under select
                var option          = new Option(locname,
                                                 idlr, 
                                                 false, 
                                                 false);
                // IE<8 does not create option element correctly
                option.innerHTML    = locname;
                option.value        = idlr; 
                select.appendChild(option);
            }                   // loop through children of top node
            select.selectedIndex    = 0;

            // make the dialog visible
            show(msgDiv);
            // the following is a workaround for a bug in FF 40.0 and
            // Chromium in which the onchange method of the <select> is
            // not called when the mouse is clicked on an option
            for(var io=0; io < select.options.length; io++)
            {
                var option  = select.options[io];
                option.addEventListener("click", function() {this.selected = true; this.parentNode.onchange();});
            }
            select.focus();
        }                       // multiple matching entries
    }                           // valid response

    hideLoading();  // hide the "loading" indicator
}       // function gotTempleJSON

/************************************************************************
 *  function closeNewTempleDialog                                       *
 *                                                                      *
 *  This closes (hides) the new temple dialog and reenables the         *
 *  update button.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        the HTML <button> element                           *
 ************************************************************************/
function closeNewTempleDialog()
{
    // no longer displaying the modal dialog popup
    var msgDiv              = document.getElementById('msgDiv');
    msgDiv.style.display    = 'none';   // hide
    deferSubmit             = false;
    var updateButton        = document.getElementById('updEvent');
    if (updateButton)
        updateButton.disabled   = false;

    var myform              = this.form;
    if (myform)
    {                           // the dialog includes a form
        var formname        = '';
        var field           = '';
        var elements        = myform.elements;
        for(var ie = 0; ie < elements.length; ie++)
        {
            var element     = elements[ie];
            switch(element.name)
            {
                case 'formname':
                    formname    = element.value;
                    break;

                case 'field':
                    field       = element.value;
                    break;

            }
        }
        if (formname == '')
            alert('templeCommon.js: closeNewTempleDialog: missing element name="formname":' .  myform.outerHTML);
        else
        if (field.length > 0)
        {
            var mainForm    = document.forms[formname];
            var element     = mainForm.elements[field];
            if (element)
            {                   // found requested field in invoking form
                focusNext(element);
            }                   // found requested field in invoking form
            else
            {                   // issue diagnostic
                var elementList = '';
                var comma       = '[';
                for(var fieldname in mainForm.elements)
                {
                    elementList += comma + fieldname;
                    comma       = ',';
                }
                alert("templeCommon.js: closeNewTempleDialog: cannot find input element with name='" + field + "' in form '" + formname + "' elements=" + elementList + "]");
            }                   // issue diagnostic
        }
        else
            alert("templeCommon.js: closeNewTempleDialog: missing element field: " . myform.outerHTML);
    }                           // the dialog includes a form
    else
        alert("templeCommon.js: closeNewTempleDialog: cannot find <form> in open dialog");
    return null;
}       // function closeNewTempleDialog

/************************************************************************
 *  function templeChosen                                               *
 *                                                                      *
 *  This method is called when the user chooses a temple from           *
 *  the dynamic selection list.                                         *
 *                                                                      *
 *  Input:                                                              *
 *      this                <select> element                            *
 ************************************************************************/
function templeChosen()
{
    var chosenOption    = this.options[this.selectedIndex];

    if (chosenOption.value > 0)
    {       // ordinary entry
        var form        = document.forms[this.getAttribute("formname")];
        var elementName = this.getAttribute("for");
        var element     = form.elements[elementName];
        if (element)
        {
            element.value   = chosenOption.innerHTML;

            // check for action to take after changed
            if (element.afterChange)
                element.afterChange();
            else
                focusNext(element);
        }
        else
            alert("templeCommon.js: templeChosen: cannot find input element with name='" + elementName + "'");
    }       // ordinary entry

    closeNewTempleDialog.call(this);
}       // function templeChosen
