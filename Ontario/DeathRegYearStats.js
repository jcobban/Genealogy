/************************************************************************
 *  DeathRegYearStats.js												*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  DeathRegYearStats.php												*
 *																		*
 *  History:															*
 *		2011/11/05		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2015/10/26		handle case where county name or township not	*
 *						initialized for a registration					*
 *		2016/04/25		button can display either township stats or		*
 *						township list									*
 *						support multiple domains						*
 *		2018/06/04		pass lang parameter								*
 *		2019/02/10      no longer need to call pageInit                 *
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
    lang            = args.lang;
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
		    element		        = form.elements[i];
		    element.onkeydown	= keyDown;

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
 *  function showTownStats												*
 *																		*
 *  When a TownStats button is clicked this function displays the		*
 *  statistics for specific year.										*
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id="TownStats...">                      *
 *      e               click Event                                     *
 ************************************************************************/
function showTownStats()
{
    var parts           = /^([a-zA-Z_]+)(\d+)/.exec(this.id);
    if (parts === null)
        return;
    var rownum          = parts[2];
    var regyear			= document.getElementById('RegYear').value;
    var domain			= document.getElementById('Domain').value;
    var county			= document.getElementById('County' + rownum).value;
    var town			= document.getElementById('Town' + rownum);
    var low	        	= document.getElementById('low' + rownum).value;
    var high	    	= document.getElementById('high' + rownum).value;

    var	dest;
    if (town)
    {
		town		= town.value;
		dest		= 'DeathRegResponse.php?RegDomain=' + domain +
				            '&Offset=0&Count=' + pcount +
                            '&RegYear=' + regyear +
				            '&RegCounty=' + county + 
                            '&RegTownship=' + town +
				            '&lang=' + lang; 
    }
    else
    if (county.length > 0)
    {
		dest		= 'DeathRegYearStats.php?RegDomain=' + domain +
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
		dest	= 'MarriageRegResponse.php?RegDomain=' + domain + 
                            '&Offset=0&Count=' + count + 
                            '&RegYear=' + regyear + 
                            '&RegNum=' + low + 
                            '&lang=' + lang; 
    }
    location	= dest;
    return false;
}		// function showTownStats
