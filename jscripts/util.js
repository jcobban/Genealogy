/************************************************************************
 *  util.js                                                             *
 *                                                                      *
 *  Miscellaneous common utility functions.                             *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/28      try to get rid of undefined in tagToString      *
 *      2010/10/17      add definition of javascript String.trim        *
 *                      return value of getText(HtmlElement) is trimmed *
 *                      finally fixed tagToString!                      *
 *      2010/11/14      if required trim trailing digits off element    *
 *                      names to obtain help division name              *
 *      2010/11/20      move function changeDiv here                    *
 *      2010/11/24      correct style setting for left and top          *
 *      2011/03/08      add keyboard shortcuts for buttons on           *
 *                      QueryDetail9999.html pages                      *
 *      2011/03/27      set default location for cities different from  *
 *                      towns and townships                             *
 *      2011/04/10      functions specific to Canadian Census queries   *
 *                      moved to database/QueryDetail.js                *
 *      2011/04/22      put try catch around code that IE7 screws up    *
 *                      change addOption to comply with IE7             *
 *      2011/05/18      display abbreviations in 3 columns to compress  *
 *                      help balloons                                   *
 *      2011/09/25      improve comments                                *
 *                      add getOffsetRight function                     *
 *      2011/10/15      add support for popping up help balloon in      *
 *                      responseto holding the mouse over an input      *
 *                      element                                         *
 *      2011/10/24      try to pop up help for the dynamically added    *
 *                      <button id='rightTop'> and pages where there is *
 *                      no page specific onload method.                 *
 *      2011/11/10      take down old balloon before displaying new     *
 *                      balloon if it was not taken down previously     *
 *      2011/11/12      change selectOptByValue to return selected      *
 *                      instance of <option>                            *
 *      2012/01/02      suppress warning message for no parent in       *
 *                      displayHelp                                     *
 *      2012/01/07      add functions (not yet used) for selecting      *
 *                      range of table cells                            *
 *      2012/01/16      add method getParmsFromXml                      *
 *      2012/01/24      add methods popupLoading and hideLoading to     *
 *                      manage an AJAX request busy indicator           *
 *                      Move defaultOnLoad to default.js                *
 *      2012/03/06      add function createFromTemplate and its         *
 *                      supporting function subParms, moved from        *
 *                      FamilyTree/commonMarriage.js                    *
 *      2012/04/07      error in displayHelp if input field was not in  *
 *                      a table                                         *
 *      2012/05/04      do not hide help bubble until need to display   *
 *                      next help bubble or 2 seconds have elapsed      *
 *                      since the mouse left the item.                  *
 *                      This permits access to                          *
 *                      hyperlinks inside the help bubble.              *
 *                      remove deprecated getEltId method               *
 *                      add displayDialog and hideDialog methods        *
 *                      support mouse dragging to reposition dialog     *
 *                      change getElt function to search multiple levels*
 *                      add getElts function to return an array of      *
 *                      matching elements                               *
 *                      do not try to hide help division                *
 *                      if not currently set                            *
 *      2012/05/07      do not create duplicate id attributes in        *
 *                      createFromTemplate                              *
 *                      permit first parameter of createFromTemplate to *
 *                      be the string id attribute                      *
 *      2012/06/24      support bypage from SubDistTable                *
 *      2012/09/12      permit getParmsFromXml to be called with a      *
 *                      NodeList or HtmlCollection                      *
 *      2012/10/25      display the maximum number of characters in an  *
 *                      input field in the associated help.             *
 *      2012/11/29      only add length restriction info to help        *
 *                      bubble once                                     *
 *                      create getObjFromXml so it sets the value of a  *
 *                      member of the returned parms to an object if the*
 *                      associated element in the XML has sub-elements. *
 *      2013/02/26      only do mouseover if there is an input element  *
 *                      in a table cell.                                *
 *      2013/04/04      add support for popup location information      *
 *                      add support for popup individual information    *
 *      2013/04/13      unescape text values in getParmsFromXml         *
 *      2013/05/15      fix tagToString for non-text nodes that do not  *
 *                      have children                                   *
 *      2013/05/20      add function actMouseOverHelp                   *
 *      2013/06/12      add support for displaying source record info   *
 *      2013/07/01      make help popup more flexible as to the type of *
 *                      element for which help can be defined           *
 *                      do not popup help for previous element          *
 *                      do not trust case of nodename in HTML DOM       *
 *      2013/08/01      add methods sizeToFit and pageInit              *
 *                      display parents and family information in       *
 *                      individual popup                                *
 *      2013/11/16      add popupAlert function to supplant             *
 *                      javascript alert                                *
 *      2013/12/19      display help for mouse over a <label for=> tag  *
 *      2013/12/27      do not require existing <span> in header div    *
 *      2013/12/30      only Firefox supports default function parms    *
 *      2013/12/31      throw alert if invoked with old version of      *
 *                      Internet Explorer                               *
 *      2014/01/02      getRightTop insert at front of <div>            *
 *      2014/01/19      getRightTop insert a <div> instead of <span>    *
 *                      topcrumbs with <table> no longer supported      *
 *      2014/02/07      correct implementation of getObjFromXml         *
 *                      change function gotIndivid to use new XML       *
 *                      layout from getIndivXml.php                     *
 *      2014/02/22      add support for tracking current dialog popup   *
 *      2014/02/27      in createFromTemplate only do substitution if   *
 *                      there is a $ in the template value              *
 *      2014/03/06      global dialogDiv not set if displayDialog       *
 *                      defered                                         *
 *      2014/03/18      substitute into for attribute of label tag      *
 *      2014/04/26      remove sizeToFit                                *
 *                      change algorithm for positioning help           *
 *      2014/07/17      scroll popups into view                         *
 *      2014/07/27      the above change made help popups a bit         *
 *                      disruptive, so a user option is added to        *
 *                      turn off help popups                            *
 *      2014/08/22      popup help was globally suppressed by mistake   *
 *      2014/09/12      remove obsolete selectOptByValue                *
 *      2014/09/13      add debug flag                                  *
 *      2014/10/07      change display of popup dialog to move it as    *
 *                      close as possible to the invoking button        *
 *      2014/10/12      enhance functions show and hide so they take    *
 *                      an HTML element or a string identifier          *
 *      2014/10/19      further validation of response to getLocation   *
 *      2014/11/02      events moved                                    *
 *      2014/12/04      in createFromTemplate when updating value       *
 *                      attribute with substitutions it is also         *
 *                      necessary to update the defaultValue attribute  *
 *      2015/01/21      add constants for letters of alphabet           *
 *      2015/01/23      add function openFrame                          *
 *      2015/02/01      openFrame should open in top window             *
 *                      pass opener to script running in frame          *
 *      2015/02/04      reduce Z coordinate of all other <iframes>      *
 *                      when bringing new <iframe> to front in          *
 *                      openFrame                                       *
 *      2015/02/10      add method closeFrame to reverse openFrame      *
 *      2015/02/16      method openFrame did not set iframe.opener      *
 *                      if the iframe already existed, so the           *
 *                      wrong opener frame was updated by feedback      *
 *      2015/02/23      openFrame returns instance of Window not Iframe *
 *      2015/04/11      add more key code constants                     *
 *      2015/04/28      correct the positioning of a popup dialog if    *
 *                      the positioning element has been scrolled       *
 *                      into view                                       *
 *      2015/05/01      support for displaying source popup moved       *
 *                      to FamilyTree/legacyIndivid.js                  *
 *                      support for displaying individual popup moved   *
 *                      to FamilyTree/legacyIndivid.js                  *
 *      2015/05/26      use absolute URLs for AJAX requests             *
 *                      support for displaying location popup moved     *
 *                      to FamilyTree/legacyIndivid.js                  *
 *      2015/08/13      if possible use Element.outerHTML to implement  *
 *                      function tagToString                            *
 *                      if possible use Node.textContent to implement   *
 *                      function getText, and define method Node.text() *
 *                      use Element.outerHTML to implement function     *
 *                      createFromTemplate                              *
 *                      set focus on first input element of dialog      *
 *      2016/01/16      during page initialization load a copy of the   *
 *                      record for the current user and set the         *
 *                      helpPopup option based upon the options         *
 *      2016/01/27      the previous did not work because it only       *
 *                      handled users who requested rememberme and      *
 *                      ran before the page was loaded, so it could not *
 *                      find the debug trace area to add its message in *
 *                      so now the important contents of the User       *
 *                      record are at the bottom of the page in a       *
 *                      hidden div and checking this is moved to        *
 *                      pageInit, which is run at load time             *
 *      2016/02/06      add method traceAlert                           *
 *      2016/03/24      set focus on first button when dialog displayed *
 *      2016/06/26      improve diagnostics on missing parm to function *
 *                      createFromTemplate                              *
 *      2017/07/07      in openFrame do not reduce Z-Index of existing  *
 *                      iframes on the stack, just ensure the new       *
 *                      iframe is on top                                *
 *      2017/09/23      consolidate warnings out of createFromTemplate  *
 *                      into a single more informative message          *
 *      2017/11/15      add language support for top-right button       *
 *      2018/01/24      remove function getRightTop                     *
 *      2018/10/30      remove Node.prototype.text                      *
 *                      remove unused function noRightTo                *
 *                      only define String.prototype.trim if not        *
 *                      supported by browser                            *
 *                      in displayHelp do not include $ substitution    *
 *                      in help division name                           *
 *      2019/02/06      function of rightTop button moved into menu     *
 *      2019/02/08      common mandatory initialization required for    *
 *                      all pages is moved from the initPage method     *
 *                      which had to be explicitly called by the        *
 *                      onload handler for each page to the commonInit  *
 *                      function which is invoked for all pages         *
 *      2019/03/02      remove facebook link if not enough room         *
 *      2019/04/07      display help if mouse click on input field      *
 *                      this is in part to support mobile devices       *
 *      2019/04/26      position dialog above element if not enough     *
 *                      room below the element to show without scrolling*
 *      2019/05/03      move management of page scrolling lines to      *
 *                      commonInit                                      *
 *                      reposition scrolling lines for scrolling        *
 *                      hide and restore facebook on page resize        *
 *      2019/06/29      remove first parameter of displayDialog         *
 *      2019/07/23      function closeFrame redirects to home page      *
 *                      if it is invoked from a top level frame when    *
 *                      there is no remaining history.                  *
 *      2019/07/30      consolidate support for tinyMCE                 *
 *      2019/11/11      do not add traceAlert output to console.log     *
 *      2019/12/30      leave header section displayed and scroll       *
 *                      <main> only                                     *
 *      2020/01/09      hide horizontal scroll bar for <body>           *
 *      2020/02/16      add right hand notification column              *
 *      2020/02/22      include scroll of main section, if any, in      *
 *                      dialog position.                                *
 *      2020/03/09      remove facebook                                 *
 *      2020/03/31      display advertisement only after loading        *
 *                      page to speed up page initialization            *
 *      2020/04/19      on resize hide right column if not enough room  *
 *                      or redisplay is there is enough room            *
 *      2020/06/30      improve handling of common parameters           *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  constants for letter key codes                                      *
 *                                                                      *
 *  Microsoft Internet Explorer release 10 and lower do not support     *
 *  defining constants with the const keyword as defined in EcmaScript  *
 *  6th edition (2015) so these have to be defined as "var"             *
 ************************************************************************/
// movement keys
var KEY_BSPACE      =  8;
var KEY_TAB         =  9;
var KEY_ENTER       = 13;
var KEY_SHIFT       = 16;
var KEY_ESC         = 27;
var KEY_PAGEUP      = 33;
var KEY_PAGEDOWN    = 34;
var KEY_END         = 35;
var KEY_HOME        = 36;
var ARROW_LEFT      = 37;
var ARROW_UP        = 38;
var ARROW_RIGHT     = 39;
var ARROW_DOWN      = 40;
var KEY_INSERT      = 45;
var KEY_DELETE      = 46;
// alphabetic keys
var LTR_A           = 65;
var LTR_B           = 66;
var LTR_C           = 67;
var LTR_D           = 68;
var LTR_E           = 69;
var LTR_F           = 70;
var LTR_G           = 71;
var LTR_H           = 72;
var LTR_I           = 73;
var LTR_J           = 74;
var LTR_K           = 75;
var LTR_L           = 76;
var LTR_M           = 77;
var LTR_N           = 78;
var LTR_O           = 79;
var LTR_P           = 80;
var LTR_Q           = 81;
var LTR_R           = 82;
var LTR_S           = 83;
var LTR_T           = 84;
var LTR_U           = 85;
var LTR_V           = 86;
var LTR_W           = 87;
var LTR_X           = 88;
var LTR_Y           = 89;
var LTR_Z           = 90;
// Windoze button
var KEY_START       = 91;
// Function Keys
var KEY_F1          = 112;
var KEY_F2          = 113;
var KEY_F3          = 114;
var KEY_F4          = 115;
var KEY_F5          = 116;
var KEY_F6          = 117;
var KEY_F7          = 118;
var KEY_F8          = 119;
var KEY_F9          = 120;
var KEY_F10         = 121;
var KEY_F11         = 122;
var KEY_F12         = 123;

/************************************************************************
 *  Global warning that Microsoft Internet Explorer doesn't work        *
 ************************************************************************/
if (navigator.appName == 'Microsoft Internet Explorer')
{
    var patt    = /MSIE (\d+)/;
    var result  = patt.exec(navigator.appVersion);
    if (result === null || result[1] < 9)
        alert("Microsoft Internet Explorer Version: " +
              navigator.appVersion +
      " is non-standard in its implementation and many services may not work. " +
      "Upgrade to Internet Explorer version 9 or later, or use any other browser.");
}

/************************************************************************
 *  loaddiv                                                             *
 *                                                                      *
 *  This division contains the "loading" indicator when a script is     *
 *  waiting for an AJAX response from the server.                       *
 ************************************************************************/
var loaddiv         = null;

/************************************************************************
 *  loadelt                                                             *
 *                                                                      *
 *  This is the element with which the "loading" indicator is           *
 *  associated.                                                         *
 ************************************************************************/
var loadelt        = null;

/************************************************************************
 *  dialogDiv                                                           *
 *                                                                      *
 *  The current modal dialog division displayed in a popup              *
 ************************************************************************/
var dialogDiv       = null;

/************************************************************************
 *  global variables used for mouse drag on a dialog                    *
 *                                                                      *
 *  This code does not currently work.                                  *
 ************************************************************************/
var dragok  = false;    // if true drag in progress
var dy;         // distance from top of dialog to mouse
var dx;         // distance from left of dialog to mouse

/************************************************************************
 *  helpDiv                                                             *
 *                                                                      *
 *  The current help division displayed in a popup                      *
 ************************************************************************/
var helpDiv     = null;

/************************************************************************
 *  helpElt                                                             *
 *                                                                      *
 *  The element for which help is to be displayed when the timer pops   *
 ************************************************************************/
var helpElt     = null;

/************************************************************************
 *  helpDelayTimer                                                      *
 *                                                                      *
 *  A timer to control when help is displayed as a result of            *
 *  mouse over events                                                   *
 ************************************************************************/
var helpDelayTimer  = null;

/************************************************************************
 *  iframe                                                              *
 *                                                                      *
 *  Global variable to hold a reference to a displayed dialog in an     *
 *  instance of <iframe> occupying the right hand half of the window.   *
 ************************************************************************/
var iframe      = null;

/************************************************************************
 *  function getArgs                                                    *
 *                                                                      *
 *  Extract the arguments from the location URL when invoked by         *
 *  method='get'                                                        *
 *                                                                      *
 *  Returns:                                                            *
 *      associative array of arguments                                  *
 ************************************************************************/
function getArgs()
{
    var args        = new Object();
    var query       = location.search.substring(1); // search excluding '?'
    var pairs       = query.split("&");     // split on ampersands
    for (var i = 0; i < pairs.length; i++)
    {       // loop through all pairs
        var pos     = pairs[i].indexOf('=');
        if (pos == -1)
            continue;

        // argument names are case-insensitive
        var name    = pairs[i].substring(0, pos).toLowerCase();
        var value   = pairs[i].substring(pos + 1);
        value       = decodeURIComponent(value);
        args[name]  = value;

    }       // loop through all pairs
    return args;
}       // function getArgs

/************************************************************************
 *  global variable args                                                *
 *                                                                      *
 *  Make arguments from the search portion of the URL available to all  *
 *  scripts.                                                            *
 ************************************************************************/
var args                    = getArgs();

/************************************************************************
 * specify the style for tinyMCE editing                                *
 ************************************************************************/
var activateMCE             = true;

/************************************************************************
 *  lang                                                                *
 *                                                                      *
 *  BCP 47 locale                                                       *
 ************************************************************************/
var lang                    = 'en';

/************************************************************************
 *  debug                                                               *
 *                                                                      *
 *  String containing the setting of the debug option used to control   *
 *  output of diagnostic information.  This is set by the script        *
 *  parameter Debug, which can be specified on any script either        *
 *  by method='get' or method='post'                                    *
 ************************************************************************/
var debug                   = 'n';  // default to no debug

for (var key in args)
{       // loop through args
    switch(key.toLowerCase())
    {
        case 'text':
            activateMCE         = false;
            break;

        case 'lang':
            lang                = args.lang;
            break;

        case 'debug':
            debug               = value.toLowerCase();
            break;

    }               // act on specific values
}

if (activateMCE && tinyMCEparms && typeof tinyMCE !== 'undefined')
{
    // alert("tinyMCEparms=" + JSON.stringify(tinyMCEparms));
    tinyMCE.init(tinyMCEparms);
}

/************************************************************************
 *  function getHelpPopupOption                                         *
 *                                                                      *
 *  Determine whether or not this user wishes to see help popups.       *
 *                                                                      *
 *  Returns:                                                            *
 *      true if the user wishes to see help popups, otherwise false     *
 ************************************************************************/
function getHelpPopupOption()
{
    // suppress help popup based upon cookie value
    var allcookies = document.cookie;
    if (allcookies.length == 0)
        return true;

    // Break the string of all cookies into individual cookie strings
    // Then loop through the cookie strings, looking for our name
    var cookies     = allcookies.split(';');
    var cookieval   = null;
    for(var i = 0; i < cookies.length; i++)
    {
        var cookieparts = cookies[i].trim().split('=');

        // Does this cookie string begin with the name we want?
        if (cookieparts[0] == 'user')
        {
            cookieval = decodeURIComponent(cookieparts[1]);
            break;
        }   // name matches
    }       // loop through cookies

    if (cookieval)
    {               // have a user cookie
        // Break user cookie into an array of name/value pairs
        var a = cookieval.split('&');

        // Break each pair into an array with 2 elements
        for(var i=0; i < a.length; i++)
        {           // loop through name/value pairs
            var keyval = a[i].split(':');
            if (keyval[0] == 'options')
            {
                // alert("util.js: getHelpPopupOption: returns " +
                //      ((((keyval[1] - 0) & 2) == 0)?"true":"false"));
                return ((keyval[1] - 0) & 2) == 0;
            }
        }           // loop through name/value pairs
    }               // have a user cookie
    return true;
}       // function getHelpPopupOption

/************************************************************************
 *  global variable popupHelpOption                                     *
 *                                                                      *
 *  Make option as to whether or not to display popup help to the       *
 *  current user available to all scripts.                              *
 ************************************************************************/
var popupHelpOption = true;

/************************************************************************
 *  global variable currentUser                                         *
 *                                                                      *
 *  This global variable contains the record for the current user as    *
 *  an XML node with children.                                          *
 ************************************************************************/
var currentUser = null;

/************************************************************************
 *  global variable cookies                                             *
 *                                                                      *
 *  This global variable contains an associative array of the cookie    *
 *  values passed to the script.                                        *
 ************************************************************************/
var cookies         = [];
var tempCookies     = document.cookie;
if (tempCookies.length > 0)
{           // cookies passed
    // Break the string of all cookies into individual cookie strings
    // Then loop through the cookie strings, looking for our name
    var cookiesList = tempCookies.split(';');
    var txt     = 'util.js: Cookies: length=' + cookiesList.length + ' ';
    for(var i = 0; i < cookiesList.length; i++)
    {           // loop through cookies
        var cookieParts = cookiesList[i].trim().split('=');
        if (cookieParts.length > 1)
        {       // cookie contains '='
            var cookieName  = cookieParts[0];
            txt += cookieName + '=[';
            var cookieVal   = decodeURIComponent(cookieParts[1]);
            var valParts    = cookieVal.trim().split('&');
            var valArray    = [];
            for(var j = 0; j < valParts.length; j++)
            {       // loop through sub-values
                var t       = valParts[j].trim().split(':');
                if (t.length > 1)
                {   // name:value
                    valArray[t[0]]  = t[1];
                    txt     += t[0] + "='" + t[1] + "'";
                }   // name:value
                else
                {   // value
                    valArray.push(t[0]);
                    txt     += "'" + t[0]+ "'";
                }   // value
                txt += ",";
            }       // loop through sub-values;
            cookies[cookieName] = valArray;
            txt     += '];';
        }       // cookie contains '='
    }           // loop through cookies
    //alert(txt);
}           // cookies passed

/************************************************************************
 *  function addOption                                                  *
 *                                                                      *
 *  Add an Option object to a Select statement.                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      select  the Select statement to add to                          *
 *      text    the text to display to the user                         *
 *      value   the value to pass on to the server                      *
 *                                                                      *
 *  Returns:                                                            *
 *      The new Option object                                           *
 *                                                                      *
 ************************************************************************/
function addOption(select, text, value)
{
    // create a new HTML Option object and add it to the Select
    var newOption       = document.createElement("option");
    select.appendChild(newOption);
    newOption.text      = text;     // ie7 demands done after append
    newOption.value     = value;
    return newOption;
}       //  function addOption

/************************************************************************
 *  function createNamedElement                                         *
 *                                                                      *
 *  IE<9 does not permit modifying the name attribute of an input       *
 *  element after it is created.                                        *
 *                                                                      *
 *  Parameters:                                                         *
 *      type    the type of element to create                           *
 *      name    the name to set                                         *
 *                                                                      *
 *  Returns:                                                            *
 *      The new Input object                                            *
 *                                                                      *
 ************************************************************************/
function createNamedElement(type, name)
{
    var element = null;
    // Try the IE way; this fails on standards-compliant browsers
    try {
        element = document.createElement('<'+type+' name="'+name+'">');
        return element;
    } catch (e) {
    }
    if (!element || element.nodeName.toUpperCase() != type.toUpperCase()) {
      // Non-IE browser; use canonical method to create named element
      element       = document.createElement(type);
      element.name  = name;
    }
    return element;
}       //  createNamedElement

/************************************************************************
 *  function getElt                                                     *
 *                                                                      *
 *  This method finds the first instance of a child element node        *
 *  that matches the supplied tag name.  The method searches            *
 *  recursively down the tree.                                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      curent  the node within which to search for a child             *
 *      tagName the name of the tag to search for.  By convention       *
 *              HTML tag names are specified in upper case,             *
 *              but the function uses a case-insensitive comparison     *
 *                                                                      *
 *  Returns:                                                            *
 *      The matching element node or null.                              *
 ************************************************************************/
function getElt(current, tagName)
{
    if (current === null)
    {
        console.trace();
        throw new Error("util.js: getElt: parameter is null");
    }
    if (current.childNodes === undefined)
        throw new Error("util.js: getElt: parameter is not a document element");
    if (current)
    {       // valid current
        for (var i = 0; i < current.childNodes.length; i++)
        {
            var cNode   = current.childNodes[i];
            if (cNode.nodeType == 1)
            {       // element node
                if (cNode.nodeName.toUpperCase() == tagName.toUpperCase())
                    return cNode;
                else
                {   // search recursively
                    var element = getElt(cNode, tagName);
                    if (element)
                        return element;
                }   // search recursively
            }       // element node
        }       // loop through children of current
    }       // valid current
    else
    {       // no parent
        throw "util.js: getElt(" + current + ",'" + tagName + "')";
    }       // no parent
    return null;
}       // function getElt

/************************************************************************
 *  function getElts                                                    *
 *                                                                      *
 *  This method finds all of the instances of a child element node      *
 *  that matches the supplied tag name.  The method searches            *
 *  recursively down the tree.                                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      curent  the node within which to search for children            *
 *      tagName the name of the tag to search for.  By convention       *
 *              HTML tag names are specified in upper case,             *
 *              but the function uses a case-insensitive comparison     *
 *      retval  existing array to add elements to if not null           *
 *                                                                      *
 *  Returns:                                                            *
 *      array of matching element nodes (which may be empty)            *
 ************************************************************************/
function getElts(current, tagName, retval)
{
    if (current.childNodes === undefined)
        throw "util.js: getElts: parameter is not a document element";
    if (retval == null)
        retval  = [];
    if (current)
    {       // valid current
        for (var i = 0; i < current.childNodes.length; i++)
        {
            var cNode   = current.childNodes[i];
            if (cNode.nodeType == 1)
            {       // element node
                if (cNode.nodeName.toUpperCase() == tagName.toUpperCase())
                    retval.push(cNode);
                else
                {   // search recursively
                    getElts(cNode, tagName, retval);
                }   // search recursively
            }       // element node
        }       // loop through children of current
    }       // valid current
    else
    {       // no parent
        throw "util.js: getElts(" + current + ",'" + tagName + "')";
    }       // no parent
    return retval;
}       // function getElts

/************************************************************************
 *  String.trim                                                         *
 *                                                                      *
 *  Define trim as a method of String to remove leading and             *
 *  trailing spaces.  This is required because the trim method          *
 *  defined in EcmaSscript 5.1 is not supported in Internet Explorer    *
 *  prior to release 9.                                                 *
 ************************************************************************/
if (!String.prototype.trim)
    String.prototype.trim = function()
    { return this.replace(/^\s+|\s+$/g, ''); };

/************************************************************************
 *  function getText                                                    *
 *                                                                      *
 *  This method accumulates the text nodes under a specified node.      *
 *  deprecated, use Node.textContent                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      element     the parent node possibly containing text nodes      *
 *                  as children                                         *
 *                                                                      *
 *  Returns:                                                            *
 *      The accumulated text from the text nodes.                       *
 ************************************************************************/
function getText(element)
{
    if (element.textContent)
        return  element.textContent;

    var     text    = "";
    if (element.childNodes === undefined)
        throw "util.js: getText: parameter is not a document element";
    for (var j = 0; j < element.childNodes.length; ++j)
    {
        var sub = element.childNodes[j];
        if ((sub.nodeType == 3) && (sub.nodeValue))
        {       // text node
            if (text.length > 0)
                text    += " ";
            text        += sub.nodeValue;
        }       // concatenate all text elements
    }       // loop through children of source node
    return text.trim();
}       // function getText

/************************************************************************
 *  function copyChildren                                               *
 *                                                                      *
 *  This method copies all of the children of one node and adds         *
 *  them to another node.                                               *
 *                                                                      *
 *  Parameters:                                                         *
 *      fromNode        the node to copy from                           *
 *      toNode          the node to copy to                             *
 *                                                                      *
 ************************************************************************/
function copyChildren(fromNode, toNode)
{
    for (var j = 0; j < fromNode.childNodes.length; ++j)
    {
        var sub = parent.childNodes[j];
        var newNode = sub.cloneNode(true);
        toNode.appendChild(newNode);
    }
}       // function copyChildren

/************************************************************************
 *  function tagToString                                                *
 *                                                                      *
 *  Extract a the string representing the XML or HTML corresponding     *
 *  to the specified node and its children.                             *
 *                                                                      *
 *  Parameters:                                                         *
 *      node    the top of the tree of nodes to be interpreted          *
 *                                                                      *
 *  Returns:                                                            *
 *      String representation of the node and its children.             *
 ************************************************************************/
function tagToString(node)
{
    if (node === null)
        return "null";
    else
    if (node === undefined)
        return "undefined";

    // if the node is an instance of HTMLElement then we can use
    // the outerHTML attribute
    if (node.outerHTML)
        return node.outerHTML;

    // otherwise we have to iterate over all of the child nodes
    var retval;
    if (node.nodeType == 1)
    {       // element
        retval  = "<" + node.nodeName;
    }
    else
    if (node.nodeType == 3)
    {       // text
    }
    else
    {
        retval  = "<nodeType=" + node.nodeType;
    }

    if (node.attributes)
    {
        for (var i = 0; i < node.attributes.length; i++)
        {
            var attrname    = node.attributes[i].name;
            if (attrname.substr(0,2) == "on")
                continue;       // ignore event methods
            if (attrname.substr(0,4) == "aria")
                continue;       // ignore M$ aria attributes
            var value   = node.attributes[i].value;
            if (value.length > 32)
                value   = value.substr(0,31) + "...";
            retval  += " " + node.attributes[i].name + "=\'"
                + value + "\'";
        }
    }

    if (node.nodeType == 3)
    {       // text node
        if (node.nodeValue !== undefined)
            retval += node.nodeValue.trim();
    }       // text node
    else
    {       // not a text node
        retval  += ">\n";

        if (node.childNodes)
        {
            for (var i = 0; i < node.childNodes.length; i++)
            {       // loop through childNOdes
                var child   = node.childNodes[i];
                if (child.nodeType == 1)
                    retval  += tagToString(child);
                else
                if (child.nodeType == 3)
                    if (child.nodeValue !== undefined)
                        retval += child.nodeValue.trim();

            }       // loop through childNodes
        }       // node.childNodes exists

        if (node.nodeValue)
        {       // text
            retval += "value=\"" + node.nodeValue.trim() + "\"\n";
        }       // text
    }       // not a text node

    if (node.nodeType == 1)
    {       // close element
        retval  += "</" + node.nodeName + ">\n";
    }       // close element
    else
    if (node.nodeType == 3)
    {       // close text
    }       // close text
    else
    {       // close other node type
        retval  += "</nodeType=" + node.nodeType + ">\n";
    }       // close other node type
    return retval;
}       // function tagToString

/************************************************************************
 *  function show                                                       *
 *                                                                      *
 *  Make the element identified by the identifier visible.              *
 *  This is primarily used to display popup dialogs.                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      id          value of the id parameter of the element to be      *
 *                  made visible, or                                    *
 *                  an HTML element instance                            *
 ************************************************************************/
function show(id)
{
    var element                 = null;
    if (this instanceof Element)
        element                 = this;
    else
    if (id instanceof Element)
        element                 = id;
    else
        element                 = document.getElementById(id);
    element.style.display       = 'block';
    element.style.visibility    = 'visible';
    element.scrollIntoView();

    // set the focus on the first button in the dialog
    // displayDialog ensures that even if the dialog designer forgot
    // to include any buttons at least one is always present
    var buttons = element.getElementsByTagName('BUTTON');
    if (buttons.length > 0)
        buttons[0].focus();
}   // function show

/************************************************************************
 *  function hide                                                       *
 *                                                                      *
 *  Make the element identified by the identifier invisible.            *
 *  This is primarily used to hide popup help balloons.                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      id          value of the id parameter of the element to be      *
 *                  hidden, or                                          *
 *                  an HTML element instance                            *
 ************************************************************************/
function hide(id)
{
    var element                 = null;
    if (this instanceof Element)
        element                 = this;
    else
    if (id instanceof Element)
        element                 = id;
    else
        element                 = document.getElementById(id);
    element.style.display       = 'none';
}   // function hide

/************************************************************************
 *  function getOffsetLeft                                              *
 *                                                                      *
 *  Get the offsetLeft of an HTML element relative to the page.         *
 *                                                                      *
 *  Input:                                                              *
 *      elt     an element from an HTML form                            *
 ************************************************************************/
function getOffsetLeft(elt)
{
    var left    = 0;
    while(elt)
    {
        left    += elt.offsetLeft;
        elt = elt.offsetParent;
    }       // increment up to top element
    return left;
}   // function getOffsetLeft

/************************************************************************
 *  function getOffsetRight                                             *
 *                                                                      *
 *  Get the offsetRight of an HTML element relative to the page.        *
 *                                                                      *
 *  Input:                                                              *
 *      elt     an element from an HTML form                            *
 ************************************************************************/
function getOffsetRight(elt)
{
    var left    = 0;
    var right   = elt.offsetWidth;
    while(elt)
    {
        left    += elt.offsetLeft;
        elt = elt.offsetParent;
    }       // increment up to top element
    return right + left;
}   // function getOffsetRight

/************************************************************************
 *  function getOffsetTop                                               *
 *                                                                      *
 *  Get the offsetTop of an HTML element relative to the page.          *
 *                                                                      *
 *  Input:                                                              *
 *      elt     an element from an HTML form                            *
 ************************************************************************/
function getOffsetTop(elt)
{
    // note that "top" is a reserved word
    var y           = 0;
    while(elt)
    {
        y           += elt.offsetTop;
        elt         = elt.offsetParent;
    }       // increment up to top element
    var main        = document.getElementsByTagName('main');
    if (main.length > 0)
    {
        main        = main[0];
        y           -= main.scrollTop;
    }
    return y;
}   // function getOffsetTop

/************************************************************************
 *  function addHelpAbbrs                                               *
 *                                                                      *
 *  The cell for which help is to be displayed supports the expansion   *
 *  of abbreviations according to a table.  Display the contents of the *
 *  abbreviation table as part of the help text.                        *
 *                                                                      *
 *  Input:                                                              *
 *      helpDiv         the 'help' DIV element                          *
 *      abbrTable       the table of abbreviations and their expansions *
 ************************************************************************/
function addHelpAbbrs(helpDiv, abbrTable)
{
    // if either of the input parameters is null, do nothing
    if (!helpDiv)
        return;
    if (!abbrTable)
        return;

    // if there is already a <TABLE> tag in this help division, then
    // the abbreviation expansion information has already been added
    var ptags   = helpDiv.getElementsByTagName("TABLE");
    if (ptags.length > 0)
        return;

    // add the abbreviation expansion documentation to the help panel
    var p1  = helpDiv.appendChild(document.createElement('P'));
    p1.className= 'label';
    p1.appendChild(document.createTextNode(
        "The following abbreviations are expanded:"
                        ));
    var tbl = helpDiv.appendChild(document.createElement('TABLE'));
    var numCols = 3;
    var col = 0;
    var tr;
    for(var abbr in abbrTable)
    {
        if (col == 0)
        {
            tr  = tbl.appendChild(document.createElement('TR'));
            col = numCols;
        }
        col--;
        var td1 = tr.appendChild(document.createElement('TH'));
        td1.className   = "left";
        td1.appendChild(document.createTextNode(abbr));
        var td2 = tr.appendChild(document.createElement('TD'));
        td2.appendChild(document.createTextNode(abbrTable[abbr]));
    }       // run through abbreviations
}       // function addHelpAbbrs

/************************************************************************
 *  function showHelp                                                   *
 *                                                                      *
 *  Display the current help text;                                      *
 ************************************************************************/
function showHelp()
{
    show(helpDiv)
}       // function showHelp

/************************************************************************
 *  function hideHelp                                                   *
 *                                                                      *
 *  Hide the current help text;                                         *
 ************************************************************************/
function hideHelp()
{
    if (helpDiv)
    {       // a help balloon is displayed
        helpDiv.style.display   = 'none';
        helpDiv                 = null;
    }       // a help balloon is displayed
}       // function hideHelp

/************************************************************************
 *  function keyDown                                                    *
 *                                                                      *
 *  Handle key strokes in text input fields.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function keyDown(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e       =  window.event;    // IE
    }       // browser is not W3C compliant
    var code    = e.key;

    // hide the help balloon on any keystroke
    if (helpDiv)
    {       // helpDiv currently displayed
        helpDiv.style.display   = 'none';
        helpDiv         = null; // no longer displayed
    }       // helpDiv currently displayed
    clearTimeout(helpDelayTimer);   // clear pending help display
    helpDelayTimer      = null;

    // take action based upon code
    switch (code)
    {
        case "F1":
        {
            displayHelp(this);      // display help page
            e.preventDefault();
            e.stopPropagation();
            return false;       // suppress default action
        }       // F1

    }       // switch on key code

    return true;
}       // function keyDown

/************************************************************************
 *  function displayHelp                                                *
 *                                                                      *
 *  Display the help division associated with a particular element      *
 *  in the web page.                                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      element the HTML tag for which help is to be displayed          *
 ************************************************************************/
function displayHelp(element)
{
    // if a previous help balloon is still being displayed, hide it
    if (helpDiv)
    {       // a help balloon is currently displayed
        helpDiv.style.display   = 'none';
        helpDiv                 = null;
    }       // a help balloon is currently displayed

    var helpDivName             = "";
    var name                    = element.name;
    if (name === undefined || name === null || name == '')
        name                    = element.id;
    var forid                   = element.getAttribute("for");
    try {
        // the help division name may be supplied by an explicit private
        // attribute "helpDiv"
        if (element.helpDiv)
            helpDivName = element.helpDiv;
        else
        if (name && name.length > 0)
        {   // element has a name
            var dollar = name.indexOf('$');
            if (name.length > 2 &&
                name.substring(name.length - 2) == "[]")
            {       // multiple selection
                helpDivName     = name.substring(0, name.length - 2);
            }       // multiple selection
            else
            if (dollar > 0)
            {
                helpDivName     = name.substring(0,dollar);
            }
            else
            {       // ordinary element
                helpDivName     = name;
            }       // ordinary element
        }   // element has a name
        else
        if (forid && forid.length > 0)
        {   // label tag has a for attribute
            helpDivName     = forid;
        }   // label tag has a for attribute
        else
        {   // try id attribute
            helpDivName     = element.id;
        }   // try id attribute
    } catch(e)
    {       // exception thrown trying to get help division name
        alert("util.js: displayHelp: exception=" + e +
                        ", element=" + tagToString(element));
    }       // exception thrown trying to get help division name

    // to ensure unique id values, the supplied name is
    // prefixed with "Help".  However some forms have not been
    // updated to use this convention

    // accumulate information to be used in diagnostic messages
    var msg         = tagToString(element);

    // 1) try for the div with id='Help<name>'
    // 2) try without any row number at end of <name>
    // 3) try back-level page without Help prefix
    helpDiv         = document.getElementById("Help" + helpDivName);
    if (!helpDiv)
    {       // first choice not found
        // strip off trailing decimal digits if any representing
        // a row number
        for (var l = helpDivName.length; l > 1; l--)
        {   // find last non-numeric character
            if (helpDivName[l - 1] < '0' ||
                helpDivName[l - 1] > '9')
            {   // non-digit
                helpDivName = helpDivName.substr(0, l);
                helpDiv = document.getElementById("Help" + helpDivName);
                break;
            }   // non-digit
        }   // find last non-numeric character

        // if cannot find division with name prefixed with 'Help'
        // then try without
        if (!helpDiv && helpDivName.length > 0)
            helpDiv = document.getElementById(helpDivName);
    }       // first choice not found

    // display the help division if found
    if (helpDiv && (helpDiv != element))
    {       // have a help division to display
        if (element.maxLength && element.maxLength > 0)
        {
            var spanId  = helpDivName + 'Maxlen';
            var oldElt  = document.getElementById(spanId);
            if (oldElt === null)
            {       // information not already appended to help
                var span    = document.createElement('span');
                span.setAttribute('id', spanId);
                var text    = document.createTextNode(
                        "A maximum of " + element.maxLength +
                        " characters may be entered in this field.  ");
                span.appendChild(text);
                helpDiv.appendChild(span);
            }       // information not already appended to help
        }

        // If presentation style requires capitalization,
        // report it in help
        var textTransform   = "";
        if (element.currentStyle)
        {       // browser supports IE API
            textTransform   = element.currentStyle.textTransform;
        }       // browser supports IE API
        else
        if (window.getComputedStyle)
        {       // browser supports W3C API
            var style       = window.getComputedStyle(element, null);
            textTransform   = style.textTransform;
        }       // browser supports W3C API

        // notify the user if text in the field is automatically capitalized
        if (textTransform == "capitalize")
        {
            if (!helpDiv.capitalized)
            {       // add text to help
                helpDiv.appendChild(document.createTextNode(
                  "Text entered in this field is automatically capitalized."
                                            ));
                helpDiv.capitalized = true;
            }
        }

        // if the field has automatic abbreviation expansion
        // describe it
        if (element.abbrTbl)
            addHelpAbbrs(helpDiv, element.abbrTbl);

        // display the help balloon in an appropriate place on the page
        var tableWidth  = window.innerWidth;
        if (getOffsetLeft(element) < Math.floor(window.innerWidth/2))
            helpDiv.style.left  = (getOffsetLeft(element) + 50) + 'px';
        else
            helpDiv.style.left  =
                (getOffsetLeft(element) -
                                Math.floor(window.innerWidth/2)) + 'px';
        helpDiv.style.top   = (getOffsetTop(element) +
                                       element.offsetHeight + 5) + 'px';
        // so key strokes in balloon will close window
        helpDiv.onkeydown   = keyDown;
        show(helpDiv);
    }       // have a help division to display
    //else
    //  alert("util.js: displayHelp: Cannot find <div id='Help" + helpDivName + "'>");
}       // function displayHelp

/************************************************************************
 *  function setDefault                                                 *
 *                                                                      *
 *  A number of fields have their value defaulted                       *
 *                                                                      *
 *  Input:                                                              *
 *      fld     an input text element                                   *
 *      value   default value if the field is empty                     *
 ************************************************************************/
function setDefault(fld, value)
{
    if (fld && fld.value.length == 0)
        fld.value   = value;
}       // function setDefault

/************************************************************************
 *  function xmlencode                                                  *
 *                                                                      *
 *  Replace all characters that have a special meaning in XML           *
 *  with their associated symbols;                                      *
 *                                                                      *
 *  Input:                                                              *
 *      string  a string to be encoded                                  *
 *                                                                      *
 *  Returns:                                                            *
 *      string with all special characters encoded                      *
 ************************************************************************/
function xmlencode(string) {
    return string.replace(/\&/g,'&'+'amp;').replace(/</g,'&'+'lt;')
        .replace(/>/g,'&'+'gt;').replace(/\'/g,'&'+'apos;').replace(/\"/g,'&'+'quot;');
}

function sizeToFit()
{
    // no operation
}

/************************************************************************
 *  function copyXmlToHtml                                              *
 *                                                                      *
 *  Copy the node tree under an XML node and add it                     *
 *  under an HTML node.                                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlNode         XML node at top of tree                         *
 *      htmlNode        target HTML node                                *
 *                                                                      *
 *  Returns:                                                            *
 *      last node element inserted                                      *
 ************************************************************************/
function copyXmlToHtml(xmlNode,
                       htmlNode)
{
    var xmlOld;
    var htmlNew;
    var htmlElt = null;

    for (xmlOld = xmlNode.firstChild; xmlOld; xmlOld = xmlOld.nextSibling)
    {
        switch(xmlOld.nodeType)
        {
            case 1: // Element
            {
                htmlElt = document.createElement(xmlOld.nodeName);
                for (var i = 0; i < xmlOld.attributes.length; i++)
                {   // loop through attributes
                    var attr    = xmlOld.attributes[i];
                    if (attr.name == 'onclick')
                    {
                        if (attr.value.substring(0,10) == "openSignon")
                            htmlElt.onclick = openSignon;
                        else
                            htmlElt.onclick = openAccount;
                    }       // onclick
                    else
                    if (attr.name == 'class')
                        htmlElt.className   = attr.value;
                    else
                        htmlElt.setAttribute(attr.name, attr.value);
                }   // loop through attributes
                copyXmlToHtml(xmlOld, htmlElt);
                htmlNode.appendChild(htmlElt);
                break;
            }       // Element

            case 3: // Text
            {
                htmlNew = document.createTextNode(xmlOld.nodeValue);
                htmlNode.appendChild(htmlNew);
                break;
            }       // Text

        }       // switch on source node type
    }           // loop through children of xmlOld

    return htmlElt; // last element created at this level
}       // function copyXmlToHtml

/************************************************************************
 *  function openSignon                                                 *
 *                                                                      *
 *  This method is called to open the signon dialog if the user is not  *
 *  yet signed on.                                                      *
 ************************************************************************/
function openSignon(ev)
{
    var server  = location.protocol + "//" +
                  location.hostname;
    if (location.port.length > 0)
        server  += ":" + location.port;
    if (location.pathname.substring(0,12) == "/jamescobban")
        server  += "/jamescobban/";
    else
        server  += "/";

    window.open(server + "Signon.php?lang=" + lang);
}       // function openSignon

/************************************************************************
 *  function openAccount                                                *
 *                                                                      *
 *  This method is called to open the account dialog if the user is     *
 *  already signed on.                                                  *
 ************************************************************************/
function openAccount(ev)
{
    var server  = location.protocol + "//" +
                  location.hostname;
    if (location.port.length > 0)
        server  += ":" + location.port;
    if (location.pathname.substring(0,12) == "/jamescobban")
        server  += "/jamescobban/";
    else
        server  += "/";

    window.open(server + "Account.php?lang=" + lang);
}       // function openAccount

/************************************************************************
 *  function changeDiv                                                  *
 *                                                                      *
 *  This method is called when the user selects a new division.         *
 *  It is called by the scripts ReqUpdateXxxxx.js.                      *
 *                                                                      *
 *  Input:                                                              *
 *      divNode             an XML element containing information about *
 *                      a division as retrieved from the database       *
 *                      table SubDistTable.                             *
 ************************************************************************/
function changeDiv(divNode)
{
    // locate cell to prompt for page number in
    var tableNode   = getElt(document.distForm, "TABLE");
    var tbNode      = getElt(tableNode,"TBODY");
    var trNode      = document.getElementById("pageRow");

    // remove any existing HTML from the table row with id='pageRow'
    if (trNode)
    {       // have <tr id='pageRow'>
        var tdNode      = document.getElementById("pageCell");
        if (tdNode)
        {   // have cell containing number of pages
            // remove previous contents of cell, if any
            while (tdNode.hasChildNodes())
                tdNode.removeChild(tdNode.firstChild);

            // add information from database record
            var pages       = divNode.getAttribute("pages");
            var page1       = divNode.getAttribute("page1");
            var bypage      = divNode.getAttribute("bypage");

            if ((pages.length > 0) &&
                (Number(pages) > 0))
            {   // explicit number of pages available from database
                // create selection element to choose page
                var select  = document.createElement("select");
                select.name = "Page";
                select.size = 1;
                var pageoff = 0;
                if ((page1.length > 0) &&
                    (Number(page1) > 0))
                    pageoff = Number(page1) - bypage;

                // add option element for each page in division
                for(var i = 1; i <= Number(pages); i++)
                {   // loop through pages
                    addOption(select,
                              i*bypage + pageoff,
                              i*bypage + pageoff);
                }   // loop through pages

                tdNode.appendChild(select);
            }   // explicit number of pages
            else
            {   // explicit number of pages not available from database
                // use simple text input to obtain page number
                var input   = document.createElement("input");
                input.type="text";
                input.name="Page";
                input.size=2;
                input.value="1";
                tdNode.appendChild(input);
            }   // explicit number of pages not available
        }   // have cell containing number of pages
    }       // have row containing number of pages
}       // function changeDiv

/************************************************************************
 *  function eltMouseOver                                               *
 *                                                                      *
 *  This function is called if the mouse moves over an input element    *
 *  on the invoking page.  Delay popping up the help balloon for        *
 *  two seconds.                                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML tag                                            *
 ************************************************************************/
var helpEltText     = '';
function eltMouseOver()
{
    if (popupHelpOption)
    {           // this user wants help
        if (this.nodeName.toUpperCase() == 'FIELDSET')
            return;

        // in some cases the mouseover event is against the table cell
        // containing the input element.  Locate the first element node
        // under the cell to display help for
        if (this.nodeName.toUpperCase() == 'TD' ||
            this.nodeName.toUpperCase() == 'DIV')
        {       // mouseover defined for the cell containing the element
            for (var i = 0; i < this.childNodes.length; i++)
            {       // loop through children of this cell
                var cNode   = this.childNodes[i];
                if (cNode.nodeType == 1 && (cNode.name || cNode.id))
                {   // element
                    helpElt = cNode;
                    break;
                }   // element
            }       // loop through children of this
        }       // mouseover defined for the cell containing the element
        else
            helpElt     = this;

        if (helpElt)
        {
            helpEltText = "helpElt=" + helpElt.outerHTML + ". ";
            helpDelayTimer  = setTimeout(popupHelp, 2000);
        }
    }           // this user wants help
}       // function eltMouseOver

/************************************************************************
 *  function popupHelpHandler                                           *
 *                                                                      *
 *  This method is called if the mouse is clicked on the element.       *
 *  It shows the associated help division.                              *
 *  This is used because mobile devices do not support the mouseover    *
 *  and mouseout events.                                                *
 ************************************************************************/
function popupHelpHandler(ev)
{
    if (popupHelpOption)
    {                           // user accepts popup help
        helpElt             = ev.target;
        popupHelp();
    }                           // user accepts popup help
}       // function popupHelpHandler

/************************************************************************
 *  function popupHelp                                                  *
 *                                                                      *
 *  This function is called if the mouse is held over an input element  *
 *  on the invoking page for more than 2 seconds, or the mouse is       *
 *  clicked on the element.  It shows the associated help division.     *
 ************************************************************************/
function popupHelp()
{
    if (helpElt)
    {
        if (!helpElt.helpAlreadyDisplayed)
        {                       // help for this element never displayed
            displayHelp(helpElt);
            helpElt.helpAlreadyDisplayed    = true; // only once
        }                       // help for this element never displayed
        helpElt         = null;
        helpEltText     += ", helpElt set to null line 1526";
    }
}       // function popupHelp

/************************************************************************
 *  function eltMouseOut                                                *
 *                                                                      *
 *  This function is called if the mouse moves off an input element     *
 *  on the invoking page.  The help balloon, if any, remains up for     *
 *  a further 2 seconds to permit access to links within the help text. *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML tag                                            *
 ************************************************************************/
function eltMouseOut()
{
    clearTimeout(helpDelayTimer);
    helpDelayTimer  = setTimeout(hideHelp, 2000);
}       // function eltMouseOut

/************************************************************************
 *  function actMouseOverHelp                                           *
 *                                                                      *
 *  This function is called to activate mouse hover initiated popup     *
 *  help display for an element.                                        *
 *                                                                      *
 *  Input:                                                              *
 *      element             an HTML element, usually an input element   *
 ************************************************************************/
function actMouseOverHelp(element)
{
    if (element === undefined)
        throw "util.js: actMouseOverHelp: element is undefined";

    addEventHandler(element, 'mouseover',   eltMouseOver);
    addEventHandler(element, 'mouseout',    eltMouseOut);
    addEventHandler(element, 'click',       popupHelpHandler);
}       // function actMouseOverHelp

/************************************************************************
 *  function displayMenu                                                *
 *                                                                      *
 *  This function displays the page menu in a popup                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        element with id="menuButton" or id="logo"           *
 *      ev          click event                                         *
 ************************************************************************/
function displayMenu(ev)
{
    ev.stopPropagation();
    var menu                    = document.getElementById('menu');

    // ensure the menu is hidden before modifying it
    menu.style.display          = 'none';
    menu.style.position         = 'absolute';
    menu.style.visibility       = 'hidden';
    menu.style.display          = 'block';

    // display the menu offset from the main menu button
    var element                 = document.getElementById('menuButton');
    var leftOffset              = getOffsetLeft(element);
    var rightOffset             = getOffsetRight(element);

    var dialogWidth             = menu.clientWidth;
    if (leftOffset - dialogWidth < 10)
        leftOffset              = rightOffset + 10;
    else
        leftOffset              = leftOffset - dialogWidth - 10;
    menu.style.left             = leftOffset + "px";
    menu.style.top              = (getOffsetTop(element) + 10) + 'px';

    var anchors     = menu.getElementsByTagName('a');
    var previous    = anchors[anchors.length - 1];
    for(var i = 0; i < anchors.length; i++)
    {           // loop through children
        var anchor          = anchors[i];
        previous.nextAnchor = anchor;
        anchor.prevAnchor   = previous;
        previous            = anchor;
        addEventHandler(anchor, "keydown", keyDownMenu);
    }           // loop through children
    menu.style.display          = 'block';
    menu.style.visibility       = 'visible';
    menu.scrollIntoView();
    var help                    = document.getElementById('menuhelp');
    if (help)
        help.focus();

    dialogDiv           = menu;

    return dialogDiv;
}       // function displayMenu

/************************************************************************
 *  function displayDialog                                              *
 *                                                                      *
 *  This function displays a customized dialog in a popup               *
 *                                                                      *
 *  Input:                                                              *
 *      dialog          an HTML element to modify and make visible.     *
 *                      This is normally a <div> element that is        *
 *                      initially not visible                           *
 *      templateId      identifier of an HTML element that provides the *
 *                      structure and constant strings to be laid       *
 *                      out in the dialog.                              *
 *                      Normally this should be a <form> element since  *
 *                      it will contain at the least one action button. *
 *      parms           an object containing values to substitute for   *
 *                      symbols ($xxxx) in the template                 *
 *      element         an HTML element used for positioning the        *
 *                      dialog for the user.  This is normally the      *
 *                      <button> for the user to request the dialog.    *
 *      action          onclick action to set for buttons               *
 *                      in the dialog.  If null all buttons default     *
 *                      to just hide the dialog.  If this is an array   *
 *                      of function refs the actions are applied        *
 *                      in order to the <button>s in the dialog.        *
 *                      If this is a single function reference then     *
 *                      it applies to the first button, and any         *
 *                      subsequent buttons close the dialog.            *
 *      defer           if true leave the dialog hidden for the         *
 *                      caller to complete and show.                    *
 ************************************************************************/
function displayDialog(templateId,
                       parms,
                       element,
                       action,
                       defer)
{
    // only one modal dialog at a time is displayed
    if (dialogDiv)
    {               // a dialog balloon is currently displayed
        dialogDiv.style.display = 'none';   // hide it
        dialogDiv               = null;     // it is no longer displayed
    }               // a dialog balloon is displayed

    // the dialog is laid out in a common shared div
    var dialog  = document.getElementById('msgDiv');
    if (dialog === null)
    {               // belt and suspenders
        dialog              = document.createElement('div');
        dialog.id           = 'msgDiv';
        dialog.className    = 'balloon';
        document.body.appendChild(dialog);
    }               // belt and susenders

    // ensure the dialog is hidden before modifying it
    dialog.style.display        = 'none';
    dialog.style.position       = 'absolute';
    dialog.style.visibility     = 'hidden';
    dialog.style.display        = 'block';

    var template                = null;
    if (typeof templateId == 'string')
        template                = document.getElementById(templateId);
    else
    if (typeof templateId == 'object')
    {
        template                = templateId;
        templateId              = template.id;
    }
    else
    {
        alert('util.js: displayDialog: first parameter omitted');
        return false;
    }

    if (template === null)
    {
        alert("util.js: displayDialog: could not find template with id='" +
                templateId + "'");
        return false;
    }
    else
    {       // template OK
        // clear existing contents of message division
        dialog.innerHTML        = '';

        // customize the template
        var form                = createFromTemplate(template,
                                                     parms,
                                                     null);
        form.id                 = '';

        if (form.nodeName.toUpperCase() != 'FORM')
        {       // catch and correct definition problem
            alert("util.js: displayDialog: template with id='" +
                    templateId + "' is an instance of <" + form.nodeName + ">");
            form                = document.createElement("FORM").
                                            appendChild(form);
        }       // catch and correct definition problem
        form                    = dialog.appendChild(form);

        // set the onclick action for the first (or only) button
        // in the dialog
        var buttons             = dialog.getElementsByTagName('BUTTON');
        var button;
        if (buttons.length == 0)
        {       // button, button, who's got the button?
            // add a default "Cancel" button
            button              = document.createElement("BUTTON");
            button.appendChild(document.createTextNode("Cancel"));
            form.appendChild(button);
            buttons[0]          = button;
        }       // missing button

        // set the onclick handler for all of the buttons in the dialog
        if (action)
        {
            if (action instanceof Array)
            {       // array of actions
                for (var i = 0; i < buttons.length; i++)
                    if (i < action.length)
                        buttons[i].onclick  = action[i];
                    else
                        buttons[i].onclick  = hideDialog;

            }       // array of actions
            else
            {       // single action for first button
                buttons[0].onclick  = action;
                for (var i = 1; i < buttons.length; i++)
                    buttons[i].onclick  = hideDialog;
            }       // single action for first button
        }
        else
        {       // default action, every button closes dialog
            for (var i = 0; i < buttons.length; i++)
                buttons[i].onclick  = hideDialog;
        }       // default action, every button closes dialog

        // display the dialog offset from the requesting button
        var topOffset           = 0;
        var leftOffset          = 0;
        var rightOffset         = 0;
        if (element)
        {
            topOffset           = getOffsetTop(element);
            leftOffset          = getOffsetLeft(element);
            rightOffset         = getOffsetRight(element);
        }
        var pane                = document.getElementById('transcription');
        if (pane === null)
            pane                = document.body;

        var dialogWidth         = dialog.clientWidth;
        var dialogHeight        = dialog.clientHeight;
        if (leftOffset - dialogWidth < 10)
        {                   // display to right of element
            leftOffset          = rightOffset + 10 - pane.scrollLeft;
        }                   // display to right of element
        else
        {                   // display to left of element
            leftOffset          = leftOffset - dialogWidth - 10 -
                                            pane.scrollLeft;
        }                   // display to left of element
        dialog.style.left       = leftOffset + "px";
        if ((topOffset + dialogHeight + 10) > pane.clientHeight)
        {                   // display above element
            dialog.style.top        = (topOffset - dialogHeight - 10) + 'px';
        }                   // display above element
        else
        {                   // display below element
            dialog.style.top        = (topOffset + 10) + 'px';
        }                   // display below element

        // support mouse dragging
        dialog.onmousedown      = dialogMouseDown;
        dialog.onmousemove      = null;
        dialog.onmouseup        = dialogMouseUp;

        // do not permit mouse clicks in this dialog to
        // bubble up to the click handler on <body>
        addEventHandler(dialog, 'click', stopProp);

        // show the dialog if not requested to defer this until dialog complete
        if (defer === undefined)
            defer               = false;
        if (!defer)
        {       // display the dialog immediately
            dialog.style.visibility     = 'visible';
            dialog.scrollIntoView();
            dialog.style.display        = 'block';

            // set the focus on the first button so Enter will apply it
            buttons[0].focus();
        }       // display the dialog immediately
        dialogDiv       = dialog;
    }           // template OK
    return dialogDiv;
}       // function displayDialog

/************************************************************************
 *  function addEventHandler                                            *
 *                                                                      *
 *  This provides a portable interface for adding event handlers.       *
 *                                                                      *
 *  Input:                                                              *
 *      element     the HTML element                                    *
 *      type        string identifying the event type                   *
 *      handler     function                                            *
 ************************************************************************/
function addEventHandler(element, type, handler)
{
    if (element.addEventListener)
    {
        element.addEventListener(type, handler, false);
    }
    else if (element.attachEvent)
        element.attachEvent('on' + type, handler);
    else
    switch(type.toLowerCase())
    {                   // incredibly ancient browser

        case 'click':
            element.onclick         = handler;
            break;

        case 'keydown':
            element.onkeydown       = handler;
            break;

        case 'mouseover':
            element.onmouseover     = handler;
            break;

        case 'mouseout':
            element.onmouseout      = handler;
            break;

    }                   // incredibly ancient browser
}       // function addEventHandler

/************************************************************************
 *  function stopProp                                                   *
 *                                                                      *
 *  This onclick handler receives all mouse click events from the       *
 *  area of the dialog.  It prevents them from propagating through      *
 *  to the mouse click event handler on <body>.                         *
 *                                                                      *
 *  Input:                                                              *
 *      this        the HTML element                                    *
 ************************************************************************/
function stopProp(ev)
{
    ev.stopPropagation();
    return false;
}       // function stopProp

/************************************************************************
 *  function hideDialog                                                 *
 *                                                                      *
 *  This is the default onclick action for a button in a dialog.  It    *
 *  closes (hides) the dialog (<div>) containing the button.            *
 *                                                                      *
 *  Input:                                                              *
 *      this        the HTML <button> element                           *
 ************************************************************************/
function hideDialog(ev)
{
    // no longer displaying the modal dialog popup
    if (dialogDiv)
        dialogDiv.style.display = 'none';   // hide`
    dialogDiv           = null;
    return null;
}       // function hideDialog

/************************************************************************
 *  function dialogIsDisplayed                                          *
 *                                                                      *
 *  This function is used to determine whether or not there is          *
 *  currently a modal dialog popup displayed to the user.               *
 ************************************************************************/
function dialogIsDisplayed()
{
    return dialogDiv?true:false;
}       // function dialogIsDisplayed

/************************************************************************
 *  function dialogMouseDown                                            *
 *                                                                      *
 *  This is the onmousedown handler for a dialog.  It prepares for      *
 *  dragging the dialog.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this    the top element of the dialog                           *
 *      event   instance of MouseDown Event                             *
 ************************************************************************/
function dialogMouseDown(event)
{
    if (!event)
        event   = window.event;
    dragok  = true;
    dx      = parseInt(this.style.left+0)   - event.clientX;
    dy      = parseInt(this.style.top+0)    - event.clientY;
    // only have onmousemove handler while dragging
    this.onmousemove = dialogMouseMove;
    return false;   // suppress default action
}       // function dialogMouseDown

/************************************************************************
 *  function dialogMouseMove                                            *
 *                                                                      *
 *  This is the onmousemove handler for a dialog.  It updates the       *
 *  position of the dialog as the mouse is dragged.                     *
 *                                                                      *
 *  Input:                                                              *
 *      this    the top element of the dialog                           *
 *      event   instance of MouseMove Event                             *
 ************************************************************************/
function dialogMouseMove(event)
{
    if (!event)
        event   = window.event;
    if (dragok)
    {
        this.style.left = dx + event.clientX + "px";
        this.style.top  = dy + event.clientY + "px";
        return false;
    }
}       // function dialogMouseMove

/************************************************************************
 *  function dialogMouseUp                                              *
 *                                                                      *
 *  This is the onmouseup handler for a dialog.  It completes           *
 *  dragging the dialog.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this    the top element of the dialog                           *
 ************************************************************************/
function dialogMouseUp()
{
    dragok      = false;
    this.onmousemove    = null;
}   // function dialogMouseUp

/************************************************************************
 *  function documentOnClick                                            *
 *                                                                      *
 *  This is the click event handler for the document.  It closes        *
 *  any open dialogs.                                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this    the top element of the dialog                           *
 ************************************************************************/
function documentOnClick(event)
{
    if (dialogDiv)
    {       // a dialog balloon is displayed
        dialogDiv.style.display = 'none';
        dialogDiv               = null;
    }       // a dialog balloon is displayed
}       // function documentOnClick

/************************************************************************
 *  function keyDownPaging                                              *
 *                                                                      *
 *  Handle key strokes for pageDown and pageUp                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function keyDownPaging(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e       =  window.event;    // IE
    }       // browser is not W3C compliant
    var code                = e.key;

    // take action based upon code
    switch (code)
    {
        case "f":
        case "F":
        {
            if (e.ctrlKey)
            {
                var element         = document.getElementById('menuButton');
                element.click();
                e.preventDefault();
                e.stopPropagation();
                return false;       // suppress default action
            }
            break;
        }

        case "F10":
        {
            var element         = document.getElementById('menuButton');
            element.click();
            e.preventDefault();
            e.stopPropagation();
            return false;       // suppress default action
        }       // F1

        case "PageDown":    // page down
        {
            var element     = document.getElementById('topNext');
            if (element)
            {               // topNext exists
                for(var child   = element.firstChild;
                    child;
                    child = child.nextSibling)
                {           // loop through children
                    if (child.nodeName.toLowerCase() == 'a')
                    {       // <a>
                        location    = child.getAttribute('href');
                        return false;
                    }       // <a>
                }           // loop through children
            }               // topNext exists
            return false;   // suppress default action
        }                   // page down

        case "PageUp":      // page up
        {
            var element     = document.getElementById('topPrev');
            if (element)
            {               // topPrev exists
                for(var child   = element.firstChild;
                    child;
                    child = child.nextSibling)
                {           // loop through children
                    if (child.nodeName.toLowerCase() == 'a')
                    {       // <a>
                        location    = child.getAttribute('href');
                        return false;
                    }       // <a>
                }           // loop through children
            }               // topPrev exists
            return false;       // suppress default action
        }       // page down

    }       // switch on key code

    return;
}       // function keyDownPaging

/************************************************************************
 *  function keyDownMenu                                                *
 *                                                                      *
 *  Handle key strokes within the menu.                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function keyDownMenu(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e       =  window.event;    // IE
    }       // browser is not W3C compliant
    var code                = e.key;

    // take action based upon code
    switch (code)
    {
        case "ArrowDown":   // arrow down
        {
            this.nextAnchor.focus();
            e.preventDefault();
            e.stopPropagation();
            return false;   // suppress default action
        }                   // page down

        case "ArrowUp":     // arrow up
        {
            this.prevAnchor.focus();
            e.preventDefault();
            e.stopPropagation();
            return false;       // suppress default action
        }       // page down

    }       // switch on key code

    return;
}       // function keyDownMenu

/************************************************************************
 *  function statusChangeCallback                                       *
 *                                                                      *
 *  Handle Facebook login status change notification                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      response        Facebook Response object                        *
 ************************************************************************/
function statusChangeCallback(response)
{
    console.log('statusChangeCallback');
    console.log(response);
    // The response object is returned with a status field that lets the
    // app know the current login status of the person.
    // Full docs on the response object can be found in the documentation
    // for FB.getLoginStatus().
    if (response.status === 'connected')
    {       // Logged into your app and Facebook.
        console.log('Welcome!  Fetching your information.... ');
        FB.api('/me', function (response)
            {
                console.log('Successful login for: ' + response.name);
                traceAlert('Thanks for logging in, ' + response.name + '!');
            });
    }       // Logged into your app and Facebook.
    else
    if (response.status === 'not_authorized')
    {       // The person is not logged into your app or we are unable to tell.
        // add Please <button id="fblogin">log in</button> to this Facebook app.' to the current page
    }       // The person is not logged into your app or we are unable to tell.
    else
    {       // the user is unknown or not logged into FB
        console.log('FB status=' + response.status);
    }       // the user is unknown or not logged into FB
}       // function statusChangeCallback

/************************************************************************
 *  function commonInit                                                 *
 *                                                                      *
 *  This function is called for all pages to perform initialization     *
 *  that is common to all pages.                                        *
 *  This replaces the requirement for all pages to call pageInit in     *
 *  the pages body.onload handler.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      event       instance of Event containing load event             *
 *      this        instance of Window                                  *
 ************************************************************************/
addEventHandler(window, "load",     commonInit);
addEventHandler(window, "resize",   commonResize);

function commonInit(event)
{
    var w       = window,
    d           = document,
    e           = d.documentElement,
    g           = d.getElementsByTagName('body')[0],
    x           = w.innerWidth || e.clientWidth || g.clientWidth,
    y           = w.innerHeight|| e.clientHeight|| g.clientHeight;

    addEventHandler(document, "click", documentOnClick);
    addEventHandler(document, "keydown", keyDownPaging);

    // set onclick action for the menu button
    var menuButton          = document.getElementById('menuButton');
    var menuWidth           = 0
    if (menuButton)
    {
        addEventHandler(menuButton,'click', displayMenu);
        menuWidth           = menuButton.offsetWidth;
    }

    var logo                = document.getElementById('logo');
    var logoWidth           = 0
    if (logo)
    {
        addEventHandler(logo,'click', displayMenu);
        logoWidth           = logo.offsetWidth;
    }

    var advert              = document.getElementById('advertSpan');
    var advertWidth         = 500
    if (advert)
    {
        advertWidth         = Math.max(advert.offsetWidth, 500);
    }

    var menusWidth= menuWidth + logoWidth + advertWidth;

    if (typeof(FB) != 'undefined')
        FB.getLoginStatus(function(response) {
            statusChangeCallback(response);
        });

    // scroll main portion of page if it does not fit without scrolling
    var headSection             = document.getElementById('headSection');
    var mainSection             = document.getElementById('mainSection');
    var mainHeight              = mainSection.offsetHeight;
    var windHeight              = window.innerHeight;
    if (headSection)
    {
        var headHeight          = headSection.offsetHeight;
        var headWidth           = headSection.offsetWidth;
        if (mainHeight + headHeight > windHeight)
        {
            mainSection.style.height    = (windHeight - headHeight - 12) + 'px';
            headSection.style.width     = (headWidth - 10) + 'px';
        }
    }                           // headSection defined in template

    var rightColumn             = document.getElementById('rightColumn');
    if (rightColumn)
    {                           // right column defined
        var windWidth           = window.innerWidth;
        if (windWidth > 1100)
        {
            mainSection.style.width     = (windWidth - 320) + 'px';
            rightColumn.style.height    = (windHeight - headHeight - 10) + 'px';
        }
        else
        {
            mainSection.style.width     = '100%';
            rightColumn.style.display   = 'none';
        }

        var useridElt           = document.getElementById('UserInfoUserid');
        var userid              = useridElt.innerHTML.trim();
        if (userid.length > 0)
        {
            var collectElt      = document.getElementById('collection');
            if (collectElt)
                collectElt.style.display    = 'none';
        }
        else
        {
            var welcomeElt      = document.getElementById('userWelcome');
            if (welcomeElt)
                welcomeElt.style.display    = 'none';
        }

    }                           // right column defined
    else
    {
        mainSection.style.width             = '100%';
    }

    // if the user has requested it, suppress popup help
    // information about the current user is now available in all pages
    var optionsElt  = document.getElementById('UserInfoOptions');
    if (optionsElt)
    {               // have info from User instance
        var topt            = optionsElt.textContent.trim() - 0;
        if (topt && 2)
        {           // turn off popup Help
            if (debug == 'y')
                traceAlert("util.js: pageInit: turn off popup help");
            popupHelpOption = false;
        }           // turn off popup Help
    }               // have info from User instance
    else
        alert("commonInit: cannot find UserInfoOptions");

    // scan through all forms and set common dynamic functionality
    // for elements
    for(var i = 0; i < document.forms.length; i++)
    {           // iterate through all forms
        var form    = document.forms[i];
        for(var j = 0; j < form.elements.length; j++)
        {       // loop through elements in form
            var element = form.elements[j];

            // pop up help balloon if the mouse hovers over an element
            // for more than 2 seconds
            actMouseOverHelp(element);
        }       // loop through elements in form
    }           // iterate through all forms

    var dataTable                   = document.getElementById('dataTable');
    if (dataTable)
    {                   // page contains display of tabular results
        var topBrowse               = document.getElementById('topBrowse');
        var botBrowse               = document.getElementById('botBrowse');
        if (topBrowse || botBrowse)
        {               // page contains pagination row
            var dataWidth           = dataTable.offsetWidth;
            var windowWidth         = document.body.clientWidth - 8;
            if (dataWidth > windowWidth)
                dataWidth           = windowWidth;
            if (topBrowse)
                topBrowse.style.width   = dataWidth + "px";
            if (botBrowse)
                botBrowse.style.width   = dataWidth + "px";

            // only check for scrolling every 1/4 of a second
            setInterval( function()
            {
                if ( scrolling )
                {
                    scrolling = false;
                    if (topBrowse)
                    {
                        topBrowse.style.left    = lastScrollX + 'px';
                        topBrowse.style.position= 'relative';
                    }
                    if (botBrowse)
                    {
                        botBrowse.style.left    = lastScrollX + 'px';
                        botBrowse.style.position= 'relative';
                    }
                }
            }, 250);
        }               // page contains pagination row
    }                   // page contains display of tabular results

    let advertFrame         = document.getElementById('advertFrame');
    if (advertFrame &&
        'load' in advertFrame.dataset &&
        typeof XMLHttpRequest === 'function')
    {
        let adurl               = advertFrame.dataset.load;
        let httpRequest         = new XMLHttpRequest();
        httpRequest.addEventListener('load', advertLoaded);
        httpRequest.addEventListener('error', advertError);
        httpRequest.addEventListener('abort', advertAbort);
        httpRequest.open('GET', adurl, true);
        httpRequest.send();
    }

}       // function commonInit
function advertLoaded (evt) {
    let pattern         = new RegExp('<body[^>]*>([^]*)</body>', 'im');
    let results         = this.responseText.match(pattern);
    let contents        = results[1];
    let frame           = document.getElementById('advertFrame');
    frame.outerHTML     = contents;
}
function advertError(evt){
    console.log("advertError: ");
}
function advertAbort(evt){
    console.log("advertAbort: ");
}
/************************************************************************
 *  function commonResize                                               *
 *                                                                      *
 *  This function is called for all pages to perform action on resize   *
 *  that is common to all pages.                                        *
 *                                                                      *
 *  Input:                                                              *
 *      event       instance of Event containing resize event           *
 *      this        instance of Window                                  *
 ************************************************************************/
addEventHandler(window, "resize",   commonResize);

function commonResize(event)
{
    var w       = window,
    d           = document,
    e           = d.documentElement,
    g           = d.getElementsByTagName('body')[0],
    x           = w.innerWidth || e.clientWidth || g.clientWidth,
    y           = w.innerHeight|| e.clientHeight|| g.clientHeight;

    var topCrumbs           = null;
    var menuButton          = document.getElementById('menuButton');
    var menuWidth           = 0
    if (menuButton)
    {
        topCrumbs           = menuButton.parentNode;
        menuWidth           = menuButton.offsetWidth;
    }

    var logo                = document.getElementById('logo');
    var logoWidth           = 0
    if (logo)
    {
        logoWidth           = logo.offsetWidth;
    }

    var advert              = document.getElementById('advertSpan');
    var advertWidth         = 0
    if (advert)
    {
        topCrumbs           = advert.parentNode;
        advertWidth         = Math.max(advert.offsetWidth, 500);
    }

    var menusWidth= menuWidth + logoWidth + advertWidth;

    var dataTable                   = document.getElementById('dataTable');
    if (dataTable)
    {                   // page contains display of tabular results
        var topBrowse               = document.getElementById('topBrowse');
        var botBrowse               = document.getElementById('botBrowse');
        if (topBrowse || botBrowse)
        {               // page contains pagination row
            var dataWidth           = dataTable.offsetWidth;
            var windowWidth         = document.body.clientWidth - 8;
            if (dataWidth > windowWidth)
                dataWidth           = windowWidth;
            if (topBrowse)
                topBrowse.style.width   = dataWidth + "px";
            if (botBrowse)
                botBrowse.style.width   = dataWidth + "px";
        }               // page contains pagination row
    }                   // page contains display of tabular results

    var headSection             = document.getElementById('headSection');
    var mainSection             = document.getElementById('mainSection');
    var rightColumn             = document.getElementById('rightColumn');
    if (rightColumn)
    {                           // right column defined
        var windWidth           = window.innerWidth;
        var windHeight          = window.innerHeight;
        var headHeight          = headSection.offsetHeight;
        var headWidth           = headSection.offsetWidth;
        if (windWidth > 1100)
        {
            mainSection.style.width     = (windWidth - 320) + 'px';
            rightColumn.style.height    = (windHeight - headHeight - 10) + 'px';
            rightColumn.style.display   = 'block';
        }
        else
        {
            mainSection.style.width     = '100%';
            rightColumn.style.display   = 'none';
        }

        var useridElt           = document.getElementById('UserInfoUserid');
        var userid              = useridElt.innerHTML.trim();
        if (userid.length > 0)
        {
            var collectElt      = document.getElementById('collection');
            if (collectElt)
                collectElt.style.display    = 'none';
        }
        else
        {
            var welcomeElt      = document.getElementById('userWelcome');
            if (welcomeElt)
                welcomeElt.style.display    = 'none';
        }

    }                           // right column defined
    else
    {
        mainSection.style.width             = '100%';
    }
}       // function commonResize

/************************************************************************
 *  function commonOrientation                                          *
 *                                                                      *
 *  This function is called for all pages to perform action on          *
 *  orientation change that is common to all pages.                     *
 *                                                                      *
 *  Input:                                                              *
 *      ev          instance of Event containing orientation change     *
 *                  event                                               *
 *      this        instance of Window                                  *
 ************************************************************************/
addEventHandler(window, "orientationchange",   commonOrientation);

function commonOrientation(ev)
{
    var w       = window,
    d           = document,
    e           = d.documentElement,
    g           = d.getElementsByTagName('body')[0],
    x           = w.innerWidth || e.clientWidth || g.clientWidth,
    y           = w.innerHeight|| e.clientHeight|| g.clientHeight;

    alert("OrientationChange: width=" + x + ", height=" + y);
}       // function commonOrientation

/************************************************************************
 *  function commonScroll                                               *
 *                                                                      *
 *  This function is called for all pages to support actions when the   *
 *  viewport of the window is scrolled from its base position.          *
 *  Because scroll events can be fired at high frequency while the user *
 *  is dragging the scroll bar, it is recommended to delay updating     *
 *  the DOM by using an interval timer.                                 *
 *                                                                      *
 *  Input:                                                              *
 *      event       instance of Event containing scroll event           *
 *      this        instance of Window                                  *
 ************************************************************************/
addEventHandler(window, "scroll",   commonScroll);
var scrolling               = false;
var lastScrollY             = 0;
var lastScrollX             = 0

function commonScroll(event)
{
    lastScrollY             = Math.round(window.scrollY);
    lastScrollX             = Math.round(window.scrollX);
    scrolling               = true;
}           // function commonScroll


/************************************************************************
 *  function traceAlert                                                 *
 *                                                                      *
 *  Add the message to the end of the debugTrace division so it is      *
 *  displayed as a warning in the page after the <h1>                   *
 *                                                                      *
 *  Input:                                                              *
 *      message         text of message to display                      *
 ************************************************************************/
function traceAlert(message)
{
    var traceDiv            = document.getElementById('debugTrace');
    if (traceDiv == null)
    {                       // no existing trace div
        traceDiv            = document.createElement('div');
        traceDiv.id         = 'debugTrace';
        traceDiv.className  = 'warning';
        var container       = document.body;
        var h1s             = container.getElementsByTagName('h1');
        if (h1s.length > 0)
        {                   // insert after first <h1>
            var h1          = h1s[0];
            container       = h1.parentNode;
            if (h1.nextSibling)
                container.insertBefore(traceDiv, h1.nextSibling);
            else
                container.appendChild(traceDiv);
        }                   // insert after first <h1>
        else
            container.appendChild(traceDiv);
    }                       // no existing trace div
    var line                = document.createElement('p');
    var tags                = message.split('>');
    if (tags.length > 10)
    {                       // so many > implies XML
        for(var it = 0; it < tags.length; it++)
        {                   // separate the tags with breaks
            line.appendChild(document.createTextNode(tags[it] + '>'));
            line.appendChild(document.createElement('br'));
        }                   // separate the tags with breaks
    }                       // so many > implies XML
    else
    {                       // single text line
        line.appendChild(document.createTextNode(message));
    }                       // single text line
    traceDiv.appendChild(line);
}       // function traceAlert

/************************************************************************
 *  The following code for performing copy and paste in tables does not *
 *  work, and is not referenced from any production code.               *
 *                                                                      *
 *  Fields to track mouse operations on a table for performing          *
 *  copy and paste                                                      *
 ************************************************************************/
var mouseIsDown     = false;
var pendStartCell   = null;     // pending possible selection
var startCell       = null;     // first cell of selection
var endCell         = null;     // last cell of selection

/************************************************************************
 *  function getStartSelection                                          *
 *                                                                      *
 *  This function is obtain the first table cell in the selection.      *
 ************************************************************************/
function getStartSelection()
{
    alert("getStartSelection: startCell=" + startCell);
    return startCell;
}       // function getStartSelection

/************************************************************************
 *  function getEndSelection                                            *
 *                                                                      *
 *  This function is obtain the first table cell in the selection.      *
 ************************************************************************/
function getEndSelection()
{
    return endCell;
}       // function getEndSelection

/************************************************************************
 *  function cancelSelection                                            *
 *                                                                      *
 *  This function is called to cancel an existing selection.            *
 ************************************************************************/
function cancelSelection()
{
    alert("cancelSelection from row=" +
                startCell.parentNode.rowIndex + " col=" +
                startCell.cellIndex + " " +
                tagToString(startCell) + " to row=" +
                endCell.parentNode.rowIndex +
                " col=" + endCell.cellIndex + " "  +
                tagToString(endCell));

    if (startCell && endCell)
    {       // have a selection to cancel
        var firstRowIndex   = startCell.parentNode.rowIndex;
        var lastRowIndex    = endCell.parentNode.rowIndex;
        var firstCellIndex  = startCell.cellIndex;
        var lastCellIndex   = endCell.cellIndex;
        if (firstRowIndex > lastRowIndex)
        {   // swap
            var tri     = firstRowIndex;
            firstRowIndex   = lastRowIndex;
            lastRowIndex    = tri;
        }   // swap
        if (firstCellIndex > lastCellIndex)
        {   // swap
            var tci     = firstCellIndex;
            firstCellIndex  = lastCellIndex;
            lastCellIndex   = tci;
        }   // swap

        var tableBody   = startCell.parentNode.parentNode;

        for (var ri = firstRowIndex; ri <= lastRowIndex; ri++)
        {   // loop through selected rows
            var row = tableBody.rows[ri];
            for (var ci = firstCellIndex; ci <= lastCellIndex; ci++)
            {   // loop through selected columns
                var cell    = row.cells[ci];
                // find first element in selected cell
                var element = cell.firstChild;
                while(element && element.nodeType != 1)
                    element = element.nextSibling;
                var oldcn   = element.className;
                var newcn   = oldcn;
                if (oldcn.substring(oldcn.length - 8) == "Selected")
                    newcn   = oldcn.substring(0,oldcn.length - 8);
                element.className   = newcn;
            }   // loop through selected columns
        }   // loop through selected rows
    }       // have a selection to cancel
    startCell       = null;
    endCell     = null;
}       // function cancelSelection

/************************************************************************
 *  function markSelection                                              *
 *                                                                      *
 *  This function is called to mark a new selection.                    *
 ************************************************************************/
function markSelection()
{
    if (startCell && endCell)
    {       // have a selection to mark
        alert("markSelection from row=" +
                startCell.parentNode.rowIndex + " col=" +
                startCell.cellIndex + " " +
                tagToString(startCell) + " to row=" +
                endCell.parentNode.rowIndex +
                " col=" + endCell.cellIndex + " "  +
                tagToString(endCell));

        var firstRowIndex   = startCell.parentNode.rowIndex;
        var lastRowIndex    = endCell.parentNode.rowIndex;
        var firstCellIndex  = startCell.cellIndex;
        var lastCellIndex   = endCell.cellIndex;
        if (firstRowIndex > lastRowIndex)
        {   // swap
            var tri     = firstRowIndex;
            firstRowIndex   = lastRowIndex;
            lastRowIndex    = tri;
        }   // swap
        if (firstCellIndex > lastCellIndex)
        {   // swap
            var tci     = firstCellIndex;
            firstCellIndex  = lastCellIndex;
            lastCellIndex   = tci;
        }   // swap

        var tableBody   = startCell.parentNode.parentNode;

        for (var ri = firstRowIndex; ri <= lastRowIndex; ri++)
        {   // loop through selected rows
            var row = tableBody.rows[ri];
            for (var ci = firstCellIndex; ci <= lastCellIndex; ci++)
            {   // loop through selected columns
                var cell    = row.cells[ci];
                // find first element in selected cell
                var element = cell.firstChild;
                while(element && element.nodeType != 1)
                    element = element.nextSibling;
                element.className   = element.className + "Selected";
            }   // loop through selected columns
        }   // loop through selected rows
    }       // have a selection to mark
}       // function markSelection

/************************************************************************
 *  function eltMouseDown                                               *
 *                                                                      *
 *  This function is called if the mouse button is pressed on a         *
 *  table cell.                                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            element the mouse was pressed on                *
 ************************************************************************/
function eltMouseDown()
{
    mouseIsDown     = true;
    pendStartCell   = this;
    return false;   // suppress default processing
}       // function eltMouseDown

/************************************************************************
 *  function eltMouseUp                                                 *
 *                                                                      *
 *  This function is called if the mouse button is released on a        *
 *  table cell.                                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            element the mouse was pressed on                *
 ************************************************************************/
function eltMouseUp()
{
    // find first element in selected cell
    var element = this.firstChild;
    while(element && element.nodeType != 1)
        element = element.nextSibling;

    // check for a simple click: mouse down and then up on same cell
    if (pendStartCell === this)
    {       // click on cell
        element.focus();
        return true;
    }       // click on cell

    // if a previous selection has not been acted on, cancel it first
    // this will reverse highlighting of selected cells
    if (startCell && endCell)
    {       // cancel existing selection
        cancelSelection();
    }       // cancel existing selection

    // process as new selection
    startCell       = pendStartCell;
    pendStartCell   = this;

    // if a valid selection, record it for future actions
    if (startCell && this.nodeName.toUpperCase() == 'TD')
    {       // valid selection
//  alert("select from row=" +
//      startCell.parentNode.rowIndex + " col=" + startCell.cellIndex + " " +
//      tagToString(startCell) + " to row=" +
//      this.parentNode.rowIndex + " col=" + this.cellIndex + " "  +
//      tagToString(this));
        mouseIsUp   = false;
        endCell     = this;
        markSelection();
        if (element)
            element.focus();    // move focus to input element
        return false;
    }       // valid selection
//    else
//  alert("unexpected from " + tagToString(startCell) + " to " +
//      tagToString(this));

    // not a valid selection, just move focus
    mouseIsUp       = false;
    if (element)
        element.focus();    // move focus to input element
    return true;
}       // function eltMouseUp

/************************************************************************
 *  function getParmsFromXml                                            *
 *                                                                      *
 *  Create a Javascript Object from an XML element by storing           *
 *  the text value containing in each child element as the value of a   *
 *  child of the Object.  If the text value can be represented as an    *
 *  integer value it is converted to one.                               *
 *                                                                      *
 *  Input:                                                              *
 *      element         an XML Element.  If a NodeList or               *
 *                      HtmlCollection is passed instead, the first     *
 *                      element is processed.                           *
 ************************************************************************/
function getParmsFromXml(element)
{
    var parms   = {};
    if (element === undefined)
        throw("util.js: getParmsFromXml: parameter is undefined");
    if (element.item)
    {       // parameter is a NodeList or HtmlCollection
        if (element.length > 0)
            element = element[0];
        else
            return parms;   // empty object
    }       // parameter is a NodeList or HtmlCollection

    // store the parameters in an object
    for (var j = 0; j < element.childNodes.length; j++)
    {       // loop through elements within XML response
        var elt = element.childNodes[j];
        if (elt.nodeType != 1)
            continue;   // ignore text & comments between elements

        var value   = "";

        for (var ic = 0; ic < elt.childNodes.length; ic++)
        {   // loop through children of current element
            var child   = elt.childNodes[ic];
            if (child.nodeType == 3)
            {   // text node
                value   += child.nodeValue;
            }   // text node
        }   // loop through children of current element

        // if the text value is entirely numeric it is converted
        // to a number otherwise the text value is returned
        if (value.search("^[0-9]+$") == 0)
            parms[elt.nodeName] = parseInt(value);
        else
            parms[elt.nodeName] = value.replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&amp;/g, "&").replace(/&nbsp;/g, " ");
    }       // loop through elements within XML response
    return  parms;
}       // function getParmsFromXml

/************************************************************************
 *  function getObjFromXml                                              *
 *                                                                      *
 *  Create a Javascript Object from an XML element by storing           *
 *  the text value containing in each child element as the value of a   *
 *  named child of the Object.  Nested elements are represented         *
 *  by arrays of objects.  For example given the XML:                   *
 *      <top>                                                           *
 *        <a>aaa</a>                                                    *
 *        <b>bbb</b>                                                    *
 *        <list>                                                        *
 *          <c>cc1</c>                                                  *
 *          <d>dd1</d>                                                  *
 *        </list>                                                       *
 *        <list>                                                        *
 *          <c>cc2</c>                                                  *
 *          <d>dd2</d>                                                  *
 *        </list>                                                       *
 *      </top>                                                          *
 *                                                                      *
 *      The returned parms value would be:                              *
 *                                                                      *
 *      parms.a         = 'aaa'                                         *
 *      parms.b         = 'bbb'                                         *
 *      parms.list      = [                                             *
 *                          {c = 'cc1', d='dd1'},                       *
 *                          {c = 'cc2', d='dd2'}]                       *
 *                                                                      *
 *  Input:                                                              *
 *      element         an XML Element.  If a NodeList or               *
 *                      HtmlCollection is passed instead, the first     *
 *                      element is processed.                           *
 ************************************************************************/
function getObjFromXml(element)
{
    var parms   = {};
    if (element.length)
    {       // parameter is a NodeList, HtmlCollection, or Array
        if (element.length > 0)
            element = element[0];
        else
            return "";  // empty string
    }       // parameter is a NodeList, HtmlCollection, or Array

    // store the parameters in an object
    var strValue    = "";
    var returnString    = true;

    for (var j = 0; j < element.childNodes.length; j++)
    {           // loop through children of XML element
        var child   = element.childNodes[j];
        if (child.nodeType == 1)
        {       // sub element
            returnString    = false;

            // update the value of the named attribute
            var currValue   = parms[child.nodeName];
            if (currValue === undefined)
                currValue   = getObjFromXml(child);
            else
            {       // already defined, add to array
                if (!(currValue instanceof Array))
                {   // make it an array
                    currValue   = [currValue];
                }   // make it an array

                // add contents of child element to array of values
                currValue.push(getObjFromXml(child));
            }       // already defined, add to array
            parms[child.nodeName]   = currValue;
        }   // sub element
        else
        if (child.nodeType == 3)
        {   // text node
            strValue    += child.nodeValue;
        }   // text node
    }       // loop through children of current element

    // return a string or an object depending upon whether there were
    // sub-tags
    if (returnString)
        return  strValue;
    else
        return  parms;
}       // function getObjFromXml

/************************************************************************
 *  function popupLoading                                               *
 *                                                                      *
 *  Popup a "loading" indicator to the user.  This indicator warns the  *
 *  user that an extended operation has begun and the user should wait  *
 *  for the indicator to disappear before taking further actions in the *
 *  current dialog.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      element         an input element for positioning the popup      *
 *                      if this is null, position relative to last      *
 *                      element passed to this method                   *
 ************************************************************************/
function popupLoading(element)
{
    if (loaddiv == null)
    {       // indicator not currently displayed
        loaddiv = document.getElementById('loading');

        // if there is no "loading" division, create a default one
        if (loaddiv === null || loaddiv === undefined)
        {       // create missing division
            var body        = document.body;
            if (body)
            {
                var div = document.createElement('div');
                div.id      = 'loading';
                div.className   = 'popup';
                div.appendChild(document.createTextNode("Loading..."));
                body.appendChild(div);
                loaddiv     = div;
            }
        }       // create missing division

        if (loaddiv)
        {       // display loading indicator to user
            if (element === null)
                element     = loadelt;
            else
                loadelt     = element;
            var leftOffset  = getOffsetLeft(element);
            if (leftOffset > 500)
                leftOffset  -= 200;
            loaddiv.style.left  = leftOffset + "px";
            loaddiv.style.top   = (getOffsetTop(element) - 30) + 'px';
            loaddiv.style.display   = 'block';
        }       // load indicator to user
    }       // indicator not currently displayed
}       // function popupLoading

/************************************************************************
 *  function popupLoadingText                                           *
 *                                                                      *
 *  Popup a "loading" indicator to the user with application supplied   *
 *  text message.  This indicator warns the                             *
 *  user that an extended operation has begun and the user should wait  *
 *  for the indicator to disappear before taking further actions in the *
 *  current dialog.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      element         an input element for positioning the popup      *
 *                      if this is null, position relative to last      *
 *                      element passed to this method                   *
 *      text            string of text to display to user               *
 ************************************************************************/
function popupLoadingText(element,
                          text)
{
    if (loaddiv == null)
    {               // loading indicator not currently displayed
        loaddiv = document.getElementById('loading');

        // if there is no "loading" division, create a default one
        if (loaddiv === null || loaddiv === undefined)
        {           // create missing division
            var body        = document.body;
            var div     = document.createElement('div');
            div.id      = 'loading';
            div.className   = 'popup';
            div.appendChild(document.createTextNode("Loading..."));
            body.appendChild(div);
            loaddiv     = div;
        }           // create missing division

        if (loaddiv)
        {           // display loading indicator to user
            // replace text in loading division
            while(loaddiv.firstChild)
                loaddiv.removeChild(loaddiv.firstChild);
            loaddiv.appendChild(document.createTextNode(text));
            // position and display loading division
            if (element === null)
                element     = loadelt;
            else
                loadelt     = element;
            var leftOffset  = getOffsetLeft(element);
            if (leftOffset > 500)
                leftOffset  -= 200;
            loaddiv.style.left  = leftOffset + "px";
            loaddiv.style.top   = (getOffsetTop(element) - 30) + 'px';
            loaddiv.style.display   = 'block';
//alert("loaddiv: " + tagToString(loaddiv));
        }           // display loading indicator to user
    }               // indicator not currently displayed
}       // function popupLoadingText

/************************************************************************
 *  function hideRightColumn                                            *
 *                                                                      *
 *  Hide the right-hand notification column.                            *
 ************************************************************************/
function hideRightColumn()
{
    var mainSection             = document.getElementById('mainSection');
    var rightColumn             = document.getElementById('rightColumn');
    if (rightColumn)
        rightColumn.style.display   = 'none';
    if (mainSection)
        mainSection.style.width     = '100%';
}       // function hideRightColumn

/************************************************************************
 *  function hideLoading                                                *
 *                                                                      *
 *  Hide the "loading" indicator from the user.  This notifies the      *
 *  user that the extended operation has completed.                     *
 ************************************************************************/
function hideLoading()
{
    if (loaddiv)
    {       // indicator currently displayed
        loaddiv.style.display   = 'none';   // hide it
        loaddiv         = null;     // not being displayed
    }       // indicator currently displayed
}       // function hideLoading

/************************************************************************
 *  function popupAlert                                                 *
 *                                                                      *
 *  This function replaces the standard Javascript alert function       *
 *  but uses the msgDiv popup mechanism to permit customizing the       *
 *  appearance of the alert message.  The invoking web page must have   *
 *  a <form id='Msg$template'> providing the appearance of the alert    *
 *  message.                                                            *
 *                                                                      *
 *  Input:                                                              *
 *      msg             string text to display in the popup             *
 *      element         an element on the page to use for positioning   *
 ************************************************************************/
function popupAlert(msg, element)
{
    // display the message in a popup
    var parms   = {"template"   : "",
                   "msg"        : msg};
    displayDialog('Msg$template',
                  parms,
                  element,      // position relative to
                  null,         // button closes dialog
                  false);       // default show on open
}       // function popupAlert

/************************************************************************
 *  function createFromTemplate                                         *
 *                                                                      *
 *  This function creates a node and its subnodes as a clone of         *
 *  an HTML template, performing substitution of symbol values from     *
 *  an associative array object into the string elements of the node    *
 *  and its subnodes.                                                   *
 *                                                                      *
 *  Input:                                                              *
 *      template        an HTML node with children that represents      *
 *                      the logical structure to be reproduced          *
 *                      or the id attribute of such a node as a String  *
 *      parms           an associative array of symbol values to be     *
 *                      substituted into the text values and selected   *
 *                      attributes of the HTML structure.  Wherever a   *
 *                      symbol reference starting with a dollar sign ($)*
 *                      occurs in a string value the symbol reference is*
 *                      replaced by the corresponding value from parms. *
 *                                                                      *
 *  Returns:                                                            *
 *      HTML node with children                                         *
 ************************************************************************/
function createFromTemplate(template,
                            parms,
                            unused)
{
    var parmsText   = "";
    for (var name in parms)
        parmsText +=  name + "='" + parms[name] + "', ";
    if (debug == 'y')
    {
        alert("util.js: createFromTemplate: parms=" + parmsText);
    }

    var templateName    = template;
    if (typeof(template) == "string")
    {
        var ttemplate   = document.getElementById(template);
        if (ttemplate === null || ttemplate.cloneNode === undefined)
            throw("util.js: createFromTemplate: unable to find template with id='" + template + "'");
        template    = ttemplate;
    }
    else
    if (template === null || template.cloneNode === undefined)
        throw("util.js: createFromTemplate: template is null or not a node");
    else
    if (template.id)
        templateName    = template.id;

    // convert the entire template to an HTML string
    // make all substitutions in the string
    var text        = template.outerHTML;
    //alert("createFromTemplate: template=" + text);
    var messages    = '';
    if (text.length > 0)
    {           // have something to substitute into
        var chunks      = text.split('$');
        var retval      = chunks[0];    // part before first variable
        for (var i=1; i<chunks.length; i++)
        {       // process each chunk
            var chunk   = chunks[i];
            var result  = chunk.match(/^\w+/);
            var varname = result[0];
            if (typeof parms[varname] != 'undefined')
                retval  += parms[varname] + chunk.substring(varname.length);
            else
                messages    += "undefined parms['" + varname + "'], ";
        }       // process each chunk
    }           // have something to substitute into
    else
        retval      = text;

    if (messages != '')
        alert("util.js: createFromTemplate: templateName=" + templateName +
                " template='" + text + "'" +
                ' ' + messages +
                ' parms=' + parmsText);
    var newdiv;
    if (retval.substring(0,3) == '<tr')
    {
        var table   = document.createElement("TABLE");
        newdiv      = table.appendChild(document.createElement("TBODY"));
    }
    else
        newdiv      = document.createElement("DIV");
    newdiv.innerHTML    = retval;
    //alert("createFromTemplate: newdiv=" + newdiv.outerHTML);
    //alert("createFromTemplate: firstchild=" + newdiv.firstChild.outerHTML);
    if (newdiv.firstChild)
        return newdiv.firstChild;
    else
        throw("util.js: createFromTemplate: retval='" + retval + "'");
}       // function createFromTemplate

/************************************************************************
 *  function openFrame                                                  *
 *                                                                      *
 *  This is a utility function to display the page generated by a URL   *
 *  in an IFRAME occupying the half of the window.                      *
 *                                                                      *
 *  Input:                                                              *
 *      name    name of the frame                                       *
 *      url     URL of the page to display, if null the current contents*
 *              of the frame are left unchanged, but the dimensions may *
 *              be changed if the dimensions of the window have changed.*
 *      side    which half to open in: "left" or "right"                *
 *                                                                      *
 *  Returns:                                                            *
 *      the instance of Window for the <iframe>                         *
 ************************************************************************/
function openFrame(name, url, side)
{
    // accept mixed case side indicator
    side                    = side.toLowerCase();
    if (side != "left")
        side                = "right";

    // locate the window and document instances for the top window
    // of the application
    var win                 = window;
    while(win.frameElement)
        win                 = win.parent;
    var doc                 = win.document;

    // get global information maintained at the top window
    if (!('dialogZindex' in win))
        win.dialogZindex    = {left : 0, right : 0};
    if (!('dialogCount' in win))
        win.dialogCount     = 0;


    // get the instance of <iframe> to display by name
    iframe                  = doc.getElementById(name);
    if (iframe)
    {               // have existing element by name
        if (iframe.nodeName !== 'IFRAME')
            throw "util.js: openFrame: the element with id='" + name +
                        "' is not an <iframe>";
        else
            iframe.className    = side;
    }               // have existing element by name
    else
    {               // need to create new frame
        iframe              = doc.createElement("IFRAME");
        iframe.name         = name;
        iframe.id           = name;
        iframe.className    = side;
        iframe.onerror      = openFrameError;
        doc.body.appendChild(iframe);
    }               // need to create new frame

    iframe.opener           = window;   // parent of new dialog

    // identify the page to load into the frame
    if (url !== null && url != iframe.src)
    {
        if (url.substring(0,5) == 'http:')
            iframe.src      = '/badUrlForIFrame.php?src=' + url;
        else
            iframe.src      = url;
    }

    // get the dimensions of the root window
    var w                   = doc.documentElement.clientWidth;
    var h                   = doc.documentElement.clientHeight;

    if (win.dialogCount == 0)
    {   // resize the main page to only occupy the left half of the window
        var transcription   = doc.getElementById('transcription');
        if (transcription)
        {           // frame for left side of window
            transcription.style.width   = w/2 + "px";
            transcription.style.height  = h + "px";
        }           // frame for left side of window
    }   // resize the main page to only occupy the left half of the window

    // size the dialog to half the width of the window and position it
    // in the requested half of the window
    iframe.style.width      = w/2 + "px";
    iframe.style.height     = h + "px";
    iframe.style.position   = "fixed";
    if (side == "left")
        iframe.style.left   = "0px";
    else
        iframe.style.left   = w/2 + "px";
    var zindex              = win.dialogZindex[side] + 2;
    iframe.style.zIndex     = zindex;   // move iframe to front
    iframe.style.top        = 0 + "px";
    iframe.style.visibility = "visible";

    // update the global information about half page dialogs
    win.dialogZindex[side]  = zindex;
    win.dialogCount++;

    return iframe.contentWindow;
}       // function openFrame

function openFrameError()
{
    alert("open frame id='" + this.id + "' failed");
}

/************************************************************************
 *  function closeFrame                                                 *
 *                                                                      *
 *  This is a utility function to close the current frame.  It handles  *
 *  the case where the current window is in an <iframe>                 *
 ************************************************************************/
function closeFrame(lastChoice)
{
    var iframe              = window.frameElement;
    if (iframe)
    {                   // current window is in an iframe
        var msg             = '';

        // locate the window and document instances for the top window
        var topwin          = window.top;
        var doc             = topwin.document;

        var frameInfo       = '';
        for (var i = 0; i < topwin.frames.length; i++)
        {
            var frame       = topwin.frames[i];
            try {
            var felt        = frame.frameElement;
            frameInfo       += i + ' name=' + frame.name +
                                " <" + felt.nodeName +
                                ' id="' + felt.id + '"> ';
            } catch(e) {};
        }

        if (topwin.dialogCount == 1)
        {               // closing last dialog
            // resize the display of the transcription
            var w           = doc.documentElement.clientWidth;
            var h           = doc.documentElement.clientHeight;
            var transcription       = doc.getElementById('transcription');
            if (transcription)
            {           // restore main window to full width
                transcription.style.width   = w + "px";
                transcription.style.height  = h + "px";
            }           // restore main window to full width
        }               // closing last dialog

        topwin.dialogCount--;
        //alert("util.js: closeFrame: topwin.dialogCount=" + win.dialogCount +
        //  " iframe.className='" + iframe.className + "' frames=" + frameInfo);
        topwin.dialogZindex[iframe.className]   -= 2;

        // this has to be done right at the end because iframe is the
        // 'this' parameter of this function
        var father  = iframe.parentNode;
        father.removeChild(iframe);
    }                   // current window is in an iframe
    else
    {                   // not in an iframe
        if (window.opener === null)
        {               // not opened from another window
            if (history.length > 1)
                history.back();
            else
            {
                if (lastChoice == undefined)
                    location    = "/genealogy.php?lang=" + lang;
                else
                    location    = lastChoice;
            }
        }               // not opened from another window
        else
        {
            window.close();
        }
    }                   // not in an iframe
}       // function closeFrame

/************************************************************************
 *  polyfill for CustomEvent                                            *
 *                                                                      *
 *  Simulate support for CustomEvent constructor on IE 9                *
 ************************************************************************/
(function () {
    function CustomEvent ( event, params ) {
      params = params || { bubbles: false, cancelable: false, detail: null };
      var evt = document.createEvent( 'CustomEvent' );
      evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
      return evt;
    }

    window.CustomEvent = CustomEvent;
})();
