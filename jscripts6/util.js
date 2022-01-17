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
 *      2020/11/21      move function showImage to common library       *
 *      2020/12/15      correct handling of parms passed by GET         *
 *      2020/12/26      eliminate duplicate URL parm testing            *
 *      2021/01/12      drop support for IE 9 & 10                      *
 *      2021/03/14      add ES2015 export                               *
 *                      if help is requested for a hidden element       *
 *                      find its closest enclosing non-hidden parent    *
 *      2021/03/30      remove changeDiv, it was obsoleted years ago    *
 *      2021/07/03      correct vertical position of popup dialogs      *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
 /*  global tinyMCE, tinyMCEparms                                       */

/************************************************************************
 *  constants for letter key codes                                      *
 ************************************************************************/
// movement keys
export const KEY_BSPACE     =  8;
export const KEY_TAB        =  9;
export const KEY_ENTER      = 13;
export const KEY_SHIFT      = 16;
export const KEY_ESC        = 27;
export const KEY_PAGEUP     = 33;
export const KEY_PAGEDOWN   = 34;
export const KEY_END        = 35;
export const KEY_HOME       = 36;
export const ARROW_LEFT     = 37;
export const ARROW_UP       = 38;
export const ARROW_RIGHT    = 39;
export const ARROW_DOWN     = 40;
export const KEY_INSERT     = 45;
export const KEY_DELETE     = 46;
// alphabetic keys
export const LTR_A          = 65;
export const LTR_B          = 66;
export const LTR_C          = 67;
export const LTR_D          = 68;
export const LTR_E          = 69;
export const LTR_F          = 70;
export const LTR_G          = 71;
export const LTR_H          = 72;
export const LTR_I          = 73;
export const LTR_J          = 74;
export const LTR_K          = 75;
export const LTR_L          = 76;
export const LTR_M          = 77;
export const LTR_N          = 78;
export const LTR_O          = 79;
export const LTR_P          = 80;
export const LTR_Q          = 81;
export const LTR_R          = 82;
export const LTR_S          = 83;
export const LTR_T          = 84;
export const LTR_U          = 85;
export const LTR_V          = 86;
export const LTR_W          = 87;
export const LTR_X          = 88;
export const LTR_Y          = 89;
export const LTR_Z          = 90;
// Windoze button
export const KEY_START      = 91;
// Function Keys
export const KEY_F1         = 112;
export const KEY_F2         = 113;
export const KEY_F3         = 114;
export const KEY_F4         = 115;
export const KEY_F5         = 116;
export const KEY_F6         = 117;
export const KEY_F7         = 118;
export const KEY_F8         = 119;
export const KEY_F9         = 120;
export const KEY_F10        = 121;
export const KEY_F11        = 122;
export const KEY_F12        = 123;

// set global defaults
var activateMCE             = true;
var lang                    = 'en';

/************************************************************************
 *  Feature Detection                                                   *
 *                                                                      *
 *  Determine which features are supported by the implementation        *
 *  of ECMAScript (JavaScript) in the user's browser.                   *
 ************************************************************************/
export const unicodeSupported   = isUnicodeSupported();

export function isUnicodeSupported()
{
    let re                      = new RegExp('\u{61}', 'u');
    return 'unicode' in re && re.unicode;
}           // function isUnicodeSupported();

/************************************************************************
 *  Audio Output                                                        *
 ************************************************************************/

export var audioContext         = null;
if ("AudioContext" in window)
{
    audioContext                = new AudioContext();
}
else
{
    alert("AudioContext not supported.  Upgrade your browser.")
}

/************************************************************************
 *  function getArgs                                                    *
 *                                                                      *
 *  Extract the arguments from the location URL when invoked by         *
 *  method='get'                                                        *
 *                                                                      *
 *  Returns:                                                            *
 *      associative array of arguments                                  *
 ************************************************************************/
export function getArgs()
{
    let args    = new Object();
    let query   = location.search.substring(1); // search excluding '?'
    let pairs   = query.split("&");     // split on ampersands
    for (let i = 0; i < pairs.length; i++)
    {       // loop through all pairs
        let pos = pairs[i].indexOf('=');
        if (pos == -1)
            continue;
        // argument names are case-insensitive
        let name    = pairs[i].substring(0, pos).toLowerCase();
        let value   = pairs[i].substring(pos + 1);
        value       = decodeURIComponent(value);
        args[name]  = value;

        // set the global diagnostic flag
        switch(name)
        {
            case 'debug':
                debug               = value;
                break;

            case 'text':
                activateMCE         = false;
                break;

            case 'lang':
                lang                = value;
                break;

        }
    }       // loop through all pairs

    return args;
}       // function getArgs

/************************************************************************
 *  global variable args                                                *
 *                                                                      *
 *  Make arguments from the search portion of the URL available to all  *
 *  scripts.                                                            *
 ************************************************************************/
export var args     = getArgs();

/************************************************************************
 * specify the style for tinyMCE editing                                *
 *                                                                      *
 *  tinyMCEparms is a configuration structure which is defined in the   *
 *  common page and dialog templates.  tinyMCE is defined by the        *
 *  tinyMCE script, so this is a test for the presence of that script.  *
 ************************************************************************/

if (activateMCE && tinyMCEparms && typeof tinyMCE !== 'undefined')
{
    // alert("tinyMCEparms=" + JSON.stringify(tinyMCEparms));
    tinyMCE.init(tinyMCEparms);
}

/************************************************************************
 *  Global warning that Microsoft Internet Explorer doesn't work        *
 ************************************************************************/
try {
    eval('let x = "y";');
} catch(error)
{
    alert("Your Browser: " + navigator.userAgent +
      " does not support ECMA ES2015 and many services may not work. " +
      "Upgrade to Edge version 12 or later, or use ANY other browser. " +
      "Many scripts on this site will fail or report syntax or other errors. " + error.message);
}

if (!('addEventListener' in document))
{
    alert("Your Browser: " + navigator.userAgent +
      " does not support html element method addEventListener and many services may not work. " +
      "Upgrade to Edge version 12 or later, or use ANY other browser. ");
}

/************************************************************************
 *  loaddiv                                                             *
 *                                                                      *
 *  This instance of HTMLElement contains the "loading" indicator when  *
 *  a script is waiting for an AJAX response from the server.           *
 ************************************************************************/
var loaddiv          = null;

/************************************************************************
 *  loadelt                                                             *
 *                                                                      *
 *  This is the instance of HTMLElement which is to be updated when     *
 *  the AJAX response is received from the server.                      *
 ************************************************************************/
var loadelt          = null;

/************************************************************************
 *  dialogDiv                                                           *
 *                                                                      *
 *  The current modal dialog division displayed in a popup              *
 ************************************************************************/
var dialogDiv        = null;

/************************************************************************
 *  global variables used for mouse drag on a dialog                    *
 *                                                                      *
 *  This code does not currently work.                                  *
 ************************************************************************/
var dragok   = false;    // if true drag in progress
var dy;                  // distance from top of dialog to mouse
var dx;                  // distance from left of dialog to mouse

/************************************************************************
 *  debug                                                               *
 *                                                                      *
 *  String containing the setting of the debug option used to control   *
 *  output of diagnostic information.  This is set by the script        *
 *  parameter Debug, which can be specified on any script either        *
 *  by method='get' or method='post'                                    *
 ************************************************************************/
export var debug            = 'n';  // default to no debug

/************************************************************************
 *  helpDiv                                                             *
 *                                                                      *
 *  If a help popup is currently open this points at it.                *
 ************************************************************************/
var helpDiv                 = null;

/************************************************************************
 *  helpDelayTimer                                                      *
 *                                                                      *
 *  A timer to control when help is displayed as a result of            *
 *  mouse over events                                                   *
 ************************************************************************/
export var helpDelayTimer   = null;

/************************************************************************
 *  iframe                                                              *
 *                                                                      *
 *  Global variable to hold a reference to a displayed dialog in an     *
 *  instance of HTMLIFrameElement occupying half of the window.         *
 ************************************************************************/
export var iframe           = null;

/************************************************************************
 *  function getHelpPopupOption                                         *
 *                                                                      *
 *  Determine whether or not this user wishes to see help popups.       *
 *                                                                      *
 *  Returns:                                                            *
 *      true if the user wishes to see help popups, otherwise false     *
 ************************************************************************/
export function getHelpPopupOption()
{
    // suppress help popup based upon cookie value
    let allcookies = document.cookie;
    if (allcookies.length == 0)
        return true;

    // Break the string of all cookies into individual cookie strings
    // Then loop through the cookie strings, looking for our name
    let cookies     = allcookies.split(';');
    let cookieval   = null;
    for(let i = 0; i < cookies.length; i++)
    {
        let cookieparts = cookies[i].trim().split('=');

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
        let a = cookieval.split('&');

        // Break each pair into an array with 2 elements
        for(let i=0; i < a.length; i++)
        {           // loop through name/value pairs
            let keyval = a[i].split(':');
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
export var popupHelpOption = true;

/************************************************************************
 *  global variable currentUser                                         *
 *                                                                      *
 *  This global variable contains the record for the current user as    *
 *  an XML node with children.                                          *
 ************************************************************************/
export var currentUser = null;

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
    let cookiesList = tempCookies.split(';');
//    let txt     = 'util.js: Cookies: length=' + cookiesList.length + ' ';
    for(let i = 0; i < cookiesList.length; i++)
    {           // loop through cookies
        let cookieParts = cookiesList[i].trim().split('=');
        if (cookieParts.length > 1)
        {       // cookie contains '='
            let cookieName  = cookieParts[0];
//            txt += cookieName + '=[';
            let cookieVal   = decodeURIComponent(cookieParts[1]);
            let valParts    = cookieVal.trim().split('&');
            let valArray    = [];
            for(let j = 0; j < valParts.length; j++)
            {       // loop through sub-values
                let t       = valParts[j].trim().split(':');
                if (t.length > 1)
                {   // name:value
                    valArray[t[0]]  = t[1];
//                    txt     += t[0] + "='" + t[1] + "'";
                }   // name:value
                else
                {   // value
                    valArray.push(t[0]);
//                    txt     += "'" + t[0]+ "'";
                }   // value
//                txt += ",";
            }       // loop through sub-values;
            cookies[cookieName] = valArray;
//            txt     += '];';
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
export function addOption(select, text, value)
{
    // create a new HTML Option object and add it to the Select
    let newOption       = document.createElement("option");
    select.appendChild(newOption);
    newOption.text      = text;     // ie7 demands done after append
    newOption.value     = value;
    return newOption;
}       //  function addOption

/************************************************************************
 *  function show                                                       *
 *                                                                      *
 *  Make the element identified by the identifier visible.              *
 *  This is primarily used to display popup dialogs.                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        an HTML element instance                            *
 *      id          value of the id parameter of the element to be      *
 *                  made visible, or                                    *
 *                  an HTML element instance                            *
 ************************************************************************/
export function show(id)
{
    let element                     = null;
    if (this instanceof Element)
        element                     = this;
    else
    if (id instanceof Element)
        element                     = id;
    else
        element                     = document.getElementById(id);

    if (element)
    {
        element.style.display       = 'block';
        element.style.visibility    = 'visible';
        element.scrollIntoView();
    
        // set the focus on the first button in the dialog
        // displayDialog ensures that even if the dialog designer forgot
        // to include any buttons at least one is always present
        let buttons = element.getElementsByTagName('BUTTON');
        if (buttons.length > 0)
            buttons[0].focus();
    }
}   // function show

/************************************************************************
 *  function getOffsetLeft                                              *
 *                                                                      *
 *  Get the offsetLeft of an HTML element relative to the page.         *
 *                                                                      *
 *  Input:                                                              *
 *      elt     an element from an HTML form                            *
 ************************************************************************/
export function getOffsetLeft(elt)
{
    let left    = 0;
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
export function getOffsetRight(elt)
{
    let left    = 0;
    let right   = elt.offsetWidth;
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
export function getOffsetTop(elt)
{
    // note that "top" is a reserved word
    let y           = 0;
    let main        = document.getElementsByTagName('main');
    if (main.length > 0)
        main        = main[0];
    while(elt)
    {
        y           += elt.offsetTop;
        if (elt.scrollTop > 0)
        {
            main    = elt;
            break;
        }
        elt         = elt.offsetParent;
    }       // increment up to top element
    if (main)
        y           -= main.scrollTop;
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
    let ptags           = helpDiv.getElementsByTagName("TABLE");
    if (ptags.length > 0)
        return;

    // add the abbreviation expansion documentation to the help panel
    let p1              = document.createElement('P');
    p1.className        = 'label';
    let text            = document.getElementById('abbrMessage').innerHTML;
    p1.appendChild(document.createTextNode(text));
    helpDiv.appendChild(p1);
    let tbl             = document.createElement('TABLE');
    helpDiv.appendChild(tbl);
    let numCols         = 3;
    let col             = 0;
    for(let abbr in abbrTable)
    {               // run through abbreviations
        let tr;
        if (col == 0)
        {
            tr          = tbl.appendChild(document.createElement('TR'));
            col         = numCols;
        }
        col--;
        let td1         = tr.appendChild(document.createElement('TH'));
        td1.className   = "left";
        td1.appendChild(document.createTextNode(abbr));
        let td2         = tr.appendChild(document.createElement('TD'));
        td2.appendChild(document.createTextNode(abbrTable[abbr]));
    }               // run through abbreviations
}       // function addHelpAbbrs

/************************************************************************
 *  function showHelp                                                   *
 *                                                                      *
 *  Display the current help text;                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element near which to display the help         *
 *      newDiv      HTML <div> element to display                       *
 ************************************************************************/
export function showHelp(newDiv)
{
    //console.log("util.js: showHelp: newDiv=" + newDiv.outerHTML);
    helpDiv             = newDiv;
    show.call(newDiv)
}       // function showHelp

/************************************************************************
 *  function hideHelp                                                   *
 *                                                                      *
 *  Hide the current help text.                                         *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element                                        *
 ************************************************************************/
export function hideHelp()
{
    //console.log("util.js: hideHelp: this=" + this.outerHTML);
    if (helpDiv)
    {                       // a help balloon is displayed
        //console.log("util.js: hideHelp: helpDiv=" + helpDiv.outerHTML);
        helpDiv.style.display   = 'none';
        helpDiv                 = null;
    }                       // a help balloon is displayed
//    else
//        console.log("util.js: hideHelp: helpDiv is null");
}       // function hideHelp

/************************************************************************
 *  function keyDown                                                    *
 *                                                                      *
 *  Handle key strokes in text input fields.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    an HTML element                                         *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
export function keyDown(ev)
{
    let code            = ev.key;

    // hide the help balloon on any keystroke
    hideHelp.call(this);
    clearTimeout(helpDelayTimer);   // clear pending help display
    helpDelayTimer      = null;

    // take action based upon code
    switch (code)
    {
        case "F1":
        {
            displayHelp.call(this);      // display help page
            ev.preventDefault();
            ev.stopPropagation();
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
 *      this    element the HTML tag for which help is to be displayed  *
 ************************************************************************/
export function displayHelp()
{
    let helpDivName         = this.helpDivName;

    helpDiv                 = document.getElementById(helpDivName);
    // display the help division if found
    if (helpDiv && (helpDiv != this))
    {                   // have a help division to display
        if (this.maxLength && this.maxLength > 0)
        {
            let spanId      = helpDivName + 'Maxlen';
            let oldElt      = document.getElementById(spanId);
            if (oldElt === null)
            {           // information not already appended to help
                let text    = document.getElementById('maxLengthMessage').innerHTML;
                let span    = document.createElement('span');
                span.setAttribute('id', spanId);
                text        = document.createTextNode(
                              text.replace('$maxlength', this.maxLength));
                span.appendChild(text);
                helpDiv.appendChild(span);
            }           // information not already appended to help
        }

        // If presentation style requires capitalization,
        // report it in help
        let textTransform   = "";
        if (window.getComputedStyle)
        {               // browser supports W3C API
            let style       = window.getComputedStyle(this, null);
            textTransform   = style.textTransform;
        }               // browser supports W3C API
        else
        if (this.currentStyle)
        {               // browser supports IE API
            textTransform   = this.currentStyle.textTransform;
        }               // browser supports IE API

        // notify the user if text in the field is capitalized
        if (textTransform == "capitalize")
        {
            if (!helpDiv.capitalized)
            {           // add text to help
                let text    = document.getElementById('capitalizationMessage').innerHTML;
                helpDiv.appendChild(document.createTextNode(text));
                helpDiv.capitalized = true;
            }           // add text to help
        }

        // if the field has automatic abbreviation expansion
        // describe it
        if (this.abbrTbl)
            addHelpAbbrs(helpDiv, this.abbrTbl);

        // display the help balloon in an appropriate place on the page
        let tableWidth          = window.innerWidth;
        let elt                 = this;
        while (getOffsetTop(elt) == 0)
            elt                 = elt.parentNode;
        if (getOffsetLeft(elt) < Math.floor(tableWidth/2))
            helpDiv.style.left  = (getOffsetLeft(elt) + 50) + 'px';
        else
            helpDiv.style.left  = (getOffsetLeft(elt) -
                                Math.floor(tableWidth/2)) + 'px';
        helpDiv.style.top       = (getOffsetTop(elt) +
                                       elt.offsetHeight + 5) + 'px';
        // so key strokes in balloon will close window
        helpDiv.onkeydown       = keyDown;
        helpDiv.style.display   = 'block';
        helpDiv.style.visibility= 'visible';
        helpDiv.scrollIntoView();
    
        // set the focus on the first button in the dialog
        // displayDialog ensures that even if the dialog designer forgot
        // to include any buttons at least one is always present
        let buttons             = helpDiv.getElementsByTagName('BUTTON');
        if (buttons.length > 0)
            buttons[0].focus();
    }                   // have a help division to display
    else
        console.log("util.js: displayHelp: Logic Error, Cannot find <div id='" + helpDivName + "'>");
}       // function displayHelp

/************************************************************************
 *  function openSignon                                                 *
 *                                                                      *
 *  This method is called to open the signon dialog if the user is not  *
 *  yet signed on and requests access to a family tree.                 *
 *                                                                      *
 *  Called by: FamilyTree/Names.js & Person.js                          *
 ************************************************************************/
export function openSignon()
{
    let server  = location.protocol + "//" +
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
 *  function eltMouseOver                                               *
 *                                                                      *
 *  This function is called if the mouse moves over an input element    *
 *  on the invoking page.  Delay popping up the help balloon for        *
 *  two seconds.                                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element                                        *
 *      ev          MouseOver Event                                     *
 ************************************************************************/
export function eltMouseOver(ev)
{
    //console.log("util.js: eltMouseOver: this=" + this.outerHTML);
    ev.stopPropagation();
    if (popupHelpOption)
    {           // this user wants help
        //console.log("util.js: eltMouseOver: this=" + this.outerHTML);
        if (this.nodeName.toUpperCase() == 'FIELDSET')
            return;

        // in some cases the mouseover event is against the table cell
        // containing the input element.  Locate the first element node
        // under the cell to display help for
        let helpElt             = this;
        if (!this.helpDivName && 
            (this.nodeName.toUpperCase() == 'TD' ||
             this.nodeName.toUpperCase() == 'DIV'))
        {       // mouseover defined for the cell containing the element
            for (let i = 0; i < this.childNodes.length; i++)
            {               // loop through children of this cell
                let cNode       = this.childNodes[i];
                if (cNode.nodeType == 1 && (cNode.name || cNode.id))
                {           // element
                    helpElt     = cNode;
                    break;
                }           // element
            }               // loop through children of this
        }       // mouseover defined for the cell containing the element

        if (helpElt)
        {
            helpDelayTimer  = setTimeout(popupHelp.bind(helpElt), 2000);
            //console.log("util.js: eltMouseOver: start timer " + helpDelayTimer);
        }
        else
            console.log("util.js: eltMouseOver: helpElt not resolved");
    }                       // this user wants help
}       // function eltMouseOver

/************************************************************************
 *  function simpleMouseOver                                            *
 *                                                                      *
 *  This function is called if the mouse moves over an element          *
 *  on the invoking page.  Delay invoking a callback for                *
 *  two seconds.                                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML tag                                            *
 *      ev          MouseOver Event                                     *
 *      callback    optional alternative callback function              *
 ************************************************************************/
export function simpleMouseOver(ev, callback)
{
    if (callback)
        helpDelayTimer  = setTimeout(callback.bind(this), 2000);
    else
        helpDelayTimer  = setTimeout(popupHelp.bind(this), 2000);
}       // function eltMouseOver


/************************************************************************
 *  function popupHelpHandler                                           *
 *                                                                      *
 *  This method is called if the mouse is clicked on the element.       *
 *  It shows the associated help division.                              *
 *  This is used because mobile devices do not support the mouseover    *
 *  and mouseout events.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML tag                                            *
 *      ev          MouseOver Event                                     *
 ************************************************************************/
export function popupHelpHandler(ev)
{
    if (popupHelpOption)
    {                           // user accepts popup help
        popupHelp.call(this);
    }                           // user accepts popup help
}       // function popupHelpHandler

/************************************************************************
 *  function popupHelp                                                  *
 *                                                                      *
 *  This function is called if the mouse is held over an input element  *
 *  on the invoking page for more than 2 seconds, or the mouse is       *
 *  clicked on the element.  It shows the associated help division.     *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element                                        *
 ************************************************************************/
export function popupHelp()
{
    console.log("util.js: popupHelp: this=" + this.outerHTML);
    if (!this.helpAlreadyDisplayed)
    {                       // help for this element not displayed
        displayHelp.call(this);
        this.helpAlreadyDisplayed    = true; // only once
    }                       // help for this element not displayed
}       // function popupHelp


/************************************************************************
 *  function eltMouseOut                                                *
 *                                                                      *
 *  This function is called if the mouse moves off an input element     *
 *  on the invoking page.  The help balloon, if any, remains up for     *
 *  a further 2 seconds to permit access to links within the help text. *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element                                        *
 ************************************************************************/
export function eltMouseOut()
{
    //console.log("util.js: eltMouseOut: this=" + this.outerHTML);
    clearTimeout(helpDelayTimer);
    helpDelayTimer  = setTimeout(hideHelp.bind(this), 2000);
}       // function eltMouseOut

/************************************************************************
 *  function actMouseOverHelp                                           *
 *                                                                      *
 *  This function is called to activate mouse hover initiated popup     *
 *  help display for an element.                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        an HTML element, usually an input element           *
 *      divName     optionally specify the id of the help division      *
 ************************************************************************/
export function actMouseOverHelp(divName)
{
    let element         = this;
    if (this.nodeName.toUpperCase() == 'TEXTAREA')
    {               // textareas are disabled by TinyMCE
        element         = this.parentNode;
    }               // textareas are disabled by TinyMCE
        
    element.addEventListener('mouseover',   eltMouseOver);
    element.addEventListener('mouseout',    eltMouseOut);
    element.addEventListener('click',       popupHelpHandler);
    if (divName && typeof divName == 'string')
    {
        element.helpDivName     = divName;
        let helpDiv             = document.getElementById(divName);
        if (!helpDiv)
        {                   // choice not found
            console.log("util.js: actMouseOverHelp: " +
                                "explicit Help Div Name='" + divName + 
                                "' for element with id '" + name +
                                "' not found in page")
        }
        return true;
    }

    let name                = this.name;
    let forid               = this.getAttribute("for");
    if (forid && forid.length > 0)
    {                   // label tag has a for attribute
        name                = forid;
    }
    else
    if (name === undefined || name === null || name == '')
        name                = this.id;

    if (name && name.length > 0)
    {                   // this has a name
        let regexp          = /^[a-zA-Z_]+/;
        let matches         = name.match(regexp);
        if (matches)
        {               // got leading alphabetic portion
            name                    = matches[0];
            let helpDivName         = 'Help' + name;
            let helpDiv             = document.getElementById(helpDivName);
            if (!helpDiv)
            {                   // first choice not found
                console.log("util.js: actMouseOverHelp: " +
                                "Help Div Name='" + helpDivName + 
                                "' for element with id '" + name +
                                "' not found in page")
                // if cannot find division with name prefixed with 'Help'
                // then try without
                if (!helpDiv && helpDivName.length > 4)
                {
                    helpDivName     = helpDivName.substr(4);
                    helpDiv         = document.getElementById(helpDivName);
                }
            }                   // first choice not found
            
            if (helpDiv)
            {
                element.helpDivName     = helpDivName;
                return true;
            }
            else
            {
                console.log("util.js: actMouseOverHelp: " +
                                "Help Div Name='" + helpDivName + 
                                "' for element with id '" + name +
                                "' not found in page")
                return false;
            }

        }               // got leading alphabetic portion
    }                   // this has a name

    // no source for name
    //console.log("util.js: actMouseOverHelp: " +
    //                    "cannot get helpDivName for " +
    //                    this.outerHTML);
    return false;
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
export function displayMenu(ev)
{
    ev.stopPropagation();
    let menu                    = document.getElementById('menu');

    // ensure the menu is hidden before modifying it
    menu.style.display          = 'none';
    menu.style.position         = 'absolute';
    menu.style.visibility       = 'hidden';
    menu.style.display          = 'block';

    // display the menu offset from the main menu button
    let element                 = document.getElementById('menuButton');
    let leftOffset              = getOffsetLeft(element);
    let rightOffset             = getOffsetRight(element);

    let dialogWidth             = menu.clientWidth;
    if (leftOffset - dialogWidth < 10)
        leftOffset              = rightOffset + 10;
    else
        leftOffset              = leftOffset - dialogWidth - 10;
    menu.style.left             = leftOffset + "px";
    menu.style.top              = (getOffsetTop(element) + 10) + 'px';

    let anchors     = menu.getElementsByTagName('a');
    let previous    = anchors[anchors.length - 1];
    for(let i = 0; i < anchors.length; i++)
    {           // loop through children
        let anchor              = anchors[i];
        previous.nextAnchor     = anchor;
        anchor.prevAnchor       = previous;
        previous                = anchor;
        anchor.addEventListener("keydown", keyDownMenu);
    }           // loop through children
    menu.style.display          = 'block';
    menu.style.visibility       = 'visible';
    menu.scrollIntoView();
    let help                    = document.getElementById('menuhelp');
    if (help)
        help.focus();

    dialogDiv                   = menu;

    return dialogDiv;
}       // function displayMenu

/************************************************************************
 *  function displayDialog                                              *
 *                                                                      *
 *  This function displays a customized dialog in a popup               *
 *                                                                      *
 *  Input:                                                              *
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
export function displayDialog(templateId,
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
    let dialog                  = document.getElementById('msgDiv');
    if (dialog === null)
    {               // belt and suspenders
        dialog                  = document.createElement('div');
        dialog.id               = 'msgDiv';
        dialog.className        = 'balloon';
        document.body.appendChild(dialog);
    }               // belt and susenders

    // ensure the dialog is hidden before modifying it
    dialog.style.display        = 'none';
    dialog.style.position       = 'absolute';
    dialog.style.visibility     = 'hidden';
    dialog.style.display        = 'block';

    let template                = null;
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
        let form                = createFromTemplate(template,
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
        let buttons             = dialog.getElementsByTagName('BUTTON');
        let button;
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
                for (let i = 0; i < buttons.length; i++)
                    if (i < action.length)
                        buttons[i].onclick  = action[i];
                    else
                        buttons[i].onclick  = hideDialog;

            }       // array of actions
            else
            {       // single action for first button
                buttons[0].onclick  = action;
                for (let i = 1; i < buttons.length; i++)
                    buttons[i].onclick  = hideDialog;
            }       // single action for first button
        }
        else
        {       // default action, every button closes dialog
            for (let i = 0; i < buttons.length; i++)
                buttons[i].onclick  = hideDialog;
        }       // default action, every button closes dialog

        // display the dialog offset from the requesting button
        let topOffset           = 0;
        let leftOffset          = 0;
        let rightOffset         = 0;
        if (element)
        {
            topOffset           = getOffsetTop(element);
            leftOffset          = getOffsetLeft(element);
            rightOffset         = getOffsetRight(element);
        }
        let pane                = document.getElementById('transcription');
        if (pane === null)
            pane                = document.body;

        let dialogWidth         = dialog.clientWidth;
        let dialogHeight        = dialog.clientHeight;
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
        dialog.addEventListener('click', stopProp);

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
 *      ev          mouse click Event                                   *
 ************************************************************************/
export function hideDialog(ev)
{
    ev.stopPropagation();
    // no longer displaying the modal dialog popup
    if (dialogDiv)
        dialogDiv.style.display = 'none';   // hide`
    dialogDiv                   = null;
    return null;
}       // function hideDialog

/************************************************************************
 *  function dialogMouseDown                                            *
 *                                                                      *
 *  This is the onmousedown handler for a dialog.  It prepares for      *
 *  dragging the dialog.                                                *
 *                                                                      *
 *  Input:                                                              *
 *      this    the top element of the dialog                           *
 *      ev      instance of MouseDown Event                             *
 ************************************************************************/
function dialogMouseDown(ev)
{
    console.log('util.js: dialogMouseDown: this=' + this.outerHTML);
    if (!ev)
        ev              = window.event;
    dragok              = true;
    dx                  = parseInt(this.style.left+0) - event.clientX;
    dy                  = parseInt(this.style.top+0)  - event.clientY;
    // only have mousemove handler while dragging
    this.onmousemove    = dialogMouseMove;
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
 *      ev      instance of MouseMove Event                             *
 ************************************************************************/
function dialogMouseMove(ev)
{
    console.log('util.js: dialogMouseMove: this=' + this.outerHTML);
    if (!ev)
        ev              = window.event;
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
    console.log('util.js: dialogMouseUp: this=' + this.outerHTML);
    dragok              = false;
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
 *      ev      mouse click Event                                       *
 ************************************************************************/
export function documentOnClick(ev)
{
    console.log("util.js: documentOnClick:");
    if (dialogDiv)
    {       // a dialog balloon is displayed
        console.log("util.js: documentOnClick: dialogDiv=" +
                    dialogDiv.outerHTML);
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
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
export function keyDownPaging(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev                  = window.event;    // IE
    }       // browser is not W3C compliant
    let code                = ev.key;

    // take action based upon code
    switch (code)
    {
        case "f":
        case "F":
        {
            if (ev.ctrlKey)
            {
                let element         = document.getElementById('menuButton');
                element.click();
                ev.preventDefault();
                ev.stopPropagation();
                return false;       // suppress default action
            }
            break;
        }

        case "F10":
        {
            let element         = document.getElementById('menuButton');
            element.click();
            ev.preventDefault();
            ev.stopPropagation();
            return false;       // suppress default action
        }       // F10

        case "PageDown":    // page down
        {
            let element     = document.getElementById('topNext');
            if (element)
            {               // topNext exists
                for(let child   = element.firstChild;
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
            let element     = document.getElementById('topPrev');
            if (element)
            {               // topPrev exists
                for(let child   = element.firstChild;
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
 *      this    HTML element                                            *
 *      ev      W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
export function keyDownMenu(ev)
{
    if (!ev)
    {       // browser is not W3C compliant
        ev       =  window.event;    // IE
    }       // browser is not W3C compliant
    let code                = ev.key;

    // take action based upon code
    switch (code)
    {
        case "ArrowDown":   // arrow down
        {
            this.nextAnchor.focus();
            ev.preventDefault();
            ev.stopPropagation();
            return false;   // suppress default action
        }                   // page down

        case "ArrowUp":     // arrow up
        {
            this.prevAnchor.focus();
            ev.preventDefault();
            ev.stopPropagation();
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
/* global FB */
export function statusChangeCallback(response)
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
 *      this        instance of Window                                  *
 *      event       instance of Event containing load event             *
 ************************************************************************/
window.addEventListener("load",     commonInit);
window.addEventListener("resize",   commonResize);

export function commonInit(ev)
{
    document.addEventListener("click", documentOnClick);
    document.addEventListener("keydown", keyDownPaging);

    // set onclick action for the menu button
    let menuButton          = document.getElementById('menuButton');
    //let menuWidth           = 0
    if (menuButton)
    {
        menuButton.addEventListener('click', displayMenu);
        //menuWidth           = menuButton.offsetWidth;
    }

    let logo                = document.getElementById('logo');
    //let logoWidth           = 0
    if (logo)
    {
        logo.addEventListener('click', displayMenu);
        //logoWidth           = logo.offsetWidth;
    }

//    let advert              = document.getElementById('advertSpan');
//    let advertWidth         = 500
//    if (advert)
//    {
//        advertWidth         = Math.max(advert.offsetWidth, 500);
//    }

//  let menusWidth          = menuWidth + logoWidth + advertWidth;

    // facebook support
    if (typeof(FB) != 'undefined')
        FB.getLoginStatus(function(response) {
            statusChangeCallback(response);
        });

    // scroll main portion of page if it does not fit without scrolling
    let headSection             = document.getElementById('headSection');
    let mainSection             = document.getElementById('mainSection');
    let mainHeight              = mainSection.offsetHeight;
    let windHeight              = window.innerHeight;
    let headHeight              = 0;
    let headWidth               = 0;
    if (headSection)
    {
        headHeight              = headSection.offsetHeight;
        headWidth               = headSection.offsetWidth;
        if (mainHeight + headHeight > windHeight)
        {
            mainSection.style.height    = (windHeight - headHeight - 12) + 'px';
            headSection.style.width     = (headWidth - 10) + 'px';
        }
    }                           // headSection defined in template

    let rightColumn             = document.getElementById('rightColumn');
    if (rightColumn)
    {                           // right column defined
        let windWidth           = window.innerWidth;
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

        let useridElt           = document.getElementById('UserInfoUserid');
        let userid              = useridElt.innerHTML.trim();
        if (userid.length > 0)
        {
            let collectElt      = document.getElementById('collection');
            if (collectElt)
                collectElt.style.display    = 'none';
        }
        else
        {
            let welcomeElt      = document.getElementById('userWelcome');
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
    let optionsElt  = document.getElementById('UserInfoOptions');
    if (optionsElt)
    {               // have info from User instance
        let topt            = optionsElt.textContent.trim() - 0;
        if (topt && 2)
        {           // turn off popup Help
            if (debug.toLowerCase() == 'y')
                traceAlert("util.js: pageInit: turn off popup help");
            popupHelpOption = false;
        }           // turn off popup Help
    }               // have info from User instance
    else
        alert("commonInit: cannot find UserInfoOptions");

    // scan through all forms and set common dynamic functionality
    // for elements
    for(let i = 0; i < document.forms.length; i++)
    {                   // iterate through all forms
        let form            = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {               // loop through elements in form
            // pop up help balloon if the mouse hovers over an element
            // for more than 2 seconds
            let element     = form.elements[j];
            if (!element.type || element.type != 'hidden')
                actMouseOverHelp.call(form.elements[j]);
        }               // loop through elements in form
    }                   // iterate through all forms

    let dataTable                   = document.getElementById('dataTable');
    if (dataTable)
    {                   // page contains display of tabular results
        let topBrowse               = document.getElementById('topBrowse');
        let botBrowse               = document.getElementById('botBrowse');
        if (topBrowse || botBrowse)
        {               // page contains pagination row
            let dataWidth           = dataTable.offsetWidth;
            let windowWidth         = document.body.clientWidth - 8;
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

function advertLoaded (evt){
    let pattern         = new RegExp('<body[^>]*>([^]*)</body>', 'im');
    let results         = this.responseText.match(pattern);
    let contents        = results[1];
    let frame           = document.getElementById('advertFrame');
    frame.outerHTML     = contents;
}       // function advertLoaded

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
 *      this        instance of Window                                  *
 *      event       instance of Event containing resize event           *
 ************************************************************************/
window.addEventListener("resize",   commonResize);

export function commonResize(ev)
{
    // let topCrumbs           = null;
    let menuButton          = document.getElementById('menuButton');
    // let menuWidth           = 0
    if (menuButton)
    {
        //topCrumbs           = menuButton.parentNode;
        //menuWidth           = menuButton.offsetWidth;
    }

    let logo                = document.getElementById('logo');
    //let logoWidth           = 0
    if (logo)
    {
        //logoWidth           = logo.offsetWidth;
    }

    let advert              = document.getElementById('advertSpan');
    //let advertWidth         = 0
    if (advert)
    {
        //topCrumbs           = advert.parentNode;
        //advertWidth         = Math.max(advert.offsetWidth, 500);
    }

    //let menusWidth          = menuWidth + logoWidth + advertWidth;

    let dataTable                   = document.getElementById('dataTable');
    if (dataTable)
    {                   // page contains display of tabular results
        let topBrowse               = document.getElementById('topBrowse');
        let botBrowse               = document.getElementById('botBrowse');
        if (topBrowse || botBrowse)
        {               // page contains pagination row
            let dataWidth           = dataTable.offsetWidth;
            let windowWidth         = document.body.clientWidth - 8;
            if (dataWidth > windowWidth)
                dataWidth           = windowWidth;
            if (topBrowse)
                topBrowse.style.width   = dataWidth + "px";
            if (botBrowse)
                botBrowse.style.width   = dataWidth + "px";
        }               // page contains pagination row
    }                   // page contains display of tabular results

    let headSection             = document.getElementById('headSection');
    let mainSection             = document.getElementById('mainSection');
    let rightColumn             = document.getElementById('rightColumn');
    if (rightColumn)
    {                           // right column defined
        let windWidth           = window.innerWidth;
        let windHeight          = window.innerHeight;
        let headHeight          = headSection.offsetHeight;
        //let headWidth           = headSection.offsetWidth;
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

        let useridElt           = document.getElementById('UserInfoUserid');
        let userid              = useridElt.innerHTML.trim();
        if (userid.length > 0)
        {
            let collectElt      = document.getElementById('collection');
            if (collectElt)
                collectElt.style.display    = 'none';
        }
        else
        {
            let welcomeElt      = document.getElementById('userWelcome');
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
window.addEventListener("orientationchange",   commonOrientation);

function commonOrientation(ev)
{

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
 *      this        instance of Window                                  *
 *      ev          instance of Event containing scroll event           *
 ************************************************************************/
window.addEventListener("scroll",   commonScroll);
var scrolling               = false;
//var lastScrollY             = 0;
var lastScrollX             = 0

function commonScroll(ev)
{
    //lastScrollY             = Math.round(window.scrollY);
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
export function traceAlert(message)
{
    let traceDiv            = document.getElementById('debugTrace');
    if (traceDiv == null)
    {                       // no existing trace div
        traceDiv            = document.createElement('div');
        traceDiv.id         = 'debugTrace';
        traceDiv.className  = 'warning';
        let container       = document.body;
        let h1s             = container.getElementsByTagName('h1');
        if (h1s.length > 0)
        {                   // insert after first <h1>
            let h1          = h1s[0];
            container       = h1.parentNode;
            if (h1.nextSibling)
                container.insertBefore(traceDiv, h1.nextSibling);
            else
                container.appendChild(traceDiv);
        }                   // insert after first <h1>
        else
            container.appendChild(traceDiv);
    }                       // no existing trace div
    let line                = document.createElement('p');
    let tags                = message.split('>');
    if (tags.length > 10)
    {                       // so many > implies XML
        for(let it = 0; it < tags.length; it++)
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
 *  This is not currently supported.                                    *
 ************************************************************************/
//var mouseIsDown     = false;
var pendStartCell   = null;     // pending possible selection
var startCell       = null;     // first cell of selection
var endCell         = null;     // last cell of selection

/************************************************************************
 *  function cancelSelection                                            *
 *                                                                      *
 *  This function is called to cancel an existing selection.            *
 *  This is not currently supported.                                    *
 ************************************************************************/
function cancelSelection()
{
    alert("cancelSelection from row=" +
                startCell.parentNode.rowIndex + " col=" +
                startCell.cellIndex + " " +
                startCell.outerHTML + " to row=" +
                endCell.parentNode.rowIndex +
                " col=" + endCell.cellIndex + " "  +
                endCell.outerHTML);

    if (startCell && endCell)
    {       // have a selection to cancel
        let firstRowIndex   = startCell.parentNode.rowIndex;
        let lastRowIndex    = endCell.parentNode.rowIndex;
        let firstCellIndex  = startCell.cellIndex;
        let lastCellIndex   = endCell.cellIndex;
        if (firstRowIndex > lastRowIndex)
        {   // swap
            let tri     = firstRowIndex;
            firstRowIndex   = lastRowIndex;
            lastRowIndex    = tri;
        }   // swap
        if (firstCellIndex > lastCellIndex)
        {   // swap
            let tci     = firstCellIndex;
            firstCellIndex  = lastCellIndex;
            lastCellIndex   = tci;
        }   // swap

        let tableBody   = startCell.parentNode.parentNode;

        for (let ri = firstRowIndex; ri <= lastRowIndex; ri++)
        {   // loop through selected rows
            let row = tableBody.rows[ri];
            for (let ci = firstCellIndex; ci <= lastCellIndex; ci++)
            {   // loop through selected columns
                let cell    = row.cells[ci];
                // find first element in selected cell
                let element = cell.firstChild;
                while(element && element.nodeType != 1)
                    element = element.nextSibling;
                let oldcn   = element.className;
                let newcn   = oldcn;
                if (oldcn.substring(oldcn.length - 8) == "Selected")
                    newcn   = oldcn.substring(0,oldcn.length - 8);
                element.className   = newcn;
            }   // loop through selected columns
        }   // loop through selected rows
    }       // have a selection to cancel
    startCell       = null;
    endCell         = null;
}       // function cancelSelection

/************************************************************************
 *  function markSelection                                              *
 *                                                                      *
 *  This function is called to mark a new selection.                    *
 *  This is not currently supported.                                    *
 ************************************************************************/
function markSelection()
{
    if (startCell && endCell)
    {       // have a selection to mark
        alert("markSelection from row=" +
                startCell.parentNode.rowIndex + " col=" +
                startCell.cellIndex + " " +
                startCell.outerHTML + " to row=" +
                endCell.parentNode.rowIndex +
                " col=" + endCell.cellIndex + " "  +
                endCell.outerHTML);

        let firstRowIndex   = startCell.parentNode.rowIndex;
        let lastRowIndex    = endCell.parentNode.rowIndex;
        let firstCellIndex  = startCell.cellIndex;
        let lastCellIndex   = endCell.cellIndex;
        if (firstRowIndex > lastRowIndex)
        {               // swap
            let tri         = firstRowIndex;
            firstRowIndex   = lastRowIndex;
            lastRowIndex    = tri;
        }               // swap
        if (firstCellIndex > lastCellIndex)
        {               // swap
            let tci         = firstCellIndex;
            firstCellIndex  = lastCellIndex;
            lastCellIndex   = tci;
        }               // swap

        let tableBody       = startCell.parentNode.parentNode;

        for (let ri = firstRowIndex; ri <= lastRowIndex; ri++)
        {               // loop through selected rows
            let row = tableBody.rows[ri];
            for (let ci = firstCellIndex; ci <= lastCellIndex; ci++)
            {           // loop through selected columns
                let cell    = row.cells[ci];
                // find first element in selected cell
                let element = cell.firstChild;
                while(element && element.nodeType != 1)
                    element = element.nextSibling;
                element.className   = element.className + "Selected";
            }           // loop through selected columns
        }               // loop through selected rows
    }                   // have a selection to mark
}       // function markSelection

/************************************************************************
 *  function eltMouseDown                                               *
 *                                                                      *
 *  This function is called if the mouse button is pressed on a         *
 *  table cell.                                                         *
 *  This is not currently supported.                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            element the mouse was pressed on                *
 ************************************************************************/
export function eltMouseDown()
{
    //mouseIsDown         = true;
    pendStartCell       = this;
    return false;   // suppress default processing
}       // function eltMouseDown

/************************************************************************
 *  function eltMouseUp                                                 *
 *                                                                      *
 *  This function is called if the mouse button is released on a        *
 *  table cell.                                                         *
 *  This is not currently supported.                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            element the mouse was pressed on                *
 ************************************************************************/
export function eltMouseUp()
{
    //mouseIsDown         = false;
    // find first element in selected cell
    let element         = this.firstChild;
    while(element && element.nodeType != 1)
        element         = element.nextSibling;

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
    startCell           = pendStartCell;
    pendStartCell       = this;

    // if a valid selection, record it for future actions
    //let mouseIsUp       = false;
    //let endCell         = this;
    if (startCell && this.nodeName.toUpperCase() == 'TD')
    {       // valid selection
//  alert("select from row=" +
//      startCell.parentNode.rowIndex + " col=" + startCell.cellIndex + " " +
//      startCell.outerHTML + " to row=" +
//      this.parentNode.rowIndex + " col=" + this.cellIndex + " "  +
//      this.outerHTML);
        markSelection();
        if (element)
            element.focus();    // move focus to input element
        return false;
    }       // valid selection
//    else
//  alert("unexpected from " + startCell.outerHTML + " to " +
//      this.outerHTML);

    // not a valid selection, just move focus
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
export function getParmsFromXml(element)
{
    let parms   = {};
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
    for (let j = 0; j < element.childNodes.length; j++)
    {       // loop through elements within XML response
        let elt = element.childNodes[j];
        if (elt.nodeType != 1)
            continue;   // ignore text & comments between elements

        let value   = "";

        for (let ic = 0; ic < elt.childNodes.length; ic++)
        {   // loop through children of current element
            let child   = elt.childNodes[ic];
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
export function getObjFromXml(element)
{
    let parms   = {};
    if (element.length)
    {       // parameter is a NodeList, HtmlCollection, or Array
        if (element.length > 0)
            element = element[0];
        else
            return "";  // empty string
    }       // parameter is a NodeList, HtmlCollection, or Array

    // store the parameters in an object
    let strValue    = "";
    let returnString    = true;

    for (let j = 0; j < element.childNodes.length; j++)
    {           // loop through children of XML element
        let child   = element.childNodes[j];
        if (child.nodeType == 1)
        {       // sub element
            returnString    = false;

            // update the value of the named attribute
            let currValue   = parms[child.nodeName];
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
 *      text            optional text to display                        *
 *                                                                      *
 *  Returns: instance of Element for <div> enclosing the popup dialog   *
 ************************************************************************/
export function popupLoading(element, text)
{
    if (loaddiv == null)
    {               // indicator not currently displayed
        loaddiv                 = document.getElementById('loading');

        // if there is no "loading" division, create a default one
        if (loaddiv === null || loaddiv === undefined)
        {           // create missing division
            let body            = document.body;
            if (body)
            {
                let div         = document.createElement('div');
                div.id          = 'loading';
                div.className   = 'popup';
                div.appendChild(document.createTextNode("Loading..."));
                body.appendChild(div);
                loaddiv         = div;
            }
        }           // create missing division

        if (loaddiv)
        {           // display loading indicator to user
            if (text && typeof text == 'string')
            {       // replace text in loading division
                while(loaddiv.firstChild)
                    loaddiv.removeChild(loaddiv.firstChild);
                loaddiv.appendChild(document.createTextNode(text));
            }       // replace text in loading division
            if (element === null)
                element         = loadelt;
            else
                loadelt         = element;
            let leftOffset      = getOffsetLeft(element);
            if (leftOffset > 500)
                leftOffset      -= 200;
            loaddiv.style.left  = leftOffset + "px";
            loaddiv.style.top   = (getOffsetTop(element) - 30) + 'px';
            loaddiv.style.display   = 'block';
        }           // display load indicator to user
    }               // indicator not currently displayed
    return loaddiv;
}       // function popupLoading

/************************************************************************
 *  function hideLoading                                                *
 *                                                                      *
 *  Hide the "loading" indicator from the user.  This notifies the      *
 *  user that the extended operation has completed.                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element (optional)                             *
 ************************************************************************/
export function hideLoading()
{
    let div                     = loaddiv;
    if (this && !div)
        div                     = this;
    if (div)
    {                       // indicator currently displayed
        div.style.display       = 'none';   // hide it
        loaddiv                 = null;     // not being displayed
    }                       // indicator currently displayed
}       // function hideLoading

/************************************************************************
 *  function hideRightColumn                                            *
 *                                                                      *
 *  Hide the right-hand notification column.                            *
 ************************************************************************/
export function hideRightColumn()
{
    let mainSection             = document.getElementById('mainSection');
    let rightColumn             = document.getElementById('rightColumn');
    if (rightColumn)
        rightColumn.style.display   = 'none';
    if (mainSection)
        mainSection.style.width     = '100%';
}       // function hideRightColumn

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
export function popupAlert(msg, element)
{
    // display the message in a popup
    let parms   = {"template"   : "",
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
export function createFromTemplate(template,
                                   parms)
{
    let parmsText   = "";
    if (typeof parms == 'object')
    {
        for (let name in parms)
            parmsText +=  name + "='" + parms[name] + "', ";
        console.log("util.js: createFromTemplate: parms=" + parmsText);
    }
    else
        parms   = {};

    let templateName    = template;
    if (typeof(template) == "string")
    {
        let ttemplate   = document.getElementById(template);
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
    let text        = template.outerHTML;
    //alert("createFromTemplate: template=" + text);
    let messages    = '';
    let retval;
    if (text.length > 0)
    {           // have something to substitute into
        let chunks      = text.split('$');
        retval          = chunks[0];    // part before first variable
        for (let i=1; i<chunks.length; i++)
        {       // process each chunk
            let chunk   = chunks[i];
            let result  = chunk.match(/^\w+/);
            let varname = result[0];
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
    let newdiv;
    if (retval.substring(0,3) == '<tr')
    {
        let table   = document.createElement("TABLE");
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
export function openFrame(name, url, side)
{
    // accept mixed case side indicator
    side                    = side.toLowerCase();
    if (side != "left")
        side                = "right";

    // locate the window and document instances for the top window
    // of the application
    let win                 = window;
    while(win.frameElement)
        win                 = win.parent;
    let doc                 = win.document;

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
    let w                   = doc.documentElement.clientWidth;
    let h                   = doc.documentElement.clientHeight;

    if (win.dialogCount == 0)
    {   // resize the main page to only occupy the left half of the window
        let transcription   = doc.getElementById('transcription');
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
    let zindex              = win.dialogZindex[side] + 2;
    iframe.style.zIndex     = zindex;   // move iframe to front
    iframe.style.top        = 0 + "px";
    iframe.style.visibility = "visible";

    // update the global information about half page dialogs
    win.dialogZindex[side]  = zindex;
    win.dialogCount++;

    return iframe.contentWindow;
}       // function openFrame

export function openFrameError()
{
    alert("open frame id='" + this.id + "' failed");
}

/************************************************************************
 *  function closeFrame                                                 *
 *                                                                      *
 *  This is a utility function to close the current frame.  It handles  *
 *  the case where the current window is in an <iframe>                 *
 ************************************************************************/
export function closeFrame(lastChoice)
{
    let iframe              = window.frameElement;
    if (iframe)
    {                   // current window is in an iframe
        // locate the window and document instances for the top window
        let topwin          = window.top;
        let doc             = topwin.document;

//      let frameInfo       = '';
//      for (let i = 0; i < topwin.frames.length; i++)
//      {
//            let frame       = topwin.frames[i];
//          let felt        = frame.frameElement;
//          frameInfo       += i + ' name=' + frame.name +
//                              " <" + felt.nodeName +
//                              ' id="' + felt.id + '"> ';
//      }

        if (topwin.dialogCount == 1)
        {               // closing last dialog
            // resize the display of the transcription
            let w           = doc.documentElement.clientWidth;
            let h           = doc.documentElement.clientHeight;
            let transcription       = doc.getElementById('transcription');
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
        let father  = iframe.parentNode;
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
 *  function showImage                                                  *
 *                                                                      *
 *  This function is called when the user clicks a show image button    *
 *  with the mouse or types Alt-I.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='ShowImage'>                         *
 *      ev              instance of 'click' Event                       *
 ************************************************************************/
export function showImage(ev)
{
    let form            = this.form;
    if (form.Image)
    {       // Image field defined
        args.showimage  = 'yes';    // previous and next request image
        let imageUrl    = form.Image.value;
        if (imageUrl.length == 0)
            alert("util.js: showImage: " +
                  "no image defined for this registration");
        else
        if (imageUrl.length > 23 &&
            (imageUrl.substring(0,23) == "http://www.ancestry.ca/" ||
             imageUrl.substring(0,23) == "https://www.ancestry.ca" ||
             imageUrl.substring(0,23) == "http://interactive.ance" ||
             imageUrl.substring(0,23) == "https://interactive.anc"))
            window.open(imageUrl, "_blank");
        else
        if (imageUrl.length > 5 &&
            (imageUrl.substring(0,5) == "http:" ||
             imageUrl.substring(0,6) == "https:"))
            openFrame("Images",
                      imageUrl,
                      "right");
        else
        if (imageUrl.substring(0, 1) == '/')
            openFrame("Images",
                      '/DisplayImage.php?src=' + imageUrl,
                      "right");
        else
            openFrame("Images",
                      '/DisplayImage.php?src=/Images/' + imageUrl,
                      "right");
    }       // Image field defined
    return false;
}       // function showImage

/************************************************************************
 *  function beep                                                       *
 *                                                                      *
 *  This function is called to issue a warning audio signal when an     *
 *  requested action will not be performed.                             *
 *                                                                      *
 ************************************************************************/
export function beep()
{
    if (typeof audioContext == 'object')
    {
        let oscillator      = audioContext.createOscillator();
        let gain            = audioContext.createGain();
        oscillator.frequency.setValueAtTime(440, audioContext.currentTime);
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start();
        gain.gain.exponentialRampToValueAtTime(0.00001, 
                                               audioContext.currentTime + 0.1);
    }
}       // function beep
