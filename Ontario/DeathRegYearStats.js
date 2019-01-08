/************************************************************************
 *  DeathRegYearStats.js						*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  DeathRegYearStats.php						*
 *									*
 *  History:								*
 *	2011/11/05	created						*
 *	2013/08/01	defer facebook initialization until after load	*
 *	2015/10/26	handle case where county name or township not	*
 *			initialized for a registration			*
 *	2016/04/25	button can display either township stats or	*
 *			township list					*
 *			support multiple domains			*
 *	2018/06/04	pass lang parameter				*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Initialize the dynamic functionality once the page is loaded	*
 ************************************************************************/
function onLoad()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    for (var j = 0; j < document.forms.length; j++)
    {		// loop through all forms in the page
	var form	= document.forms[j];
	for (var i = 0; i < form.elements.length; ++i)
	{	// loop through all elements in the form
	    element		= form.elements[i];
	    element.onkeydown	= keyDown;
    
	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    if (element.parentNode.nodeName == 'TD')
	    {	// set mouseover on containing cell
		element.parentNode.onmouseover	= eltMouseOver;
		element.parentNode.onmouseout	= eltMouseOut;
	    }	// set mouseover on containing cell
	    else
	    {	// set mouseover on input element itself
		element.onmouseover		= eltMouseOver;
		element.onmouseout		= eltMouseOut;
	    }	// set mouseover on input element itself
    
	    if (element.id.substring(0, 9) == 'TownStats')
	    {
		element.onclick	= showTownStats;
	    }
	}	// loop through all elements in the form
    }		// loop through all forms in the page
    
}		// onLoad

/************************************************************************
 *  showTownStats							*
 *									*
 *  When a TownStats button is clicked this function displays the	*
 *  statistics for specific year.					*
 ************************************************************************/
function showTownStats()
{
    var	rownum		= this.id.substring(9);
    var regyear		= document.getElementById('RegYear').value;
    var domain		= document.getElementById('Domain').value;
    var county		= document.getElementById('County' + rownum).value;
    var town		= document.getElementById('Town' + rownum);
    var	lang		= 'en';
    if ('lang' in args)
	lang		= args['lang'];
    var	dest;
    if (town)
    {
	town		= town.value;
	dest		= 'DeathRegResponse.php?RegDomain=' + domain +
			  '&Offset=0&Count=20&RegYear=' + regyear +
			  '&RegCounty=' + county + '&RegTownship=' + town +
			  '&lang=' + lang; 
    }
    else
    {
	dest		= 'DeathRegYearStats.php?RegDomain=' + domain +
			  '&RegYear=' + regyear + '&County=' + county + 
			  '&lang=' + lang; 
    }
    location	= dest;
    return false;
}		// showTownStats
