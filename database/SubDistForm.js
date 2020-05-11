/************************************************************************
 *  SubDistForm.js														*
 *																		*
 *  Dynamic functionality of form for editting sub-district information	*
 *  for a district of a Census of Canada								*
 *																		*
 *  History:															*
 *		2010/11/23		add column for page increment					*
 *						add button to delete row						*
 *						allow replication of columns that don't			*
 *						function capitalize								*
 *						correct enter/down from bottom row				*
 *						correct Ctl-Home and Ctl-End					*
 *						add onchange function for Pages column			*
 *		2011/03/08		query LAC database for data						*
 *		2011/06/05		add function to hide column by clicking on		*
 *						column hdr										*
 *		2011/09/24		handle error where censusForm not defined. 		*
 *						This is legitimate if user is not authorized.	*
 *						include definition values for 1916 census		*
 *						replicate down for full length of table			*
 *		2011/10/15		use shared displayHelp function from			*
 *						../jscripts/util.js								*
 *		2011/10/17		correct up and down arrow motion when a new row	*
 *						has been inserted								*
 *		2011/11/17		do not change relative frame number in next row	*
 *						if it has previously been set					*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						function getElementById							*
 *		2012/09/18		pass full census identifier to scripts			*
 *		2012/10/27		support more than 100 subdistrict per district	*
 *		2013/06/25		main form is now the 2nd form					*
 *		2013/07/13		validate some columns							*
 *						activate mouse-over help						*
 *		2013/07/17		check for duplicates							*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/17		include definition values for 1921 census		*
 *						improve keystroke handling in table				*
 *		2013/08/25		use pageInit common function					*
 *		2013/08/26		use location abbreviations table on name		*
 *		2013/09/03		activate field specific dynamic functionality	*
 *						in added rows, including spreadsheet emulation	*
 *		2013/09/04		use shared implementation of columnClick		*
 *		2013/09/07		remove unused code								*
 *						update next relative frame number if frame		*
 *						count changed									*
 *						increment all numeric id adding after last row	*
 *		2014/09/24		use AJAX to actually delete instance			*
 *		2015/03/28		reset error format flag when repeating values	*
 *		2015/07/08		move columnWiden function to CommonForm.js		*
 *		2017/08/06		correct implementation to permit changing		*
 *						sub-district id and div in existing row			*
 *						move deletion of division to SubDistUpdate.php	*
 *		2018/05/11		some numeric fields permit zero					*
 *		2018/05/21		changeReplDown changed to use new styles		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/04/07      ensure that the paging lines can be displayed   *
 *		                within the visible portion of the browser.      *
 *		2019/06/08      pass language to Pages form                     *
 *		2020/05/03      correct addition of new enumeration division    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
var lang    = 'en';
if ('lang' in args)
    lang    = args.lang;

var namePattern	= /([A-Za-z_]+)([0-9]+)/;

window.onload	= onLoadSub;

/************************************************************************
 *  function onLoadSub													*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of Window                                  *
 ************************************************************************/
function onLoadSub()
{
    document.body.onkeydown		= qsKeyDown;
    var firstElt			= null;

    // initialize dynamic functionality for selected input fields
    // in the form
    for(var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var form	= document.forms[fi];
		if (form.name == "censusForm")
		{
		    form.onsubmit	= checkForDuplicates;
		}

		for (var ei = 0; ei < form.elements.length; ei++)
		{		// loop through all elements of the form
		    var element	= form.elements[ei];

		    var result	= namePattern.exec(element.id);
		    var colName;
		    var rowNum;
		    if (result == null)
		    {
				colName		= element.id;
				rowNum		= 0;
		    }
		    else
		    {
				colName		= result[1];
				rowNum		= Number(result[2]);
		    }

		    // override default key processing
		    element.onkeydown	= tableKeyDown;

		    // identify the first input element in the form for the
		    // destination for Ctrl-Home
		    if (firstElt === null)
		    {
				if ((element.tagName == 'INPUT') && 
				    (element.type == 'text'))
				    firstElt	= element;
		    }
		    switch(colName)
		    {		// identify change action for each cell
				case "SD_Name":
				{	// replicates to subsequent rows
				    element.onchange	= changeReplDown;
				    setClassByValue(colName,
							    rowNum, 
							    form.elements);
				    element.checkfunc	= checkText;
				    element.abbrTbl	= LocAbbrs;
				    element.checkfunc();
				    break;
				}	// SD_Name

				case "SD_LacReel":
				{	// replicates to subsequent rows
				    element.onchange	= changeReplDown;
				    setClassByValue(colName,
							    rowNum, 
							    form.elements);
				    element.checkfunc	= checkText;
				    element.checkfunc();
				    break;
				}	// SD_LacReel

				case "SD_LdsReel":
				case "SD_ImageBase":
				{	// replicates to subsequent rows
				    element.onchange	= changeReplDown;
				    setClassByValue(colName,
							    rowNum, 
							    form.elements);
				    element.checkfunc	= checkNumber;
				    element.checkfunc();
				    break;
				}	// SD_LdsReel, SD_ImageBase

				case "SD_Pages":
				{	// number of pages in division
				    element.onchange	= changePages;
				    element.checkfunc	= checkPositiveNumber;
				    element.checkfunc();
				    break;
				}	// number of pages in division

				case "SD_Population":
				case "SD_RelFrame":
				{	// population of division
				    element.onchange	= change;
				    element.checkfunc	= checkPositiveNumber;
				    break;
				}	// population of division

				case "SD_FrameCt":
				{	// population of division
				    element.onchange	= changeFrameCt;
				    element.checkfunc	= checkNumber;
				    break;
				}	// population of division

				case "SD_Remarks":
				{
				    element.onchange	= change;
				    element.checkfunc	= checkText;
				    element.abbrTbl	= LocAbbrs;
				    element.checkfunc();
				    break;
				}	// SD_Remarks

				case "Add":
				{	// button to add a division after the current row
				    element.onclick	= addDiv;
				    break;
				}	// Add a division after the current row

				case "Del":
				{	// button to delete current row
				    element.onclick	= delRow;
				    break;
				}	// delete current row

				case "Pages":
				{	// button to display Page table section
				    element.onclick	= showPageTable;
				    break;
				}	// button to display Page table section

				default:
				{
				    if (element.className.substr(0,3) == "dft")
				    {	// element contains default value
						element.onchange	= changeDefault;
				    }	// element contains default value
				    else
				    {	// element contains specific value
						element.onchange	= change;
				    }	// element contains specific value
				    break;
				}
		    }		// switch on column name
		}		// loop through all form elements
    }			// loop through forms

    // enable support for hiding and revealing columns
    var dataTable		        = document.getElementById("dataTable");
    var tblHdr		            = dataTable.tHead;
    var tblHdrRow	            = tblHdr.rows[0];
    for(i = 0; i < tblHdrRow.cells.length; i++)
    {		// loop through all cells of header row
		var th			        = tblHdrRow.cells[i];
		th.onclick		        = columnClick;	// left button click
		th.oncontextmenu	    = columnWiden;	// right button click
    }		// loop through all cells of header row

    // set the focus to the first element of the first row
    firstElt.focus();
    firstElt.select();

    hideRightColumn();
}		// onLoadSub

/************************************************************************
 *  function getFieldByColRow											*
 *																		*
 *  Get a field in the form given its column name and row number.		*
 *																		*
 *  Input:																*
 *		colName			the name of the column in the spreadsheet		*
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
}	// getFieldByColRow

/************************************************************************
 *  function setClassByValue											*
 *																		*
 *  Set the class name for the indicated cell of the spreadsheet		*
 *  depending upon its value.  If the value is equal to the value of	*
 *  the same cell in the previous row of the spreadsheet, then the class*
 *  is set to indicate that the cell has inherited its value from the	*
 *  previous row.  														*
 *																		*
 *  Input:																*
 *		colName			the name of the column in the spreadsheet		*
 *		rowNum			the row number within the spreadsheet			*
 *		formElts		the associative array of form elements			*
 ************************************************************************/
function setClassByValue(colName,
						 rowNum,
						 formElts)
{
    if (rowNum > 1)
    {	// not first row of table
		var field	= getFieldByColRow(colName,
								   rowNum,
								   formElts);
		var prevField	= getFieldByColRow(colName,
								   rowNum - 1,
								   formElts);

		if (prevField === undefined)
		{
		    alert("setClassByValue(colname='" + colName +
						"', rowNum='" + rowNum + "')");
		    return;
		}
		if (field.value == prevField.value)
		{	// change the presentation of this field
		    if (field.className == "act left")
		    {
				field.className = "dftleft";
		    }
		    else
		    if (field.className == "act right")
		    {
				field.className = "dftright";
		    }
		    else
		    if (field.className == "act leftnc")
		    {
				field.className = "dftleftnc";
		    }
		    else
		    if (field.className == "actrightnc")
		    {
				field.className = "dftrightnc";
		    }
		}	// change the presentation of this field
    }	// not first row of table
}	// setClassByValue

/************************************************************************
 *  function changeReplDown												*
 *																		*
 *  Take action when the user changes a field whose value is			*
 *  replicated into subsequent fields in the same column whose			*
 *  value has not yet been explicitly set.								*
 *																		*
 *  Input:																*
 *		$this			instance of <input>								*
 ************************************************************************/
function changeReplDown()
{
    // perform common functonality
    changeElt(this);

    // change the presentation of the current field
    if (this.className.substr(0, 4) == "same")
    {
		this.className = "black" + this.className.substr(4);
    }

    // update the presented values of this field in subsequent rows
    var	cell		= this.parentNode;
    if (cell.nodeName != "TD")
		throw new Error("SubDistForm.js: replDown: this is child of <" +
						cell.nodeName + ">");
    var	column		= cell.cellIndex;
    var	row		= cell.parentNode;
    if (row.nodeName != "TR")
		throw new Error("SubDistForm.js: replDown: cell is child of <" + 
						row.nodeName + ">");
    var rowNum		= row.sectionRowIndex;
    var	tbody		= row.parentNode;
    if (tbody.nodeName != "TBODY")
		throw new Error("SubDistForm.js: replDown: row is child of <" + 
						tbody.nodeName + ">");
    var	newValue	= this.value;

    for (rowNum++; rowNum < tbody.rows.length; rowNum++)
    {
		row		= tbody.rows[rowNum];
		cell		= row.cells[column];
		// field is first element under cell
		var field	= cell.firstChild;
		while(field && field.nodeType != 1)
		    field	= field.nextSibling;

		if (field === undefined)
		    throw new Error("SubDistForm.js: replDown: row.cells[" + 
							column + "] is undefined");
		var className	= field.className;
		if (className.substr(0,4) == "same")
		{	// alter value to match modified field
		    field.value	= this.value;
		    if (className.substr(className.length - 5) == 'error')
				field.className	= className.substr(0,
									   className.length - 5);
		}	// alter value to match modified field
		else
		    break;	// stop replicating value on first explicit cell
    }		// loop to end of page
    if (this.checkfunc)
		this.checkfunc();

}		// changeReplDown

/************************************************************************
 *  function changeDefault												*
 *																		*
 *  Take action when the user changes a field whose value				*
 *  may be a default.  If it is, change the presentation of				*
 *  the field.															*
 *																		*
 *  Input:																*
 *		$this			instance of <input>								*
 ************************************************************************/
function changeDefault()
{
    // perform common functonality
    changeElt(this);

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
    else
    if (this.className == "dftleftnc")
    {
		this.className = "act leftnc";
    }
    else
    if (this.className == "dftrightnc")
    {
		this.className = "actrightnc";
    }
    if (this.checkfunc)
		this.checkfunc();
}		// changeDefault

/************************************************************************
 *  censusLinesPerPage												    *
 ************************************************************************/
var censusLinesPerPage	= new Array();
censusLinesPerPage[1851]	= 50;	
censusLinesPerPage[1861]	= 50;	
censusLinesPerPage[1871]	= 20;	
censusLinesPerPage[1881]	= 25;	
censusLinesPerPage[1891]	= 25;	
censusLinesPerPage[1901]	= 50;	
censusLinesPerPage[1906]	= 40;	
censusLinesPerPage[1911]	= 50;
censusLinesPerPage[1916]	= 50;
censusLinesPerPage[1921]	= 50;

/************************************************************************
 *  censusPagesPerFrame												    *
 ************************************************************************/
var censusPagesPerFrame	= new Array();
censusPagesPerFrame[1851]	= 0.5;	
censusPagesPerFrame[1861]	= 0.5;	
censusPagesPerFrame[1871]	= 2;	
censusPagesPerFrame[1881]	= 2;	
censusPagesPerFrame[1891]	= 2;	
censusPagesPerFrame[1901]	= 1;	
censusPagesPerFrame[1906]	= 2;	
censusPagesPerFrame[1911]	= 1;
censusPagesPerFrame[1916]	= 1;
censusPagesPerFrame[1921]	= 1;

/************************************************************************
 *  function changePages												*
 *																		*
 *  Take action when the user changes the number of pages in the		*
 *  division.															*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text'>						*
 ************************************************************************/
function changePages()
{
    var	re		= /^[0-9]+$/;
    var	pages		= this.value;
    if (re.test(pages))
    {			// value is all numeric
		var colName		= this.id.substr(0, 8);
		var rowNum		= this.id.substr(8);
		var form		= this.form;
		var censusId		= form.Census.value;
		var censusYear		= censusId.substr(2,4);
		var linesPerPage	= censusLinesPerPage[censusYear];
		var pagesPerFrame	= censusPagesPerFrame[censusYear];
		var popElt		= form.elements["SD_Population" + rowNum];
		popElt.value		= pages*linesPerPage -
								Math.floor(linesPerPage/2);
		var fcElt		= form.elements["SD_FrameCt" + rowNum];
		fcElt.value		= Math.ceil((pages - 0 + 1)/pagesPerFrame);
		var rfElt		= form.elements["SD_RelFrame" + rowNum];
		var nextRow		= parseInt(rowNum, 10) + 1;
		if (nextRow < 10)
		    nextRow		= "0" + nextRow;
		var nrfElt		= form.elements["SD_RelFrame" + nextRow];
		var nextFrame	= parseInt(rfElt.value) + parseInt(fcElt.value);
		if (nrfElt === undefined || nrfElt.value > 0)
		    alert("next RelFrame = " + nextFrame);
		else
		    nrfElt.value	= nextFrame;
    }			// value is all numeric

    if (this.checkfunc)
		this.checkfunc();
}		// changePages

/************************************************************************
 *  function changeFrameCt												*
 *																		*
 *  Take action when the user changes the number of image frames 		*
 *  in the division.													*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text'>						*
 ************************************************************************/
function changeFrameCt()
{
    var	re		= /^[0-9]+$/;
    var	frameCt		= this.value;
    if (re.test(frameCt))
    {			// value is all numeric
		var rowNum		= this.id.substr(10);
		var form		= this.form;
		var rfElt		= form.elements["SD_RelFrame" + rowNum];
		var nextRow		= parseInt(rowNum, 10) + 1;
		if (nextRow < 10)
		    nextRow		= "0" + nextRow;
		var nrfElt		= form.elements["SD_RelFrame" + nextRow];
		var nextFrame		= parseInt(rfElt.value) + parseInt(frameCt);
		if (nrfElt === undefined || nrfElt.value > 0)
		    alert("next RelFrame = " + nextFrame);
		else
		    nrfElt.value	= nextFrame;
    }			// value is all numeric

    if (this.checkfunc)
		this.checkfunc();
}		// changeFrameCt

/************************************************************************
 *  function addDiv														*
 *																		*
 *  Add a division after the current row.								*
 *  This is the onclick method of a <button>.							*
 *																		*
 *  Parameters:															*
 *		this		the invoking button									*
 ************************************************************************/
function addDiv()
{
    var	form			= this.form;
    var	census			= form.Census.value;
    var cenYear			= (census.substring(2,6)) - 0;
    var	tbl	    		= document.getElementById("dataTable");
    var cell			= this.parentNode
    var oldRow			= cell.parentNode;
    var	tblSect			= oldRow.parentNode;
    var	colnum;
    var	sdId			= null;

    // rownum is the index of the TableRow object within the rows
    // collection of the enclosing table
    var rownum	        = oldRow.rowIndex;

    // the name attribute of the button invoking this function consists
    // of the letters "Add" followed by the numeric row number which is
    // usually 2 digits long (padded with '0' if less than 10) but may
    // be 3 digits if there are more than 99 rows.
    var oLine	                    = this.id.substring(3);
    var oNumLen	                    = oLine.length;	// length of line number

    // generate a new line number to use on the input fields in the
    // added line.  This value is one greater than the current last row
    var rowNum	                    = Number(tbl.rows.length).toString(10);
    if (rowNum.length == 1)
		rowNum	                    = "0" + rowNum;	// pad to at least 2 digits

    // add new row after the row we are copying
    var newRow	                    = tbl.insertRow(rownum + 1);
    newRow.setAttribute("id", "Row" + rowNum);
    var origDivId                   = null;

    for(colnum = 0; colnum < oldRow.cells.length; colnum++)
    {			// loop through columns of old row
		var	ocell	                = oldRow.cells[colnum];
		var	ncell	                = ocell.cloneNode(false);   // shallow
		// clone the contents of the cell
		for(var child = ocell.firstChild;
		    child; 
		    child = child.nextSibling)
		{		// loop through all children of the cell
		    // clone the existing child
		    var	element	            = child.cloneNode(true);
		    if (child.id)
		    {		// child has a name
				// change the row number portion of the name
				colName		        = child.id.substring(0, 
									                child.id.length - oNumLen);
				element.setAttribute("name", colName + rowNum);
				element.name		= colName + rowNum;
				element.id		    = colName + rowNum;
				element.onkeydown	= tableKeyDown;

				switch(colName)
				{	// take action based upon column name
                    case "Orig_Div":
                    {
                        origDivId       = element.id;
                        break;
                    }

				    case "SD_Id":
				    {
						sdId	    = element;
						break;
				    }	// sub-district identifier

				    case "SD_Div":
				    {	// division number
						var	lastRowIndex	= tblSect.rows.length - 1;
						if (newRow.sectionRowIndex == lastRowIndex &&
						    sdId &&
						    sdId.value.search(/^[0-9]+$/) >= 0 &&
						    element.value == "")
						{	// last row and numeric sub-district id
						    sdId.value	= sdId.value - 0 + 1;
						}	// last row and numeric sub-district id
						else
						if (element.value == "")
						    element.value	= "1";
						else
						if (element.value.search(/^[0-9]+$/) >= 0)
						    element.value	= element.value - 0 + 1;
						else
						{	// not-numeric
						    element.value	= child.value + "1";
						}	// not-numeric
						var origCopy	= document.getElementById('Orig_Div' + rowNum);
						if (origCopy)
						    origCopy.value	= element.value;
						break;
				    }	// division number

				    case "SD_Name":
				    {	// replicates to subsequent rows
						element.onchange	= changeReplDown;
						element.checkfunc	= checkText;
						element.abbrTbl	= LocAbbrs;
						element.checkfunc();
						break;
				    }	// SD_Name

				    case "SD_LacReel":
				    {	// replicates to subsequent rows
						element.onchange	= changeReplDown;
						element.checkfunc	= checkText;
						element.checkfunc();
						break;
				    }	// SD_LacReel

				    case "SD_LdsReel":
				    case "SD_ImageBase":
				    {	// replicates to subsequent rows
						element.onchange	= changeReplDown;
						element.checkfunc	= checkNumber;
						element.checkfunc();
						break;
				    }	// SD_LdsReel, SD_ImageBase

				    case "SD_Pages":
				    {	// number of pages in division
						element.onchange	= changePages;
						element.checkfunc	= checkNumber;
						element.checkfunc();
						break;
				    }	// number of pages in division

				    case "SD_Population":
				    case "SD_FrameCt":
				    {	// population of division
						element.onchange	= change;
						element.checkfunc	= checkNumber;
						break;
				    }	// population of division

				    case "SD_Remarks":
				    {
						element.onchange	= change;
						element.checkfunc	= checkText;
						element.abbrTbl		= LocAbbrs;
						element.checkfunc();
						break;
				    }	// SD_Remarks

				    case "SD_RelFrame":
				    {	// number of pages in division
						element.value		= 0;
						element.onchange	= change;
						element.checkfunc	= checkNumber;
						break;
				    }	// number of pages in division

				    case "Add":
				    {	// add a division button
						element.onclick	= addDiv;
						break;
				    }	// add a division button

				    case "Del":
				    {	// delete a division button
						element.onclick	= delRow;
						break;
				    }	// delete a division button
				}	// take action based upon column name

		        // ensure the id value is unique on the page
				// change the row number portion of the id
				var colId	= child.id.substring(0, 
									child.id.length - oNumLen);
				element.id	= colId + rowNum;
		    }		// child has a id

		    // add the cloned child into the new row
		    var nelement    = ncell.appendChild(element);
		}		// loop through all children of the cell
		newRow.appendChild(ncell);
    }			// loop through columns of old row

    if (origDivId)
    {
        var origDivCell                 = document.getElementById(origDivId);
        if (origDivCell)
            origDivCell.value           = 'X';
        else
            alert('Cannot find element with id=' + origDivId);
    }
    alert(newRow.outerHTML);

    return false; 
}		// addDiv

/************************************************************************
 *  function checkForDuplicates											*
 *																		*
 *  Check the updated list of subdistricts for any duplicates by key	*
 *  to ensure the internal validity of the database.					*
 *																		*
 *  Parameters:															*
 *		this	the form containing the displayed table of subdistricts	*
 ************************************************************************/
function checkForDuplicates()
{
    var	form		= this;
    for (var i = 0; i < form.elements.length; i++)
    {		// loop through all elements of the form
		var element	= form.elements[i];
		if (element.id.substring(0,5) == 'SD_Id')
		{	// subdistrict identifier
		    var	cell		= element.parentNode;
		    var	row		= cell.parentNode;
		    var	rowIndex	= row.sectionRowIndex;
		    var	section		= row.parentNode;
		    var	rowId		= element.id.substring(5);
		    var	newId		= element.value;
		    var	newDivElement	= form.elements["SD_Div" + rowId];
		    var	newDiv		= newDivElement.value;
		    for (var ir = 0; ir < rowIndex; ir++)
		    {		// check for match to a preceding row
				var	oldRow	= section.rows[ir];
				var	irs	= oldRow.id.substring(3);
				var	oldId	= form.elements["SD_Id" + irs].value;
				var	oldDiv	= form.elements["SD_Div" + irs].value;
				if (oldId == newId && oldDiv == newDiv)
				{		// duplicate
				    var className	= element.className;
				    if (className.substring(className.length - 5) != 'error')
						element.className	+= 'error';
				    className		= newDivElement.className;
				    if (className.substring(className.length - 5) != 'error')
						newDivElement.className	+= 'error';
				    popupAlert("SubDistForm.js: checkForDuplicates: " +
							  "Eliminate duplicate rows and reapply.",
						       element);
				    return false;
				}		// duplicate
		    }		// check for match to a preceding row
		}	// subdistrict identifier
    }		// loop through all elements of the form
    return true;
}		// checkForDuplicates

/************************************************************************
 *  function delRow														*
 *																		*
 *  Delete the current row.												*
 *  This is the onclick method of a <button>.							*
 *																		*
 *  Parameters:															*
 *		this		the invoking <button id='Del...'>					*
 ************************************************************************/
function delRow()
{
    var	button		= this;
    var	rowId		= button.id.substring(3);
    var	currCell	= button.parentNode;
    var	currRow		= currCell.parentNode;
    var nameField	= document.getElementById('SD_Name' + rowId);
    nameField.value	= '[Delete]';
    return false;	// suppress default action
}		// delRow

/************************************************************************
 *  function gotDel														*
 *																		*
 *  This method is called when the XML file reporting the results of	*
 *  deleting an instance of SubDistrict from the database is received.	*
 *																		*
 *  Input:																*
 *		xmlDoc			Document representing the XML file				*
 ************************************************************************/
function gotDel(xmlDoc)
{

    if (xmlDoc === null)
		return noDel();
    if (xmlDoc.documentElement)
    {		// XML document
		var	root	= xmlDoc.documentElement;
		if (root.tagName == 'deleted')
		{		// correctly formatted response
		    for (var i = 0; i < root.childNodes.length; i++)
		    {		// loop through all children
				var	node	= root.childNodes[i];
				if (node.nodeType == 1)
				{	// element Node
				    var	value	= node.textContent;

				    switch(node.nodeName.toLowerCase())
				    {	// take action depending upon tag name
						case 'msg':
						{
						    alert("SubDistForm.js: gotDel: msg=" + value);
						    break;
						}

						case 'parms':
						{
						    var cNodes	= node.childNodes;
						    for (var j = 0; j < cNodes.length; j++)
						    {		// loop through child nodes
							var parm	= cNodes[j];
							if (parm.nodeType == 1 &&
							    parm.nodeName.toLowerCase() == 'id')
							{	// id parameter
							    var cValue		= parm.textContent;
							    var element		=
									document.getElementById(cValue);
							    var	currCell	= element.parentNode;
							    var	currRow		= currCell.parentNode;
							    var	currSect	= currRow.parentNode;
							    currSect.deleteRow(currRow.sectionRowIndex);
							}	// id parameter
						    }		// loop through child nodes
						    break;
						}
				    }	// take action depending upon tag name
				}	// element Node
		    }		// loop through all children
		}		// correctly formatted response
		else
		    alert("SubDistForm.js: gotDel: xmlDoc=" + tagToString(root));
    }		// XML document
    else	// not an XML document
		alert("SubDistForm.js: gotDel: xmlDoc=" + xmlDoc);
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
}		// gotDel

/************************************************************************
 *  function noDel														*
 *																		*
 *  This method is called if the script to copy the division data		*
 *  from the development server to the production server is missing.	*
 ************************************************************************/
function noDel()
{
    // hide the loading indicator
    hideLoading();	// hide "loading" indicator
    alert("SubDistForm.js: noDel: script deleteSubdistXml.php is missing.");
}		// noDel

/************************************************************************
 *  function showPageTable												*
 *																		*
 *  Display the page table.  This is invoked as the onclick method		*
 *  of a <button>.														*
 *																		*
 *  Parameters:															*
 *		this		the invoking <button id='Pages...'>					*
 ************************************************************************/
function showPageTable()
{
    var	form		= this.form;
    var	censusId	= form.Census.value;
    var	province	= form.Province.value;
    var	distId		= form.District.value;
    var	rowNum		= this.id.substring(5);
    var subdistId	= form.elements["SD_Id" + rowNum].value;
    var division	= form.elements["SD_Div" + rowNum].value;
    var imageBase	= form.elements["SD_ImageBase" + rowNum].value;
    var relFrame	= form.elements["SD_RelFrame" + rowNum].value;

    window.open("PageForm.php?Census=" + censusId + 
						"&Province=" + province + 
						"&District=" + distId + 
						"&SubDistrict=" + subdistId + 
						"&Division=" + division +
						"&ImageBase=" + imageBase +
						"&RelFrame=" + relFrame +
                        "&lang=" + lang,
				"_blank");
}		// showPageTable

/************************************************************************
 *  function qsKeyDown													*
 *																		*
 *  Handle key strokes that apply to the entire dialog window.			*
 *  For example the key combinations Ctrl-S and Alt-U are interpreted	*
 *  to apply the update, as shortcut alternatives to using the mouse	*
 *  to click the Update Individual button								*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function qsKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
//  if (code > 32)
//    alert("qsKeyDown: code=" + code + ", e.altKey=" + e.altKey);
    var	form	= document.censusForm;

    // take action based upon code
    if (e.ctrlKey)
    {		// ctrl key shortcuts
		if (code == 83)
		{		// letter 'S'
		    form.submit();
		    return false;	// do not perform standard action
		}		// letter 'S'
    }		// ctrl key shortcuts

    if (e.altKey)
    {		// alt key shortcuts
		switch (code)
		{
		    case 85:
		    {		// letter 'U'
				form.submit();
				return false;
		    }		// letter 'U'

		}	// switch on key code
    }		// alt key shortcuts

    return true;
}		// qsKeyDown
