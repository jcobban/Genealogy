/************************************************************************
 *  Address.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Address.php.													*
 *																		*
 *  History:															*
 *		2010/12/05		created											*
 *		2010/12/08		add new mailing address							*
 *		2011/03/02		change name of submit button to 'Submit'		*
 *		2012/01/13		change class names								*
 *		2013/02/23		add Google maps support							*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/07/31		defer setup of facebook link					*
 *		2014/08/28		correct name of button for merging addresses	*
 *						if debugging merge addresses by submitting form	*
 *						instead of by AJAX to permit the invoker to see	*
 *						the generated XML from mergeAddressesXml.php	*
 *		2014/10/12		use routine show to display map					*
 *		2015/02/10		support being opened in <iframe>				*
 *		2015/05/27		use absolute URLs for AJAX						*
 *						close and hide dialog if in a frame				*
 *		2015/06/02		use main style for TinyMCE editor				*
 *		2015/06/16		open list of pictures in other half				*
 *						of the window									*
 *		2016/12/09		use pre-determined search name for				*
 *						maps geolocate									*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/09/25      disable delete button for new record            *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  childFrameClass										                *
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	= 'right';

/************************************************************************
 *  Parameters for Google Maps											*
 ************************************************************************/
var	map;		// instance of google.maps.Map
var	geocoder;	// instance of google.maps.Geocoder

/************************************************************************
 *  function onLoad										                *
 *																		*
 *  Function invoked once the web page is loaded into the browser.		*
 *  Initialize dynamic functionality of elements.						*
 ************************************************************************/
function onLoad()
{
    // determine in which half of the window child frames are opened
    if (window.frameElement)
    {				// dialog opened in half frame
		childFrameClass		= window.frameElement.className;
		if (childFrameClass == 'left')
		    childFrameClass	= 'right';
		else
		    childFrameClass	= 'left';
    }				// dialog opened in half frame

    var	form				= document.locForm;

    // support for displaying map and geocoding
    try {
		geocoder	= new google.maps.Geocoder();
    }
    catch(e)
    {
		geocoder	= null;
    }
    map			= null;

    var	opener	= null;
    if (window.frameElement && window.frameElement.opener)
		opener	= window.frameElement.opener;
    else
		opener	= window.opener;
    var callersFormName			= form.formname.value;
    if (callersFormName.length > 0 && opener && form.idar.value > 0)
    {
		var callersForm	= opener.document.forms[callersFormName];
		if (callersForm)
		{
		    callersForm.setIdar(form.idar.value);
		}		// have callers form
    }			// invoked from another window with a form name

    // set action methods for elements
    form.onsubmit		 	= validateForm;
    form.onreset 			= resetForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {		        // loop through all elements in the form
		var element	= formElts[i];

		var	name;
		if (element.name && element.name.length > 0)
		    name	= element.name;
		else
		    name	= element.id;

		switch(name)
		{		    // act on field name
		    case 'AddrName':
		    {
				element.onkeydown	= keyDown;
				element.onchange	= changeAddress;
				break;
		    }		// address name field

		    case 'HomePage':
		    {
				element.onkeydown	= keyDown;
				element.onchange	= change;
				element.onclick		= openHomePage;
				break;
		    }		// address name field

		    case 'Merge':
		    {
				element.onclick		= mergeDuplicates;
				break;
		    }

		    case 'showMap':
		    {
				element.onclick		= showMap;
				break;
		    }

		    case 'getMap':
		    {			// <button id='getMap'>
				element.onclick		= getMap;
				break;
		    }			// <button id='getMap'>

		    case 'Pictures':
		    {			// <button id='Pictures'>
				element.onclick	= editPictures;
				break;
		    }			// <button id='Pictures'>

		    case 'Close':
		    {			// <button id='Close'>
				element.onclick	= closeDialog;
				break;
		    }			// <button id='Close'>

		    case 'Delete':
		    {			// <button id='Delete'>
				element.onclick	= deleteAddress;
                var idarElt     = document.getElementById('idar');
                if (idarElt && idarElt.value == 0)
                    element.disabled    = true;
				break;
		    }			// <button id='Delete'>

		    default:
		    {
				element.onkeydown	= keyDown;
				element.onchange	= change;
		    }		// all other fields
		}		    // act on field name
    }		        // loop through all elements in the form

    hideRightColumn();
}		// function onLoad

/************************************************************************
 *  function validateForm										        *
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this		instance of <form>									*
 ************************************************************************/
function validateForm()
{
    return true;
}		// function validateForm

/************************************************************************
 *  function resetForm												    *
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *																		*
 *  Input:																*
 *		this		instance of <form>									*
 ************************************************************************/
function resetForm()
{
    return true;
}		// function resetForm

/************************************************************************
 *  function changeAddress										        *
 *																		*
 *  Handle a change to the Address field.								*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text'>						*
 ************************************************************************/
function changeAddress()
{
    changeElt(this);		// apply default services

    var	value			= this.value;

    this.form.AddrSort.value	= value;
}		// function changeAddress

/************************************************************************
 *  function openHomePage										        *
 *																		*
 *  User has clicked with the mouse on the HomePage input field			*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text'>						*
 ************************************************************************/
function openHomePage()
{
    if (this.value.length > 5)
		window.open(this.value);	// open in a new tab or window
    else
		alert("Address.js: openHomePage: " +
		      "Invalid URL='" + this.value + "'");
    return false;
}		// function openHomePage

/************************************************************************
 *  function deleteAddress										        *
 *																		*
 *  This method requests the deletion of the address.					*
 *																		*
 *  Input:																*
 *		this		instance of <button id='Delete'>					*
 ************************************************************************/
function deleteAddress()
{
    var	form			        = this.form;
    if (form.action)
		form.action.value	    = 'delete'
    form.submit();
    return false;
}		// function deleteAddress

/************************************************************************
 *  function closeDialog												*
 *																		*
 *  This method closes the frame without updating the record.			*
 *																		*
 *  Input:																*
 *		this		instance of <button id='Close'>						*
 ************************************************************************/
function closeDialog()
{
    closeFrame();
    return false;
}		// function closeDialog

/************************************************************************
 *  function mergeDuplicates										    *
 *																		*
 *  This method is called when the user requests to merge				*
 *  all duplicates of the current address								*
 *																		*
 *  Input:																*
 *		this	instance of <button id='Merge'>							*
 ************************************************************************/
function mergeDuplicates()
{
    var	form					= document.locForm;
    var mainIdar				= form.idar.value;
    var kind					= form.Kind.value;
    var	parms					= {};
    parms['to']					= mainIdar;
    parms['kind']				= kind;
    var	from		            = '';

    // disable the submit button until this long running operation is complete
    var	submit	            	= form.Submit;
    submit.disabled         	= true;

    // construct the 'from' parameter
    for (var i = 0; i < form.elements.length; i++)
    {		            // loop through duplicates
		var	elt	            = form.elements[i];
		if (elt.name)
		{		        // element has a name
		    if (elt.name.substr(0,7) == 'DupIdar')
		    {		    // element containing duplicate idar
				from	    += elt.value + ',';
		    }		    // element containing duplicate idar
		}		        // element has a name
    }		            // loop through duplicates

    // trim off trailing comma, if any
    if (from.length > 0)
		from	            = from.substr(0,from.length - 1);
    else
		return;		// nothing to do
    parms['from']	        = from;

    // invoke script to merge addresss and return XML result
    if (form.debug.value == 'Y')
    {
		form.debug.value	= '';
		form.action		    = 'mergeAddressesXml.php';
		form.submit();
    }
    else
    {			// use AJAX	 
		HTTP.post('/FamilyTree/mergeAddressesXml.php',
				  parms,
				  gotMerge,
				  noMerge);
    }			// use AJAX	 
}		// function mergeDuplicates

/************************************************************************
 *  function gotMerge												    *
 *																		*
 *  This method is called when the XML file representing				*
 *  a completed merge is retrieved from the database.					*
 ************************************************************************/
function gotMerge(xmlDoc)
{
    var	form		    = document.locForm;

    // disable the submit button until this long running operation is complete
    var	button		    = form.Submit;
    button.disabled	    = false;

    var	root	        = xmlDoc.documentElement;
    if (root.nodeName == 'update')
    {
		window.location	= window.location;	// refresh
    }
    else
    {		// error
		var	msg	= "Error: ";
		for(var i = 0; i < root.childNodes.length; i++)
		{		// loop through children
		    var node	= root.childNodes[i];
		    if (node.nodeValue != null)
				msg	+= node.nodeValue;
		}		// loop through children
		alert (msg);
    }		// error
}		// function gotMerge

/************************************************************************
 *  function noMerge												    *
 *																		*
 *  This method is called if there is no response						*
 *  file.																*
 ************************************************************************/
function noMerge()
{
    alert('Address.js: noMerge: ' +
		  'Script mergeAddressesXml.php not found on server. ');
}		// function noMerge

/************************************************************************
 *  function showMap												    *
 *																		*
 *  This function is called if the user clicks on the show Map button.	*
 *  It displays a map using Google maps support.						*
 *																		*
 *  Input:																*
 *		this		instance of <button id='ShowMap'>					*
 ************************************************************************/
function showMap()
{
    var	button		= this;
    var	form		= button.form;
    var latlng		= null;		// Google maps latitude/longitude
    var	mapDiv		= document.getElementById("mapDiv");

    // if latitude and longitude specified in database, display the
    // map based upon those values
    var lat		    = form.Latitude.value;
    var lng		    = form.Longitude.value;

    var locn		= form.Address1.value;
    if (form.Address2.value.length > 0)
    {
		if (locn.length > 0)
		    locn	+= ', ';
		locn		+= form.Address2.value;
    }
    if (form.City.value.length > 0)
    {
		if (locn.length > 0)
		    locn	+= ', ';
		locn		+= form.City.value;
    }
    if (form.State.value.length > 0)
    {
		if (locn.length > 0)
		    locn	+= ', ';
		locn		+= form.State.value;
    }
    if (form.Country.value.length > 0)
    {
		if (locn.length > 0)
		    locn	+= ', ';
		locn		+= form.Country.value;
    }
    if (lat == 0 && lng == 0 && locn.length == 0)
    {
		alert("Address.js: showMap: " +
		      "No information available for displaying map");
		return false;
    }		            // nothing to display

    var zoom		= Number(form.Zoom.value);

    if (lat != '0' || lng != '0')
    {		            // display map for coordinates
		// convert latitude as stored in database to decimal degrees
		var	latn		= (lat - 0.0) < 0.0;
		lat			    = Math.abs(lat - 0.0);
		var	latd		= Math.floor(lat/10000);
		lat			    = lat - latd*10000;
		var	latm		= Math.floor(lat/100);
		var	lats		= lat - latm*100;
		lat			    = latd + (latm/60.0) + (lats/3600.0);
		if (latn)
		    lat		    = -lat;

		// convert longitude as stored in database to decimal degrees
		var	lngn		= (lng - 0.0) < 0.0;
		lng			    = Math.abs(lng - 0.0);
		var	lngd		= Math.floor(lng/10000);
		lng			    = lng - lngd*10000;
		var	lngm		= Math.floor(lng/100);
		var	lngs		= lng - lngm*100;
		lng			    = lngd + (lngm/60.0) + (lngs/3600.0);
		if (lngn)
		    lng		    = -lng;

		try {
		    displayMap(new google.maps.LatLng(lat, lng), zoom);
		}
		catch(e)
		{
		    alert("Address.js: showMap: " +
				  "Unable to use google maps to display map of location: " +
				  "message='" + e.message + "', " +
				  "lat=" + lat + ", lng=" + lng + ", zoom=" + zoom);
		}
    }		            // display map for coordinates
    else
    if (geocoder !== null)
    {		            // use Geocoder
		var searchName		= form.searchName.value;
		geocoder.geocode( { 'address': searchName},
					function(results, status) {
		    if (status == google.maps.GeocoderStatus.OK) {
				displayMap(results[0].geometry.location, zoom);
		    } else {	// geocode failed
				popupAlert("Address.js: showMap: " +
						"Geocode for '" + searchName +
					   "' was not successful for the following reason: " +
						status,
					   this);
		    }	        // geocode failed
		});	            // end of inline function and invocation of geocode
    }		            // use Geocoder
    return false;
}		// function showMap

/************************************************************************
 *  function displayMap												    *
 *																		*
 *  This function is called to display a Google maps map				*
 *  of the location.													*
 ************************************************************************/
function displayMap(latlng, zoomlevel)
{
    if (latlng !== null)
    {		            // location resolved
		var	button		    = document.getElementById('showMap');
		var	form	    	= document.locForm;
		var	notes	    	= form.Notes;
		mapDiv		    	= document.getElementById("mapDiv");
		mapDiv.style.left	= getOffsetLeft(notes) + "px";
		mapDiv.style.top	= getOffsetTop(notes) + "px";
		show(mapDiv);
		var myOptions = {
				  zoom: zoomlevel,
				  center: latlng,
				  mapTypeId: google.maps.MapTypeId.ROADMAP
				};
		try {
		    map	            = new google.maps.Map(mapDiv,
						                          myOptions);
		}
		catch(e) {
		    alert("Address.js: displayMap: " +
		          "new google.maps.Map failed: message='" + e.message + "'");
		}
		try {
		    var marker      = new google.maps.Marker({map: map, 
							                    	 position: latlng });
		}
		catch(e) {
		    alert("Address.js: displayMap: " +
		          "new google.maps.Marker failed: message='" + e.message + "'");
		}
		button.onclick		= hideMap;
		while(button.firstChild)
		    button.removeChild(button.firstChild)
		var template	    = document.getElementById('hideMapTemplate');
		for(var childTemp = template.firstChild;
				childTemp;
				childTemp = childTemp.nextSibling)
		    button.appendChild(childTemp.cloneNode(true));
    }		// location resolved
    else
    {		// location not resolved
		alert("Address.js: displayMap: " +
				"location " + locn + " not resolved");
    }		// location not resolved
}		// displayMap

/************************************************************************
 *  function hideMap												    *
 *																		*
 *  This function is called if the user clicks on the Hide Map button.	*
 *																		*
 *  Input:																*
 *		this		instance of <button id='HideMap'>					*
 ************************************************************************/
function hideMap()
{
    var	button          	= this;
    var	mapDiv          	= document.getElementById("mapDiv");
    mapDiv.style.display	= 'none';	// hide
    while(button.firstChild)
		button.removeChild(button.firstChild)
    var template	        = document.getElementById('showMapTemplate');
    for(var childTemp = template.firstChild;
		    childTemp;
		    childTemp = childTemp.nextSibling)
		button.appendChild(childTemp.cloneNode(true));
    button.onclick		    = showMap;
    return false;
}		// function hideMap

/************************************************************************
 *  function getMap												        *
 *																		*
 *  This function is called to display info from the map.				*
 ************************************************************************/
function getMap()
{
    if (map !== null && map.getMapTypeId() !== null)
    {
		var form	= document.locForm;
		var center	= map.getCenter();
		var zoom	= map.getZoom();
		var min;
		var sec;
		var deg;
		var neg;

		// translate latitude to Legacy internal format
		neg		= center.lat() < 0;
		var latsecs	= Math.abs(center.lat())*3600;
		min		= Math.floor(latsecs/60);
		sec		= latsecs - 60*min;
		deg		= Math.floor(min/60);
		min		= Math.floor(min - 60*deg);
		latsecs		= 10000*deg + 100*min + sec;
		if (neg)
		    latsecs	= -latsecs;
		form.Latitude.value	= latsecs;

		// translate longitude to Legacy internal format
		neg		= center.lng() < 0;
		var lngsecs	= Math.abs(center.lng())*3600;
		min		= Math.floor(lngsecs/60);
		sec		= lngsecs - 60*min;
		deg		= Math.floor(min/60);
		min		= Math.floor(min - 60*deg);
		lngsecs		= 10000*deg + 100*min + sec;
		if (neg)
		    lngsecs	= -lngsecs;
		form.Longitude.value	= lngsecs;

		// set the zoom factor
		form.Zoom.value		= zoom;
    }
    else
    {
		alert("Address.js: getMap: " +
		      "map not initialized");
    }
    return false;
}		// function getMap

/************************************************************************
 *  function editPictures										        *
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  			*
 *  It is called when the user requests to edit							*
 *  information about the Pictures associated with the source			*
 *  that are recorded by instances of Picture.							*
 *																		*
 *  Parameters:															*
 *		this		a <button> element									*
 ************************************************************************/
function editPictures()
{
    var	form		= this.form;
    var	picIdType	= form.PicIdType.value;
    var	idar;

    if (form.idar && form.idar.value > 0)
    {		// idar present in form
		idar		= form.idar.value;
		openFrame("pictures",
				  "editPictures.php?idar=" + idar +
						    "&idtype=" + picIdType, 
				  childFrameClass);
    }		// idar present in form
    else
    {		// unable to identify record to associate with
		popupAlert("Address.js: editPictures: " +
				   "Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	// editPictures

