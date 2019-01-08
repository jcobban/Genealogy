/************************************************************************
 *  mergeUpdIndivid.js							*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  page mergeUpdIndivid.php.						*
 *									*
 *  History:								*
 *	2015/01/27	created						*
 *	2015/02/10	if invoked from an instance of editIndivid.php	*
 *			then update input fields in that page		*
 *			use closeFrame					*
 *	2015/06/06	if invoked from an instance of editIndivid.php	*
 *			add events copied from second to that page	*
 *	2017/03/18	pass updated given name and surname to caller	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/

/************************************************************************
 *  constants identifying the types of events and where the details	*
 *  are located within the record identified by IDIME.			*
 *  This table must match the one in the php file Citation.inc		*
 *									*
 *  Facts where IDIME points to an Individual Record tblIR		*
 ************************************************************************/
var STYPE_NAME		= 1;
var STYPE_BIRTH		= 2;
var STYPE_CHRISTEN	= 3;
var STYPE_DEATH		= 4;
var STYPE_BURIED	= 5;
var STYPE_NOTESGENERAL	= 6;
var STYPE_NOTESRESEARCH	= 7;
var STYPE_NOTESMEDICAL	= 8;
var STYPE_DEATHCAUSE	= 9;
var STYPE_LDSB		= 15;	// Baptism
var STYPE_LDSE		= 16;	// Endowment
var STYPE_LDSC		= 26;	// Confirmation
var STYPE_LDSI		= 27;	// Initiatory

/************************************************************************
 *  Facts where IDIME points to an Event Record in tblER		*
 ************************************************************************/
var STYPE_EVENT		= 30;	// Individual Event
var STYPE_MAREVENT	= 31;	// Individual Event
 
/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *									*
 *  Define the function to be called once the web page is loaded.	*
 ************************************************************************/
    window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  This function is invoked once the page is completely loaded into the*
 *  browser.  Initialize dynamic behavior of elements.			*
 ************************************************************************/
function onLoad()
{
    // common initialization
    pageInit();

    // setup feedback to invoking edit dialog
    var	opener		= null;
    var	feedbackFunc	= null;
    var feedbackParms	= {};
    if (window.frameElement && window.frameElement.opener)
	opener		= window.frameElement.opener;
    else
	opener		= window.opener;
    if (opener)
    {				// have invoking window
	var	openerForm	= opener.indForm;
	if (openerForm && openerForm.eventFeedback)
	    feedbackFunc	= openerForm.eventFeedback;
    }				// have invoking window

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
	var form	= document.forms[i];

	if (form.name == 'mainForm')
	{			// set action methods for form
	    form.onsubmit	 	= validateForm;
	    form.onreset 		= resetForm;
	}			// set action methods for form

	// enable dynamic functionality of form elements
	for(var j = 0; j < form.elements.length; j++)
	{
	    var element	= form.elements[j];

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    // take action specific to element
	    var	name;
	    var	ider		= '';
	    if (element.name && element.name.length > 0)
		name	= element.name;
	    else
		name	= element.id;

	    var	namePattern	= /([a-zA-Z]+)(\d*)/;
	    var	results		= namePattern.exec(name);
	    if (results)
	    {
		name		= results[1];
		ider		= results[2];
	    }

	    switch(name.toLowerCase())
	    {			// act on field name
		case 'submit':
		{
		    element.focus();	// allow enter key to submit form
		    break;
		}		// location name field

		case 'birthd':
		{
	 	    feedbackParms['type']	= STYPE_BIRTH;
		    feedbackParms['preferred']	= 1;
		    feedbackParms['ider']	= 0;
		    feedbackParms['date']	= element.value;
		    break;
		}

		case 'birthloc':
		{
		    feedbackParms['location']	= element.value;
		    if (feedbackFunc)
			feedbackFunc(feedbackParms);
		    feedbackParms	= {};
		    break;
		}

		case 'chrisd':
		{
		    feedbackParms['type']	= STYPE_CHRISTEN;
		    feedbackParms['preferred']	= 1;
		    feedbackParms['ider']	= 0;
		    feedbackParms['date']	= element.value;
		    break;
		}

		case 'chrisloc':
		{
		    feedbackParms['location']	= element.value
		    if (feedbackFunc)
			feedbackFunc(feedbackParms);
		    feedbackParms	= {};
		    break;
		}

		case 'deathd':
		{
		    feedbackParms['type']	= STYPE_DEATH;
		    feedbackParms['preferred']	= 1;
		    feedbackParms['ider']	= 0;
		    feedbackParms['date']	= element.value;
		    break;
		}

		case 'deathloc':
		{
		    feedbackParms['location']	= element.value
		    if (feedbackFunc)
			feedbackFunc(feedbackParms);
		    feedbackParms	= {};
		    break;
		}

		case 'buriald':
		{
		    feedbackParms['type']	= STYPE_BURIED;
		    feedbackParms['preferred']	= 1;
		    feedbackParms['ider']	= 0;
		    feedbackParms['date']	= element.value;
		    break;
		}

		case 'burialloc':
		{
		    feedbackParms['location']	= element.value;
		    if (feedbackFunc)
			feedbackFunc(feedbackParms);
		    feedbackParms	= {};
		    break;
		}

		case 'eventd':
		{
		    feedbackParms['type']	= STYPE_EVENT;
		    feedbackParms['preferred']	= 0;
		    feedbackParms['ider']	= ider;
		    feedbackParms['date']	= element.value;
		    break;
		}

		case 'eventtype':
		{
		    feedbackParms['etype']	= element.value;
		    break;
		}

		case 'eventcittype':
		{
		    feedbackParms['citType']	= element.value;
		    break;
		}

		case 'eventdescription':
		{
		    feedbackParms['description']= element.value;
		    break;
		}

		case 'eventloc':
		{
		    feedbackParms['location']	= element.value;
		    if (feedbackFunc)
			feedbackFunc(feedbackParms);
		    feedbackParms	= {};
		    break;
		}

		case 'givenname':
		{
		    feedbackParms['givenname']	= element.value;
		    break;
		}

		case 'surname':
		{
		    feedbackParms['surname']	= element.value;
		    feedbackParms['type']	= STYPE_NAME;
		    if (feedbackFunc)
			feedbackFunc(feedbackParms);
		    feedbackParms	= {};
		    break;
		}

		default:
		{
		    break;
		}
	    }			// act on field name
	}			// loop through all elements in the form
    }				// loop through all forms
}		// onLoad

/************************************************************************
 *  validateForm							*
 *									*
 *  Take action when the form is submitted.				*
 *  All of the actions have already been taken so just close the	*
 *  frame if any.							*
 *									*
 *  Input:								*
 *	this		<form name='mainForm'>				*
 ************************************************************************/
function validateForm()
{
    closeFrame();
    return true;
}		// validateForm

/************************************************************************
 *  resetForm								*
 *									*
 *  This method is called when the user requests the form		*
 *  to be reset to default values.					*
 *									*
 *  Input:								*
 *	this		<form name='mainForm'>				*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm
