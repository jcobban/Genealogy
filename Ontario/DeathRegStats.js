/************************************************************************
 *  DeathRegStats.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  DeathRegStats.php													*
 *																		*
 *  History:															*
 *		2011/00/27		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2013/12/24		simplify implementation							*
 *						add support for domains other than Ontario		*
 *		2018/06/04		pass lang parameter								*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad				                                        *
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    var	element;
    for (fi = 0; fi < document.forms.length; fi++)
    {		        // loop through all forms
		var form	= document.forms[fi];
		for (var i = 0; i < form.elements.length; ++i)
		{	        // loop through all elements of form
		    element		= form.elements[i];
		    element.onkeydown	= keyDown;
	    
		    if (element.id.substring(0, 9) == 'YearStats')
		    {
				element.helpDiv	    = 'YearStats';
				element.onclick	    = showYearStats;
		    }
		}	        // loop through all elements in the form
    }		        // loop through all forms
}		// function onLoad

/************************************************************************
 *  function showYearStats												*
 *																		*
 *  When a YearStats button is clicked this function displays the		*
 *  statistics for specific year.										*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function showYearStats()
{
    var	domain	= this.id.substring(9, 13);
    var year	= this.id.substring(13);
    var	lang		= 'en';
    if ('lang' in args)
		lang		= args['lang'];
    location	    = 'DeathRegYearStats.php?RegDomain=' + domain +
					    '&RegYear=' + year + '&lang=' + lang;
    return false;
}		// showYearStats
