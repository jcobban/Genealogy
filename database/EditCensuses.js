/************************************************************************
 *  EditCensuses.js														*
 *																		*
 *  Dynamic functionality of EditCensuses.php							*
 *																		*
 *  History:															*
 *		2016/01/21		created											*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/02/21      delete requested by setting name to 'Delete'    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  The onload method of the web page.  This is invoked after the		*
 *  web page has been loaded into the browser. 							*
 ************************************************************************/
function onLoad()
{
    var	namePattern	= /^([^0-9]*)([0-9]*)$/

    // activate functionality for individual input elements
    for(var i = 0; i < document.forms.length; i++)
    {			    // loop through all forms
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var	element		= form.elements[j];
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

		    // identify change action for each cell
		    switch(colName.toLowerCase())
		    {		// switch on column name
				case 'censusid':
				case 'name':
				case 'collective':
				case 'partof':
				case 'provinces':
				{	// text fields
				    element.onchange	= change;
				    element.onkeydown	= tableKeyDown;
				    break;
				}	// text fields

				case 'linesperpage':
				{	// lines per page
				    element.checkfunc	= checkNumber;
				    element.onchange	= change;
				    element.onkeydown	= tableKeyDown;
				    break;
				}	// lines per page

				case 'delete':
				{	// delete census
				    element.onclick	= deleteCensus;
				    break;
				}	// delete census

				case 'submit':
				{	// submit update
				    break;
				}	// submit update

				case 'add':
				{	// add census
				    element.onclick	= addCensus;
				    break;
				}	// add census

				default:
				{	// other fields
				    element.onchange	= change;
				    element.onkeydown	= tableKeyDown;
				    break;
				}	// other fields
		    }		// switch on column name
		}		    // loop through all elements
    }			    // loop through all forms

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

}		// function onLoad

/************************************************************************
 *  function deleteCensus												*
 *																		*
 *  This function is called when the user requests to delete a census.	*
 *																		*
 *  Input:																*
 *		this		<button id='Delete...'> element						*
 ************************************************************************/
function deleteCensus()
{
    var	id		        = this.id.substring(6);
    var censusIdElt	    = document.getElementById('CensusId' + id);
    var parms	        = {'censusid'		: censusIdElt.value,
				           'row'		    : id};

    var	cell		    = this.parentNode;
    var	row		        = cell.parentNode;
    var inputs		    = row.getElementsByTagName('input');
    for(var ic = 0; ic < inputs.length; ic++)
    {
        var element     = inputs[ic];
        element.type    = 'hidden';
    }                   // loop through cells of row
    var buttons		    = row.getElementsByTagName('button');
    for(var ic = 0; ic < buttons.length; ic++)
    {
        element         = buttons[ic];
        cell            = element.parentNode;
        row.removeChild(cell);
    }                   // loop through cells of row
    var anchors		    = row.getElementsByTagName('a');
    for(var ic = 0; ic < anchors.length; ic++)
    {
        element         = anchors[ic];
        cell            = element.parentNode;
        row.removeChild(cell);
    }
    var nameElt	        = document.getElementById('Name' + id);
    nameElt.value       = 'delete';
}		// deleteCensus

/************************************************************************
 *  function addCensus													*
 *																		*
 *  This function is called when the user requests to add a census.		*
 *																		*
 *  Input:																*
 *		this			<button id='Add'> element						*
 ************************************************************************/
function addCensus()
{
    var	table		= document.getElementById('dataTable');
    var	body		= table.tBodies[0];
    var	newRow		= body.rows[0].cloneNode(true);
    var inputs		= newRow.getElementsByTagName('input');
    body.appendChild(newRow);
}		// addCensus
