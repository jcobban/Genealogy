/************************************************************************
 *  nominalIndex.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  dialog nominalIndex.html											*
 *																		*
 *  History:															*
 *		2010/09/11		created											*
 *		2010/12/18		allow specifying the initial value of name		*
 *		2011/01/31		insert a dummy entry at the top of the			*
 *						selection list 		so selecting the first real	*
 *						entry triggers onchange							*
 *		2011/02/22		set onchange method in javascript, not HTML		*
 *						remove functions no longer used					*
 *		2011/03/10		select the name input field						*
 *		2011/04/22		support for IE7									*
 *		2011/06/12		color code names by gender						*
 *		2011/10/28		support mouse-over help							*
 *		2011/11/26		add checkbox to include married names in		*
 *						function selection								*
 *		2011/12/27		display loading indicator while waiting for		*
 *						response from server for list of names			*
 *						only display response from server when it is	*
 *						the last outstanding response.					*
 *		2011/12/30		display loading indicator while waiting for		*
 *						response from server for list of names			*
 *						only display response from server when it is the*
 *						last outstanding response.						*
 *						display field help on mouse over				*
 *		2012/01/13		change class names								*
 *						suppress the submit action						*
 *		2012/05/28		display individuals with unknown sex in green	*
 *		2012/08/10		extend inactivity timer to 0.7 seconds			*
 *		2013/04/08		changed to support nominalIndex.php				*
 *		2013/05/29		use actMouseOverHelp common function			*
 *						standardize initialization						*
 *		2013/07/01		invoke legacyIndivid.php with idir parameter	*
 *		2013/11/16		use popupAlert instead of javascript alert		*
 *		2013/12/31		implementation did not display matches on IE6	*
 *						use onkeydown instead of onkeypress				*
 *						clean up comment blocks							*
 *		2014/01/23		add support for birth year range				*
 *		2014/10/14		indices of args array are now lower case		*
 *		2014/11/25		add names of parents and spouse if available	*
 *		2015/02/02		add button to add an unrelated individual		*
 *		2015/06/16		display only privatised dates for private		*
 *						function individuals								*
 *		2015/08/11		add treeName field on form						*
 *		2015/08/12		pass debug flag to next page					*
 *		2015/10/26		make display more responsive by asking for		*
 *						small sections of the names and building the	*
 *						selection with one short query after another	*
 *		2016/02/07		use traceAlert for debugging output				*
 *		2017/08/16		renamed to nominalIndex.js						*
 *						invoke Person.php instead of legacyIndiv.php	*
 *		2018/09/07		add diagnostic information to noNames report	*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2020/01/09      resize selection list to match window width     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  function timer														*
 *																		*
 *  This timer is started whenever the user presses a key in the input	*
 *  field and pops if 0.7 second passes without a new keystroke			*
 ************************************************************************/
var	timer	= null;

/************************************************************************
 *  function loadcnt													*
 *																		*
 *  This counts the number of outstanding requests to the server		*
 ************************************************************************/
var	loadcnt	= 0;

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
addEventHandler(window, 'load',	    onLoad);
addEventHandler(window, 'resize',	onResize);

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 *																		*
 *  Input:																*
 *		this		window object										*
 *		ev          load Event											*
 ************************************************************************/
function onLoad(ev)
{
    // activate dynamic functionality for elements
    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var	form		= document.forms[fi];

		form.onsubmit	= suppressSubmit;

		for(var j = 0; j < form.elements.length; j++)
		{               // loop through all elements of the form
		    var element	                = form.elements[j];

		    var	name	                = element.name;
		    if (name === undefined || name.length == 0)
				name	                = element.id;

		    // take action specific to the element based on its name
		    switch(name)
		    {		    // switch on name
				case 'Name':
				{	    // name of individual
				    if (args['name'])
						element.value	= decodeURIComponent(args['name']); 
				    element.onkeydown	= onKeyDownName;
				    element.focus();
				    element.select();
				    break;
				}	    // name of individual

				case 'individ':
				{	    // list of individuals
				    element.onchange	= onChangeIndivid;
                    var left            = element.offsetLeft;
                    var parentWidth     = element.offsetParent.clientWidth;
                    element.style.width = (parentWidth - left - 20) +'px';
				    break;
				}	    // list of individuals

				case 'birthmin':
				case 'birthmax':
				{
				    element.checkfunc	= checkYear;
				    element.onchange	= update;
				    break;
				}	    // birth year range

				case 'incMarried':
				{	    // whether to include married names
				    if (args['incmarried'] && args['incmarried'].length > 0)
						element.checked	= true;
				    element.onchange	= update;
				    break;
				}	    // whether to include married names

				case 'addUnrelated':
				{	    // add unrelated individual
				    element.onclick	    = addUnrelated;
				    break;
				}	    // add unrelated individual


				case 'includeParents':
				{	    // whether to include names of parents
				    if (args['includeparents'] &&
						args['includeparents'].length > 0)
						element.checked	= true;
				    element.onchange	= update;
				    break;
				}	    // whether to include names of parents

				case 'includeSpouse':
				{	    // whether to include name of spouse
				    if (args['includespouse'] &&
						args['includespouse'].length > 0)
						element.checked	= true;
				    element.onchange	= update;
				    break;
				}	    // whether to include name of spouse

				case 'Sex':
				{	    // whether to restrict report by sex
				    if (args['sex'] &&
						args['sex'].length > 0)
						element.value	= args['sex'];
				    element.onchange	= update;
				    break;
				}	    // whether to restrict report by sex

				case 'treeName':
				{	    // selection of tree name to display
				    element.onchange	= changeTree;
				    break;
				}	    // selection of tree name to display
		    }		    // switch on element name
		}		        // loop through all form elements
    }			        // loop through all forms

    // invoke script to obtain initial list of names for selection list
    update();
}		// function onLoad

/************************************************************************
 *  function onResize													*
 *																		*
 *  Take action when window changes size.                               *
 *																		*
 *  Input:																*
 *		this		window object										*
 *		ev          resize Event										*
 ************************************************************************/
function onResize(ev)
{
    var form            = document.nameForm;
    var element         = form.individ;
    var left            = element.offsetLeft;
    var parentWidth     = element.offsetParent.clientWidth;
    element.style.width = (parentWidth - left - 20) +'px';
}       // function onResize

/************************************************************************
 *  function suppressSubmit												*
 *																		*
 *  The input elements on this form are required by HTML to be enclosed	*
 *  in  a <form> tag, and by default pressing the enter key in an		*
 *  input element submits the form of which it is a part.  However		*
 *  there is no defined submit action for this form, so the submit		*
 *  needs to be suppressed to prevent an error being reported			*
 *  to the user.														*
 ************************************************************************/
function suppressSubmit()
{
    return false;
}		// function suppressSubmit

/************************************************************************
 *  function onKeyDownName												*
 *																		*
 *  This method is called when a key is pressed in the Name field.		*
 *  A timer is set so that when the user stops typing the selection		*
 *  list is repopulated.												*
 *																		*
 *  Input:																*
 *		event	if passed, event.keyCode identifies the key pressed		*
 *				in older releases of Internet Explorer use window.event	*
 ************************************************************************/
function onKeyDownName(event)
{
    if (timer)
		clearTimeout(timer);
    timer	= setTimeout(update, 900);
}		// function onKeyDownName

/************************************************************************
 *  function changeTree													*
 *																		*
 *  This method is called when the user changes the selection of		*
 *  which tree to display.												*
 *																		*
 *  Input:																*
 *		this			<select name='treeName'>						*
 ************************************************************************/
function changeTree()
{
    var	form		= this.form;
    form.Name.value	= ' ';
    if (this.value == '[new]')
    {			// create new tree
		// ask user for name of new tree
		var dialog  = displayDialog('CreateTreeTemplate',
		            			    [],         // no parms
		            			    this,		// position relative to
		            			    null,		// button cancels request
                                    true);      // defer display
        var forms                   = dialog.getElementsByTagName('form');
        form                        = forms[0];
		var	treeNameElt	            = form.newTreeName;
		treeNameElt.focus();
		treeNameElt.onchange	    = newTreeNameChanged;
		dialog.style.visibility	    = 'visible';
		dialog.scrollIntoView();
        dialog.style.display 	    = 'block';
    }			// display dialog to create new tree
    else
    {			// select existing tree
		var	title	                = document.getElementById('title');
		if (this.value == '')
		    title.innerHTML	        = "Families of South-Western Ontario";
		else
		if (this.value == '*')
		    title.innerHTML	        = "Families of All Trees";
		else
		    title.innerHTML	        = "Families of " + this.value;
		update();	// update the display
    }			// select existing tree
}		// function changeTree

/************************************************************************
 *  function newTreeNameChanged											*
 *																		*
 *  This method is called when the user enters the name of a new		*
 *  tree to create or select by name.									*
 *																		*
 *  Input:																*
 *		this			<select name='newTreeName'>						*
 ************************************************************************/
function newTreeNameChanged()
{
    hideDialog();
    var newTreeName	= this.value;
    var patt		= /^[A-Z][A-Z ']*$/i;
    if (patt.test(newTreeName))
    {			// valid name
		var	treeNameSel	= document.getElementById('treeName');
		var options		= treeNameSel.options;
		for (var io = 0; io < options.length; io++)
		{		// loop through existing options
		    if (options[io].value.toLowerCase() == newTreeName.toLowerCase())
		    {		// match existing name
				treeNameSel.selectedIndex	= io;
				return;
		    }		// match existing name
		}		// loop through existing options
		
		// add entry into selection list
		var option	= new Option(newTreeName,
							     newTreeName,
							     false,
							     false);
		treeNameSel.add(option);
		treeNameSel.selectedIndex	= treeNameSel.options.length - 1;
		update();
    }			// valid name
    else
    {
		popupAlert("Tree names may only contain letters and spaces", this);
    }
}		// function newTreeNameChanged

/************************************************************************
 *  function update														*
 *																		*
 *  This method is called when the user stops typing to repopulate		*
 *  the selection list based upon the current contents of the Name		*
 *  field.  It is also the onchange method for the following fields:	*
 *  'birthmin', 'birthmax', 'incMarried', 'includeParents',				*
 *  'includeSpouse', and 'Sex', and it is called by the onchange		*
 *  method for the field 'treeName'.									*
 ************************************************************************/
function update()
{
    var	form	        = document.nameForm;
    if (form)
    {		        // form present
		let url	        = "/FamilyTree/getIndivNamesJSON.php";
		let lang	    = 'en';
		if ('lang' in args)
		    lang	    = args['lang'];

		for(var j = 0; j < form.elements.length; j++)
		{		    // loop through all input elements
		    var element	= form.elements[j];

		    var	name	= element.name;
		    var value	= element.value.trim();
		    if (name === undefined || name.length == 0)
				name	= element.id;

		    // take action specific to the element based on its name
		    switch(name)
		    {		// switch on name
				case 'Name':
				{	// search pattern for name of individual
				    if (value.length > 0)
				    {		// search using value
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

				case 'incMarried':
				{
				    if (element.checked)
						url	+= '&incMarried=Y';
				    break;
				}

				case 'includeParents':
				{
				    if (element.checked)
						url	+= '&includeParents=Y';
				    break;
				}

				case 'includeSpouse':
				{
				    if (element.checked)
						url	+= '&includeSpouse=Y';
				    break;
				}

				case 'Sex':
				{
				    if (element.value.length > 0)
						url	+= '&Sex=' + element.value;
				    break;
				}

				case 'birthmin':
				case 'birthmax':
				{	// other limits
				    if (value.length > 0 && value != 0)
				    {	// search using value
						url	+= "&" + name + "=" + value;
				    }	// search using value
				    break;
				}	// other limits

				case 'treeName':
				{
				    url			        += '&treename=' + element.value;
				    var	cookie		    = new Cookie("familyTree");
				    cookie.treeName	    = element.value;
				    cookie.store(10);		// keep for 10 days
				    break;
				}


		    }		// switch on element name
		}		    // loop through all input elements
		url		                += "&limit=50&lang=" + lang;

		loadcnt++;	// number of outstanding loads
		if (loadcnt == 1)
		    popupLoading(form.individ);	// display loading indicator to user

		if (debug.toLowerCase() == 'y')
		    alert("nominalIndex.js: update: url='" + url + "'");
        var trace = "url=" + url + "<br>\n";
        let para        = document.createElement('P');
        para.innerHTML  = trace;
        document.body.appendChild(para);
		// invoke script to obtain list of names for selection list
        var options             = {"timeout"    : false};
		HTTP.get(url,
				 gotNames,
				 options);

		// clear out the old selection list while we are waiting
		var	select	            = form.individ;
		select.options.length	= 1;
    }		// form present
}		// function update

/************************************************************************
 *  function gotNames													*
 *																		*
 *  This method is called when the XML file representing				*
 *  the list of individuals is retrieved from the database.				*
 *  Repopulate the selection list.										*
 *																		*
 *  Parameters:															*
 *		obj		    Javascript object returned from server				*
 ************************************************************************/
function gotNames(obj)
{
    loadcnt--;		// decrement number of outstanding responses
    if (loadcnt > 0)
		return;		// do not refresh if more outstanding responses
    hideLoading();	// hide "loading" indicator

    var	form	            = document.nameForm;
    var	select	            = form.individ;
    var trace               = '';
    if (obj && typeof(obj) == 'object')
    {
        if ('parms' in obj)
        {
            trace           += "parms=" + JSON.stringify(obj.parms) + "<br>";
        }
        if ('cmd' in obj)
        {
            trace           += "cmd=" + JSON.stringify(obj.cmd) + "<br>";
        }
        if ('query' in obj)
        {
            trace           += "query=" + JSON.stringify(obj.query) + "<br>";
        }
        if ('warn' in obj)
        {
            trace           += "warn=" + JSON.stringify(obj.warn) + "<br>";
        }
        if ('persons' in obj)
        {
		    var	parms	                    = obj.parms;
            var persons                     = obj.persons;

            for (var idir in persons)
            {
                var person                  = persons[idir];
                var gender                  = person.gender;
                var name                    = person.name;
                trace += "{idir=" + idir + ",gender=" + gender + ",name=" + name + "}<br>\n";

				var option	                = new Option(name,
                							    	     idir,
		                						         false,
				                				         false);
				option.className	        = gender;

				// add internal information to option
				select.appendChild(option);	// add to <select>
		    }		                    // person

			// check to make sure we have enough names
			var	nameCount	= select.options.length;
			if (nameCount < 51)
			{                           // name count < 51
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
	
			    var url	= "/FamilyTree/getIndivNamesJSON.php";
			    var	op	= '?';
			    for(var name in parms)
			    {
					url	+= op + name + '=' + encodeURIComponent(parms[name]);
					op	= '&';
			    }
			    if (debug.toLowerCase() == 'y')
					traceAlert("nominalIndex.js: gotNames: " + url);
                trace += "url=" + url + "<br>\n";
			    // invoke script to obtain list of names for selection list
			    if (parms.LastSurname !== null)
                {
                    var options             = {"timeout"    : false};
		            HTTP.get(url,
            				 gotNames,
			            	 options);
                }
		    }                           // name count < 51
		}		                        // array of persons present
        let para        = document.createElement('P');
        para.innerHTML  = trace;
        document.body.appendChild(para);
    }               // response object present
    else
    {               // no response object
		popupAlert('nominalIndex.js: gotNames: object is null',
				   form.Name);
    }               // no response object
}		// function gotNames

/************************************************************************
 *  function noNames													*
 *																		*
 *  This method is called if there is no getIndivNamesJSON.php script	*
 *  on the server.														*
 ************************************************************************/
function noNames()
{
    var	form	= document.nameForm;
    loadcnt--;		// decrement number of outstanding responses
    if (loadcnt > 0)
		return;		// do not refresh if more outstanding responses
    hideLoading();	// hide "loading" indicator

    popupAlert('No response file from ' + url,
				form.Name);
}		// function noNames

/************************************************************************
 *  function onChangeIndivid											*
 *																		*
 *  This method is called when the user changes the selected			*
 *  individual.															*
 *																		*
 *  Input:																*
 *		this	<select name='individ'> element							*
 ************************************************************************/
function onChangeIndivid()
{
    var	option	= null;		// selected option
    var	idir	= -1;		// selected IDIR

    var	select	= this;
    if (select.selectedIndex >= 0)
    {		// item selected
		option	= select.options[select.selectedIndex];
		idir	= parseInt(option.value);
    }		// item selected

    if (idir > 0)
    {			// individual selected
		var lang	= args['lang'];
		if (lang === undefined)
		    lang	= 'en';
		var script	= "/FamilyTree/Person.php?idir=" + idir +
									'&lang=' + lang;
		if (debug.toLowerCase() == 'y')
		    script		+= '&debug=Y';
		location	= script;
    }			// individual selected
}		// function onChangeIndivid

/************************************************************************
 *  function addUnrelated												*
 *																		*
 *  This method is called when the user requests to add an unrelated	*
 *  individual to the database/											*
 *																		*
 *  Input:																*
 *		this		<button id='addUnrelated'> element					*
 ************************************************************************/
function addUnrelated()
{
    var form		= this.form;
    var	treeName	= form.treeName.value;
    var lang	= args['lang'];
    if (lang === undefined)
		lang	= 'en';
    var	script		= "editIndivid.php?treeName=" + treeName +
								'&lang=' + lang;
    if (debug.toLowerCase() == 'y')
		script		+= '&debug=Y';
    location		= script;
}		// function addUnrelated
