/************************************************************************
 *  Signon.js															*
 *																		*
 *  Implement the dynamic functionality of the common sign-on script.	*
 *																		*
 *  History:															*
 *		2010/08/22		created											*
 *		2010/11/21		refresh invoking page							*
 *		2011/03/19		add keyboard shortcuts							*
 *						update invoking button in opener				*
 *		2011/04/22		IE does not implement form.elements correctly	*
 *		2011/12/12		refresh invoking window rather than just fixing	*
 *						the right-top button							*
 *		2012/01/05		make logoff more explicit						*
 *						use id rather than name for buttons to avoid	*
 *						passing them to action script as parameters		*
 *						in IE											*
 *		2012/05/28		add mouse-over help balloons					*
 *		2013/12/10		clear password field before invoking			*
 *						Register.php									*
 *		2014/07/18		reformat comments								*
 *		2014/08/22		provide option to remember userid and password	*
 *		2015/06/30		did not handle remember userid and password		*
 *						setting correctly								*
 *		2015/08/04		add button to reset password					*
 *		2016/01/18		signoff set wrong field to logoff				*
 *		2018/10/18      pass language to scripts initiated by buttons   *
 *		2019/05/18      call element.click() to simulate buton click    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization after page is loaded							*
 *																		*
 ************************************************************************/
function onLoad()
{
    document.body.onkeydown	= soKeyDown;
    var	cookie	= new Cookie('rememberme');
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
        form.onsubmit	= onSubmit;
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element		= form.elements[j];
		    var	name		= element.name;
		    if(!name || name.length == 0)
				name		= element.id;

		    // activate keystroke support
		    element.onkeydown	= keyDown;

		    switch(name)
		    {	// act on specific element
				case 'userid':
				{
				    element.focus();	// put focus in userid field
				    if (cookie.username)
						element.value	= cookie.username;
				    break;
				}

				case 'password':
				{
				    if (cookie.password)
						element.value	= cookie.password;
				    break;
				}		// password

				case 'Signoff':
				{
				    element.onclick	= signoff;
				    break;
				}

				case 'ForgotPassword':
				{
				    element.onclick	= forgotPassword;
				    break;
				}

				case 'Register':
				{
				    element.onclick	= register;
				    break;
				}

				case 'Close':
				{
				    element.onclick	= finish;
				    break;
				}
		    }	// act on specific element
		}	// loop through elements in form
    }		// loop through all form elements
}		// onLoad

/************************************************************************
 *  signoff																*
 *																		*
 *  Sign the user off.													*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Signoff'>					*
 ************************************************************************/
function signoff()
{
    var	lang			= 'en';
    if ('lang' in args)
		lang			= args.lang;
    var	form			= this.form;
    form.action			= 'Signon.php?lang=' + lang;
    form.userid.value	= '';	// clear userid and password
    form.password.value	= '';
    form.act.value		= 'logoff';
    form.submit();		// clears session data
}		// signoff

/************************************************************************
 *  function register													*
 *																		*
 *  Invoke the process to register a new user.							*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Register'>		    		*
 ************************************************************************/
function register()
{
    var	lang			= 'en';
    if ('lang' in args)
		lang			= args.lang;
    var	form			= this.form;

    // change the action so the values are submitted to the
    // registration function instead of the sign-on function
    form.password.value	= '';
    form.action			= 'Register.php?lang=' + lang;
    form.submit();
}		// register

/************************************************************************
 *  forgotPassword														*
 *																		*
 *  Invoke the process to reset password an existing user.				*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Register'>			    	*
 ************************************************************************/
function forgotPassword()
{
    var	lang			= 'en';
    if ('lang' in args)
		lang			= args.lang;
    var	form			= this.form;

    // change the action so the values are submitted to the
    // password reset function instead of the sign-on function
    form.password.value	= '';
    form.action			= 'forgotPassword.php?lang=' + lang;
    form.submit();
}		// forgotPassword


/************************************************************************
 *  onSubmit															*
 *																		*
 *  Completion of signon.												*
 *																		*
 *  Input:																*
 *		this		<form>												*
 ************************************************************************/
function onSubmit()
{
    var	form		= this;
    if (form.remember.checked)
    {
		var	cookie	= new Cookie('rememberme');
		cookie.username	= form.userid.value;
		cookie.password	= form.password.value;
		cookie.store(30*24*60*60);	// remember for 30 days
    }
    return true;
}		// onSubmit

/************************************************************************
 *  finish																*
 *																		*
 *  Close the current window											*
 *																		*
 *  Input:																*
 *		this		<button type=button id='Close'>						*
 ************************************************************************/
function finish()
{
    window.close();
}		// finish

/************************************************************************
 *  soKeyDown															*
 *																		*
 *  Handle key strokes that apply to the entire dialog window.  For		*
 *  example: the key combinations Ctrl-S and Alt-A are interpreted to	*
 *  apply the update, as shortcut alternatives to using the mouse to 	*
 *  click on the "Apply Changes" button.								*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function soKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.signonForm;

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
		    case 67:
		    {		// letter 'C'
		        form.Close.click();
		        break;
		    }		// letter 'C'

		    case 70:
		    {		// letter 'F'
		        form.ForgotPassword.click();
		        break;
		    }		// letter 'F'

		    case 73:
		    {		// letter 'I'
		        form.submit();
		        break;
		    }		// letter 'I'

		    case 79:
		    {		// letter 'O'
		        form.Signoff.click();
		        break;
		    }		// letter 'O'

		    case 82:
		    {		// letter 'R'
		        form.Register.click();
		        return false;
		    }		// letter 'R'

        }	    // switch on key code
    }		// alt key shortcuts

    return;
}		// function soKeyDown


