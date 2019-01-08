/************************************************************************
 *  EditCensuses.js														*
 *																		*
 *  Dynamic functionality of EditCensuses.php							*
 *																		*
 *  History:															*
 *		2016/01/21		created											*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  The onload method of the web page.  This is invoked after the		*
 *  web page has been loaded into the browser. 								*
 ************************************************************************/
function onLoad()
{
    var	namePattern	= /^([^0-9]*)([0-9]*)$/

    // perform common page initialization
    pageInit();

    // activate functionality for individual input elements
    for(var i = 0; i < document.forms.length; i++)
    {			// loop through all forms
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

		    // pop up help balloon if the mouse hovers over a element
		    // for more than 2 seconds
		    actMouseOverHelp(element);

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
		}		// loop through all elements
    }			// loop through all forms
}		// onLoad

/************************************************************************
 *  deleteCensus														*
 *																		*
 *  This function is called when the user requests to delete a census.		*
 *																		*
 *  Input:																*
 *		this				<button id='Delete...'> element						*
 ************************************************************************/
function deleteCensus()
{
    var	id		= this.id.substring(6);
    var censusIdElt	= document.getElementById('CensusId' + id);
    var parms	= {'censusid'		: censusIdElt.value,
				   'row'		: id};

    HTTP.post("deleteCensusXml.php",
		      parms,
		      gotDelete,
		      noDelete);

    var	cell		= this.parentNode;
    var	row		= cell.parentNode;
    var	section		= row.parentNode;
    section.removeChild(row);
}		// deleteCensus

/************************************************************************
 *  gotDelete																*
 *																		*
 *  This method is called when the XML file representing				*
 *  the deletion of the census is received.								*
 *																		*
 *  Input:																*
 *		xmlDoc		XML response file describing the deletion of the message*
 ************************************************************************/
function gotDelete(xmlDoc)
{
    var	censusId	= 'Unknown';
    var	count		= 0;
    var censusIdList	= xmlDoc.getElementsByTagName("censusid");
    if (censusIdList.length > 0)
		censusId	= censusIdList[0].textContent;
    var cmdList		= xmlDoc.getElementsByTagName("cmd");
    if (cmdList.length > 0)
		count		= cmdList[0].getAttribute('count') - 0;

    if (count == 1)
		alert("Census " + censusId + ' deleted' );
    else
		alert("EditCensuses: gotDelete: Census " + censusId + ' unexpected count=' + count );
}		// gotDelete

/************************************************************************
 *  noDelete																*
 *																		*
 *  This method is called if there is no script to delete the Census.		*
 ************************************************************************/
function noDelete()
{
    alert("EditCensuses: script deleteCensusXml.php not found on server");
}		// noDelete

/************************************************************************
 *  addCensus																*
 *																		*
 *  This function is called when the user requests to add a census.		*
 *																		*
 *  Input:																*
 *		this				<button id='Add'> element						*
 ************************************************************************/
function addCensus()
{
    var	table		= document.getElementById('dataTbl');
    var	body		= table.tBodies[0];
    var	newRow		= body.rows[0].cloneNode(true);
    var inputs		= newRow.getElementsByTagName('input');
    for(var ie = 0; ie < inputs.length; ie++)
		inputs[ie].value	= '';
    body.appendChild(newRow);
}		// addCensus
