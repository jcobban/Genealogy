/************************************************************************
 *  Surnames.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Surnames.php.													*
 *																		*
 *  History:															*
 *		2011/10/31		created											*
 *		2012/01/13		change class names								*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onloadSurnames;

/************************************************************************
 *  function onloadSurnames												*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function onloadSurnames()
{
    var	table	= document.getElementById('namesTable');
    for(var ir = 0; ir < table.rows.length; ir++)
    {		// loop through all rows of table of names
		var	row	= table.rows[ir];
		for (var ic = 0; ic < row.cells.length; ic++)
		{	// loop through all cells of table of names
		    var cell	= row.cells[ic];
		    if (cell)
            {
			    cell.onclick	= followLink;
            }
		}	// loop through all cells of table of names
    }		// loop through all rows of table of names
}		// function onloadSurnames

/************************************************************************
 *  function followLink													*
 *																		*
 *  This is the onclick method for a table cell that contains an <a>	*
 *  element.  When this cell is clicked on, it acts as if the mouse		*
 *  was clicking on the contained <a> tag.								*
 *																		*
 *  Input:																*
 *		this		table cell node										*
 ************************************************************************/
function followLink()
{
    for(var ie = 0; ie < this.childNodes.length; ie++)
    {		// loop through all children
		var node	= this.childNodes[ie];
		if (node.nodeName == 'A')
		{	// anchor node
		    location	= node.href;
		    return false;
		}	// anchor node
    }		// loop through all children
    return false;
}		// function followLink
