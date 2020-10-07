/************************************************************************
 *  Advertisers.js													    *
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  Advertisers.php													    *
 *																		*
 *  History:															*
 *		2020/01/13		created											*
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace	        = '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	    = document.forms[fi];
		trace	        += "<form";
		if (form.name.length > 0)
		    trace	    += " name='" + form.name + "'";
		if (form.id.length > 0)
		    trace	    += " id='" + form.id + "'";
		trace	        += ">";

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		                = form.elements[i];
		    trace                       += "<" + element.nodeName;
		    if (element.name.length > 0)
				trace	                += " name='" + element.name + "'";
		    if (element.id.length > 0)
				trace	                += " id='" + element.id + "' ";
		    trace	                    += ">";
		    element.onkeydown	        = keyDown;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
            var mouseOn                 = element;
		    if (element.parentNode.nodeName == 'TD')
		    {	// set mouseover on containing cell
				mouseOn                 = element.parentNode;
		    }	// set mouseover on containing cell
			mouseOn.onmouseover		    = eltMouseOver;
			mouseOn.onmouseout		    = eltMouseOut;

		    var	name					= element.name;
		    if (name.length == 0)
				name					= element.id;
		    var	column					= name;
		    var	row     				= '';
            var results 				= /^([a-zA-Z_$#]+)(\d*)/.exec(name);
            if (results)
            {
                column  				= results[1];
                row     				= results[2];
            }
            column      				= column.toLowerCase();

		    switch (column)
		    {		// act on a field from a table row
				case 'adname':
				{	    // advertiser name
				    element.helpDiv	    = 'Name';
                    element.onchange    = changeName;
				    break;
				}	    // advertiser name

				case 'ademail':
				{	    // advertiser email
				    element.helpDiv	    = 'Email';
				    break;
				}	    // advertiser email

				case 'delete':
				{	    // delete this advertiser
				    element.helpDiv	    = 'Delete';
				    element.onclick	    = deleteAdvertiser;
				    break;
				}	    // delete this advertiser

				case 'add':
				{
					element.onclick	    = addAdvertiser;
				    break;
				}
		    }			// act on a field
		}			    // loop through all elements in the form
    }				    // loop through all forms

    var nameHead            = document.getElementById('NameHead');
    var actionsHead         = document.getElementById('ActionsHead');
    var nameFoot            = document.getElementById('NameFoot');
    var actionsFoot         = document.getElementById('ActionsFoot');
    nameFoot.style.width    = nameHead.clientWidth + 'px'; 
    actionsFoot.style.width = actionsHead.clientWidth + 'px'; 
}		// function onLoad

/************************************************************************
 *  function changeName													*
 *																		*
 *  Take action when the user changes the advertiser name.				*
 *																		*
 *  Input:																*
 *		$this			instance of <input name='AdName...'>			*
 ************************************************************************/
function changeName()
{
    changeElt(this);
}		// function changeName

/************************************************************************
 *  function deleteAdvertiser											*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Delete....'				*
 ************************************************************************/
function deleteAdvertiser()
{
    var	trownum	        	= this.id.substring(6);
    var rownumelt           = document.getElementById('RowNum' + trownum);
    var recid               = rownumelt.value;
    var	form	    		= this.form;
    var	cell	    		= this.parentNode;
    var	row	        		= cell.parentNode;
    var	section	    		= row.parentNode;
    section.removeChild(row);
    var	operator			= document.createElement('input');
    operator.type			= 'hidden';
    operator.name			= 'deleteAdvertiser' + recid;
    operator.id			    = 'deleteAdvertiser' + recid;
    operator.value			= 'deleteAdvertiser';
    form.appendChild(operator);

    return false;
}		// function deleteAdvertiser

/************************************************************************
 *  function addAdvertiser												*
 *																		*
 *  When the Add advertiser button is clicked this function adds a row	*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		$this		<button type=button id='Add'>						*
 ************************************************************************/
function addAdvertiser()
{
    this.disabled	    = true;	// only permit one row to be added
    var	form		    = this.form;
    var	table		    = document.getElementById("dataTable");
    var	tbody		    = table.tBodies[0];
    var rowId           = tbody.rows.length + 1;
    var className       = 'even';
    if ((rowId % 2) == 1)
        className       = 'odd';
    var	parms	        = {'adname'	:       'New Advertiser',
                           'ademail' :      '',
                           'id' :           rowId,
                           'row' :          rowId,
                           'rowtype' :      className,
                           'count01':       '',
                           'count02':       '',
                           'count03':       '',
                           'count04':       '',
                           'count05':       '',
                           'count06':       '',
                           'count07':       '',
                           'count08':       '',
                           'count09':       '',
                           'count10':       '',
                           'count11':       '',
                           'count12':       '' };
    var	template	    = document.getElementById("Row$id");
    var	newRow		    = createFromTemplate(template,
						    			     parms,
							    		     null);
    tbody.appendChild(newRow);

    // take action when the user changes the name of the added advertiser
    var	nameElt		    = document.getElementById('AdName' + rowId);
    nameElt.focus();
    nameElt.select();
    nameElt.onchange	= changeName;

    return false;
}		// function addAdvertiser
