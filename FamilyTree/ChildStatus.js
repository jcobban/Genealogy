/************************************************************************
 *  ChildStatus.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page ChildStatus.php.												*
 *																		*
 *  History:															*
 *		2010/11/30		created											*
 *		2012/01/13		change class names								*
 *		2012/03/08		use createFromTemplate							*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/03/12		set initial IDCS value of new row to rownum+1	*
 *		2015/02/18		simplify action on field change or button click	*
 *		2017/08/15		renamed to ChildStatus.js						*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function onLoad()
{
    var	form				= document.srcForm;

    // set action methods for elements
    form.onsubmit		 	= validateForm;
    form.onreset 			= resetForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	        = form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var elt	            = formElts[i];
		elt.onkeydown	    = keyDown;
		elt.onchange	    = statusChange;

        var id              = '';
        if (elt.hasAttribute('id'))
            id              = elt.id;
        else
            id              = elt.name;

        var matches         = /^([a-zA-Z_$%]+)(\d*)$/.exec(id);
        var column          = matches[1].toLowerCase();
        var idcs            = matches[2];
		if (column == 'add')
		    elt.onclick	    = addStatus;
		else
		if (column == 'delete')
		    elt.onclick	    = delStatus;
        if (idcs == 1)
            elt.disabled    = true;
    }		// loop through all elements in the form

}		// onLoad

/************************************************************************
 *  validateForm														*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  resetForm															*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  statusChange														*
 *																		*
 *  This method is called when the user modifies the value of			*
 *  a field.															*
 *																		*
 *  Parameters:															*
 *		this points to the input element whose value has been changed.	*
 ************************************************************************/
function statusChange()
{
    var	form		= this.form;
    var	name		= this.name;
    var	rownum		= '';
    var	result		= /^([a-zA-Z_]+)(\d+)$/.exec(name);
    if (result)
    {
		name		= result[1];
		rownum		= result[2];
		if (name == 'tag')
		{
		    name	= 'tag1';
		    rownum	= rownum.substring(1);
		}
    }
    var	chgElt		= document.getElementById('Updated' + rownum);
    if (chgElt)
		chgElt.value	= 1;
}		// statusChange

/************************************************************************
 *  addStatus															*
 *																		*
 *  This method is called when the user requests to create				*
 *  a new Status.														*
 *																		*
 *  Input:																*
 *		this		<button id='addStatus'>								*
 ************************************************************************/
function addStatus()
{
    var	table		= document.getElementById('formTable');
    var tbody		= table.tBodies[0];
    var newrownum	= tbody.rows.length;
    var	form		= this.form;
    var formElts	= form.elements;
    
    var	template	= document.getElementById('newRowTemplate');
    var	parms		= {"idcs"	: newrownum + 1,
					   "status"	: ""};	
    var newrow		= createFromTemplate(template,
							     parms,
							     null);
    tbody.appendChild(newrow);
}	// addStatus

/************************************************************************
 *  delStatus															*
 *																		*
 *  This method is called when the user requests to delete				*
 *  an existing Status.													*
 *																		*
 *  Input:																*
 *		this		<button id='Delete...'>								*
 ************************************************************************/
function delStatus()
{
    var button		= this;
    var	form		= button.form;
    var	name		= this.id;
    var	rownum		= '';
    var	result		= /^([a-zA-Z_]+)(\d+)$/.exec(name);
    if (result)
    {
		name		= result[1];
		rownum		= result[2];
    } 
    form.elements['IDCS' + rownum].type	        = 'hidden';
    form.elements['ChildStatus' + rownum].type	= 'hidden';
    form.elements['ChildStatus' + rownum].value	= '';
    form.elements['Used' + rownum].type	        = 'hidden';
    form.elements['tagi' + rownum].type	        = 'hidden';
    form.elements['qstag' + rownum].type	    = 'hidden';
}		// delStatus
