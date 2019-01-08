/************************************************************************
 *  BlogPost.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page BlogPost.php.													*
 *																		*
 *  History:															*
 *		2018/09/12		created											*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
window.onload	= onLoad;

// specify style for tinyMCE editing
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

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 ************************************************************************/
function onLoad()
{
    pageInit();

    document.body.onresize	= onWindowResize;

    var names	= "";
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    var	name	= element.name;
		    var	id	= '';
		    if (name.length == 0)
		    {		// button elements usually have id not name
				name	= element.id;
		    }		// button elements usually have id not name
		    var result	= /^([a-zA-Z]*)([0-9]*)$/.exec(name);
		    if (result !== null)
		    {
				name	= result[1];
				id	= result[2];
		    }

		    // take action specific to the element based on its name
		    switch(name.toLowerCase())
		    {		// switch on name
				case 'message':
				{	// blog text area
				    var msgLabel	= document.getElementById('msgLabel');
				    var mframe		= tinymce.DOM.get('message_ifr');
				    var textwidth	= window.innerWidth -
								  msgLabel.offsetWidth - 40;
				    tinymce.DOM.setStyle(mframe, 'width', textwidth + 'px');
				    element.focus();	// make it the current input element
				    element.select();	// select all text
				    break;
				}	// blog text area

				case 'postblog':
				{	// post blog button
				    element.onclick	= postBlog;
				    break;
				}	// post blog button

				case 'edit':
				{
				    element.onclick	= editBlog;
				    break;
				}

				case 'del':
				{
				    element.onclick	= delBlog;
				    break;
				}

				default:
				{
				    break;
				}

		    }		// switch on name
		}	// loop through elements in form
    }		// iterate through all forms

    // pop up help balloon if the mouse hovers over a field
    // for more than 2 seconds
    element		= document.getElementById('message_ifr');
    if (element)
    {
		element.helpDiv	= 'message';
		actMouseOverHelp(element);
    }

}		// onLoad

/************************************************************************
 *  onWindowResize														*
 *																		*
 *  This method is called when the browser window size is changed.		*
 *  If the window is split between the main display and a second		*
 *  display, resize.													*
 *																		*
 *  Input:																*
 *		this		<body> element										*
 ************************************************************************/
function onWindowResize()
{
    if (iframe)
		openFrame(iframe.name, null, "right");
    var msgLabel	= document.getElementById('msgLabel');
    var mframe		= tinymce.DOM.get('message_ifr');
    var textwidth	= window.innerWidth - msgLabel.offsetWidth - 40;
    tinymce.DOM.setStyle(mframe, 'width', textwidth + 'px');
}		// onWindowResize

/************************************************************************
 *  postBlog															*
 *																		*
 *  This method is called when the user requests to post				*
 *  a message to the blog of an individual.								*
 *																		*
 *  Input:																*
 *		this		<button id='PostBlog'>								*
 ************************************************************************/
function postBlog(rownum)
{
    var	form		= this.form;
    var	userid		= form.userid.value;
    var	email		= '';
    var	subject		= form.subject.value;
    if (form.emailAddress)
		email		= form.emailAddress.value;

    if (userid == '' && email == '')
    {			// not signed on or identified
		openSignon();
    }			// not signed on or identified
    else
    {
		var	idir		= form.blogid.value;
		var	message		= tinyMCE.get('message').getContent();
		var parms		= {
				"idir"		: idir,
				"table"		: 'Blogs',
				"emailAddress"	: email,
				"subject"	: subject,
				"message"	: message};

		var	blogParms	= "parms={" +
				"idir="		+ idir +
				", table='Blogs'" +
				", emailAddress='"+ email +
				"', subject='"	+ subject + "'";
		if ('edit' in args && args['edit'].toUpperCase() == 'Y')
		{
		    parms['update']	= 'Y';
		    blogParms		+= ", update='Y'";
		}
		blogParms		+= "}";
		if (debug.toLowerCase() == 'y')
		{
		    alert("BlogPost.js: postBlog: " + blogParms);
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
 *  gotBlog																*
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

	if (debug.toLowerCase() == 'y')
    {
        alert("BlogPost.js: gotBlog: xmlDoc=" + tagToString(xmlDoc));
    }

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
    {			// error
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
    }			// error

    if (msg.length > 0)
		popupAlert(msg, messageElt);

    var	url	    = location.href;
    url		    = url.replace(/&edit=Y/i, '');
    location	= url;	// refresh the page
}		// gotBlog

/************************************************************************
 *  noBlog																*
 *																		*
 *  This method is called if there is no blog script on the web server.	*
 ************************************************************************/
function noBlog()
{
    var	messageElt	= document.getElementById('PostBlog');
    popupAlert('BlogPost.js: noBlog: ' +
				    'script "postBlogXml.php" not found on web server',
    		   messageElt);
    location	= location;	// refresh the page
}		// noBlog

/************************************************************************
 *  editBlog															*
 *																		*
 *  This method is called if the user requests to edit the blog			*
 *  message.															*
 *																		*
 *  Input:																*
 *		this		<button id='edit...'>								*
 ************************************************************************/
function editBlog()
{
    var	lang	= 'en';
    if ('lang' in args)
		lang	= args.lang;
    location	= "/BlogPost.php?blogid=" + this.id.substring(4) +
						"&table=Blogs&lang=" + lang + "&edit=Y";
    return false;
}		// editBlog

/************************************************************************
 *  delBlog																*
 *																		*
 *  This method is called if the user requests to delete the blog		*
 *  message.															*
 *																		*
 *  Input:																*
 *		this		<button id='del...'>								*
 ************************************************************************/
function delBlog()
{
    var	form		= this.form;
    var	blid		= this.id.substring(3);

    var parms		= {"blid"	: blid};

    // invoke script to update blog and return XML result
    HTTP.post('/deleteBlogXml.php',
		      parms,
		      gotBlog,
		      noDelBlog);
}		// delBlog

/************************************************************************
 *  noDelBlog																*
 *																		*
 *  This method is called if there is no blog script on the web server.		*
 ************************************************************************/
function noDelBlog()
{
    alert('BlogPost.js: noDelBlog: ' +
				'script "deleteBlogXml.php" not found on web server');
}		// noDelBlog

