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
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

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
		    element		= form.elements[i];
		    element.onkeydown	= keyDown;
    
		    if (element.id.substring(0, 9) == 'TownStats')
		    {
			    element.onclick	= showTownStats;
		    }
		}	// loop through all elements in the form
    }		// loop through all forms in the page

    var dataTable               = document.getElementById('dataTable');
    var dataWidth               = dataTable.offsetWidth;
    var windowWidth             = document.body.clientWidth - 8;
    if (dataWidth > windowWidth)
        dataWidth               = windowWidth;
    var topBrowse               = document.getElementById('topBrowse');
    topBrowse.style.width       = dataWidth + "px";
    var botBrowse               = document.getElementById('botBrowse');
    if (botBrowse)
        botBrowse.style.width   = dataWidth + "px";
}		// onLoad

/************************************************************************
 *  function showTownStats												*
 *																		*
 *  When a TownStats button is clicked this function displays the		*
 *  statistics for specific year.										*
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
