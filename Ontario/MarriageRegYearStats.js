/************************************************************************
 *  MarriageRegYearStats.js						*
 *									*
 *  This file implements the dynamic functionality of the web page	*
 *  MarriageRegYearStats.php						*
 *									*
 *  History:								*
 *	2011/10/27	created						*
 *	2013/08/01	defer facebook initialization until after load	*
 *	2016/03/31	button can display either township stats or	*
 *			township list					*
 *	2016/04/25	support multiple domains			*
 *	2017/12/17	display registrations by number			*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
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
	    else
	    if (element.id.substring(0, 8) == 'ByNumber')
	    {
		element.onclick	= showByNumber;
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
    var	rownum	= this.id.substring(9);
    var regyear	= document.getElementById('RegYear').value;
    var domain	= document.getElementById('Domain').value;
    var county	= document.getElementById('County' + rownum).value;
    var town	= document.getElementById('Town' + rownum);
    var	count	= 20;
    if ('count' in args)
	count	= args['count'];
    var	dest;
    if (town)
    {
	town	= town.value;
	dest	= 'MarriageRegResponse.php?RegDomain=' + domain + '&Offset=0&Count=' + count + '&RegYear=' + regyear + '&RegCounty=' + county + '&RegTownship=' + town; 
    }
    else
    {
	dest	= 'MarriageRegYearStats.php?RegDomain=' + domain + '&RegYear=' + regyear + '&County=' + county; 
    }
    location	= dest;
    return false;
}		// showTownStats

/************************************************************************
 *  showByNumber							*
 *									*
 *  When a ByNumber button is clicked this function displays the	*
 *  details for a particular township by registration number.		*
 ************************************************************************/
function showByNumber()
{
    var	rownum	= this.id.substring(8);
    var regyear	= document.getElementById('RegYear').value;
    var domain	= document.getElementById('Domain').value;
    var first	= document.getElementById('First' + rownum).value;
    var	count	= 20;
    if ('count' in args)
	count	= args['count'];
    var	dest	= 'MarriageRegResponse.php?RegDomain=' + domain + 
				'&Offset=0&Count=' + count + 
				'&RegYear=' + regyear + 
				'&RegNum=' + first + 
				'&order=number'; 
    location	= dest;
    return false;
}		// showByNumber
