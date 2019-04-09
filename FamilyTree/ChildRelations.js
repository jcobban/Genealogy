/************************************************************************
 *  ChildRelations.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page ChildRelations.php.											*
 *																		*
 *  History:															*
 *		2010/11/30		created											*
 *		2012/01/13		change class names								*
 *		2012/05/07		use createFromTemplate							*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2015/02/18		simplify action on field change or button click	*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2017/11/28		$rownum replaced by $idcp in added row			*
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
    var	form		= document.srcForm;

    // set action methods for elements
    form.onsubmit 	= validateForm;
    form.onreset 	= resetForm;

    // activate handling of key strokes in text input fields
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var elt	= formElts[i];
		elt.onkeydown	= keyDown;

		elt.onchange	= relChange;
		if (elt.id == 'Add')
		    elt.onclick	= addRelation;
		else
		if (elt.id.substring(0, 6) == 'Delete')
		    elt.onclick	= delRelation;
    }		// loop through all elements in the form

}		// onLoad

/************************************************************************
 *	validateForm														*
 *																		*
 *	Ensure that the data entered by the user has been minimally		    *
 *	validated before submitting the form.								*
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
 *  relChange															*
 *																		*
 *  This method is called when the user modifies the value of			*
 *  a field.															*
 *																		*
 *  Parameters:															*
 *		this points to the input element whose value has been changed.	*
 ************************************************************************/
function relChange()
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
}		// relChange

/************************************************************************
 *  addRelation															*
 *																		*
 *  This method is called when the user requests to create				*
 *  a new Relation.														*
 ************************************************************************/
function addRelation()
{
    var	table		= document.getElementById('formTable');
    var tbody		= table.tBodies[0];
    var newrownum	= tbody.rows.length;
    var	form		= this.form;
    var formElts	= form.elements;
    
    var	template	= document.getElementById('newRowTemplate');
    var	parms		= {"rownum"	: newrownum,
					   "idcp"	: newrownum + 1,
					   "relation"	: ""};	
    var newrow		= createFromTemplate(template,
							     parms,
							     null);
    tbody.appendChild(newrow);
}	// addRelation

/************************************************************************
 *  delRelation															*
 *																		*
 *  This method is called when the user requests to delete				*
 *  an existing Relation.												*
 ************************************************************************/
function delRelation()
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
    form.elements['CPRelation' + rownum].value	= '';
    form.elements['Used' + rownum].checked	= false;
    form.elements['tag1' + rownum].checked	= false;
    form.elements['qstag' + rownum].checked	= false;
}		// delRelation
