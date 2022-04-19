/************************************************************************
 *  editEvent.js                                                        *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page editEvent.php, which implements the ability to edit            *
 *  details of an event that is recorded in a Event record              *
 *  representing one record in the table tblER.                         *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/08      use encodeURIComponent on values passed to      *
 *                      updateEvent.php.  This permits passing          *
 *                      characters such as '&' that have a special      *
 *                      meaning in the URI.                             *
 *      2010/08/08      change to use POST for updates                  *
 *                      add support for Order field                     *
 *      2010/08/25      Make first cell in added citation row a         *
 *                      <TH class='left>                                *
 *      2010/08/28      Use getAttribute to get attribute value of XMl  *
 *                      element                                         *
 *                      add functionality for deleting citations        *
 *      2010/10/11      Simplify adding citations                       *
 *      2010/10/15      Use cookies to default to last citation         *
 *                      generalize to handle all events and facts       *
 *      2010/10/17      citation support moved to citTable.js           *
 *      2010/11/14      move prefix and title to name event             *
 *                      redirect submit to updateEvent, so enter does   *
 *                      update                                          *
 *      2011/02/23      handle mousedown in notes textarea              *
 *      2011/02/26      order was not set when invoking updateEvent.php *
 *                      using AJAX                                      *
 *      2011/02/27      change to initialization for citations          *
 *                      clean up element handling                       *
 *      2011/03/03      support keyboard shortcuts:                     *
 *                      ctrl-S and alt-U for update event               *
 *                      alt-A for add citation                          *
 *      2011/07/29      changed implementation for getting updated      *
 *                      values from the invoking form.  They are now    *
 *                      explicitly passed as parameters to              *
 *                      editEvent.php, instead of being extracted by    *
 *                      this routine.  This removes                     *
 *                      an interdependency between this page and the    *
 *                      invoking page.                                  *
 *      2011/08/08      perform standard expansion of abbreviations in  *
 *                      location of event                               *
 *      2011/08/21      enable add citation button for events in        *
 *                      Person add Temple vs. Live kind row to          *
 *                      menu for LDS Baptism and LDS Confirmation       *
 *                      events when selected in generic event case.     *
 *      2011/10/01      provide database lookup assist on setting       *
 *                      location name                                   *
 *      2011/11/19      add support for deleting alternate names        *
 *      2011/12/21      refresh the dialog when the user selects a new  *
 *                      event type                                      *
 *                      support marriage events                         *
 *      2012/01/07      set focus in "etype" selection for generic event*
 *      2012/01/08      can only change location of page from same host *
 *      2012/01/13      change class names                              *
 *                      invoke eventFeedback method of invoking page to *
 *                      report changes instead of directly updating the *
 *                      page                                            *
 *                      add support for no children fact in family      *
 *      2012/02/25      use tinyMCE to edit extended text notes         *
 *      2013/01/11      suppress "not updated" warning message          *
 *                      ensure all alerts start with file name          *
 *      2013/03/11      changeLocation renamed to locationChanged       *
 *      2013/04/02      add support for multiple citation tables in a   *
 *                      page                                            *
 *                      add support for citations for alternate names   *
 *      2013/05/15      add ability to create new source for citation   *
 *                      activate popup help for the fields in an        *
 *                      citation that is being defined.                 *
 *      2013/05/20      correct eventFeedback parameters for checkboxes *
 *                      The value of a checkbox is that of its value    *
 *                      attribute only if the checkbox is checked,      *
 *                      otherwise return 0.                             *
 *      2013/05/29      use actMouseOverHelp common function            *
 *      2013/07/04      permit changing event type for events recorded  *
 *                      in an instance of Event without creating        *
 *                      a new instance.                                 *
 *      2013/08/16      resize dialog after initializing new citation   *
 *                      selection                                       *
 *      2013/08/25      add clear button for note textarea              *
 *      2014/02/12      replace tables with CSS layout                  *
 *      2014/02/19      rename citation feedback routine to             *
 *                      updateCitation to make its purpose clearer      *
 *                      rename validateForm to suppressSubmit           *
 *                      to make its purpose clearer                     *
 *      2014/03/06      increase size of edit citation dialog window    *
 *      2014/03/10      ability to edit cause of death, and identify    *
 *                      source citations for the cause of death, added  *
 *                      to the edit dialogue for normal death event     *
 *      2014/03/21      ignore fieldset elements in form                *
 *      2014/04/08      LegacyAltName renamed to LegacyName             *
 *                      add button to edit alternate name               *
 *                      pass debug flag to all subordinate scripts      *
 *      2014/04/14      Display default citation while waiting for      *
 *                      database server to respond to request for list  *
 *                      of sources                                      *
 *                      record name of source in cookie                 *
 *      2014/04/24      pass citation fields to update script           *
 *      2014/04/26      remove sizeToFit                                *
 *      2014/04/30      response from addCitXml.php changed             *
 *                      do not create new row if addCitXml.php did      *
 *                      not create a new citation.  This handles the    *
 *                      case where Citation creation fails              *
 *                      because the new citation is a duplicate         *
 *      2014/09/13      use global debug flag from util.js              *
 *      2014/10/01      request user to confirm delete of citation      *
 *      2014/10/03      add button for managing pictures associated     *
 *                      with an event                                   *
 *      2014/11/10      pass IDET to addCitXml.php to support events    *
 *                      moved to tblER                                  *
 *      2014/11/19      add support for distinctive Occupation field    *
 *      2014/11/20      if debug set issue alert before invoking        *
 *                      addCitXml.php and on return from addCitXml.php  *
 *      2014/12/26      add rownum feedback to editIndivid.js           *
 *      2015/01/04      pass name of temple as well as IDTR to          *
 *                      eventFeedback                                   *
 *      2015/01/15      do not refresh input form when event type       *
 *                      changes if a standard event form is already     *
 *                      displayed, just change the event type           *
 *      2014/01/18      dates were not validated                        *
 *                      permit dialog to be opened in an iframe of      *
 *                      a window rather than as a separate window       *
 *                      hide iframe on close                            *
 *      2014/02/01      invalid parms to editSource.php on add Source   *
 *      2014/02/04      direct feedback to proper parent instance       *
 *                      when invoked in an <iframe>                     *
 *      2015/02/10      use closeFrame                                  *
 *      2015/03/08      simplify switch for initializing elements       *
 *                      open editPictures using openFrame               *
 *      2015/03/14      include Close button if errors                  *
 *      2015/03/31      if the user has not selected an event type,     *
 *                      close the window on update                      *
 *      2015/04/09      naming a function close caused recursion loop   *
 *                      renamed to closeWithoutUpdating                 *
 *      2015/04/11      apply changes on enter key in input text fields *
 *      2015/05/27      use absolute URLs for AJAX                      *
 *      2015/05/29      Description/Occupation field changed to use     *
 *                      rich-text editor                                *
 *      2015/06/01      open all child dialogs in half window           *
 *      2015/06/02      use main style for TinyMCE editor               *
 *                      make tab out of date field go to description    *
 *                      feedback content of occupation & description    *
 *                      rich text editors                               *
 *      2016/05/31      use common function dateChanged                 *
 *      2016/07/27      handle global debug flag consistently           *
 *      2016/10/27      handle deferred event creation for add Citation *
 *      2016/11/29      always update IDIME if required after event     *
 *                      updated                                         *
 *      2017/08/29      form.ider undefined failure even though I can   *
 *                      SEE the field in the form                       *
 *      2018/03/24      add button to control whether textareas are     *
 *                      displayed as rich text or raw text              *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/05/19      call element.click to trigger button click      *
 *      2019/06/29      first parameter of displayDialog removed        *
 *      2019/08/01      support tinyMCE 5.0.3                           *
 *      2020/02/17      hide right column                               *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *                      use addEventListener                            *
 *      2021/03/07      use beep to signal button click ignored         *
 *      2021/03/15      use addCitJSON.php in place of addCitXml.php    *
 *      2022/02/01      use addEventListener and removeEventListener    *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  dialogDiv                                                           *
 *                                                                      *
 *  global variable to hold a reference to a displayed dialog           *
 ************************************************************************/
var dialogDiv           = null;

/************************************************************************
 *  Specify the function to get control once the page is loaded.        *
 ************************************************************************/
window.onload           = loadEdit;

/************************************************************************
 *  childFrameClass                                                     *
 *                                                                      *
 *  If this dialog is opened in a half window then any child dialogs    *
 *  are opened in the other half of the window.                         *
 ************************************************************************/
var childFrameClass     = 'right';

/************************************************************************
 *  function loadEdit                                                   *
 *                                                                      *
 *  Initialize dynamic functionality of elements.                       *
 *                                                                      *
 *  Input:                                                              *
 *      this            window                                          *
 ************************************************************************/
function loadEdit()
{
    let namePattern         = /^([a-zA-Z_]+)(\d+)$/;

    // determine in which half of the window child frames are opened
    if (window.frameElement)
    {                   // dialog opened in half frame
        childFrameClass         = window.frameElement.className;
        if (childFrameClass == 'left')
            childFrameClass     = 'right';
        else
            childFrameClass     = 'left';
    }                   // dialog opened in half frame

    // handle keystrokes anywhere in body of page
    document.body.addEventListener('keydown',eeKeyDown);

    // activate functionality of various input fields
    let focusSet                = false;
    for (var fi = 0; fi < document.forms.length; fi++)
    {                   // loop through all forms in page
        let form                = document.forms[fi];
        form.updateCitation     = updateCitation;
        form.addEventListener('click', stopProp);

        // set action methods for form
        if (form.name == 'evtForm')
        {               // main form
            form.onsubmit       = suppressSubmit;
            form.onreset        = resetForm;
            form.nameFeedback   = nameFeedback;
            form.sourceCreated  = sourceCreated;
        }               // main form

        let formElts            = form.elements;
        for (var i = 0; i < formElts.length; ++i)
        {               // loop through all elements in form
            let element = formElts[i];
            if (element.nodeName.toLowerCase() == 'fieldset')
                continue;

            let name;
            if (element.name && element.name.length > 0)
                name    = element.name;
            else
                name    = element.id;
            let matches = namePattern.exec(name);
            let id  = '';
            if (matches)
            {           // name matched the pattern
                name    = matches[1];
                id  = matches[2];
            }           // name matched the pattern

            // take action specific to specific elements
            switch(name.toLowerCase())
            {           // action depends upon element name
                case 'etype':
                {       // event type input field
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('change', changeEtype);
                    if (!focusSet)
                    {       // need focus in some field
                        element.focus();    // set focus
                        focusSet        = true;
                    }       // need focus in some field
                    break;
                }       // event type input field

                case 'idet':
                {
                    let idet                = element.value;
                    let etypeSelect         = form.etype;
                    if (etypeSelect)
                        etypeSelect.value   = idet;
                    else
                        console.log("editEvent.js: loadEdit: 274 " +
                            " idet=" + idet + " etype field not found");
                    break;
                }

                case 'date':
                {       // event date 
                    element.addEventListener('keydown',inputKeyDown);
                    element.abbrTbl     = MonthAbbrs;
                    element.checkfunc   = checkDate;
                    element.addEventListener('change', dateChanged);
                    element.onblur      = gotoDescription;
                    element.focus();    // set focus
                    focusSet            = true;
                    break;
                }       // event date

                case 'occupation':
                {       // occupation
                    element.addEventListener('keydown',inputKeyDown);
                    element.checkfunc   = checkOccupation;
                    element.addEventListener('change', change);
                    element.abbrTbl     = OccAbbrs;
                    break;
                }       // occupation

                case 'location':
                {       // event location
                    element.addEventListener('focus', focusLocation);
                    element.addEventListener('keydown',inputKeyDown);
                    element.abbrTbl     = evtLocAbbrs;
                    element.addEventListener('change', locationChanged);
                    break;
                }       // event location

                case 'temple':
                {       // event temple
                    element.addEventListener('focus', focusLocation);
                    element.addEventListener('keydown',inputKeyDown);
                    element.abbrTbl     = evtLocAbbrs;
                    element.addEventListener('change', templeChanged);
                    break;
                }       // event temple

                case 'templeready':
                case 'cremated':
                case 'title':
                {       // templeReady checkbox
                    element.onblur  = gotoNotes;
                    break;
                }       // templeReady checkbox

                case 'deathcause':
                {       // cause of death
                    element.addEventListener('keydown',inputKeyDown);
                    element.abbrTbl     = CauseAbbrs;
                    element.checkfunc   = checkText;
                    element.addEventListener('change', change);
                    break;
                }       // cause of death

                case 'updevent':
                {       // <button id='updEvent'>
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',updateEvent);
                    element.addEventListener('change', change);   // default handler
                    break;
                }       // <button id='updEvent'>

                case 'raw':
                {       // <button id='raw'>
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',showRaw);
                    break;
                }       // <button id='raw'>

                case 'clear':
                {       // <button id='Clear'>
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',clearNotes);
                    break;
                }       // <button id='Clear'>

                case 'submit':
                {       // <button id='Submit' type='submit'>
                    element.addEventListener('keydown',keyDown);
                    form.onsubmit       = proceedWithSubmit;
                    break;
                }       // <button id='Submit' type='submit'>

                case 'close':
                {       // <button id='close'>
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',closeWithoutUpdating);
                    break;
                }       // <button id='close'>


                case 'note':
                {       // textual notes on event
                    element.addEventListener('change', change);   // default handler
                    if (!focusSet)
                    {       // need focus in some field
                        element.focus();    // set focus
                        focusSet    = true;
                    }       // need focus in some field
                    break;
                }       // textual notes on event

                case 'addcitation':
                case 'addcitationdeathcause':
                {       // add citation to primary fact
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',addCitation);
                    break;
                }       // add citation to primary fact

                case 'pictures':
                {       // <button id='Pictures'>
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',editPictures);
                    break;
                }       // <button id='Pictures'>

                case 'editname':
                {       // edit alternate name button
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',editName);
                    break;
                }       // edit alternate name button

                case 'delname':
                {       // delete alternate name button
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',delName);
                    break;
                }       // delete alternate name button

                case 'editcitation':
                {       // edit alternate name citation button
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',editCitation);
                    break;
                }       // edit alternate name citation button

                case 'delcitation':
                {       // delete alternate name citation button
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',deleteCitation);
                    break;
                }       // delete alternate name citation button

                case 'addcitation':
                {       // add alternate name citation button
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('click',addAltCitation);
                    break;
                }       // add alternate name citation button

                default:
                {
                    element.addEventListener('keydown',keyDown);
                    element.addEventListener('change', change);   // default handler
                    break;
                }       // default

            }           // action depends upon element name
        }               // loop through all elements in the form
    }                   // loop through all forms in page

    hideRightColumn();
}       // function loadEdit

/************************************************************************
 *  function inputKeyDown                                               *
 *                                                                      *
 *  Handle key strokes in text input fields.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    instance of <input type='text'>                         *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function inputKeyDown(ev)
{
    if (!ev)
    {                   // browser is not W3C compliant
        ev  =  window.event;    // IE
    }                   // browser is not W3C compliant

    let code    = ev.keyCode;       // numeric keystroke

    let form    = this.form;        // form containing input field

    // hide the help balloon on any keystroke
    if (helpDiv)
    {                   // helpDiv currently displayed
        helpDiv.style.display   = 'none';
        helpDiv         = null; // no longer displayed
    }                   // helpDiv currently displayed
    clearTimeout(helpDelayTimer);   // clear pending help display
    helpDelayTimer      = null;

    // take action based upon code
    switch (code)
    {
        case KEY_ENTER: // Enter
        {
            document.getElementById('updEvent').click();
            return true;        // default action submit
        }               // Enter

        case KEY_F1:    // F1
        {
            displayHelp(this);      // display help page
            return false;       // suppress default action
        }               // F1
    }       // switch on key code

    return;
}       // function inputKeyDown

/************************************************************************
 *  function changeEtype                                                *
 *                                                                      *
 *  Take action when the user selects an item in the name='etype' list  *
 *  of event types.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='etype'>                               *
 ************************************************************************/
function changeEtype()
{
    let form            = this.form;
    let ider            = 0;
    if (form.ider)
        ider            = form.ider.value - 0;      // IDER

    // if an existing event is being displayed, no need to expand form
    if (ider > 0 && form.date)
        return;

    let idet            = this.value - 0;           // IDET
    let citType         = form.type.value - 0;      // citation type

    // construct the URL to refresh the page
    let url             = 'editEvent.php?';

    // identify the object the event is associated with
    if (form.idir)
        url             += 'idir=' + form.idir.value;
    else
    if (form.idmr)
        url             += 'idmr=' + form.idmr.value;
    else
    if (form.idnx)
        url             += 'idnx=' + form.idnx.value;
    else
    if (form.idcr)
        url             += 'idcr=' + form.idcr.value;
    else
    if (form.idtd)
        url             += 'idtd=' + form.idtd.value;

    // add feedback parameter
    if (form.rownum)
        url             += '&rownum=' + form.rownum.value;

    if (debug.toLowerCase() == 'y')
        url             += "&debug=" + debug;

    // add citation type and IDET value to URL
    if (idet > 999)
    {       // events defined in Person or Family
        citType = Math.floor(idet/1000);    // citation citType
        url             += '&type=' + citType;
    }       // events defined in Person or Family
    else
    {       // events defined in other records
        if (citType != 30 && citType != 31)
        {   // event is not in instance of Event
            if (form.idir)
                url     += '&ider=0&type=30&idet=' + idet;
            else
                url     += '&ider=0&type=31&idet=' + idet;
        }   // event is not in instance of Event
        else
            url         += '&ider=0&type=' + citType + '&idet=' + idet;
    }       // events defined in other records

    // reinvoke editEvent.php with the parameters to create the dialog
    // for the new event
    location    = url;
}       // function changeEtype

/************************************************************************
 *  function changeOccupation                                           *
 *                                                                      *
 *  Take action when the user changes the value of the Occupation       *
 *  field.                                                              *
 *  This is called for all instances of tinyMCE.Editor, but only        *
 *  Occupation requires transformation of the value.                    *
 *                                                                      *
 *  Input:                                                              *
 *      editor      instance of tinyMCE.Editor                          *
 ************************************************************************/
function changeOccupation(editor) 
{
    if (editor.id != 'occupation')
        return;
    let element             = document.getElementById(editor.id);
    let abbrTbl             = OccAbbrs;
    let html                = editor.getContent();
    let tagArray            = html.split('<');
    let text                = "";
    let result              = "";

    for(var it = 0; it < tagArray.length; it++)
    {                   // loop through all tags
        if (it > 0)
        {
            let temp        = tagArray[it].split('>', 2);
            result  += '<' + temp[0] + '>';
            if (temp.length == 2)
                text        = temp[1];
            else
                text        = '';
        }
        else
            text            = tagArray[0];
        let words           = text.split(" ");

        for(var iw = 0; iw < words.length; iw++)
        {               // loop through all words
            let key         = words[iw];
            // separate words with a space
            if (iw > 0)
                result  += " ";
            if (key.length == 0)
                continue;
            let firstChar   = key.charAt(0);
            if ("abcdefghijklmnopqrstuvwxyz".indexOf(firstChar) >= 0)
            {           // fold initial lower case letter to upper case
                key = key.charAt(0).toUpperCase() + key.substring(1);
                firstChar   = '';
            }           // fold initial lower case letter to upper case
            else
            // if word starts with punctuation do not include it
            if (key.length > 1 && "['\"".indexOf(firstChar) >= 0)
            {           // key starts with punctuation mark
                // do not include punctuation mark in key value
                key     = key.substring(1);
            }           // key starts with punctuation mark
            else
            {           // key does not start with special char
                firstChar   = "";
            }           // key does not start with special char

            // if word ends with a punctuation mark, do not include it
            let lastChar    = key.charAt(key.length - 1);
            if (key.substring(key.length - 2) == "'s")
            {           // possessive
                key         = key.substring(0, key.length - 2);
                lastChar    = "'s";
            }           // possessive
            else
            if (",;:]".indexOf(lastChar) >= 0)
            {           // key ends with punctuation mark
                // do not include punctuation mark in key value
                key         = key.substring(0, key.length - 1);
            }           // key ends with punctuation mark
            else
            {           // key does not end with special char
                lastChar    = "";
            }           // key does not end with special char

            // do a table lookup in the table of abbreviations
            let exp = abbrTbl[key];
            if (exp)
            {           // substitute word from abbreviation table
                result      += firstChar + exp + lastChar;
            }           // substitute word from abbreviation table
            else
            {           // substitute folded word
                result      += firstChar + key + lastChar;
            }           // substitute folded word
        }               // loop through all words
    }                   // loop through all tags
    editor.setContent(result);
    document.getElementById('location').focus();
}       // function changeOccupation

/************************************************************************
 *  function focusLocation                                              *
 *                                                                      *
 *  Because tinyMCE 5.0.3 is not calling the changeOccupation handler   *
 *  this routine is called when focus lands in another field.           *
 *                                                                      *
 *  Input:                                                              *
 *      this    <input>                                                 *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function focusLocation(ev)
{
    let mceElt          = tinyMCE.get('occupation');
    if (mceElt)
        changeOccupation(mceElt);
}       // function focusLocation

/************************************************************************
 *  function gotoDescription                                            *
 *                                                                      *
 *  Take action when the user leaves the date field.                    *
 *  This is required because the next field after the 'date' field is   *
 *  now a TinyMCE rich text editor, so tabbing out of the 'date' field  *
 *  moves the focus to the first control icon, not the text editor.     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input name='date'>                                 *
 ************************************************************************/
function gotoDescription()
{
    let form        = this.form;
    if (form.occupation)
        tinyMCE.get('occupation').focus();
    else
    if (form.description)
        tinyMCE.get('description').focus();
}       // function gotoDescription

/************************************************************************
 *  function gotoNotes                                                  *
 *                                                                      *
 *  Take action when the user leaves the date field.                    *
 *  This is required because the next field after the 'date' field is   *
 *  now a TinyMCE rich text editor, so tabbing out of the 'date' field  *
 *  moves the focus to the first control icon, not the text editor.     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input name='date'>                                 *
 ************************************************************************/
function gotoNotes()
{
    let form        = this.form;
    if (form.note)
        tinyMCE.get('note').focus();
}       // function gotoNotes

/************************************************************************
 *  function newEvent                                                   *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  an instance of Event created to satisfy a change to the event       *
 *  type is retrieved from the database.                                *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc      XML document representing the new event             *
 ************************************************************************/
function newEvent(xmlDoc)
{
    if (xmlDoc === undefined)
    {
        alert("editEvent.js: newEvent: xmlDoc is undefined!");
        return;
    }
    let form        = document.evtForm;
    let addCitBtn   = document.getElementById('AddCitation');
    let addCitDcBtn = document.getElementById('addCitationDeathCause')

    let root        = xmlDoc.documentElement;
    if (root && root.nodeName == 'event')
    {
        for (var i = 0; i < root.childNodes.length; i++)
        {                       // loop through all children
            let elt                 = root.childNodes[i];
            if (elt.nodeType == 1)
            {                   // tag
                switch(elt.nodeName)
                {
                    case 'parms':
                    {
                        // no action
                        break;
                    }           // parms

                    case 'event':
                    {           // returned event object
                        let iders   = elt.getElementsByTagName('ider');
                        let ider    = iders[0].textContent;
                        let form    = document.evtForm;
                        form.type.value     = 30;   // individual event
                        form.citType.value  = 30;   // individual event
                        form.ider.value     = ider;
                        form.idime.value    = ider;
                        addCitBtn.disabled  = false;
                        if (addCitDcBtn)
                            addCitDcBtn.disabled= false;
                        break;
                    }           // returned event object

                    case 'msg':
                    {
                        alert('editEvent.js: newEvent: message ' +
                              new XMLSerializer().serializeToString(elt));
                        break;
                    }           // error message

                    default:
                    {
                        alert('editEvent.js: newEvent: unexpected tag <' +
                              elt.nodeName + '>');
                        break;
                    }           // unexpected

                }               // switch on element name
            }                   // tag
        }                       // loop through all children
    }                           // valid response
    else
    {                           // invalid response
        if (xmlDoc)
        {
            if (root.nodeName)
                alert('editEvent.js: newEvent: ' + new XMLSerializer().serializeToString(root));
            else
                alert('editEvent.js: newEvent: ' + root);
        }
        else
            alert('editEvent.js: newEvent: null parameter');
    }                           // invalid response
}       // function newEvent

/************************************************************************
 *  function suppressSubmit                                             *
 *                                                                      *
 *  This function ensures that the form cannot be submitted in the      *
 *  normal way, for example by pressing the Enter key.                  *
 ************************************************************************/
function suppressSubmit()
{
    return false;
}       // function suppressSubmit

/************************************************************************
 *  function proceedWithSubmit                                          *
 *                                                                      *
 *  For testing do not intercept submit.                                *
 ************************************************************************/
function proceedWithSubmit()
{
    return true;
}       // function proceedWithSubmit


/************************************************************************
 *  function resetForm                                                  *
 *                                                                      *
 *  This method is called when the user requests the form               *
 *  to be reset to default values.                                      *
 ************************************************************************/
function resetForm()
{
    return true;
}   // function resetForm

/************************************************************************
 *  function updateEvent                                                *
 *                                                                      *
 *  This method is called when the user requests to update              *
 *  an event of an individual.                                          *
 *                                                                      *
 *  Input:                                                              *
 *      this    the <button id='updateEvent'> element                   *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
var parmStr;
function updateEvent(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    if (deferSubmit)
    {
        deferSubmit     = false;
        return  false;
    }

    let form            = document.evtForm;
    let parms           = {};
    parms.etype         = 0;
    let formElts        = form.elements;
    let idime           = 0;
    for (var i = 0; i < formElts.length; ++i)
    {       // loop through elements
        let elt         = formElts[i];
        switch(elt.name)
        {
            case 'etype':
            {
                parms.etype         = form.etype.value;
                if (parms.etype > 1000)
                    parms.type      = Math.floor(parms.etype / 1000);
                else
                    parms.type      = form.type.value;  // individual event
                break;
            }

            case 'kind':
            {
                if (elt.checked)
                    parms.kind      = elt.value;
                break;
            }

            case 'idir':
            case 'ider':
            case 'idcr':
            case 'idmr':
            case 'idime':
            case 'date':
            case 'prefix':
            case 'title':
            case 'type':
            case 'location':
            case 'order':
            case 'givenName':
            case 'surname':
            case 'newAltGivenName':
            case 'newAltSurname':
            case 'deathCause':
            case 'citType':
            case 'AddCitation':
            {
                parms[elt.name]     = elt.value;
                break;
            }   // supported fields

            case 'temple':
            {
                parms.temple        = elt.value;
                let option          = elt.options[elt.selectedIndex];
                parms.location      = option.text.trim();
                break;
            }   // supported fields

            case 'templeReady':
            case 'notmarried':
            case 'nochildren':
            {       // check boxes
                if (elt.checked)
                    parms[elt.name] = elt.value;
                else
                    parms[elt.name] = 0;
                break;
            }       // check boxes

            case 'description':
            case 'occupation':
            case 'note':
            {       // fields using tinyMCE for extended notes
                let text                = elt.value;
                if (typeof tinyMCE !== 'undefined')
                {
                    let mceElt          = tinyMCE.get(elt.name);
                    if (mceElt)
                    {
                        text            = mceElt.getContent();
                        let results     = /<p>(.*)<\/p>/.exec(text);
                        if (results)
                        {
                            text        = results[1];
                            mceElt.setContent(text);
                        }
                        elt.value       = text;
                    }
                }
                changeElt(elt);
                text                    = elt.value;
                parms[elt.name]         = text;
                break;
            }       // extended notes

            default:
            {
                // pass on fields in a citation
                if (elt.name.substring(0,6) == 'Source' ||
                    elt.name.substring(0,4) == 'IDSR' ||
                    elt.name.substring(0,4) == 'Page')
                    parms[elt.name] = elt.value;
                break;
            }
        }   // switch on elt.name
    }       // loop through elements

    // if there are no parms then there was an error setting up the
    // event edit, so just close the window
    if (Object.keys(parms).length == 0)
    {
        closeFrame();
        return;
    }

    if ((parms.type == 30 || parms.type == 31) && parms.etype <= 1)
    {
        console.log("editEvent.js: updateEvent: " +
                " cannot create unspecified event:" +
                " parms= " + JSON.stringify(parms));
        beep();
        return;
    }
    // pass on debug flag
    if (args['debug'])
        parms['debug']  = args['debug'];
    let parmstr='{';
    for(var k in parms)
        parmstr += k + ' : ' + parms[k] + ',';
    if (debug.toLowerCase() == 'y')
        alert("editEvent.js: updateEvent: " +
              "invoke '/FamilyTree/updateEvent.php', " +
              parmstr);

    // invoke script to update Event and return XML result
    HTTP.post('/FamilyTree/updateEvent.php',
              parms,
              gotEvent,
              noEvent);
}       // function updateEvent

/************************************************************************
 *  function closeWithoutUpdating                                       *
 *                                                                      *
 *  This method is called when the user requests to close the           *
 *  window without updating the event                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        the <button id='close'> element                     *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function closeWithoutUpdating(ev)
{
    closeFrame();
}       // function closeWithoutUpdating

/************************************************************************
 *  function gotEvent                                                   *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  an updated event is retrieved from the database.                    *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      XML document containing event                       *
 ************************************************************************/
function gotEvent(xmlDoc)
{
    if (xmlDoc === undefined || xmlDoc === null)
    {
        alert("editEvent.js: gotEvent: xmlDoc is undefined! " + parmStr);
        return;
    }
    let form        = document.evtForm;

    let root        = xmlDoc.documentElement;

    if (root && root.nodeName == 'event')
    {                       // expected response
        let serializer      = new XMLSerializer;
        if (debug.toLowerCase() == 'y')
            alert("editEvent.js: gotEvent: " +
                        serializer.serializeToString(root));
        let msgs    = root.getElementsByTagName("msg");
        if (msgs.length > 0)
        {                   // have messages in reply
            let msgsText    = '';
            let connector   = '';
            for(var j = 0; j < msgs.length; j++)
            {               // accumulate messages
                msgsText    += connector + msgs[j].textContent;
                connector   = ', ';
            }               // accumulate messages
            alert("editEvent.js: gotEvent: msg=" + msgsText);
        }                   // have messages in reply

        let cmds    = root.getElementsByTagName("cmd");
        if (cmds.length == 0)
        {                   // no update
            //alert("editEvent.js: gotEvent: database not updated" +
            //  new XMLSerializer().serializeToString(root));
        }                   // no update
        //else
        //    alert("editEvent.js: gotEvent: database updated" +
        //  new XMLSerializer().serializeToString(root));

        // ensure that ider and idime fields are initialized
        let idime       = document.getElementById('idime');
        let ider        = document.getElementById('ider');
        for(var i = 0; i < cmds.length; i++)
        {               // loop through <cmd> tags
            let result  = cmds[i].getAttribute('result');
            if (result && result.length > 0)
            {           // result attribute value present
                if (ider !== null && ider.value == 0)
                    ider.value      = result;
                if (idime !== null && idime.value == 0)
                    idime.value     = result;
                break;
            }           // result attribute value present
        }               // loop through <cmd> tags

        // if the request for update was initiated because some
        // function needed the IDER value then perform that function
        if (pendingElement)
        {                   // action to perform before closing window
            let button          = pendingElement;
            pendingElement      = null;
            button.disabled     = false;
            button.click();
            return;
        }                   // action to perform before closing window

        // notify the window that invoked this dialog
        let opener
        if (window.frameElement && window.frameElement.opener)
            opener  = window.frameElement.opener;
        else
            opener  = window.opener;
        if (opener === null)
            opener  = window.parent;
        if (opener)
        {
          try
          {                 // invoked from an existing window
            // reflect changes made to the main fields of the event
            // back to the opener's form
            for (var fi = 0; fi < opener.document.forms.length; fi++)
            {               // loop through forms in invoking page
                let srcForm     = opener.document.forms[fi];
                if (srcForm.eventFeedback)
                {           // feedback method defined on the form
                    let parms   = {};
                    for (var ei = 0; ei < form.elements.length; ei++)
                    {       // copy select element values to parms
                        let element                 = form.elements[ei];
                        if (element.type == 'checkbox' &&
                            !(element.checked))
                            parms[element.name]     = 0;
                        else
                        if (element.name == 'occupation' ||
                            element.name == 'description')
                        {   // may be a tinyMCE editor
                            let text                = element.value;
                            if (typeof tinyMCE !== 'undefined')
                            {
                                let mceElt          = tinyMCE.get(element.name);
                                if (mceElt)
                                {
                                    text            = mceElt.getContent();
                                    let results     = /<p>(.*)<\/p>/.exec(text);
                                    if (results)
                                        text        = results[1];
                                }
                            }
                            parms.description       = text;
                        }   // may be a tinyMCE editor
                        else    
                        if (element.name == 'temple')
                        {   // selection list
                            parms.temple    = element.value;
                            let index       = element.selectedIndex;
                            let option      = element.options[index];
                            parms.location  = option.text.trim();
                        }   // selection list
                        else    
                        if (element.name.length > 0)
                            parms[element.name] = element.value;
                    }       // copy element values to parms
                    srcForm.eventFeedback(parms);
                }           // feedback method defined on the form
            }               // loop through forms in invoking page

            closeFrame();
          } catch(ex) { alert("editEvent.js: 1145 Exception " + ex);}
        }                   // invoked from an existing window
        else
        {
            alert("EditEvent.js: gotEvent: " +
                  "parent window has already been closed")
        }
    }                       // expected response
    else
    {                       // error
        let msg = "Error: ";
        if (root)
        {
            msg += new XMLSerializer().serializeToString(root);
        }
        else
            msg += xmlDoc;
        alert ("EditEvent.js: gotEvent: " + msg);
    }                       // error
}       // function gotEvent

/************************************************************************
 *  function noEvent                                                    *
 *                                                                      *
 *  This method is called if there is no event response from the        *
 *  server.                                                             *
 ************************************************************************/
function noEvent()
{
    alert("editEvent.js: noEvent: 'updateEvent.php' script not found");
}       // function noEvent

/************************************************************************
 *  function editName                                                   *
 *                                                                      *
 *  This function is called when the user clicks on a "Details"         *
 *  button associated with an alternate name.                           *
 *                                                                      *
 *  Input:                                                              *
 *      this    instance of <button id='editName...'>                   *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function editName(ev)
{
    let idnx        = this.id.substr(8);
    let formName    = this.form.name;

    let url = "editName.php?idnx=" + idnx + "&form=" + formName;
    if (debug.toLowerCase() == 'y')
        url += "&debug=" + debug;

    // open a citation in the other half of the window
    openFrame("name",
              url,
              childFrameClass);
    return false;       // do not submit form
}       // function editName

/************************************************************************
 *  function delName                                                    *
 *                                                                      *
 *  This function is called when the user clicks on a "Delete Name"     *
 *  button.                                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this    instance of <button id='delName...'>                    *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function delName(ev)
{
    let idnx    = this.id.substr(7);

    // invoke script to update Event and return XML result
    let parms       = {"idnx"   : idnx};
    if (debug.toLowerCase() == 'y')
        parms["debug"]  = debug;

    HTTP.post('/FamilyTree/deleteNameXml.php',
              parms,
              gotDelName,
              noDelName);
    return false;       // do not submit form
}       // function delName

/************************************************************************
 *  function gotDelName                                                 *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the result of a request to delete an alternate name record          *
 *  is retrieved from the database server.                              *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      XML document containing results                     *
 ************************************************************************/
function gotDelName(xmlDoc)
{
    if (xmlDoc === undefined)
    {
        alert("editEvent.js: gotDelName: xmlDoc is undefined!");
        return;
    }
    let form        = document.evtForm;

    let root    = xmlDoc.documentElement;
    if (root && root.nodeName == 'deleted')
    {       // expected root node name
        if (debug.toLowerCase() == 'y')
            alert('editEvent.js: gotDelName: ' + new XMLSerializer().serializeToString(root));
        let msgs    = root.getElementsByTagName("msg");
        if (msgs.length > 0)
        {       // have messages in reply
            let msgtext = "editEvent.js: gotDelName: msg=";
            for(var j = 0; j < msgs.length; j++)
                msgtext     += msgs[j].textContent + ',';
            alert(msgtext);
        }       // have messages in reply
        location    = location; // refresh
    }       // expected root node name
    else
        alert("editEvent.js: gotDelName: unexpected root node <" + 
                root.nodeName + ">");
}       // function gotDelName

/************************************************************************
 *  function noDelName                                                  *
 *                                                                      *
 *  This method is called if there is no response to delete alternate   *
 *  name.                                                               *
 ************************************************************************/
function noDelName()
{
    alert("editEvent.js: noDelName: 'deleteNameXml.php' script not found on server");
}       // function noDelName

/************************************************************************
 *  function eeKeyDown                                                  *
 *                                                                      *
 *  Handle key strokes that apply to the dialog as a whole.  For        *
 *  example the key combinations Ctrl-S and Alt-U are interpreted to    *
 *  apply the update, as shortcut alternatives to using the mouse to    *
 *  click the "Update Event" button.                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    instance of <input>                                     *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function eeKeyDown(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    let code    = ev.keyCode;

    // take action based upon code
    switch (code)
    {
        case LTR_A:
        {       // letter 'A'
            if (ev.altKey)
            {       // alt-A
                let button  = document.getElementById('addCitation');
                button.click();
                return false;
            }       // alt-A
            break;
        }       // letter 'A'

        case LTR_C:
        {       // letter 'C'
            if (ev.altKey)
            {       // alt-C
                let button  = document.getElementById('Clear');
                button.click();
                return false;
            }       // alt-C
            break;
        }       // letter 'A'

        case LTR_S:
        {       // letter 'S'
            if (ev.ctrlKey)
            {       // ctrl-S
                document.getElementById('updEvent').click();
                return false;   // do not perform standard action
            }       // ctrl-S
            break;
        }       // letter 'S'

        case LTR_U:
        {       // letter 'U'
            if (ev.altKey)
            {       // alt-U
                document.getElementById('updEvent').click();
            }       // alt-U
            break;
        }       // letter 'U'

    }       // switch on key code

    return true;
}       // function eeKeyDown

/************************************************************************
 *  function addCitation                                                *
 *                                                                      *
 *  This method is called when the user requests to add                 *
 *  a citation to the event.                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this            the invoking <button> element.                  *
 *      ev              W3C compliant browsers pass Event               *
 ************************************************************************/
var pendingElement  = null;

function addCitation(ev)
{
    console.log("addCitation: 1361 this.id=" + this.id);
    if (!ev)
    {           // browser is not W3C compliant
        ev          =  window.event;    // IE
    }           // browser is not W3C compliant
    ev.stopPropagation();

    this.disabled   = true;         // prevent double add cit
    let form        = this.form;
    let cell        = this.parentNode;      // <td>
    let row         = cell.parentNode;      // <tr>
    let sect        = row.parentNode;       // <tfoot>
    let table       = sect.parentNode;      // <table>
    let body        = table.tBodies[0];     // <tbody>

    // identification of the event to be cited
    let idet        = 0;
    if (typeof(form.etype) != 'undefined')
        idet        = form.etype.value;     // event type
    let type        = form.citType.value;   // citation type
    let idime       = form.idime.value;     // key of associated record
    if (this.id == 'addCitationDeathCause')
    {           // special button
        type        = 9;
        idime       = form.idir.value;      // key of associated record
    }           // special button
    console.log("1386 type=" + type + ", idime=" + idime);

    if (type < 1)
    {
        alert("addCitation: invalid value of citation type:" +
                    " id=" + this.id +
                    ", type=" + type +
                    ", idime=" + idime);
        beep();
        return;
    }

    if ((type == 30 || type == 31) && idet < 1)
    {
        alert("addCitation: invalid value of idet:" + 
                    " id=" + this.id +
                    ", type=" + type +
                    ", idime=" + idime + ", idet=" + idet);
        beep();
        return;
    }

    if (debug.toLowerCase() == 'y')
    {
        alert("editEvent: addCitation: id=" + this.id +
                ", type=" + type +
                ", idime=" + idime);
    }
    console.log("editEvent: addCitation: id=" + this.id +
                ", type=" + type +
                ", idime=" + idime);

    if (idime < 1)
    {           // not assigned yet, update record in tblER
        pendingElement  = this;
        let updEvent    = document.getElementById('updEvent');

        if (updEvent == null)
            form.submit();
        else
            updEvent.click();
        return;
    }           // not assigned yet, update record in tblER

    // use cookie to recall the specifics of the last citation added
    // as the defaults to fill in
    let cookie      = new Cookie("familyTree");
    let detail      = '';
    let idsr        = 0;
    let sourceName  = '';
    if (cookie.text)
    {           // recall last value entered
        detail      = cookie.text;
    }           // recall last value entered
    if (cookie.idsr)
    {           // recall last value entered
        idsr        = cookie.idsr;
    }           // recall last value entered
    if (cookie.sourceName)
    {           // recall last value entered
        sourceName  = cookie.sourceName;
    }           // recall last value entered
    else
    {           // backwards compatibility
        sourceName  = 'Source for IDSR=' + idsr;
    }           // backwards compatibility

    let parms       = {"rownum"     : 0,
                       "detail"     : detail,
                       'idime'      : idime,
                       'stype'      : type,
                       'idsr'       : idsr,
                       'sourceName' : sourceName};
    let newRow      = createFromTemplate('sourceRow$rownum',
                                         parms,
                                         null);

    if (body == sect)
    {       // add button is in body table row
        body.insertBefore(newRow, row);
    }       // add button is in body table row
    else
    {       // add button is in footer row
        body.appendChild(newRow);
    }       // add button is in footer row

    // support popup help for the fields in the added row
    let sourceCell          = form.Source0;
    sourceCell.helpDiv      = 'SourceSel';
    actMouseOverHelp(sourceCell);

    // set actions for detail input text field
    let element             = form.Page0;
    element.onblur          = createCitation;   // leave field
    element.addEventListener('change', createCitation);   // change field
    actMouseOverHelp(element);

    // populate the select with the list of defined sources to 
    // in the second cell.  The name of the <select> element,
    // the numeric key of the <option> to select, and the name of
    // the <form> are passed as parameters so they can be returned
    // in the response.
    popupLoading(sourceCell);   // display loading indicator
    HTTP.getXML('/FamilyTree/getSourcesXml.php?name=Source0' +
                         "&idsr=" + idsr +
                         "&formname=" + form.name,
                gotSources,
                noSources);
}       // function addCitation

/************************************************************************
 *  function editPictures                                               *
 *                                                                      *
 *  This is the onclick method of the "Edit Pictures" button.           *
 *  It is called when the user requests to edit                         *
 *  information about the Pictures associated with the event that are   *
 *  recorded by instances of Picture.                                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        a <button> element                                  *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function editPictures(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    let form        = this.form;
    let picIdType   = form.PicIdType.value;
    let idir        = null;

    if (form.ider && form.ider.value > 0)
    {       // ider present in form
        idir        = form.ider.value;
    }       // ider present in form
    else
    if (form.idir && form.idir.value > 0)
    {       // idir present in form
        idir        = form.idir.value;
    }       // idir present in form
    else
    if (form.idmr && form.idmr.value > 0)
    {       // idmr present in form
        idir        = form.idmr.value;
    }       // idmr present in form
    else
    {       // unable to identify record to associate with
        popupAlert("Unable to identify record to associate pictures with",
                   this);
        return false;
    }       // unable to identify record to associate with

    // open the edit pictures dialog in the other half of the window
    let frameClass  = 'left';
    openFrame("pictures",
              "editPictures.php?idir=" + idir + "&idtype=" + picIdType, 
              childFrameClass);
    return true;
}   // function editPictures


/************************************************************************
 *  function gotSources                                                 *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the list of sources from the database is retrieved.                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc  information about the defined sources as an XML         *
 *              document                                                *
 ************************************************************************/
function gotSources(xmlDoc)
{
    // get the name of the select element to be updated from the XML document
    let nameElts    = xmlDoc.getElementsByTagName('name');
    let name        = '';
    try {
    if (nameElts.length >= 1)
    {       // name returned
        name    = nameElts[0].textContent;
    }       // name returned
    else
    {       // name not returned
        alert("editEvent.js: gotSources: " +
                "name value not returned from getSourcesXml.php");
        return;
    }       // name not returned
    }
    catch(ex) {
        alert("editEvent.js: gotSources: nameElts=" + nameElts);
    }

    // get the idsr of the select option to be highlighted
    let idsrElts    = xmlDoc.getElementsByTagName('idsr');
    let idsr        = null;
    if (idsrElts.length >= 1)
    {       // idsr returned
        idsr    = idsrElts[0].textContent;
    }       // idsr returned

    // get the formname of the select option to be highlighted
    let formnameElts    = xmlDoc.getElementsByTagName('formname');
    let formname    = null;
    if (formnameElts.length >= 1)
    {       // formname returned
        formname    = formnameElts[0].textContent;
    }       // formname returned
    else
    {       // name not returned
        alert("editEvent.js: gotSources: formname value not returned from getSourcesXml.php");
        return;
    }       // name not returned

    // the form element in the web page
    let form        = document.forms[formname];

    // get the list of sources from the XML file
    let newOptions  = xmlDoc.getElementsByTagName("source");

    // locate the selection element in the web page to be updated
    let elt     = form.elements[name];
    if (elt == null)
    {
        let msg = "";
        for(var i=0; i < form.elements.length; i++)
        {
            msg += form.elements[i].name + ", ";
            if (form.elements[i].name == name)
            {
                elt = form.elements[i];
                break;
            }
        }
        if (elt == null)
        {       // elt still null
        alert("editEvent.js: gotSources: could not find named element " +
                name + ", element names=" + msg);
        return;
        }       // elt still null
    }

    // purge old options on the select if any
    if (elt.options)
        elt.options.length  = 0;    // purge old options if any
    else
        alert("editEvent.js: gotSources:" + new XMLSerializer().serializeToString(elt));

    hideLoading();  // hide loading indicator

    // create a new HTML Option object to represent the ability to
    // create a new source and add it to the Select as the first option
    let option  = addOption(elt,    // Select element
                        'Add New Source',   // text value to display
                        -1);    // key to request add
    elt.addEventListener('change', checkForAdd);

    // customize selection
    elt.size    = 10;   // height of selection list

    // add the options from the XML file to the Select
    for (var i = 0; i < newOptions.length; ++i)
    {       // loop through source nodes
        let node    = newOptions[i];

        // get the text value to display to the user
        // this is the name of the source
        let text    = node.textContent;

        // get the "id" attribute, this is the IDSR value identifying
        // the source.  It becomes the value of the Option. 
        let value   = node.getAttribute("id");
        if ((value == null) || (value.length == 0))
        {       // cover our ass
            value       = text;
        }       // cover our ass

        // create a new HTML Option object and add it to the Select
        option  = addOption(elt,    // Select element
                        text,   // text value to display
                        value); // unique key of source record

        // select the last source chosen by the user
        if (idsr &&
            (value == idsr))
                option.selected = true;

    }       // loop through source nodes

    elt.focus();        // give selection list the focus
}       // function gotSources

/************************************************************************
 *  function noSources                                                  *
 *                                                                      *
 *  This method is called if there is no sources script on the server.  *
 ************************************************************************/
function noSources()
{
    alert("editEvent.js: getSourcesXml.php not found on server");
}       // function noSources

/************************************************************************
 *  function createCitation                                             *
 *                                                                      *
 *  The user has requested to add a citation and supplied all of        *
 *  the required information.                                           *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            <input name='Page...'> element for which this   *
 *                      is the change event or onblur event listener    *
 ************************************************************************/
function createCitation()
{
    console.log("editEvent.js: createCitation: 1699, this->" + this.outerHTML)
    let rownum          = this.name.substring(4);   // IDSX if existing

    // prevent double invocation
    this.removeEventListener('change', createCitation);
    this.removeEventListener('blur', createCitation);

    // get parameters from the form containing this cell
    let form            = this.form;        // form containing element
    let formName        = form.name;        // name of the form

    // type of event to cite
    let type            = form.type.value; 
    let idet            = 0;
    if (form.etype)
        idet            = form.etype.value;

    // key of associated record
    let idime           = form.idime.value;
    if (type == 9)
        idime           = form.idir.value;

    let addButton       = document.getElementById('addCitation' + idime);
    if (!addButton)
        addButton       = document.getElementById('AddCitation');
    if (addButton)
        addButton.disabled      = false;    // re-enable adding citations
    addButton           = document.getElementById('addCitationDeathCause');
    if (addButton)
        addButton.disabled      = false;    // re-enable adding citations

    // other parameters
    let pageText        = this.value;       // value of page element
    let cell            = this.parentNode;  // cell containing page element
    let row             = cell.parentNode;  // row containing page element
    let cell2           = row.cells[1];     // 2nd cell in same row
    let sourceSel       = form.elements['Source' + rownum];
    let sourceOpt       = null;
    if (sourceSel)
    {
        sourceOpt       = sourceSel.options[sourceSel.selectedIndex];

        let idsr        = 0;
        if (sourceOpt)
            idsr        = sourceOpt.value;

        if (idsr > 0)
        {       // existing source IDSR
            // update the cookies for the IDSR value of the last source
            // requested and the citation page text
            let cookie      = new Cookie("familyTree");
            cookie.idsr     = idsr;
            cookie.text     = pageText;
            cookie.sourceName   = sourceOpt.innerHTML;
            cookie.store(10);       // keep for 10 days

            // parameters passed by method='post'
            let parms       = { "idime"     : idime,
                                "type"      : type,
                                "idet"      : idet,
                                "idsr"      : idsr,
                                "page"      : pageText,
                                "row"       : rownum,
                                "formname"  : formName,
                                "debug"     : debug}; 

            let msg         = "parms={";
            let comma       = '';
            for(var pname in parms)
            {
                msg         += comma + pname + "='" + parms[pname] + "'";
                comma       = ',';
            }
            msg             += "}";

            if (debug.toLowerCase() == 'y')
            {           // debugging activated
                popupAlert("editEvent.js: createCitation: " + msg,
                           this);
            }           // debugging activated

            // send the request to add a citation to the server requesting
            // an XML response
            HTTP.post('/FamilyTree/addCitJSON.php',
                      parms,
                      gotAddCit,
                      noAddCit);
        }       // existing source IDSR
        else
        {       // create a new source
            let url = "editSource.php?idsr=0&form=" + formName +
                            "&select=" + sourceSel.name;
            if (debug.toLowerCase() == 'y')
            {
                url += "&debug=" + debug;
                popupAlert("editEvent.js: createCitation: " + url,
                       this);
            }
            openFrame("source",
                      url,
                      childFrameClass);
        }       // create a new source
    }
    else
    {           // source <select> tag not found
        let names   = "";
        for(var n in form)
            names   += n + ", ";
        popupAlert("editEvent.js: createCitation: form.elements[" + names +
                            "], name='Source" + rownum + "'",
                   this);
    }           // source <select: tag not found
}   // function createCitation

/************************************************************************
 *  function gotAddCit                                                  *
 *                                                                      *
 *  This method is called when the JSON file representing               *
 *  the addition of a Citation is retrieved.                            *
 *                                                                      *
 *  {                                                                   *
 *    "parms" : {                                                       *
 *      "idime" : "1063353",                                            *
 *      "type" : "10",                                                  *
 *      "idsr" : "97",                                                  *
 *      "source" : "Birth Register, CA, Ontario",                       *
 *      "page" : "1896-07769",                                          *
 *      "row" : "0",                                                    *
 *      "formname" : "nameForm"  },                                     *
 *      "sqlcmd1051" : "INSERT INTO tblSX (`idsr`, ... , `overridebibliography`) VALUES(97, 1063353, 10, '1896-07769', 1, '', 1, '', 1, 1, 3, 20210315, 001503202100000000, '', 0, 0, 0, '', '', 1, 1, 1)",
 *      "citation" : {                                                  *
 *          "idsx": "449426",                                           *
 *          "idsr": 97,                                                 *
 *          "source":   "Birth Register, CA, Ontario",                  *
 *          "idime":    1063353,                                        *
 *          "type": 10,                                                 *
 *          "typemeans":    "Alternate Name",                           *
 *          "reckey":   "IDNX",                                         *
 *          "srcdetail":    "1896-07769",                               *
 *          "srcprintdetail":   1,                                      *
 *          "srcdettext":   "",                                         *
 *          "srcprinttext": 1,                                          *
 *          "srcdetnote":   "",                                         *
 *          "srcprintnote": 1,                                          *
 *          "srcprint": 1,                                              *
 *          "srcsurety":    3,                                          *
 *          "enteredsd":    20210315,                                   *
 *          "entereddc":    "001503202100000000",                       *
 *          "enteredd": "15 Mar 2021",                                  *
 *          "filingref":    "",                                         *
 *          "order":    "0",                                            *
 *          "used": 0,                                                  *
 *          "verified": 0,                                              *
 *          "content":  "",                                             *
 *          "override": "",                                             *
 *          "overridefootnote": 1,                                      *
 *          "overridesubsequent":   1,                                  *
 *          "overridebibliography": 1                                   *
 *      },                                                              *
 *      "type" : 10                                                     *
 *  }                                                                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      jsonDoc  information about the added citation                   *
 ************************************************************************/
function gotAddCit(jsonDoc)
{
    let msg                         = jsonDoc.msg;
    if (msg && msg.length > 0)
    {
        alert("editEvent.js: gotAddCit: msg='" + msg + "'");
    }
    else
    {
        let parms                   = jsonDoc.parms;
        if (parms.formname)
        {
            let rowNum              = parms.row;
            let idsr                = parms.idsr;
            let sourcename          = parms.source;
            let formname            = parms.formname;
            let form                = document.forms[formname];
            let citation            = jsonDoc.citation;

            // locate elements in web page to be updated
            let trowName            = 'sourceRow' + rowNum;
            let trow                = document.getElementById(trowName);
            if (trow)
            {                   // found table row element
                let tbody           = trow.parentNode;
                let nextRow         = trow.nextSibling;

                tbody.removeChild(trow);    // remove temporary row

                if (citation)
                {               // citation created
                    let idsx        = citation.idsx;
                    parms.idsx      = idsx;
                    parms.detail    = citation.srcdetail;
                    let newRow      = createFromTemplate('sourceRown$idsx',
                                                         parms,
                                                         null);
                    tbody.insertBefore(newRow, nextRow);

                    // activate functionality of buttons
                    let citid       = 'editCitation' + idsx;
                    let edit        = document.getElementById(citid);
                    edit.addEventListener('click', editCitation);
                    let delid       = 'delCitation' + idsx;
                    let del         = document.getElementById(delid);
                    del.addEventListener('click', deleteCitation);
                }               // citation created
            }                   // found table row element
        }                       // valid response
        else                    // unexpected response
            alert("editEvent.js: gotAddCit: " +
                  "jsonDoc='" + JSON.stringify(jsonDoc));
    }
}       // function gotAddCit

/************************************************************************
 *  function noAddCit                                                   *
 *                                                                      *
 *  This method is called if there is no add citation response          *
 *  file from the server.                                               *
 ************************************************************************/
function noAddCit()
{
    alert("editEvent.js: noAddCit: script addCitJSON.php not found on server");
}       // function noAddCit

/************************************************************************
 *  function addAltCitation                                             *
 *                                                                      *
 *  This method is called when the user requests to add                 *
 *  a citation to an alternate name                                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        the invoking <button> element                       *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function addAltCitation(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    this.disabled   = true;         // prevent double add cit
    let form        = this.form;
    let cell        = this.parentNode;  // <td>
    let row     = cell.parentNode;  // <tr>
    let body        = row.parentNode;   // <tbody>
    let table       = body.parentNode;  // <table>

    // identification of the event to be cited
    let type        = 10;           // Citation::STYPE_ALTNAME
    let idime       = this.id.substring(11);// key of Name

    // use cookie to recall the specifics of the last citation added
    // as the defaults to fill in
    let cookie      = new Cookie("familyTree");
    let detail      = '';
    if (cookie.text)
    {           // remember last value entered
        detail      = cookie.text;
    }           // remember last value entered

    let parms       = {"rownum" : 'A',
                       "detail" : detail,
                       "idime"  : idime,
                       "type"   : type};
    let newRow      = createFromTemplate('sourceRow$rownum',
                                 parms,
                                 null);
    let tRow    = body.insertBefore(newRow, row);

    // set actions for detail input text field
    let detailTxt   = form.PageA;
    if (detailTxt)
    {
        detailTxt.addEventListener('blur', createCitation);   // leave field
        detailTxt.addEventListener('change', createCitation);   // change field
    }
    else
    {
        alert("editEvent.js: addAltCitation: <input name='PageA'> not defined tRow=" + new XMLSerializer().serializeToString(tRow));
    }

    // populate the select with the list of defined sources to 
    // in the second cell.  The name of the <select> element,
    // the numeric key of the <option> to select, and the name of
    // the <form> are passed as parameters so they can be returned
    // in the response.
    let sourceCell  = form.SourceA;
    popupLoading(sourceCell);   // display loading indicator
    HTTP.getXML('/FamilyTree/getSourcesXml.php?name=SourceA' +
                    "&idsr=" + cookie.idsr +
                    "&formname=" + form.name,
                gotSources,
                noSources);
}       // function addAltCitation

/************************************************************************
 *  function editCitation                                               *
 *                                                                      *
 *  This method is called when the user requests to edit                *
 *  a citation to a source for an event.                                *
 *                                                                      *
 *  Input:                                                              *
 *      this            instance of <button> tag                        *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function editCitation(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    let form        = this.form;
    let idsx        = this.id.substr(12);

    let url = "editCitation.php?idsx=" + idsx + '&formId=' + form.id;
    if (debug.toLowerCase() == 'y')
    {
        url += "&debug=" + debug;
        if (form.updateCitation)
            alert("editEvent.js: editCitation: open '" + url + "'");
        else
            alert("editEvent.js: editCitation: " + 
                "feedback function updateCitation not defined on <form id='" + 
                form.id + "'>");
    }

    // open a citation in the other half of the window
    openFrame("citation",
              url,
              childFrameClass);
}   // function editCitation

/************************************************************************
 *  function deleteCitation                                             *
 *                                                                      *
 *  This method is called when the user requests to delete              *
 *  a citation to a source for an event.                                *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='delCitation...'>                    *
 *      ev              instance of Event                               *
 ************************************************************************/
function deleteCitation(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    let form        = this.form;
    let idsx        = this.id.substr(11);

    let parms       = {"idsx"   : idsx,
                       "rownum" : idsx,
                       "formname"   : form.name, 
                       "template"   : "",
                       "msg"    :
                    "Are you sure you want to delete this citation?"};

    if (debug.toLowerCase() == 'y')
        parms["debug"]  = debug;

    // ask user to confirm delete
    displayDialog('CitDel$template',
                  parms,
                  this,             // position relative to
                  confirmDelete);   // 1st button confirms Delete
}       // function deleteCitation

/************************************************************************
 *  function confirmDelete                                              *
 *                                                                      *
 *  This method is called when the user confirms the request to delete  *
 *  a citation to a source for an event.                                *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='confirmDelete...'>                  *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function confirmDelete(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    // get the parameter values hidden in the dialog
    let form        = this.form;
    let idsx        = this.id.substr(13);
    let rownum      = form.elements['rownum' + idsx].value;
    let formname    = form.elements['formname' + idsx].value;

    let parms       = {"idsx"   : idsx,
                       "rownum" : idsx,
                       "formname"   : formname}; 

    if (debug.toLowerCase() == 'y')
        parms["debug"]  = debug;

    // hide the dialog
    dialogDiv.style.display = 'none';

    // invoke script to update Event and return XML result
    HTTP.post('/FamilyTree/deleteCitationXml.php',
              parms,
              gotDeleteCit,
              noDeleteCit);
}   // function deleteCitation

/************************************************************************
 *  function gotDeleteCit                                               *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  a deleted citation is retrieved from the database.                  *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc  information about the deleted citation                  *
 ************************************************************************/
function gotDeleteCit(xmlDoc)
{
    let root        = xmlDoc.documentElement;
    const serializer    = new XMLSerializer();
    if (root && (root.nodeName == 'deleted'))
    {               // valid XML response
        let rownum      = root.getAttribute("rownum");
        let idsx        = root.getAttribute("idsx");
        let formname    = root.getAttribute("formname");
        let form        = document.forms[formname];
        for (var i = 0; i < root.childNodes.length; i++)
        {           // loop through immediate children of root
            let elt = root.childNodes[i];
            if (elt.nodeType == 1)
            {       // only examine elements at this level
                if (elt.nodeName == 'msg')
                {   // error message
                    alert(elt.textContent);
                    return; // do not perform any other functions
                }   // error message
            }       // only examine elements at this level
        }           // loop through immediate children of root
        let row     = document.getElementById("sourceRow" + rownum);
        if (row)
        {
            let sect    = row.parentNode;
            sect.removeChild(row);
        }
        else
        {
            alert("cannot find sourceRow" + rownum);
        }
    }               // valid XML response
    else
    {               // error unexpected document
        if (root)
            msg = new XMLSerializer().serializeToString(root);
        else
            msg = xmlDoc;
        alert ("editEvent.js: gotDeleteCit: Error: " + msg);
    }               // error unexpected document
}       // function gotDeleteCit

/************************************************************************
 *  function noDeleteCit                                                *
 *                                                                      *
 *  This method is called if there is no delete citation response       *
 *  file.                                                               *
 ************************************************************************/
function noDeleteCit()
{
    alert("editEvent.js: deleteCitationXml.php not found on server");
}       // function noDeleteCit

/************************************************************************
 *  functin updateCitation                                              *
 *                                                                      *
 *  This method is called by the editCitation.php script to feed back   *
 *  the results so they can be reflected in this page.                  *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            instance of <form> containing citation list     *
 *      idsx            unique numeric key of instance of Citation      *
 *      idsr            unique numeric key of instance of Source        *
 *      sourceName      textual name of source for display              *
 *      page            source detail text (page number)                *
 ************************************************************************/
function updateCitation(idsx,
                        idsr,
                        sourceName,
                        page)
{
    if (debug.toLowerCase() == 'y')
        alert("editEvent.js: updateCitation(idsx=" + idsx +
                              ",idsr=" + idsr +
                              ",sourceName=" + sourceName +
                              ",page=" + page + ")");
    let form        = this;
    let sourceElement   = document.getElementById("Source" + idsx);
    let pageElement = document.getElementById("Page" + idsx);
    if (sourceElement)
        sourceElement.value = sourceName;
    else
        alert("editEvent.js: updateCitation: unable to get element id='Source"+
                idsx + "'");
    if (pageElement)
        pageElement.value   = page;
    else
        alert("editEvent.js: updateCitation: unable to get element id='Page"+
                idsx + "'");
}       // function updateCitation

/************************************************************************
 *  function checkForAdd                                                *
 *                                                                      *
 *  The user has selected a different option in the selection list of   *
 *  sources.                                                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        the input element for which this is the             *
 *                  change event listener                               *
 ************************************************************************/
function checkForAdd()
{
    let option  = this.options[this.selectedIndex];
    if (option.value < 1)
    {           // create new source
        let formName    = this.form.name;
        let elementName = this.name;
        let url     = "editSource.php?idsr=0&form=" + formName +
                            "&select=" + elementName;
        if (debug.toLowerCase() == 'y')
        {
            url += "&debug=" + debug;
            popupAlert("editEvent.js: checkForAdd: " + url,
                    this);
        }

        // open a citation in the other half of the window
        openFrame("source",
                  url,
                  childFrameClass);
    }           // create new source
}       // function checkForAdd

/************************************************************************
 *  function showRaw                                                    *
 *                                                                      *
 *  This method is called when the user requests to change the          *
 *  presentation of the notes text area.                                *
 *                                                                      *
 *  Input:                                                              *
 *      this        the <button type='button' id='raw'>                 *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function showRaw(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    if ('editnotes' in args)
    {
        this.innerHTML      = 'Show TextAreas';
        let newLocation     = location.href.replace('&editNotes=HTML','');
        location        = newLocation;
    }
    else
    {
        this.innerHTML      = 'Show Rich Text Notes';
        location        = location + "&editNotes=HTML";
    }
}       // function showRaw


/************************************************************************
 *  function clearNotes                                                 *
 *                                                                      *
 *  This method is called when the user requests to clear the note      *
 *  area to empty.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this    the <button type='button' id='Clear'>                   *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function clearNotes(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev  =  window.event;    // IE
    }       // browser is not W3C compliant
    ev.stopPropagation();

    tinyMCE.get('note').setContent("");
}       // function clearNotes

/************************************************************************
 *  function nameFeedback                                               *
 *                                                                      *
 *  This method is called when the 'editName.php' script provides       *
 *  feed back on an update to an alternate name record.                 *
 *                                                                      *
 *  Input:                                                              *
 *      parms       associative array of values                         *
 *      this        the <form name='evtForm'>                           *
 ************************************************************************/
function nameFeedback(parms)
{
    let idnx                = null;
    let title               = "";
    let givenName           = "";
    let surname             = "";
    let suffix              = "";
    for(key in parms)
    {                   // loop through all parameters
        let value           = parms[key];
        switch(key)
        {               // act on specific parameter
            case 'idnx':
            {
                idnx        = value;
                break;
            }           // IDNX

            case 'title':
            {
                title       = " " + value;
                break;
            }           // title

            case 'givenName':
            {
                givenName   = value + " ";
                break;
            }           // givenName

            case 'surname':
            {
                surname     = value;
                break;
            }           // surname

            case 'prefix':
            {
                prefix      = value + " ";
                break;
            }           // prefix

        }               // act on specific parameter
    }                   // loop through all parameters

    let name                = prefix + givenName + surname + title;
    let textid              = 'altNameText' + idnx;
    let nameDiv             = document.getElementById(textid);
    if (nameDiv)
        nameDiv.innerHTML   = name;
    else
        alert("editEvent.js: cannot find '" + textid +
                "', nameFeedback: '" + name + "'");
}       // function nameFeedback

/************************************************************************
 *  function sourceCreated                                              *
 *                                                                      *
 *  This method is called when a child window notifies this script      *
 *  that a new source has been created.                                 *
 *  The new source is added to the end of the selection list, out of    *
 *  alphabetical order, and made the currently selected item.           *
 *                                                                      *
 *  Input:                                                              *
 *      this            <form ...>                                      *
 *      parms           associative array of field values               *
 *                      parms.elementname       = name of <select>      *
 ************************************************************************/
function sourceCreated(parms)
{
    let form                = this;
    let formName            = form.name;
    let element             = form.elements[parms.elementname];
    if (element)
    {       // element found in caller
        // update the selection list in the invoking page
        let option          = addOption(element,
                                        parms.srcname,
                                        parms.idsr);
        element.selectedIndex   = option.index;
    }       // element found in caller
    else
    {       // element not found in caller
        alert("editEvent.js: sourceCreated: <select name='" +
                parms.elementname +
                "'> not found in <form name='" + formName +
                "'> in calling page");
    }       // element not found in caller

    return false;
}       // function sourceCreated

