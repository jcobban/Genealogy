/************************************************************************
 *  Account.js															*
 *																		*
 *  Implement the dynamic functionality of the account management		*
 *  script.																*
 *																		*
 *  History:															*
 *		2010/10/30		created											*
 *		2011/03/10		on sign off clear userid out of rightTop button	*
 *						of opener										*
 *		2011/04/22		IE does not implement form.elements correctly	*
 *		2012/01/05		use id rather than name for buttons to avoid	*
 *						passing them to the action script in IE			*
 *						change signoff to alt-O to match Signon script	*
 *		2012/05/28		add mouse-over help balloons					*
 *		2014/03/27		add on the fly validation of input fields		*
 *		2014/08/29		add blog messages support						*
 *		2018/02/05		changed to support template						*
 *		2018/02/28		add random password generator					*
 *						add score for supplied password					*
 *		2018/10/18      pass language to scripts initiated by buttons   *
 *		2018/12/21      increase probability of digits and letters      *
 *		                in generated password, and trim password        *
 *		2019/02/06      session status moved to link in menu            *
 *		2019/02/08      use addEventListener                            *
 *		2019/12/03      change random password generator to exclude "   *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization of dynamic functionality after page is		*
 *  loaded.																*
 *																		*
 ************************************************************************/
function onLoad()
{
    if (document.body.addEventListener) 
    {       // For all major browsers, except IE 8 and earlier
        document.body.addEventListener("keydown",
                                       amKeyDown, 
                                       false);
    }       // For all major browsers, except IE 8 and earlier
    else 
    if (document.body.attachEvent) 
    {       // For IE 8 and earlier versions
        document.body.attachEvent("onkeydown",
                                  amKeyDown);
    }       // For IE 8 and earlier versions

    for(var i = 0; i < document.forms.length; i++)
    {                   // loop through forms
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{               // loop through elements
		    var element		= form.elements[j];
		    var	name		= element.name;
		    if(!name || name.length == 0)
				name		= element.id;

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    element.onmouseover		= eltMouseOver;
		    element.onmouseout		= eltMouseOut;
		    element.onkeydown		= keyDown;

		    switch(name)
		    {	        // act on specific element
				case 'userid':
				{
				    element.focus();	// put focus in userid field
				    break;
				}

				case 'Close':
				{
				    element.onclick	= finish;
				    break;
				}

				case 'Signoff':
				{
				    element.onclick	= signoff;
				    break;
				}

				case 'newPassword':
				{
				    if (element.addEventListener) 
				    {       // For all major browsers, except IE 8 and earlier
				        element.addEventListener("keypress",
                                                 newPasswordKeyPress, 
                                                 false);
				        element.addEventListener("change",
                                                 newPasswordChange, 
                                                 false);
				    }       // For all major browsers, except IE 8 and earlier
				    else 
				    if (element.attachEvent) 
				    {       // For IE 8 and earlier versions
				        element.attachEvent("onkeypress",
                                            newPasswordKeyPress);
				        element.attachEvent("onchange",
                                            newPasswordChange);
				    }       // For IE 8 and earlier versions
				    break;
				}

				case 'newPassword2':
				{
				    element.onchange	= checkNewPassword2;
				    break;
				}

				case 'generatePassword':
				{
				    element.onclick	= generatePassword;
				    break;
				}

				case 'email':
				{
				    element.onchange	= checkEmail;
				    break;
				}

				default:
				{		// others
				    if (name.substring(0, 6) == 'Delete')
						element.onclick	= deleteBlog;
				    else
				    if (name.substring(0, 5) == 'Reply')
						element.onclick	= replyBlog;
				    break;
				}		// others

		    }	        // act on specific element
		}	            // loop through elements in form
    }		            // loop through all form elements
}		// function onLoad

/************************************************************************
 *  function signoff													*
 *																		*
 *  Sign off.															*
 *																		*
 *  Input:																*
 *		this		<button id='Signoff'>								*
 ************************************************************************/
function signoff()
{
    var	opener		    = window.opener;
    if (opener)
    {			// invoked from another window
        var callPage    = opener.document;
		var session	    = callPage.getElementById("session");
		if (session)
		{		// opener has a session button
            var href    = session.getAttribute('href');
            session.setAttribute('href', href.replace('Account','Signon'));
            var userInfoSignon  = callPage.getElementById("UserInfoSignon");
            if (userInfoSignon)
                session.innerHTML   = userInfoSignon.innerHTML;
            else
                session.innerHTML   = 'Sign On';
		}		// opener has a session button
    }			// invoked from another window

    // go to the signon dialog to permit user to sign on with a different
    // userid.  This also completes the server side actions for the signoff.
    var	lang			= 'en';
    if ('lang' in args)
		lang			= args.lang;
    var	form		    = document.accountForm;
    form.action		    = 'Signon.php';
    form.userid.value	= '';	// clear userid and password
    form.password.value	= '';
    form.act.value	    = 'logoff';
    form.lang.value	    = lang;
    form.submit();		// clears session data
}		// signoff

/************************************************************************
 *  function finish														*
 *																		*
 *  Close the current window											*
 *																		*
 *  Input:																*
 *		this			<button id='Close'>								*
 ************************************************************************/
function finish()
{
    window.close();
}		// finish

/************************************************************************
 *  checkEmail															*
 *																		*
 *  Validate the e-mail address.										*
 *																		*
 *  Input:																*
 *		this		<input type='text' id='email'>						*
 ************************************************************************/
function checkEmail()
{
    var emailPattern	= /^\w+@[.a-zA-Z0-9]+$/;
    if (emailPattern.test(this.value))
		this.className	= 'actleftnc';
    else
		this.className	= 'actleftncerror';
}		// checkEmail

/************************************************************************
 *  newPasswordKeyPress													*
 *																		*
 *  handle key presses which alter the value of the password field		*
 *  as the user is typing into the field.								*
 *																		*
 *  Input:																*
 *		this		<input type='text' id='newPassword'>				*
 *		evt			the key press event							    	*
 ************************************************************************/
function newPasswordKeyPress(evt)
{
    var	code		= evt.which || evt.keyCode;
    var element     = this;
    if (element == window)
        element     = evt.target || evt.srcElement;
    var	pass		= element.value + String.fromCharCode(code);
    if (code == 8)	// backspace passed by some browsers
		pass		= pass.substr(0, pass.length - 2);
    scorePassword(pass);
}		// function newPasswordKeyPress

/************************************************************************
 *  newPasswordChange													*
 *																		*
 *  Handle changes to the value of the password field.  This is called	*
 *  if the user finished changing the value and leaves the field.		*
 *																		*
 *  Input:																*
 *		this		<input type='text' id='newPassword'>				*
 *		evt			the change event							    	*
 ************************************************************************/
function newPasswordChange(evt)
{
    var element     = this;
    if (element == window)
        element     = evt.target || evt.srcElement;
    scorePassword(element.value);
}

/************************************************************************
 *  scorePassword														*
 *																		*
 *  Determine the entropy of the supplied password.  This is called		*
 *  as the user is changing the field.									*
 *																		*
 *  Input:																*
 *		pass			password to check								*
 ************************************************************************/
function scorePassword(pass)
{
    // determine the size of the character set chosen by the user
    var	digits		= false;
    var	lower		= false;
    var	upper		= false;
    var	specASCII	= false;
    var	unicode		= [];		// other unicode

    for(var i = 0; i < pass.length; i++)
    {
		var code	= pass.charCodeAt(i);
		if (code >= "0".charCodeAt(0) && code <= "9".charCodeAt(0))
		    digits	= true;
		else
		if (code >= "A".charCodeAt(0) && code <= "Z".charCodeAt(0))
		    upper	= true;
		else
		if (code >= "a".charCodeAt(0) && code <= "z".charCodeAt(0))
		    lower	= true;
		else
		if (code >= 32 && code <= 128)
		    specASCII	= true;
		else
		if (code >= "0".charCodeAt(0) && code <= "9".charCodeAt(0))
		    digits	= true;
		else
		if (code >= 128)
		{			// other unicode code page
		    var codePage	= Math.floor(code / 128);
		    unicode[codePage]	= true;
		}			// other unicode code page
    }

    // calculate the logarithm of the character set size
    // most theoretical discussions use the base 2 logarithm since they
    // are determining the total number of potential passwords that can be
    // expressed in the character set.  The following uses log 10.
    var	logSetSize = 0.0;
    if (digits)
		logSetSize	+= 1.0;
    if (lower)
		logSetSize	+= 1.415;
    if (upper)
		logSetSize	+= 1.415;
    if (specASCII)
		logSetSize	+= 1.519;
    logSetSize		+= unicode.length * 2.107;

    var score	= Math.floor(pass.length * logSetSize);

    var passwordStrong	= document.getElementById('passwordStrong');
    var passwordGood	= document.getElementById('passwordGood');
    var passwordWeak	= document.getElementById('passwordWeak');
    var passwordPoor	= document.getElementById('passwordPoor');
    if (score > 90)
    {
		passwordStrong.style.display	= 'inline';
		passwordGood.style.display	= 'none';
		passwordWeak.style.display	= 'none';
		passwordPoor.style.display	= 'none';
    }
    else
    if (score > 60)
    {
		passwordStrong.style.display	= 'none';
		passwordGood.style.display	= 'inline';
		passwordWeak.style.display	= 'none';
		passwordPoor.style.display	= 'none';
    }
    else
    if (score >= 30)
    {
		passwordStrong.style.display	= 'none';
		passwordGood.style.display	= 'none';
		passwordWeak.style.display	= 'inline';
		passwordPoor.style.display	= 'none';
    }
    else
    {
		passwordStrong.style.display	= 'none';
		passwordGood.style.display	= 'none';
		passwordWeak.style.display	= 'none';
		passwordPoor.style.display	= 'inline';
    }
}		// scorePassword

/************************************************************************
 *  generatePassword													*
 *																		*
 *  Generate a new random password for the user.						*
 *																		*
 *  Input:																*
 *		this		<button id='generatePassword'>						*
 ************************************************************************/
var charset     = "!#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
function generatePassword()
{
    var	randArray	    = new Uint32Array(12);
    window.crypto.getRandomValues(randArray);
    var	password		= '';
    for(var i = 0; i < randArray.length; i++)
    {
		var code	    = randArray[i] % charset.length;
		password	    += charset.substr(code, 1);
    }
    var outputElement	= document.getElementById('randomPassword');
    outputElement.value	= password;
    return false;
}		// function generatePassword

/************************************************************************
 *  checkNewPassword2													*
 *																		*
 *  Validate the repeat of the new password								*
 *																		*
 *  Input:																*
 *		this		<input type='text' id='newPassword2'>				*
 ************************************************************************/
function checkNewPassword2()
{
    if (this.value != this.form.newPassword.value)
		alert("The two copies of the new password must be the same");
}		// checkNewPassword2

/************************************************************************
 *  deleteBlog															*
 *																		*
 *  This method is called when the user requests to delete a specific	*
 *  message. This is the onclick method of <button id='Delete...'>		*
 *																		*
 *  Input:																*
 *		this		<button id='Delete...'>								*
 ************************************************************************/
function deleteBlog()
{
    var	form	= this.form;
    var	blid	= this.id.substring(6);
    // get the subdistrict information file
    parms	= {'id'		: blid};

    HTTP.post("deleteBlogXml.php",
				parms,
				gotDelete,
				noDelete);
    return true;
}	// deleteDelete

/************************************************************************
 *  gotDelete															*
 *																		*
 *  This method is called when the XML file representing				*
 *  the deletion of the blog is received.								*
 *																		*
 *  Input:																*
 *		xmlDoc		XML response file describing the deletion           *
 *		            of the message                                      *
 ************************************************************************/
function gotDelete(xmlDoc)
{
    //alert("UserInfo: gotDelete: " + tagToString(xmlDoc));
    location.reload();
}		// gotDelete

/************************************************************************
 *  noDelete															*
 *																		*
 *  This method is called if there is no script to delete the Blog.		*
 ************************************************************************/
function noDelete()
{
    alert("UserInfo: script deleteBlogXml.php not found on server");
}		// noDelete

/************************************************************************
 *  replyBlog															*
 *																		*
 *  This method is called when the user requests to view the reply		*
 *  to a specific queued message.										*
 *  This is the onclick method of <button id='Reply...'>				*
 *																		*
 *  Input:																*
 *		this		<button id='Reply...'>								*
 ************************************************************************/
function replyBlog()
{
    var	form	= this.form;
    var	blid	= this.id.substring(5);
    var	message	= this.form.elements['message' + blid].value;

    // get the subdistrict information file
    parms	= {'id'		: blid,
				   'message'	: message};

    HTTP.post("replyBlogXml.php",
				parms,
				gotReply,
				noReply);
    return true;
}	// replyBlog

/************************************************************************
 *  gotReply															*
 *																		*
 *  This method is called when the XML file representing				*
 *  the act of replying to the blog is received.						*
 *																		*
 *  Input:																*
 *		xmlDoc	XML response file describing the sending of the reply	*
 ************************************************************************/
function gotReply(xmlDoc)
{
    //alert("UserInfo: gotReply: " + tagToString(xmlDoc));
    location.reload();
}		// gotReply

/************************************************************************
 *  noReply																*
 *																		*
 *  This method is called if there is no script to reply to the Blog.	*
 ************************************************************************/
function noReply()
{
    alert("UserInfo: script replyBlogXml.php not found on server");
}		// function noReply

/************************************************************************
 *  amKeyDown															*
 *																		*
 *  Handle key strokes that apply to the entire dialog window.  For		*
 *  example the key combinations Ctrl-S and Alt-A are interpreted to	*
 *  apply the update, as shortcut alternatives to using the mouse to	*
 *  click the "Apply Changes" button.									*
 *																		*
 *  Parameters:															*
 *		e		instance of Event                                       *
 ************************************************************************/
function amKeyDown(e)
{
    var	code	= e.keyCode;
    var	form	= document.accountForm;

    // take action based upon code
    if (e.ctrlKey)
    {		// ctrl key shortcuts
		if (code == 83)
		{		// letter 'S'
		    form.submit();
		    return false;	// do not perform standard action
		}		// letter 'S'
    }		// ctrl key shortcuts
    
    if (e.altKey)
    {		// alt key shortcuts
        switch (code)
        {
		    case 65:
		    {		// letter 'A'
		        form.submit();
		        break;
		    }		// letter 'A'

		    case 67:
		    {		// letter 'C'
		        window.close();
		        break;
		    }		// letter 'C'

		    case 79:
		    {		// letter 'O'
		        signoff();
		        return false;	// suppress default action
		    }		// letter 'O'

        }	    // switch on key code
    }		// alt key shortcuts

    return true;	// perform default action as well
}		// function amKeyDown

