/************************************************************************
 *  BirthRegYearStats.js												*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  BirthRegYearStats.php												*
 *																		*
 *  History:															*
 *		2011/11/05		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2016/04/25		button can display either township stats or		*
 *						township list									*
 *						support multiple domains						*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/03/21      pass language to next script                    *
 *		2019/04/07      ensure that the paging lines can be displayed   *
 *		                within the visible portion of the browser.      *
 *		2019/05/29      handle clicking on stats where there is no      *
 *		                county or township in the registrations         *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

var lang            = 'en';
if ('lang' in args)
    lang    =       args.lang;
var	pcount			= 20;
if ('count' in args)
	pcount			= args.count;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    for (var j = 0; j < document.forms.length; j++)
    {		// loop through all forms in the page
		var form	= document.forms[j];
		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements in the form
		    element		                        = form.elements[i];
		    element.onkeydown	                = keyDown;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {	// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }	// set mouseover on containing cell
		    else
		    {	// set mouseover on input element itself
				element.onmouseover		        = eltMouseOver;
				element.onmouseout		        = eltMouseOut;
		    }	// set mouseover on input element itself

            var parts           = /^([a-zA-Z_]+)(\d+)/.exec(element.id);
            if (parts === null)
                continue;
            var column          = parts[1].toLowerCase();
            var row             = parts[2];
		    if (column == 'townstats')
		    {
				element.onclick	= showTownStats;
		    }
		}	// loop through all elements in the form
    }		// loop through all forms in the page

}		// function onLoad

/************************************************************************
 *  showTownStats														*
 *																		*
 *  When a TownStats button is clicked this function displays the		*
 *  statistics for specific year.										*
 *																		*
 *	Input:																*
 *		this        instance of <button>								*
 *		e           an Event                                            *
 ************************************************************************/
function showTownStats(e)
{
    var parts           = /^([a-zA-Z_]+)(\d+)/.exec(this.id);
    if (parts === null)
        return;
    var rownum          = parts[2];
    var regyear			= document.getElementById('RegYear').value;
    var domain			= document.getElementById('Domain').value;
    var county			= document.getElementById('County' + rownum).value;
    var town			= document.getElementById('Town' + rownum);
    var low	    		= document.getElementById('low' + rownum).value;
    var high			= document.getElementById('high' + rownum).value;

    var	dest;
    if (town)
    {
		town	        = town.value;
		dest	        = 'BirthRegResponse.php?RegDomain=' + domain +
                            '&Offset=0&Count=' + pcount +
                            '&RegYear=' + regyear + 
                            '&RegCounty=' + county + 
                            '&RegTownship=' + town + 
                            '&lang=' + lang; 
    }
    else
    if (county.length > 0)
    {
		dest	        = 'BirthRegYearStats.php?RegDomain=' + domain +
                            '&RegYear=' + regyear + 
                            '&County=' + county + 
                            '&lang=' + lang;  
    }
    else
    {
        var count       = high - low;
        if (count == 0)
            count       = 1;
        else
        if (count > pcount)
            count       = pcount;
		dest	        = 'BirthRegResponse.php?RegDomain=' + domain +
                            '&Offset=0&Count=' + count + 
                            '&RegYear=' + regyear + 
                            '&RegNum=' + low + 
                            '&lang=' + lang; 
    }
    location	        = dest;
    return false;
}		// showTownStats
