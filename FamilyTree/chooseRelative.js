/************************************************************************
 *  chooseRelative.js							*
 *									*
 *  Javascript code to implement dynamic functionality of the		*
 *  dialog chooseRelative.php.  See the header for that script for	*
 *  further information.						*
 *									*
 *  History:								*
 *	2011/01/16	created						*
 *	2011/04/22	support for IE7					*
 *	2012/01/07	display loading indicator while waiting for	*
 *			response from server for list of names		*
 *			only display response from server when it is	*
 *			the last outstanding response.			*
 *			display field help on mouse over		*
 *			color code names by gender			*
 *	2012/01/13	change class names				*
 *	2013/05/29	use actMouseOverHelp common function		*
 *			standardize initialization			*
 *	2013/07/31	defer setup of facebook link			*
 *			reimplement as a popup dialog			*
 *	2014/01/01	correct display of names of relatives when	*
 *			invoked on old releases of IE			*
 *	2014/04/26	remove sizeToFit				*
 *	2014/10/14	indices of args array are now lower case	*
 *	2015/05/27	use absolute URLs for AJAX			*
 *	2015/06/20	loading indicator was not displayed because	*
 *			wrong field name was used to position it	*
 *			Include names of parents and spouses in		*
 *			selection list					*
 *	2016/02/06	call pageInit on load				*
 *	2017/05/24	add debug output				*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/

/************************************************************************
 *  timer								*
 *									*
 *  This timer is started whenever the user presses a key in the input	*
 *  field and pops if 0.3 second passes without a new keystroke		*
 ************************************************************************/
var	timer	= null;

/************************************************************************
 *  loadcnt								*
 *									*
 *  This counts the number of outstanding requests to the server	*
 ************************************************************************/
var	loadcnt	= 0;

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *									*
 *  Define the function to be called once the web page is loaded.	*
 ************************************************************************/
    window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Perform initialization functions once the page is loaded.		*
 ************************************************************************/
function onLoad()
{
    pageInit();

    // initialize dynamic functionality
    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
	var	form		= document.forms[fi];
	for(var j = 0; j < form.elements.length; j++)
	{
	    var element	= form.elements[j];

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    var	name	= element.name;
	    if (name === undefined || name.length == 0)
		name	= element.id;

	    // take action specific to the element based on its name
	    switch(name)
	    {		// switch on name
		case 'Name':
		{	// name of individual
		    element.onkeypress	= nameKey;
		    element.focus();
		    element.select();
		    if (args['name'])
			element.value	= decodeURIComponent(args['name']) 
		    break;
		}	// name of individual

		case 'idir2':
		{	// list of individuals
		    element.onchange	= selectIndivid;
		    break;
		}	// list of individuals

		case 'incMarried':
		{	// whether to include married names
		    if (args['incmarried'] && args['incmarried'].length > 0)
			element.checked	= true;
		    element.onchange	= update;
		    break;
		}	// whether to include married names
	    }		// switch on name
	}		// loop through all form elements
    }			// loop through all forms

    // invoke script to obtain initial list of names for selection list
    update();
}		// onLoad

/************************************************************************
 *  gotNames								*
 *									*
 *  This method is called when the XML file representing		*
 *  the list of individuals is retrieved from the database.		*
 *  Repopulate the selection list.					*
 *									*
 *  Input:								*
 *	xmlDoc		response from the server			*
 ************************************************************************/
function gotNames(xmlDoc)
{
    loadcnt--;		// decrement number of outstanding responses
    if (loadcnt > 0)
	return;		// do not refresh if more outstanding responses
    hideLoading();	// hide "loading" indicator

    var	form	= document.indForm;
    if (xmlDoc == null)
    {
	alert('chooseRelative.js: gotNames: xmlDoc is null');
	return;
    }

    var root	= xmlDoc.documentElement;
    if (root && (root.nodeName == 'names'))
    {			// valid response
	var	select	= form.idir2;
	select.options.length	= 0;

	// put a dummy entry at the top of the selection, otherwise
	// selecting the first name does not call onchange
	var	name	= '[choose an individual]';
	var	option	= new Option(name,
				     -1,
				     false,
				     false);
	select.appendChild(option);
	option.innerHTML	= '[choose an individual]';

	// loop through immediate children of root node of XML document
	for (var i = 0; i < root.childNodes.length; i++)
	{		// loop through children of root
	    var	elt	= root.childNodes[i];

	    // each individual in the response is represented by
	    // an <indiv id='9999'> node, with subfields represented
	    // as child nodes containing text
	    if ((elt.nodeType == 1) &&
		(elt.nodeName == 'indiv'))
	    {		// individual
		var	id	= elt.getAttribute('id');
		var	fields	= {};
		for (var j = 0; j < elt.childNodes.length; j++)
		{	// loop through children of <indiv>
		    child	= elt.childNodes[j];
		    if (child.nodeType == 1)
		    {	// element
			if (child.childNodes.length > 0)
			    fields[child.nodeName]	= child.childNodes[0].nodeValue;
			else
			    fields[child.nodeName]	= '';
		    }	// element
		}	// loop through children of <indiv>

		// members of "fields" have been initialized from the XML
		name		= fields.surname;
		if (fields.maidenname && fields.maidenname != fields.surname)
		    name	+= ' (' + fields.maidenname + ')';
		name		+= ', ' + fields.givenname;

		// Javascript does not optimize multiple references to
		// the same expression the way a compiled language does
		var	birthd	= fields.birthd;
		var	deathd	= fields.deathd;
		if (birthd.length > 0 ||
		    deathd.length > 0)
		    name	+= ' (' + birthd + '-' + deathd + ')';

		var	gender		= fields.gender;

		// check for names of parents
		if (fields.parents && fields.parents.length > 1)
		{		// parent's names available
		    if (gender == 0)
			name	+= ', son of ';
		    else
		    if (gender == 1)
			name	+= ', daughter of ';
		    else
			name	+= ', child of ';
		    name	+= fields.parents;
		}		// parent's names available

		// check for name of spouse
		if (fields.families && fields.families.length > 1)
		{		// parent's names available
		    if (gender == 0)
			name	+= ', husband of ';
		    else
		    if (gender == 1)
			name	+= ', wife of ';
		    else
			name	+= ', spouse of ';
		    name	+= fields.families;
		}		// spouse's name available

		option	= new Option(name,
				     id,
				     false,
				     false);
		if (gender == 1)
		    option.className	= 'female';
		else
		    option.className	= 'male';
		option.innerHTML	= name;
		option.value		= id;
		option.surname		= fields.surname;
		option.givenname	= fields.givenname;
		option.birthd		= fields.birthd;
		option.deathd		= fields.deathd;
		select.appendChild(option);
	    }		// individual
	}		// loop through children of root
	if (debug.toLowerCase() == 'y')
	    alert("chooseRelative.js: gotNames: " + url);
    }			// valid response
    else
    {			// error
	var	msg	= "Error: ";
	if (root)
	{		// XML response
	for(var i = 0; i < root.childNodes.length; i++)
	{		// loop through children
	    var node	= root.childNodes[i];
	    if (node.nodeValue != null)
		msg	+= node.nodeValue;
	}		// loop through children
	}		// XML response
	else
	    msg	+= xmlDoc;
	alert (msg);
    }		// error
}		// gotNames

/************************************************************************
 *  noNames								*
 *									*
 *  This method is called if there is no names response document	*
 ************************************************************************/
function noNames()
{
    loadcnt--;		// decrement number of outstanding responses
    if (loadcnt > 0)
	return;		// do not refresh if more outstanding responses
    hideLoading();	// hide "loading" indicator

    alert('chooseRelative.js: noNames: ' +
	  'No response file from getIndivNamesXml.php.');
}		// noNames

/************************************************************************
 *  nameKey								*
 *									*
 *  This method is called when a key is pressed in the Name field.	*
 *  A timer is set so that when the user stops typing for 0.2 seconds	*
 *  the selection list is repopulated.					*
 *									*
 *  The timeout value is chosen so that it is longer than the normal	*
 *  time between keystrokes for experienced users, but not so long that	*
 *  the script is unresponsive.						*
 *									*
 *  Input:								*
 *	event		keydown event					*
 ************************************************************************/

function nameKey(event)
{
    if (timer)
	clearTimeout(timer);
    timer	= setTimeout(update, 500);
}		// nameKey

/************************************************************************
 *  update								*
 *									*
 *  This method is called when the user stops typing to repopulate	*
 *  the selection list based upon the current contents of the Name	*
 *  field.								*
 *  This method is called in three different ways:			*
 *	o When the page is loaded					*
 *	o When the activity timer on the name field pops		*
 *	o When the user changes selection options			*
 *  So the contents of this cannot be depended upon.			*
 ************************************************************************/
function update()
{
    var	form	= document.indForm;

    if (form)
    {		// form present
	var	idir		= form.idir1.value;
	var	name		= form.Name.value;
	var	surname		= '';
	var	givenname	= '';
	var	comma		= name.indexOf(',');
	if (comma >= 0)
	{		// comma separator between surname and given names
	    surname		= name.substring(0, comma);
	    for (var i = comma + 1; i < name.length; i++)
	    {		// trim off leading space
		if (name.substring(i, i+1) != ' ')
		    break;
	    }
	    givenname	= name.substring(i, name.length);
	}		// comma separator between surname and given names
	else
	    surname	= name;

	var	incMarried;
	if (form.incMarried && form.incMarried.checked)
	    incMarried	= 'Y';
	else
	    incMarried	= '';

	loadcnt++;	// number of outstanding loads
	if (loadcnt == 1)
	    popupLoading(form.idir2);	// display loading indicator to user

	// invoke script to obtain list of names for selection list
	var url	= '/FamilyTree/getIndivNamesXml.php?Surname=' + surname +
				"&GivenName=" + givenname +
				"&idir=" + idir +
				"&incMarried=" + incMarried +
				'&includeParents=Y' +
				'&includeSpouse=Y';
	if (debug.toLowerCase() == 'y')
	    alert("chooseRelative.js: update: url='" + url + "'");
	HTTP.getXML(url,
		    gotNames,
		    noNames);
    }		// form present
}		// update

/************************************************************************
 *  selectIndivid							*
 *									*
 *  This method is called when the user changes the selection.		*
 *									*
 *  Input:								*
 *	this -> <select id='idir2'>					*
 ************************************************************************/
function selectIndivid()
{
    var	option	= null;		// selected option
    var	idir	= -1;		// selected IDIR

    var	srcform	= document.indForm;

    if (srcform)
    {			// srcform present
	var	idir1	= srcform.idir1.value;
	var	select	= srcform.idir2;
	if (select.selectedIndex >= 0)
	{		// item selected
	    option	= select.options[select.selectedIndex];
	    idir2	= parseInt(option.value);
	}		// item selected
    }			// srcform present

    if (idir2 > 0)
    {			// individual selected
	location	= "relationshipCalculator.php?idir1=" + idir1 + 
					"&idir2=" + idir2;
    }			// individual selected
}		// select

