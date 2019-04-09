/************************************************************************
 *  CensusUpdate.js														*
 *																		*
 *  This file contains the JavaScript functions that implement the		*
 *  dynamic functionality of the CensusUpdate.php script used to update	*
 *  a page of census data.  											*
 *																		*
 *  History:															*
 *		2011/09/24		created.										*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2015/02/03		move onclick methods for buttons to .js			*
 *						add a close window button so dialog can be		*
 *						closed when in a frame							*
 *		2016/01/04		change action of query button to display		*
 *						division summary								*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

var helpDiv	= null;

// invoke the function onLoad when the page has been completely loaded
window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 *																		*
 *  Input:																*
 *		this		instance of Window									*
 ************************************************************************/	
function onLoad()
{
    var	msg	= '';

    // activate dynamic functionality of page
    for(var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var form		= document.forms[fi];
		var formElts		= form.elements;
		msg		+= "<form name='" + form.name + "'>";
		var comma	= '';
		for (var i = 0; i < formElts.length; i++)
		{		// loop through all form elements
		    var element		= formElts[i];
		    var name		= element.name;
		    if (!name || name.length == 0)
				name		= element.id;
		    msg		+= comma + "name='" + name + "'";
		    comma	= ',';

		    // identify change action for each cell
		    switch(name)
		    {		// switch on field name
				case 'query':
				{
				    element.focus();
				    element.onclick	= newQuery;
				    msg	+= ": onclick=newQuery";
				    break;
				}	// new query button

				case 'nextPageButton':
				{
				    element.focus();
				    element.onclick	= nextPage;
				    msg	+= ": onclick=nextPage";
				    break;
				}	// next page button

				case 'prevPageButton':
				{
				    element.onclick	= prevPage;
				    msg	+= ": onclick=prevPage";
				    break;
				}	// previous page button

				case 'close':
				{
				    element.onclick	= closeWindow;
				    break;
				}	// previous page button

		    }		// switch on field name
		}		// loop through all form elements
    }			// loop through all forms
}		// onLoad


/************************************************************************
 *  newQuery															*
 *																		*
 *  Open the dialog to request a new census query.						*
 *																		*
 *  Input:																*
 *		this		<button id='query'>									*
 ************************************************************************/	
function newQuery()
{
    var	form			= this.form;
    form.Page.disabled		= true;
    form.nextPage.disabled	= true;
    form.prevPage.disabled	= true;
    form.Image.disabled		= true;
    form.action			= "CensusUpdateStatusDetails.php";
    form.submit();
    return false;
}		// newQuery


/************************************************************************
 *  nextPage															*
 *																		*
 *  Display the next page of the census.								*
 *																		*
 *  Input:																*
 *		this		<button id='nextPage'>								*
 ************************************************************************/	
function nextPage()
{
    var	form		= this.form;
    var	censusId	= form.Census.value;
    var	censusYear	= censusId.substring(2);
    var	msg		= '';
    for(var ie = 0; ie < form.elements.length; ie++)
    {
		var element	= form.elements[ie];
		msg	+= "elements['" + element.name + "'].value='" + element.value + "',";
    }
    form.Page.value	= form.nextPage.value;
    form.action	= "CensusForm.php";
    form.submit();
    return false;
}		// nextPage

/************************************************************************
 *  prevPage															*
 *																		*
 *  Display the previous page of the census.							*
 *																		*
 *  Input:																*
 *		this		<button id='prevPage'>								*
 ************************************************************************/	
function prevPage()
{
    var	form		= this.form;
    var	censusId	= form.Census.value;
    var	censusYear	= censusId.substring(2);
    form.Page.value	= form.prevPage.value;
    form.action	= "CensusForm.php";
    form.submit();
    return false;
}		// prevPage

/************************************************************************
 *  closeWindow															*
 *																		*
 *  Close the dialog													*
 *																		*
 *  Input:																*
 *		this		<button id='close'>									*
 ************************************************************************/	
function closeWindow()
{
    if (window.frameElement && window.frameElement.nodeName == 'IFRAME')
    {		// make invisible
		window.frameElement.style.visibility	= 'hidden';
    }		// make invisible
    else
		window.close();
    return false;
}		// closeWindow
