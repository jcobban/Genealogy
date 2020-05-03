/************************************************************************
 *  ReqUpdateDists.js                                                   *
 *                                                                      *
 *  Implement dynamic functionality the the web page to select          *
 *  a district of the subdistrict table to be editted.                  *
 *                                                                      *
 *  History:                                                            *
 *      2010/11/20      function getArgs moved to util.js               *
 *      2011/06/03      improve separation of Javascript and HTML       *
 *      2013/04/13      support mouse over help                         *
 *                      functionality moved from here to PHP script     *
 *      2013/08/25      use pageInit common function                    *
 *      2013/09/05      add 1871 as a special case for provinces        *
 *                      add Yukon Territory to 1911 and 1921            *
 *      2014/10/14      indices of args array are now lower case        *
 *      2015/06/02      add 1831 census                                 *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2020/05/02      use getRecordJSON.php to get domains            *
 *                      use addEventListener and dispatchEvent          *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

window.onload   = onLoad;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  The onload method of the Districts web page.                        *
 *  If the user is returning from a previous request the province       *
 *  may be specified as a search argument.                              *
 ************************************************************************/
function onLoad()
{
    // set change event handlers on Select elements
    var censusSelect        = document.distForm.Census;
    censusSelect.addEventListener('change', changeCensus);
    var evt                 = new Event('change',{'bubbles':true});
    censusSelect.dispatchEvent(evt);
    var provSelect          = document.distForm.Province;
    evt                     = new Event('change',{'bubbles':true});
    provSelect.dispatchEvent(evt);
    
    // initialize dynamic functionality of form elements
    var elements            = document.distForm.elements;
    for (var i = 0; i < elements.length; i++)
    {                   // loop through all form elements
        var element         = elements[i];
        element.onkeydown   = keyDown;
    }                   // loop through all form elements

}       // function onLoad

/************************************************************************
 *  function changeCensus                                               *
 *                                                                      *
 *  The change event handler of the Census selection.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this    instance of <select>                                    *
 *      evt     instance of Event                                       *
 ************************************************************************/
function changeCensus(evt)
{
    var censusSelect        = this;
    var censusOptions       = this.options;
    var census;

    if (this.selectedIndex >= 0)
    {           // option chosen
        var currCensusOpt       = censusOptions[this.selectedIndex];
        census                  = currCensusOpt.value;
        if (census.length > 0)
        {       // non-empty option chosen 
            var options         = {"timeout"    : false};
            HTTP.get('/getRecordJSON.php?table=Censuses&id=' + census,
                     gotDomains,
                     options);
        }       // non-empty census chosen 
    }           // option chosen

}       // function changeCensus

/************************************************************************
 *  function gotDomains                                                 *
 *                                                                      *
 *  This method is called when the JSON document representing           *
 *  the list of domains is received from the server.                    *
 ************************************************************************/
function gotDomains(obj)
{
    if (obj && typeof(obj) == 'object')
    {
        var provSelect              = document.distForm.Province;
        provSelect.options.length   = 0;    // clear the list

        for(var id in obj.domains)
        {
            var domain              = obj.domains[id];
            var option              = document.createElement('option')
            option.value            = id.substring(2);
            option.text             = domain;
            provSelect.add(option);
        }
        provSelect.selectedIndex    = 0;

        // check for province passed as a parameter
        var province                = 'ON';
        if ('province' in args)
        {
            province                = args["province"];
        }
    
        var provOpts                = provSelect.options;
        for(var i = 0; i < provOpts.length; i++)
        {
            if (provOpts[i].value == province)
            {               // found matching entry
                provSelect.selectedIndex    = i;
                break;
            }               // found matching entry
        }
    }
    else
        alert('ReqUpdateDists.js: gotDomains: ' . obj);
}       // function gotDomains

/************************************************************************
 *  function showForm                                                   *
 *                                                                      *
 *  Show the form for editting the district table.                      *
 ************************************************************************/
function showForm()
{
    document.distForm.submit();
}       // showForm
