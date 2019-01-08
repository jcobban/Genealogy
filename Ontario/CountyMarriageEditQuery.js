/************************************************************************
 *  CountyMarriageEditQuery.js											*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  CountyMarriageEditQuery.php											*
 *																		*
 *  History:															*
 *		2016/01/30		created											*
 *		2016/05/31		use common function dateChanged					*
 *		2017/10/22		display result from Upper Canada using			*
 *						DistrictMarriagesEdit.php						*
 *																		*
 *  Copyright &copy; 2016 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace	= '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		        = form.elements[i];
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

		    var namePattern	= /^([a-zA-Z_]+)(\d*)$/;
		    var	id		= element.id;
		    if (id.length == 0)
				id		= element.name;
		    var rresult		= namePattern.exec(id);
		    var	column		= id;
		    var	rownum		= '';
		    if (rresult !== null)
		    {
				column		= rresult[1];
				rownum		= rresult[2];
		    }

		    switch(column.toLowerCase())
		    {		// act on specific fields
				case 'givennames':
				case 'witnessname':
				{
				    element.abbrTbl	    = GivnAbbrs;
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= changeAction;	// default handler
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}	// given names field

				case 'surname':
				{
				    element.abbrTbl	= SurnAbbrs;
				    element.onchange	= changeAction;
				    element.onkeydown	= keyDown;	// special key handling
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}	// surname field

				case 'soundex':
				{
				    element.onchange	= changeAction;
				    break;
				}	// surname field


				case 'residence':
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= changeAction;	// special handler
				    element.checkfunc	= checkAddress;
				    element.checkfunc(); 
				    break;
				}	// location fields

				case 'date':
				{
				    element.abbrTbl	= MonthAbbrs;
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= dateChanged;
				    element.checkfunc	= checkDate;
				    element.checkfunc();
				    break;
				}	// age field

				case 'volume':
				{	// numeric fields
				    element.onkeydown	= keyDown;	// key handling
				    element.onchange	= change;	// special handler
				    element.checkfunc	= checkNumber;
				    element.checkfunc();
				    break;
				}

				case 'reportno':
				{	// numeric fields
				    element.onkeydown	= keyDown;	// key handling
				    element.onchange	= changeAction;	// special handler
				    element.checkfunc	= checkNumber;
				    element.checkfunc();
				    break;
				}

				case 'itemno':
				{	// numeric fields
				    element.onkeydown	= keyDown;	// key handling
				    element.onchange	= changeAction;	// special handler
				    element.checkfunc	= checkNumber;
				    element.checkfunc();
				    break;
				}

				case 'domain':
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    element.checkfunc	= checkText;
				    element.checkfunc();
				    break;
				}

				case 'remarks':
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    element.checkfunc	= checkText;
				    element.checkfunc();
				    break;
				}

				case 'role':
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    //element.checkfunc	= checkFlagBG;
				    //element.checkfunc();
				    break;
				}

				case 'licensetype':
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    //element.checkfunc	= checkFlagBL;
				    //element.checkfunc();
				    break;
				}

				case 'status':
				case 'stats':
				{
				    element.onclick	= showStatus;
				    break;
				}

		    }		// act on specific fields
		}		// loop through all elements in the form
    }			// loop through all forms
}		// function onLoad

/************************************************************************
 *  function changeAction												*
 *																		*
 *  If search values are entered in any of the fields referencing		*
 *  individuals, then the query is directed to CountyMarriagesEdit.php	*
 *  instead of CounryMarriageReportEdit.php.							*
 ************************************************************************/
function changeAction()
{
    var	form	= this.form;
    if (form.RegDomain.value == 'CAUC')
		form.action	= 'DistrictMarriagesEdit.php';
    else
		form.action	= 'CountyMarriagesEdit.php';
}		// function changeAction

/************************************************************************
 *  function showStatus													*
 *																		*
 *  Display the volume summary.											*
 ************************************************************************/
function showStatus()
{
    var	form	= this.form;
    var	domain	= form.RegDomain.value;
    location	= 'CountyMarriageVolumeSummary.php?Domain=' + domain;
}		// function showStatus


