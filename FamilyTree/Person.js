/************************************************************************
 *  Person.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Person.php.													*
 *																		*
 *  History:															*
 *		2010/08/23		add onload function								*
 *						select all text in the blog textarea			*
 *		2010/10/29		close window if adding child or spouse to		*
 *						function marriage								*
 *		2010/12/25		set onclick for blogging here rather than in	*
 *						function HTML									*
 *		2011/06/24		For editting spouses and children this page is	*
 *						now invoked from editMarriages.php				*
 *		2011/08/12		add buttons so owner of blog message can edit	*
 *						or delete it.									*
 *		2011/09/13		catch IE exception in accessing window.opener	*
 *		2011/10/23		use actual buttons for functions previously		*
 *						invoked by hyperlinks and add keyboard			*
 *						shortcuts for most buttons.						*
 *		2012/01/13		change class name								*
 *		2012/02/26		shrink frames around pictures so they are just	*
 *						big enough										*
 *		2012/10/30		execute correctly if invoked across domains		*
 *		2013/04/12		add support for displaying a boundary			*
 *		2013/04/13		record last referenced individual				*
 *		2013/04/17		LegacyLocation::getLatitude and getLongitude	*
 *						return DD.dddd values							*
 *		2013/05/25		boundaries were concatenated when multiple		*
 *						locations were displayed in sequence			*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/06/12		popup info on mouse over source 				*
 *		2013/07/30		defer facebook initialization until after load	*
 *						change relationship calculator to popup dialog	*
 *		2013/11/28		defer loading Google(r) maps API to speed up	*
 *						display of page									*
 *		2014/06/29		allow non-registered users to post blogs so we	*
 *						can capture their e-mail addresses				*
 *		2014/10/12		use method show to display popups				*
 *		2015/01/11		add support for Ancestry search in split window	*
 *						pass surname and given name of initial			*
 *						individual to choose relative dialog			*
 *		2015/01/23		open descendant and ancestor trees in a new		*
 *						function frame									*
 *		2015/01/26		edit and delete blog onclick not activated		*
 *		2015/02/05		request and pass email address to let non-user	*
 *						post a blog										*
 *		2015/05/01		support for displaying source popup moved here	*
 *						from common util.js								*
 *						new implementation of laying out source popup	*
 *						by building it when the page is laid out		*
 *						support for displaying individ popup moved here	*
 *						from common util.js								*
 *						new implementation of laying out individ popup	*
 *						by building it when the page is laid out		*
 *		2015/05/14		add button to request permission to update		*
 *						in the case where the user is signed on but		*
 *						not already an owner							*
 *		2015/05/26		use absolute URLs for blog scripts				*
 *						add guidance to grant request message			*
 *						support for displaying location popup moved		*
 *						here from common util.js						*
 *						new implementation of laying out location popup	*
 *						by building it when the page is laid out		*
 *		2015/06/02		use main style for TinyMCE editor				*
 *		2015/07/06		add a button to the location popup to permit	*
 *						editing the location information				*
 *		2015/07/24		display parms to postBlogXml.php if invoked		*
 *						with debug										*
 *		2015/07/30		signal LegacyLocation.php to close at end		*
 *		2016/03/17		use https to load googleapis					*
 *		2017/08/16		renamed to Person.js							*
 *		2017/09/09		change LegacyLocation to Location				*
 *		2017/11/12		handle link to individual with lang parameter	*
 *		2017/10/27      support language selection                      *
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2018/11/02      pass authentication key to GoogleApis           *
 *		                ensure lang= parameter not passed to popup      *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/16      familyTree cookie was erroneously set from      *
 *		                the idir field in the popup template            *
 *		2019/05/19      call element.click to trigger button click      *
 *      2021/01/16      use addEventListener                            *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
window.addEventListener("load", onLoad);

/************************************************************************
 *  function ontarioCountyNames											*
 *																		*
 *	The google database of location names does not generally		    *
 *	include the county name, so it is necessary to remove the		    *
 *	county name before passing the location name to the lookup.		    *
 *	If this table were defined as an array, then checking for a		    *
 *	match would require linearly searching the array, using the		    *
 *	method indexOf, which is not even defined in IE<9!  Defining		*
 *	it as an object uses a hash table lookup for matches.				*
 ************************************************************************/
var ontarioCountyNames	= {
				'Algoma'		: 1,
				'Brant'			: 1,
				'Bruce'			: 1,
				'Carleton'		: 1,
				'Dufferin'		: 1,
				'Dundas'		: 1,
				'Durham'		: 1,
				'Elgin'			: 1,
				'Essex'			: 1,
				'Frontenac'		: 1,
				'Glengarry'		: 1,
				'Grenville'		: 1,
				'Grey'			: 1,
				'Haldimand'		: 1,
				'Haliburton'		: 1,
				'Halton'		: 1,
				'Hastings'		: 1,
				'Huron'			: 1,
				'Kenora'		: 1,
				'Kent'			: 1,
				'Lambton'		: 1,
				'Lanark'		: 1,
				'Leeds'			: 1,
				'Lincoln'		: 1,
				'Middlesex'		: 1,
				'Muskoka'		: 1,
				'Nippising'		: 1,
				'Norfolk'		: 1,
				'Northumberland'	: 1,
				'Ontario'		: 1,
				'Oxford'		: 1,
				'Peel'			: 1,
				'Perth'			: 1,
				'Pontiac'		: 1,
				'Prescott'		: 1,
				'Prince Edward'		: 1,
				'Renfrew'		: 1,
				'Russell'		: 1,
				'Simcoe'		: 1,
				'Stormont'		: 1,
				'Temiskaming'		: 1,
				'Victoria'		: 1,
				'Welland'		: 1,
				'Wellington'		: 1,
				'Wentworth'		: 1,
				'York' 			: 1};

// instance of google.maps.Map for displaying the map
var	map		            = null;

// array of instances of google.maps.LatLng for boundary of area
var	path		        = [];

// instance of google.maps.PolygonOptions for displaying boundary
var	polyOptions	        = {strokeColor: "red", 
						   strokeOpacity: 0.5,
						   strokeWeight: 2,
						   fillColor: "black",
						   fillOpacity: 0.10};

// instance of google.maps.Polygon for displaying boundary
var	boundary	        = null;

// instance of google.maps.Geocoder for resolving place names
var	geocoder	        = null;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 ************************************************************************/
function onLoad()
{
    window.addEventListener("resize", personWindowResize);

    // set action methods for form
    var	invoker	            = window.opener;
    if (invoker)
    {		// invoked from another page
		try {
		var openerPath	    = invoker.location.pathname;
		var dlm		        = openerPath.lastIndexOf('/');
		var openerName	    = openerPath.substr(dlm + 1);
		//alert("Person: openerName='" + openerName + "'");
		if (openerName == "editMarriages.php" ||
		    openerName == "editParents.php")
		    close();
		}	// try
		catch (e) {
		   var msg	= "Person.js: onLoad: msg=" + e.message;
//	   if (invoker.location)
//	   {
//		msg	+= ", location=" + invoker.location;
//		if (invoker.location.pathname)
//		    msg	+= ", pathname=" + invoker.location.pathname;
//	    }
//	    alert(msg);
		}	// catch
    }		// invoked from another page

    // activate local keystroke handling
    document.body.onkeydown	= diKeyDown;

    var names   	        = "";
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    var	name	= element.name;
		    if (name.length == 0)
		    {		// button elements usually have id not name
				name	= element.id;
		    }		// button elements usually have id not name

		    var parts	= /^([a-zA-Z_]+)(\d*)$/.exec(name);
		    if (parts)
		    {
				name	= parts[1];
		    }
		    // take action specific to the element based on its name
		    switch(name)
		    {		// switch on name
				case 'blEdit':
				{	// edit message button
				    element.onclick	= editBlog;
				    break;
				}	// edit message button

				case 'blDel':
				{	// delete message button
				    element.onclick	= delBlog;
				    break;
				}	// delete message button

				case 'message':
				{	// blog text area
				    element.focus();	// make it the current input element
				    element.select();	// select all text
				    break;
				}	// blog text area

				case 'PostBlog':
				{	// post blog button
				    element.onclick	= postBlog;
				    break;
				}	// post blog button

				case 'showDescTree':
				{	// show descendant tree
				    element.onclick	= showDescTree;
				    element.focus();
				    break;
				}	// show descendant tree

				case 'showAncTree':
				{	// show ancestor tree
				    element.onclick	= showAncTree;
				    break;
				}	// show ancestor tree

				case 'relationshipCalc':
				{	// relationship calculator
				    element.onclick	= relationshipCalc;
				    break;
				}	// relationship calculator

				case 'ancestrySearch':
				{	// perform Ancestry.com search
				    element.onclick	= ancestrySearch;
				    break;
				}	// perform Ancestry.com search

				case 'edit':
				{	// edit individual
				    element.onclick	= editPerson;
				    element.focus();
				    break;
				}	// edit individual

				case 'reqgrant':
				{	// request permission to update
				    element.onclick	= reqGrant;
				    break;
				}	// request permission to update

				case 'idir':
				{	// edit individual
				    var	cookie		= new Cookie("familyTree");
                    var tvalue      = parseInt(element.value);
                    if (Number.isInteger(tvalue))
                    {
				        cookie.idir		= tvalue;
				        cookie.store(10);		// keep for 10 days
                    }
				    break;
				}	// edit individual

				case 'treeName':
				{	// name of tree the individual belongs to
				    var	cookie		= new Cookie("familyTree");
				    cookie.treeName	= element.value;
				    cookie.store(10);		// keep for 10 days
				    break;
				}	// name of tree the individual belongs to


				case 'showMap':
				case 'tshowMap':
				{
				    element.onclick	= showMap;
				    break;
				}

				case 'editLoc':
				{
				    element.onclick	= editLocation;
				    break;
				}

				case 'mbsubmit':
				{
				    element.onclick	= submitMbBirth;
				    break;
				}
		    }		// switch on name
		}	// loop through elements in form
    }		// iterate through all forms

    // for each image displayed in a <div class='picture'>
    // adjust the width of the division frame
    for (var ip = 0; ip < document.images.length; ip++)
    {		// loop through all images
		var	image	= document.images[ip];
		var	div	= image.parentNode;
		if (div.nodeName.toLowerCase() == 'div' &&
		    div.className == 'picture')
		{	// image is in a frame
		    div.style.width	= Math.max(image.width + 5, 100) + "px";
		}	// image is in a frame
    }		// loop through all images

    // activate support for a popup on each location name
    var	allSpan	= document.getElementsByTagName("span");
    for (var ispan = 0, maxSpan = allSpan.length; ispan < maxSpan; ispan++)
    {
		var	span	= allSpan[ispan];
		if (span.id.length > 9 && span.id.substring(0,4) == "show")
		{
		    span.onmouseover		= locMouseOver;
		    span.onmouseout		    = locMouseOut;
		}
		else
		if (span.id.length > 10 && span.id.substring(0,10) == 'DeathCause')
		{
		    span.onmouseover		= causeMouseOver;
		    span.onmouseout		    = causeMouseOut;
		}
    } 

    // activate support for a popup on each hyperlink to an individual
    var	allAnc	= document.getElementsByTagName("a");
    for (var ianc = 0, maxAnc = allAnc.length; ianc < maxAnc; ianc++)
    {		// loop through all anchors
		var	anc	= allAnc[ianc];
		var	li	= anc.href.lastIndexOf('/');
		var	name	= anc.href.substring(li + 1);
		var	hi	= name.indexOf('#');
		if (hi == -1 && name.substring(0, 13) == "Person.php?id")
		{	// link to another individual
		    anc.onmouseover		= indMouseOver;
		    anc.onmouseout		= indMouseOut;
		}	// link to another individual
		else
		if (name.substring(0, 13) == "Source.php?id")
		{	// link to a source
		    anc.onmouseover		= srcMouseOver;
		    anc.onmouseout		= srcMouseOut;
		}	// link to a source
		else
		if (name.substring(0, 15) == "getPersonSvg.php")
		{	// link to a graphical family tree
		    actMouseOverHelp(anc);
		}	// link to a graphical family tree
    }		// loop through all anchors

}		// function onLoad

/************************************************************************
 *  function personWindowResize											*
 *																		*
 *  This method is called when the browser window size is changed.		*
 *  If the window is split between the main display and a second		*
 *  display, resize.													*
 *																		*
 *  Input:																*
 *		this		<body> element										*
 *		ev          Javascript resize Event                             *
 ************************************************************************/
function personWindowResize(ev)
{
    if (iframe)
		openFrame(iframe.name, null, "right");
}		// function personWindowResize

/************************************************************************
 *  function initializeMaps												*
 *																		*
 *  Initialize support for Google Maps.									*
 *  This is a callback from the Google API site once the Javascript		*
 *  code for displaying maps is loaded.									*
 ************************************************************************/
function initializeMaps()
{
    // support for displaying Google map
    try {
		geocoder	= new google.maps.Geocoder();
    }
    catch(e)
    {
		alert("Person.js: initializeMaps: " + e.message);
    }
}		// function initializeMaps

/************************************************************************
 *  function postBlog													*
 *																		*
 *  This method is called when the user requests to post				*
 *  a message to the blog of an individual.								*
 *																		*
 *  Input:																*
 *		this			<button id='PostBlog'>							*
 ************************************************************************/
function postBlog(rownum)
{
    var	form		= this.form;
    var	userid		= form.userid.value;
    var	email		= '';
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    if (form.emailAddress)
		email		= form.emailAddress.value;

    if (userid == '' && email == '')
    {			// not signed on or identified
		openSignon();
    }			// not signed on or identified
    else
    {
		var	idir		= form.idir.value;
		var	message		= tinyMCE.get('message').getContent();
		var parms		= { "idir"		    : idir,
				            "emailAddress"	: email,
				            "message"	    : message,
                            "lang"          : lang};

		if (debug.toLowerCase() == 'y')
		{
		    alert("Person.js: postBlog: parms={" +
							"idir="		        + idir +
							", emailAddress='"  + email +
							"', message='"	    + message + 
							"', lang='"	        + lang + "'}");
		    parms['debug']	= 'y';
		}

		// invoke script to update Event and return XML result
		HTTP.post('/postBlogXml.php',
				  parms,
				  gotBlog,
				  noBlog);
    }
}		// postBlog

/************************************************************************
 *  function gotBlog													*
 *																		*
 *  This method is called when the XML file representing				*
 *  a posted blog is retrieved from the database.						*
 *																		*
 *  Input:																*
 *		xmlDoc			response from web server as XML document		*
 ************************************************************************/
function gotBlog(xmlDoc)
{
    var	evtForm		= document.evtForm;
    var	root		= xmlDoc.documentElement;
    var	messageElt	= document.getElementById('PostBlog');
    var	msg		= "";

    if (root && root.nodeName == 'blog')
    {
		for(var i = 0; i < root.childNodes.length; i++)
		{		// loop through children
		    var node	= root.childNodes[i];
		    if (node.nodeName == 'msg')
				msg	+= node.textContent;
		}		// loop through children
    }
    else
    {		// error
		if (root)
		{
		    for(var i = 0; i < root.childNodes.length; i++)
		    {		// loop through children
				var node	= root.childNodes[i];
				if (node.nodeValue != null)
				    msg	+= node.nodeValue;
		    }		// loop through children
		}
		else
		    msg	+= root;
    }		// error

    if (msg.length > 0)
		popupAlert(msg, messageElt);

    location	= location;
}		// gotBlog

/************************************************************************
 *  function noBlog														*
 *																		*
 *  This method is called if there is no blog script on the web server.	*
 ************************************************************************/
function noBlog()
{
    alert('Person.js: noBlog: ' +
				'script "postBlogXml.php" not found on web server');
}		// noBlog

/************************************************************************
 *  function noDelBlog													*
 *																		*
 *  This method is called if there is no blog script on the web server.	*
 ************************************************************************/
function noDelBlog()
{
    alert('Person.js: noDelBlog: ' +
				'script "deleteBlogXml.php" not found on web server');
}		// noDelBlog

/************************************************************************
 *  function editBlog													*
 *																		*
 *  This method is called if the user requests to edit the blog			*
 *  message.															*
 *																		*
 *  Input:																*
 *		this			<button id='blEdit'>							*
 ************************************************************************/
function editBlog()
{
    alert('to do: editBlog: ' + this.id.substring(6));
    return false;
}		// editBlog

/************************************************************************
 *  function delBlog													*
 *																		*
 *  This method is called if the user requests to delete the blog		*
 *  message.															*
 *																		*
 *  Input:																*
 *		this			<button id='blDel'>								*
 ************************************************************************/
function delBlog()
{
    var	form		= this.form;
    var	blid		= this.id.substring(5);
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;

    var parms		= {"blid"	: blid,
                       "lang"   : lang};

    // invoke script to update blog and return XML result
    HTTP.post('/deleteBlogXml.php',
		      parms,
		      gotBlog,
		      noDelBlog);
}		// delBlog

/************************************************************************
 *  function showDescTree												*
 *																		*
 *  This method is called when the user requests to display a tree of	*
 *  the descendants of an individual.									*
 *																		*
 *  Input:																*
 *		this			<button id='showDescTree'>					    *
 ************************************************************************/
function showDescTree()
{
    var	form		= this.form;
    var	idir		= form.idir.value;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	url		    = "/FamilyTree/descendantReport.php?idir=" + idir +
                        "&lang=" + lang;
    if (debug.toLowerCase() == 'y')
		url		    += '&debug=Y';
    openFrame("chooser",
		      url,
		      "right");
}		// showDescTree

/************************************************************************
 *  function showAncTree												*
 *																		*
 *  This method is called when the user requests to display a tree of	*
 *  the ancestors of an individual.										*
 *																		*
 *  Input:																*
 *		this			<button id='showAncTree'>						*
 ************************************************************************/
function showAncTree()
{
    var	form		= this.form;
    var	idir		= form.idir.value;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	url		    = "/FamilyTree/ancestorReport.php?idir=" + idir +
                        "&lang=" + lang;
    if (debug.toLowerCase() == 'y')
		url		    += '&debug=Y';
    openFrame("chooser",
		      url,
		      "right");
}		// showAncTree

/************************************************************************
 *  function relationshipCalc											*
 *																		*
 *  This method is called when the user requests to determine the		*
 *  relationship between the current individual and another individual	*
 *  in the database.													*
 *																		*
 *  Input:																*
 *		this			<button id='relationshipCalc'>					*
 ************************************************************************/
function relationshipCalc()
{
    var	form		= this.form;
    var	idir		= form.idir.value;
    var	givenName	= form.givenname.value;
    var	surname		= form.surname.value;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	url	    	= "/FamilyTree/chooseRelative.php" +
							"?name=" + surname +
							"&idir=" + idir; 
							"&lang=" + lang; 
    if (debug.toLowerCase() == 'y')
		url		    += '&debug=Y';
    openFrame("chooser",
		      url,
		      "right");
}		// relationshipCalc

/************************************************************************
 *  function ancestrySearch												*
 *																		*
 *  Perform a search for a matching individual in Ancestry.ca.			*
 *																		*
 *  Input:																*
 *		this		<button id='ancestrySearch'>						*
 ************************************************************************/
function ancestrySearch()
{
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    var	form			= this.form;
    var	yearPatt		= /\d{4}/;
    var	birthDate		= form.birthDate.value;
    var	birthYear		= '';
    var	rxRes			= yearPatt.exec(birthDate);
    if (rxRes)
		birthYear		= rxRes[0];
    var searchUrl		= 
		"http://search.ancestry.ca/cgi-bin/sse.dll?gl=ROOT_CATEGORY" +
		"&rank=1" +
		"&new=1" +
		"&so=3" +
		"&MSAV=1" +
		"&msT=1" +
		"&gss=ms_f-2_s" +
		"&gsfn=" + encodeURIComponent(form.givenname.value) +
		"&gsln=" + encodeURIComponent(form.surname.value) +
		"&msbdy=" + birthYear +
		"&msbpn__ftp=" + encodeURIComponent(form.birthPlace.value) +
		"&msbpn=5007" +
		"&msbpn_PInfo=5-|0|1652393|0|3243|0|5007|0|0|0|0|" +
		"&msfng0=" + encodeURIComponent(form.fatherGivenName.value) +
		"&msfns0=" + encodeURIComponent(form.fatherSurname.value) +
		"&msmng0=" + encodeURIComponent(form.motherGivenName.value) +
		"&msmns0=" + encodeURIComponent(form.motherSurname.value) +
		"&cpxt=1" +
		"&catBucket=rstp" +
		"&uidh=l88" +
		"&cp=3"

    var	sframe			= document.getElementById("searchFrame");
    if (!sframe)
    {
		sframe			= document.createElement("IFRAME");
		sframe.name		= "searchFrame";
		sframe.id		= "searchFrame";
		document.body.appendChild(sframe);
    }
    sframe.src			        = searchUrl;
    var	w		    	        = document.documentElement.clientWidth;
    var	h			            = document.documentElement.clientHeight;
    // resize the display of the transcription
    var transcription		    = document.getElementById('transcription');
    transcription.style.width	= w/2 + "px";
    transcription.style.height	= h + "px";

    // size and position the image
    sframe.style.width			= w/2 + "px";
    sframe.style.height			= h + "px";
    sframe.style.position		= "fixed";
    sframe.style.left			= w/2 + "px";
    sframe.style.top			= 0 + "px";
    sframe.style.visibility		= "visible";
    return false;
}	// ancestrySearch

/************************************************************************
 *  function editPerson													*
 *																		*
 *  This method is called when the user requests to determine the		*
 *  relationship between the current individual and another individual	*
 *  in the database.													*
 *																		*
 *  Input:																*
 *		this			<button id='edit...'>							*
 ************************************************************************/
function editPerson()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	form		= this.form;
    var	idir		= form.idir.value;
    location		= 'editIndivid.php?idir=' + idir +
							"&lang=" + lang; 
}		// editPerson

/************************************************************************
 *  function reqGrant													*
 *																		*
 *  This method is called when the user requests to permission to		*
 *  update this individual and his/her family.							*
 *																		*
 *  Input:																*
 *		this			<button id='reqGrant'>							*
 ************************************************************************/
function reqGrant()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	form		= this.form;
    var	idir		= form.idir.value;
    var	givenName	= capitalize(form.givenname);
    var	surName		= capitalize(form.surname);
    var	subject		= 'Please Grant Access to ' + 
						  givenName + ' ' + surName + ', IDIR='+ idir;
    subject		= encodeURIComponent(subject);
    var	url		= '/contactAuthor.php?idir=' + idir + 
						  '&tableName=tblIR' +
						  "&lang=" + lang + 
						  '&subject=' + subject +
				'&text=Please explain why you should be granted access.';
    if (debug.toLowerCase() == 'y')
		url		+= '&debug=Y';
    openFrame("chooser",
		      url,
		      "right");
    return false;
}		// reqGrant

/************************************************************************
 *  function diKeyDown													*
 *																		*
 *  Handle key strokes that apply to the entire window.  For example	*
 *  the key combination Alt-E are interpreted to edit the				*
 *  current individual.													*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function diKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e		        =  window.event;	// IE
    }		// browser is not W3C compliant
    var	key		        = e.key;
    var	actionsForm	    = document.actionsForm;
    var	idir		    = actionsForm.idir.value;
    var	button;

    // take action based upon code
    if (e.altKey)
    {		        // alt key shortcuts
		switch (key)
		{           // act on specific key
		    case 'a':
		    case 'A':
		    {		// letter 'A'
				button	= document.getElementById('showAncTree');
				button.click(); 
				return false;
		    }		// letter 'A'

		    case 'b':
		    case 'B':
		    {		// letter 'B'
				button	= document.getElementById('PostBlog');
				button.click(); 
				return false;
		    }		// letter 'B'

		    case 'd':
		    case 'D':
		    {		// letter 'D'
				button	= document.getElementById('showDescTree');
				button.click(); 
				return false;
		    }		// letter 'D'

		    case 'e':
		    case 'E':
		    {		// letter 'E'
				button	= document.getElementById('edit');
				button.click(); 
				return false;
		    }		// letter 'E'

		    case 'r':
		    case 'R':
		    {		// letter 'R'
				button	= document.getElementById('relationshipCalc');
				button.click(); 
				return false;
		    }		// letter 'R'

		    case 's':
		    case 'S':
		    {		// letter 'S'
				button	= document.getElementById('ancestrySearch');
				button.click(); 
				return false;
		    }		// letter 'S'

		}	        // switch on key code
    }		        // alt key shortcuts

    return true;	// do default action
}		// diKeyDown

/************************************************************************
 *  function showMap													*
 *																		*
 *  This function is called if the user clicks on the show Map button.	*
 *  It displays a map using Google maps support.						*
 *																		*
 *  Input:																*
 *		this		<button id='ShowMap'> 								*
 ************************************************************************/
function showMap()
{
    var	button		= this;
    var	form		= button.form;
    var latlng		= null;		// Google maps latitude/longitude
    var	mapDiv		= document.getElementById("mapDiv");
    if (mapDiv === null || mapDiv === undefined)
    {
		alert("Person.js: showMap: cannot locate <div id='mapDiv'>");
		return;
    }

    // if latitude and longitude specified in database, display the
    // map based upon those values
    var lat		    = form.Latitude.value;
    var lng		    = form.Longitude.value;
    var locn		= form.Location.value;
    var searchName	= form.searchName.value;
    var boundary	= form.Boundary.value;
    var zoom		= Number(form.Zoom.value);

    if (lat != '0' || lng != '0')
    {		// display map for coordinates
		try {
		    displayMap(new google.maps.LatLng(lat, lng),
				       zoom,
				       boundary);
		}
		catch(e)
		{
		    alert("Unable to use google maps to display map of location: " +
				  "message='" + e.message + "', " +
				  "lat=" + lat + ", lng=" + lng + ", zoom=" + zoom);
		}
    }		// display map for coordinates
    else
    if (geocoder !== null)
    {		// use Geocoder
		geocoder.geocode( { 'address': searchName},
						 function(results, status) {
		    if (status == google.maps.GeocoderStatus.OK) {
				displayMap(results[0].geometry.location,
						   zoom,
						   boundary);
		    }
		    else
		    {		// geocode failed
				popupAlert("Person.js: showMap: " +
				"Geocode for '" + searchName + "' was not successful for the following reason: " + status,
						   this);
		    }		// geocode failed
		});		// end of inline function and invocation of geocode
    }		// use Geocoder
    else
		popupAlert("Person.js: showMap: cannot locate Google geocoder",
				   this);
    return false;
}		// showMap

/************************************************************************
 *  function displayMap													*
 *																		*
 *  This function is called to display a Google maps map				*
 *  of the location.													*
 *																		*
 *  Input:																*
 *		latlng			instance of google.maps.LatLng					*
 *		zoomlevel		level of detail to zoom in on					*
 *		boundary		array of instances of LatLng as a string		*
 ************************************************************************/
function displayMap(latlng,
				    zoomlevel, 
				    boundStr)
{
    // parse the boundary string
    var	latPatt	= /\(([0-9.\-]+)/;
    var	lngPatt	= /([0-9.\-]+)\)/;
    path	= [];

    if (boundStr.length > 0)
    {		// have a boundary to display
		var	bounds	= boundStr.split(',');
		for (var ib=0; ib < bounds.length; ib++)
		{		// loop through each element
		    var	bound	= bounds[ib];
		    var rxRes	= latPatt.exec(bound);
		    if (rxRes != null)
		    {		// latitude 
				var lat	= rxRes[1];
				ib++;
				bound	= bounds[ib];
				rxRes	= lngPatt.exec(bound);
				if (rxRes != null)
				{		// longitude)
				    var lng		= rxRes[1];
				    var latLng	= new google.maps.LatLng(lat,
										 lng);
				    path.push(latLng);
				}		// longitude
				else
				{	// match failed
				    alert("Person.js: displayMap: " +
				      "Invalid Boundary Element " +
				      bound + " ignored");
				}	// match failed
		    }	// succeeded
		    else
				alert("Person.js: displayMap: " +
				      "Invalid Boundary Element " +
				      bound + " ignored");
		}		// loop through each element
		boundary	= new google.maps.Polygon(polyOptions);
		boundary.setPath(path);
    }		// have a boundary to display
    else
		boundary	= null;

    if (latlng !== null)
    {		// location resolved
		var	form		= document.locForm;
		mapDiv			= document.getElementById("mapDiv");
		mapDiv.style.left	= "0px";
		mapDiv.style.top	= "0px";
		show(mapDiv);				// make visible

		var hideMapDiv		= document.getElementById("hideMapDiv");
		hideMapDiv.style.left	= "80px";
		hideMapDiv.style.top	= "0px";
		hideMapDiv.style.width	= "120px";
		var hideMapBtn		= document.getElementById("hideMap");
		hideMapBtn.onclick	= hideMap;
		show(hideMapDiv);			// make visible

		var myOptions = {
				  zoom: zoomlevel,
				  center: latlng,
				  mapTypeId: google.maps.MapTypeId.ROADMAP
				};
		try {
		    map	= new google.maps.Map(mapDiv,
							      myOptions);
		    try {
				    var marker = new google.maps.Marker({map: map, 
									 position: latlng });
		    }		// try to allocate marker
		    catch(e) {
				alert("Person.js: displayMap: " +
				      "new google.maps.Marker failed: message='" + e.message +
				      "'");
		    }

		    // display boundary if any
		    if (boundary)
				boundary.setMap(map);
		}		// try to allocation map
		catch(e) {
		    alert("Person.js: displayMap: " +
				  "new google.maps.Map failed: message='" + e.message + "'");
		}
    }		// location resolved
    else
    {		// location not resolved
		alert("Person.js: displayMap: location " + locn + 
				" not resolved");
    }		// location not resolved
}		// displayMap

/************************************************************************
 *  function hideMap													*
 *																		*
 *  This function is called if the user clicks on the Hide Map button.	*
 *  It hides the map that has previously been displayed.				*
 *																		*
 *  Input:																*
 *		this		<button id='Parents'>								*
 ************************************************************************/
function hideMap()
{
    var	button		= this;
    var	hideMapDiv	= document.getElementById("hideMapDiv");
    hideMapDiv.style.display	= 'none';	// hide
    var	mapDiv		= document.getElementById("mapDiv");
    mapDiv.style.display	= 'none';	// hide
    return false;
}		// hideMap

/************************************************************************
 *  function editLocation												*
 *																		*
 *  This function is called if the user clicks on an edit ocation		*
 *  button in the popup for a location.									*
 *  It opens the edit dialog for the Location record.			        * 
 *																		*
 *  Input:																*
 *		this		<button id='editLoc9999'> 							*
 ************************************************************************/
function editLocation()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	button		= this;
    var	form		= button.form;
    var	idlr		= button.id.substring(7);
    var	url		= "/FamilyTree/Location.php?idlr=" + idlr +
                                            '&lang=' + lang +
								            "&closeAtEnd=Y";
    if (debug.toLowerCase() == 'y')
		url		+= '&debug=Y';
    openFrame("chooser",
		      url,
		      "right");
}		// function editLocation

/************************************************************************
 *  function submitMbBirth												*
 *																		*
 *  This function is called if the user clicks on a Manitoba birth      *
 *  registration detail.                                                *
 *  It submits the containing form.                                     *
 *																		*
 *  Input:																*
 *		this		<button id='submitMbBirth9999'> 					*
 ************************************************************************/
function submitMbBirth()
{
    var	form		= this.parentElement;
    form.submit();
}		// function submitMbBirth

/************************************************************************
 *  function locMouseOver												*
 *																		*
 *  This function is called if the mouse moves over an element			*
 *  containing a location on the invoking page. 						*
 *  Delay popping up the information balloon for two seconds.			*
 *																		*
 *  Input:																*
 *		this		HTML tag											*
 ************************************************************************/
function locMouseOver()
{
    // this method reuses the display management fields from popup help
    helpElt		= this;
    helpDelayTimer	= setTimeout(popupLoc, 2000);
}		// locMouseOver

/************************************************************************
 *  function popupLoc													*
 *																		*
 *  This function is called if the mouse is held over a location		*
 *  element on the invoking page for more than 2 seconds.  It shows		*
 *  the information from the associated instance of Location			*
 ************************************************************************/
function popupLoc()
{
    var	locIndex	= helpElt.id.indexOf('_');
    var idlr		= helpElt.id.substring(locIndex + 1);
    var matches     = idlr.match(/\d+/)
    if (matches)
        idlr        = matches[0];
    var	prefix		= helpElt.id.substring(0,7);
    helpDiv		    = document.getElementById(prefix + "Div" + idlr);
    if (helpDiv)
    {		// have a help division to display
		var	tableWidth	= window.innerWidth;
		helpDiv.style.left	= Math.max(Math.min(getOffsetLeft(helpElt) - 50,		                tableWidth - Math.floor(window.innerWidth/2)), 2) + 'px';
		helpDiv.style.top	= (getOffsetTop(helpElt) +
							  helpElt.offsetHeight + 5) + 'px';
		helpDiv.onkeydown	= keyDown;
		// so key strokes in balloon will close window
		show(helpDiv);
    }		// have a help division to display
    else
    {
		alert('Person.js: popupLoc: Logic Error: "' + prefix + 'Div' + idlr + '" not found'); 
    }
}		// popupLoc

/************************************************************************
 *  function locMouseOut												*
 *																		*
 *  This function is called if the mouse moves off an element			*
 *  containing a location name on the invoking page. 					*
 *  The help balloon, if any, remains up for							*
 *  a further 2 seconds to permit access to links within the help text.	*
 *																		*
 *  Input:																*
 *		this		HTML tag											*
 ************************************************************************/
function locMouseOut()
{
    clearTimeout(helpDelayTimer);
    helpDelayTimer	= setTimeout(hideHelp, 2000);
}		// locMouseOut

/************************************************************************
 *  function causeMouseOver												*
 *																		*
 *  This function is called if the mouse moves over an element			*
 *  containing the text of a cause of death on the invoking page. 		*
 *  Delay popping up the information balloon for two seconds.			*
 *																		*
 *  Input:																*
 *		this		<span> element containing a cause of death			*
 ************************************************************************/
function causeMouseOver()
{
    // this method reuses the display management fields from popup help
    helpElt		= this;
    helpDelayTimer	= setTimeout(popupCause, 2000);
}		// causeMouseOver

/************************************************************************
 *  function popupCause													*
 *																		*
 *  This function is called if the mouse is held over a cause element	*
 *  on the invoking page for more than 2 seconds.  It shows the			*
 *  explanation of the cause of death from the script DeathCauses.php	*
 ************************************************************************/
function popupCause()
{
    var	causeIndex	= helpElt.id.substring(10);
    helpDiv	= document.getElementById("DeathCauseHelp" + causeIndex);
    if (helpDiv)
    {		// have a help division to display
		var	tableWidth	= window.innerWidth;
		helpDiv.style.left	= Math.max(Math.min(getOffsetLeft(helpElt) - 50,		tableWidth - Math.floor(window.innerWidth/2)), 2) + 'px';
		helpDiv.style.top	= (getOffsetTop(helpElt) +
							  helpElt.offsetHeight + 5) + 'px';
		helpDiv.onkeydown	= keyDown;
		show(helpDiv);
		// so key strokes in balloon will close window
    }		// have a help division to display
}		// popupCause

/************************************************************************
 *  function causeMouseOut												*
 *																		*
 *  This function is called if the mouse moves off an element			*
 *  containing a cause name on the invoking page. 						*
 *  The help balloon, if any, remains up for							*
 *  a further 2 seconds to permit access to links within the help text.	*
 *																		*
 *  Input:																*
 *		this		<span> element containing a cause of death			*
 ************************************************************************/
function causeMouseOut()
{
    clearTimeout(helpDelayTimer);
    helpDelayTimer	= setTimeout(hideHelp, 2000);
}		// causeMouseOut

/************************************************************************
 *  function srcMouseOver												*
 *																		*
 *  This function is called if the mouse moves over an element			*
 *  containing a hyperlink to a source record on the invoking page. 	*
 *  Delay popping up the information balloon for two seconds.			*
 *																		*
 *  Input:																*
 *		this		<a> tag												*
 ************************************************************************/
function srcMouseOver()
{
    // this method reuses the display management fields from popup help
    helpElt		= this;
    helpDelayTimer	= setTimeout(popupSource, 2000);
}		// srcMouseOver

/************************************************************************
 *  function popupSource												*
 *																		*
 *  This function is called if the mouse is held over a link to a		*
 *  source record on the invoking page for more than 2 seconds.			*
 *  It shows the information  from the associated instance of			*
 *  Source														        *
 ************************************************************************/
function popupSource()
{
    var	indIndex	= helpElt.href.indexOf('=');
    if (indIndex >= 0)
    {
		var idsr	= helpElt.href.substring(indIndex + 1);
        var j       = idsr.indexOf('&');
        if (j > 0)
            idsr    = idsr.substring(0, j);

		// if a previous help balloon is still being displayed, hide it
		if (helpDiv)
		{		// a help division is currently displayed
		    helpDiv.style.display	= 'none';
		    helpDiv			= null;
		}		// a help division is currently displayed

		helpDiv	= document.getElementById("Source" + idsr);

		if (helpDiv)
		{		// have the division

		    // position and display division
		    var leftOffset	= getOffsetLeft(helpElt);
		    if (leftOffset > (window.innerWidth / 2))
				leftOffset	= window.innerWidth / 2;
		    helpDiv.style.left	= leftOffset + "px";
		    helpDiv.style.top	= (getOffsetTop(helpElt) + 30) + 'px';
		    show(helpDiv)
//alert("util.js: popupSource: helpDiv.style.left=" + helpDiv.style.left +
//			", helpDiv.style.top=" + helpDiv.style.top);
		}		// have the division to display
		else
		    alert("person.js: popupSource: Cannot find <div id='Source" +
				  idsr + "'>");
    }
}		// popupSource

/************************************************************************
 *  function srcMouseOut												*
 *																		*
 *  This function is called if the mouse moves off an element			*
 *  containing a hyperlink to a source record on the invoking page. 	*
 *  The help balloon, if any, remains up for							*
 *  a further 2 seconds to permit access to links within the help text.	*
 *																		*
 *  Input:																*
 *		this		<a> tag												*
 ************************************************************************/
function srcMouseOut()
{
    clearTimeout(helpDelayTimer);
    helpDelayTimer	= setTimeout(hideHelp, 2000);
}		// srcMouseOut

/************************************************************************
 *  function indMouseOver												*
 *																		*
 *  This function is called if the mouse moves over an element			*
 *  containing a hyperlink to an individual on the invoking page. 		*
 *  Delay popping up the information balloon for two seconds.			*
 *																		*
 *  Input:																*
 *		this		<a> tag												*
 ************************************************************************/
function indMouseOver()
{
    // this method reuses the display management fields from popup help
    helpElt		= this;
    helpDelayTimer	= setTimeout(popupIndiv, 2000);
}		// indMouseOver

/************************************************************************
 *  function popupIndiv													*
 *																		*
 *  This function is called if the mouse is held over a link to an		*
 *  individual on the invoking page for more than 2 seconds.  It shows	*
 *  the information from the associated instance of Person				*
 ************************************************************************/
function popupIndiv()
{
    var	indIndex	= helpElt.href.indexOf('=');
    if (indIndex >= 0)
    {
		var idir	= helpElt.href.substring(indIndex + 1);
		var ampPos	= idir.indexOf('&');
		if (ampPos > 0)
		    idir	= idir.substring(0,ampPos);

		// if a previous help balloon is still being displayed, hide it
		if (helpDiv)
		{		// a help division is currently displayed
		    helpDiv.style.display	= 'none';
		    helpDiv			= null;
		}		// a help division is currently displayed

		helpDiv	= document.getElementById("Individ" + idir);

		if (helpDiv)
		{		// have the division

		    // position and display division
		    var leftOffset	= getOffsetLeft(helpElt);
		    if (leftOffset > (window.innerWidth / 2))
				leftOffset	= window.innerWidth / 2;
		    helpDiv.style.left	= leftOffset + "px";
		    helpDiv.style.top	= (getOffsetTop(helpElt) + 30) + 'px';
		    show(helpDiv)
//alert("util.js: popupSource: helpDiv.style.left=" + helpDiv.style.left +
//			", helpDiv.style.top=" + helpDiv.style.top);
		}		// have the division to display
		else
		    alert("util.js: popupIndiv: Cannot find <div id='Individ" +
				  idir + "'>");
    }
}		// popupIndiv

/************************************************************************
 *  function indMouseOut												*
 *																		*
 *  This function is called if the mouse moves off an element			*
 *  containing a indiv name on the invoking page. 						*
 *  The help balloon, if any, remains up for							*
 *  a further 2 seconds to permit access to links within the help text.	*
 *																		*
 *  Input:																*
 *		this		<a> tag												*
 ************************************************************************/
function indMouseOut()
{
    clearTimeout(helpDelayTimer);
    helpDelayTimer	= setTimeout(hideHelp, 2000);
}		// indMouseOut
