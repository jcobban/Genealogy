/************************************************************************
 *  Register.js                                                         *
 *                                                                      *
 *  Implement the dynamic functionality of the script to register a new *
 *  user.                                                               *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/22      created                                         *
 *      2011/02/12      improve separation of javascript and HTML       *
 *                      remove signon and register functions            *
 *      2012/05/28      add mouse-over help balloons                    *
 *      2014/03/27      validate input dynamically                      *
 *      2014/08/01      do not warn for userid with @                   *
 *                      if userid contains @ copy to email              *
 *                      use popupAlert in place of alert                *
 *      2015/08/31      display password quality as entered             *
 *      2018/02/05      changed to support template                     *
 *      2018/02/28      add random password generator                   *
 *                      add score for supplied password                 *
 *      2018/12/21      increase probability of digits and letters      *
 *                      in generated password, and trim password        *
 *      2021/03/29      use common displayDialog for password mismatch  *
 *      2022/05/25      add method ForgotPassword                       *
 *                      generatePassword autofills password and repeat  *
 *      2022/06/10      reduce generated password to 12 characters      *
 *      2022/06/24      support ES2015                                  *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
import {eltMouseOver, eltMouseOut, keyDown, args,
        displayDialog}
            from "../jscripts6/util.js";

window.addEventListener("load", onLoadRegister);

let uidPattern              = /^[^<>@&]{6,63}$/;
let emPattern               = /^[A-Z0-9._%+-]+@[A-Z0-9.-_]+$/i;

/************************************************************************
 *  onLoad                                                              *
 *                                                                      *
 *  Perform initialization after page is loaded                         *
 *                                                                      *
 ************************************************************************/
function onLoadRegister()
{
    for(let i = 0; i < document.forms.length; i++)
    {
        let form    = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {
            let element     = form.elements[j];
            let name        = element.name;
            if(!name || name.length == 0)
                name        = element.id;

            // pop up help balloon if the mouse hovers over a field
            // for more than 2 seconds
            if (element.parentNode.nodeName == 'TD')
            {           // set mouseover on containing cell
                let cell    = element.parentNode;
                cell.addEventListener('mouseover',      eltMouseOver);
                cell.addEventListener('mouseout',	    eltMouseOut);
            }           // set mouseover on containing cell
            else
            {           // set mouseover on input element itself
                element.addEventListener('mouseover',	eltMouseOver);
                element.addEventListener('mouseout',	eltMouseOut);
            }           // set mouseover on input element itself
            element.addEventListener('keydown',          keyDown);
            switch(name)
            {           // act on specific element
                case 'userid':
                {       // user name
                    element.focus();    // put focus in userid field
                    element.addEventListener('change',	checkUserid);
                    break;
                }       // user name

                case 'password':
                {       // password
                    element.addEventListener('keypress',newPasswordKeyPress);
                    element.addEventListener('change',	newPasswordChange);
                    break;
                }       // password

                case 'password2':
                {       // password
                    element.addEventListener('change',	matchPasswords);
                    break;
                }       // password

                case 'generatePassword':
                {
                    element.addEventListener('click',	generatePassword);
                    break;
                }

                case 'email':
                {       // email address
                    element.addEventListener('change',	checkEmail);
                    break;
                }       // email address

                case 'ForgotPassword':
                {
                    element.addEventListener('click',	forgotPassword);
                    if (form.userid.value == '')
                    {
                        element.disabled    = true;
                    }
                    break;
                }

                case 'close':
                {
                    element.addEventListener('click',	finish);
                    break;
                }

            }           // act on specific element
        }               // loop through elements in form
    }                   // loop through all form elements
}       // function onLoad

/************************************************************************
 *  finish                                                              *
 *                                                                      *
 *  Close the current window                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='close'>                                 *
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
 *      this        <input type='text' name='userid'>                   *
 ************************************************************************/
function checkUserid()
{
    if (uidPattern.test(this.value))
        this.className          ='black white leftnc';
    else
        this.className          ='error white left';
    let emailElement            = document.getElementById('email');
    if (this.value.indexOf('@') >= 0 &&
        emailElement &&
        emailElement.value.length == 0)
        emailElement.value      = this.value;
    if (this.value != '')
    {
        let forgotElt           = document.getElementById('ForgotPassword');
        forgotElt.disabled      = false;
    }
}       // function checkUserid

/************************************************************************
 *  checkEmail                                                          *
 *                                                                      *
 *  Validate the e-mail address                                         *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' name='email'>                    *
 ************************************************************************/
function checkEmail()
{
    if (emPattern.test(this.value))
        this.className='black white leftnc';
    else
        this.className='error white leftnc';
}       // function checkEmail

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
    evt         = evt || window.event;
    let code        = evt.which || evt.keyCode;
    let pass        = this.value + String.fromCharCode(code);
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
 ************************************************************************/
function newPasswordChange()
{
    scorePassword(this.value);
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
    let unicode     = [];       // other unicode pages

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
    if (score > 100)
    {
        passwordStrong.style.display    = 'inline';
        passwordGood.style.display      = 'none';
        passwordWeak.style.display      = 'none';
        passwordPoor.style.display      = 'none';
    }
    else
    if (score > 60)
    {
        passwordStrong.style.display    = 'none';
        passwordGood.style.display      = 'inline';
        passwordWeak.style.display      = 'none';
        passwordPoor.style.display      = 'none';
    }
    else
    if (score >= 30)
    {
        passwordStrong.style.display    = 'none';
        passwordGood.style.display      = 'none';
        passwordWeak.style.display      = 'inline';
        passwordPoor.style.display      = 'none';
    }
    else
    {
        passwordStrong.style.display    = 'none';
        passwordGood.style.display      = 'none';
        passwordWeak.style.display      = 'none';
        passwordPoor.style.display      = 'inline';
    }
}       // function scorePassword

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
    let lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    let form            = this.form;

    // change the action so the values are submitted to the
    // password reset function instead of the sign-on function
    form.password.value = '';
    form.action         = 'forgotPassword.php?lang=' + lang;
    form.submit();
}       // function forgotPassword

/************************************************************************
 *  generatePassword                                                    *
 *                                                                      *
 *  Generate a new random password for the user.                        *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='generatePassword'>                  *
 ************************************************************************/
const charset     = "!#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~";
function generatePassword()
{
    let randArray       = new Uint32Array(12);
    window.crypto.getRandomValues(randArray);
    let password        = '';
    for(let i = 0; i < randArray.length; i++)
    {
        let code        = randArray[i] % 157;
        password        += charset.substr(code, 1);
    }
    let outputElement   = document.getElementById('randomPassword');
    outputElement.value = password;
    outputElement       = document.getElementById('password');
    outputElement.value = password;
    outputElement       = document.getElementById('password2');
    outputElement.value = password;
    return false;
}       // function generatePassword

/************************************************************************
 *  matchPasswords                                                      *
 *                                                                      *
 *  Validate the two passwords are the same.                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' name='password2'>                *
 *      ev          Change Event                                        *
 ************************************************************************/
function matchPasswords()
{
    if (this.value != this.form.password.value)
    {
        displayDialog('matchForm', {}, this, null, false);
    }
}       // function matchPasswords
