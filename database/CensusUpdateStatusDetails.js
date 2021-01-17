/************************************************************************
 *  CensusUpdateStatusDetails.js										*
 *																		*
 *  Javascript code to implement dynamic functionality of				*
 *  CensusUpdateStatusDetails.php.										*
 *																		*
 *  History:															*
 *		2011/10/26		created											*
 *		2011/12/08		use <button> for edit page						*
 *		2012/03/10		add <button> to upload individual page to		*
 *						production										*
 *		2012/09/17		Census input field contains census identifier	*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2015/07/08		assignment to undeclared variable censusYear	*
 *						move functions linkMouseOver, linkMouseOut,		*
 *						and goToLink to CommonForm.js					*
 *		2018/01/15		invoke CensusForm.php instead of				*
 *						CensusFormYYYY.php								*
 *		2018/02/27		do not pass province to CensusForm.php for		*
 *						post-confederation census						*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Define the function to call once the page is loaded					*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  This function is called once the page has been loaded from the		*
 *  server to initialize the dynamic functionality of the page.			*
 *																		*
 *  Parameters:															*
 *		this			instance of window						        *
 ************************************************************************/
function onLoad()
{
    // add mouseover actions for forward and backward links
    for (var il = 0; il < document.links.length; il++)
    {			// loop through all hyper-links
		var	linkTag		= document.links[il];
		linkTag.onmouseover	= linkMouseOver;
		linkTag.onmouseout	= linkMouseOut;
    }			// loop through all hyper-links

    // define dynamic functionality for all form elements
    for(var i = 0; i < document.forms.length; i++)
    {			// loop through all forms
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{			// loop through all elements in form
		    var element	= form.elements[j];

		    var	name	= element.name;
		    var	id	    = '';		// numeric portion of name
		    if (!name || name.length == 0)
				name	= element.id;
		    var namePattern	= /^([a-zA-Z]+)(\d*)$/
		    var	results		= namePattern.exec(name);
		    if (results)
		    {			// parse succeeded
				name		= results[1];
				id		= results[2];
		    }			// parse succeeded

		    switch(name.toLowerCase())
		    {			// act on alphabetic part of name
				case 'edit':
				{
				    element.onclick	= editPage;
				    break;
				}

				case 'upload':
				{
				    element.onclick	= copyPage;
				    break;
				}
		    }			// act on alphabetic part of name
		}			// loop through all elements in form
    }				// loop through all forms
}		// onLoad

/************************************************************************
 *  editPage																*
 *																		*
 *  This function is called if the user clicks on the "Edit Page"		*
 *  button for a row.														*
 *																		*
 *  Parameters:																*
 *		this				button												*
 ************************************************************************/
function editPage()
{
    var	pageNo		= this.id.substring(4);
    var censusId	= document.getElementById('Census').value;
    var censusYear;
    if (censusId.length == 6)
		censusYear	= censusId.substring(2);
    else
    {
		censusYear	= censusId;
		censusId	= 'CA' + censusId;
    }
    var province	= document.getElementById('Province').value;
    if (censusYear > 1867)
		province	= '';
    var distId		= document.getElementById('District').value;
    var subdistId	= document.getElementById('SubDistrict').value;
    var	division	= document.getElementById('Division').value;
    location	= 'CensusForm.php?CensusId=CA' + censusYear +
						'&Province=' + province +
						'&District=' + distId +
						'&SubDistrict=' + subdistId +
						'&Division=' + division +
						'&Page=' + pageNo;
    return false;
}		// editPage

/************************************************************************
 *  copyPage																*
 *																		*
 *  This function is called if the user clicks on the "Copy" button		*
 *  for a page.																*
 *																		*
 *  Parameters:																*
 *		this				<button> element								*
 ************************************************************************/
function copyPage()
{
    var census		= document.getElementById('Census').value;
    var province	= document.getElementById('Province').value;
    var district	= document.getElementById('District').value;
    var subDistrict	= document.getElementById('SubDistrict').value;
    var	division	= document.getElementById('Division').value;
    var	page		= this.id.substring(6);
    var	copyParms	= {
						'Census'	: census,
						'Province'	: province,
						'District'	: district,
						'SubDistrict'	: subDistrict,
						'Division'	: division,
						'Page'		: page
						  };

    // display loading indicator to user
    popupLoading(this);

    // invoke script to copy page of transcription
    HTTP.post("UploadSubdistXml.php",
		      copyParms,
		      gotCopy,
		      noCopy);

    return false;	// suppress default action
}		// copyDiv

/************************************************************************
 *  gotCopy																*
 *																		*
 *  This method is called when the XML file reporting the results of		*
 *  copying a page of data to the production server is received.		*
 *																		*
 *  Input:																*
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
		    alert("gotCopy: xmlDoc=" + new XMLSerializer().serializeToString(root));
    }		// XML document
    else	// not an XML document
		alert("gotCopy: xmlDoc=" + xmlDoc);
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
}		// gotCopy

/************************************************************************
 *  noCopy																*
 *																		*
 *  This method is called if the script to copy the division data		*
 *  from the development server to the production server is missing.		*
 ************************************************************************/
function noCopy()
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
    alert("CensusUpdateStatusDetails.js: noCopy: " +
		  "script UploadSubdistXml.php is missing.");
}		// noCopy
