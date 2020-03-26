/************************************************************************
 *  MarriageRegYearStats.js												*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  MarriageRegYearStats.php											*
 *																		*
 *  History:															*
 *		2011/10/27		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2016/03/31		button can display either township stats or		*
 *						township list									*
 *		2016/04/25		support multiple domains						*
 *		2017/12/17		display registrations by number					*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/29      handle clicking on stats where there is no      *
 *		                county or township in the registrations         *
 *		2020/03/21      hide right column                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

var lang    = 'en';
if ('lang' in args)
    lang    = args.lang;
var	pcount			= 20;
if ('count' in args)
	pcount			= args.count;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 *                                                                      *
 *  Input:                                                              *
 *      this        Window                                              *
 *      ev          Javascript load Event                               *
 ************************************************************************/
function onLoad(ev)
{
    // activate handling of key strokes in text input fields
    var	element;
    for (var j = 0; j < document.forms.length; j++)
    {		        // loop through all forms in the page
		var form	= document.forms[j];
		for (var i = 0; i < form.elements.length; ++i)
		{	        // loop through all elements in the form
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
		    else
		    if (column == 'bynumber')
		    {
				element.onclick	= showByNumber;
		    }
		}	        // loop through all elements in the form
    }		        // loop through all forms in the page

    hideRightColumn();
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
    var low	    		= document.getElementById('low' + rownum).value;
    var high			= document.getElementById('high' + rownum).value;

    var	dest;
    if (town)
    {
		town        	= town.value;
		dest	        = 'MarriageRegResponse.php?RegDomain=' + domain + 
                            '&Offset=0&Count=' + pcount + 
                            '&RegYear=' + regyear + 
                            '&RegCounty=' + county + 
                            '&RegTownship=' + town + 
				            '&lang=' + lang; 
    }
    else
    if (county.length > 0)
    {
		dest        	= 'MarriageRegYearStats.php?RegDomain=' + domain +
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
		dest        	= 'MarriageRegResponse.php?RegDomain=' + domain + 
                            '&Offset=0&Count=' + count + 
                            '&RegYear=' + regyear + 
                            '&RegNum=' + low + 
                            '&lang=' + lang; 
    }
    location	        = dest;
    return false;
}		// function showTownStats

/************************************************************************
 *  function showByNumber												*
 *																		*
 *  When a ByNumber button is clicked this function displays the		*
 *  details for a particular township by registration number.			*
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id="ByNumber...">                       *
 *      e               click Event                                     *
 ************************************************************************/
function showByNumber()
{
    var parts           = /^([a-zA-Z_]+)(\d+)/.exec(this.id);
    if (parts === null)
        return;
    var rownum          = parts[2];
    var regyear	        = document.getElementById('RegYear').value;
    var domain	        = document.getElementById('Domain').value;
    var first	        = document.getElementById('First' + rownum).value;

    var	dest	        = 'MarriageRegResponse.php?RegDomain=' + domain + 
							'&Offset=0&Count=' + pcount + 
							'&RegYear=' + regyear + 
							'&RegNum=' + first + 
							'&order=number' + 
                            '&lang=' + lang; 
    location	        = dest;
    return false;
}		// function showByNumber
