/************************************************************************
 *  testChooseIndivid.js						*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page testChooseIndivid.php.						*
 *									*
 *  History:								*
 *									*
 *  Copyright &copy; 2010 James A. Cobban				*
 ************************************************************************/

window.onload	= loadEdit;

/************************************************************************
 *  loadEdit								*
 *									*
 * Initialize elements.							*
 ************************************************************************/
function loadEdit()
{
    var	form		= document.indForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;

    for (var i = 0; i < formElts.length; ++i)
    {
	var elt		= formElts[i];
	elt.onkeydown	= keyDown;
	elt.onchange	= change;	// default handler

	if (elt.id.toLowerCase() == 'choose')
	{
	    elt.onclick	= choose;
	}
    }		// loop through all elements in the form

}		// loadEdit

/************************************************************************
 *	validateForm	*
 *									*
 *	Ensure that the data entered by the user has been minimally validated	*
 *	before submitting the form.	*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *	resetForm	*
 *									*
 *	This method is called when the user requests the form	*
 *	to be reset to default values.	*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  term								*
 *									*
 *  This method is called when the user requests to close		*
 *  the window.								*
 ************************************************************************/
function term()
{
    window.close();
    return true;
}	// term

/************************************************************************
 *  choose								*
 *									*
 *  This method is called when the user requests to choose		*
 *  an individual.							*
 *									*
 *  Input:								*
 *	this		<button id='choose'>				*
 ************************************************************************/
function choose()
{
    var	form		= this.form;
    var	idir		= form.IDIR.value;
    var givenname	= form.GivenName.value;
    var	surname		= form.Surname.value;
    var	parentsIdmr	= form.ParentsIdmr.value;
    var	birthmin	= form.BirthMin.value;
    var	birthmax	= form.BirthMax.value;
    var	debug		= form.debug.value;

    window.open("/FamilyTree/chooseIndivid.php?idir=" + idir +
		"&name=" + surname + ", " + givenname +
		"&parentsIdmr=" + parentsIdmr + 
		"&birthmin=" + birthmin + 
		"&birthmax=" + birthmax + 
		"&debug=" + debug, 
		"citation",
		"width=700,height=350,status=yes,resizable=yes,scrollbars=yes");
    return true;
}	// editIndivid
