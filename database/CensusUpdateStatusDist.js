/************************************************************************
 *  CensusUpdateStatusDist.js											*
 *																		*
 *  Javascript code to implement dynamic functionality of				*
 *  CensusUpdateStatusDist.php.											*
 *																		*
 *  History:															*
 *		2011/10/26		created											*
 *		2011/12/08		use <button> for edit page						*
 *		2012/03/10		add <button> to upload division to production	*
 *		2012/09/16		Province removed as separate parameter to		*
 *						`scripts										*
 *		2013/05/23		add button to display surnames					*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

// identify function to invoke when page loaded
window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 *																		*
 *  Input:																*
 *		this			Window object									*
 ************************************************************************/
function onLoad()
{
    // perform common page initialization
    pageInit();

    // add mouseover actions for forward and backward links
    for (var il = 0; il < document.links.length; il++)
    {			// loop through all hyper-links
		var	linkTag		= document.links[il];
		linkTag.onmouseover	= linkMouseOver;
		linkTag.onmouseout	= linkMouseOut;
    }			// loop through all hyper-links

    // activate dynamic functionality of all fields
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {		// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }		// set mouseover on containing cell
		    else
		    {		// set mouseover on input element itself
				element.onmouseover		= eltMouseOver;
				element.onmouseout		= eltMouseOut;
		    }		// set mouseover on input element itself

		    if (element.id.substring(0,4) == 'Edit')
				element.onclick	= editDiv;
		    else
		    if (element.id.substring(0,8) == 'Surnames')
				element.onclick	= showSurnames;
		    else
		    if (element.id.substring(0,4) == 'Copy')
				element.onclick	= copyDiv;
		}	// loop through all elements in form
    }		// loop through all forms

}		// onLoad

/************************************************************************
 *  editDiv																*
 *																		*
 *  This function is called if the user clicks on the "Edit" button		*
 *  for a row.																*
 *																		*
 *  Parameters:																*
 *		this				button												*
 ************************************************************************/
function editDiv()
{
    var	rowNo		= this.id.substring(4);
    var Census		= document.getElementById('Census').value;
    var District	= document.getElementById('District').value;
    var SubDistrict	= document.getElementById('SdId' + rowNo).value;
    var	Division	= document.getElementById('Div' + rowNo).value;
    location	= 'CensusUpdateStatusDetails.php?Census=' + Census +
					'&District=' + District +
					'&SubDistrict=' + SubDistrict +
					'&Division=' + Division;
    return false;
}		// editDiv

/************************************************************************
 *  showSurnames														*
 *																		*
 *  This function is called if the user clicks on the "Surnames" button		*
 *  for a row.																*
 *																		*
 *  Parameters:																*
 *		this				button												*
 ************************************************************************/
function showSurnames()
{
    var	rowNo		= this.id.substring(8);
    var Census		= document.getElementById('Census').value;
    var District	= document.getElementById('District').value;
    var SubDistrict	= document.getElementById('SdId' + rowNo).value;
    var	Division	= document.getElementById('Div' + rowNo).value;
    location	= 'QuerySurnamesTop.php?Census=' + Census +
					'&District=' + District +
					'&SubDistrict=' + SubDistrict +
					'&Division=' + Division;
    return false;
}		// showSurnames

/************************************************************************
 *  copyDiv																*
 *																		*
 *  This function is called if the user clicks on the "Copy" button		*
 *  for a row.																*
 *																		*
 *  Parameters:																*
 *		this				button												*
 ************************************************************************/
function copyDiv()
{
    var	rowNo		= this.id.substring(4);
    var Census		= document.getElementById('Census').value;
    var District	= document.getElementById('District').value;
    var SubDistrict	= document.getElementById('SdId' + rowNo).value;
    var	Division	= document.getElementById('Div' + rowNo).value;

    // display loading indicator to user
    popupLoading(this);

    // get list of transcribed pages from server
    var	pagesUrl	= 'GetPagesInCensusDivisionXml.php?Census=' + Census +
					  '&District=' + District +
					  '&SubDistrict=' + SubDistrict +
					  '&Division=' + Division;
    HTTP.getXML(pagesUrl,
				gotPages,
				noPages);
    return false;
}		// copyDiv

/************************************************************************
 *  gotPages																*
 *																		*
 *  This method is called when the XML file reporting the list of pages		*
 *  in the transcription of the division is received.						*
 *																		*
 *  Input:																*
 *		xmlDoc				Document representing the XML file				*
 ************************************************************************/
var	pages;		// array of XML elements
var	pageIndex;	// index of next entry in pages
var	copyParms;	// parameters to pass to UploadSubdistXml.php

function gotPages(xmlDoc)
{
    if (xmlDoc === null)
		return noPages();

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator

    if (xmlDoc.documentElement && xmlDoc.documentElement.getElementsByTagName)
    {		// parameter is a valid XML document
		var	parmElt	= xmlDoc.documentElement.getElementsByTagName("parms");
		copyParms	= getParmsFromXml(parmElt[0]);
		pages		= xmlDoc.documentElement.getElementsByTagName("page");
		pageIndex	= 0;
		if (pageIndex < pages.length)
		{	// there are transcribed pages in this division
		    // get first page number
		    copyParms.Page	= pages[pageIndex].textContent.trim();
		    pageIndex++;	// increment to next page in transcription
		    popupLoadingText(null,
					     "Copying page " + copyParms.Page);
		    // invoke script to copy 1st page of transcription
		    HTTP.post("UploadSubdistXml.php",
				      copyParms,
				      gotCopy,
				      noCopy);
		}	// there are transcribed pages in this division
    }		// parameter is a valid XML document
    else
		alert("CensusUpdateStatusDist.js: gotPages: " + 
		      "invalid parameter xmlDoc=" + xmlDoc);
}		// gotPages

/************************************************************************
 *  noPages																*
 *																		*
 *  This method is called if the script to obtain the list of pages		*
 *  in a census division is missing.										*
 ************************************************************************/
function noPages()
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
    alert("CensusUpdateStatusDist.js: noPages: " + 
		  "script GetPagesInCensusDivisionXml.php is missing.");
}		// noPages

/************************************************************************
 *  gotCopy				*
 *																		*
 *  This method is called when the XML file reporting the results of				*
 *  copying the division data to the production server is received.				*
 *																		*
 *  Input:				*
 *		xmlDoc				Document representing the XML file				*
 ************************************************************************/
function gotCopy(xmlDoc)
{
    if (xmlDoc === null)
		return noCopy();
    if (xmlDoc.documentElement)
    {		// XML document
		var	root	= xmlDoc.documentElement;
		if (root.tagName == 'upload')
		{		// correctly formatted response
		    for (var i = 0; i < root.childNodes.length; i++)
		    {		// loop through all children
				var	node	= root.childNodes[i];
				if (node.nodeType == 1)
				{	// element Node
				    var	value	= node.textContent;

				    switch(node.nodeName)
				    {	// take action depending upon tag name
					case 'msg':
					{
					    alert("gotCopy: msg=" + value);
					    break;
					}
				    }	// take action depending upon tag name
				}	// element Node
		    }		// loop through all children
		}		// correctly formatted response
		else
		    alert("gotCopy: xmlDoc=" + tagToString(root));
    }		// XML document
    else	// not an XML document
		alert("gotCopy: xmlDoc=" + xmlDoc);

    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
    if (pageIndex < pages.length)
    {	// there are more transcribed pages in this division
		// get first page number
		copyParms.Page	= pages[pageIndex].textContent.trim();
		pageIndex++;	// increment to next page in transcription
		popupLoadingText(null,
					 "Copying page " + copyParms.Page);

		// invoke script to copy 1st page of transcription
		HTTP.post("UploadSubdistXml.php",
				  copyParms,
				  gotCopy,
				  noCopy);
    }	// there are more transcribed pages in this division
}		// gotCopy

/************************************************************************
 *  function noCopy				                                        *
 *																		*
 *  This method is called if the script to copy the division data		*
 *  from the development server to the production server is missing.	*
 ************************************************************************/
function noCopy()
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
    alert("script UploadSubdistXml.php is missing.");
}		// noCopy
