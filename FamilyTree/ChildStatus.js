/************************************************************************
 *  ChildStatus.js							*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page ChildStatus.php.						*
 *									*
 *  History:								*
 *	2010/11/30	created						*
 *	2012/01/13	change class names				*
 *	2012/03/08	use createFromTemplate				*
 *	2013/05/29	use actMouseOverHelp common function		*
 *	2013/08/01	defer facebook initialization until after load	*
 *	2014/03/12	set initial IDCS value of new row to rownum+1	*
 *	2015/02/18	simplify action on field change or button click	*
 *	2017/08/15	renamed to ChildStatus.js			*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/

    window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Initialize elements.						*
 ************************************************************************/
function onLoad()
{
    pageInit();

    var	form				= document.srcForm;

    // set action methods for elements
    form.onsubmit		 	= validateForm;
    form.onreset 			= resetForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
	var elt	= formElts[i];
	elt.onkeydown	= keyDown;
	elt.onchange	= statusChange;

	// pop up help balloon if the mouse hovers over a field
	// for more than 2 seconds
	actMouseOverHelp(elt);

	if (elt.id == 'Add')
	    elt.onclick	= addStatus;
	else
	if (elt.id.substring(0, 6) == 'Delete')
	    elt.onclick	= delStatus;
    }		// loop through all elements in the form

}		// onLoad

/************************************************************************
 *  validateForm							*
 *									*
 *  Ensure that the data entered by the user has been minimally		*
 *  validated before submitting the form.				*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  resetForm								*
 *									*
 *  This method is called when the user requests the form		*
 *  to be reset to default values.					*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  statusChange							*
 *									*
 *  This method is called when the user modifies the value of		*
 *  a field.								*
 *									*
 *  Parameters:								*
 *	this points to the input element whose value has been changed.	*
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
 *  addStatus								*
 *									*
 *  This method is called when the user requests to create		*
 *  a new Status.							*
 *									*
 *  Input:								*
 *	this		<button id='addStatus'>				*
 ************************************************************************/
function addStatus()
{
    var	table		= document.getElementById('formTable');
    var tbody		= table.tBodies[0];
    var newrownum	= tbody.rows.length;
    var	form		= this.form;
    var formElts	= form.elements;
    
    var	template	= document.getElementById('newRowTemplate');
    var	parms		= {"rownum"	: newrownum,
			   "idcs"	: newrownum + 1,
			   "status"	: ""};	
    var newrow		= createFromTemplate(template,
					     parms,
					     null);
    tbody.appendChild(newrow);
}	// addStatus

/************************************************************************
 *  delStatus								*
 *									*
 *  This method is called when the user requests to delete		*
 *  an existing Status.							*
 *									*
 *  Input:								*
 *	this		<button id='Delete...'>				*
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
    form.elements['Updated' + rownum].value	= 1;
    form.elements['ChildStatus' + rownum].value	= '';
    form.elements['Used' + rownum].checked	= false;
    form.elements['tag1' + rownum].checked	= false;
    form.elements['qstag' + rownum].checked	= false;
}		// delStatus
