/************************************************************************
 *  chooseIndivid.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  dialog chooseIndivid.php.  See the header for that script for		*
 *  further information.												*
 *																		*
 *  History:															*
 *		2010/08/29		created											*
 *		2010/11/11		move addChildToPage to editMarriage.js			*
 *		2010/12/25		Support name= parameter to initialize list.		*
 *						The script did not pay attention to initial		*
 *						value of the name field.						*
 *						Support callidir= parameter to identify a form	*
 *						in the invoking page that supports a method		*
 *						"callidir(idir)" to formally notify the invoking*
 *						page of the chosen individual.					*
 *		2011/02/22		change parameters for							*
 *						editMarriage.js:addChildToPage					*
 *						updating of database moved to editMarriage.js	*
 *		2011/02/23		support for setidir= etc. removed in favor of	*
 *						setNew method in invoker						*
 *		2011/02/26		Give Name field the initial focus				*
 *		2011/04/22		changes for support of IE7						*
 *		2011/06/12		add gender to parameter list for addChildToPage	*
 *						set class for option based upon gender			*
 *		2011/09/13		only show birth and death dates when present	*
 *						rearrange to try and get selection to work in IE*
 *		2011/09/15		IE<9 does not display names in selection list	*
 *		2012/01/13		change class names								*
 *		2012/01/20		change parameter list for addChildToPage		*
 *		2013/02/06		pass requested IDIR value to server for			*
 *						exclusion										*
 *		2013/03/05		invoke PHP script only to update display		*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/07/31		include birthsd and deathsd in parameters		*
 *						to addChild										*
 *		2013/08/01		remove top and bottom of page and				*
 *						resize window to fit							*
 *		2013/11/26		move the loading popup so it does not cover the	*
 *						search pattern field							*
 *		2013/12/08		add support for birth year range limit			*
 *		2013/12/12		change support to explicit birth year range		*
 *		2014/01/01		fix non-display of names in IE6					*
 *		2014/02/28		setNew interface replaced with changePerson		*
 *		2014/04/26		remove sizeToFit								*
 *		2014/09/12		display search URL in alert if invoked			*
 *						with debug										*
 *						examine returned parameter values				*
 *		2013/09/13		parameter names in array args are lower case	*
 *						use global debug flag from util.js				*
 *		2015/02/04		include parents and spouses names in response	*
 *						support being opened in a frame					*
 *		2015/02/10		use closeFrame									*
 *						no longer an attribute gender in option, use	*
 *						className										*
 *		2015/02/16		display appropriate text in button based		*
 *						upon selection									*
 *		2015/03/20		did not pass birthsd field to family page when	*
 *						invoked to add an existing child to a family	*
 *		2015/03/24		allow passing Sex in arguments to script		*
 *		2015/04/08		could not change name to search for				*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2015/08/23		add support for treename						*
 *		2015/11/06		make display more responsive by asking for		*
 *						small sections of the names and building the	*
 *						selection with one short query after another	*
 *		2016/02/06		call pageInit on load							*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  timer																*
 *																		*
 *  This timer is started whenever the user presses a key in the input	*
 *  field and pops if 0.3 second passes without a new keystroke			*
 ************************************************************************/
var	timer	= null;

/************************************************************************
 *  loadcnt																*
 *																		*
 *  This counts the number of outstanding requests to the server		*
 ************************************************************************/
var	loadcnt	= 0;

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
    window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 *																		*
 *  Input:																*
 *		this		Window object										*
 ************************************************************************/
function onLoad()
{
    // initialize dynamic functionality
    for (var fi = 0; fi < document.forms.length; fi++)
    {
		var	form		= document.forms[fi];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    var	name	= element.name;
		    if (name === undefined || name.length == 0)
				name	= element.id;

		    // take action specific to the element based on its name
		    switch(name)
		    {		// switch on name
				case 'Name':
				{	// search pattern for name of individual
				    element.onkeydown	= keyDownName;
				    element.focus();	// give Name the input focus
				    break;
				}	// search pattern for name of individual

				case 'individ':
				{	// list of matching individuals
				    element.onchange	= changeIndivid;
				    break;
				}	// list of matching individuals

				case 'incMarried':
				case 'birthmin':
				case 'birthmax':
				{	// whether to include married names
				    element.onchange	= update;
				    break;
				}	// whether to include married names

				case 'select':
				{	// act on selection
				    element.onclick	= select;
				    break;
				}	// act on selection
		    }		// switch on name
		}		// loop through all form elements
    }			// loop through all forms

    // load the initial list
    update();
}		// onLoad

/************************************************************************
 *  gotNames															*
 *																		*
 *  This method is called when the XML document representing			*
 *  the list of individuals is retrieved from the database.				*
 *  Repopulate the selection list.										*
 *																		*
 *  Input:																*
 *		xmlDoc		the response document from the server				*
 ************************************************************************/
function gotNames(xmlDoc)
{
    loadcnt--;		// decrement number of outstanding responses
    if (loadcnt > 0)
		return;		// do not refresh if more outstanding responses
    hideLoading();	// hide "loading" indicator

    var	form	= document.indForm;
    var	select	= form.individ;
    if (xmlDoc == null)
    {
		popupAlert('LegacyIndex: gotNames: xmlDoc is null',
				   form.Name);
		return;
    }

    var root	= xmlDoc.documentElement;
    if (root && (root.nodeName == 'names'))
    {			// valid response
		var	parms	= {};

		// loop through immediate children of root node of XML document
		for (var i = 0; i < root.childNodes.length; i++)
		{		// loop through children of root
		    var	elt	= root.childNodes[i];

		    // each individual in the response is represented by
		    // an <indiv id='9999'> node, with subfields represented
		    // as child nodes containing text
		    if (elt.nodeType == 1)
		    {		// individual
				if (elt.nodeName == 'parms')
				{
				    parms	= getParmsFromXml(elt);
				}
				else
				if (elt.nodeName == 'indiv')
				{	// <indiv>
						var	id	= elt.getAttribute('id');
				    var	fields	= getParmsFromXml(elt);

				    name		= fields.surname;
				    if (fields.maidenname && fields.maidenname != fields.surname)
						name	+= ' (' + fields.maidenname + ')';
				    name		+= ', ' + fields.givenname;

				    if (fields.birthd != '' ||
						fields.deathd != '')
				    {
						name	+= ' (' +
								      fields.birthd + '-' +
								      fields.deathd + ')';
				    }

				    var	gender	= fields.gender;

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

				    // add entry into selection list
				    option	= new Option(name,
									 id,
									 false,
									 false);

				    // set appearance (color) of entry by gender
				    if (gender == 0)
						option.className	= 'male';
				    else
				    if (gender == 1)
						option.className	= 'female';
				    else
						option.className	= 'unknown';

				    // add internal information to option
				    option.innerHTML= name;		// should already be set
				    option.value	= id;		// should already be set
				    option.surname	= fields.surname;
				    option.givenname= fields.givenname;
				    option.birthd	= fields.birthd;
				    option.birthsd	= fields.birthsd;
				    option.deathd	= fields.deathd;
				    select.appendChild(option);	// add to <select>
				}	// <indiv>
		    }		// tag
		}		// loop through children of root

		// check to make sure we have enough names
		var	nameCount	= select.options.length;
		if (nameCount < 51)
		{
		    parms.limit			= 51 - nameCount;
		    if (parms.hasOwnProperty('LastSurname'))
		    {			// not first query response
				parms.Surname		= parms.LastSurname;
		    }			// not first query response
		    else
		    {			// after first query
				// set up for second query
				parms.LastSurname	= parms.Surname;
				parms.GivenName		= '';
		    }			// after first query

		    // adjust delimiting surname
		    var	char1	= parms.LastSurname.substring(0,1).toUpperCase();
		    var	char2	= parms.LastSurname.substring(1,2);
		    if (char2 >= "z")
		    {		// overflow to next letter
				if (char1 < "Z")
				    parms.LastSurname	= 
						    String.fromCharCode(char1.charCodeAt(0) + 1) +
						    ' ';
				else
				    parms.LastSurname	= null;
		    }
		    else
		    if (char2 < "a")
		    {		// handle, for example, O' names
				parms.LastSurname		= char1 + 'a';
		    }
		    else
		    {		// letters 'a' through 'y'
				parms.LastSurname	= char1 +
						      String.fromCharCode(char2.charCodeAt(0) + 1);
		    }		// letters 'a' through 'y'

		    var	url	= "/FamilyTree/getIndivNamesXml.php";
		    var	op	= '?';
		    for(var name in parms)
		    {
				url	+= op + name + '=' + encodeURIComponent(parms[name]);
				op	= '&';
		    }
		    if (debug.toLowerCase() == 'y')
				alert("chooseIndivid.js: gotNames: " + url);
		    // invoke script to obtain list of names for selection list
		    if (parms.LastSurname !== null)
				HTTP.getXML(url,
						    gotNames,
						    noNames);
		}
    }			// valid response
    else
    {			// error
		var	msg	= "";
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

		var	form	= document.nameForm;
		popupAlert(msg, form.Name);
    }		// error
}		// gotNames

/************************************************************************
 *  noNames																*
 *																		*
 *  This method is called if there is no response file from the server.	*
 ************************************************************************/
function noNames()
{
    loadcnt--;		// decrement number of outstanding responses
    if (loadcnt > 0)
		return;		// do not refresh if more outstanding responses
    hideLoading();	// hide loading indicator

    alert('chooseIndivid.js: noNames: No response file from server script getIndivNamesXml.php.');
}		// noNames

/************************************************************************
 *  changeIndivid														*
 *																		*
 *  This method is called when the user clicks on one of the options	*
 *  in the selection list of individuals.								*
 *																		*
 *  Input:																*
 *		this	<select id='individ'>									*
 ************************************************************************/

function changeIndivid()
{
    var	select	= this;
    var	button	= document.getElementById('select');
    var	template= document.getElementById('selectSelectTemplate');
    if (select.value <= 0)
    {
		template= document.getElementById('selectCancelTemplate');
    }

    button.innerHTML	= template.innerHTML;
}

/************************************************************************
 *  keyDownName															*
 *																		*
 *  This method is called when a key is pressed in the Name field.		*
 *  A timer is set so that when the user stops typing for 0.2 seconds	*
 *  the selection list is repopulated.									*
 *																		*
 *  The timeout value is chosen so that it is longer than the normal	*
 *  time between keystrokes for experienced users, but not so long that	*
 *  the script is unresponsive.											*
 *																		*
 *  Input:																*
 *		event		a keystroke event									*
 ************************************************************************/

function keyDownName(event)
{
    if (timer)
		clearTimeout(timer);
    timer	= setTimeout(update, 500);
}		// keyDownName

/************************************************************************
 *  update																*
 *																		*
 *  This method is called to repopulate									*
 *  the selection list based upon the current search parameters.		*
 ************************************************************************/
function update()
{
    var	form	= document.indForm;

    if (form)
    {			// form present
		var	url	= '/FamilyTree/getIndivNamesXml.php';

		var nameSet		= false;
		for(var j = 0; j < form.elements.length; j++)
		{		// loop through all input elements
		    var element	= form.elements[j];

		    var	name	= element.name;
		    var value	= element.value;
		    if (name === undefined || name.length == 0)
				name	= element.id;

		    // take action specific to the element based on its name
		    switch(name)
		    {		// switch on name
				case 'Name':
				{	// search pattern for name of individual
				    if (value.length > 0)
				    {		// search using value
						nameSet		= true;
						var comma	= value.indexOf(',');
						if (comma >= 0)
						{	// comma separator between surname and given
						    url	+= "?Surname=" +
						encodeURIComponent(value.substring(0, comma));
						    for (var i = comma + 1; i < value.length; i++)
						    {		// trim off leading space
								if (value.substring(i, i+1) != ' ')
								    break;
						    }
						    url	+= "&GivenName=" +
						encodeURIComponent(value.substring(i, value.length));
						}	// comma separator between surname and given
						else
						    url	+= "?Surname=" + encodeURIComponent(value) +
									"&GivenName=";
				    }		// search using value
				    else
						url	+= "?Surname=";
				    break;
				}	// search pattern for name of individual

				case 'parentsIdmr':
				case 'birthmin':
				case 'birthmax':
				{	// other limits
				    if (value.length > 0 && value != 0)
				    {	// search using value
						url	+= "&" + name + "=" + value;
				    }	// search using value
				    break;
				}	// other limits

		    }		// switch on element name
		}		// loop through all input elements

		// also check command line arguments
		for(var key in args)
		{		// loop through command line arguments
		    var	value	= args[key];
		    switch(key.toLowerCase())
		    {		// act on specific argument
				case 'idir':
				{	// looking for individual to merge with
				    // this indicates to exclude this individual
				    url		+= "&idir=" + value;
				    break;
				}	// individual to merge with

				case 'given':
				{	// given name to look for
				    if (!nameSet)
						url	+= "&GivenName=" + encodeURIComponent(value);
				    break;
				}	// given name to look for

				case 'surname':
				{	// surname to look for
				    if (!nameSet)
						url	+= "&Surname=" + encodeURIComponent(value);
				    break;
				}	// surname to look for

				case 'sex':
				case 'gender':
				{	// gender to look for
				    url		+= "&Sex=" + value;
				    break;
				}	// gender to look for

				case 'treename':
				{	// gender to look for
				    url		+= "&Treename=" + value;
				    break;
				}	// gender to look for

				case 'id':
				{	// id attribute of invoking element
				    // id attribute of an element in the invoking page
				    // to which results are to be delivered by feedback call
				    if (value == 'Father' || value == 'Husb')
						url	+= "&Sex=M";
				    else
				    if (value == 'Mother' || value == 'Wife')
						url	+= "&Sex=F";
				    break;
				}	// id attribute of invoking element
		    }		// act on specific argument
		}		// loop through command line arguments

		// always display parents and spouse
		url	+= '&includeParents=Y';
		url	+= '&includeSpouse=Y';

		// load indicator
		loadcnt++;	// number of outstanding loads
		if (loadcnt == 1)
		    popupLoading(form.Name);	// display loading indicator to user

		// invoke script to obtain list of names for selection list
		if (debug.toLowerCase() == 'y')
		    alert("chooseIndivid.js: update: url='" + url + "'");
		HTTP.getXML(url,
				    gotNames,
				    noNames);

		// clear out the old selection list while we are waiting
		var	select	= form.individ;
		select.options.length	= 0;

		// put a dummy entry at the top of the selection, otherwise
		// selecting the first name does not call onchange
		var	name		= '[choose an individual]';
		var	option		= new Option(name,
									     -1,
									     false,
									     false);
		option.innerHTML	= '[choose an individual]';
		select.appendChild(option);
    }		// form present
}		// update

/************************************************************************
 *  select																*
 *																		*
 *  This method is called when the user clicks on the Select button		*
 *																		*
 *  Input:																*
 *		this	<button id='Select'>									*
 ************************************************************************/
function select()
{
    var	button		= this;		// invoking button
    var	srcform		= button.form;
    var	option		= null;		// selected option
    var	idir		= -1;		// selected IDIR

    // process selected option in <select name='individ'>
    var	select		= srcform.individ;
    if (select.selectedIndex >= 0)
    {			// item selected
		option		= select.options[select.selectedIndex];
		idir		= parseInt(option.value);
    }			// item selected

    if (idir > 0)
    {				// individual selected
		var	opener	= null;
		if (window.frameElement && window.frameElement.opener)
		    opener	= window.frameElement.opener;
		else
		    opener	= window.opener;
		if (opener != null)
		{			// invoked from another window
		    var callerDoc	= opener.document;

		    // process the parameters
		    for (var key in args)
		    {			// loop through parameters
				var	val	= args[key];
				switch(key)
				{		// act on specific parameters
				    case 'callidir':
				    {		// explicit notification back to invoker
						var callform	= callerDoc.forms[val];
						if (callform)
						{		// form found in opener
						    // explicit notify
						    callform.callidir(idir);
						}	// form found in opener
						else
						{	// form not found
						    alert("chooseIndivid.js: select: " +
								  "Could not find form id=" + val +
								  " in opener");
						}	// form not found
						break;
				    }		// explicit notification back to invoker

				    case 'idir':
				    case 'name':
				    {		// handled by PHP
						break;
				    }		// handled by PHP

				    case 'parentsidmr':
				    {		// request to add a child onto a family
						var idmr	= val;
						var	childTable	=
						    callerDoc.getElementById('children');
						var	gender	= option.className;
						var	parms	= {
									'idir'	: idir,
									'givenname'	: option.givenname,
									'surname'	: option.surname,
									'birthd'	: option.birthd,
									'birthsd'	: option.birthsd,
									'deathd'	: option.deathd,
									'gender'	: gender
								       };

						// add row onto table on opener's web page
						// invoke the add method of the 'children' table
						childTable.addChildToPage(parms,
										  true);

						break;
				    }		// request to add a child onto a family

				    case 'id':
				    {		// request to invoke changePerson method
						var	elt	= callerDoc.getElementById(val);
						var	parms	= {
								    'idir'		:idir,
								    'givenname'	: option.givenname,
								    'surname'	: option.surname,
								    'birthd'	: option.birthd,
								    'deathd'	: option.deathd};
						if (elt)
						    elt.changePerson(parms);
						else
						    alert("chooseIndivid.js: select: " +
								  "unable to locate element id='" + val +
								  "' in calling page");
						break;
				    }		// request to invoke changePerson method
				}		// act on specific parameters
		    }			// loop through parameters

		}			// invoked from another window
		else
		    alert("chooseIndivid.js: select: " +
				  "not opened from another window");
    }				// individual selected

    // close the chooser window
    closeFrame();
}		// function select
