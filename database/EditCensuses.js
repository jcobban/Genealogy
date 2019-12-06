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
 *		2019/11/28      correct addition of row and fill defaults from  *
 *		                Census record                                   *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

addEventHandler(window,'load',onLoad);

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
				{	// text fields
				    addEventHandler(element,'change',changeId);
				    addEventHandler(element,'keydown',tableKeyDown);
				    break;
				}	// text fields

				case 'linesperpage':
				case 'name':
				case 'collective':
				case 'partof':
				case 'provinces':
				{	// text fields
				    addEventHandler(element,'change',change);
				    addEventHandler(element,'keydown',tableKeyDown);
				    break;
				}	// text fields

				case 'linesperpage':
				{	// lines per page
				    element.checkfunc	= checkNumber;
				    addEventHandler(element,'change',change);
				    addEventHandler(element,'keydown',tableKeyDown);
				    break;
				}	// lines per page

				case 'delete':
				{	// delete census
				    addEventHandler(element,'click',deleteCensus);
				    break;
				}	// delete census

				case 'submit':
				{	// submit update
				    break;
				}	// submit update

				case 'add':
				{	// add census
				    addEventHandler(element,'click',addCensus);
				    break;
				}	// add census

				default:
				{	// other fields
				    addEventHandler(element,'change',change);
				    addEventHandler(element,'keydown',tableKeyDown);
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
		addEventHandler(th,'click',columnClick);	// left button click
		addEventHandler(th,'contextmenu',columnWiden);	// right button click
    }

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
    var rowNum      = body.rows.length + 1;
    if (rowNum < 10)
        rowNum      = "0" + rowNum;
    newRow.id       = 'Row' + rowNum;
    var inputs		= newRow.getElementsByTagName('input');

    for (var i = 0; i < inputs.length; i++)
    {
        var input           = inputs[i];
        var col             = input.id.substring(0, input.id.length - 2); 
        var name            = col + rowNum;
        input.id            = name;
        input.name          = name;
        if (col != 'LinesPerPage')
            input.value     = '';
        if (col == 'CensusId')
		    addEventHandler(input,'change',changeId);
        else
		    addEventHandler(input,'change',change);
		addEventHandler(input,'keydown',tableKeyDown);
    }
    body.appendChild(newRow);
    document.getElementById('CensusId' + rowNum).focus();
}		// addCensus

/************************************************************************
 *  function changeId													*
 *																		*
 *  Take action when the user changes the CensusId field                *
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 *		ev              an instance of Javascript Event                 *
 ************************************************************************/
function changeId(ev)
{
    if (!ev)
		ev	            =  window.event;

    var element         = this;
	// trim off leading and trailing spaces
	var censusId	    = element.value.trim().toUpperCase();
	element.value	    = censusId;

    var	re		        = /^[A-Z]{2,5}\d{4}$/;
    var valid           = re.test(censusId);
    setErrorFlag(element, valid);

    if (valid)
    {
        var options             = {};
        options.errorHandler    = function() {alert('script getRecordJson.php not found')};
        HTTP.get('/getRecordJson.php?table=Censuses&id=' + censusId,
                 gotCensus,
                 options);
    }

}		// function changeId

/************************************************************************
 *  function gotCensus  												*
 *																		*
 *  Take action when the Census object is retrieved from the server.    *
 *																		*
 *  Input:																*
 *		census          a Javascript object                             *
 ************************************************************************/
function gotCensus(census)
{
    var	table		= document.getElementById('dataTable');
    var	body		= table.tBodies[0];
    var lastrow     = body.rows[body.rows.length - 1];
    var rowid       = lastrow.id;
    var rowNum      = rowid.substr(3,2);
    var inputs		= lastrow.getElementsByTagName('input');

    for (var i = 0; i < inputs.length; i++)
    {               // loop through <input> tags
        var input           = inputs[i];
        var col             = input.id.substring(0, input.id.length - 2);
        switch(col.toLowerCase())
        {           // switch on field name
            case 'name':
                input.value      = census.name;
                break;

            case 'linesperpage':
                input.value     = census.linesperpage;
                break;

            case 'collective':
                if (census.collective == 1)
                    input.value     = 'Y';
                break;

            case 'provinces':
                input.value = census.provinces;
                break;

            case 'grouplines':
                input.value = census.grouplines;
                break;

            case 'lastunderline':
                input.value = census.lastunderline;
                break;

        }           // switch on field name
    }               // loop through <input> tags

    // now look for a matching Source
    var options             = {};
    options.errorHandler    = function() {alert('script getRecordJson.php not found')};
    var censusId            = census.censusid;
    var cc                  = censusId.substr(0,2);
    if (cc == 'UK' || cc == 'GB')
        cc                  = 'United Kingdom';
    var censusName          = censusId.substr(-4) + ' Census of ' + cc;
    HTTP.get('/getRecordJson.php?table=Sources&srcname=' + censusName,
             gotSource,
             options);
}       // function gotCensus

/************************************************************************
 *  function gotSource  												*
 *																		*
 *  Take action when the Source object is retrieved from the server.    *
 *																		*
 *  Input:																*
 *		source          a Javascript object                             *
 ************************************************************************/
function gotSource(source)
{
    if (!('idsr' in source))
        return;
    var	table		= document.getElementById('dataTable');
    var	body		= table.tBodies[0];
    var lastrow     = body.rows[body.rows.length - 1];
    var rowid       = lastrow.id;
    var rowNum      = rowid.substr(3,2);
    var inputs		= lastrow.getElementsByTagName('input');

    for (var i = 0; i < inputs.length; i++)
    {               // loop through <input> tags
        var input           = inputs[i];
        var col             = input.id.substring(0, input.id.length - 2);
        switch(col.toLowerCase())
        {           // switch on field name
            case 'idsr':
                input.value      = source.idsr;
                break;

        }           // switch on field name
    }               // loop through <input> tags
}       // function gotCensus
