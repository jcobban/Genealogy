/************************************************************************
 *  Address.js                                                          *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Address.php.                                                   *
 *                                                                      *
 *  History:                                                            *
 *      2010/12/05      created                                         *
 *      2010/12/08      add new mailing address                         *
 *      2011/03/02      change name of submit button to 'Submit'        *
 *      2012/01/13      change class names                              *
 *      2013/02/23      add Google maps support                         *
 *      2013/05/29      use actMouseOverHelp common function            *
 *      2013/07/31      defer setup of facebook link                    *
 *      2014/08/28      correct name of button for merging addresses    *
 *                      if debugging merge addresses by submitting form *
 *                      instead of by AJAX to permit the invoker to see *
 *                      the generated XML from mergeAddressesXml.php    *
 *      2014/10/12      use routine show to display map                 *
 *      2015/02/10      support being opened in <iframe>                *
 *      2015/05/27      use absolute URLs for AJAX                      *
 *                      close and hide dialog if in a frame             *
 *      2015/06/02      use main style for TinyMCE editor               *
 *      2015/06/16      open list of pictures in other half             *
 *                      of the window                                   *
 *      2016/12/09      use pre-determined search name for              *
 *                      maps geolocate                                  *
 *      2017/08/04      class LegacyAddress renamed to Address          *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/09/25      disable delete button for new record            *
 *      2023/07/29      migrate to Es2015                               *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {HTTP} from "../jscripts6/js20/http.js";
import {openFrame, closeFrame, show, args, 
        popupAlert, hideRightColumn,
        keyDown, getOffsetLeft, getOffsetTop} 
            from "../jscripts6/util.js";
import {change, changeElt}
            from "../jscripts6/CommonForm.js";
/* global google */

/************************************************************************
 *  Specify the function to get control once the page is loaded.        *
 ************************************************************************/
window.addEventListener('load', onLoad);

/************************************************************************
 *  Parameters for Google Maps                                          *
 ************************************************************************/
var map;        // instance of google.maps.Map
var geocoder;   // instance of google.maps.Geocoder

/************************************************************************
 *  function initMap                                                    *
 *                                                                      *
 *  This function is called once the Google Maps API has been loaded.   *
 ************************************************************************/
function initMap()
{
    // support for displaying map and geocoding
    try {
        geocoder    = new google.maps.Geocoder();
    }
    catch(e)
    {
        geocoder    = null;
    }
    map             = null;
}       // function initMap

window.initMap      = initMap;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Function invoked once the web page is loaded into the browser.      *
 *  Initialize dynamic functionality of elements.                       *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        Window object                                       *
 ************************************************************************/
function onLoad()
{
    let form                    = document.locForm;

    let opener                  = null;
    if (window.frameElement && window.frameElement.opener)
        opener                  = window.frameElement.opener;
    else
        opener                  = window.opener;
    let callersFormName         = form.formname.value;
    if (callersFormName.length > 0 && opener && form.idar.value > 0)
    {
        let callersForm         = opener.document.forms[callersFormName];
        if (callersForm)
        {
            callersForm.setIdar(form.idar.value);
        }       // have callers form
    }           // invoked from another window with a form name

    // set action methods for elements
    form.addEventListener('submit', validateForm);
    form.addEventListener('reset', resetForm);

    // activate handling of key strokes in text input fields
    // including support for context specific help
    let formElts                = form.elements;
    for (let i = 0; i < formElts.length; ++i)
    {                   // loop through all elements in the form
        let element             = formElts[i];

        let name;
        if (element.name && element.name.length > 0)
            name                = element.name;
        else
            name                = element.id;

        switch(name)
        {               // act on field name
            case 'AddrName':
            {
                element.addEventListener('keydown', keyDown);
                element.addEventListener('change', changeAddress);
                break;
            }           // address name field

            case 'HomePage':
            {
                element.addEventListener('keydown', keyDown);
                element.addEventListener('change', change);
                break;
            }           // address name field

            case 'Merge':
            {
                element.addEventListener('click', mergeDuplicates);
                break;
            }

            case 'showMap':
            {
                element.addEventListener('click', showMap);
                break;
            }

            case 'getMap':
            {           // <button id='getMap'>
                element.addEventListener('click', getMap);
                break;
            }           // <button id='getMap'>

            case 'Pictures':
            {           // <button id='Pictures'>
                element.addEventListener('click', editPictures);
                break;
            }           // <button id='Pictures'>

            case 'Close':
            {           // <button id='Close'>
                element.addEventListener('click', closeDialog);
                break;
            }           // <button id='Close'>

            case 'Delete':
            {           // <button id='Delete'>
                element.addEventListener('click', deleteAddress);
                let idarElt     = document.getElementById('idar');
                if (idarElt && idarElt.value == 0)
                    element.disabled    = true;
                break;
            }           // <button id='Delete'>

            default:
            {
                element.addEventListener('keydown', keyDown);
                element.addEventListener('change', change);
            }           // all other fields
        }               // act on field name
    }                   // loop through all elements in the form

    hideRightColumn();
}       // function onLoad

/************************************************************************
 *  function validateForm                                               *
 *                                                                      *
 *  Ensure that the data entered by the user has been minimally         *
 *  validated before submitting the form.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <form>                                  *
 ************************************************************************/
function validateForm()
{
    return true;
}       // function validateForm

/************************************************************************
 *  function resetForm                                                  *
 *                                                                      *
 *  This method is called when the user requests the form               *
 *  to be reset to default values.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <form>                                  *
 ************************************************************************/
function resetForm()
{
    return true;
}       // function resetForm

/************************************************************************
 *  function changeAddress                                              *
 *                                                                      *
 *  Handle a change to the Address field.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <input type='text'>                     *
 ************************************************************************/
function changeAddress()
{
    changeElt(this);        // apply default services

    let value           = this.value;

    this.form.AddrSort.value    = value;
}       // function changeAddress

/************************************************************************
 *  function deleteAddress                                              *
 *                                                                      *
 *  This method requests the deletion of the address.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button id='Delete'>                    *
 ************************************************************************/
function deleteAddress(ev)
{
    let form                    = this.form;
    if (form.action)
        form.action.value       = 'delete'
    form.submit();
    ev.stopPropagation();
    return false;
}       // function deleteAddress

/************************************************************************
 *  function closeDialog                                                *
 *                                                                      *
 *  This method closes the frame without updating the record.           *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button id='Close'>                     *
 ************************************************************************/
function closeDialog(ev)
{
    closeFrame();
    ev.stopPropagation();
    return false;
}       // function closeDialog

/************************************************************************
 *  function mergeDuplicates                                            *
 *                                                                      *
 *  This method is called when the user requests to merge               *
 *  all duplicates of the current address                               *
 *                                                                      *
 *  Input:                                                              *
 *      this    instance of <button id='Merge'>                         *
 ************************************************************************/
function mergeDuplicates()
{
    let form                    = document.locForm;
    let mainIdar                = form.idar.value;
    let kind                    = form.Kind.value;
    let parms                   = {};
    parms['to']                 = mainIdar;
    parms['kind']               = kind;
    let from                    = '';

    // disable the submit button until this long running operation is complete
    let submit                  = form.Submit;
    submit.disabled             = true;

    // construct the 'from' parameter
    for (let i = 0; i < form.elements.length; i++)
    {                   // loop through duplicates
        let elt             = form.elements[i];
        if (elt.name)
        {               // element has a name
            if (elt.name.substr(0,7) == 'DupIdar')
            {           // element containing duplicate idar
                from        += elt.value + ',';
            }           // element containing duplicate idar
        }               // element has a name
    }                   // loop through duplicates

    // trim off trailing comma, if any
    if (from.length > 0)
        from                = from.substr(0,from.length - 1);
    else
        return;     // nothing to do
    parms['from']           = from;

    // invoke script to merge addresss and return XML result
    if (form.debug.value == 'Y')
    {
        form.debug.value    = '';
        form.action         = 'mergeAddressesXml.php';
        form.submit();
    }
    else
    {           // use AJAX  
        HTTP.post('/FamilyTree/mergeAddressesXml.php',
                  parms,
                  gotMerge,
                  noMerge);
    }           // use AJAX  
}       // function mergeDuplicates

/************************************************************************
 *  function gotMerge                                                   *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  a completed merge is retrieved from the database.                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc      an XML document object with the output of           *
 *                  script mergeAddressesXml.php                        *
 ************************************************************************/
function gotMerge(xmlDoc)
{
    let form            = document.locForm;

    // disable the submit button until this long running operation
    // is complete
    let button          = form.Submit;
    button.disabled     = false;

    let root            = xmlDoc.documentElement;
    if (root.nodeName == 'update')
    {
        window.location.reload();  // refresh
    }
    else
    {       // error
        let msg         = "Error: ";
        for(let i = 0; i < root.childNodes.length; i++)
        {       // loop through children
            let node    = root.childNodes[i];
            if (node.nodeValue != null)
                msg     += node.nodeValue;
        }       // loop through children
        alert (msg);
    }       // error
}       // function gotMerge

/************************************************************************
 *  function noMerge                                                    *
 *                                                                      *
 *  This method is called if there is no response                       *
 *  file.                                                               *
 ************************************************************************/
function noMerge()
{
    alert('Address.js: noMerge: ' +
          'Script mergeAddressesXml.php not found on server. ');
}       // function noMerge

/************************************************************************
 *  function showMap                                                    *
 *                                                                      *
 *  This function is called if the user clicks on the show Map button.  *
 *  It displays a map using Google maps support.                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button id='ShowMap'>                   *
 *      ev          click Event                                         *
 ************************************************************************/
function showMap(ev)
{
    let button      = this;
    let form        = button.form;

    // if latitude and longitude specified in database, display the
    // map based upon those values
    let lat         = parseFloat(form.Latitude.value.replaceAll(',','.'));
    let lng         = parseFloat(form.Longitude.value.replaceAll(',','.'));
    let locn        = form.Address1.value;

    if (form.Address2.value.length > 0)
    {
        if (locn.length > 0)
            locn        += ', ';
        locn            += form.Address2.value;
    }
    if (form.City.value.length > 0)
    {
        if (locn.length > 0)
            locn        += ', ';
        locn            += form.City.value;
    }
    if (form.State.value.length > 0)
    {
        if (locn.length > 0)
            locn        += ', ';
        locn            += form.State.value;
    }
    if (form.Country.value.length > 0)
    {
        if (locn.length > 0)
            locn        += ', ';
        locn            += form.Country.value;
    }
    if (lat == 0 && lng == 0 && locn.length == 0)
    {
        popupAlert("Address.js: showMap: " +
                    "No information available for displaying map",
                   this);
        ev.stopPropagation();
        return false;
    }                   // nothing to display

    let zoom            = parseInt(form.Zoom.value, 10);

    if (lat != 0 || lng != 0)
    {                   // display map for coordinates
        try {
            displayMap(new google.maps.LatLng(lat, lng), zoom, locn);
        }
        catch(e)
        {
            popupAlert("Address.js: showMap: " +
                  "Unable to use google maps to display map of location: " +
                  "message='" + e.message + "', " +
                  "lat=" + lat + ", lng=" + lng + ", zoom=" + zoom,
                       this);
        }
    }                   // display map for coordinates
    else
    if (geocoder !== null)
    {                   // use Geocoder
        let searchName      = form.searchName.value;
        geocoder.geocode( { 'address': searchName},
                    function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                displayMap(results[0].geometry.location, zoom, locn);
            } else {    // geocode failed
                popupAlert("Address.js: showMap: " +
                        "Geocode for '" + searchName +
                       "' was not successful for the following reason: " +
                        status,
                       this);
            }           // geocode failed
        });             // end of inline function and invocation of geocode
    }                   // use Geocoder
    ev.stopPropagation();
    return false;
}       // function showMap

/************************************************************************
 *  function displayMap                                                 *
 *                                                                      *
 *  This function is called to display a Google maps map                *
 *  of the location.                                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      latlng      instance of google.maps.LatLng or null              *
 *      zoomlevel   integer zoom level for map                          *
 *      locn        string containing name of address
 ************************************************************************/
function displayMap(latlng, zoomlevel, locn)
{
    let button              = document.getElementById('showMap');
    if (latlng !== null)
    {                   // location resolved
        let form            = document.locForm;
        let notes           = form.Notes;
        let mapDiv          = document.getElementById("mapDiv");
        mapDiv.style.left   = getOffsetLeft(notes) + "px";
        mapDiv.style.top    = getOffsetTop(notes) + "px";
        mapDiv.style.width  = getOffsetLeft(button) + "px";
        button.style.zIndex = 7;
        show(mapDiv);
        let myOptions       = {
                              zoom: zoomlevel,
                              center: latlng,
                              mapTypeId: google.maps.MapTypeId.ROADMAP
                            };
        try {
            map             = new google.maps.Map(mapDiv,
                                                  myOptions);
        }
        catch(e) {
            popupAlert("Address.js: displayMap: " +
                  "new google.maps.Map failed: message='" + e.message + "'",
                        button);
        }
        try {
            new google.maps.Marker({map: map, position: latlng });
        }
        catch(e) {
            popupAlert("Address.js: displayMap: " +
                  "new google.maps.Marker failed: message='" + e.message + "'", button);
        }
        button.removeEventListener('click', showMap);
        button.addEventListener('click', hideMap);
        while(button.firstChild)
            button.removeChild(button.firstChild)
        let template        = document.getElementById('hideMapTemplate');
        for(let childTemp = template.firstChild;
                childTemp;
                childTemp = childTemp.nextSibling)
            button.appendChild(childTemp.cloneNode(true));
    }       // location resolved
    else
    {       // location not resolved
        popupAlert("Address.js: displayMap: " +
                        "location " + locn + " not resolved",
                   button);
    }       // location not resolved
}       // function displayMap

/************************************************************************
 *  function hideMap                                                    *
 *                                                                      *
 *  This function is called if the user clicks on the Hide Map button.  *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <button id='HideMap'>                   *
 *      ev          click Event                                         *
 ************************************************************************/
function hideMap(ev)
{
    let button              = this;
    let mapDiv              = document.getElementById("mapDiv");
    mapDiv.style.display    = 'none';   // hide
    while(button.firstChild)
        button.removeChild(button.firstChild)
    let template            = document.getElementById('showMapTemplate');
    for(let childTemp = template.firstChild;
            childTemp;
            childTemp       = childTemp.nextSibling)
        button.appendChild(childTemp.cloneNode(true));
    button.removeEventListener('click', hideMap);
    button.addEventListener('click', showMap);
    ev.stopPropagation();
    return false;
}       // function hideMap

/************************************************************************
 *  function getMap                                                     *
 *                                                                      *
 *  This function is called to display info from the map.               *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        a <button> element                                  *
 ************************************************************************/
function getMap(ev)
{
    if (map !== null && map.getMapTypeId() !== null)
    {
        let form                = document.locForm;
        let center              = map.getCenter();
        let zoom                = map.getZoom();
        let min;
        let sec;
        let deg;
        let neg;

        // translate latitude to Legacy internal format
        neg                     = center.lat() < 0;
        let latsecs             = Math.abs(center.lat())*3600;
        min                     = Math.floor(latsecs/60);
        sec                     = latsecs - 60*min;
        deg                     = Math.floor(min/60);
        min                     = Math.floor(min - 60*deg);
        latsecs                 = 10000*deg + 100*min + sec;
        if (neg)
            latsecs             = -latsecs;
        form.Latitude.value     = latsecs;

        // translate longitude to Legacy internal format
        neg                     = center.lng() < 0;
        let lngsecs             = Math.abs(center.lng())*3600;
        min                     = Math.floor(lngsecs/60);
        sec                     = lngsecs - 60*min;
        deg                     = Math.floor(min/60);
        min                     = Math.floor(min - 60*deg);
        lngsecs                 = 10000*deg + 100*min + sec;
        if (neg)
            lngsecs             = -lngsecs;
        form.Longitude.value    = lngsecs;

        // set the zoom factor
        form.Zoom.value         = zoom;
    }
    else
    {
        popupAlert("Address.js: getMap: " +
                        "map not initialized",
                   this);
    }
    ev.stopPropagation();
    return false;
}       // function getMap

/************************************************************************
 *  function editPictures                                               *
 *                                                                      *
 *  This is the onclick method of the "Edit Pictures" button.           *
 *  It is called when the user requests to edit                         *
 *  information about the Pictures associated with the source           *
 *  that are recorded by instances of Picture.                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        a <button> element                                  *
 ************************************************************************/
function editPictures()
{
    let form                        = this.form;
    let picIdType                   = form.PicIdType.value;
    let idlr;

    if (form.idlr && form.idlr.value > 0)
    {       // idlr present in form
        idlr                        = form.idlr.value;
        let iframe                  = window.frameElement;
        let childFrameClass         = 'right';
        if (iframe)
        {
            if (iframe.className == 'right')
                childFrameClass     = 'left';
        }
        let lang                    = 'en';
        if ('lang' in args)
            lang                    = args.lang;
        openFrame("pictures",
                  "editPictures.php?idlr=" + idlr +
                                    "&idtype=" + picIdType +
                                    "&lang=" + lang, 
                  childFrameClass);
    }       // idlr present in form
    else
    {       // unable to identify record to associate with
        popupAlert("Location.js: editPictures: " +
                        "Unable to identify record to associate pictures with",
                   this);
    }       // unable to identify record to associate with
    return true;
}       // function editPictures

