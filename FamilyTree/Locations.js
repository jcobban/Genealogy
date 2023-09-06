/************************************************************************
 *  Locations.js                                                        *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Locations.php.                                                 *
 *                                                                      *
 *  History:                                                            *
 *      2011/10/31      standardize implementation                      *
 *                      support mouseover help                          *
 *      2012/01/13      change class names                              *
 *      2013/05/18      add name field to permit direct creation of     *
 *                      function locations                              *
 *      2013/05/29      use actMouseOverHelp common function            *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2014/10/12      use method show to display popups               *
 *      2015/07/06      add button to close the dialog                  *
 *      2016/04/05      add button to create new location               *
 *      2017/09/09      renamed to Locations.js                         *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/04/07      ensure that the paging lines can be displayed   *
 *                      within the visible portion of the browser.      *
 *      2023/07/29      migrate to Es2015                               *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {keyDown, args, closeFrame, getOffsetLeft, getOffsetTop, 
        show}
            from "../jscripts6/util.js";
import {change, expAbbr}
            from "../jscripts6/CommonForm.js";
import {evtLocAbbrs}
            from "../jscripts6/locationCommon.js";

window.addEventListener('load', onloadLocations);

/************************************************************************
 *  function onLoadLocations                                            *
 *                                                                      *
 *  Initialize dynamic functionality of page.                           *
 ************************************************************************/
function onloadLocations()
{
    // pass feedback to openet
    let opener                  = window.opener;
    if (opener)
    {                       // opened from another page
        if ('idlr' in args && args.idlr.length > 0 &&
            'feedback' in args && args.feedback.length > 0)
        {                   // feedback field identified
            let element         = opener.document.getElementById(args.feedback);
            if (element)
            {
                element.value   = args.idlr;
            }
            else
                alert("cannot find input field '" + args.feedback +
                        "' in the opener page");
        }                   // feedback field identified
    }                       // opened from another page

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(let i = 0; i < document.forms.length; i++)
    {                       // loop through all forms
        let form                = document.forms[i];

        if (form.name == 'locForm')
        {                   // locForm
            form.addEventListener('submit', validateForm);
            form.addEventListener('reset', resetForm);
        }                   // locForm

        for(let j = 0; j < form.elements.length; j++)
        {                   // loop through all elements
            let element         = form.elements[j];

            // take action depending upon the element name
            let  name;
            if (element.name && element.name.length > 0)
                name            = element.name;
            else
                name            = element.id;

            switch(name)
            {               // act on specific element
                case 'pattern':
                {
                    element.addEventListener('keydown', keyDown);
                    element.abbrTbl     = evtLocAbbrs;
                    element.addEventListener('change', patternChanged);
                    element.focus();
                    break;
                }

                case 'namefld':
                {
                    element.addEventListener('keydown', keyDown);
                    element.abbrTbl     = evtLocAbbrs;
                    element.addEventListener('change', nameChanged);
                    break;
                }

                case 'Search':
                {
                    element.addEventListener('click', search);
                    break;
                }

                case 'Close':
                {
                    element.addEventListener('click', closeDialog);
                    break;
                }

                case 'New':
                {
                    element.addEventListener('click', newLocation);
                    break;
                }

                default:
                {
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', change); // default handler
                    break;
                }
            }               // act on specific element
        }                   // loop through elements in form
    }                       // iterate through all forms

    // add mouseover actions for forward and backward links
    let npprev                  = document.getElementById('topPrev');
    if (npprev)
    {                       // defined
        npprev.addEventListener('mouseover', linkMouseOver);
        npprev.addEventListener('mouseout', linkMouseOut);
    }                       // defined
    let npnext                  = document.getElementById('topNext');
    if (npnext)
    {                       // defined
        npnext.addEventListener('mouseover', linkMouseOver);
        npnext.addEventListener('mouseout', linkMouseOut);
    }                       // defined

}       // function onLoadLocations

/************************************************************************
 *  function validateForm                                               *
 *                                                                      *
 *  Ensure that the data entered by the user has been minimally         *
 *  validated before submitting the form.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of HtmlForm                                *
 *      ev          submit Event                                        *
 ************************************************************************/
function validateForm()
{
    return true;
}       // validateForm

/************************************************************************
 *  function resetForm                                                  *
 *                                                                      *
 *  This method is called when the user requests the form               *
 *  to be reset to default values.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of HtmlForm                                *
 *      ev          reset Event                                         *
 ************************************************************************/
function resetForm()
{
    return true;
}   // resetForm

/************************************************************************
 *  function patternChanged                                             *
 *                                                                      *
 *  Take action when the value of the pattern field changes.  This      *
 *  specifically means that changes have been made and the focus has    *
 *  then left the field.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='pattern'>                    *
 *      ev          changed Event                                       *
 ************************************************************************/
function patternChanged()
{
    let  form   = this.form;

    // expand abbreviations
    if (this.abbrTbl)
        expAbbr(this,
                this.abbrTbl);
    else
    if (this.value == '[')
        this.value  = '[Blank]';

    form.submit();
}       // function patternChanged

/************************************************************************
 *  function nameChanged                                                *
 *                                                                      *
 *  Take action when the value of the name field changes.  This         *
 *  specifically means that changes have been made and the focus has    *
 *  then left the field.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='namefld'>                    *
 *      ev          changed Event                                       *
 ************************************************************************/
function nameChanged()
{
    // expand abbreviations
    if (this.abbrTbl)
        expAbbr(this,
                this.abbrTbl);
    else
    if (this.value == '[')
        this.value      = '[Blank]';

    // open the individual location in a new tab or window
    let name            = encodeURIComponent(this.value);
    window.open("Location.php?name=" + name);
}           // function nameChanged

/************************************************************************
 *  function search                                                     *
 *                                                                      *
 *  Take action to either submit the form or pop up a dialog to create  *
 *  or edit a specific location.                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Search'>                                *
 *      ev          click Event                                         *
 ************************************************************************/
function search()
{
    let form            = this.form;
    let name            = form.namefld.value;
    if (name.length > 0)
    {
        name            = encodeURIComponent(name);
        window.open("Location.php?name=" + name);
    }
    else
        form.submit();
}       // search

/************************************************************************
 *  function closeDialog                                                *
 *                                                                      *
 *  Take action to close the dialog.                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Close'>                                 *
 *      ev          click Event                                         *
 ************************************************************************/
function closeDialog()
{
    closeFrame();
}       // function closeDialog

/************************************************************************
 *  function newLocation                                                *
 *                                                                      *
 *  Create a new location using the pattern or name                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='New'>                                   *
 *      ev          click Event                                         *
 ************************************************************************/
function newLocation(ev)
{
    let form                = this.form;
    if (form.namefld.value.length > 0)
        location            = "Location.php?name=" + form.namefld.value;
    else
    if (form.pattern.value.length > 0)
    {
        let pattern         = form.pattern.value;
        pattern             = pattern.replace(/[.?*^$]/g,'');
        location            = "Location.php?name=" + pattern;
    }
    ev.stopPropagation;
    return false;
}       // newLocation

/************************************************************************
 *  function linkMouseOver                                              *
 *                                                                      *
 *  This function is called if the mouse moves over a forward or        *
 *  backward hyperlink on the invoking page.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        element the mouse moved on to                       *
 *      ev          mouseover Event                                     *
 ************************************************************************/
function linkMouseOver()
{
    let  msgDiv             = document.getElementById('mouse' + this.id);
    if (msgDiv)
    {       // support for dynamic display of messages
        // display the messages balloon in an appropriate place on the page
        let leftOffset      = getOffsetLeft(this);
        if (leftOffset > 500)
            leftOffset      -= 200;
        msgDiv.style.left   = leftOffset + "px";
        msgDiv.style.top    = (getOffsetTop(this) - 30) + 'px';
        show(msgDiv);
        msgDiv.addEventListener('keydown', keyDown);
    }       // support for dynamic display of messages
}       // linkMouseOver

/************************************************************************
 *  function linkMouseOut                                               *
 *                                                                      *
 *  This function is called if the mouse moves off a forward or         *
 *  backward hyperlink on the invoking page.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        element the mouse moved off of                      *
 *      ev          mouseoout Event                                     *
 ************************************************************************/
function linkMouseOut()
{
    let  msgDiv             = document.getElementById('mouse' + this.id);
    if (msgDiv)
    {       // support for dynamic display of messages
        msgDiv.style.display   = 'none';
        msgDiv                 = null;
    }
}       // function linkMouseOut

