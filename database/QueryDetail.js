/************************************************************************
 *  QueryDetail.js                                                      *
 *                                                                      *
 *  Common dynamic functionality of census query pages                  *
 *  QueryDetailYYYY.html.                                               *
 *                                                                      *
 *  History:                                                            *
 *      2011/04/10      split off from util.js                          *
 *                      use a cookie to remember last district used for *
 *                      each census.                                    *
 *      2011/09/03      support a comma-separated list of               *
 *                      district:subdistrict pairs in the option value  *
 *                      for a sub-district selection This is to support *
 *                      collapsing the 1911 selection lists             *
 *      2011/10/07      clear the sub-district selection list if the    *
 *                      user specifies to search all districts          *
 *                      make coding more consistent and add error       *
 *                      function handling                               *
 *      2011/12/28      popup a loading indicator while waiting for a   *
 *                      server response                                 *
 *                      pop up help balloon if mouse held over field    *
 *      2012/01/20      use id= rather than name= for buttons to avoid  *
 *                      problem with IE                                 *
 *      2012/01/24      use popupLoading and hideLoading methods        *
 *      2012/04/01      use getElementById instead of getEltId          *
 *                      support for 1911 and 1916 broke support for     *
 *                      older censuses                                  *
 *                      if the page number is set and there is an       *
 *                      "OrderBy" selection list, set it to "Line"      *
 *      2012/09/27      correct problems arising from confusion between *
 *                      census identifier and census year by using the  *
 *                      name censusId for the identifier and censusYear *
 *                      for the year.                                   *
 *      2013/07/30      defer facebook initialization until after load  *
 *      2013/12/13      form layout done using CSS instead of tables    *
 *      2014/08/10      comments updated.  No functional changes.       *
 *      2014/09/12      remove use of obsolete selectOptByValue         *
 *      2014/10/14      indices of args array are now lower case        *
 *      2017/09/19      support lang=fr                                 *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/05/19      call element.click to trigger button click      *
 *      2021/03/29      correct initialization of division list         *
 *      2023/08/23      restrict displayed districts to match Province  *
 *                      use JSON                                        *
 *                      use addEventListener                            *
 *      2023/08/25      correct sort order changed by JSON              *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/

window.addEventListener('load', onLoadDetail);

/************************************************************************
 *  function onLoadDetail                                               *
 *                                                                      *
 *  The onload method of a QueryDetailYyyy.html web page.               *
 *  This is invoked after the web page has been loaded into the browser.*
 *  Obtain the list of districts in the census as an XML file           *
 *  from the web server.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this        window object                                       *
 ************************************************************************/
function onLoadDetail()
{
    document.body.addEventListener('keydown', qdKeyDown);

    let form                    = document.distForm;
    let censusId                = '';
    let censusYear              = 1881;
    let element                 = null;
    if (form)
    {       // web page has a form named distForm
        // get the census identifier from the invoking form
        // This has the format "CCYYYY" where "CC" is the administrative
        // domain, usually the country code, and "YYYY" is the census year 
        censusId                = form.Census.value;
        censusYear              = censusId.substring(2);


        // initialize dynamic functionality of form elements
        let elements            = form.elements;
        for (let i = 0; i < elements.length; i++)
        {   // loop through all form elements
            element             = elements[i];
            element.addEventListener('keydown', keyDown);

            let name            = element.name;
            if (name.length == 0)
                name            = element.id;

            switch(name)
            {               // act on specific elements by name
                case 'Coverage':
                    element.addEventListener('click', showCoverage);
                    break;  // display status of transcription

                case 'Province':
                    element.addEventListener('change', changeProv);
                    break;  // user selected different province

                case 'Page':
                    element.addEventListener('change', changePage);
                    break;  // user selected different page

                case 'Surname':
                    element.focus();    // initial keyboard focus
                    break;  // surname field

                case 'District[]':
                    break;           // districts selection
            }               // act on specific elements by name
        }                   // loop through all form elements
    }       // web page has a form named distForm
    else
        alert("QueryDetail.js: onLoad: invoking web page does not contain " +
                "a <form name='distForm'>");

    // get list of districts matching parameters
    let distURL             = 'CensusGetDistrictsJSON.php';
    let provSelect          = form.Province;
    let optIndex            = provSelect.selectedIndex;
    let prov                = provSelect.options[optIndex].value;
    if (optIndex != -1)
    {
        if (censusYear < 1867)
            distURL         += "?Census=" + prov + censusYear;
        else
        {
            distURL         += "?Census=" + censusId + "&Province=" + prov;
        }
    }                   // provincial census
    else
        distURL             += "?Census=" + censusId;
    if ('lang' in args)
        distURL             += '&lang=' + args['lang'];

    // display indicator that we are waiting forresponse from the server
    popupLoading(element);

    //alert("QueryDetail.js: onLoadDetail: get: distUrl=" + distURL);
    HTTP.get(distURL,
             gotDistFile,
             noDistFile);

    // initialize dynamic functionality of labels
    elements    = document.getElementsByTagName("label");
    for (let i = 0; i < elements.length; i++)
    {                       // loop through all labels
        let element             = elements[i];
        // pop up help balloon if the mouse hovers over a label
        // for more than 2 seconds
        element.addEventListener('mouseover', eltMouseOver);
        element.addEventListener('mouseout', eltMouseOut);
    }                       // loop through all labels

}       // function onLoadDetail

/************************************************************************
 *  function gotDistFile                                                *
 *                                                                      *
 *  This method is called when the JSON document, listing all of the    *
 *  districts in the province, has been retrieved from the web server.  *
 *                                                                      *
 *  Input:                                                              *
 *      jsonDoc             Document representing the XML file          *
 ************************************************************************/
function gotDistFile(jsonDoc)
{
    if (jsonDoc === null)
        return noDistFile();

    // hide the loading indicator
    hideLoading();              // hide "loading" indicator
    //console.log("QueryDetail.js: gotDistFile: " + JSON.stringify(jsonDoc));

    let form                        = document.distForm;
    if (form)
    {                           // web page has a form named distForm
        // get the census identifier from the invoking form
        // This has the format "CCYYYY" where "CC" is the administrative
        // domain, usually the country code, and "YYYY" is the census year 
        let censusId                = form.Census.value;
        let censusYear              = censusId.substring(2);
        if (censusYear < 1867)  // pre-confederation
            censusId                = form.Province.value + censusYear;

        // if the district selection list supports multiple selection
        // then the name of the element has array subscript brackets
        // appended by PHP convention.
        let distSelect              = form.elements["District[]"];
        if (distSelect === undefined)
            distSelect              = form.District;
        distSelect.options.length   = 0;    // clear the selection
        distSelect.addEventListener('change', changeDist);

        // create a new HTML Option object and add it as the first
        // option to the Select.  This ensures that when the user selects
        // any other option that the change event handler is called
        let opt                     = addOption(distSelect,
                                                "All Districts",
                                                "");
        opt.selected                = true;

        // add the districts to the Select
        for (let distid in jsonDoc)
        {
            if (distid == 'get')
                continue;

            // get the district name to display to the user
            let text                = jsonDoc[distid];
            let result              = /[0-9.]+$/.exec(distid);
            if (result)
                distid              = result[0];

            let textarea            = document.createElement('textarea');
            textarea.innerHTML      = text;
            text                    = textarea.value;
            if ((distid == null) || (distid.length == 0))
            {                   // cover our ass
                distid              = text;
            }                   // cover our ass
            else
            {                   // append district number to displayed text
                text                += " [dist=" + distid + "]";
            }                   // append district number to displayed text

            // create a new HTML Option object and add it to the Select
            let option              = addOption(distSelect,
                                                text,
                                                distid);
        }                       // loop through source "option" nodes

        // if implied by the environment, set the initial selection
        let district                = args["district"];
        if (!district)
        {                       // initial district not explicitly set
            // get the initially selected district number from a cookie
            let cookie              = new Cookie("familyTree");
            district                = cookie[censusId];
            if (district === undefined || district === null)
                district            = "";
        }

        // select the option whose value matches the supplied district num
        distSelect.value            = district;

        // take action for change in selection
        // this populates the subdistrict selection list
        let evt                     = new Event('change',{'bubbles':true});
        distSelect.dispatchEvent(evt);
    }                           // web page has a form named distForm
}       // function gotDistFile

/************************************************************************
 *  function noDistFile                                                 *
 *                                                                      *
 *  This method is called if there is no list of districts for this     *
 *  census.  The selection list of districts is cleared.                *
 ************************************************************************/
function noDistFile()
{
    // hide the loading indicator
    hideLoading();  // hide "loading" indicator

    let form                = document.distForm;
    if (form)
    {                   // web page has a form named distForm
        // get the census identifier from the invoking form
        // This has the format "CCYYYY" where "CC" is the administrative
        // domain, usually the country code, and "YYYY" is the census year 
        let censusId        = form.Census.value;
        let censusYear      = censusId.substring(2);
        if (censusYear < 1867)  // pre-confederation
            censusId        = form.Province.value + censusYear;

        // if the district selection list supports multiple selection
        // then the name of the element has array subscript brackets
        // appended by PHP convention.
        let distSelect      = form.elements['District[]'];
        if (distSelect === undefined)
            distSelect      = form.District;
        distSelect.options.length   = 0;    // clear the selection
        addOption(distSelect,
                  "Census summary file \"CensusGetDistrictsJSON.php?Census=" +
                    censusId +
                    "\" unavailable",
                    "");
    }                   // web page has a form named distForm
}       // function noDistFile

/************************************************************************
 *  function changeProv                                                 *
 *                                                                      *
 *  The change event handler of the Province select element.            *
 *  Take action when the user selects a new province.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='Province'>                            *
 ************************************************************************/
function changeProv()
{
    let provSelect  = this;
    let optIndex    = provSelect.selectedIndex;
    if (optIndex == -1)
        return; // nothing selected

    // get the two character province code
    let optVal  = provSelect.options[optIndex].value;

    // take action on the selected province
    onLoadProv(optVal);
}       // function changeProv

/************************************************************************
 *  function changePage                                                 *
 *                                                                      *
 *  The change event handler of the Page select element.                *
 *  Take action when the user selects a new province.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='Page'>                                *
 ************************************************************************/
function changePage()
{
    if (this.value.length > 0 &&
        (this.value - 0) > 0 &&
        distForm.OrderBy !== undefined)
        document.distForm.OrderBy.selectedIndex = 1;
}       // function changePage


/************************************************************************
 *  function onLoadProv                                                 *
 *                                                                      *
 *  Use Ajax to obtain the list of districts in the census              *
 *  for a specific province as an XML file from the Web server.         *
 *                                                                      *
 *  Input:                                                              *
 *      prov        two character province code                         *
 ************************************************************************/
function onLoadProv(prov)
{
    let form    = document.distForm;
    if (form)
    {       // web page has a form named distForm
        // get the census identifier from the invoking form
        // This has the format "CCYYYY" where "CC" is the administrative
        // domain, usually the country code, and "YYYY" is the census year 
        let censusId        = form.Census.value;
        let censusYear      = censusId.substring(2);
        if (censusYear < 1867)  // pre-confederation
            censusId        = form.Province.value + censusYear;

        // display an indicator that we are waiting for server response
        popupLoading();

        // get the district information from the server
        let distUrl = "CensusGetDistrictsJSON.php";
        if (censusYear < 1867)
        {       // pre-confederation
            distUrl += "?Census=" + prov + censusYear;
        }       // pre-confederation
        else
        {       // post-confederation
            distUrl += "?Census=" + censusId +
                        "&Province=" + prov;
        }       // post-confederation
        if ('lang' in args)
            distUrl += '&lang=' + args['lang'];

        HTTP.get(distUrl,
                 gotDistFile,
                 noDistFile);
    }       // web page has a form named distForm
}       // function onLoadProv

/************************************************************************
 *  function changeDist                                                 *
 *                                                                      *
 *  This method is called when the user selects a new district.         *
 *  This includes extending or retracting a multiple selection.         *
 *  A request is sent to the server to retrieve the list of             *
 *  subdistricts associated with the currently selected districts.      *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='District[]'>                          *
 ************************************************************************/
function changeDist()
{
    let form    = document.distForm;
    if (form)
    {                       // web page has a form named distForm
        // get the census identifier from the invoking form
        // This has the format "CCYYYY" where "CC" is the administrative
        // domain, usually the country code, and "YYYY" is the census year 
        let censusId            = form.Census.value;
        let censusYear          = censusId.substring(2);
        if (censusYear < 1867)  // pre-confederation
            censusId            = form.Province.value + censusYear;

        // identify the selected district
        // if the district selection list supports multiple selection
        // then the name of the element has array subscript brackets
        // appended by PHP convention.
        let distSelect          = form.elements['District[]'];
        if (distSelect === undefined)
            distSelect          = form.District;

        // accumulate a list of selected district numbers in an array
        let dists               = new Array();
        let options             = distSelect.options;
        for (let i = 0; i < options.length; ++i)
        {                   // check all options
            if ((options[i].selected) &&
                (options[i].value.length > 0))
                dists.push(options[i].value);
        }                   // check all options
        if (dists.length == 0)
        {                   // clear subdistricts
            let subdistSelect           = form.SubDistrict;
            subdistSelect.options.length    = 0;
            return;
        }                   // clear subdistricts

        // save the first or only district number in a cookie
        let cookie              = new Cookie("familyTree");
        cookie[censusId]        = dists[0];
        cookie.store(10);   // keep for 10 days

        // Create the query that obtains the subdistrict info for
        // the selected districts in an XML response
        let parms               = new Object();
        parms.Census            = censusId;
        parms.District          = dists;
        // get the subdistrict information file
        HTTP.post("CensusGetSubDistL.php",
                  parms,
                  gotSubDist,
                  noSubDist);
    }                       // web page has a form named distForm
}       // function changeDist

/************************************************************************
 *  function addSubDistOption                                           *
 *                                                                      *
 *  This method is called when the sub-district information XML         *
 *  document relating to a particular district is retrieved.            *
 *  The sub-district selection list is updated.                         *
 *                                                                      *
 *  Input:                                                              *
 *      name            name of the sub-district                        *
 *      optval          option value, comma separated list of           *
 *                      dist:subdist pairs                              *
 *      node            XML node with details                           *
 ************************************************************************/
function addSubDistOption(subdistSelect, name, optval, node)
{
    // set the text value to display to the user
    let text            = name + " [subdist=" + optval + "]";

    // create a new HTML Option object and add it 
    let newOption       = addOption(subdistSelect,
                                    text,
                                    optval);

    // make the additional information in the XML Option
    // available to the application without changing the
    // appearance of the HTML Option
    newOption.xmlOption = node;
}       // function addSubDistOption

/************************************************************************
 *  function gotSubDist                                                 *
 *                                                                      *
 *  This method is called when the sub-district information XML         *
 *  document relating to a particular district is retrieved.            *
 *  The sub-district selection list is updated.                         *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc          Document representing the XML file              *
 ************************************************************************/
function gotSubDist(xmlDoc)
{
    let form                        = document.distForm;
    if (form)
    {                           // web page has a form named distForm
        let subdistSelect           = form.SubDistrict;
        subdistSelect.options.length    = 0;    // clear the selection
        // action on changing sub-dist
        subdistSelect.addEventListener('change', changeSubDist);
        // add an instructional entry at the top so onchange is fired
        // if the user chooses any entry but the first
        addOption(subdistSelect,
                  "Choose a Sub-District",
                  "");
    
        if (xmlDoc.documentElement)
        {                       // input is a document
            // get the list of subdistricts to select from
            let newOptions          = xmlDoc.getElementsByTagName("option");
        
            let oldname             = "";   // previous sub-district name
            let oldnode             = null;
            let optval              = "";   // list of sub-district identifiers
            for (let i = 0; i < newOptions.length; ++i)
            {                   // loop through all the subdistrict options
                // get the source "option" node
                // Note that although this has the same contents and attributes
                // as an HTML "option" statement, it is an XML Element
                // object, not an HTML Option object.
                let node            = newOptions[i];
        
                // get the sub-district name to display to the user
                let name            = node.textContent;
        
                // consolidate options with the same name to simplify
                // user access to 1911 and 1916 censuses
                if (name != oldname)
                {               // new township
                    if (oldname != "")
                    {           // add the previous township to the list
                        addSubDistOption(subdistSelect,
                                         oldname,
                                         optval,
                                         oldnode);
                    }           // add the previous township to the list
                    // set up for next entry
                    oldname         = name;
                    oldnode         = node;
                    optval          = "";
                }               // new township
        
                // get the sub-district identifier attribute
                let value           = node.getAttribute("value");
                if ((value == null) || (value.length == 0))
                {               // cover our ass
                    value           = name;
                }               // cover our ass
                // accumulate the list of sub-district identifiers as
                // a comma-separated list
                if (optval.length > 0)
                    optval          += ",";
                optval              += value;
            }                   // loop through source "option" nodes
        
            // add the last sub-district entry to the select statement
            addSubDistOption(subdistSelect,
                             oldname,
                             optval,
                             oldnode);
        
            // if implied by the environment, set the initial selection
            let district            = args["district"];
            let subDistrict         = args["subdistrict"];
            if (subDistrict)
            {                   // set the initial subdistrict
                if ((subdistSelect.options.length > 1) &&
                    (subdistSelect.options[1].value.indexOf(":") > 0))
                {               // option format <dist>:<subdist>
                    subdistSelect.value = district + ":" + subDistrict;
                }               // option format <dist>:<subdist>
                else
                {               // option format <subdist>
                    subdistSelect.value = subDistrict;
                }               // option format <subdist>
        
                // take action for change in selection
                let evt             = new Event('change',{'bubbles':true});
                subdistSelect.dispatchEvent(evt);
            }                   // set the initial subdistrict
        }                       // input is a document
        else
            alert("QueryDetail.js: gotSubDist: xmlDoc=" + xmlDoc);
    }                           // web page has a form named distForm
}       // function gotSubDist

/************************************************************************
 *  function noSubDist                                                  *
 *                                                                      *
 *  This method is called if there is no sub-district                   *
 *  description to return.                                              *
 *  The selection list of subdistricts is cleared.                      *
 ************************************************************************/
function noSubDist()
{
    let form                            = document.distForm;
    if (form)
    {                               // web page has a form named distForm
        let subdistSelect               = form.SubDistrict;
        subdistSelect.options.length    = 0;    // clear the selection
    }                               // web page has a form named distForm
}       // function noSubDist

/************************************************************************
 *  function changeSubDist                                              *
 *                                                                      *
 *  This method is called when the user selects a new sub-district.     *
 *  If required a selection list of divisions is created in the form.   *
 *  The information for creating the list of divisions was obtained     *
 *  as part of the XML document describing the sub-districts.           *
 *  This is the change event handler of the SubDistrict selection list. *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='SubDistrict'>                         *
 ************************************************************************/
function changeSubDist()
{
    let form                    = this.form;
    // identify the selected SubDistrict
    // note that this code only supports a single selection model
    // but a single selection may represent multiple subdistricts
    // in censuses that do not officially support enumeration divisions
    let subDistSelect           = form.SubDistrict;
    let optIndex                = subDistSelect.selectedIndex;
    if (optIndex == -1)
    {                       // none selected
        return; // act as if all subdistricts selected
    }                       // none selected
    let subDistOpt              = subDistSelect.options[optIndex];
    let subDistrict             = subDistOpt.value.split(",");
       
    // if census supports divisions, display a selection list 
    // locate cell to display response in
    let divSelect               = form.Division;
    
    if (divSelect)
    {                       // form supports division selection
        divSelect.options.length= 0;    // clear the selection
        let subDistXml          = subDistOpt.xmlOption;
        if (subDistrict.length > 1)
        {                   // more than one subdistrict in township
            gotMultSD(subDistrict, divSelect);
        }                   // more than one subdistrict in township
        else
        if (subDistXml)
        {                   // may be subdivisions in XML
            gotDivs(subDistXml, divSelect);
        }                   // may be subdivisions in XML
        else
        {
            divSelect.size      = 1;
        }
    }                       // census form supports division selection
}       // function changeSubDist


/************************************************************************
 *  function changeDiv                                                  *
 *                                                                      *
 *  This method is called when the user selects a new division.         *
 *                                                                      *
 *  Input:                                                              *
 *      this            <select name="Division">                        *
 *      ev              change Event                                    *
 ************************************************************************/
function changeDiv(ev)
{
    //console.log("QueryDetail.js: changeDiv: selectedIndex=" +
    //                this.selectedIndex);
    if (this.selectedIndex == -1)
    {
        this.selectedIndex  = 0;
        this.focus();
    }
    else
        this.form.elements['Page'].focus();
}       // function changeDiv
 
/************************************************************************
 *  function gotMultSD                                                  *
 *                                                                      *
 *  This method is called when there is more than one sub-district in   *
 *  a named township.  This is the case for the 1906, 1911, 1916, and   *
 *  1921 censuses of Canada.                                            *
 *                                                                      *
 *  Input:                                                              *
 *      subDistrict     array of sub-district identifiers               *
 *      divSelect       <select name='Division'>                        *
 ************************************************************************/
function gotMultSD(subDistrict,
                   divSelect)
{
    // create a new selection element
    divSelect.size  = 5;
    divSelect.addEventListener('change', changeDiv);
    divSelect.addEventListener('keydown', keyDown);  // support advanced editing

    addOption(divSelect,
              "Choose a Division",
              "?");

    // add an HTML option for each division defined in the database
    for (let i = 0; i < subDistrict.length; ++i)
    {
        // get the "id" attribute
        let value           = subDistrict[i];
        let text            = "division " + value;

        // create a new HTML Option object and add it 
        let newOption       = addOption(divSelect,
                                        text,
                                        value);
        newOption.xmlOption = null;
    }           // loop through source "option" nodes

    // if the environment specifies a choice of division, select it
    let Division            = args["division"];
    if (Division)
    {       // set the initial division
        divSelect.value     = Division;
    }       // set the initial division

    // take action for change in selection
    // because this does not happen automatically when selectedIndex is set
    let evt                 = new Event('change',{'bubbles':true});
    divSelect.dispatchEvent(evt);
}       // function gotMultSD

/************************************************************************
 *  function gotDivs                                                    *
 *                                                                      *
 *  This method is called when the division information XML document    *
 *  relating to a particular sub-district is retrieved.                 *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc          Document representing the XML file              *
 *      divSelect       <select name='Division'>                        *
 ************************************************************************/
function gotDivs(xmlDoc, divSelect)
{
    // get the list of divisions to select from
    let newOptions      = xmlDoc.getElementsByTagName("div");
   
    if (newOptions.length > 1)
    {           // there are divisions in this subdistrict
        // create a new selection element
        divSelect.size  = 5;
        divSelect.addEventListener('change', changeDiv);
        divSelect.addEventListener('keydown', keyDown);  // support advanced editing
        divSelect.options.length= 0;    // clear the selection

        addOption(divSelect,
                  "Choose a Division",
                  "?");

        // add an HTML option for each division defined in the database
        for (let i = 0; i < newOptions.length; ++i)
        {
            // get the source "option" node
            // note that although this has the same contents and appearance
            // as an HTML "option" statement, it is an XML Element object,
            // not an HTML Option object.
            let node    = newOptions[i];

            // get the "id" attribute
            let value   = node.getAttribute("div");
            let text    = "division " + value;

            // create a new HTML Option object and add it 
            let newOption   = addOption(divSelect,
                                text,
                                value);

            // make any additional information in the XML div node
            // available to the application without changing the
            // appearance of the HTML Option
            newOption.xmlOption = node;
        }           // loop through source "option" nodes

        // if the environment specifies a choice of division, select it
        let Division    = args["division"];
        if (Division)
        {       // set the initial division
            divSelect.value = Division;
        }       // set the initial division
        else
        // if there is only one division in the sub-district, select it
        if (newOptions.length == 1)
        {
            divSelect.selectedIndex = 0;
        }
        else
        {       // no selection
            divSelect.selectedIndex = -1;
        }       // no selection

        // take action for change in selection
        // this does not happen automatically when selectedIndex is set
        let evt                 = new Event('change',{'bubbles':true});
        divSelect.dispatchEvent(evt);
    }           // there are divisions in this subdistrict
    else
    {           // empty selection list
        divSelect.size  = 1;
    }           // empty selection list
}       // function gotDivs

/************************************************************************
 *  function showCoverage                                               *
 *                                                                      *
 *  Display a web page showing the transcription coverage of this       *
 *  census.                                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Coverage'>                              *
 ************************************************************************/
function showCoverage()
{
    let form        = document.distForm;
    let censusId    = form.Census.value;
    location = "CensusUpdateStatus.php?Census=" + censusId;
}       //function showCoverage

/************************************************************************
 *  function qdKeyDown                                                  *
 *                                                                      *
 *  Handle key strokes that apply to the entire dialog window.  For     *
 *  example the key combinations Ctrl-S and Alt-Q are interpreted to    *
 *  close the window. update, as shortcut alternatives to using the     *
 *  mouse to click the Quit button                                      *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function qdKeyDown(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e   =  window.event;    // IE
    }       // browser is not W3C compliant
    let code    = e.keyCode;
//  if (code > 32)
//    alert("qdKeyDown: code=" + code + ", e.altKey=" + e.altKey);
    let form    = document.distForm;

    // take action based upon code
    if (e.ctrlKey)
    {       // ctrl key shortcuts
        if (code == 83)
        {       // letter 'S'
            form.submit();
            return false;   // do not perform standard action
        }       // letter 'S'
    }       // ctrl key shortcuts
    
    if (e.altKey)
    {       // alt key shortcuts
        switch (code)
        {
            case 81:
            {       // letter 'Q'
                form.submit();
                break;
            }       // letter 'Q'

            case 67:
            {       // letter 'C'
                form.Coverage.click();
                break;
            }       // letter 'C'

        }           // switch on key code
    }               // alt key shortcuts

    return;
}       // function qdKeyDown

