/************************************************************************
 *  CountyMarriagesEdit.js                                              *
 *                                                                      *
 *  This file implements the dynamic functionality of the web pages     *
 *  CountyMarriagesEdit.php and DistrictMarriagesEdit.php               *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/30      created                                         *
 *      2016/03/19      use popup menus for selection                   *
 *      2016/03/22      use longer given name prefix for common names   *
 *      2016/05/31      use common function dateChanged                 *
 *      2016/11/14      add dynamic support for new columns             *
 *                      copy value of date and license type to bride    *
 *                      share initialization code between initial load  *
 *                      and adding new lines                            *
 *      2017/01/11      add ability to hide columns                     *
 *      2017/01/12      include age when calculating birth year         *
 *                      for link button                                 *
 *      2017/01/13      add "Clear" button to remove linkage            *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/06/29      first parameter of displayDialog removed        *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *      2021/03/03      separate function of Link and Find buttons      *
 *                      use ECMA 2015 syntax                            *
 *      2022/02/06      use record identifier from individual row       *
 *                      if not supplied in parameters to script         *
 *      2023/08/10      use addEventListener                            *
 *                      use regexp match to extract row number          *
 *                      do not  open new windows for functions          *
 *                      use let in place of var                         *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/

window.addEventListener('load', onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize the dynamic functionality once the page is loaded        *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    let element;
    let trace               = '';
    for (let fi = 0; fi < document.forms.length; fi++)
    {       // loop through all forms
        let form            = document.forms[fi];

        for (let i = 0; i < form.elements.length; ++i)
        {   // loop through all elements of form
            element         = form.elements[i];

            initElement(element);
        }       // loop through all elements in the form
    }           // loop through all forms

    // enable support for hiding and revealing columns within a table
    let dataTable           = document.getElementById("dataTable");
    let tblHdr              = dataTable.tHead;
    let tblHdrRow           = tblHdr.rows[0];
    for(i = 0; i < tblHdrRow.cells.length; i++)
    {           // loop through cells of header row
        let th              = tblHdrRow.cells[i];
        th.addEventListener('click', columnClick);
        th.addEventListener('contextmenu', columnWiden);
    }           // loop through cells of header row

    hideRightColumn();
}       // function onLoad

/************************************************************************
 *  function linkToTree                                                 *
 *                                                                      *
 *  When a Link button is clicked a window is opened either to display  *
 *  an existing individual in the tree or to search for a match.        *
 *                                                                      *
 *  Input:                                                              *
 *      $this               <button type=button id='Link....'           *
 ************************************************************************/
function linkToTree()
{
    let form                = this.form;
    let rownum              = this.id.match(/\d*$/);
    let element, idir, script;

    element                 = document.getElementById('IDIR' + rownum);
    if (element)
        idir                = element.value;
    else
        idir                = 0;

    window.open('../FamilyTree/Person.php?idir=' + idir);
}       // function linkToTree

/************************************************************************
 *  function findInTree                                                 *
 *                                                                      *
 *  When a Find button is clicked a window is opened  to search         *
 *  for a match in the Family Tree.                                     *
 *                                                                      *
 *  Input:                                                              *
 *      $this               <button type=button id='Find....'           *
 ************************************************************************/
function findInTree()
{
    let form                = this.form;
    let rownum              = this.id.match(/\d*$/);
    let element, idir, script;

    element                 = document.getElementById('IDIR' + rownum);
    if (element)
        idir                = element.value;
    else
        idir                = 0;

    let msgDiv              = document.getElementById('msgDiv');
    msgDiv.style.display    = 'none';
    let role                = form.elements['Role' + rownum].value;
    let surname             = form.elements['Surname' + rownum].value;
    let given               = form.elements['GivenNames' + rownum].value;
    if (given.substring(0,4) == 'Mary' ||
        given.substring(0,4) == 'John')
        given               = given.substring(0,4);
    else
        given               = given.substring(0,2);
    let date                = form.elements['Date' + rownum].value;
    let ageElement          = form.elements['Age' + rownum];
    let age                 = '';
    if (ageElement)
        age                 = ageElement.value;
    let birthmin            = 1750;
    let birthmax            = 1856;
    if (date.length >= 4)
    {
        let matches         = /\d\d\d\d/.exec(date);
        if (Array.isArray(matches))
        {               // year of marriage
            let year        = parseInt(matches[0]);

            if (age.length == 0 || isNaN(age))
            {
                birthmin    = year - 80;
                birthmax    = year - 16;
            }
            else
            {
                birthmin    = year - (age - 0 + 5);
                birthmax    = year - (age - 5);
            }
        }               // year of marriage
    }

    let sex                 = 'M';
    if (role == 'B')
        sex                 = 'F';

    let url = "/FamilyTree/getIndivNamesXml.php?Surname=" +
                    encodeURIComponent(surname) +
                    "&GivenName=" + encodeURIComponent(given) +
                    "&Sex=" + sex +
                    "&BirthMin=" + birthmin +
                    "&BirthMax=" + birthmax +
                    "&buttonId=" + this.id +
                    "&includeSpouse=Y" +
                    "&incMarried=yes&loose=yes";

    HTTP.getXML(url,
                gotIdir,
                noIdir);

    return false;
}       // function linkToTree

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
    let rootNode                = xmlDoc.documentElement;
    let buttonId                = rootNode.getAttribute("buttonId");
    let button                  = document.getElementById(buttonId);
    if (button === null)
    {
        let msgDiv              = document.getElementById('msgDiv');
        msgDiv.style.display    = 'none';
        alert("CountyMarriagesEdit.js: gotIdir: unable to find element with id='" +
            buttonId + "' rootNode=" + new XMLSerializer().serializeToString(rootNode));
        return;
    }

    let form                    = button.form;
    let line                    = buttonId.substring(4);
    let surname                 = form.elements['Surname' + line].value;
    let givennames              = form.elements['GivenNames' + line].value;
    let birthmin                = 1750;
    let birthmax                = 1852;

    let parmElts                = xmlDoc.getElementsByTagName("parms");
    let parmElt                 = parmElts[0];
    for (let elt = parmElt.firstChild; elt; elt = elt.nextSibling)
    {
        if (elt.nodeType == 1)
        {       // is an element node
            switch (elt.nodeName.toLowerCase())
            {   // act on specific parameter names
                case 'birthmin':
                    birthmin    = elt.textContent;
                    break;

                case 'birthmax':
                    birthmax    = elt.textContent;
                    break;

            }   // act on specific parameter names
        }       // is an element node
    }       // loop through elements under <parms>

    // substitutions into the template
    let parms       = {"sub"        : "",
                       "surname"    : surname,  
                       "givenname"  : givennames,
                       'birthmin'   : birthmin,
                       'birthmax'   : birthmax,
                       "line"       : line};

    let matches = xmlDoc.getElementsByTagName("indiv");
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
    alert("CountyMarriagesEdit.js: noIdir: " +
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
    let dialog  = displayDialog(templateId,
                                parms,
                                element,
                                action,
                                true);
    if (dialog)
    {
        // update the selection list with the matching individuals
        let select  = document.getElementById("chooseIdir");
        select.addEventListener('change', idirSelected);
        //select.addEventListener('click', function)() {alert("select.onclick");};

        // add the matches
        for (let i = 0; i < matches.length; ++i)
        {   // loop through the matches
            let indiv   = matches[i];

            // get the "id" attribute
            let value       = indiv.getAttribute("id");
            let surname     = "";
            let maidenname  = "";
            let givenname   = "";
            let gender      = "";
            let birthd      = "";
            let deathd      = "";
            let parents     = "";
            let spouses     = "";

            for (let child = indiv.firstChild;
             child;
             child = child.nextSibling)
            {       // loop through all children of indiv
            if (child.nodeType == 1)
            {   // element node
                switch(child.nodeName)
                {   // act on specific child
                case "surname":
                {
                    surname = child.textContent;
                    break;
                }

                case "maidenname":
                {
                    maidenname  = child.textContent;
                    break;
                }

                case "givenname":
                {
                    givenname   = child.textContent;
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
                    parents = child.textContent;
                    break;
                }

                case "families":
                {
                    spouses = child.textContent;
                    break;
                }

                default:
                {
                    // alert("CountyMarriagesEdit.js:displaySelectIdir: " +
                    //    "nodeName='" + child.nodeName + "'");
                    break;
                }
                }   // act on specific child
            }   // element node
            }       // loop through all children of indiv

            let text    = surname;
            if (maidenname != surname)
            text    += " (" + maidenname + ")";
            text    += ", " + givenname + "(" + 
                   birthd + "-" +
                   deathd + ")";
            if (parents.length > 0)
            text    += ", child of " + parents;
            if (spouses.length > 0)
            text    += ", spouse of " + spouses;

            // add a new HTML Option object
            addOption(select,   // Select element
                  text, // text value 
                  value);   // unique key
        }   // loop through the matches

        select.selectedIndex    = 0;

        // show the dialog
        dialog.style.visibility = 'visible';
        dialog.style.display    = 'block';
        // the following is a workaround for a bug in FF 40.0 and Chromium
        // in which the onchange method of the <select> is not called when
        // the mouse is clicked on an option
        for(let io=0; io < select.options.length; io++)
        {
            let option  = select.options[io];
            option.addEventListener("click", function() {this.selected = true; this.parentNode.onchange();});
        }
        select.focus();
        return true;
    }       // template OK
    else
        return false;
}       // function displaySelectIdir

/************************************************************************
 *  function idirSelected                                               *
 *                                                                      *
 *  This is the onchange method of the select in the popup to choose    *
 *  the individual to associated with the current line.                 *
 *                                                                      *
 *  Input:                                                              *
 *      this        = <select id='chooseIdir'>                          *
 ************************************************************************/
function idirSelected()
{
    let select  = this;
    let idir    = 0;
    let index   = select.selectedIndex;
    if (index >= 0)
    {
        let option  = select.options[index];
        idir    = option.value;
    }
    let form    = this.form;    // <form name='idirChooserForm'>

    for(let ie = 0; ie < form.elements.length; ie++)
    {       // search for choose button
        let element = form.elements[ie];
        if (element != select &&
            element.id && element.id.length >= 6 && 
            element.id.substring(0,6) == "choose")
        {   // have the button
            if (idir == 0)
            element.innerHTML   = 'Cancel';
            else
            element.innerHTML   = 'Select';
        }   // have the button
    }       // search for choose button
}       // function idirSelected

/************************************************************************
 *  function closeIdirDialog                                            *
 *                                                                      *
 *  The user clicked on the button to close the IDIR dialog.            *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button>                                *
 ************************************************************************/
function closeIdirDialog()
{
    let form    = this.form;
    let select  = form.chooseIdir;
    if (select)
    {       // select for IDIR present
        if (select.selectedIndex >= 0)
        {   // option chosen
            let option          = select.options[select.selectedIndex]; 
            let idir            = option.value;
            if (idir > 0)
            {   // individual chosen
            let line            = this.id.substring(6);
            let mainForm        = document.countyForm;
            mainForm.elements["IDIR" + line].value      = idir;
            // remove "find" button from cell
            let findid          = "Find" + line;
            let findButton      = document.getElementById(findid);
            if (findButton)
            {
                let cell        = findButton.parentNode;
                cell.removeChild(findButton);
                // add "tree" linked button
                let parms       = {'row'    : line};
                let template    = document.getElementById('Link$row');
                let linkButton  = createFromTemplate(template,
                                                     parms,
                                                     null);
                let newButton   = cell.appendChild(linkButton);
                newButton.addEventListener('click', linkToTree);
                // add "clear" button to remove link to tree
                template        = document.getElementById('Clear$row');
                let clearButton = createFromTemplate(template,
                                                     parms,
                                                     null);
                newButton       = cell.appendChild(clearButton);
                newButton.addEventListener('click', clearFromTree);
            }
            else
                alert("Cannot find element with name '" + findid + "'");
            }   // individual chosen
        }   // option chosen
    }       // select for IDIR present

    let msgDiv  = document.getElementById('msgDiv');
    msgDiv.style.display    = 'none';

    // suppress default action
    return false;
}       // function closeIdirDialog

/************************************************************************
 *  function clearFromTree                                              *
 *                                                                      *
 *  The user clicked on the button to remove an existing linkage to     *
 *  the family tree.                                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button>                                *
 ************************************************************************/
function clearFromTree()
{
    let form            = this.form;
    let line            = this.id.match(/\s*$/);
    // clear linkage
    form.elements["IDIR" + line].value      = 0;
    // remove "Tree" button from cell
    let treeButton      = form.elements["Link" + line];
    let cell            = treeButton.parentNode;
    cell.removeChild(treeButton);   
    // remove "Clear" button from cell
    let clearButton     = form.elements["Clear" + line];
    cell.removeChild(clearButton);  
    // add "find" linked button
    let parms           = {'rowf'   : line};
    let template        = document.getElementById('Link$rowf');
    let findButton      = createFromTemplate(template,
                                             parms,
                                             null);
    let newButton       = cell.appendChild(findButton);
    newButton.addEventListener('click', linkToTree);

    // suppress default action
    return false;
}       // function clearFromTree

/************************************************************************
 *  function showDetails                                                *
 *                                                                      *
 *  When a Details button is clicked this function displays the         *
 *  detailed information about the marriage.                            *
 *  Temporarily this just displays the two rows associated with the     *
 *  current item.                                                       *
 *                                                                      *
 *  Input:                                                              *
 *      $this       <button type=button id='Details....'                *
 ************************************************************************/
function showDetails()
{
    let form            = this.form;
    let rownum          = this.id.match(/\d*$/);
    let domain, volume, reportNo, element;

    element             = form.elements['Domain' + rownum];
    if (element)
        domain          = element.value;
    else
    if (form.Domain && form.Domain.value != '')
        domain          = form.Domain.value;
    else
        popupAlert("showDetails: cannot find Domain identifier",
                   this);

    element             = form.elements['Volume' + rownum];
    if (element)
        volume          = element.value;
    else
    if (form.Volume && form.Volume.value != '')
        volume          = form.Volume.value;
    else
        popupAlert("showDetails: cannot find Volume number",
                   this);

    element             = form.elements['ReportNo' + rownum];
    if (element)
        reportNo        = element.value;
    else
    if (form.ReportNo && form.ReportNo.value != '')
        reportNo        = form.ReportNo.value;
    else
        popupAlert("showDetails: cannot find ReportNo",
                   this);

    let itemNo          = form.elements['ItemNo' + rownum].value;
    let script;
    if (domain == 'CAUC')
        script          = 'DistrictMarriagesEdit.php?Domain=' + domain +
                                '&Volume=' + volume +
                                '&ReportNo=' + reportNo +
                                '&ItemNo=' + itemNo;
    else
        script          = 'CountyMarriagesEdit.php?Domain=' + domain +
                                '&Volume=' + volume +
                                '&ReportNo=' + reportNo +
                                '&ItemNo=' + itemNo;

    location            = script;
    return false;
}       // function showDetails

/************************************************************************
 *  function deleteRow                                                  *
 *                                                                      *
 *  When a Delete button is clicked this function removes the           *
 *  row from the table.                                                 *
 *                                                                      *
 *  Input:                                                              *
 *      $this           <button type=button id='Delete....'             *
 ************************************************************************/
function deleteRow()
{
    let form                = this.form;
    let rownum              = this.id.match(/\d*$/);
    let domain, volume, reportNo, element;

    element                 = form.elements['Domain' + rownum];
    if (element)
        domain              = element.value;
    else
    if (form.Domain)
        domain              = form.Domain.value;
    else
        popupAlert("showDetails: cannot find Domain field",
                    this);

    element                 = form.elements['Volume' + rownum];
    if (element)
        volume              = element.value;
    else
    if (form.Volume)
        volume              = form.Volume.value;
    else
       popupAlert("showDetails: cannot find Volume field",
                  this);

    element                 = form.elements['ReportNo' + rownum];
    if (element)
        reportNo            = element.value;
    else
    if (form.ReportNo)
        reportNo            = form.ReportNo.value;
    else
        popupAlert("showDetails: cannot find ReportNo field",
                    this);

    let itemNo              = form.elements['ItemNo' + rownum].value;
    let role                = form.elements['Role' + rownum].value;
    //alert("deleteRow: domain='" + domain + "', volume=" + volume + ", reportNo=" + report);
    let script              = 'deleteCountyMarriageXml.php';
    let parms               = { 'Domain'    : domain,
                                'Volume'    : volume,
                                'reportNo'  : reportNo,
                                'itemNo'    : itemNo,
                                'role'      : role,
                                'rownum'    : rownum};
    if (debug != 'n')
        parms["debug"]      = debug;

    // update the citation in the database
    HTTP.post(  script,
            parms,
            gotDelete,
            noDelete);
    return false;
}       // function deleteRow

/************************************************************************
 *  function gotDelete                                                  *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the deletion of the report from the database is retrieved.          *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      response document                                   *
 ************************************************************************/
function gotDelete(xmlDoc)
{
    if (xmlDoc === undefined)
    {
        alert("CountyMarriagesEdit.js: gotDelete: xmlDoc is undefined!");
    }
    else
    {                   // xmlDoc is defined
        let root            = xmlDoc.documentElement;
        console.log("gotDelete: " +
                    new XMLSerializer().serializeToString(root));
        let parms           = root.getElementsByTagName('parms');
        if (parms.length > 0)
        {               // have at least 1 parms element
            parms           = parms[0];
            let rownums     = parms.getElementsByTagName('rownum');
            if (rownums.length > 0)
            {           // have at least 1 rownum element
                let child   = rownums[0];
                let rownum  = child.textContent.trim();
                // remove identified row
                let rowid   = 'Row' + rownum;
                let row     = document.getElementById(rowid);
                let section = row.parentNode;
                section.removeChild(row);
            }           // have at least 1 rownum element
        }               // have at least 1 parms element
    }                   // xmlDoc is defined
}       // function gotDelete

/************************************************************************
 *  function noDelete                                                   *
 *                                                                      *
 *  This method is called if there is no delete registration script.    *
 ************************************************************************/
function noDelete()
{
    alert("CountyMarriagesEdit.js: noDelete: " +
            "script 'deleteCountyMarriagesXml.php' not found on server");
}       // function noDelete

/************************************************************************
 *  function checkFlagBG                                                *
 *                                                                      *
 *  Validate the current value of a field containing a flag.            *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkFlagBG()
{
    let elt                 = this;
    let re                  = /^[BGbg ]?$/;
    let flag                = elt.value;
    let className           = elt.className;
    if (className.substring(className.length - 5) == 'error')
    {                   // error currently flagged
        // if valid value, clear the flag
        if (re.test(flag))
            elt.className   = className.substring(0, className.length - 5);
    }                   // error currently flagged
    else
    {                   // error not currently flagged
        // if in error add flag to class name
        if (!re.test(flag))
            elt.className   = elt.className + "error";
    }                   // error not currently flagged
}       // function checkFlagBG

/************************************************************************
 *  function checkFlagBL                                                *
 *                                                                      *
 *  Validate the current value of a field containing a flag.            *
 *                                                                      *
 *  Input:                                                              *
 *      this        an instance of an HTML input element.               *
 ************************************************************************/
function checkFlagBL()
{
    let elt                 = this;
    let re                  = /^[BLbl ]?$/;
    let flag                = elt.value;
    let className           = elt.className;
    if (className.substring(className.length - 5) == 'error')
    {                   // error currently flagged
        // if valid value, clear the flag
        if (re.test(flag))
            elt.className   = className.substring(0, className.length - 5);
    }                   // error currently flagged
    else
    {                   // error not currently flagged
        // if in error add flag to class name
        if (!re.test(flag))
            elt.className   = elt.className + "error";
    }                   // error not currently flagged
}       // function checkFlagBL

/************************************************************************
 *  function tableKeyDown                                               *
 *                                                                      *
 *  Handle key strokes in text input fields in a row.                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    input element                                           *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function tableKeyDown(ev)
{
    if (!ev)
    {                           // browser is not W3C compliant
        ev                          =  window.event;    // IE
    }                           // browser is not W3C compliant
    let code                        = ev.key;
    let element                     = ev.target;
    let form                        = element.form;
    let rc                          = true;

    // hide the help balloon on any keystroke
    if (helpDiv)
    {                           // helpDiv currently displayed
        helpDiv.style.display       = 'none';
        helpDiv                     = null; // no longer displayed
    }                           // helpDiv currently displayed
    clearTimeout(helpDelayTimer);   // clear pending help display
    helpDelayTimer                  = null;

    // take action based upon code
    switch (code)
    {
        case "F1":              // F1
            displayHelp(this);  // display help page
            rc			            = false;
			break;              // F1

        case "Enter":
            if (element)
            {
                let cell            = element.parentNode;
                let row             = cell.parentNode;
                let body            = row.parentNode;
                let rownum          = row.sectionRowIndex;
                if (rownum < (body.rows.length - 1))
                {               // not the last row
                    rownum++;
                    row             = body.rows[rownum];
                    let focusSet    = false;
                    let itemNo      = 0;
                    let names       = '';
                    for (let itd = 0; itd < row.cells.length; itd++)
                    {           // loop through <td>s
                        cell        = row.cells[itd];
                        let children= cell.children;
                        for (let ic = 0; ic < children.length; ic++)
                        {       // loop through children of cell
                            let child   = children[ic];
                            if (child.nodeName.toLowerCase() == 'input' &&
                                child.type == 'text')
                            {   // <input type='text'>
                                if (!child.readOnly)
                                {
                                    child.focus();
                                    child.select();
                                    focusSet    = true;
                                }
                                break;
                            }   // first <input type='text'>
                        }       // loop through children of cell
                        if (focusSet)
                            break
                    }           // loop through <td>
                }               // not the last row
                else
                {               // last row, add new row
                    let itemNo          = 0;
                    for (let itd = 0; itd < row.cells.length; itd++)
                    {           // loop through <td>s
                        cell            = row.cells[itd];
                        let children    = cell.children;
                        for (let ic = 0; ic < children.length; ic++)
                        {       // loop through children of cell
                            let child   = children[ic];
                            if (child.nodeName.toLowerCase() == 'input' &&
                            child.type == 'text')
                            {   // <input type='text'>
                                if (child.name.substring(0,6) == 'ItemNo')
                                {
                                    itemNo  = child.value;
                                    break;
                                }
                            }   // first <input type='text'>
                        }       // loop through children of cell
                    }           // loop through <td>
                    let rowa        = rownum + 2;
                    if (rowa < 10)
                        rowa        = '0' + rowa.toString();
                    let rowb        = rownum + 3;
                    if (rowb < 10)
                        rowb        = '0' + rowb.toString();
                    itemNo          = (itemNo-0) + 1;
                    let parms       = {'rowa'   : rowa,
                                       'rowb'   : rowb,
                                       'itemNo' : itemNo};

                    // add new row for groom
                    let template    = document.getElementById('Row$rowa');
                    let newRow      = createFromTemplate(template,
                                                         parms,
                                                         null);
                    newrow  = body.appendChild(newRow);
                    let inputs      = newRow.getElementsByTagName('input');
                    for (let ii = 0; ii < inputs.length; ii++)
                    {
                        let element = inputs[ii];
                        initElement(element);
                    }

                    // add new row for bride
                    template        = document.getElementById('Row$rowb');
                    newRow          = createFromTemplate(template,
                                                         parms,
                                                         null);
                    newRow          = body.appendChild(newRow);
                    inputs          = newRow.getElementsByTagName('input');
                    for (let ii = 0; ii < inputs.length; ii++)
                    {
                        let element = inputs[ii];
                        initElement(element);
                    }
                }               // last row, add new row
            }                   // have element
            else
                alert("commonMarriage.js: tableKeyDown: element is null.");
            rc			            = false;
			break;              // enter key

        case "ArrowUp":
            if (element)
            {
                let cell            = element.parentNode;
                let row             = cell.parentNode;
                let body            = row.parentNode;
                let rownum          = row.sectionRowIndex;
                if (rownum > 0)
                {               // not the first row
                    rownum--;
                    row             = body.rows[rownum];
                    cell            = row.cells[cell.cellIndex];
                    let children= cell.children;
                    for (let ic = 0; ic < children.length; ic++)
                    {           // loop through children of cell
                        let child   = children[ic];
                        if (child.nodeName.toLowerCase() == 'input' &&
                            child.type == 'text')
                        {       // first <input type='text'>
                            child.focus();
                            break;
                        }       // first <input type='text'>
                    }           // loop through children of cell
                }               // not the first row
            }
            else
                alert("commonMarriage.js: tableKeyDown: element is null.");
            rc			            = false;
			break;              // arrow up key

        case "ArrowDown":
            if (element)
            {
                let cell            = element.parentNode;
                let row             = cell.parentNode;
                let body            = row.parentNode;
                let rownum          = row.sectionRowIndex;
                if (rownum < (body.rows.length - 1))
                {               // not the last row
                    rownum++;
                    row             = body.rows[rownum];
                    cell            = row.cells[cell.cellIndex];
                    let children= cell.children;
                    for (let ic = 0; ic < children.length; ic++)
                    {           // loop through children of cell
                        let child   = children[ic];
                        if (child.nodeName.toLowerCase() == 'input' &&
                            child.type == 'text')
                        {       // first <input type='text'>
                            child.focus();
                            break;
                        }       // first <input type='text'>
                    }           // loop through children of cell
                }               // not the last row
            }
            else
                alert("commonMarriage.js: tableKeyDown: element is null.");
            rc			            = false;
			break;              // arrow down key
            //
    }                           // switch on key code

    if (rc)
        return true;

    ev.stopPropagation();
    ev.preventDefault();
    return false
}       // function tableKeyDown

/************************************************************************
 *  function initElement                                                *
 *                                                                      *
 *  Initialize a form element.                                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      element     instance of HTMLElement                             *
 ************************************************************************/
function initElement(element)
{
    element.addEventListener('keydown', keyDown);

    let namePattern             = /^([a-zA-Z_]+)(\d*)$/;
    let id                      = element.id;
    if (id.length == 0)
        id                      = element.name;
    let rresult                 = namePattern.exec(id);
    let column                  = id;
    let rownum                  = '';
    if (rresult !== null)
    {
        column                  = rresult[1];
        rownum                  = rresult[2];
    }

    switch(column.toLowerCase())
    {                           // act on column name
        case 'domain':
            element.addEventListener('keydown', keyDown); 
            element.addEventListener('change', change);  
            element.checkfunc   = checkText;
            element.checkfunc();
            break;              // domain field

        case 'volume':
        case 'reportno':
            element.addEventListener('keydown', keyDown); 
            element.addEventListener('change', change);  
            element.checkfunc   = checkNumber;
            element.checkfunc();
            break;              // numeric fields

        case 'itemno':
            element.addEventListener('keydown', tableKeyDown); 
            element.addEventListener('change', change);
            element.checkfunc   = checkNumber;
            element.checkfunc();
            break;              // itemno

        case 'role':
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', change);
            element.checkfunc   = checkFlagBG;
            element.checkfunc();
            break;              // role

        case 'givennames':
        case 'fathername':
        case 'mothername':
        case 'witnessname':
            element.abbrTbl = GivnAbbrs;
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', change);
            element.checkfunc   = checkName;
            element.checkfunc();
            break;              // given names field

        case 'surname':
            element.abbrTbl = SurnAbbrs;
            element.addEventListener('change', change);
            element.addEventListener('keydown', tableKeyDown);
            element.checkfunc   = checkName;
            element.checkfunc();
            break;              // surname field

        case 'age':
            element.addEventListener('change', change);
            element.addEventListener('keydown', tableKeyDown);
            element.checkfunc   = checkAge;
            element.checkfunc();
            break;              // age field

        case 'residence':
        case 'birthplace':
            element.abbrTbl = LocAbbrs;
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', change);
            element.checkfunc   = checkAddress;
            element.checkfunc(); 
            break;              // location fields

        case 'date':
            element.abbrTbl = MonthAbbrs;
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', marriageDateChanged);
            element.checkfunc   = checkDate;
            element.checkfunc();
            break;              // date field

        case 'licensetype':
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', licenseTypeChanged);
            element.checkfunc   = checkFlagBL;
            element.checkfunc();
            break;              // licensetype

        case 'witnessname':
            element.abbrTbl     = GivnAbbrs;
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', change);
            element.checkfunc   = checkName;
            element.checkfunc();
            break;              // witness names field

        case 'remarks':
            element.addEventListener('keydown', tableKeyDown);
            element.addEventListener('change', change);
            element.checkfunc   = checkText;
            element.checkfunc();
            break;              // remarks

        case 'link':
            element.addEventListener('click', linkToTree);
            break;              // link button

        case 'find':
            element.addEventListener('click', findInTree);
            break;              // find button

        case 'clear':
            element.addEventListener('click', clearFromTree);
            break;              // clear button

        case 'details':
            element.addEventListener('click', showDetails);
            break;              // details button

        case 'delete':
            element.addEventListener('click', deleteRow);
            break;              // delete button

    }                           // act on column name
}       // function initElement

/************************************************************************
 *  function marriageDateChanged                                        *
 *                                                                      *
 *  Take action when the user changes the marriage date field           *
 *                                                                      *
 *  Input:                                                              *
 *      this        an instance of an HTML input element.               *
 ************************************************************************/
function marriageDateChanged(ev)
{
    let form        = this.form;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    let value       = this.value;
    value           = value.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    this.value      = value.replace(/(\d)([a-zA-Z])/g,"$1 $2");

    changeElt(this);    // change case and expand abbreviations

    if (this.checkfunc)
        this.checkfunc();

    let rownum      = this.id.match(/\d*$/);
    let roleElement = form.elements['Role' + rownum];
    if (roleElement && roleElement.value.toUpperCase() == 'G')
    {
        let brownum = (rownum - 0) + 1;
        if (brownum < 10)
        {
            brownum = "0" + brownum;
        }
        let brideDateName       = 'Date' + brownum;
        let brideDateElement    = form.elements[brideDateName];
        if (brideDateElement)
            brideDateElement.value  = this.value;
        else
            alert("Unable to find element with name='" + brideDateName + "'");
    }

    ev.stopPropagation();
    return false;
}       // function marriageDateChanged

/************************************************************************
 *  function licenseTypeChanged                                         *
 *                                                                      *
 *  Take action when the user changes the licence type field            *
 *                                                                      *
 *  Input:                                                              *
 *      this        an instance of an HTML input element.               *
 ************************************************************************/
function licenseTypeChanged(ev)
{
    let form        = this.form;
    changeElt(this);    // change case and expand abbreviations

    if (this.checkfunc)
        this.checkfunc();

    let rownum              = this.id.match(/\d*$/);
    let roleName            = 'Role' + rownum;
    let roleElement         = form.elements[roleName];
    if (roleElement && roleElement.value.toUpperCase() == 'G')
    {
        let brownum         = (rownum - 0) + 1;
        if (brownum < 10)
        {
            brownum         = "0" + brownum;
        }
        let brideLtName     = 'LicenseType' + brownum;
        let brideLtElement  = form.elements[brideLtName];
        if (brideLtElement)
            brideLtElement.value    = this.value;
        else
            alert("Unable to find element with name='" + brideLtName + "'");
    }

    ev.stopPropagation();
    return false;
}       // function licenseTypeChanged
