/************************************************************************
 *  OntarioPsychHospitalEmployeeLedgerPage.js                           *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  OntarioPsychHospitalEmployeeLedgerPage.php                          *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/13      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban.                              *
 ************************************************************************/

window.onload   = onLoad;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform dynamic initialization for the page                         *
 ************************************************************************/
function onLoad()
{
    let     namere      = /^([a-zA-Z_-]+)(\d+)$/;

    // enable support for hiding and revealing columns within a table
    let dataTable               = document.getElementById("dataTable");
    if (dataTable)
    {
        let tblHdr              = dataTable.tHead;
        let tblHdrRow           = tblHdr.rows[0];
        for(i = 0; i < tblHdrRow.cells.length; i++)
        {                   // loop through cells of header row
            let th              = tblHdrRow.cells[i];
            if (th.addEventListener)
            {
                th.addEventListener('click', columnClick, false);
                th.addEventListener('contextmenu', columnWiden, false);
            }
        }                   // loop through cells of header row
    }

    // define dynamic actions
    for(var i = 0; i < document.forms.length; i++)
    {                   // loop through all forms
        var form                = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {               // loop through all elements of a form
            var element         = form.elements[j];

            element.onkeydown   = keyDown;

            // an element whose value is passed with the update
            // request to the server is identified by a name= attribute
            // but elements which are used only by this script are
            // identified by an id= attribute
            let name            = element.name;
            let employee        = '';
            if (name.length == 0)
                name            = element.id;
            let results         = namere.exec(name);
            if (results)
            {
                name            = results[1];
                employee        = results[2];
            }

            let focusNotSet     = true;
            switch(name.toLowerCase())
            {               // act on individual fields
                case 'image':
                    let image   = element.value;
                    window.open("/DisplayImage.php?src=/LondonPsychiatricHospital/" + encodeURIComponent(image),'ledger');
                    break;

                case 'employee':
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkNumber;
                    element.checkfunc();
                    break;

                case 'givennames':
                    element.abbrTbl     = GivnAbbrs;
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    if (focusNotSet)
                        element.focus();
                    break;

                case 'surname':
                    element.abbrTbl     = SurnAbbrs;
                    element.onchange    = change;
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;

                case 'age':
                    element.abbrTbl     = AgeAbbrs;
                    element.onchange    = change;
                    element.checkfunc   = checkAge;
                    element.checkfunc();
                    break;

                case 'prevocc':
                    element.abbrTbl     = OccAbbrs;
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkOccupation;
                    element.checkfunc();
                    break;

                case 'prevres':
                    element.abbrTbl     = LocAbbrs;
                    element.onchange    = locationChanged;
                    element.checkfunc   = checkAddress;
                    element.checkfunc();
                    break;

                case 'service':
                    element.abbrTbl     = OccAbbrs;
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkOccupation;
                    element.checkfunc();
                    break;

                case 'dateemploy':
                    element.abbrTbl     = MonthAbbrs;
                    element.onchange    = dateChanged;
                    element.checkfunc   = checkDate;
                    element.checkfunc();
                    break;

                case 'datedisch':
                    element.abbrTbl     = MonthAbbrs;
                    element.onchange    = dateChanged;
                    element.checkfunc   = checkDate;
                    element.checkfunc();
                    break;

                case 'wages':
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkCurrency;
                    element.checkfunc();
                    break;

                case 'religion':
                    element.abbrTbl     = RlgnAbbrs;
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;

                case 'remarks':
                    element.onchange    = change;   // default handling
                    element.checkfunc   = checkText;
                    element.checkfunc();
                    break;

                case 'tree':
                    element.onclick     = clickTree;
                    break;

            }               // act on individual fields

            // override default key processing for input fields to provide
            // spreadsheet emulation
            if (element.nodeName.toUpperCase() == 'INPUT')
                element.addEventListener('keydown',   tableKeyDown);

        }                   // loop through all elements in the form
    }                       // loop through forms in the page

    hideRightColumn();
}       // function onLoad

/************************************************************************
 *  function clickTree                                                  *
 *                                                                      *
 *  Act when the "Find/Show" button is clicked.                         *
 *                                                                      *
 *  Input:                                                              *
 *      this          <button id='tree$employee'>                       *
 ************************************************************************/
function clickTree(ev)
{
    ev.stopPropagation();
    let button              = this;
    let employee            = button.id.substring(4);
    let idirElt             = document.getElementById('idir' + employee);
    let idir                = idirElt.value;
    if (idir)
    {                   // show the Person
        let url             = '/FamilyTree/Person.php?idir=' + idir +
                                "&lang=" + lang;
        openFrame('Person', url, 'right');
    }                   // show the person
    else
    {                   // search for the Person
        popupLoading(button);
        let form                = button.form;
        let surname             = form.elements['surname' + employee].value;
        let givennames          = form.elements['givennames' + employee].value;
        let age                 = form.elements['age' + employee].value;
        let agePattern          = /\d+/;
        let aresults            = age.match(agePattern);
        if (aresults)
            age                 = parseInt(aresults[0]);
        let dateEmployed        = form.elements['dateemploy' + employee].value;
        let yearPattern         = /\d\d\d\d/;
        let results             = dateEmployed.match(yearPattern);
        let curryear            = 1870;
        if (results)
            curryear            = parseInt(results[0]);
        let birthYear           = curryear - age;

        let url  = "/FamilyTree/getIndivNamesXml.php?Surname=" +
                        encodeURIComponent(surname) +
                        "&GivenName=" + encodeURIComponent(givennames) +
                        "&BirthYear=" + birthYear +
                        "&Range=5" +
                        "&buttonId=" + button.id +
                        "&includeParents=Y&includeSpouse=Y" +
                        "&incMarried=yes&loose=yes" +
                        "&lang=" + lang;
        if (debug.toLowerCase() == 'y')
            alert("OntarioPsychHospitalEmployeeLedgerPage.js: clickTree: HTTP.getXML('" + url + "')");
        HTTP.getXML(url,
                    gotIdir,
                    noIdir);
    }                   // search for the Person
}       // function clickTree

/************************************************************************
 *  function gotIdir                                                    *
 *                                                                      *
 *  The XML response to the database query for matching individuals has *
 *  been returned.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      XML document                                        *
 ************************************************************************/
function gotIdir(xmlDoc)
{
    if (debug.toLowerCase() == 'y')
        alert("OntarioPsychHospitalEmployeeLedgerPage.js: gotIdir: xmlDoc=" + new XMLSerializer().serializeToString(xmlDoc));
    let  rootNode   = xmlDoc.documentElement;
    let  buttonId   = rootNode.getAttribute("buttonId");
    let  button     = document.getElementById(buttonId);
    if (button === null)
    {
        hideLoading();
        alert("OntarioPsychHospitalEmployeeLedgerPage.js: gotIdir: unable to find element with id='" +
                buttonId + "' rootNode=" + new XMLSerializer().serializeToString(rootNode));
        return;
    }

    let form        = button.form;
    let line        = button.id.substring(4);
    let surname     = form.elements['surname' + line].value;
    let givennames  = form.elements['givennames' + line].value;
    let age         = parseInt(form.elements['age' + line].value);
    if (isNaN(age))
        age         = 30;
    let dateemploy  = form.elements['dateemploy' + line].value;
    let dateresults = dateemploy.match('/\d\d\d\d/');
    if (dateresults)
        dateemploy  = pareInt(dateresults[0]);
    else
        dateemploy  = 1870;
    let birthyear   = dateemploy - age;

    hideLoading();
    // substitutions into the template
    let parms       = {"sub"        : "",
                       "surname"    : surname,
                       "givenname"  : givennames,
                       "birthyear"  : birthyear,
                       "line"       : line};

    let matches     = xmlDoc.getElementsByTagName("indiv");
    if (matches.length > 0)
    {       // have some matching entries
        return displaySelectIdir('idirChooserForm$sub',
                                 parms,
                                 button,
                                 closeIdirDialog,
                                 matches);
    }       // have some matching entries
    else
    {       // have no matching entries
        let cmds  = xmlDoc.getElementsByTagName("cmd");
        parms.cmd  = new XMLSerializer().serializeToString(cmds[0]).replace('<','&lt;');
        return displayDialog('idirNullForm$sub',
                             parms,
                             button,
                             null);     // default close dialog
    }       // have no matching entries
}       // function gotIdir

/************************************************************************
 *  function noIdir                                                     *
 *                                                                      *
 *  The database server was unable to respond to the query.             *
 ************************************************************************/
function noIdir()
{
    alert("OntarioPsychHospitalEmployeeLedgerPage.js: noIdir: " +
          "unable to find getIndivNamesXml.php script on server");
}       // function noIdir

/************************************************************************
 *  function displaySelectIdir                                          *
 *                                                                      *
 *  This function displays a customized dialog for choosing from        *
 *  a list of individuals who match the individual described by the     *
 *  current line of the census.                                         *
 *                                                                      *
 *  Input:                                                              *
 *      templateId      identifier of an HTML element that provides the *
 *                      structure and constant strings to be laid out   *
 *                      in the dialog                                   *
 *      parms           an object containing values to substitute for   *
 *                      symbols ($xxxx) in the template                 *
 *      element         an HTML element used for positioning the        *
 *                      dialog for the user.  This is normally the      *
 *                      <button> for the user to request the dialog.    *
 *      action          onclick action to set for 1st (or only) button  *
 *                      in the dialog.  If null the default action is   *
 *                      to just hide the dialog.                        *
 *      matches         array of XML <indiv> tags                       *
 ************************************************************************/
function displaySelectIdir(templateId,
                           parms,
                           element,
                           action,
                           matches)
{
    let dialog              = displayDialog(templateId,
                                            parms,
                                            element,
                                            action,
                                            true);  // defer display
    if (dialog)
    {
        let forms           = dialog.getElementsByTagName('form');
        let form            = forms[0];

        // update the selection list with the matching individuals
        let select          = form.chooseIdir;
        if (select.addEventListener)
            select.addEventListener('change', idirSelected, false);

        // add the matches
        for (var i = 0; i < matches.length; ++i)
        {   // loop through the matches
            let  indiv      = matches[i];

            // get the "id" attribute
            let  value      = indiv.getAttribute("id");
            let  surname        = "";
            let  maidenname = "";
            let  givenname  = "";
            let  gender     = "";
            let  birthd     = "";
            let  deathd     = "";
            let  parents        = "";
            let  spouses        = "";

            for (var child = indiv.firstChild;
                 child;
                 child = child.nextSibling)
            {       // loop through all children of indiv
                if (child.nodeType == 1)
                {   // element node
                    switch(child.nodeName)
                    {   // act on specific child
                        case "surname":
                        {
                            surname  = child.textContent;
                            break;
                        }

                        case "maidenname":
                        {
                            maidenname  = child.textContent;
                            break;
                        }

                        case "givenname":
                        {
                            givenname  = child.textContent;
                            break;
                        }

                        case "gender":
                        {
                            gender  = child.textContent;
                            break;
                        }

                        case "birthd":
                        {
                            birthd  = child.textContent;
                            break;
                        }

                        case "deathd":
                        {
                            deathd  = child.textContent;
                            break;
                        }

                        case "parents":
                        {
                            parents  = child.textContent;
                            break;
                        }

                        case "families":
                        {
                            spouses  = child.textContent;
                            break;
                        }

                        default:
                        {
                            // alert("CensusForm.js:displaySelectIdir: " +
                            //    "nodeName='" + child.nodeName + "'");
                            break;
                        }
                    }   // act on specific child
                }   // element node
            }       // loop through all children of indiv

            let text  = surname;
            if (maidenname != surname)
                text  += " (" + maidenname + ")";
            text      += ", " + givenname + "(" +
                               birthd + "-" +
                               deathd + ")";
            if (parents.length > 0)
                text  += ", child of " + parents;
            if (spouses.length > 0)
                text  += ", spouse of " + spouses;

            // add a new HTML Option object
            addOption(select,   // Select element
                      text, // text value
                      value);   // unique key
        }   // loop through the matches

        select.selectedIndex  = 0;

        // show the dialog
        dialog.style.visibility  = 'visible';
        dialog.style.display  = 'block';
        // the following is a workaround for a bug in FF 40.0 and Chromium
        // in which the onchange method of the <select> is not called when
        // the mouse is clicked on an option
        for(var io=0; io < select.options.length; io++)
        {
            let option  = select.options[io];
            evt             = new Event('change',{'bubbles':true});
            option.addEventListener("click",
                                    function(event) {
                                        event.stopPropagation(); 
                                        this.selected = true; 
                                        this.parentNode.dispatchEvent(evt);
                                    });
        }
        select.focus();
        return true;
    }       // template OK
    else
        return false;
}       // function displaySelectIdir

/************************************************************************
 *  function closeIdirDialog                                            *
 *                                                                      *
 *  The user clicked on the button to close the IDIR dialog.            *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button>                                *
 ************************************************************************/
function closeIdirDialog(event)
{
    event.stopPropagation();
    let  form                           = this.form;
    let select                          = form.chooseIdir;
    if (select)
    {                               // select for IDIR present
        let index                       = select.selectedIndex;
        if (index >= 0)
        {                           // option chosen
            let option                  = select.options[index];
            let idir                    = option.value;
            if (idir > 0)
            {                       // individual chosen
                let line                = this.id.substring(6);
                let mainForm            = document.divForm;
                mainForm.elements["idir" + line].value      = idir;
                let findButton          = mainForm.elements["tree" + line];
                while(findButton.hasChildNodes())
                {                   // remove contents of cell
                    findButton.removeChild(findButton.firstChild);
                }                   // remove contents of cell
                findButton.appendChild(document.createTextNode("Show"));
                findButton.className    = 'green';
                let cell                = findButton.parentNode;
                /*
                let clearButton  = document.getElementById("clearIdir" + line);
                if (clearButton === undefined || clearButton === null)
                {                   // need to add clear button
                    clearButton             = document.createElement("BUTTON");
                    clearButton.type        = 'button';
                    clearButton.id          = "clearIdir" + line;
                    clearButton.className   = 'button';
                    clearButton.appendChild(document.createTextNode("Clear"));
                    cell.appendChild(clearButton);
                    if (clearButton.addEventListener)
                        clearButton.addEventListener('click', clearIdir, false);
                }                   // need to add clear button
                let setFlag  = document.getElementById("setIdir" + line);
                if (setFlag === undefined || setFlag === null)
                {                   // need to add set field
                    setFlag                 = document.createElement("INPUT");
                    setFlag.type            = 'hidden';
                    setFlag.id              = "setIdir" + line;
                    setFlag.name            = "setIdir" + line;
                    cell.appendChild(setFlag);
                }                   // need to add set field
                setFlag.value  = idir;
                */
            }                       // individual chosen
        }                           // option chosen
    }                               // select for IDIR present

    // hide the dialog
    for (var div = this.parentNode; div; div = div.parentNode)
    {                               // loop up the element tree
        if (div.nodeName.toLowerCase() == 'div')
        {
            div.style.display  = 'none';    // hide
            break;
        }
    }                               // loop up the element tree


    return false;                   // suppress default action
}       // function closeIdirDialog

/************************************************************************
 *  function idirSelected                                               *
 *                                                                      *
 *  This is the onchange method of the select in the popup to choose    *
 *  the individual to associated with the current line.                 *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select id='chooseIdir'>                            *
 ************************************************************************/
function idirSelected()
{
    let  select         = this;
    let  idir           = 0;
    let  index          = select.selectedIndex;
    if (index >= 0)
    {
        let  option     = select.options[index];
        idir            = option.value;
    }
    let  form           = this.form;    // <form name='idirChooserForm'>

    for(var ie = 0; ie < form.elements.length; ie++)
    {       // search for choose button
        let  element    = form.elements[ie];
        if (element != select &&
            element.id && element.id.length >= 6 &&
            element.id.substring(0,6) == "choose")
        {   // have the button
            if (idir == 0)
                element.innerHTML  = 'Cancel';
            else
                element.innerHTML  = 'Select';
        }   // have the button
    }       // search for choose button
}       // function idirSelected
