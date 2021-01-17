/************************************************************************
 *  ReqUpdate.js                                                        *
 *                                                                      *
 *  This is the JavaScript code to implement dynamic functionality of   *
 *  the page ReqUpdate.php.                                             *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/27      improve error response handling                 *
 *      2010/11/20      functions changeDiv, getArgs moved to util.js   *
 *      2011/01/22      improve separation of HTML and Javascript       *
 *      2011/06/02      handle IE                                       *
 *      2012/05/06      replace calls to getEltId with calls to         *
 *                      function getElementById                         *
 *      2012/09/21      pass census id to CensusUpdateStatus.php        *
 *      2013/05/07      use common scripts for all censuses             *
 *      2013/06/11      change event handlers must be invoked as methods*
 *      2013/07/30      defer facebook initialization until after load  *
 *      2013/08/25      use pageInit common function                    *
 *      2014/09/12      remove use of obsolete selectOptByValue         *
 *      2014/10/14      indices of args array are now lower case        *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2020/05/02      use addEventListener and dispatchEvent          *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

    window.onload   = loadDistricts;

/************************************************************************
 *  the census identifier consisting of a 2 character domain identifier *
 *  and the year of the census                                          *
 ************************************************************************/
    var census      = null;

/************************************************************************
 *  function loadDistricts                                              *
 *                                                                      *
 *  The onload method of the Census Request web page.                   *
 *  Request a list of districts in the census as an XML file.           *
 *  If the user is returning from a previous request the province       *
 *  may be specified as a search argument, in which case only the       *
 *  districts for that province are loaded.                             *
 *                                                                      *
 *  Input:                                                              *
 *      this            instance of Window                              *
 ************************************************************************/
function loadDistricts()
{
    let provSelect  = null;

    // activate functionality for individual input elements
    for(let i = 0; i < document.forms.length; i++)
    {
        let form    = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {
            let element = form.elements[j];

            let name    = element.name;
            if (name === undefined || name.length == 0)
                name    = element.id;

            switch(name)
            {                       // act on specific input element
                case 'Province':
                {
                    let provSelect      = element;
                    element.addEventListener('change', provChanged);
                    break;
                }                   // Province

                case 'District':
                {
                    element.addEventListener('change', districtChanged);
                    break;
                }                   // District

                case 'SubDistrict':
                {
                    element.addEventListener('change', subDistChanged);
                    break;
                }                   // SubDistrict

                case 'showForm':
                {
                    element.onclick     = doSubmit;
                    break;
                }                   // showForm

                case 'progress':
                {
                    element.onclick     = showStatus;
                    break;
                }                   // progress

                case 'Census':
                {
                    census              = element.value;
                    break;
                }                   // Census

            }                       // act on specific input element
        }                           // loop through all elements in form
    }                               // loop through all forms

    // act on parameters
    let provCode    = args["province"];
    let censusYear  = census.substring(2) - 0;
    if (provCode === undefined && censusYear < 1867)
        provCode    = census.substring(0,2);

    if (provCode === undefined)
    {   // specific province not specified
        // set the Province select element to "All Provinces"
        provSelect.selectedIndex    = 0;

        // Load the list of all districts in the census
        HTTP.getXML("CensusGetDistricts.php?Census=" + census,
                    gotDistFile,
                    noDistFile);
    }   // specific province not specified
    else
    {   // specific province specified
        provSelect.value    = provCode;

        // get the subdistrict information file
        HTTP.getXML("CensusGetDistricts.php?Census=" + census +
                            "&Province=" + provCode,
                    gotDistFile,
                    noDistFile);
    }   // specific province specified
}       // function loadDistricts

/************************************************************************
 *  function doSubmit                                                   *
 *                                                                      *
 *  This method is invoked when the user clicks on the "submit"         *
 *  button.  The request to obtain a form for updating the specified    *
 *  page of the census is submitted to the server.                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button name='showForm'>                        *
 ************************************************************************/
function doSubmit()
{
    document.distForm.submit();
    return false;
}       // function doSubmit

/************************************************************************
 *  function showStatus                                                 *
 *                                                                      *
 *  This method is invoked when the user clicks on the "progress"       *
 *  button.  A web page summarizing the progress of the transcription   *
 *  effort is displayed.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='progress'>                              *
 ************************************************************************/
function showStatus()
{
    let form    = this.form;
    window.location = 'CensusUpdateStatus.php?Census=' + census;
    return false;
}       // function showStatus

/************************************************************************
 *  function provChanged                                                *
 *                                                                      *
 *  The change event handler for the Province select element.           *
 *  Take action when the user selects a new province.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='Province'>                        *
 ************************************************************************/
function provChanged()
{
    let form        = this.form;
    let provSelect  = this;
    let optIndex    = provSelect.selectedIndex;
    if (optIndex == -1)
        return; // nothing to do
    let optVal  = provSelect.options[optIndex].value;
    loadDistsProv(provSelect, 
                  optVal);      // limit the districts selection
}

/************************************************************************
 *  function loadDistsProv                                              *
 *                                                                      *
 *  Obtain the list of districts for a specific province                *
 *  in the census as an XML file.                                       *
 *                                                                      *
 *  Input:                                                              *
 *      provSelect      <select> object                                 *
 *      prov            two character province code                     *
 ************************************************************************/
function loadDistsProv(provSelect, 
                    prov)
{
    let form    = provSelect.form;
    // get the subdistrict information file
    HTTP.getXML("CensusGetDistricts.php?Census=" + census + 
                            "&Province=" + prov,
                gotDistFile,
                noDistFile);
}       // function loadDistsProv

/************************************************************************
 *  function gotDistFile                                                *
 *                                                                      *
 *  This method is called when the XML file containing                  *
 *  the districts information is retrieved.                             *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      XML document from server with districts information *
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    let distSelect  = document.distForm.District;
    distSelect.options.length   = 0;    // clear the list

    // create a new HTML Option object representing
    // the default of all districts and add it to the Select
    addOption(distSelect, "All Districts", "");

    try {
    // get the list of districts from the XML file
    let newOptions  = xmlDoc.getElementsByTagName("option");

    // add the options to the Select
    for (let i = 0; i < newOptions.length; ++i)
    {
        // get the source "option" node
        // note that although this has the same contents and appearance as an
        // HTML "option" statement, it is an XML Element object, not an HTML
        // Option object.
        let node    = newOptions[i];

        // get the text value to display to the user
        let text    = node.textContent;

        // get the "value" attribute
        let value   = node.getAttribute("value");
        if ((value == null) || (value.length == 0))
        {       // cover our ass
            value       = text;
        }       // cover our ass

        // create a new HTML Option object and add it to the Select
        text += " [dist " + value + "]";
        addOption(distSelect, text, value);
    }           // loop through source "option" nodes
    // if required select a specific district 
    setDist();
    }
    catch(e)
    {
        if (xmlDoc.documentElement)
            alert("gotSubDist: " + new XMLSerializer().serializeToString(xmlDoc.documentElement) + e);
        else
            alert("gotSubDist: " + xmlDoc + e);
    }
}       // function gotDistFile

/************************************************************************
 *  function setDist                                                    *
 *                                                                      *
 *  This method ensures that the District selection matches             *
 *  the value passed in the search arguments.                           *
 *                                                                      *
 *  Returns:                                                            *
 *      true if no District was specified, or if it did not match       *
 *              any of the selection items                              *
 *      false if it is necessary to load the SubDistrict selection      *
 *              list from the server for a specific District            *
 ************************************************************************/
function setDist()
{
    let newDistCode = args["district"];
    if (newDistCode === undefined)
        return true;

    let distSelect  = document.distForm.District;
    let distOpts    = distSelect.options;
    for(let i = 0; i < distOpts.length; i++)
    {
        if (distOpts[i].value == newDistCode)
        {                   // found matching entry
            distSelect.selectedIndex    = i;
            let evt                 = new Event('change',{'bubbles':true});
            distSelect.dispatchEvent(evt);
            return false;
        }                   // found matching entry
    }                       // search for distince to select
    return true;
}       // function setDist

/************************************************************************
 *  function noDistFile                                                 *
 *                                                                      *
 *  This method is called if there is no census summary script on the   *
 *  server. The selection list of districts is cleared and an error     *
 *  message is displayed.                                               *
 ************************************************************************/
function noDistFile()
{
    let form        = document.distForm;
    let distSelect  = form.District;
    distSelect.options.length   = 0;    // clear the selection
    let tdNode      = document.getElementById("msgCell");
    while (tdNode.hasChildNodes())
           tdNode.removeChild(tdNode.firstChild);
    let spanElt = document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className   = "label";
    tdNode.appendChild(spanElt);
    let msg = document.createTextNode(
        "Census summary \"CensusGetDistricts.php?Census=" + census +
                    "\" failed");
    spanElt.appendChild(msg);
}       // function noDistFile

/************************************************************************
 *  function districtChanged                                            *
 *                                                                      *
 *  The change event handler of the District select element.            *
 *  This method is called when the user selects a new district.         *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='District'>                            *
 ************************************************************************/  
function districtChanged()
{
    // identify the selected district
    let distSelect  = this;
    let form        = this.form;
    let optIndex    = distSelect.selectedIndex;
    if (optIndex == -1)
        optIndex    = 0;        // default to first entry
    let optVal      = distSelect.options[optIndex].value;

    // identify the file containing subdistrict information for
    // the selected district
    let subFileName = "CensusGetSubDists.php?Census=" + census +
                            "&District=" + optVal;
    
    // get the subdistrict information file
    HTTP.getXML(subFileName,
                gotSubDist,
                noSubDist);

}       // function districtChanged

/************************************************************************
 *  function gotSubDist                                                 *
 *                                                                      *
 *  This method is called when the sub-district information XML         *
 *  document describing a particular district is retrieved.             *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc          XML document returned from server               *
 ************************************************************************/
function gotSubDist(xmlDoc)
{
    let subdistSelect       = document.distForm.SubDistrict;
    subdistSelect.options.length    = 0;    // clear the options
    addOption(subdistSelect,
              "All Sub-Districts",
              "");

    try {
        // get the list of subdistricts to select from
        let newOptions      = xmlDoc.getElementsByTagName("option");

        for (let i = 0; i < newOptions.length; ++i)
        {
            // get the source "option" node
            // note that although this has the same contents and appearance as
            // an HTML "option" statement, it is an XML Element object,
            // not an HTML Option object.
            let node    = newOptions[i];
    
            // get the text value to display to the user
            let text    = node.textContent;
    
            // get the "value" attribute
            let value   = node.getAttribute("value");
            if ((value == null) || (value.length == 0))
            {       // cover our ass
                value       = text;
            }       // cover our ass
    
            // create a new HTML Option object and add it
            text += " [subdist " + value + "]";
            let newOption   = addOption(subdistSelect,
                                text,
                                value);
            // make the additional information in the XML Option
            // available to the application without changing the
            // appearance of the HTML Option
            newOption.xmlOption = node;
    
            let tdNode      = document.getElementById("divCell");
            while (tdNode.hasChildNodes())
                    tdNode.removeChild(tdNode.firstChild);
        }           // loop through source "option" nodes

        // if required select a specific element in the sub dist list
        setSubDist();
    }   // function try
    catch(e)
    {
        if (xmlDoc.documentElement)
            alert("gotSubDist: " +
                    new XMLSerializer().serializeToString(xmlDoc.documentElement));
        else
            alert("gotSubDist: " + xmlDoc);
    }       // function catch
}       // function gotSubDist

/************************************************************************
 *  function setSubDist                                                 *
 *                                                                      *
 *  This method ensures that the SubDistrict selection matches          *
 *  the value passed in the search arguments.                           *
 ************************************************************************/
function setSubDist()
{
    let newSubDistCode      = args["subdistrict"];
    if (newSubDistCode === undefined)
        return true;

    let subDistSelect       = document.distForm.SubDistrict;
    let distOpts            = subDistSelect.options;
    for(let i = 0; i < distOpts.length; i++)
    {
        if (distOpts[i].value == newSubDistCode)
        {               // found matching entry
            subDistSelect.selectedIndex = i;
            let evt         = new Event('change',{'bubbles':true});
            subDistSelect.dispatchEvent(evt);
            break;
        }               // found matching entry
    }                   // search for subDistrict to select

    // select specific division
    let newDivCode          = args["division"];
    if (newDivCode !== undefined)
    {                   // Division identifier supplied
        let divSelect       = document.distForm.Division;
        if (divSelect !== undefined)
        {               // SubDistrict has a Division select
            let divOpts     = divSelect.options;
            for(let i = 0; i < divOpts.length; i++)
            {
                if (divOpts[i].value == newDivCode)
                {       // found matching entry
                    divSelect.selectedIndex = i;
                    changeDiv(divOpts[i].xmlNode);
                    break;
                }       // found matching entry
            }           // search for division to select
        }               // SubDistrict has a Division select
    }                   // Division identifier supplied

    // select specific Page
    let newPageCode = args["page"];
    if (newPageCode !== undefined)
    {                   // Page identifier supplied
        let pageSelect      = document.distForm.Page;
        if (pageSelect !== undefined)
        {               // Division has a Page select
            // first entry (index = 0) is for page 1
            // so index value is 1 less than page number
            pageSelect.selectedIndex    = Number(newPageCode) - 1;
        }               // Division has a Page select
    }                   // Page identifier supplied
}       // function setSubDist

/************************************************************************
 *  function noSubDist                                                  *
 *                                                                      *
 *  This method is called if there is no sub-district                   *
 *  script on the server.                                               *
 ************************************************************************/
function noSubDist()
{
    let subdistSelect   = document.distForm.SubDistrict;
    subdistSelect.options.length    = 0;    // clear the options
    let tdNode      = document.getElementById("divCell");
    while (tdNode.hasChildNodes())
        tdNode.removeChild(tdNode.firstChild);
    let spanElt = document.createElement("span");
    spanElt.setAttribute("class", "label");
    spanElt.className   = "label";
    tdNode.appendChild(spanElt);
    let msg = document.createTextNode("No subdistricts defined yet");
    spanElt.appendChild(msg);
}

/************************************************************************
 *  function subDistChanged                                             *
 *                                                                      *
 *  This is the change event handler of the subdistrict select element. *
 *  This method is called when the user selects a new sub-district.     *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='SubDistrict'>                     *
 ************************************************************************/  
function subDistChanged()
{
    // locate cell to display response in
    let tdNode      = document.getElementById("divCell");
    
    // identify the selected district
    let subDistSelect   = document.distForm.SubDistrict;
    let optIndex    = subDistSelect.selectedIndex;
    if (optIndex == -1)
        optIndex    = 0;        // default to first entry
    let optElt  = subDistSelect.options[optIndex];
    let optVal  = optElt.value;
    
    // remove any existing HTML from this cell
    while (tdNode.hasChildNodes())
                tdNode.removeChild(tdNode.firstChild);

    // determine how many divisions there are in selected subdist
    let xmlOpt      = optElt.xmlOption;
    let divCt       = 0;
    let firstDiv;

    for (let i = 0; i < xmlOpt.childNodes.length; i++)
    {
        let cNode   = xmlOpt.childNodes[i];
        if ((cNode.nodeType == 1) && (cNode.nodeName == "div"))
        {
            if (!firstDiv)
                firstDiv    = cNode;
            divCt++;
        }   // element is a "div"
    }       // loop through children of parent

    if (divCt > 1)
    {   // add selections based upon information from XML response
        let select      = tdNode.appendChild(document.createElement("select"));
        select.name     = "Division";
        select.size     = 1;
        select.addEventListener('change', divSelected);

        for (let i = 0; i < xmlOpt.childNodes.length; i++)
        {
            let cNode   = xmlOpt.childNodes[i];
            if ((cNode.nodeType == 1) && (cNode.nodeName == "div"))
            {
                let ident   = cNode.getAttribute("id");
                let newOpt  = addOption(select,
                                "division " + ident,
                                ident);
                newOpt.xmlNode  = cNode;
            }   // element is a "div"
        }       // loop through children of parent
        // user must select a value
        select.selectedIndex    = 0;
    }   // add selections based upon information from XML response

    // update page prompt in form
    changeDiv(firstDiv);
}       // function subDistChanged

/************************************************************************
 *  function divSelected                                                *
 *                                                                      *
 *  This change event handler for the division Select element.          *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='Division'>                        *
 *      evt             instance of Event                               *
 ************************************************************************/  
function divSelected(evt)
{
    let select  = this;
    changeDiv(select.options[select.selectedIndex].xmlNode);
}       // function divSelected
