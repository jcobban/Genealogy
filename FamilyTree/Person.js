/************************************************************************
 *  Person.js                                                           *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Person.php.                                                    *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/23      add onload function                             *
 *                      select all text in the blog textarea            *
 *      2010/10/29      close window if adding child or spouse to       *
 *                      function marriage                               *
 *      2010/12/25      set onclick for blogging here rather than in    *
 *                      function HTML                                   *
 *      2011/06/24      For editting spouses and children this page is  *
 *                      now invoked from editMarriages.php              *
 *      2011/08/12      add buttons so owner of blog message can edit   *
 *                      or delete it.                                   *
 *      2011/09/13      catch IE exception in accessing window.opener   *
 *      2011/10/23      use actual buttons for functions previously     *
 *                      invoked by hyperlinks and add keyboard          *
 *                      shortcuts for most buttons.                     *
 *      2012/01/13      change class name                               *
 *      2012/02/26      shrink frames around pictures so they are just  *
 *                      big enough                                      *
 *      2012/10/30      execute correctly if invoked across domains     *
 *      2013/04/12      add support for displaying a boundary           *
 *      2013/04/13      record last referenced individual               *
 *      2013/04/17      LegacyLocation::getLatitude and getLongitude    *
 *                      return DD.dddd values                           *
 *      2013/05/25      boundaries were concatenated when multiple      *
 *                      locations were displayed in sequence            *
 *      2013/05/29      use actMouseOverHelp common function            *
 *      2013/06/12      popup info on mouse over source                 *
 *      2013/07/30      defer facebook initialization until after load  *
 *                      change relationship calculator to popup dialog  *
 *      2013/11/28      defer loading Google(r) maps API to speed up    *
 *                      display of page                                 *
 *      2014/06/29      allow non-registered users to post blogs so we  *
 *                      can capture their e-mail addresses              *
 *      2014/10/12      use method show to display popups               *
 *      2015/01/11      add support for Ancestry search in split window *
 *                      pass surname and given name of initial          *
 *                      individual to choose relative dialog            *
 *      2015/01/23      open descendant and ancestor trees in a new     *
 *                      function frame                                  *
 *      2015/01/26      edit and delete blog onclick not activated      *
 *      2015/02/05      request and pass email address to let non-user  *
 *                      post a blog                                     *
 *      2015/05/01      support for displaying source popup moved here  *
 *                      from common util.js                             *
 *                      new implementation of laying out source popup   *
 *                      by building it when the page is laid out        *
 *                      support for displaying individ popup moved here *
 *                      from common util.js                             *
 *                      new implementation of laying out individ popup  *
 *                      by building it when the page is laid out        *
 *      2015/05/14      add button to request permission to update      *
 *                      in the case where the user is signed on but     *
 *                      not already an owner                            *
 *      2015/05/26      use absolute URLs for blog scripts              *
 *                      add guidance to grant request message           *
 *                      support for displaying location popup moved     *
 *                      here from common util.js                        *
 *                      new implementation of laying out location popup *
 *                      by building it when the page is laid out        *
 *      2015/06/02      use main style for TinyMCE editor               *
 *      2015/07/06      add a button to the location popup to permit    *
 *                      editing the location information                *
 *      2015/07/24      display parms to postBlogXml.php if invoked     *
 *                      with debug                                      *
 *      2015/07/30      signal LegacyLocation.php to close at end       *
 *      2016/03/17      use https to load googleapis                    *
 *      2017/08/16      renamed to Person.js                            *
 *      2017/09/09      change LegacyLocation to Location               *
 *      2017/11/12      handle link to individual with lang parameter   *
 *      2017/10/27      support language selection                      *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2018/11/02      pass authentication key to GoogleApis           *
 *                      ensure lang= parameter not passed to popup      *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/05/16      familyTree cookie was erroneously set from      *
 *                      the idir field in the popup template            *
 *      2019/05/19      call element.click to trigger button click      *
 *      2021/01/16      use addEventListener                            *
 *      2021/03/19      use ES2015 import                               *
 *                      fix popup help for showGraphTree and message    *
 *      2023/06/04      put mapdiv in front of TinyMce editor           *
 *      2023/09/03      support location boundaries with style override *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {HTTP} from "../jscripts6/js20/http.js";
import {iframe, actMouseOverHelp, openFrame, openSignon, debug, args,
        getOffsetLeft, getOffsetTop, popupAlert, show,
        showHelp, hideHelp, keyDown,
        simpleMouseOver, eltMouseOut}
            from "../jscripts6/util.js";
import {capitalize} from "../jscripts6/CommonForm.js";
import {Cookie} from "../jscripts6/Cookie.js";
/* global tinyMCE, google */

/************************************************************************
 *  Initialization code that is executed when this script is loaded.    *
 *                                                                      *
 *  Define the function to be called once the web page is loaded.       *
 ************************************************************************/
window.addEventListener("load", onLoad);

// instance of google.maps.Map for displaying the map
var map                 = null;

// array of instances of google.maps.LatLng for boundary of area
var path                = [];

// instance of google.maps.PolygonOptions for displaying boundary
var polyOptions         = {strokeColor: "red", 
                           strokeOpacity: 0.5,
                           strokeWeight: 2,
                           fillColor: "black",
                           fillOpacity: 0.10};

// instance of google.maps.Polygon for displaying boundary
var boundary            = null;

// instance of google.maps.Geocoder for resolving place names
var geocoder            = null;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization functions once the page is loaded.           *
 ************************************************************************/
function onLoad()
{
    window.addEventListener("resize", personWindowResize);

    // set action methods for form
    let invoker             = window.opener;
    if (invoker)
    {       // invoked from another page
        try {
        let openerPath      = invoker.location.pathname;
        let dlm             = openerPath.lastIndexOf('/');
        let openerName      = openerPath.substr(dlm + 1);
        if (openerName == "editMarriages.php" ||
            openerName == "editParents.php")
            close();
        }   // try
        catch (e) {
            var msg     = "Person.js: onLoad: msg=" + e.message;
//            if (invoker.location)
//            {
//                msg     += ", location=" + invoker.location;
//                if (invoker.location.pathname)
//                    msg += ", pathname=" + invoker.location.pathname;
//            }
            console.log(msg);
        }   // catch
    }       // invoked from another page

    // activate local keystroke handling
    document.body.addEventListener("keydown", diKeyDown);

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(let i = 0; i < document.forms.length; i++)
    { 
        let form    = document.forms[i];
        for(let j = 0; j < form.elements.length; j++)
        {                       // loop through elements in form
            let element = form.elements[j];

            let name    = element.name;
            if (name.length == 0)
            {                   // button elements usually have id not name
                name    = element.id;
            }                   // button elements usually have id not name

            let parts   = /^([a-zA-Z_]+)(\d*)$/.exec(name);
            if (parts)
            {
                name    = parts[1];
            }

            // take action specific to the element based on its name
            switch(name)
            {                   // switch on name
                case 'blEdit':
                {               // edit message button
                    element.addEventListener("click", editBlog);
                    break;
                }               // edit message button

                case 'blDel':
                {               // delete message button
                    element.addEventListener("click", delBlog);
                    break;
                }               // delete message button

                case 'message':
                {               // blog text area
                    let cell            = element.parentNode;
                    if (cell === null)
                    {
                        console.log("Person.js: onLoad: parentNode is null this=" +
                                    this.outerHTML);
                    }
                    else
                    {
                        actMouseOverHelp.call(cell, 'Helpmessage');
                    }
                    break;
                }               // blog text area

                case 'PostBlog':
                {               // post blog button
                    element.addEventListener("click", postBlog);
                    break;
                }               // post blog button

                case 'showDescTree':
                {               // show descendant tree
                    element.addEventListener("click", showDescTree);
                    element.focus();
                    break;
                }               // show descendant tree

                case 'showAncTree':
                {               // show ancestor tree
                    element.addEventListener("click", showAncTree);
                    break;
                }               // show ancestor tree

                case 'relationshipCalc':
                {               // relationship calculator
                    element.addEventListener("click", relationshipCalc);
                    break;
                }               // relationship calculator

                case 'ancestrySearch':
                {               // perform Ancestry.com search
                    element.addEventListener("click", ancestrySearch);
                    break;
                }               // perform Ancestry.com search

                case 'edit':
                {               // edit individual
                    element.addEventListener("click", editPerson);
                    element.focus();
                    break;
                }               // edit individual

                case 'reqgrant':
                {               // request permission to update
                    element.addEventListener("click", reqGrant);
                    break;
                }               // request permission to update

                case 'idir':
                {               // edit individual
                    let cookie      = new Cookie("familyTree");
                    let tvalue      = parseInt(element.value);
                    if (Number.isInteger(tvalue))
                    {
                        cookie.idir     = tvalue;
                        cookie.store(10);   // keep for 10 days
                    }
                    break;
                }               // edit individual

                case 'treeName':
                {               // name of tree the individual belongs to
                    let cookie      = new Cookie("familyTree");
                    cookie.treeName = element.value;
                    cookie.store(10);       // keep for 10 days
                    break;
                }               // name of tree the individual belongs to


                case 'showMap':
                case 'tshowMap':
                {
                    element.addEventListener("click", showMap);
                    break;
                }

                case 'editLoc':
                {
                    element.addEventListener("click", editLocation);
                    break;
                }

                case 'mbsubmit':
                {
                    element.addEventListener("click", submitMbBirth);
                    break;
                }
            }                   // switch on name
        }                       // loop through elements in form
    }                           // iterate through all forms

    let showGraphTree       = document.getElementById('showGraphTree');
    if (showGraphTree)
    {                           // support help
        actMouseOverHelp.call(showGraphTree, 'HelpshowGraphTree');
    }                           // support help

    // for each image displayed in a <div class='picture'>
    // adjust the width of the division frame
    for (var ip = 0; ip < document.images.length; ip++)
    {                           // loop through all images
        let image                   = document.images[ip];
        let div                     = image.parentNode;
        if (div.nodeName.toLowerCase() == 'div' &&
            div.className == 'picture')
        {               // image is in a frame
            div.style.width     = Math.max(image.width + 5, 100) + "px";
        }               // image is in a frame
    }                   // loop through all images

    // activate support for a popup on each location name
    let allSpan = document.getElementsByTagName("span");
    for (var ispan = 0, maxSpan = allSpan.length; ispan < maxSpan; ispan++)
    {                           // loop through all spans
        let span                    = allSpan[ispan];
        if (span.id.length > 9 && span.id.substring(0,4) == "show")
        {
            span.addEventListener("mouseover", locMouseOver);
            span.addEventListener("mouseout", eltMouseOut);
        }
        else
        if (span.id.length > 10 && span.id.substring(0,10) == 'DeathCause')
        {
            span.addEventListener("mouseover", causeMouseOver);
            span.addEventListener("mouseout", eltMouseOut);
        }
    }                           // loop through all spans

    // activate support for a popup on each hyperlink to an individual
    let allAnc                      = document.getElementsByTagName("a");
    for (var ianc = 0, maxAnc = allAnc.length; ianc < maxAnc; ianc++)
    {                           // loop through all anchors
        let anc                     = allAnc[ianc];
        let li                      = anc.href.lastIndexOf('/');
        let name                    = anc.href.substring(li + 1);
        let hi                      = name.indexOf('#');
        if (hi == -1 && name.substring(0, 13) == "Person.php?id")
        {   // link to another individual
            anc.addEventListener("mouseover", indMouseOver);
            anc.addEventListener("mouseout", eltMouseOut);
        }   // link to another individual
        else
        if (name.substring(0, 13) == "Source.php?id")
        {   // link to a source
            anc.addEventListener("mouseover", srcMouseOver);
            anc.addEventListener("mouseout", eltMouseOut);
        }   // link to a source
        else
        if (name.substring(0, 15) == "getPersonSvg.php")
        {   // link to a graphical family tree
            actMouseOverHelp.call(anc, 'HelpshowGraphTree');
        }   // link to a graphical family tree
    }       // loop through all anchors

}       // function onLoad

/************************************************************************
 *  function personWindowResize                                         *
 *                                                                      *
 *  This method is called when the browser window size is changed.      *
 *  If the window is split between the main display and a second        *
 *  display, resize.                                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this        <body> element                                      *
 *      ev          Javascript resize Event                             *
 ************************************************************************/
function personWindowResize()
{
    if (iframe)
        openFrame(iframe.name, null, "right");
}       // function personWindowResize

/************************************************************************
 *  function initializeMaps                                             *
 *                                                                      *
 *  Initialize support for Google Maps.                                 *
 *  This is a callback from the Google API site once the Javascript     *
 *  code for displaying maps is loaded.                                 *
 ************************************************************************/
window.initializeMaps       = 
function initializeMaps()
{
    // support for displaying Google map
    try {
        geocoder    = new google.maps.Geocoder();
    }
    catch(e)
    {
        alert("Person.js: initializeMaps: " + e.message);
    }
}       // function initializeMaps

/************************************************************************
 *  function postBlog                                                   *
 *                                                                      *
 *  This method is called when the user requests to post                *
 *  a message to the blog of an individual.                             *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='PostBlog'>                          *
 *      ev              Click Event                                     *
 ************************************************************************/
function postBlog()
{
    let form        = this.form;
    let userid      = form.userid.value;
    let email       = '';
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    if (form.emailAddress)
        email       = form.emailAddress.value;

    if (userid == '' && email == '')
    {           // not signed on or identified
        openSignon();
    }           // not signed on or identified
    else
    {
        let idir        = form.idir.value;
        let message     = tinyMCE.get('message').getContent();
        let parms       = { "idir"          : idir,
                            "emailAddress"  : email,
                            "message"       : message,
                            "lang"          : lang};

        if (debug.toLowerCase() == 'y')
        {
            alert("Person.js: postBlog: parms={" +
                            "idir="             + idir +
                            ", emailAddress='"  + email +
                            "', message='"      + message + 
                            "', lang='"         + lang + "'}");
            parms['debug']  = 'y';
        }

        // invoke script to update Event and return XML result
        HTTP.post('/postBlogXml.php',
                  parms,
                  gotBlog,
                  noBlog);
    }
}       // postBlog

/************************************************************************
 *  function gotBlog                                                    *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  a posted blog is retrieved from the database.                       *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc          response from web server as XML document        *
 ************************************************************************/
function gotBlog(xmlDoc)
{
    //let evtForm       = document.evtForm;
    let root            = xmlDoc.documentElement;
    let messageElt      = document.getElementById('PostBlog');
    let msg             = "";

    if (root && root.nodeName == 'blog')
    {
        for(let i = 0; i < root.childNodes.length; i++)
        {       // loop through children
            let node    = root.childNodes[i];
            if (node.nodeName == 'msg')
                msg += node.textContent;
        }       // loop through children
    }
    else
    {       // error
        if (root)
        {
            for(let i = 0; i < root.childNodes.length; i++)
            {       // loop through children
                let node    = root.childNodes[i];
                if (node.nodeValue != null)
                    msg += node.nodeValue;
            }       // loop through children
        }
        else
            msg += root;
    }       // error

    if (msg.length > 0)
        popupAlert(msg, messageElt);

    location.reload();     // refresh
}       // gotBlog

/************************************************************************
 *  function noBlog                                                     *
 *                                                                      *
 *  This method is called if there is no blog script on the web server. *
 ************************************************************************/
function noBlog()
{
    alert('Person.js: noBlog: ' +
                'script "postBlogXml.php" not found on web server');
}       // noBlog

/************************************************************************
 *  function noDelBlog                                                  *
 *                                                                      *
 *  This method is called if there is no blog script on the web server. *
 ************************************************************************/
function noDelBlog()
{
    alert('Person.js: noDelBlog: ' +
                'script "deleteBlogXml.php" not found on web server');
}       // noDelBlog

/************************************************************************
 *  function editBlog                                                   *
 *                                                                      *
 *  This method is called if the user requests to edit the blog         *
 *  message.                                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='blEdit'>                            *
 ************************************************************************/
function editBlog()
{
    alert('to do: editBlog: ' + this.id.substring(6));
    return false;
}       // editBlog

/************************************************************************
 *  function delBlog                                                    *
 *                                                                      *
 *  This method is called if the user requests to delete the blog       *
 *  message.                                                            *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='blDel'>                             *
 ************************************************************************/
function delBlog()
{
    //let form        = this.form;
    let blid        = this.id.substring(5);
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;

    let parms       = {"blid"   : blid,
                       "lang"   : lang};

    // invoke script to update blog and return XML result
    HTTP.post('/deleteBlogXml.php',
              parms,
              gotBlog,
              noDelBlog);
}       // delBlog

/************************************************************************
 *  function showDescTree                                               *
 *                                                                      *
 *  This method is called when the user requests to display a tree of   *
 *  the descendants of an individual.                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='showDescTree'>                      *
 ************************************************************************/
function showDescTree()
{
    let form        = this.form;
    let idir        = form.idir.value;
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    let url         = "/FamilyTree/descendantReport.php?idir=" + idir +
                        "&lang=" + lang;
    if (debug.toLowerCase() == 'y')
        url         += '&debug=Y';
    openFrame("chooser",
              url,
              "right");
}       // showDescTree

/************************************************************************
 *  function showAncTree                                                *
 *                                                                      *
 *  This method is called when the user requests to display a tree of   *
 *  the ancestors of an individual.                                     *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='showAncTree'>                       *
 ************************************************************************/
function showAncTree()
{
    let form        = this.form;
    let idir        = form.idir.value;
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    let url         = "/FamilyTree/ancestorReport.php?idir=" + idir +
                        "&lang=" + lang;
    if (debug.toLowerCase() == 'y')
        url         += '&debug=Y';
    openFrame("chooser",
              url,
              "right");
}       // showAncTree

/************************************************************************
 *  function relationshipCalc                                           *
 *                                                                      *
 *  This method is called when the user requests to determine the       *
 *  relationship between the current individual and another individual  *
 *  in the database.                                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='relationshipCalc'>                  *
 ************************************************************************/
function relationshipCalc()
{
    let form        = this.form;
    let idir        = form.idir.value;
    //let givenName   = form.givenname.value;
    let surname     = form.surname.value;
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    let url         = "/FamilyTree/chooseRelative.php" +
                            "?name=" + surname +
                            "&idir=" + idir; 
                            "&lang=" + lang; 
    if (debug.toLowerCase() == 'y')
        url         += '&debug=Y';
    openFrame("chooser",
              url,
              "right");
}       // relationshipCalc

/************************************************************************
 *  function ancestrySearch                                             *
 *                                                                      *
 *  Perform a search for a matching individual in Ancestry.ca.          *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='ancestrySearch'>                        *
 ************************************************************************/
function ancestrySearch()
{
    let form            = this.form;
    let yearPatt        = /\d{4}/;
    let birthDate       = form.birthDate.value;
    let birthYear       = '';
    let rxRes           = yearPatt.exec(birthDate);
    if (rxRes)
        birthYear       = rxRes[0];
    let searchUrl       = 
        "http://search.ancestry.ca/cgi-bin/sse.dll?gl=ROOT_CATEGORY" +
        "&rank=1" +
        "&new=1" +
        "&so=3" +
        "&MSAV=1" +
        "&msT=1" +
        "&gss=ms_f-2_s" +
        "&gsfn=" + encodeURIComponent(form.givenname.value) +
        "&gsln=" + encodeURIComponent(form.surname.value) +
        "&msbdy=" + birthYear +
        "&msbpn__ftp=" + encodeURIComponent(form.birthPlace.value) +
        "&msbpn=5007" +
        "&msbpn_PInfo=5-|0|1652393|0|3243|0|5007|0|0|0|0|" +
        "&msfng0=" + encodeURIComponent(form.fatherGivenName.value) +
        "&msfns0=" + encodeURIComponent(form.fatherSurname.value) +
        "&msmng0=" + encodeURIComponent(form.motherGivenName.value) +
        "&msmns0=" + encodeURIComponent(form.motherSurname.value) +
        "&cpxt=1" +
        "&catBucket=rstp" +
        "&uidh=l88" +
        "&cp=3"

    let sframe          = document.getElementById("searchFrame");
    if (!sframe)
    {
        sframe          = document.createElement("IFRAME");
        sframe.name     = "searchFrame";
        sframe.id       = "searchFrame";
        document.body.appendChild(sframe);
    }
    sframe.src                  = searchUrl;
    let w                       = document.documentElement.clientWidth;
    let h                       = document.documentElement.clientHeight;
    // resize the display of the transcription
    let transcription           = document.getElementById('transcription');
    transcription.style.width   = w/2 + "px";
    transcription.style.height  = h + "px";

    // size and position the image
    sframe.style.width          = w/2 + "px";
    sframe.style.height         = h + "px";
    sframe.style.position       = "fixed";
    sframe.style.left           = w/2 + "px";
    sframe.style.top            = 0 + "px";
    sframe.style.visibility     = "visible";
    return false;
}   // ancestrySearch

/************************************************************************
 *  function editPerson                                                 *
 *                                                                      *
 *  This method is called when the user requests to determine the       *
 *  relationship between the current individual and another individual  *
 *  in the database.                                                    *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='edit...'>                           *
 ************************************************************************/
function editPerson()
{
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    let form        = this.form;
    let idir        = form.idir.value;
    location        = 'editIndivid.php?idir=' + idir +
                            "&lang=" + lang; 
}       // editPerson

/************************************************************************
 *  function reqGrant                                                   *
 *                                                                      *
 *  This method is called when the user requests to permission to       *
 *  update this individual and his/her family.                          *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='reqGrant'>                          *
 ************************************************************************/
function reqGrant()
{
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    let form        = this.form;
    let idir        = form.idir.value;
    let givenName   = capitalize(form.givenname);
    let surName     = capitalize(form.surname);
    let subject     = 'Please Grant Access to ' + 
                          givenName + ' ' + surName + ', IDIR='+ idir;
    subject     = encodeURIComponent(subject);
    let url     = '/contactAuthor.php?idir=' + idir + 
                          '&tableName=tblIR' +
                          "&lang=" + lang + 
                          '&subject=' + subject +
                '&text=Please explain why you should be granted access.';
    if (debug.toLowerCase() == 'y')
        url     += '&debug=Y';
    openFrame("chooser",
              url,
              "right");
    return false;
}       // reqGrant

/************************************************************************
 *  function diKeyDown                                                  *
 *                                                                      *
 *  Handle key strokes that apply to the entire window.  For example    *
 *  the key combination Alt-E are interpreted to edit the               *
 *  current individual.                                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    the element                                             *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function diKeyDown(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e                   =  window.event;    // IE
    }       // browser is not W3C compliant
    let key                 = e.key;            // key label
    let button;

    // take action based upon code
    if (e.altKey)
    {               // alt key shortcuts
        switch (key)
        {           // act on specific key
            case 'a':
            case 'A':
                button  = document.getElementById('showAncTree');
                button.click(); 
                return false;

            case 'b':
            case 'B':
                button  = document.getElementById('PostBlog');
                button.click(); 
                return false;

            case 'd':
            case 'D':
                button  = document.getElementById('showDescTree');
                button.click(); 
                return false;

            case 'e':
            case 'E':
                button  = document.getElementById('edit');
                button.click(); 
                return false;

            case 'r':
            case 'R':
                button  = document.getElementById('relationshipCalc');
                button.click(); 
                return false;

            case 's':
            case 'S':
                button  = document.getElementById('ancestrySearch');
                button.click(); 
                return false;

        }           // switch on key code
    }               // alt key shortcuts

    return true;    // do default action
}       // diKeyDown

/************************************************************************
 *  function showMap                                                    *
 *                                                                      *
 *  This function is called if the user clicks on the show Map button.  *
 *  It displays a map using Google maps support.                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='ShowMap'>                               *
 ************************************************************************/
function showMap()
{
    let button      = this;
    let form        = button.form;
    let mapDiv      = document.getElementById("mapDiv");
    if (mapDiv === null || mapDiv === undefined)
    {
        popupAlert("Person.js: showMap: cannot locate <div id='mapDiv'>",
                   this);
        return;
    }

    // if latitude and longitude specified in database, display the
    // map based upon those values
    let lat         = form.Latitude.value;
    let lng         = form.Longitude.value;
    let locn        = form.Location.value;
    let searchName  = form.searchName.value;
    let boundary    = form.Boundary.value;
    let zoom        = Number(form.Zoom.value);

    if (lat != '0' || lng != '0')
    {       // display map for coordinates
        try {
            displayMap(new google.maps.LatLng(lat, lng),
                       zoom,
                       boundary,
                       locn);
        }
        catch(e)
        {
            popupAlert("Unable to use google maps to display map of location: " +
                        "message='" + e.message + "', " +
                        "lat=" + lat + ", lng=" + lng + ", zoom=" + zoom,
                        this);
        }
    }       // display map for coordinates
    else
    if (geocoder !== null)
    {       // use Geocoder
        geocoder.geocode( { 'address': searchName},
                         function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                displayMap(results[0].geometry.location,
                           zoom,
                           boundary,
                           locn);
            }
            else
            {       // geocode failed
                popupAlert("Person.js: showMap: " +
                            "Geocode for '" + searchName +
                "' was not successful for the following reason: " + status,
                           this);
            }       // geocode failed
        });     // end of inline function and invocation of geocode
    }       // use Geocoder
    else
        popupAlert("Person.js: showMap: cannot locate Google geocoder",
                   this);
    return false;
}       // showMap

/************************************************************************
 *  function displayMap                                                 *
 *                                                                      *
 *  This function is called to display a Google maps map                *
 *  of the location.                                                    *
 *                                                                      *
 *  Input:                                                              *
 *      latlng          instance of google.maps.LatLng                  *
 *      zoomlevel       level of detail to zoom in on                   *
 *      boundary        array of instances of LatLng as a string        *
 *      locn            name of the location for diagnostics            *
 ************************************************************************/
function displayMap(latlng,
                    zoomlevel, 
                    boundStr,
                    locn)
{
    // parse the boundary string
    let latPatt                 = /\(([0-9.-]+)/;
    let lngPatt                 = /([0-9.-]+)\)/;

    // instance of google.maps.PolygonOptions for displaying boundary
    let mapStyle                = polyOptions;
    path                        = [];   // clear global

    let bl                      = boundStr.length;
    if (bl > 0)
    {                       // have a boundary to display
        if (boundStr.charAt(0) == '{')
        {                   // JSON style prefix
            let prefEng         = boundStr.indexOf('},');
            let prefix          = boundStr.substring(0, prefEng + 1);
            mapStyle            = JSON.parse(prefix);
            for (let prop in mapStyle)
            {               // loop through properties
                let value       = mapStyle[prop];
                switch (prop)
                {           // act on specific option
                    case 'strokeColor':
                        mapStyle[prop]   = value;
                        break;

                    case 'strokeOpacity':
                        mapStyle[prop]   = value;
                        break;

                    case 'strokeWeight':
                        mapStyle[prop]   = value;
                        break;

                    case 'fillColor':
                        mapStyle[prop]   = value;
                        break;

                    case 'fillOpacity':
                        mapStyle[prop]   = value;
                        break;

                }           // act on specific option
            }               // loop through properties
            boundStr            = boundStr.substring(prefEng + 2);
        }                   // JSON style prefix

        let bounds              = boundStr.split(',');
        for (let ib=0; ib < bounds.length; ib++)
        {                   // loop through each element
            let bound           = bounds[ib];
            let rxRes           = latPatt.exec(bound);
            if (rxRes != null)
            {               // latitude 
                let lat         = rxRes[1];
                ib++;
                bound           = bounds[ib];
                rxRes           = lngPatt.exec(bound);
                if (rxRes != null)
                {           // longitude)
                    let lng     = rxRes[1];
                    let latLng  = new google.maps.LatLng(lat,
                                         lng);
                    path.push(latLng);
                }           // longitude
                else
                {           // match failed
                    alert("Person.js: displayMap: " +
                      "Invalid Boundary Element " +
                      bound + " ignored");
                }           // match failed
            }               // succeeded
            else
                alert("Person.js: displayMap: " +
                      "Invalid Boundary Element " +
                      bound + " ignored");
        }                   // loop through each element
        
        if (mapStyle.strokeColor == 'red')
            boundary            = new google.maps.Polygon(mapStyle);
        else    
            boundary            = new google.maps.Polyline(mapStyle);
        boundary.setPath(path);
    }       // have a boundary to display
    else
        boundary    = null;

    if (latlng !== null)
    {       // location resolved
        //let form              = document.locForm;
        let mapDiv              = document.getElementById("mapDiv");
        mapDiv.style.left       = "0px";
        mapDiv.style.top        = "0px";
        mapDiv.style.zIndex     = "9";
        show.call(mapDiv);               // make visible

        let hideMapDiv          = document.getElementById("hideMapDiv");
        hideMapDiv.style.left   = "80px";
        hideMapDiv.style.top    = "0px";
        hideMapDiv.style.width  = "120px";
        hideMapDiv.style.zIndex = "10";
        let hideMapBtn          = document.getElementById("hideMap");
        hideMapBtn.addEventListener("click", hideMap);
        show.call(hideMapDiv);           // make visible

        let myOptions = {
                          zoom:     zoomlevel,
                          center:   latlng,
                          mapTypeId: google.maps.MapTypeId.ROADMAP
                        };
        try {
            map = new google.maps.Map(mapDiv,
                                  myOptions);
            try {
                //let marker = 
                new google.maps.Marker({map:       map, 
                                        position:  latlng });
            }       // try to allocate marker
            catch(e) {
                alert("Person.js: displayMap: " +
                      "new google.maps.Marker failed: message='" + e.message +
                      "'");
            }

            // display boundary if any
            if (boundary)
                boundary.setMap(map);
        }       // try to allocation map
        catch(e) {
            alert("Person.js: displayMap: " +
                  "new google.maps.Map failed: message='" + e.message + "'");
        }
    }       // location resolved
    else
    {       // location not resolved
        alert("Person.js: displayMap: location " + locn + 
                " not resolved");
    }       // location not resolved
}       // displayMap

/************************************************************************
 *  function hideMap                                                    *
 *                                                                      *
 *  This function is called if the user clicks on the Hide Map button.  *
 *  It hides the map that has previously been displayed.                *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Parents'>                               *
 ************************************************************************/
function hideMap()
{
    let hideMapDiv              = document.getElementById("hideMapDiv");
    hideMapDiv.style.display    = 'none';   // hide
    let mapDiv                  = document.getElementById("mapDiv");
    mapDiv.style.display        = 'none';   // hide
    return false;
}       // hideMap

/************************************************************************
 *  function editLocation                                               *
 *                                                                      *
 *  This function is called if the user clicks on an edit ocation       *
 *  button in the popup for a location.                                 *
 *  It opens the edit dialog for the Location record.                   * 
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='editLoc9999'>                           *
 ************************************************************************/
function editLocation()
{
    let lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    let button      = this;
    //let form      = button.form;
    let idlr        = button.id.substring(7);
    let url         = "/FamilyTree/Location.php?idlr=" + idlr +
                                              "&lang=" + lang +
                                              "&closeAtEnd=Y";
    if (debug.toLowerCase() == 'y')
        url         += '&debug=Y';
    openFrame("chooser",
              url,
              "right");
}       // function editLocation

/************************************************************************
 *  function submitMbBirth                                              *
 *                                                                      *
 *  This function is called if the user clicks on a Manitoba birth      *
 *  registration detail.                                                *
 *  It submits the containing form.                                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='submitMbBirth9999'>                     *
 ************************************************************************/
function submitMbBirth()
{
    let form        = this.parentElement;
    form.submit();
}       // function submitMbBirth

/************************************************************************
 *  function locMouseOver                                               *
 *                                                                      *
 *  This function is called if the mouse moves over an element          *
 *  containing a location on the invoking page.                         *
 *  Delay popping up the information balloon for two seconds.           *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element                                        *
 *      ev          MouseOver Event                                     *
 ************************************************************************/
function locMouseOver(ev)
{
    console.log("locMouseOver: " + this.outerHTML);
    simpleMouseOver.call(this, ev, popupLoc);
}       // locMouseOver

/************************************************************************
 *  function popupLoc                                                   *
 *                                                                      *
 *  This function is called if the mouse is held over a location        *
 *  element on the invoking page for more than 2 seconds.  It shows     *
 *  the information from the associated instance of Location            *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML element                                        *
 ************************************************************************/
function popupLoc()
{
    console.log("Person.js: popupLoc: this=" + this.outerHTML);
    let locIndex    	    = this.id.indexOf('_');
    let idlr        	    = this.id.substring(locIndex + 1);
    let matches     	    = idlr.match(/\d+/)
    if (matches)
        idlr        	    = matches[0];
    let prefix      	    = this.id.substring(0,7);
    let helpDivId     	    = prefix + "Div" + idlr;
    let helpDiv     	    = document.getElementById(helpDivId);
    if (helpDiv)
    {       // have a help division to display
        let tableWidth      = window.innerWidth;
        helpDiv.style.left  = Math.max(Math.min(getOffsetLeft(this) - 50,                   tableWidth - Math.floor(window.innerWidth/2)), 2) + 'px';
        helpDiv.style.top   = (getOffsetTop(this) +
                              this.offsetHeight + 5) + 'px';
        helpDiv.addEventListener("keydown", keyDown);
        // so key strokes in balloon will close window
        showHelp.call(this, helpDiv);
    }       // have a help division to display
    else
    {
        console.loc('Person.js: popupLoc: Logic Error: "' +
                        helpDivId + '" not found this=' + this.outerHTML); 
        popupAlert('Person.js: popupLoc: Logic Error: "' +
                        helpDivId + '" not found',
                   this); 
    }
}       // function popupLoc

/************************************************************************
 *  function causeMouseOver                                             *
 *                                                                      *
 *  This function is called if the mouse moves over an element          *
 *  containing the text of a cause of death on the invoking page.       *
 *  Delay popping up the information balloon for two seconds.           *
 *                                                                      *
 *  Input:                                                              *
 *      this        <span> element containing a cause of death          *
 *      ev          MouseOver Event                                     *
 ************************************************************************/
function causeMouseOver(ev)
{
    simpleMouseOver.call(this, ev, popupCause);
}       // causeMouseOver

/************************************************************************
 *  function popupCause                                                 *
 *                                                                      *
 *  This function is called if the mouse is held over a cause element   *
 *  on the invoking page for more than 2 seconds.  It shows the         *
 *  explanation of the cause of death from the script DeathCauses.php   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <span> element containing a cause of death          *
 ************************************************************************/
function popupCause()
{
    let causeId             = "DeathCauseHelp" + this.id.substring(10);
    let helpDiv             = document.getElementById(causeId);
    if (helpDiv)
    {                   // have a help division to display
        let tableWidth      = window.innerWidth;
        helpDiv.style.left  = Math.max(Math.min(getOffsetLeft(this) - 50,        tableWidth - Math.floor(window.innerWidth/2)), 2) + 'px';
        helpDiv.style.top   = (getOffsetTop(this) +
                               this.offsetHeight + 5) + 'px';
        helpDiv.addEventListener("keydown", keyDown);
        // so key strokes in balloon will close window
        showHelp.call(this, helpDiv);
    }                   // have a help division to display
}       // function popupCause

/************************************************************************
 *  function srcMouseOver                                               *
 *                                                                      *
 *  This function is called if the mouse moves over an element          *
 *  containing a hyperlink to a source record on the invoking page.     *
 *  Delay popping up the information balloon for two seconds.           *
 *                                                                      *
 *  Input:                                                              *
 *      this        <a> element                                         *
 *      ev          MouseOver Event                                     *
 ************************************************************************/
function srcMouseOver(ev)
{
    simpleMouseOver.call(this, ev, popupSource);
}       // srcMouseOver

/************************************************************************
 *  function popupSource                                                *
 *                                                                      *
 *  This function is called if the mouse is held over a link to a       *
 *  source record on the invoking page for more than 2 seconds.         *
 *  It shows the information  from the associated instance of           *
 *  Source                                                              *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of HTML element                            *
 ************************************************************************/
function popupSource()
{
    let regex               = /id=(\d+)/;
    let matches    		    = this.href.match(regex);
    if (matches)
    {
        let idsr    		= matches[1];

        // if a previous help balloon is still being displayed, hide it
        hideHelp.call(this);

        let helpDiv 		= document.getElementById("Source" + idsr);

        if (helpDiv)
        {       // have the division

            // position and display division
            let leftOffset      = getOffsetLeft(this);
            if (leftOffset > (window.innerWidth / 2))
                leftOffset      = window.innerWidth / 2;
            helpDiv.style.left  = leftOffset + "px";
            helpDiv.style.top   = (getOffsetTop(this) + 30) + 'px';
            showHelp.call(this, helpDiv)
//alert("util.js: popupSource: helpDiv.style.left=" + helpDiv.style.left +
//          ", helpDiv.style.top=" + helpDiv.style.top);
        }       // have the division to display
        else
            alert("person.js: popupSource: Cannot find <div id='Source" +
                  idsr + "'>");
    }
}       // function popupSource

/************************************************************************
 *  function indMouseOver                                               *
 *                                                                      *
 *  This function is called if the mouse moves over an element          *
 *  containing a hyperlink to an individual on the invoking page.       *
 *  Delay popping up the information balloon for two seconds.           *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML Element                                        *
 *      ev          MouseOver Event                                     *
 ************************************************************************/
function indMouseOver(ev)
{
    simpleMouseOver.call(this, ev, popupIndiv);
}       // indMouseOver

/************************************************************************
 *  function popupIndiv                                                 *
 *                                                                      *
 *  This function is called if the mouse is held over a link to an      *
 *  individual on the invoking page for more than 2 seconds.  It shows  *
 *  the information from the associated instance of Person              *
 *                                                                      *
 *  Input:                                                              *
 *      this        HTML Element                                        *
 ************************************************************************/
function popupIndiv()
{
    let regex               = /idir=(\d+)/;
    let matches    		    = this.href.match(regex);
    if (matches)
    {
        let idir            = matches[1];
        let popupDiv         = document.getElementById("Individ" + idir);

        if (popupDiv)
        {       // have the division

            // position and display division
            let leftOffset      = getOffsetLeft(this);
            if (leftOffset > (window.innerWidth / 2))
                leftOffset      = window.innerWidth / 2;
            popupDiv.style.left  = leftOffset + "px";
            popupDiv.style.top   = (getOffsetTop(this) + 30) + 'px';
            showHelp.call(this, popupDiv)
        }       // have the division to display
        else
            alert("util.js: popupIndiv: Cannot find <div id='Individ" +
                  idir + "'>");
    }
}       // popupIndiv
