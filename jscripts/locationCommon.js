/************************************************************************
 *  locationCommon.js                                                   *
 *                                                                      *
 *  Javascript code to implement common functionality of scripts        *
 *  in the FamilyTree database management system.                       *
 *  This set of routines is shared between the following scripts:       *
 *      FamilyTree/editEvent                                            * 
 *      FamilyTree/editIndivid                                          *
 *      FamilyTree/editMarriages                                        *
 *      FamilyTree/editParents                                          *
 *      FamilyTree/Locations                                            *
 *      Canada/BirthRegDetail                                           *
 *      Ontario/DeathRegDetail                                          *
 *      Ontario/MarriageRegDetail                                       *
 *                                                                      *
 *  History:                                                            *
 *      2011/10/01      created                                         *
 *      2011/10/28      correct error messages that still reference     *
 *                      editIndivid.js                                  *
 *      2012/01/13      change class names                              *
 *                      add "residence" to list of words not capitalized*
 *      2012/07/04      suppress location lookup for blank              *
 *      2012/08/25      support EventLocation fields in locationChanged *
 *      2013/01/08      add abbreviations for prepositions in locations *
 *      2013/03/11      rename changeLocation as locationChanged        *
 *      2013/05/26      replace use of alert for displaying message     *
 *                      about new location                              *
 *      2014/01/01      use the innerHTML property for getting the text *
 *                      value of an <option> tag to support old         * 
 *                      releases of IE                                  *
 *      2014/02/21      handling of EventLocation changed due to        *
 *                      migration to use of CSS for layout.             *
 *                      renamed to locationCommon.js                    *
 *      2014/10/12      use method show to display dialog               *
 *      2014/12/08      locationChanged method now handles any element  *
 *                      whose name contains the text 'Location'         *
 *      2015/07/05      extra semicolon in text                         *
 *      2015/08/14      set focus on <select> in selection dialog       *
 *                      add workaround for bug in FF 40 and Chromium    *
 *      2017/07/30      support afterChange handler for location        *
 *      2018/01/08      put focus on updated location field after       *
 *                      update.                                         *
 *      2018/01/09      add fractions 1/3 and 2/3                       *
 *                      correct placement of focus after user accepts   *
 *                      notification of previously undefined location   *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2018/12/28      dynamically load templates                      *
 *      2019/01/22      do not lookup locations [blank] or [N/A]        *
 *      2019/03/03      myform was no longer defined                    *
 *      2019/05/28      do not treat special characters in location     *
 *                      as regular expression operators                 *
 *      2019/06/12      correctly set focus to next element when a      *
 *                      new location is defined                         *
 *      2019/07/12      insert spaces into location name value if       *
 *                      missing after a comma, between a digit and      *
 *                      a letter, or between a letter and a digit       *
 *      2019/11/09      escaping of characters in name moved to         *
 *                      getLocationXml.php from function locationChanged*
 *      2019/11/15      normalize locations that have concession before *
 *                      lot to put lot first                            *
 *      2019/11/18      use getLocationJSON.php                         *
 *      2020/03/04      loading of dialogs moved to FtTemplate          *
 *      2020/04/14      do not include descriptive prefixes in search   *
 *      2020/04/26      convert trailing 1/2 into ½                     *
 *      2020/06/28      preserve square brackets around location value  *
 *      2021/01/13      use ES2015 syntax                               *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  evtLocAbbrs                                                         *
 *                                                                      *
 *  Table for expanding abbreviations for locations                     *
 ************************************************************************/
const evtLocAbbrs = {
                "1/4" :         "¼",
                "1/3" :         "&#8531;",
                "1/2" :         "½",
                "2/3" :         "&#8532;",
                "3/4" :         "¾",
                "1rn" :         "1RN",
                "2rn" :         "2RN",
                "3rn" :         "3RN",
                "4rn" :         "4RN",
                "5rn" :         "5RN",
                "1rs" :         "1RS",
                "2rs" :         "2RS",
                "3rs" :         "3RS",
                "4rs" :         "4RS",
                "5rs" :         "5RS",
                "Ab" :          "AB",
                "At" :          "at",
                "Bc" :          "BC",
                "By" :          "by",
                "Ca" :          "CA",
                "Con" :         "con",      // suppress capitalization
                "Elg" :         "Elgin",
                "Esx" :         "Essex",
                "For" :         "for",      // suppress capitalization
                "From" :        "from",     // suppress capitalization
                "In" :          "in",       // suppress capitalization
                "Lmt" :         "Lambton", 
                "Lot" :         "lot",      // suppress capitalization
                "Mb" :          "MB",
                "Msx" :         "Middlesex",
                "Nb" :          "NB",
                "Ne" :          "NE",
                "Nl" :          "NL",
                "Ns" :          "NS",
                "Nw" :          "NW",
                "Of" :          "of",       // suppress capitalization
                "On" :          "ON",
                "Or" :          "or",       // suppress capitalization
                "P.o." :        "P.O.",
                "Pi" :          "PI",
                "Qc" :          "QC",
                "Res" :         "residence",
                "Res." :        "residence",
                "Residence" :   "residence",    // suppress capitalization
                "Se" :          "SE",
                "Sk" :          "SK",
                "Sw" :          "SW",
                "Us" :          "USA",
                "Usa" :         "USA",
                "[" :           "[blank]"
                };

/************************************************************************
 *  global flag deferSubmit                                             *
 *                                                                      *
 *  Common global flag to prevent submit from completing until all      *
 *  required operations to resolve a location are completed.            *
 ************************************************************************/
var deferSubmit         = false;

/************************************************************************
 *  function locationChanged                                            *
 *                                                                      *
 *  Take action when the user changes a field containing a location     *
 *  name to implement assists such as converting to upper case,         *
 *  expanding abbreviations, and completing short form names.           *
 *  This is the onchange method of any input text field that contains   *
 *  location text that is to be mapped to a reference to a              *
 *  Location.                                                           *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of <input type='text'>              *
 *      ev              Javascript change Event                         *
 ************************************************************************/
var housePattern            = new RegExp('^(House|Residence) [a-zA-Z \']*, *');

function locationChanged(ev)
{
    let form                    = this.form;
    let updateButton            = document.getElementById('updEvent');
    if (updateButton)
        updateButton.disabled   = true;

    // get the name of the input field
    let name;
    let ider                    = 0;

    if (this.name)
        name                    = this.name;
    else
    if (this.id)
        name                    = this.id;
    else
        name                    = '';

    // trim off leading and trailing spaces
    let value                   = this.value.trim();

    // convert "1/2" alone or at the end of a number to "½"
    let halfRegex               = /1\/2([^0-9])/;
    value                       = value.replace(halfRegex, "½$1");

    // insert spaces where they should appear but don't
    let commaRegex              = /,(\w)/g;
    value                       = value.replace(commaRegex, ", $1");
    let digalfaRegex            = /([0-9½])([a-zA-Z]+)/g;
    let results                 = digalfaRegex.exec(value);
    if (results)
    {
        let letters             = results[2];
        if (letters == 'RN' || letters == 'RS' || letters == 'R' ||
            letters == 'NBTR' || letters == 'RSLR' || 
            letters == 'STR' || letters == 'NTR' ||
            letters == 'st' || letters == 'nd' ||
            letters == 'rd' || letters == 'th')
        {                   // do not separate
        }                   // do not separate
        else
            value               = value.replace(digalfaRegex, "$1 $2");
    }
    let alfadigRegex            = /([a-zA-Z])(\d)/g;
    value                       = value.replace(alfadigRegex, "$1 $2");

    // if the location has a concession followed by a lot, switch them
    let conlotRegex             = /^(con \d+)\s([^,]*lot[^,]*)/;
    let conres                  = conlotRegex.exec(value);
    if (conres)
    {
        let len                 = conres[0].length;
        value                   = conres[2] + ' ' + conres[1] + 
                                  value.substring(len);
    }

    // return the normalized value to the input form
    this.value                  = value;

    // if the form has a button named Submit, enable it just in case
    // it was previously disabled
    let submitButton            = document.getElementById('Submit');
    if (submitButton)
        submitButton.disabled   = false;

    // if the value is explicitly [blank] accept it
    if (value == '[' || value == '[blank]' || value == '[Blank]')
    {
        this.value              = '[Blank]';
        return;
    }

    // capitalize words in value if presentation style requires it
    let textTransform           = "";
    if (window.getComputedStyle)    // W3C API
        textTransform   = window.getComputedStyle(this, null).textTransform;
    else
    if (this.currentStyle)      // try IE API
        textTransform           = this.currentStyle.textTransform;
    if (textTransform == "capitalize")
        capitalize(this);

    // expand abbreviations
    if (this.abbrTbl)
        expAbbr(this,
                this.abbrTbl);

    // if possible display a loading indicator to the user so he/she is
    // aware that the location lookup is being performed
    let loc                     = this.value.toLowerCase();
    if (loc.length != 0 && loc != '[blank]' && loc != '[n/a]')
    {                       // search only for non-blank location
        popupLoading(this);

        // get an XML file containing location information from the database
        let loc                 = this.value;
        let results             = housePattern.exec(loc);
        let locPrefix           = '';
        if (results !== null)
        {
            locPrefix           = results[0];
            let l               = locPrefix.length;
            loc                 = loc.substring(l);
        }
        let options             = {};
        options.errorHandler    = function() {alert('script getLocationJSON.php not found on the server')};
        let url                 = "/FamilyTree/getLocationJSON.php?name=" +
                                    encodeURIComponent(loc) +
                                    "&form=" + this.form.name +
                                    "&field=" + this.name +
                                    "&prefix=" + encodeURIComponent(locPrefix) +
                                    "&fieldname=" + this.name;
        HTTP.get(url,
                 gotLocationJSON,
                 options);
        deferSubmit             = true;
    }                       // search only for non-blank location
    else
        deferSubmit             = false;
}       // function locationChanged

/************************************************************************
 *  function gotLocationJSON                                            *
 *                                                                      *
 *  This method is called when the JSON document representing           *
 *  the location or locations is retrieved from the database.           *
 *                                                                      *
 *  Input:                                                              *
 *      JSON object with response, for example                          *
 *                                                                      *
 *  {                                                                   *
 *      "parms" : {                                                     *
 *          "name" : "caradoc"                                          *
 *      },                                                              *
 *      "count" : "1",                                                  *
 *      "cmd" : "SELECT * FROM tblLR WHERE (`location`='caradoc' ...",  *
 *      "locations" : {                                                 *
 *          "17" :                                                      *
 *          {                                                           *
 *              "idlr":         17,                                     *
 *              "fsplaceid":    "",                                     *
 *              "preposition":  "",                                     *
 *              "location":     "Caradoc, Middlesex, ON, CA",           *
 *              "sortedlocation":   "Caradoc, Middlesex, ON, CA",       *
 *              "shortname":    "Caradoc",                              *
 *              ...                                                     *
 *          }                                                           *
 *      }                                                               *
 *  }                                                                   *
 *                                                                      *
 ************************************************************************/
function gotLocationJSON(response)
{
    if (typeof response == 'object')
    {                       // response is a JSON object
        if ('message' in response)
        {
            alert(response.message);
        }
        else
        {
            let count               = response.count;
            let cmd                 = response.cmd;
            let field               = '';       // initiating field name
            if ('field' in response.parms)
                field               = response.parms.field;
            let formname            = '';       // form containing field
            if ('form' in response.parms)
                formname            = response.parms.form;
            let name                = '';       // search argument
            if ('name' in response.parms)
                name                = response.parms.name;
            let prefix              = '';       // location name prefix
            if ('prefix' in response.parms)
                prefix              = response.parms.prefix;

            // locate the form containing the element that initiated the request
            let form                = document.forms[formname];
            if (form instanceof HTMLFormElement)
            {                   // have form
                // locate the element that initiated the request
                let element         = form.elements[field];
                if (element instanceof Element)
                {               // name identifies Element
                    // if there is exactly one location matching the request then
                    // replace the text value of the element with the full location
                    // name from the database
                    if (count == 1)
                    {           // exactly one matching entry
                        for(let idlr in response.locations)
                        {       // examine the one location
                            let loc         = response.locations[idlr];
                            if (element.value.charAt(0) == '[')
                                element.value   = '[' + prefix + loc['location'] + ']';
                            else
                                element.value   = prefix + loc['location'];
                        }       // examine the one location

                        // location field is updated
                        deferSubmit         = false;
                        let updateButton    = document.getElementById('updEvent');
                        if (updateButton)
                            updateButton.disabled   = false;

                        // check for action to take after changed
                        if (element.afterChange)
                            element.afterChange();
                        else
                            focusNext(element);
                    }           // exactly one matching location
                    else
                    if (count == 0)
                    {           // no matching entries
                        let parms           = {"template"   : "",
                                                "name"      : name,
                                                "formname"  : formname,
                                                "field"     : field};
                        displayDialog('NewLocationMsg$template',
                                      parms,
                                      element,          // position
                                      closeNewDialog);  // button closes dialog
                    }           // no matching entries
                    else
                    {           // multiple matching entries
                        let parms               = { "template"  : "",
                                                    "name"      : name};
                        let dialog  = displayDialog('ChooseLocationMsg$template',
                                                    parms,
                                                    element,    // position
                                                    null,       // button closes
                                                    true);      // defer show

                        // update selection list for choice
                        let form            = dialog.getElementsByTagName('form')[0];
                        let select          = form.locationSelect;
                        select.onchange     = locationChosen;
                        select.setAttribute("for", field);
                        select.setAttribute("formname", formname);

                        for(let i in response.locations)
                        {           // loop through the locations
                            let loc             = response.locations[i];
                            let idlr            = loc.idlr;
                            let locname         = loc.location;

                            // create option element under select
                            let option          = new Option(locname,
                                                             idlr, 
                                                             false, 
                                                             false);
                            // IE<8 does not create option element correctly
                            option.innerHTML    = locname;
                            option.value        = idlr; 
                            select.appendChild(option);
                        }           // loop through children of top node
                        select.selectedIndex    = 0;

                        // make the dialog visible
                        show(msgDiv);
                        // the following is a workaround for a bug in FF 40.0 and
                        // Chromium in which the onchange method of the <select> is
                        // not called when the mouse is clicked on an option
                        for(let io=0; io < select.options.length; io++)
                        {
                            let option  = select.options[io];
                            option.addEventListener("click",
                                                    function() 
                                                    {   this.selected = true;
                                                    this.parentNode.onchange();});
                        }
                        select.focus();
                    }           // multiple matching entries
                }               // name identifies Element
                else
                {               // element not found
                    alert("locationCommon.js: gotLocationJSON: element name='" +
                            field +
                            "' not found in form");
                }               // element not found
            }                   // have form
            else
            {                   // form not found
                alert("locationCommon.js: gotLocationJSON: form name='" + formname +
                        "' not found");
            }                   // form not found
        }                       // valid response
    }                           // response is a JSON object
    else
    {                           // response is not JSON
        alert(response);
    }                           // response is not JSON

    hideLoading();  // hide the "loading" indicator
}       // function gotLocationJSON

/************************************************************************
 *  function closeNewDialog                                             *
 *                                                                      *
 *  This closes (hides) the new location dialog and reenables the       *
 *  update button.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        the HTML <button> element                           *
 ************************************************************************/
function closeNewDialog()
{
    // no longer displaying the modal dialog popup
    let msgDiv              = document.getElementById('msgDiv');
    msgDiv.style.display    = 'none';   // hide
    deferSubmit             = false;
    let updateButton        = document.getElementById('updEvent');
    if (updateButton)
        updateButton.disabled   = false;

    let myform              = this.form;
    if (myform)
    {                           // the dialog includes a form
        let formname        = '';
        let field           = '';
        let elements        = myform.elements;
        for(let ie = 0; ie < elements.length; ie++)
        {
            let element     = elements[ie];
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

        if (formname.length > 0 && field.length > 0)
        {
            let mainForm    = document.forms[formname];
            let element     = mainForm.elements[field];
            if (element)
            {                   // found requested field in invoking form
                focusNext(element);
            }                   // found requested field in invoking form
            else
            {                   // issue diagnostic
                let elementList = '';
                let comma       = '[';
                for(let fieldname in mainForm.elements)
                {
                    elementList += comma + fieldname;
                    comma       = ',';
                }
                alert("locationCommon.js: closeNewDialog: cannot find input element with name='" + field + "' in form '" + formname + "' elements=" + elementList + "]");
            }                   // issue diagnostic
        }
    }                           // the dialog includes a form
    else
        alert("locationCommon.js: closeNewDialog: cannot find <form> in open dialog");
    return null;
}       // function closeNewDialog

/************************************************************************
 *  function locationChosen                                             *
 *                                                                      *
 *  This method is called when the user chooses a location from         *
 *  the dynamic selection list.                                         *
 *                                                                      *
 *  Input:                                                              *
 *      this                <select> element                            *
 *      ev                  instance of 'select' Event                  *
 ************************************************************************/
function locationChosen(ev)
{
    let chosenOption    = this.options[this.selectedIndex];

    if (chosenOption.value > 0)
    {       // ordinary entry
        let form        = document.forms[this.getAttribute("formname")];
        let elementName = this.getAttribute("for");
        let element     = form.elements[elementName];
        if (element)
        {
            if (element.value.charAt(0) == '[')
                element.value   = '[' + chosenOption.innerHTML + ']';
            else
                element.value   = chosenOption.innerHTML;

            // check for action to take after changed
            if (element.afterChange)
                element.afterChange();
            else
                focusNext(element);
        }
        else
            alert("locationCommon.js: locationChosen: cannot find input element with name='" + elementName + "'");
    }       // ordinary entry

    closeNewDialog.call(this);
}       // function locationChosen

/************************************************************************
 *  function focusNext                                                  *
 *                                                                      *
 *  This function sets the focus on the next input element after        *
 *  the supplied element.                                               *
 *                                                                      *
 *  Input:                                                              *
 *      element         instance of HtmlElement                         *
 ************************************************************************/
function focusNext(element)
{
    let form        = element.form;
    let elements    = form.elements;
    let searching   = true;
    let trace       = '';
    for (let ie = 0; ie < elements.length; ie++)
    {               // loop through form elements
        let e       = elements[ie];
        if (searching)
            trace   += ", searching " + e.outerHTML;
        else
            trace   += ", getNext " + e.outerHTML;
        if (e === element)
            searching   = false;
        else
        if (!searching)
        {           // get next active element
            if (!e.disabled)
            {
                e.focus();
                break;
            }
        }           // get next active element
    }               // loop through form elements
}       // function focusNext
