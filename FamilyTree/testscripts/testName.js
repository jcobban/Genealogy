/************************************************************************
 *  testName.js								*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page testName.php.							*
 *									*
 *  History:								*
 *	2014/11/13	created						*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/

/************************************************************************
 *  loadEdit								*
 *									*
 *  Initialize elements.						*
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
    }		// loop through all elements in the form

}		// loadEdit

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

