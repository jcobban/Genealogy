/************************************************************************
 *  Signon.js                                                           *
 *                                                                      *
 *  Implement the dynamic functionality of the common sign-on script.   *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/22      created                                         *
 *      2010/11/21      refresh invoking page                           *
 *      2011/03/19      add keyboard shortcuts                          *
 *                      update invoking button in opener                *
 *      2011/04/22      IE does not implement form.elements correctly   *
 *      2011/12/12      refresh invoking window rather than just fixing *
 *                      the right-top button                            *
 *      2012/01/05      make logoff more explicit                       *
 *                      use id rather than name for buttons to avoid    *
 *                      passing them to action script as parameters     *
 *                      in IE                                           *
 *      2012/05/28      add mouse-over help balloons                    *
 *      2013/12/10      clear password field before invoking            *
 *                      Register.php                                    *
 *      2014/07/18      reformat comments                               *
 *      2014/08/22      provide option to remember userid and password  *
 *      2015/06/30      did not handle remember userid and password     *
 *                      setting correctly                               *
 *      2015/08/04      add button to reset password                    *
 *      2016/01/18      signoff set wrong field to logoff               *
 *      2018/10/18      pass language to scripts initiated by buttons   *
 *      2019/05/18      call element.click() to simulate button click   *
 *      2022/06/13      use addEventListener                            *
 *                      fix password forgot                             *
 *      2022/06/24      support ES2015                                  *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
import {Cookie} from "../jscripts6/Cookie.js";
import {keyDown, args}
            from "../jscripts6/util.js";

window.addEventListener("load", onLoad);

/************************************************************************
 *  onLoad                                                              *
 *                                                                      *
 *  Perform initialization after page is loaded                         *
 *                                                                      *
 ************************************************************************/
function onLoad()
{
    document.body.addEventListener("keydown", soKeyDown);

    let cookie              = new Cookie('rememberme');
    for(let i = 0; i < document.forms.length; i++)
    {
        let form            = document.forms[i];
        form.addEventListener("submit", onSubmit);
        for(let j = 0; j < form.elements.length; j++)
        {
            let element     = form.elements[j];
            let name        = element.name;
            if(!name || name.length == 0)
                name        = element.id;

            // activate keystroke support
            element.addEventListener("keydown", keyDown);
            switch(name.toLowerCase())
            {       // act on specific element
                case 'userid':
                    element.focus();    // put focus in userid field
                    if (cookie.username)
                        element.value   = cookie.username;
                    break;              // userid

                case 'password':
                    if (cookie.password)
                        element.value   = cookie.password;
                    break;              // password

                case 'signoff':
                    element.onclick = signoff;
                    break;

                case 'forgotpassword':
                    element.onclick = forgotPassword;
                    break;

                case 'register':
                    element.onclick = register;
                    break;

                case 'close':
                    element.onclick = finish;
                    break;

                case 'redirect':
                    if (element.value == 'forgotPassword.php')
                    {
                        form.action = element.value;
                        form.submit();
                    }
                    break;      // redirect

            }       // act on specific element
        }           // loop through elements in form
    }               // loop through all form elements
}       // function onLoad

/************************************************************************
 *  signoff                                                             *
 *                                                                      *
 *  Sign the user off.                                                  *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type=button id='Signoff'>                   *
 ************************************************************************/
function signoff()
{
    let lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    let form            = this.form;
    form.action         = 'Signon.php?lang=' + lang;
    form.userid.value   = '';   // clear userid and password
    form.password.value = '';
    form.act.value      = 'logoff';
    form.submit();      // clears session data
}       // function signoff

/************************************************************************
 *  function register                                                   *
 *                                                                      *
 *  Invoke the process to register a new user.                          *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type=button id='Register'>                  *
 ************************************************************************/
function register()
{
    let lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    let form            = this.form;

    // change the action so the values are submitted to the
    // registration function instead of the sign-on function
    form.password.value = '';
    form.action         = 'Register.php?lang=' + lang;
    form.submit();
}       // function register

/************************************************************************
 *  forgotPassword                                                      *
 *                                                                      *
 *  Invoke the process to reset password an existing user.              *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type=button id='Register'>                  *
 ************************************************************************/
function forgotPassword()
{
    let form                = this.form;

    // password reset function instead of the sign-on function
    form.password.value     = '';
    form.act.value          = 'forgotPassword';
    form.submit();
}       // function forgotPassword


/************************************************************************
 *  onSubmit                                                            *
 *                                                                      *
 *  Completion of signon.                                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        <form>                                              *
 ************************************************************************/
function onSubmit()
{
    let form        = this;
    if (form.remember.checked)
    {
        let cookie  = new Cookie('rememberme');
        cookie.username = form.userid.value;
        cookie.password = form.password.value;
        cookie.store(30*24*60*60);  // remember for 30 days
    }
    return true;
}       // function onSubmit

/************************************************************************
 *  finish                                                              *
 *                                                                      *
 *  Close the current window                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type=button id='Close'>                     *
 ************************************************************************/
function finish()
{
    window.close();
}       // function finish

/************************************************************************
 *  soKeyDown                                                           *
 *                                                                      *
 *  Handle key strokes that apply to the entire dialog window.  For     *
 *  example: the key combinations Ctrl-S and Alt-A are interpreted to   *
 *  apply the update, as shortcut alternatives to using the mouse to    *
 *  click on the "Apply Changes" button.                                *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function soKeyDown(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e       =  window.event;    // IE
    }       // browser is not W3C compliant
    let code    = e.keyCode;
    let form    = document.signonForm;

    // take action based upon code
    if (e.ctrlKey)
    {       // ctrl key shortcuts
        if (code == 83)
        {           // letter 'S'
            form.submit();
            return false;   // do not perform standard action
        }           // letter 'S'
    }               // ctrl key shortcuts
    
    if (e.altKey)
    {               // alt key shortcuts
        switch (code)
        {
            case 67:
            {       // letter 'C'
                form.Close.click();
                break;
            }       // letter 'C'

            case 70:
            {       // letter 'F'
                form.ForgotPassword.click();
                break;
            }       // letter 'F'

            case 73:
            {       // letter 'I'
                form.submit();
                break;
            }       // letter 'I'

            case 79:
            {       // letter 'O'
                form.Signoff.click();
                break;
            }       // letter 'O'

            case 82:
            {       // letter 'R'
                form.Register.click();
                return false;
            }       // letter 'R'

        }       // switch on key code
    }       // alt key shortcuts

    return;
}       // function soKeyDown

