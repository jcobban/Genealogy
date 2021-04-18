/************************************************************************
 *  ReqUpdatePages.js                                                   *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  ReqUpdatePages.html                                                 *
 *                                                                      *
 *  History:                                                            *
 *      2011/03/14      created                                         *
 *      2011/11/04      support mouseover help                          *
 *      2012/05/06      replace calls to getEltId with calls to         *
 *                      getElementById                                  *
 *      2013/07/30      defer facebook initialization until after load  *
 *                      standardize initialization of form              *
 *                      activate mouse over help for division selection *
 *      2013/08/25      use pageInit common function                    *
 *      2014/09/12      remove use of obsolete selectOptByValue         *
 *      2014/10/14      indices of args array are now lower case        *
 *      2015/08/12      values of <select name='Census'> are now        *
 *                      full 6 character census identifier              *
 *      2016/03/16      adjust value of Census parameter to PageForm.php*
 *                      for 1851 and 1861 censuses to include province  *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/12/04      redesign to use CSS instead of tables           *
 *      2020/05/02      use addEventListener and dispatchEvent          *
 *      2021/03/30      do not call divChange                           *
 *                      get layout of Division selection from template  *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

window.onload    = onloadPages;

/************************************************************************
 *  function onloadPages                                                *
 *                                                                      *
 *  The onload method of the request to update the Pages table web page.*
 *  If the user is returning from a previous request the province.      *
 *  may be specified as a search argument, in which case only the       *
 *  districts for that province are loaded.                             *
 *                                                                      *
 *  Input:                                                              *
 *      this        Window                                              *
 *      ev          Javascript Load Event                               *
 ************************************************************************/
function onloadPages(ev)
{
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {               // iterate through all forms  
        var form    = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {           // loop through elements in form
            var element    = form.elements[j];
            element.onkeydown    = keyDown;

            var    name    = element.name;
            if (!name || name.length == 0)
                name    = element.id;

            switch(name)
            {       // act on specific elements
                case "CensusSel":
                {
                    element.addEventListener('change', changeCensus);
                    var evt         = new Event('change',{'bubbles':true});
                    element.dispatchEvent(evt);
                    break;
                }   // function Census
        
                case "Province":
                {
                    element.addEventListener('change', changeProv);
                    break;
                }   // function Province
        
                case "District":
                {
                    element.addEventListener('change', changeDist);
                    break;
                }   // function District
        
                case "SubDistrict":
                {
                    element.addEventListener('change', changeSubDist);
                    break;
                }   // function SubDistrict

            }       // act on specific elements
        }           // loop through elements in form
    }               // iterate through all forms
}       // function onloadPages

/************************************************************************
 *  function getElt                                                     *
 *                                                                      *
 *  This method finds the first instance of a child element node        *
 *  that matches the supplied tag name.  The method searches            *
 *  recursively down the tree.                                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      curent  the node within which to search for a child             *
 *      tagName the name of the tag to search for.  By convention       *
 *              HTML tag names are specified in upper case,             *
 *              but the function uses a case-insensitive comparison     *
 *                                                                      *
 *  Returns:                                                            *
 *      The matching element node or null.                              *
 ************************************************************************/
function getElt(current, tagName)
{
    if (current === null)
    {
        console.trace();
        throw new Error("util.js: getElt: parameter is null");
    }
    if (current.childNodes === undefined)
        throw new Error("util.js: getElt: parameter is not a document element");
    if (current)
    {       // valid current
        for (let i = 0; i < current.childNodes.length; i++)
        {
            let cNode   = current.childNodes[i];
            if (cNode.nodeType == 1)
            {       // element node
                if (cNode.nodeName.toUpperCase() == tagName.toUpperCase())
                    return cNode;
                else
                {   // search recursively
                    let element = getElt(cNode, tagName);
                    if (element)
                        return element;
                }   // search recursively
            }       // element node
        }       // loop through children of current
    }       // valid current
    else
    {       // no parent
        throw "util.js: getElt(" + current + ",'" + tagName + "')";
    }       // no parent
    return null;
}       // function getElt

/************************************************************************
 *  function changeCensus                                               *
 *                                                                      *
 *  The change event handler of the Census selection.                   *
 *  The user has selected a specific census.  Initiate the load         *
 *  of the associated districts file.                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select id="Census">                                *
 *      ev          Javascript Change Event                             *
 ************************************************************************/
function changeCensus(ev)
{
    var    censusSelect                    = this;
    var    form                            = this.form;
    var    censusOptions                    = this.options;
    var    census;

    if (this.selectedIndex >= 0)
    {           // option chosen
        var currCensusOpt               = censusOptions[this.selectedIndex];
        var census                      = currCensusOpt.value;
        if (census.length > 0)
        {       // non-empty option chosen
            form.Census.value           = census; 
            var censusYear              = census.substring(2);
            var provSelect              = document.distForm.Province;
            switch(censusYear)
            {           // act on census year
                case "1831":
                {       // pre-confederation
                    provSelect.options.length   = 0;   // clear the list
                    addOption(provSelect,   "Quebec",               "QC");
                    provSelect.selectedIndex    = 0;
                    break;
                }

                case "1851":
                case "1861":
                {       // pre-confederation
                    provSelect.options.length   = 0;   // clear the list
                    addOption(provSelect,   "Canada East (Quebec)", "CE");
                    addOption(provSelect,   "Canada West (Ontario)","CW");
                    addOption(provSelect,   "New Brunswick",        "NB");
                    addOption(provSelect,   "Nova Scotia",          "NS");
                    addOption(provSelect,   "Prince Edward Island", "PI");
                    provSelect.selectedIndex    = 1;
                    break;
                }       // pre-confederation

                case "1871":
                case "1881":
                case "1891":
                case "1901":
                {       // post-confederation
                    provSelect.options.length   = 1;   // clear the list
                    addOption(provSelect,   "British Columbia",     "BC");
                    addOption(provSelect,   "Manitoba",             "MB");
                    addOption(provSelect,   "New Brunswick",        "NB");
                    addOption(provSelect,   "Nova Scotia",          "NS");
                    addOption(provSelect,   "North-West Territories","NT");
                    addOption(provSelect,   "Ontario",              "ON");
                    addOption(provSelect,   "Prince Edward Island", "PI");
                    addOption(provSelect,   "Quebec",               "QC");
                    provSelect.selectedIndex    = 0;
                    break;
                }       // post-confederation

                case "1911":
                case "1921":
                {       // post-confederation
                    provSelect.options.length   = 1;   // clear the list
                    addOption(provSelect,   "Alberta",              "AB");
                    addOption(provSelect,   "British Columbia",     "BC");
                    addOption(provSelect,   "Manitoba",             "MB");
                    addOption(provSelect,   "New Brunswick",        "NB");
                    addOption(provSelect,   "Nova Scotia",          "NS");
                    addOption(provSelect,   "North-West Territories","NT");
                    addOption(provSelect,   "Ontario",              "ON");
                    addOption(provSelect,   "Prince Edward Island", "PI");
                    addOption(provSelect,   "Quebec",               "QC");
                    addOption(provSelect,   "Saskatchewan",         "SK");
                    provSelect.selectedIndex    = 0;
                    break;
                }       // post-confederation

                case "1906":
                case "1916":
                {       // prairie provinces
                    provSelect.options.length   = 1;   // clear the list
                    addOption(provSelect,   "Alberta",              "AB");
                    addOption(provSelect,   "Manitoba",             "MB");
                    addOption(provSelect,   "Saskatchewan",         "SK");
                    provSelect.selectedIndex    = 0;
                    break;
                }       // prairie provinces
            }           // act on census year

            // check for province passed as a parameter
            var    province            = form.ProvinceCode.value;

            if (province && (province.length > 0))
            {       // province specified in invocation
                provSelect.value    = province;
            }       // province specified in invocation
            else
            {       // default 
                var provOpts        = provSelect.options;
                province            = provOpts[provSelect.selectedIndex].value;
            }           // default
            loadDistsProv(province);    // load districts
        }               // non-empty census chosen 
    }                   // option chosen

}       // function changeCensus

/************************************************************************
 *  function changeProv                                                 *
 *                                                                      *
 *  The change event handler for the Province select element.           *
 *  Take action when the user selects a new province.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select id="Province">                              *
 *      ev          Javascript Change Event                             *
 ************************************************************************/
function changeProv()
{
    var    provSelect            = this;
    var    form                = this.form;
    var    census                = form.Census.value;
    var    censusYear            = census.substring(2);
    var    optIndex            = provSelect.selectedIndex;
    if (optIndex == -1)
        return; // nothing to do
    var    optVal                = provSelect.options[optIndex].value;
    if (censusYear == '1851' || censusYear == '1861')
        form.Census.value    = optVal + censusYear;
    loadDistsProv(optVal);      // limit the districts selection
    var tdNode              = document.getElementById('DivisionCell');
    tdNode.innerHTML        = '';
}       // function changeProv

/************************************************************************
 *  function loadDistsProv                                              *
 *                                                                      *
 *  Obtain the list of districts for a specific province                *
 *  in the 1871 census as an XML file.                                  *
 *                                                                      *
 *  Input:                                                              *
 *      prov        two character province code                         *
 ************************************************************************/
function loadDistsProv(prov)
{
    var    censusSelect     = document.distForm.Census;
    var    census           = censusSelect.value;
    var    censusYear       = census.substring(2);
    var    xmlName;
    if (censusYear < "1871")
    {                   // pre-confederation
        xmlName    = "CensusGetDistricts.php?Census=" + prov + censusYear;
    }                   // pre-confederation
    else
    {                   // post-confederation
        xmlName    = "CensusGetDistricts.php?Census=" + census +
                    "&Province=" + prov;
    }                   // post-confederation
    var tdNode              = document.getElementById('DivisionCell');
    tdNode.innerHTML        = '';

    // get the district information file    
    HTTP.getXML(xmlName,
                gotDistFile,
                noDistFile);
}       // function loadDistsProv

/************************************************************************
 *  function gotDistFile                                                *
 *                                                                      *
 *  This method is called when the XML file containing                  *
 *  the districts information is retrieved from the server.             *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc        XML from server with districts information        *
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    if(!xmlDoc)
    {
        alert("ReqUpdatePages.js: gotDistFile: unable to retrieve districts file: " + xmlName);
        return;
    }

    var    distSelect    = document.distForm.District;
    distSelect.options.length    = 1;   // clear the list

    // get the list of districts from the XML file
    var newOptions    = xmlDoc.getElementsByTagName("option");

    // add the options to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {
        // get the source "option" node
        // note that although this has the same contents and appearance as an
        // HTML "option" statement, it is an XML Element object, not an HTML
        // Option object.
        var    node    = newOptions[i];

        // get the text value to display to the user
        var    text    = node.textContent;

        // get the "value" attribute
        var    value    = node.getAttribute("value");
        if ((value == null) || (value.length == 0))
        {       // cover our ass
            value        = text;
        }       // cover our ass

        // create a new HTML Option object and add it to the Select
        text += " [dist " + value + "]";
        addOption(distSelect, text, value);
    }           // loop through source "option" nodes

    // if required select a specific district 
    DistSet();
}       // function gotDistFile

/************************************************************************
 *  function DistSet                                                    *
 *                                                                      *
 *  This method ensures that the District selection matches             *
 *  the value passed in the search arguments.                           *
 *                                                                      *
 *  Returns:                                                            *
 *      true    if no District was specified, or if it did not match    *
 *              any of the selection items                              *
 *      false   if it is necessary to load the SubDistrict selection    *
 *              list from the server for a specific District            *
 ************************************************************************/
function DistSet()
{
    var    newDistCode                        = args["district"];
    if (newDistCode === undefined)
        return true;

    var    distSelect                        = document.distForm.District;
    var    distOpts                        = distSelect.options;
    for(var i = 0; i < distOpts.length; i++)
    {
        if (distOpts[i].value == newDistCode)
        {                           // found matching entry
            distSelect.selectedIndex    = i;
            changeDist();   
            return false;
        }                           // found matching entry
    }                               // search for distince to select
    return true;
}       // function distSet

/************************************************************************
 *  function noDistFile                                                 *
 *                                                                      *
 *  This method is called if there is no census summary file from the   *
 *  server.                                                             *
 *  The selection list of districts is cleared and an error message     *
 *  displayed.                                                          *
 ************************************************************************/
function noDistFile()
{
    var distSelect              = document.distForm.District;
    distSelect.options.length   = 0;   // clear the selection
    var tableNode               = getElt(document.distForm, "TABLE");
    var tbNode                  = getElt(tableNode,"TBODY");
    var trNode                  = document.getElementById("distRow");
    var tdNode                  = document.getElementById("msgCell");
    while (tdNode.hasChildNodes())
           tdNode.removeChild(tdNode.firstChild);
    var spanElt                 = document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className           = "label";
    tdNode.appendChild(spanElt);
    var msg                     = document.createTextNode(
        "Census summary \"CensusGetDistricts.php?Census=CW1871\" failed");
    spanElt.appendChild(msg);
}       // function noDistFile

/************************************************************************
 *  function changeDist                                                 *
 *                                                                      *
 *  This is the change event handler of the District select element.    *
 *  This method is called when the user selects a new district.         *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select id="District">                              *
 *      ev          Javascript Change Event                             *
 ************************************************************************/
function changeDist(ev)
{
    // identify the selected census
    var    form                = document.distForm;
    var    censusSelect        = form.CensusSel;
    var census                = censusSelect.value;
    var    censusYear            = census.substring(2);

    // identify the selected district
    var    distSelect            = form.District;
    var    optIndex            = distSelect.selectedIndex;
    if (optIndex < 1)
        return;     // no district selected
    var    distId                = distSelect.options[optIndex].value;
    var tdNode              = document.getElementById('DivisionCell');
    tdNode.innerHTML        = '';

    // identify the file containing subdistrict information for
    // the selected district
    var subFileName;
    var    provSelect;
    var    provId;

    if (censusYear > 1867)
    {       // post-confederation, one census for all of Canada
        subFileName    = "CensusGetSubDists.php?Census=" + census +
                        "&District=" + distId;
    }       // post-confederation, one census for all of Canada
    else
    {       // pre-confederation, separate census for each colony
        provSelect    = form.Province;
        optIndex    = provSelect.selectedIndex;
        if (optIndex < 0)
            return;     // no colony selected
        provId    = provSelect.options[optIndex].value;
        subFileName    = "CensusGetSubDists.php?Census=" +
                        provId + censusYear +
                        "&District=" + distId;
    }       // pre-confederation, separate census for each colony
    // get the subdistrict information file
    //alert("ReqUpdatePages.js: changeDist: subFileName=" + subFileName);
    HTTP.getXML(subFileName,
                gotSubDist,
                noSubDist);

}       // function changeDist

/************************************************************************
 *  function gotSubDist                                                 *
 *                                                                      *
 *  This method is called when the sub-district information XML         *
 *  document relating to a particular district is retrieved from the    *
 *  server.                                                             *
 ************************************************************************/
function gotSubDist(xmlDoc)
{
    var    subdistSelect    = document.distForm.SubDistrict;
    subdistSelect.options.length    = 1;    // clear the selection

    // get the list of subdistricts to select from
    var newOptions        = xmlDoc.getElementsByTagName("option");

    for (var i = 0; i < newOptions.length; ++i)
    {
        // get the source "option" node
        // note that although this has the same contents and appearance as an
        // HTML "option" statement, it is an XML Element object, not an HTML
        // Option object.
        var    node             = newOptions[i];

        // get the text value to display to the user
        var    text             = node.textContent;

        // get the "value" attribute
        var    value            = node.getAttribute("value");
        if ((value == null) || (value.length == 0))
        {                   // cover our ass
            value               = text;
        }                   // cover our ass

        // create a new HTML Option object and add it
        if (text.length > 35)
            text                = text.substr(0, 32) + '...';
        text                    += " [subdist " + value + "]";
        var    newOption        = addOption(subdistSelect,
                                            text,
                                            value);
        // make the additional information in the XML Option
        // available to the application without changing the
        // appearance of the HTML Option
        newOption.xmlOption     = node;
    }                       // loop through source "option" nodes

    // if required select a specific element in the sub dist list
    subDistSet();
}       // function gotSubDist

/************************************************************************
 *  function subDistSet                                                 *
 *                                                                      *
 *  This method ensures that the SubDistrict selection matches          *
 *  the value passed in the search arguments.                           *
 ************************************************************************/
function subDistSet()
{
    var    newSubDistCode   = args["subdistrict"];
    if (newSubDistCode === undefined)
        return true;

    var    subDistSelect    = document.distForm.SubDistrict;
    var    distOpts         = subDistSelect.options;
    for(var i = 0; i < distOpts.length; i++)
    {
        if (distOpts[i].value == newSubDistCode)
        {   // found matching entry
            subDistSelect.selectedIndex    = i;
            changeSubDist();    
            break;
        }   // found matching entry
    }   // search for subDistrict to select

    // select specific division
    var    newDivCode    = args["division"];
    if (newDivCode !== undefined)
    {       // Division identifier supplied
        var    divSelect    = document.distForm.Division;
        if (divSelect !== undefined)
        {   // SubDistrict has a Division select
            var    divOpts    = divSelect.options;
            for(var i = 0; i < divOpts.length; i++)
            {
                if (divOpts[i].value == newDivCode)
                {   // found matching entry
                    divSelect.selectedIndex    = i;
                    break;
                }   // found matching entry
            }       // search for division to select
        }   // SubDistrict has a Division select
    }       // Division identifier supplied

    // select specific Page
    var    newPageCode    = args["page"];
    if (newPageCode !== undefined)
    {       // Page identifier supplied
        var    pageSelect    = document.distForm.Page;
        if (pageSelect !== undefined)
        {   // Division has a Page select
            pageSelect.selectedIndex    = Number(newPageCode) - 1;
        }   // Division has a Page select
    }       // Page identifier supplied
}       // function subDistSet

/************************************************************************
 *  function noSubDist                                                  *
 *                                                                      *
 *  This method is called if there is no sub-district                   *
 *  description to return.                                              *
 ************************************************************************/
function noSubDist()
{
    var    subdistSelect    = document.distForm.SubDistrict;
    subdistSelect.options.length    = 0;    // clear the selection
    var    tableNode    = getElt(document.distForm, "TABLE");
    var tbNode        = getElt(tableNode,"TBODY");
    var    trNode        = document.getElementById("divRow");
    var    tdNode        = document.getElementById("divCell");
    while (tdNode.hasChildNodes())
        tdNode.removeChild(tdNode.firstChild);
    var    spanElt    = document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className    = "label";
    tdNode.appendChild(spanElt);
    var    msg    = document.createTextNode("No subdistricts defined yet");
    spanElt.appendChild(msg);
}       // function noSubDist 

/************************************************************************
 *  function changeSubDist                                              *
 *                                                                      *
 *  This method is called when the user selects a new sub-district.     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select id="SubDistrict">                           *
 *      ev          Javascript Change Event                             *
 ************************************************************************/
function changeSubDist(ev)
{
    // identify the selected district
    var    subDistSelect    = this;
    var    optIndex         = subDistSelect.selectedIndex;
    if (optIndex == -1)
        optIndex            = 0;        // default to first entry
    var    optElt           = subDistSelect.options[optIndex];
    var    optVal           = optElt.value;
    //alert("ReqUpdatePages.js: changeSubDist: optIndex=" + optIndex + ", optElt=" + optElt.outerHTML + ", xmlOption=" + new XMLSerializer().serializeToString(optElt.xmlOption));
    
    // determine how many divisions there are in selected subdist
    var tdNode              = document.getElementById('DivisionCell');
    tdNode.innerHTML        = '';
    var xmlOpt              = optElt.xmlOption;
    var divCt               = 0;
    var firstDiv;

    for (var i = 0; i < xmlOpt.childNodes.length; i++)
    {
        var    cNode            = xmlOpt.childNodes[i];
        if ((cNode.nodeType == 1) && (cNode.nodeName == "div"))
        {
            if (!firstDiv)
                firstDiv    = cNode;
            divCt++;
        }   // element is a "div"
    }       // loop through children of parent

    if (divCt > 1)
    {               // add selections based upon info from XML response
        var template        = document.getElementById('divisionTemplate');
        var select          = template.cloneNode(true);
        select              = tdNode.appendChild(select);
        select.name         = "Division";

        for (var i = 0; i < xmlOpt.childNodes.length; i++)
        {           // loop through children of parent
            var    cNode        = xmlOpt.childNodes[i];
            if ((cNode.nodeType == 1) && (cNode.nodeName == "div"))
            {       // element is a "div"
                var ident       = cNode.getAttribute("id");
                var newOpt      = addOption(select,
                                            "division " + ident,
                                            ident);
                newOpt.xmlNode  = cNode;
            }       // element is a "div"
        }           // loop through children of parent
    }               // add selections based upon info from XML response

}       // function changeSubDist

/************************************************************************
 *  function showForm                                                   *
 *                                                                      *
 *  Show the form for editting the Pages table.                         *
 ************************************************************************/
function showForm()
{
    document.distForm.submit();
}       // function showForm
