/************************************************************************
 *  PageForm.js															*
 *																		*
 *  Implement dynamic functionality of the form for editting the Page	*
 *  information table of the database.									*
 *																		*
 *  History:															*
 *		2011/04/20		improve separation of javascript and HTML		*
 *		2011/09/30		replicate down for full length of table			*
 *		2011/10/15		use shared displayHelp routine in				*
 *						../jscripts/util.js								*
 *		2013/07/23		use regexp to separate column name 				*
 *						from row number									*
 *						use actMouseOverHelp to activate popup help		*
 *						fix Alt-Home bug introduced by enclosing		*
 *						topRight button in a form						*
 *						add ripple down of image update					*
 *						remove local functions that are never referenced*
 *		2013/07/26		validate population field as numeric			*
 *						popup help for all elements in form				*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/21		use common tableKeyDown method					*
 *		2013/09/04		implement common columnClick method				*
 *		2015/04/28		improve comments								*
 *		2015/05/08		correct syntax error							*
 *						display image using DisplayImage.php			*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/06/29      first parameter of displayDialog removed        *
 *		2019/11/25      support scrollable body matched to headings     *
 *		2020/06/17      DisplayImage moved to top folder                *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

var	imagePattern	= /([0-9]+)(\.[a-z]+)$/;

/************************************************************************
 *  function getFieldByColRow											*
 *																		*
 *  Get a field in the form given its column name and row number.		*
 *																		*
 *  Input:																*
 *		colName			the name of the column in the spreadsheet	    *
 *		rowNum			the row number within the spreadsheet			*
 *		formElts		the associative array of form elements			*
 ************************************************************************/
function getFieldByColRow(colName,
						  rowNum,
						  formElts)
{
    if (rowNum < 1)
		return formElts[colName + "01"];
    else
    if (rowNum < 10)
		return formElts[colName + "0" + rowNum];
    else
		return formElts[colName + rowNum];
}	// function getFieldByColRow

/************************************************************************
 *  function changeDefault												*
 *																		*
 *  Take action when the user changes a field whose value				*
 *  may be a default.  If it is, change the presentation of				*
 *  the field to indicate it no longer has the default value.			*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text'>						*
 ************************************************************************/
function changeDefault()
{
    // change the presentation of this field
    if (this.className == "dftleft")
    {
		this.className = "act left";
    }
    else
    if (this.className == "dftright")
    {
		this.className = "act right";
    }
}		// function changeDefault

/************************************************************************
 *  specify function to invoke when page is loaded						*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  viewButtons	        												*
 *																		*
 *  An array containing all of the "View" buttons on the page			*
 ************************************************************************/
var viewButtons	= [];

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 ************************************************************************/
function onLoad()
{
    // initialize onchange handlers for selected input fields
    // in the form
    var firstElt	= null;
    var msg		= '';	// accumulate diagnostics for alert
    var	namePattern	= /^([^0-9]*)([0-9]*)$/

    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var form		= document.forms[fi];
		var formElts		= form.elements;

		for (var i = 0; i < formElts.length; i++)
		{		// loop through all form elements
		    var element		= formElts[i];
		    var name		= element.name;
		    if (!name || name.length == 0)
				name		= element.id;
		    var colName		= name;		// column name
		    var rowNum		= '';		// row number if present
		    var rgxResult	= namePattern.exec(name);
		    if (rgxResult)
		    {
				colName		= rgxResult[1];
				rowNum		= rgxResult[2];
		    }

		    // override default key processing
		    element.onkeydown	= tableKeyDown;

		    // identify first text input element
		    if (firstElt == null)
		    {		// first input element
				if (element.tagName == 'INPUT')
				{		// <input>
				    if (element.type == 'text')
				    {	// <input type='text' ...
						firstElt	= element;
						// set the focus to the first element of the first row
						firstElt.focus();
						firstElt.select();
				    }	// <input type='text' ...
				}		// <input>
		    }		// first input element

		    // identify change action for each cell
		    switch(colName)
		    {		// switch on column name
				case 'PT_Image':
				{	// image URL field
				    element.onfocus		= saveOldImage;
				    element.onchange	= changeImage;
				    break;
				}	// fields that replicate to subsequent rows

				case 'PT_Population':
				{	// number of individuals on page
				    element.checkfunc	= checkNumber;
				    element.onchange	= change;
				    break;
				}	// number of individuals on page

				case 'View':
				{	// button to view image
				    viewButtons.push(element);
				    element.onclick		= showImage;
				    break;
				}	// button to view image

				default:
				{	// other fields
				    if (element.className.substr(0,3) == "dft")
				    {	// initial value of element is default value
						element.onchange	= changeDefault;
				    }	// initial value of element is default value
				    else
				    {	// initial value of element is specific
						element.onchange	= change;
				    }	// initial value of element is specific
				    break;
				}	// other fields
		    }		// switch on column name
		}		// loop through all form elements
    }			// loop through all forms

    // enable support for hiding and revealing columns
    var dataTable		    = document.getElementById("dataTable");
    var tblHdr		        = dataTable.tHead;
    var tblHdrRow	        = tblHdr.rows[0];
    var tblBody		        = dataTable.tBodies[0];
    var tblBodyRow	        = null;
    if (tblBody.rows.length > 0)
        tblBodyRow          = tblBody.rows[0];
    for(i = 0; i < tblHdrRow.cells.length; i++)
    {		// loop through all cells of header row
		var th		        = tblHdrRow.cells[i];
		th.onclick	        = columnClick;
        var compstyle       = getComputedStyle(th);
        var bw              = compstyle.getPropertyValue('border-left-width') * 2;
        if (tblBodyRow)
        {
            var rect        = tblBodyRow.cells[i].getBoundingClientRect();
            th.style.width  = (rect.width - 6) + 'px';
        }
    }		// loop through all cells of header row

}		// function onLoad

/************************************************************************
 *  function saveOldImage												*
 *																		*
 *  This function is called when the user moves onto an image URL text	*
 *  field.																*
 *  It saves the current value of the image URL so the exact nature		*
 *  of a subsequent change made by the user can be analyzed.			*
 ************************************************************************/
function saveOldImage()
{
    this.oldValue	= this.value;
}		// function saveOldImage

/************************************************************************
 *  function changeImage												*
 *																		*
 *  This function is called when the user changes the value of the		*
 *  URL for an image.													*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text'>						*
 ************************************************************************/
function changeImage()
{
    var	oldValue	= this.oldValue;
    var	oldResult	= imagePattern.exec(oldValue);
    if (oldResult === null)
    {
		//alert("PageForm.js: changeImage: pattern match failed for image '" +
		//	oldValue + "'");
		return true;
    }
    var	oldMatchLen	= oldResult[0].length;
    var	oldPrefix	= oldValue.substring(0,oldValue.length - oldMatchLen);
    var	newValue	= this.value;
    var	newResult	= imagePattern.exec(newValue);
    var	newMatchLen	= newResult[0].length;
    var	newPrefix	= newValue.substring(0,newValue.length - newMatchLen);
    if (oldPrefix != newPrefix || oldResult[2] != newResult[2])
		return true;
	// substitutions into the template
	var parms	= {"sub"	: "",
				   "page"	: this.name.substring(8),
				   "increment"	: (newResult[1] - oldResult[1])};	

	return displayDialog('ChangeImageForm$sub',
					     parms,
					     this,	        // display relative to current field
					     rippleImages); // action for first button
}		// function changeImage

/************************************************************************
 *  function rippleImages												*
 *																		*
 *  This function is called when the user chooses the dialog option to	*
 *  apply the increment to all subsequent image URLs.					*
 *																		*
 *  Input:																*
 *		this			instance of <button type='button'>				*
 ************************************************************************/
function rippleImages()
{
    hideDialog.call(this);	// hide the dialog this button is in
    var	form		= this.form;
    var	page		= form.Page.value;
    var	increment	= parseInt(form.Increment.value);
    var	currImageElt	= document.forms.censusForm.elements['PT_Image' + page];
    var currCell	= currImageElt.parentNode;
    var	ci		= currCell.cellIndex;
    var	currRow		= currCell.parentNode;
    var	ri		= currRow.sectionRowIndex;
    var	currSection	= currRow.parentNode;
    for (++ri; ri < currSection.rows.length; ri++)
    {			// run remainder of rows in table body section
		var	row	= currSection.rows[ri];
		var	cell	= row.cells[ci];
		for (var child = cell.firstChild; child; child = child.nextSibling)
		{		// loop through children
		    if (child.nodeName == "INPUT")
		    {
				page			= child.name.substring(8);
				var	rxResult	= imagePattern.exec(child.value);
				var	pfxLen	= child.value.length - rxResult[0].length;
				var	prefix		= child.value.substring(0,
											pfxLen);
				var	newImage	= parseInt(rxResult[1]) + increment;
				newImage		= newImage.toString();
				var	olen		= rxResult[1].length;
				var	nlen		= newImage.length;
				if (nlen < olen)
				{	// pad image number
				    newImage	= "00000000".substring(0,olen - nlen) +
							  newImage;
				}	// pad image number
				child.value	= prefix + newImage + rxResult[2];
				break;
		    }
		}		// loop through children
    }			// run remainder of rows in table body section
}		// function rippleImages

/************************************************************************
 *  function showImage													*
 *																		*
 *  Display the image of the original census page.						*
 *  This is the onclick method for the button with id 'View...'.		*
 *																		*
 *  Input:																*
 *		this		<button id='View...'>								*
 ************************************************************************/
var imageTypes	= ['jpg', 'jpeg', 'gif', 'png'];

function showImage()
{
    var	form			= this.form;
    var	rownum			= this.id.substring(4);
    var	imageName		= 'PT_Image' + rownum;
    var image			= form.elements[imageName].value;
    var imageUrl		= "/DisplayImage.php?src=" +
							  image + "&fldName=" + imageName;
    var	dotPos			= image.lastIndexOf('.');
    if (dotPos >= 0)
    {
		var	imageType	= image.substring(dotPos + 1).toLowerCase();
		var	imageIndex	= imageTypes.indexOf(imageType);
		if (imageIndex == -1)
		    imageUrl		= image;
    }
    else
		imageUrl		= image;

    // reenable any View buttons that have been disabled
    for (var ib = 0; ib < viewButtons.length; ib++)
		viewButtons[ib].disabled	= false;

    // disable the button for the current page
    this.disabled		= true;

    // display the image in the right half of the window
    if (imageUrl.substring(0,23) == 'https://www.ancestry.ca')
        window.open(imageUrl, '_blank');
    else
        openFrame("imageFrame",
		          imageUrl,
		          "right");
    return false;	// do not perform default action for button
}	// function showImage

