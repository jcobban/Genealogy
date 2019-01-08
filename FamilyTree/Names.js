/************************************************************************
 *  Names.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Names.php.														*
 *																		*
 *  History:															*
 *		2011/10/31		created											*
 *		2012/01/13		change class names								*
 *		2013/05/17		do not emit alert for missing table, it may just*
 *						mean no names matched							*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2015/05/18		add ability to edit the Surname record			*
 *		2015/06/02		use main style for TinyMCE editor				*
 *		2017/10/13		add support to validate regular expression		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 * specify the style for tinyMCE editing								*
 ************************************************************************/
tinyMCE.init({
		mode			: "textareas",
		theme			: "advanced",
		plugins 		: "spellchecker,advhr,preview", 

		// Theme options - button# indicated the row# only
		theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,fontselect,fontsizeselect,formatselect",
		theme_advanced_buttons2 : "cut,copy,paste,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,
		forced_root_block	: false,
		forced_root_block	: false,
		content_css		: "/styles.css",

});

window.onload	= onloadNames;

/************************************************************************
 *  onLoadNames																*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function onloadNames()
{
    pageInit();

    // activate functionality of form elements
    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var	form		= document.forms[fi];
		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{
		    var element	= formElts[i];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    var	name			= element.name;
		    if (name.length == 0)
		    {		// button elements usually have id not name
				name			= element.id;
		    }		// button elements usually have id not name

		    switch(name)
		    {		// act on specific element
				case 'PostBlog':
				{	// post blog button
				    element.onclick		= postBlog;
				    break;
				}	// post blog button

				case 'Pattern':
				{
				    element.onchange		= changePattern;
				    break;
				}	// Pattern input field

				case 'message':
				{
                    var frame       = document.getElementById('message_ifr');
                    frame.helpDiv   = 'message';
				    actMouseOverHelp(frame);    // tinymce
				    break;
				}	// Pattern input field

		    }		
		// act on specific element
		}		// loop through all elements in the form
    }			// loop through all forms

    // activate functionality associated with hyperlinks
    for(var il = 0; il < document.links.length; il++)
    {			// loop through all links
		var link	= document.links[il];
		actMouseOverHelp(link);
    }			// loop through all links

    // activate functionality of table cells
    var	table	= document.getElementById('namesTable');
    if (table)
    {		// table defined in page
		for(var ir = 0; ir < table.rows.length; ir++)
		{		// loop through all rows of table of names
		    var	row	= table.rows[ir];
		    for (var ic = 0; ic < row.cells.length; ic++)
		    {	// loop through all cells of table of names
				var cell	= row.cells[ic];
				if (cell)
				    cell.onclick	= followLink;
		    }	// loop through all cells of table of names
		}		// loop through all rows of table of names
    }		// table defined in page
}		// onLoadNames

/************************************************************************
 *  followLink																*
 *																		*
 *  This is the onclick method for a table cell that contains a <a>		*
 *  element.																*
 *  When this cell is clicked on, it acts as if the mouse was clicking		*
 *  on the contained <a> tag.												*
 ************************************************************************/
function followLink()
{
    for(var ie = 0; ie < this.childNodes.length; ie++)
    {		// loop through all children
		var node	= this.childNodes[ie];
		if (node.nodeName == 'A')
		{	// anchor node
		    location	= node.href;
		    return false;
		}	// anchor node
    }		// loop through all children
    return false;
}		// follow link

/************************************************************************
 *  postBlog																*
 *																		*
 *  This method is called when the user requests to post				*
 *  a message to the blog of an individual.								*
 *																		*
 *  Input:																*
 *		this				<button id='PostBlog'>								*
 ************************************************************************/
function postBlog(rownum)
{
    var	form		= this.form;
    var	userid		= form.userid.value;
    var	email		= '';
    if (form.emailAddress)
		email		= form.emailAddress.value;

    if (userid == '' && email == '')
    {			// not signed on or identified
		openSignon();
    }			// not signed on or identified
    else
    {
		var	idnr		= form.idnr.value;
		var	message		= tinyMCE.get('message').getContent();
		var parms		= {
				"idnr"		: idnr,
				"email"		: email,
				"message"	: message};

		if (debug.toLowerCase() == 'y')
		{
		    alert("Names.js: postBlog: /postBlogXml?idnr=" + idnr +
					"&email=" + email +
					"&message=" + message);
		}
		// invoke script to update Event and return XML result
		HTTP.post('/postBlogXml.php',
				  parms,
				  gotBlog,
				  noBlog);
    }
}		// postBlog

/************************************************************************
 *  gotBlog																*
 *																		*
 *  This method is called when the XML file representing				*
 *  a posted blog is retrieved from the database.						*
 *																		*
 *  Input:																*
 *		xmlDoc				response from web server as XML document		*
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
 *  noBlog																*
 *																		*
 *  This method is called if there is no blog script on the web server.		*
 ************************************************************************/
function noBlog()
{
    alert('Names.js: noBlog: ' +
				'script "postBlogXml.php" not found on web server');
}		// noBlog

/************************************************************************
 *  noDelBlog																*
 *																		*
 *  This method is called if there is no blog script on the web server.		*
 ************************************************************************/
function noDelBlog()
{
    alert('Names.js: noDelBlog: ' +
				'script "deleteBlogXml.php" not found on web server');
}		// noDelBlog

/************************************************************************
 *  changePattern														*
 *																		*
 *  This method is called if the user changes the regular expression		*
 *  pattern.																*
 *																		*
 *  Input:																*
 *		this				<button id='Pattern'>								*
 ************************************************************************/
function changePattern()
{
    var	pattern		= this.value;
    if (pattern.length > 0)
    {		// invoke script to update Event and return XML result
		HTTP.getXML('testNamePatternXml.php?pattern=' +
							encodeURIComponent(pattern),
				    gotTestPattern,
				    noTestPattern);
    }		// invoke script to update Event and return XML result
}		// changePattern

/************************************************************************
 *  gotTestPattern														*
 *																		*
 *  This method is called to process the response to testing the		*
 *  name pattern on the web server.										*
 ************************************************************************/
function gotTestPattern(xmlDoc)
{
    if (xmlDoc === null)
    {
		alert('Names.js: error');
		return;
    }
    var	root		= xmlDoc.documentElement;
    //alert("names.js: root=" + tagToString(root));
    var	cmd		= '';
    var	matchingNames	= '';
    var	comma		= '';
    if (root && root.nodeName == 'test')
    {
		//alert('count=' + root.attributes.count.value);
		for(var i = 0; i < root.childNodes.length; i++)
		{		// loop through children
		    var node	= root.childNodes[i];
		    if (node.nodeName == 'cmd')
				cmd	= node.textContent;
		    else
		    if (node.nodeName == 'surname')
		    {
				for (var j = 0; j < node.childNodes.length; j++)
				{
				    var child	= node.childNodes[j];
				    if (child.nodeName == 'surname')
				    {
					matchingNames	+= comma + child.textContent;
					comma		= ', ';
				    }
				}
		    }
		}		// loop through children
		alert('Pattern matches=' + matchingNames);
    }
    else
    if (root && root.nodeName == 'msg')
    {
		alert('Names.js: message=' + root.textContent);
    }
}		// gotTestPattern

/************************************************************************
 *  noTestPattern														*
 *																		*
 *  This method is called if there is no name pattern test script		*
 *  on the web server.														*
 ************************************************************************/
function noTestPattern()
{
    alert('Names.js: noTestPattern: ' +
				'script "testNamePatternXml.php" not found on web server');
}		// noTestPattern

/************************************************************************
 *  editBlog																*
 *																		*
 *  This method is called if the user requests to edit the blog				*
 *  message.																*
 *																		*
 *  Input:																*
 *		this				<button id='blEdit'>								*
 ************************************************************************/
function editBlog()
{
    alert('to do: editBlog: ' + this.id.substring(6));
    return false;
}		// editBlog

/************************************************************************
 *  delBlog																*
 *																		*
 *  This method is called if the user requests to delete the blog		*
 *  message.																*
 *																		*
 *  Input:																*
 *		this				<button id='blDel'>								*
 ************************************************************************/
function delBlog()
{
    var	form		= this.form;
    var	blid		= this.id.substring(5);

    var parms		= {"blid"	: blid};

    // invoke script to update blog and return XML result
    HTTP.post('/deleteBlogXml.php',
		      parms,
		      gotBlog,
		      noDelBlog);
}		// delBlog
