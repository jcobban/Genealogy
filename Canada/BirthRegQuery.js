/************************************************************************
 *  BirthRegQuery.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page BirthRegQuery.html.											*
 *																		*
 *  History:															*
 *		2011/02/17		improve separation of HTML and Javascript		*
 *		2011/05/21		set initial input focus on registration year	*
 *						input field										*
 *						change name of onload handler					*
 *						clean up comments								*
 *		2011/11/06		support mousover help							*
 *						add button for displaying transcription status	*
 *		2012/05/06		replace calls to getEltId with calls to			*
 *						getElementById									*
 *		2012/05/09		invoke scripts to obtain database information	*
 *						rather than loading static files				*
 *						show loading indicator							*
 *		2013/08/04		defer initialization of facebook link			*
 *		2013/12/26		use CSS for layout								*
 *		2014/08/29		change name of row limit to Limit				*
 *		2017/01/23		use common implementations of gotCountiesFile,	*
 *						noCountiesFile, and changeCounty				*
 *		2018/10/05      pass language to transcription status script    *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoadPage;

/************************************************************************
 *  onLoadPage															*
 *																		*
 *  Obtain the list of counties in the province as an XML file.			*
 *																		*
 *  Input:																*
 *		this		Window object										*
 ************************************************************************/
function onLoadPage()
{
    var	domain;
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		form.onsubmit 	= validateForm;
		form.onreset 	= resetForm;

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handling

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {		// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }		// set mouseover on containing cell
		    else
		    {		// set mouseover on input element itself
				element.onmouseover		= eltMouseOver;
				element.onmouseout		= eltMouseOut;
		    }		// set mouseover on input element itself

		    // an element whose value is passed with the update
		    // request to the server is identified by a name= attribute
		    // but elements which are used only by this script are
		    // identified by an id= attribute
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;

		    // set up dynamic functionality based on the name of the element
		    switch(name)
		    {
				case "RegDomain":
				{
				    domain	= element.value;
				    break;
				}

				case "RegYear":
				{
				    element.focus();
				    break;
				}

				case "ShowStatus":
				{
				    element.onclick	= showStatus;
				    break;
				}

				case "RegCounty":
				{
				    break;
				}

		    }	// switch on field name
		}		// loop through all elements in the form
    }		// loop through forms in the page

    popupLoading(element);
    // get the counties information file
    HTTP.getXML("CountiesListXml.php?Domain=" + domain,
				gotCountiesFile,
				noCountiesFile);

}		// onLoadPage

/************************************************************************
 *  validateForm														*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this	<form>													*
 ************************************************************************/
function validateForm()
{
    var	form	= this;
    var yearPat	= /^\d{4}$/;
    var numPat	= /^\d{1,6}$/;
    var countPat= /^\d{1,2}$/;

    var	msg	= "";
    if (form.RegYear &&
		(form.RegYear.value.length > 0) && 
		form.RegYear.value.search(yearPat) == -1)
		msg	= "Year is not 4 digit number. ";
    if (form.RegNum &&
		(form.RegNum.value.length > 0) &&
		form.RegNum.value.search(numPat) == -1)
		msg	+= "Number is not a valid number. ";
    if (form.Limit &&
		(form.Limit.value.length > 0) &&
		form.Limit.value.search(countPat) == -1)
		msg	+= "Limit is not a 1 or 2 digit number. ";

    if (msg != "")
    {
		alert(msg);
		return false;
    }
    return true;
}		// validateForm

/************************************************************************
 *  resetForm															*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *  This is required because the browser does not call the				*
 *  onchange method for form elements that have one.					*
 *																		*
 *  Input:																*
 *		this		<form>												*
 ************************************************************************/
function resetForm()
{
    var	countySelect	= document.distForm.RegCounty;
    changeCounty();	// repopulate Township selection
    return true;
}	// resetForm

/************************************************************************
 *  showStatus															*
 *																		*
 *  Switch to the transcription status page.							*
 *  This function is called when the ShowStatus button is selected.		*
 *																		*
 *  Input:																*
 *		this		<button id='ShowStatus'>							*
 ************************************************************************/	
function showStatus()
{
    var lang    = 'en';
    if ('lang' in args)
        lang    = args.lang;
    var	form	= this.form;
    location	= "BirthRegStats.php?RegDomain=" + form.RegDomain.value +
                    '&lang=' + lang;
    return false;	// do not submit form
}		// showStatus
