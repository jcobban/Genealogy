/************************************************************************
 *  Location.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Location.php.													*
 *																		*
 *  History:															*
 *		2010/09/25		created											*
 *		2011/03/02		change name of submit button to 'Submit'		*
 *		2011/10/01		add support for google maps						*
 *		2011/10/24		use google.maps.Geocoder to resolve locations	*
 *						convert "Display Individuals using this			*
 *						Location" link to a <button>					*
 *		2012/01/13		change class names								*
 *		2012/05/08		templatize replacement of button text			*
 *		2012/11/03		use tinyMCE to edit extended text notes			*
 *		2012/11/08		expand abbreviations in location				*
 *		2012/12/31		set sorted location name to reflect lot number	*
 *						order within a street or concession				*
 *		2013/02/23		use switch in element initialization			*
 *		2013/03/12		changeLocation renamed to locationChanged		*
 *		2013/04/12		add support for displaying a boundary			*
 *		2013/04/16		all knowledge of Legacy longitude and latitude	*
 *						format is now hidden inside class Location		*
 *		2013/04/19		also pad out concession number					*
 *		2013/04/27		add more lot parts								*
 *		2013/05/23		popup loading indicator while running merge		*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/05/25		boundary polygon not updated					*
 *		2014/09/29		fix error in checking for county name			*
 *		2014/10/12		use method show to display popups				*
 *		2015/05/22		check if boundary set before using it			*
 *		2015/06/02		use main style for TinyMCE editor				*
 *		2015/06/16		open list of pictures in other half				*
 *						of the window									*
 *		2015/06/22		only retain coordinates to 6 digits after the	*
 *						decimal point, that is to 1/1,000,000th of a	*
 *						degree or 0.1 metre resolution					*
 *		2015/12/12		do showMap if user clicks on getMap first		*
 *		2016/06/12		try another way to resolve addresses including	*
 *						county names									*
 *		2016/12/09		use pre-determined search name for				*
 *						maps geolocate									*
 *		2016/12/29		adjust search name if displayed name changes	*
 *		2017/09/09		renamed to Location.js							*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.onload	= onLoadLocation;

/************************************************************************
 *  ontarioCountyNames													*
 *																		*
 *		The google database of location names does not generally		*
 *		include the county name, so it is necessary to remove the		*
 *		county name before passing the location name to the lookup.		*
 *		If this table were defined as an array, then checking for a		*
 *		match would require linearly searching the array, using the		*
 *		method indexOf, which is not even defined in IE<9!  Defining	*
 *		it as an object uses a hash table lookup for matches.			*
 ************************************************************************/
var ontarioCountyNames	= {
		'Algoma'				: 1,
		'Brant'					: 1,
		'Bruce'					: 1,
		'Carleton'				: 1,
		'Dufferin'				: 1,
		'Dundas'				: 1,
		'Durham'				: 1,
		'Elgin'					: 1,
		'Essex'					: 1,
		'Frontenac'				: 1,
		'Glengarry'				: 1,
		'Grenville'				: 1,
		'Grey'					: 1,
		'Haldimand'				: 1,
		'Haliburton'			: 1,
		'Halton'				: 1,
		'Hastings'				: 1,
		'Huron'					: 1,
		'Kenora'				: 1,
		'Kent'					: 1,
		'Lambton'				: 1,
		'Lanark'				: 1,
		'Leeds'					: 1,
		'Lincoln'				: 1,
		'Middlesex'				: 1,
		'Muskoka'				: 1,
		'Nippising'				: 1,
		'Norfolk'				: 1,
		'Northumberland'		: 1,
		'Ontario'				: 1,
		'Oxford'				: 1,
		'Peel'					: 1,
		'Perth'					: 1,
		'Pontiac'				: 1,
		'Prescott'				: 1,
		'Prince Edward'			: 1,
		'Renfrew'				: 1,
		'Russell'				: 1,
		'Simcoe'				: 1,
		'Stormont'				: 1,
		'Temiskaming'			: 1,
		'Victoria'				: 1,
		'Welland'				: 1,
		'Wellington'			: 1,
		'Wentworth'				: 1,
		'York' 					: 1};

var county	= null;
var locn	= null;
var options = {"timeout"    : false};

HTTP.get('/Canada/CountiesListJson.php?domain=CAON',
         gotCounties,
         options);

/************************************************************************
 *  gotCounties															*
 *																		*
 *  This method is called when the JSON document representing			*
 *  the list of counties is received from the server.                   *
 ************************************************************************/
function gotCounties(obj)
{
    if (typeof(obj) == 'object')
    {
        ontarioCountyNames      = obj;
    }
    else
        alert('gotCounties: ' + typeof obj);
}       // function gotCounties

/************************************************************************
 *  Parameters and objects used for the interface to Google maps		*
 ************************************************************************/

// instance of google.maps.Map for displaying the map
var	map		= null;

// array of instances of google.maps.LatLng for boundary of area
var	path		= [];

// instance of google.maps.PolyOptions for editing boundary
var	polyOptionsEdit	= {strokeColor: "red", 
			   strokeOpacity: 0.5,
			   strokeWeight: 2,
			   editable: true};

// instance of google.maps.PolygonOptions for displaying boundary
var	polyOptionsShow	= {strokeColor: "red", 
			   strokeOpacity: 0.5,
			   strokeWeight: 2,
			   fillColor: "black",
			   fillOpacity: 0.10};

// instance of google.maps.Polyline for displaying boundary
var	boundary	= null;

// instance of google.maps.Geocoder for resolving place names
var	geocoder	= null;

// specify style for tinyMCE editing
tinyMCE.init({
	mode			: "textareas",
	theme			: "advanced",
	plugins 		: "spellchecker,advhr,preview", 
		
	// Theme options - button# indicated the row# only
	theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,fontselect,fontsizeselect,formatselect",
	theme_advanced_buttons2 : "cut,copy,paste,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	forced_root_block	: false,
	forced_root_block	: false,
	content_css		: "/styles.css",

});

/************************************************************************
 *  onLoadLocation														*
 *																		*
 *  This function is invoked once the page is completely loaded into the*
 *  browser.  Initialize dynamic behavior of elements.					*
 ************************************************************************/
function onLoadLocation()
{
    pageInit();
    // support for displaying map and geocoding
    try {
		geocoder	= new google.maps.Geocoder();
    }
    catch(e)
    {		// ignore, may not be used
    }		// ignore, may not be used

    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];

		if (form.name == 'locForm')
		{	// set action methods for form
		    form.onsubmit		 	= validateForm;
		    form.onreset 			= resetForm;
		}	// set action methods for form

		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    actMouseOverHelp(element);

		    // take action specific to element
		    var	name;
		    if (element.name && element.name.length > 0)
			name	= element.name;
		    else
			name	= element.id;

		    switch(name)
		    {		// act on field name
				case 'Location':
				{
				    element.onkeydown	= keyDown;
				    element.abbrTbl	= LocAbbrs;
				    element.onchange	= locationChanged;
				    break;
				}		// location name field
	
				case 'mergeDuplicates':
				{
				    element.onclick	= mergeDuplicates;
				    break;
				}		// mergeDuplicates button
	
				case 'showMap':
				{
				    element.onclick	= showMap;
				    break;
				}		// showMap button
	
				case 'getMap':
				{
				    element.onclick	= getMap;
				    break;
				}		// getMap button
	
				case 'References':
				{
				    element.onclick	= displayReferences;
				    break;
				}		// References button
	
				case 'Boundary':
				{
				    var	latPatt	= /\(([0-9.\-]+)/;
				    var	lngPatt	= /([0-9.\-]+)\)/;
				    var	boundStr	= element.value;
				    var	readonly	= form.Zoom.readOnly;
	
				    if (boundStr.length > 0)
				    {		// have a boundary to display
						var	bounds	= boundStr.split(',');
						for (var ib=0; ib < bounds.length; ib++)
						{		// loop through each element
						    var	bound	= bounds[ib];
						    var rxRes	= latPatt.exec(bound);
						    if (rxRes != null)
						    {		// latitude 
								var lat	= rxRes[1];
								ib++;
								bound	= bounds[ib];
								rxRes	= lngPatt.exec(bound);
								if (rxRes != null)
								{		// longitude)
								    var lng		= rxRes[1];
								    var latLng	= new google.maps.LatLng(lat,
													 lng);
								    path.push(latLng);
								}		// longitude
								else
								{	// match failed
								    alert("Location.js: onLoadLocation: "+
								      "Invalid Boundary Element " +
								      bound + " ignored");
								}	// match failed
						    }	// succeeded
						    else
							    alert("Location.js: onLoadLocation: " +
							            "Invalid Boundary Element " +
							            bound + " ignored");
						}		// loop through each element
						if (readonly)
						    boundary = new google.maps.Polygon(polyOptionsShow);
						else
						    boundary = new google.maps.Polyline(polyOptionsEdit);
						boundary.setPath(path);
				    }		// have a boundary to display
				    break;
				}		// Boundary
	
				case 'Pictures':
				{		// <button id='Pictures'>
				    element.onclick	= editPictures;
				    break;
				}		// <button id='Pictures'>
	
				case 'Close':
				{		// <button id='Close'>
				    element.onclick	= close;
				    break;
				}		// <button id='Close'>
	
				default:
				{
				    break;
				}
		    }	        // act on field name
		}	            // loop through all elements in the form
    }		            // loop through all forms
}		// onLoadLocation

/************************************************************************
 *  validateForm														*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  resetForm															*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  locationChanged														*
 *																		*
 *  Handle a change to the value of the Location field.					*
 *																		*
 *  Input:																*
 *		this is the Location input text element							*
 ************************************************************************/
function locationChanged()
{
    var	form			= this.form;

    // the following code is from CommonForm.js function change
    // trim off leading and trailing spaces
    this.value	= this.value.trim();

    // if the form has a button named Submit, enable it just in case
    // it was previously disabled
    if (form.Submit)
		form.Submit.disabled	= false;

    // capitalize words in value if presentation style requires it
    var textTransform	= "";
    if (this.currentStyle)		// try IE API
		textTransform	= this.currentStyle.textTransform;
    else
    if (window.getComputedStyle)	// W3C API
		textTransform	= window.getComputedStyle(this, null).textTransform;
    if (textTransform == "capitalize")
		capitalize(this);

    // expand abbreviations
    if (this.abbrTbl)
		expAbbr(this,
			this.abbrTbl);
    else
    if (this.value == '[')
		this.value	= '[Blank]';

    // default short name and sort key to match location name
    var	value			= this.value;

    // set short name to location name as default
    form.ShortName.value	= value;

    // update the search name hidden field
    var countyPattern		= /([a-zA-Z ]+), ON, CA\s*$/;
    var cmatches		= countyPattern.exec(value);
    if (cmatches != null)
    {
		var county	= cmatches[1];
		if (ontarioCountyNames[county])
		{
		    form.searchName.value	= substr(value, 0, value.length - 8) +
						  ' County, ON, CA';
		}
		    form.searchName.value	= value;
    }
    else
		form.searchName.value	= value;

    // the sorted location name depends upon the format of the location name
    //	For consistency of behavior this pattern should be the same
    //	as the one in Location.inc

    var namePattern	= /^(E ½ |S ½ |W ½ |N ½ |E½ |S½ |W½ |N½ |½ |E ¼ |S ¼ |W ¼ |N ¼ |E ½ E ½ |E ½ S ½ |E ½ W ½ |E ½ N ½ |S ½ E ½ |S ½ S ½ |S ½ W ½ |S ½ N ½ |W ½ E ½ |W ½ S ½ |W ½ W ½ |W ½ N ½ |N ½ E ½ |N ½ S ½ |N ½ W ½ |N ½ N ½ |NE ¼ |NW ¼ |SE ¼ |SW ¼ |NE ½ |NW ½ |SE ½ |SW ½ |N pt |NE pt |E pt |NW pt |S pt |SE pt |SW pt |W pt |pt |pt E ½ |pt S ½ |pt W ½ |pt N ½ |)((lot )?[0-9½&\-]+) ([a-zA-Z0-9., ]*)(,.*)$/i;
    var matches	= namePattern.exec(value)
    if (matches != null)
    {		// street address
		var part		= matches[1];
		var lotnum		= matches[2];
		var streetname		= matches[4];
		var rest		= matches[5];

		// pad out lot number
		if (lotnum.length < 3)
		    lotnum	= ("000" + lotnum).substring(lotnum.length);
		else
		if (lotnum.substring(0, 4) == 'lot ' && lotnum.length == 5)
		    lotnum	= 'lot 0' + lotnum.substring(4);

		// pad out concession number
		if (streetname.substring(0,4) == 'con ' &&
		    streetname.substring(5,6) == ',')
		    streetname	= 'con 0' + streetname.substring(4);
		var sortedLocation	= streetname + rest + ', ' +
					  lotnum + ' ' + part;
		form.SortedLocation.value	= sortedLocation;
    }		// street address
    else		
		form.SortedLocation.value	= value;
}		// locationChanged

/************************************************************************
 *  mergeDuplicates														*
 *																		*
 *  This method is called when the user requests to merge				*
 *  all duplicates of the current location								*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function mergeDuplicates()
{
    var	form		= this.form;
    var mainIdlr	= form.idlr.value;
    var	parms		= {};
    parms['to']		= mainIdlr;
    var	from		= '';

    for (var i = 0; i < form.elements.length; i++)
    {		// loop through duplicates
		var	elt	= form.elements[i];
		if (elt.name)
		{		// element has a name
		    if (elt.name.substr(0,7) == 'DupIdlr')
		    {		// element containing duplicate idlr
			from	+= elt.value + ',';
		    }		// element containing duplicate idlr
		}		// element has a name
    }		// loop through duplicates

    // trim off trailing comma, if any
    if (from.length > 0)
		from	= from.substr(0,from.length - 1);
    else
		return;		// nothing to do
    parms['from']	= from;

    // disable the submit button until this long running operation is complete
    var	button		= form.Submit;
    button.disabled	= true;

    // invoke script to merge locations and return XML result
    popupLoading(this);
    var parmsTxt	= '{';
    for(var key in parms)
    {
		parmsTxt	+= key + "='" + parms[key] + "',";
    }
    if (debug.toLowerCase() == 'y')
		alert("Location.js: mergeLocationsXml.php: parms=" + parmsTxt);
    HTTP.post("mergeLocationsXml.php",
		      parms,
		      gotMerge,
		      noMerge);
}	// mergeDuplicates

/************************************************************************
 *  gotMerge															*
 *																		*
 *  This method is called when the XML file representing				*
 *  a completed merge is retrieved from the database.					*
 ************************************************************************/
function gotMerge(xmlDoc)
{
    var	form		= document.locForm;

    // enable the submit button
    var	button		= form.Submit;
    button.disabled	= false;
    hideLoading();

    var	root	= xmlDoc.documentElement;
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
}		// gotMerge

/************************************************************************
 *  noMerge																*
 *																		*
 *  This method is called if there is no response						*
 *  file.																*
 ************************************************************************/
function noMerge()
{
    var button	= document.locForm.mergeDuplicates;
    if (button)
		popupAlert('Location.js: script mergeLocationsXml.php not found',
			   button);
    else
		alert('Location.js: script mergeLocationsXml.php not found');
}		// noMerge

/************************************************************************
 *  showMap																*
 *																		*
 *  This function is called if the user clicks on the show Map button.	*
 *  It displays a map using Google maps support.						*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function showMap()
{
    var	button		= this;
    var	form		= button.form;
    var latlng		= null;		// Google maps latitude/longitude
    var	mapDiv		= document.getElementById("mapDiv");

    // if latitude and longitude specified in database, display the
    // map based upon those values
    var lat		= parseFloat(form.Latitude.value);
    var lng		= parseFloat(form.Longitude.value);
    locn		= form.Location.value;
    var zoom		= parseInt(form.Zoom.value, 10);

    if (lat != 0 || lng != 0)
    {		// display map for coordinates
		try {
		    displayMap(new google.maps.LatLng(lat, lng), zoom);
		}
		catch(e)
		{
		    popupAlert("Location.js: showMap: " +
			"Unable to use google maps to display map of location: " +
			  "message='" + e.message + "', " +
			  "lat=" + lat + ", lng=" + lng + ", zoom=" + zoom,
				this);
		}
    }		// display map for coordinates
    else
    if (geocoder !== null)
    {		// use Geocoder
		var searchName	= form.searchName.value;
		geocoder.geocode({ 'address': searchName},
				 function(results, status)
		{
		    if (status == google.maps.GeocoderStatus.OK) {
			displayMap(results[0].geometry.location, zoom);
		    } else {	// geocode failed
			popupAlert("Location.js: showMap: " +
					"Geocode for '" + searchName +
				   "' was not successful for the following reason: " +
					status,
				   this);
		    }	// geocode failed
		});	// end of inline function and invocation of geocode
    }		// use Geocoder
    return false;
}		// showMap

/************************************************************************
 *  displayMap															*
 *																		*
 *  This function is called to display a Google maps map				*
 *  of the location.													*
 *																		*
 *  Input:																*
 *		latlng			instance of google.maps.LatLng for center of map*
 *		zoomlevel		the zoom level for displaying the map			*
 ************************************************************************/
function displayMap(latlng, zoomlevel)
{
    if (latlng !== null)
    {		// location resolved
		var	button		= document.getElementById('showMap');
		var	form		= document.locForm;
		var	readonly	= form.Zoom.readOnly;
		var	notes		= form.Notes;
		mapDiv			= document.getElementById("mapDiv");
		mapDiv.style.left	= getOffsetLeft(notes) + "px";
		mapDiv.style.top	= getOffsetTop(notes) + "px";
		show(mapDiv);				// make visible

		var hideMapDiv		= document.getElementById("hideMapDiv");
		hideMapDiv.style.left	= "80px";
		hideMapDiv.style.top	= "0px";
		hideMapDiv.style.width	= "120px";
		var hideMapBtn		= document.getElementById("hideMap");
		hideMapBtn.onclick	= hideMap;
		show(hideMapDiv);			// make visible

		var myOptions = {
			  zoom: zoomlevel,
			  center: latlng,
			  mapTypeId: google.maps.MapTypeId.ROADMAP,
			};
		if (!readonly)
		    myOptions.draggableCursor	= 'crosshair';

		try {		// try to create map
		    map	= new google.maps.Map(mapDiv,
					      myOptions);
		    try {
			var marker = new google.maps.Marker({map: map, 
							     position: latlng });
			if (!readonly)
			    google.maps.event.addListener(map, 'click', mapClick);
		    }		// try to create marker on map
		    catch(e) {	// failed to create marker
			popupAlert("Location.js: displayMap: " +
			"new google.maps.Marker failed: message='" + e.message + "'",
				   this);
		    }		// failed to create marker
		}		// try to create map	
		catch(e) {	// failed to create map
		    popupAlert("Location.js: displayMap: " +
			"new google.maps.Map failed: message='" + e.message + "'",
				this);
		}		// failed to create map

		if (boundary)
		{		// have a Polyline to draw
		    boundary.setMap(map);
		}		// have a Polyline to draw

		// change the show map button into a hide map button
		button.onclick		= hideMap;
		while(button.firstChild)
		    button.removeChild(button.firstChild)
		var template	= document.getElementById('hideMapTemplate');
		for(var childTemp = template.firstChild;
			childTemp;
			childTemp = childTemp.nextSibling)
		    button.appendChild(childTemp.cloneNode(true));
    }		// location resolved
    else
    {		// location not resolved
		popupAlert("Location.js: displayMap: location " + locn +
				" not resolved",
			   this);
    }		// location not resolved
}		// displayMap

/************************************************************************
 *  mapClick															*
 *																		*
 *  This function is called if the user clicks the cursor somewhere in	*
 *  the map other than on a marker.  The location under the cursor is	*
 *  added to the boundary.												*
 *																		*
 *  Input:																*
 *		mouseEvent		a Google maps mouse event including the			*
 *						latitude and longitude corresponding to the		*
 *						point the user clicked on						*
 ************************************************************************/
function mapClick(mouseEvent)
{
    //alert("mapClick: mouseEvent.latLng: " + mouseEvent.latLng.toString());
    if (path.length == 0)
    {		// first click
		boundary	= new google.maps.Polyline(polyOptionsEdit);
		boundary.setMap(map);
    }		// first click
    path.push(mouseEvent.latLng);
    boundary.setPath(path);
}

/************************************************************************
 *  hideMap																*
 *																		*
 *  This function is called if the user clicks on the show Map button.	*
 *  It displays a map using Google maps support.						*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function hideMap()
{
    var	hideMapDiv	= document.getElementById("hideMapDiv");
    hideMapDiv.style.display	= 'none';	// hide
    var	mapDiv		= document.getElementById("mapDiv");
    mapDiv.style.display	= 'none';	// hide

    var	form		= document.locForm;
    var	button		= form.showMap;
    while(button.firstChild)
		button.removeChild(button.firstChild)
    var template	= document.getElementById('showMapTemplate');
    for(var childTemp = template.firstChild;
		    childTemp;
		    childTemp = childTemp.nextSibling)
		button.appendChild(childTemp.cloneNode(true));
    button.onclick		= showMap;
    return false;
}		// hideMap

/************************************************************************
 *  getMap																*
 *																		*
 *  This function is called to copy information from the map into		*
 *  the location record.												*
 *																		*
 *  Input:sourc															*
 *		this		instance of <button>								*
 ************************************************************************/
function getMap()
{
    if (map === null)
    {			// user forgot to do show map first
		var	form	= this.form;
		form.showMap.onclick();
		return;
    }			// user forgot to do show map first

    if (map !== null && map.getMapTypeId() !== null)
    {
		var form	= this.form;
		var center	= map.getCenter();
		var zoom	= map.getZoom();
		var min;
		var sec;
		var deg;
		var neg;

		form.Latitude.value	= center.lat().toFixed(6);
		form.Longitude.value	= center.lng().toFixed(6);

		// set the map zoom factor
		form.Zoom.value		= zoom;

		// copy the boundary path
		if (boundary)
		{
		    var path		= boundary.getPath().getArray();
		    var pathStr		= '';
		    var comma		= '';
		    for(var pi = 0; pi < path.length; pi++)
		    {
	    		var point	= path[pi];
	    		pathStr		+= comma +
	    				  '(' + point.lat().toFixed(6) + ',' +
	    					point.lng().toFixed(6) + ')';
	    		comma		= ',';
		    }
		    form.Boundary.value	= pathStr;
		}
		else
		    form.Boundary.value	= '';
    }
    else
    {
		popupAlert("Location.js: getMap: map not initialized",
			   this);
    }
    return false;
}		// getMap

/************************************************************************
 *  displayReferences													*
 *																		*
 *  This function is called if the user clicks on the references button.*
 *  It displays a list of records that reference this location			*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function displayReferences()
{
    var	form	    	= document.locForm;
    var	idlr	    	= form.idlr.value;
    location	    	= 'getIndividualsByLocation.php?idlr=' + idlr;
    return false;
}		// displayReferences

/************************************************************************
 *  editPictures														*
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
    var	form		    = this.form;
    var	picIdType	    = form.PicIdType.value;
    var	idlr;

    if (form.idlr && form.idlr.value > 0)
    {		// idlr present in form
		idlr		    = form.idlr.value;
		var iframe	    = window.frameElement;
		var childFrameClass		= 'right';
		if (iframe)
		{
		    if (iframe.className == 'right')
			    childFrameClass		= 'left';
		}
		openFrame("pictures",
			  "editPictures.php?idlr=" + idlr +
					    "&idtype=" + picIdType, 
			  childFrameClass);
    }		// idlr present in form
    else
    {		// unable to identify record to associate with
		popupAlert("Location.js: editPictures: " +
			   "Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	// editPictures

/************************************************************************
 *  close																*
 *																		*
 *  This is the onclick method of the "Close" button. 		 			*
 *																		*
 *  Parameters:															*
 *		this		a <button> element									*
 ************************************************************************/
function close()
{
    closeFrame();
}
