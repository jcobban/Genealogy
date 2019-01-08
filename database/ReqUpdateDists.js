/************************************************************************
 *  ReqUpdateDists.js							*
 *									*
 *  Implement dynamic functionality the the web page to select		*
 *  a district of the subdistrict table to be editted.			*
 *									*
 *  History:								*
 *	2010/11/20	function getArgs moved to util.js		*
 *	2011/06/03	improve separation of Javascript and HTML	*
 *	2013/04/13	support mouse over help				*
 *			functionality moved from here to PHP script	*
 *	2013/08/25	use pageInit common function			*
 *	2013/09/05	add 1871 as a special case for provinces	*
 *			add Yukon Territory to 1911 and 1921		*
 *	2014/10/14	indices of args array are now lower case	*
 *	2015/06/02	add 1831 census					*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

window.onload	= loadDistricts;

/************************************************************************
 *  loadDistricts
 *
 *  The onload method of the Districts web page.
 *  If the user is returning from a previous request the province
 *  may be specified as a search argument.
 ************************************************************************/
function loadDistricts()
{
    // perform common page initialization
    pageInit();

    // set onchange methods on Select elements
    var	censusSelect		= document.distForm.Census;
    censusSelect.onchange	= changeCensus;
    censusSelect.onchange();
    var	provSelect		= document.distForm.Province;
    provSelect.onchange		= showForm;
    
    // initialize dynamic functionality of form elements
    var	elements	= document.distForm.elements;
    for (var i = 0; i < elements.length; i++)
    {		// loop through all form elements
	var	element	= elements[i];
	element.onkeydown	= keyDown;

	// pop up help balloon if the mouse hovers over a field
	// for more than 2 seconds
	actMouseOverHelp(element);
    }		// loop through all form elements

}		// loadDistricts

/************************************************************************
 *  changeCensus
 *
 *  The onchange method of the Census selection.
 *
 *  Input:
 *	this	instance of <select>
 ************************************************************************/
function changeCensus()
{
    var	censusSelect		= this;
    var	censusOptions		= this.options;
    var	census;

    if (this.selectedIndex >= 0)
    {			// option chosen
	var currCensusOpt	= censusOptions[this.selectedIndex];
	var census		= currCensusOpt.value;
	if (census.length > 0)
	{		// non-empty option chosen 
	    if (census.length == 6)
		census	= census.substring(2);
	    var	provSelect		= document.distForm.Province;
	    provSelect.options.length	= 0;	// clear the list
	    switch(census)
	    {		// switch on census year
		case "1831":
		{		// pre-confederation
		    addOption(provSelect,	"Québec",		"QC");
		    provSelect.selectedIndex	= 0;
		    break;
		}		// pre-confederation

		case "1851":
		case "1861":
		{		// pre-confederation
		    addOption(provSelect,	"Canada East (Québec)",	"CE");
		    addOption(provSelect,	"Canada West (Ontario)","CW");
		    addOption(provSelect,	"New Brunswick",	"NB");
		    addOption(provSelect,	"Nova Scotia",		"NS");
		    addOption(provSelect,	"Prince Edward Island",	"PI");
		    provSelect.selectedIndex	= 1;
		    break;
		}		// pre-confederation

		case "1871":
		{		// 1st post-confederation
		    addOption(provSelect,	"All Provinces",	"");
		    addOption(provSelect,	"New Brunswick",	"NB");
		    addOption(provSelect,	"Nova Scotia",		"NS");
		    addOption(provSelect,	"Ontario",		"ON");
		    addOption(provSelect,	"Québec",		"QC");
		    provSelect.selectedIndex	= 0;
		    break;
		}		// 1st post-confederation
		
		case "1881":
		case "1891":
		case "1901":
		{		// post-confederation
		    addOption(provSelect,	"All Provinces",	"");
		    addOption(provSelect,	"British Columbia",	"BC");
		    addOption(provSelect,	"Manitoba",		"MB");
		    addOption(provSelect,	"New Brunswick",	"NB");
		    addOption(provSelect,	"Nova Scotia",		"NS");
		    addOption(provSelect,	"North-West Territories","NT");
		    addOption(provSelect,	"Ontario",		"ON");
		    addOption(provSelect,	"Prince Edward Island",	"PI");
		    addOption(provSelect,	"Québec",		"QC");
		    provSelect.selectedIndex	= 0;
		    break;
		}		// post-confederation

		case "1911":
		case "1921":
		case "1931":
		case "1941":
		case "1951":
		case "1961":
		{		// 20th century
		    addOption(provSelect,	"All Provinces",	"");
		    addOption(provSelect,	"Alberta",		"AB");
		    addOption(provSelect,	"British Columbia",	"BC");
		    addOption(provSelect,	"Manitoba",		"MB");
		    addOption(provSelect,	"New Brunswick",	"NB");
		    addOption(provSelect,	"Nova Scotia",		"NS");
		    addOption(provSelect,	"North-West Territories","NT");
		    addOption(provSelect,	"Ontario",		"ON");
		    addOption(provSelect,	"Prince Edward Island",	"PI");
		    addOption(provSelect,	"Québec",		"QC");
		    addOption(provSelect,	"Saskatchewan",		"SK");
		    addOption(provSelect,	"Yukon Territory",	"YT");
		    provSelect.selectedIndex	= 0;
		    break;
		}		// 20th century

		case "1906":
		case "1916":
		{		// prairie provinces
		    addOption(provSelect,	"All Provinces",	"");
		    addOption(provSelect,	"Alberta",		"AB");
		    addOption(provSelect,	"Manitoba",		"MB");
		    addOption(provSelect,	"Saskatchewan",		"SK");
		    provSelect.selectedIndex	= 0;
		    break;
		}		// prairie provinces
	    }			// switch on census year

	    // check for province passed as a parameter
	    var	province	= args["province"];

	    var	provOpts	= provSelect.options;
	    for(var i = 0; i < provOpts.length; i++)
	    {
		if (provOpts[i].value == province)
		{	// found matching entry
		    provSelect.selectedIndex	= i;
		    break;
		}	// found matching entry
	    }		// search for province to select
	}		// non-empty census chosen 
    }			// option chosen

}		// changeCensus

/************************************************************************
 *  showForm								*
 *									*
 *  Show the form for editting the district table.			*
 ************************************************************************/
function showForm()
{
    document.distForm.submit();
}		// showForm
