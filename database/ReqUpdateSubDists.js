/************************************************************************
 *  ReqUpdateSubDists.js                                                *
 *                                                                      *
 *  Implement dynamic functionality the the web page to select          *
 *  a district of the subdistrict table to be editted.                  *
 *                                                                      *
 *  History:                                                            *
 *      2010/11/20      function getArgs moved to util.js               *
 *      2011/03/09      improve separation of HTML and Javascript       *
 *      2012/05/06      replace calls to getEltId with calls to         *
 *                      getElementById                                  *
 *      2012/09/17      support census identifiers                      *
 *      2013/07/30      defer facebook initialization until after load  *
 *      2013/08/25      use pageInit common function                    *
 *      2013/08/27      handle incomplete census identified             *
 *      2014/10/14      indices of args array are now lower case        *
 *      2015/03/15      missing support for 1921 census                 *
 *                      clean up comments                               *
 *                      display popup message for dist file error       *
 *                      internationalize all text strings including     *
 *                      province names                                  *
 *      2015/06/02      invoked from ReqUpdateSubDists.php              *
 *      2016/09/27      check for presence of arg before using          *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2020/03/08      fix infinite loop                               *
 *      2020/04/05      fix reference to undefined element Census       *
 *      2023/01/22      tolerate PHP script deleting entire form        *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/

// define the function to be called when the page is loaded
window.onload   = loadDistricts;

/************************************************************************
 *  loadDistricts                                                       *
 *                                                                      *
 *  The load event handler of the Sub Districts request web page.       *
 *  If the user is returning from a previous request the province       *
 *  may be specified as a search argument, in which case only the       *
 *  districts for that province are loaded.                             *
 *                                                                      *
 *  Input:                                                              *
 *      this            window                                          *
 *      ev              Javascript load Event                           *
 ************************************************************************/
var lang                    = 'en';     // used in multiple functions

function loadDistricts()
{
    let province            = ''
    for (const key in args)
    {                   // process arguments on URL
        const value         = args[key];
        switch(key.toLowerCase())
        {
            case 'lang':
                lang                = value;
                break;

            case 'province':
                province            = value;
                break;

        }
    }                   // process arguments on URL

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {                                   // loop through all forms
        var form                = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {                               // loop through elements in form
            var element         = form.elements[j];

            element.onkeydown   = keyDown;
            var name            = element.name;
            if (!name || name.length == 0)
                name            = element.id;
            switch(name)
            {                           // act on specific elements
                case "CensusYear":
                    element.addEventListener('change', changeCensus);
                    break;              // CensusYear

                case "Province":
                    element.addEventListener('change', changeProv);
                    if (strlen(province) > 0)
                    {
                        element.value   = province;
                        let evt         = new Event('change',
                                                    {'bubbles' : true});
                        element.dispatchEvent(evt);
                    }
                    break;              // Province

                case "District":
                    element.addEventListener('change',  changeDist);
                    element.addEventListener('dblclick', showForm);
                    break;              // District

            }                           // act on specific elements

        }                               // loop through elements in form
    }                                   // loop through all forms

}       // function loadDistricts

/************************************************************************
 *  function changeCensus                                               *
 *                                                                      *
 *  The change event handler for the Census selection.                  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='CensusYear'>                      *
 *      ev              Javascript change Event                         *
 ************************************************************************/
function changeCensus()
{
    let censusSelect        = this;
    let censusOptions       = this.options;
    let censusElt           = document.distForm.censusId;
    let provSelect          = document.distForm.Province;
    let distSelect          = document.distForm.District;
    let censusId;

    if (this.selectedIndex > 0)
    {           // option chosen
        let currCensusOpt   = censusOptions[this.selectedIndex];
        let censusId        = currCensusOpt.value;

        if (censusId.length > 0)
        {       // non-empty option chosen
            censusElt.value     = censusId;
            provSelect.options.length   = 1;    // clear the list
            let options             = {};
            options.errorHandler    = function() {alert('script getRecordJSON.php not found')};
            HTTP.get('/getRecordJSON.php?table=Censuses&id=' + censusId +
                                            '&lang=' + lang,
                     gotCensus,
                     options);
        }       // non-empty option chosen
    }           // census chosen
    else
    {
        provSelect.options.length   = 1;
        distSelect.options.length   = 1;
    }
}       // function changeCensus

/************************************************************************
 *  function gotCensus                                                  *
 *                                                                      *
 *  Take action when the Census object is retrieved from the server.    *
 *                                                                      *
 *  Input:                                                              *
 *      census          a Javascript object                             *
 ************************************************************************/
function gotCensus(census)
{
    if ('censusid' in census)
    {
        let censusId            = census.censusid;
        let cc                  = censusId.substring(0,2);
        let censusYear          = censusId.substring(2);
        let provinces           = census.provinces;   
        var censusElt           = document.distForm.censusId;
        let provSelect          = document.distForm.Province;
        // province passed as a parameter
        let province            = ''
        if ('province' in args)
            province            = args.province;
        if (censusYear < 1867)
        {       // pre-confederation
            if (province.length == 0)
                province        = 'CW';
            if (provinces.indexOf(province) == -1)
                province        = 'CW';
            censusElt.value     = province + censusYear;
        }       // pre-confederation
        else
        {       // post-confederation
            provSelect.selectedIndex    = 0;
            if (provinces.indexOf(province) == -1)
                province        = '';
        }       // post-confederation
        let domains             = census.domains;   
        for (const code in domains)
        {
            let provCode        = code.substring(2);
            addOption(provSelect,   domains[code], provCode);
        }       // loop through provinces
        provSelect.value        = province;

        // update districts 
        loadDistsProv(province);    // load districts
    }
    else
    if ('msg' in census)
    {
        popupAlert(census.msg, document.distForm.CensusYear);
    }
}       // function gotCensus

/************************************************************************
 *  function changeProv                                                 *
 *                                                                      *
 *  The change event handler for the Province selection.                *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='Province'>                        *
 *      ev              Javascript change Event                         *
 ************************************************************************/
function changeProv()
{
    var provSelect  = this;
    var optIndex    = provSelect.selectedIndex;
    if (optIndex < 1)
        return; // nothing to do
    var province    = provSelect.value;
    var censusId    = document.distForm.CensusYear.value;
    var censusYear  = censusId.substring(2);
    if (censusYear < "1867")
        document.distForm.censusId.value = province + censusYear;
    else
        document.distForm.censusId.value = censusId;
    loadDistsProv(province);        // limit the districts selection
}       // function changeProv

/************************************************************************
 *  function loadDistsProv                                              *
 *                                                                      *
 *  Obtain the list of districts for a specific province                *
 *  in the census as an XML file.                                       *
 *                                                                      *
 *  Input:                                                              *
 *      prov        two character province code                         *
 ************************************************************************/
function loadDistsProv(prov)
{
    var censusId    = document.distForm.censusId.value;
    var censusYear  = censusId.substring(2);

    // get the district information file    
    HTTP.getXML("CensusGetDistricts.php?Census=" + censusId +
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
 *      xmlDoc      XML from server with districts information          *
 ************************************************************************/
function gotDistFile(xmlDoc)
{
    var distSelect  = document.distForm.District;
    var rootNode    = xmlDoc.documentElement;
    var msgs        = xmlDoc.getElementsByTagName("msg");
    if (msgs.length > 0)
    {       // error messages
        var alertTxtElt = document.getElementById('badDistFileMsg');
        var alertTxt    = alertTxtElt.innerHTML.replace('$msgs',
                                    msgs[0].textContent);
        popupAlert(alertTxt,
                   distSelect);
        return;
    }       // error messages

    distSelect.options.length   = 0;    // clear the list

    // create a new HTML Option object representing
    // the default of all districts and add it to the Select
    var textElement = document.getElementById("chooseDistText");
    addOption(distSelect, textElement.innerHTML, "?");

    // get the list of districts from the XML file
    var newOptions  = xmlDoc.getElementsByTagName("option");

    // add the options to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {
        // get the source "option" node
        // note that although this has the same contents and appearance as an
        // HTML "option" statement, it is an XML Element object, not an HTML
        // Option object.
        var node    = newOptions[i];

        // get the text value to display to the user
        var text    = node.textContent;

        // get the "value" attribute
        var value   = node.getAttribute("value");
        if ((value == null) || (value.length == 0))
        {       // cover our ass
            value       = text;
        }       // cover our ass

        // create a new HTML Option object and add it to the Select
        text += " [dist " + value + "]";
        addOption(distSelect, text, value);
    }           // loop through source "option" nodes

    // if required select a specific district 
    if ('district' in args)
        setDist(args['district']);
}       // function gotDistFile

/************************************************************************
 *  function setDist                                                    *
 *                                                                      *
 *  This method ensures that the District selection matches             *
 *  a particular value.                                                 *
 *                                                                      *
 *  Input:                                                              *
 *      newDistCode     new district identifier                         *
 *                                                                      *
 *  Returns:                                                            *
 *      null if there is no current selected district                   *
 *      the former selected district                                    *
 ************************************************************************/
function setDist(newDistCode)
{
    var distSelect  = document.distForm.District;
    var distOpts    = distSelect.options;
    if (distOpts.length == 0)
        return null;
    var oldValue    = null;
    if (distSelect.selectedIndex >= 0  &&
        distSelect.selectedIndex < distOpts.length)
        oldValue    = distOpts[distSelect.selectedIndex];

    if (newDistCode === undefined || newDistCode === null)
        return oldValue;

    for(var i = 0; i < distOpts.length; i++)
    {
        if (distOpts[i].value == newDistCode)
        {   // found matching entry
            distSelect.selectedIndex    = i;
            changeDist();   
            break;
        }   // found matching entry
    }   // search for district to select
    return oldValue;
}       // function setDist

/************************************************************************
 *  function noDistFile                                                 *
 *                                                                      *
 *  This method is called if there is no census summary file.           *
 *  The selection list of districts is cleared and an error message     *
 *  displayed.                                                          *
 ************************************************************************/
function noDistFile()
{
    var distSelect  = document.distForm.District;
    distSelect.options.length   = 0;    // clear the selection
    var censusId    = document.distForm.censusId.value;
    var alertTxtElt = document.getElementById('noDistFileMsg');
    var alertTxt    = alertTxtElt.innerHTML.replace('$census',
                                    censusId);
    popupAlert(alertTxt,
               distSelect);
}       // function noDistFile

/************************************************************************
 *  function changeDist                                                 *
 *                                                                      *
 *  This change event handler for the District select.                  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='District'>                        *
 *      ev              Javascript change Event                         *
 ************************************************************************/
function changeDist()
{
    // identify the selected district
    var distSelect  = document.distForm.District;
    var optIndex    = distSelect.selectedIndex;
    if (optIndex == -1)
        optIndex    = 0;        // default to first entry
    var optVal  = distSelect.options[optIndex].value;

}       // function changeDist

/************************************************************************
 *  function showForm                                                   *
 *                                                                      *
 *  Show the form for editting the sub-district table.                  *
 *  This is invoked by double-clicking on a district in the selection   *
 *  list.                                                               *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name='District'>                        *
 *      ev              Javascript click Event                          *
 ************************************************************************/
function showForm()
{
    document.distForm.submit();
}       // function showForm
