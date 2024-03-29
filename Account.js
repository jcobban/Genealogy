/************************************************************************
 *  Account.js                                                          *
 *                                                                      *
 *  Implement the dynamic functionality of the account management       *
 *  script.                                                             *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/30      created                                         *
 *      2011/03/10      on sign off clear userid out of rightTop button *
 *                      of opener                                       *
 *      2011/04/22      IE does not implement form.elements correctly   *
 *      2012/01/05      use id rather than name for buttons to avoid    *
 *                      passing them to the action script in IE         *
 *                      change signoff to alt-O to match Signon script  *
 *      2012/05/28      add mouse-over help balloons                    *
 *      2014/03/27      add on the fly validation of input fields       *
 *      2014/08/29      add blog messages support                       *
 *      2018/02/05      changed to support template                     *
 *      2018/02/28      add random password generator                   *
 *                      add score for supplied password                 *
 *      2018/10/18      pass language to scripts initiated by buttons   *
 *      2018/12/21      increase probability of digits and letters      *
 *                      in generated password, and trim password        *
 *      2019/02/06      session status moved to link in menu            *
 *      2019/02/08      use addEventListener                            *
 *      2019/12/03      change random password generator to exclude "   *
 *      2020/04/23      increase generated password length to 32        *
 *      2021/06/02      add support for account language preference     *
 *                      validate userid, email, and phone number        *
 *      2022/06/10      reduce generated password to 12 characters      *
 *                      copy generated password to new password fields  *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
import {HTTP} from "../jscripts6/js20/http.js";
import {eltMouseOver, eltMouseOut, keyDown, args}
            from "../jscripts6/util.js";
import {change, setErrorFlag}
            from "../jscripts6/CommonForm.js";

window.addEventListener("load", onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization of dynamic functionality after page is       *
 *  loaded.                                                             *
 *                                                                      *
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

    for(let i = 0; i < document.forms.length; i++)
    {                   // loop through forms
        let form    = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {               // loop through elements
            let element     = form.elements[j];
            let name        = element.name;
            if(!name || name.length == 0)
                name        = element.id;

            // pop up help balloon if the mouse hovers over a field
            // for more than 2 seconds
            element.addEventListener('mouseover',	eltMouseOver);
            element.addEventListener('mouseout',	eltMouseOut);
            element.onkeydown       = keyDown;

            switch(name)
            {           // act on specific element
                case 'userid':
                {
                    element.focus();    // put focus in userid field
                    element.addEventListener("change",
                                             change); 
                    element.checkfunc   = checkUserid;
                    break;
                }

                case 'Close':
                {
                    element.onclick     = finish;
                    break;
                }

                case 'Signoff':
                {
                    element.onclick     = signoff;
                    break;
                }

                case 'newPassword':
                {
                    element.addEventListener("keypress",
                                             newPasswordKeyPress);
                    element.addEventListener("change",
                                             newPasswordChange);
                    break;
                }

                case 'newPassword2':
                {
                    element.addEventListener("change",
                                             checkNewPassword2);
                    break;
                }

                case 'generatePassword':
                {
                    element.onclick     = generatePassword;
                    break;
                }

                case 'email':
                {
                    element.addEventListener("change",
                                             change); 
                    element.checkfunc   = checkEmail;
                    break;
                }

                case 'cellphone':
                {
                    element.addEventListener("change",
                                             change); 
                    element.checkfunc   = checkPhoneNumber;
                    break;
                }

                case 'language':
                {
                    let accountLang     = document.getElementById('accountLang');
                    element.value       = accountLang.value;
                    break;
                }


                default:
                {       // others
                    if (name.substring(0, 6) == 'Delete')
                        element.onclick = deleteBlog;
                    else
                    if (name.substring(0, 5) == 'Reply')
                        element.onclick = replyBlog;
                    break;
                }       // others

            }           // act on specific element
        }               // loop through elements in form
    }                   // loop through all form elements
}       // function onLoad

/************************************************************************
 *  function signoff                                                    *
 *                                                                      *
 *  Sign off.                                                           *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Signoff'>                               *
 ************************************************************************/
function signoff()
{
    let opener          = window.opener;
    if (opener)
    {           // invoked from another window
        let callPage    = opener.document;
        let session     = callPage.getElementById("session");
        if (session)
        {       // opener has a session button
            let href    = session.getAttribute('href');
            session.setAttribute('href', href.replace('Account','Signon'));
            let userInfoSignon  = callPage.getElementById("UserInfoSignon");
            if (userInfoSignon)
                session.innerHTML   = userInfoSignon.innerHTML;
            else
                session.innerHTML   = 'Sign On';
        }       // opener has a session button
    }           // invoked from another window

    // go to the signon dialog to permit user to sign on with a different
    // userid.  This also completes the server side actions for the signoff.
    let lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    let form            = document.accountForm;
    form.action         = 'Signon.php';
    form.userid.value   = '';   // clear userid and password
    form.password.value = '';
    form.act.value      = 'logoff';
    form.lang.value     = lang;
    form.submit();      // clears session data
}       // function signoff

/************************************************************************
 *  function finish                                                     *
 *                                                                      *
 *  Close the current window                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Close'>                             *
 ************************************************************************/
function finish()
{
    window.close();
}       // function finish

/************************************************************************
 *  checkUserid                                                         *
 *                                                                      *
 *  Validate the user name.                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='userid'>                     *
 ************************************************************************/
function checkUserid()
{
    let UseridPattern   = /^[^@]+$/;
    setErrorFlag(this, UseridPattern.test(this.value));
}       // function checkUserid

/************************************************************************
 *  checkEmail                                                          *
 *                                                                      *
 *  Validate the e-mail address.                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='email'>                      *
 ************************************************************************/
function checkEmail()
{
    let emailPattern    = /^[A-Za-z0-9+_!#$%&'*+/=?`{}~^..-]+@[.a-zA-Z0-9_-]+$/;
    setErrorFlag(this, emailPattern.test(this.value));
}       // function checkEmail

/************************************************************************
 *  checkPhoneNumber                                                    *
 *                                                                      *
 *  Validate the mobile phone number.                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='cellphone'>                  *
 ************************************************************************/
function checkPhoneNumber()
{
    let phoneNumberPattern   = /^[0-9() +-]+$/;
    setErrorFlag(this, phoneNumberPattern.test(this.value));
}       // function checkPhoneNumber

/************************************************************************
 *  newPasswordKeyPress                                                 *
 *                                                                      *
 *  handle key presses which alter the value of the password field      *
 *  as the user is typing into the field.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='newPassword'>                *
 *      evt         the key press event                                 *
 ************************************************************************/
function newPasswordKeyPress(evt)
{
    let code        = evt.which || evt.keyCode;
    let element     = this;
    if (element == window)
        element     = evt.target || evt.srcElement;
    let pass        = element.value + String.fromCharCode(code);
    if (code == 8)  // backspace passed by some browsers
        pass        = pass.substr(0, pass.length - 2);
    scorePassword(pass);
}       // function newPasswordKeyPress

/************************************************************************
 *  newPasswordChange                                                   *
 *                                                                      *
 *  Handle changes to the value of the password field.  This is called  *
 *  if the user finished changing the value and leaves the field.       *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='newPassword'>                *
 *      evt         the change event                                    *
 ************************************************************************/
function newPasswordChange(evt)
{
    let element     = this;
    if (element == window)
        element     = evt.target || evt.srcElement;
    scorePassword(element.value);
}

/************************************************************************
 *  scorePassword                                                       *
 *                                                                      *
 *  Determine the entropy of the supplied password.  This is called     *
 *  as the user is changing the field.                                  *
 *                                                                      *
 *  Input:                                                              *
 *      pass            password to check                               *
 ************************************************************************/
function scorePassword(pass)
{
    // determine the size of the character set chosen by the user
    let digits      = false;
    let lower       = false;
    let upper       = false;
    let specASCII   = false;
    let unicode     = [];       // other unicode

    for(let i = 0; i < pass.length; i++)
    {
        let code    = pass.charCodeAt(i);
        if (code >= "0".charCodeAt(0) && code <= "9".charCodeAt(0))
            digits  = true;
        else
        if (code >= "A".charCodeAt(0) && code <= "Z".charCodeAt(0))
            upper   = true;
        else
        if (code >= "a".charCodeAt(0) && code <= "z".charCodeAt(0))
            lower   = true;
        else
        if (code >= 32 && code <= 128)
            specASCII   = true;
        else
        if (code >= 128)
        {           // other unicode code page
            let codePage    = Math.floor(code / 128);
            unicode[codePage]   = true;
        }           // other unicode code page
    }

    // calculate the logarithm of the character set size
    // most theoretical discussions use the base 2 logarithm since they
    // are determining the total number of potential passwords that can be
    // expressed in the character set.  The following uses log 10.
    let logSetSize = 0.0;
    if (digits)
        logSetSize  += 1.0;
    if (lower)
        logSetSize  += 1.415;
    if (upper)
        logSetSize  += 1.415;
    if (specASCII)
        logSetSize  += 1.519;
    logSetSize      += unicode.length * 2.107;

    let score   = Math.floor(pass.length * logSetSize);

    let passwordStrong  = document.getElementById('passwordStrong');
    let passwordGood    = document.getElementById('passwordGood');
    let passwordWeak    = document.getElementById('passwordWeak');
    let passwordPoor    = document.getElementById('passwordPoor');
    if (score > 90)
    {
        passwordStrong.style.display    = 'inline';
        passwordGood.style.display  = 'none';
        passwordWeak.style.display  = 'none';
        passwordPoor.style.display  = 'none';
    }
    else
    if (score > 60)
    {
        passwordStrong.style.display    = 'none';
        passwordGood.style.display  = 'inline';
        passwordWeak.style.display  = 'none';
        passwordPoor.style.display  = 'none';
    }
    else
    if (score >= 30)
    {
        passwordStrong.style.display    = 'none';
        passwordGood.style.display  = 'none';
        passwordWeak.style.display  = 'inline';
        passwordPoor.style.display  = 'none';
    }
    else
    {
        passwordStrong.style.display    = 'none';
        passwordGood.style.display  = 'none';
        passwordWeak.style.display  = 'none';
        passwordPoor.style.display  = 'inline';
    }
}       // scorePassword

/************************************************************************
 *  generatePassword                                                    *
 *                                                                      *
 *  Generate a new random password for the user.                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='generatePassword'>                      *
 ************************************************************************/
const charset     = "!#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~";
function generatePassword()
{
    let randArray       = new Uint32Array(12);
    window.crypto.getRandomValues(randArray);
    let password        = '';
    for(let i = 0; i < randArray.length; i++)
    {
        let code        = randArray[i] % charset.length;
        password        += charset.substr(code, 1);
    }
    let outputElement   = document.getElementById('randomPassword');
    outputElement.value = password;
    outputElement       = document.getElementById('newPassword');
    outputElement.value = password;
    outputElement       = document.getElementById('newPassword2');
    outputElement.value = password;
    return false;
}       // function generatePassword

/************************************************************************
 *  checkNewPassword2                                                   *
 *                                                                      *
 *  Validate the repeat of the new password                             *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='newPassword2'>               *
 ************************************************************************/
function checkNewPassword2()
{
    if (this.value != this.form.newPassword.value)
        alert("The two copies of the new password must be the same");
}       // checkNewPassword2

/************************************************************************
 *  deleteBlog                                                          *
 *                                                                      *
 *  This method is called when the user requests to delete a specific   *
 *  message. This is the onclick method of <button id='Delete...'>      *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Delete...'>                             *
 ************************************************************************/
function deleteBlog()
{
    let blid    = this.id.substring(6);
    // get the subdistrict information file
    let parms       = {'id'     : blid};

    HTTP.post("deleteBlogXml.php",
              parms,
              gotDelete,
              noDelete);
    return true;
}   // deleteDelete

/************************************************************************
 *  gotDelete                                                           *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the deletion of the blog is received.                               *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      XML response file describing the deletion           *
 *                  of the message                                      *
 ************************************************************************/
function gotDelete(xmlDoc)
{
    console.log("UserInfo: gotDelete: " + new XMLSerializer().serializeToString(xmlDoc));
    location.reload();
}       // gotDelete

/************************************************************************
 *  noDelete                                                            *
 *                                                                      *
 *  This method is called if there is no script to delete the Blog.     *
 ************************************************************************/
function noDelete()
{
    alert("UserInfo: script deleteBlogXml.php not found on server");
}       // noDelete

/************************************************************************
 *  replyBlog                                                           *
 *                                                                      *
 *  This method is called when the user requests to view the reply      *
 *  to a specific queued message.                                       *
 *  This is the onclick method of <button id='Reply...'>                *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Reply...'>                              *
 ************************************************************************/
function replyBlog()
{
    let blid    = this.id.substring(5);
    let message = this.form.elements['message' + blid].value;

    // get the subdistrict information file
    let parms   = {'id'     : blid,
                   'message'    : message};

    HTTP.post("replyBlogXml.php",
              parms,
              gotReply,
              noReply);
    return true;
}   // replyBlog

/************************************************************************
 *  gotReply                                                            *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the act of replying to the blog is received.                        *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc  XML response file describing the sending of the reply   *
 ************************************************************************/
function gotReply(xmlDoc)
{
    console.log("UserInfo: gotReply: " + new XMLSerializer().serializeToString(xmlDoc));
    location.reload();
}       // gotReply

/************************************************************************
 *  noReply                                                             *
 *                                                                      *
 *  This method is called if there is no script to reply to the Blog.   *
 ************************************************************************/
function noReply()
{
    alert("UserInfo: script replyBlogXml.php not found on server");
}       // function noReply

/************************************************************************
 *  amKeyDown                                                           *
 *                                                                      *
 *  Handle key strokes that apply to the entire dialog window.  For     *
 *  example the key combinations Ctrl-S and Alt-A are interpreted to    *
 *  apply the update, as shortcut alternatives to using the mouse to    *
 *  click the "Apply Changes" button.                                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       instance of Event                                       *
 ************************************************************************/
function amKeyDown(e)
{
    let code    = e.keyCode;
    let form    = document.accountForm;

    // take action based upon code
    if (e.ctrlKey)
    {       // ctrl key shortcuts
        if (code == 83)
        {       // letter 'S'
            form.submit();
            return false;   // do not perform standard action
        }       // letter 'S'
    }       // ctrl key shortcuts
    
    if (e.altKey)
    {       // alt key shortcuts
        switch (code)
        {
            case 65:
            {       // letter 'A'
                form.submit();
                break;
            }       // letter 'A'

            case 67:
            {       // letter 'C'
                window.close();
                break;
            }       // letter 'C'

            case 79:
            {       // letter 'O'
                signoff();
                return false;   // suppress default action
            }       // letter 'O'

        }       // switch on key code
    }       // alt key shortcuts

    return true;    // perform default action as well
}       // function amKeyDown

