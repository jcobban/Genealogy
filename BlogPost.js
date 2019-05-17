/************************************************************************
 *  BlogPost.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page BlogPost.php.													*
 *																		*
 *  History:															*
 *		2018/09/12		created											*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/04/13      support new tinyMCE                             *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.	*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
window.onload	= onLoad;

// specify style for tinyMCE editing
tinymce.init({
	selector            : 'textarea',
    plugins             : 'link lists image',
    menubar             : 'file edit view format insert',
    toolbar             : "undo redo | styleselect | bold italic | " 
                           + "alignleft aligncenter alignright alignjustify | " 
                           + "bullist numlist outdent indent | link image",
    content_css		    : "/styles.css"

});

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization functions once the page is loaded.			*
 ************************************************************************/
function onLoad()
{
    document.body.onresize	= onWindowResize;

    var names	        = "";

    var blogid          = 0;
    if ('blogid' in args)
        blogid          = args.blogid;
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

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
                case 'subject':
                {
                    if (blogid == 0)
                        element.focus();
				    break;
                }

				case 'message':
				{	// blog text area
				    var msgLabel	= document.getElementById('msgLabel');
				    var mframe		= tinymce.DOM.get('message_ifr');
				    var textwidth	= window.innerWidth -
								      msgLabel.offsetWidth - 40;
				    tinymce.DOM.setStyle(mframe, 'width', textwidth + 'px');
                    if (blogid > 0)
                    {	            // focus on the current input element
				        tinymce.get('message').focus();
                    }	            // focus on the current input element
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

    // pop up help balloon if the mouse hovers over the message input field
    // for more than 2 seconds
    element		= document.getElementById('message_ifr');
    if (element)
    {
		element.helpDiv	= 'message';
		actMouseOverHelp(element);
    }

}		// onLoad

/************************************************************************
 *  function onWindowResize												*
 *																		*
 *  This method is called when the browser window size is changed.		*
 *  For example if the window is split between the main display and	    *
 *  a second display, resize.											*
 *																		*
 *  Input:																*
 *		this		<body> element										*
 ************************************************************************/
function onWindowResize()
{
    if (iframe)
		openFrame(iframe.name, null, "right");
    var msgLabel	        = document.getElementById('msgLabel');
    var mframe		        = tinymce.DOM.get('message_ifr');
    var textwidth	        = window.innerWidth - msgLabel.offsetWidth - 40;
    tinymce.DOM.setStyle(mframe, 'width', textwidth + 'px');
    var subject             = document.getElementById('subject');
    subject.style.width     = textwidth + 'px';
    var email               = document.getElementById('emailAddress');
    email.style.width       = textwidth + 'px';
}		// onWindowResize

/************************************************************************
 *  function postBlog													*
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
 *  function noBlog														*
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
 *  function editBlog													*
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
 *  function delBlog													*
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
 *  function noDelBlog													*
 *																		*
 *  This method is called if there is no blog script on the web server.	*
 ************************************************************************/
function noDelBlog()
{
    alert('BlogPost.js: noDelBlog: ' +
				'script "deleteBlogXml.php" not found on web server');
}		// noDelBlog

