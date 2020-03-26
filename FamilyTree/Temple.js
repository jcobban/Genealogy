/************************************************************************
 *  Temple.js															*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Temple.php.													*
 *																		*
 *  History:															*
 *		2012/12/06		created											*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2015/06/16		open list of pictures in other half				*
 *						of the window									*
 *		2015/12/08		remove alert									*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2020/02/16      add show Map button                             *
 *		2020/02/17      hide right column                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Specify the function to get control once the page is loaded.		*
 ************************************************************************/
window.addEventListener('load',	onLoadTemple);

/************************************************************************
 *  childFrameClass											            *
 *																		*
 *  If this dialog is opened in a half window then any child dialogs	*
 *  are opened in the other half of the window.							*
 ************************************************************************/
var childFrameClass	        = 'right';

// instance of google.maps.Geocoder for resolving place names
var	geocoder	            = null;

/************************************************************************
 *  function onLoadTemple												*
 *																		*
 *  This function is invoked once the page is completely loaded into	*
 *  the browser.  Initialize dynamic behavior of elements.				*
 ************************************************************************/
function onLoadTemple()
{
    // support for displaying map and geocoding
    try {
		geocoder	        = new google.maps.Geocoder();
    }
    catch(e)
    {		// ignore, may not be used
    }		// ignore, may not be used

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

    // set action methods for form
    form.addEventListener('submit',	validateForm);
    form.addEventListener('reset',	resetForm);

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	        = form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var element		    = formElts[i];
		element.addEventListener('keydown',	keyDown);

		// take action specific to element
		var	name	        = element.name;
		if (name.length == 0)
		    name	        = element.id;
		switch(name.toLowerCase())
		{		// switch on field name
		    case 'code':
		    {
				element.addEventListener('change',	change);
				element.checkfunc	= checkTCode;
				break;
		    }

		    case 'temple':
		    {
				element.addEventListener('change',	changeTemple);
				break;
		    }

		    case 'templestart':
		    case 'templeend':
		    {
				element.addEventListener('change',	change);
				element.checkfunc	= checkYyyymmdd;
				break;
		    }

		    case 'references':
		    {			    // <button id='References'>
				element.addEventListener('click',	displayReferences);
				break;
		    }			    // <button id='References'>
	
			case 'showmap':
			{		        // <button id='showMap'>
			    element.addEventListener('click',	showMap);
			    break;
            }		        // <button id='showMap'>

		    case 'pictures':
		    {		        // <button id='Pictures'>
				element.addEventListener('click',	editPictures);
				break;
		    }		        // <button id='Pictures'>
		    
		    default:
		    {	            // default handler
				element.addEventListener('change',	change);
				break;
		    }	            // default handler
		}		            // switch on field name
    }		                // loop through all elements in the form

    hideRightColumn();
}		// function onLoadTemple

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  function changeTemple												*
 *																		*
 *  Handle a change to the value of the Temple field.					*
 *																		*
 *  Input:																*
 *		this        the Temple input text element						*
 *		ev          javascript change event                             *
 ************************************************************************/
function changeTemple(ev)
{
    var	form		= this.form;

    // the following code is from CommonForm.js function change
    // trim off leading and trailing spaces
    this.value		= this.value.trim();

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

}		// function changeTemple

/************************************************************************
 *  function checkTCode													*
 *																		*
 *  Validate the current value of a field containing a temple			*
 *  abbreviation.														*
 *																		*
 *  Input:																*
 *	    this		<input name='code'>				                    * 
 ************************************************************************/
function checkTCode()
{
    var	element		= this;
    var	re		= /^[A-Z]{5}$/;
    var	date		= element.value;
    var	className	= element.className;
    var	result	= re.exec(date);
    var	matched	= typeof result === 'object' && result instanceof Array;

    if (className.substring(className.length - 5) == 'error')
    {		// error currently flagged
		// if valid value, clear the error flag
		if (matched)
		{
		    element.className	= className.substring(0, className.length - 5);
		}
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add 'error' to class name
		if (!matched)
		    element.className	= element.className + "error";
    }		// error not currently flagged
}		// checkTCode

/************************************************************************
 *  function checkYyyymmdd												*
 *																		*
 *  Validate the current value of a field containing a date.			*
 *																		*
 *  Input:																*
 *		this		<input name=templestart'> or						*
 *			        <input name='templeend'>                            *
 ************************************************************************/
function checkYyyymmdd()
{
    var	element		= this;
    var	re		= /^[0-9]{8}$/;
    var	date		= element.value;
    var	className	= element.className;
    var	result	= re.exec(date);
    var	matched	= typeof result === 'object' && result instanceof Array;

    if (className.substring(className.length - 5) == 'error')
    {		// error currently flagged
		// if valid value, clear the error flag
		if (matched)
		{
		    element.className	= className.substring(0, className.length - 5);
		}
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add 'error' to class name
		if (!matched)
		    element.className	= element.className + "error";
    }		// error not currently flagged
}		// function checkYyyymmdd

/************************************************************************
 *  function displayReferences											*
 *																		*
 *  This function is called if the user clicks on the references button.*
 *  It displays a list of records that reference this temple			*
 *																		*
 *  Input:																*
 *		this		<button id='References'>						    *
 *		ev          javascript click event                              *
 ************************************************************************/
function displayReferences(ev)
{
    var	form		= document.locForm;
    var	idlr		= form.idlr.value;
    temple		    = 'getIndividualsByTemple.php?idlr=' + idlr;
    return false;
}		// function displayReferences

/************************************************************************
 *  function editPictures												*
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  			*
 *  It is called when the user requests to edit							*
 *  information about the Pictures associated with the source			*
 *  that are recorded by instances of Picture.							*
 *																		*
 *  Parameters:															*
 *		this		<button id='Pictures'> element					    *
 *		ev          javascript click event                              *
 ************************************************************************/
function editPictures()
{
    var	form		= this.form;
    var	picIdType	= form.PicIdType.value;
    var	idtr;

    if (form.idtr && form.idtr.value > 0)
    {		// idtr present in form
		idtr		= form.idtr.value;
		openFrame("pictures",
				  "editPictures.php?idtr=" + idtr +
						    "&idtype=" + picIdType, 
				  childFrameClass);
    }		// idtr present in form
    else
    {		// unable to identify record to associate with
		popupAlert("Temple.js: editPictures: " +
				   "Unable to identify record to associate pictures with",
				   this);
    }		// unable to identify record to associate with
    return true;
}	// function editPictures

/************************************************************************
 *  function hideMap													*
 *																		*
 *  This function is called if the user clicks on the show Map button.	*
 *  It displays a map using Google maps support.						*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function hideMap()
{
    var	hideMapDiv	            = document.getElementById("hideMapDiv");
    hideMapDiv.style.display	= 'none';	// hide
    var	mapDiv		            = document.getElementById("mapDiv");
    mapDiv.style.display	    = 'none';	// hide

    var	form	            	= document.locForm;
    var	button	            	= form.showMap;
    while(button.firstChild)
		button.removeChild(button.firstChild)
    var template            	= document.getElementById('showMapTemplate');
    for(var childTemp           = template.firstChild;
		    childTemp;
		    childTemp           = childTemp.nextSibling)
		button.appendChild(childTemp.cloneNode(true));
    button.onclick	        	= showMap;
    return false;
}		// function hideMap

/************************************************************************
 *  function showMap													*
 *																		*
 *  This function is called if the user clicks on the show Map button.	*
 *  It displays a map using Google maps support.						*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 *		ev          javascript click event                              *
 ************************************************************************/
function showMap(ev)
{
    let	button		        = this;
    let	form		        = button.form;
    let latlng		        = null;		// Google maps latitude/longitude

    // if latitude and longitude specified in database, display the
    // map based upon those values
    let locn		        = form.Address.value;
    let zoom                = 14;
    if (locn.length == 0)
    {
        locn                = form.Temple.value;
        zoom                = 10;
    }

    if (geocoder !== null)
    {		// use Geocoder
		let searchName	    = locn;
		geocoder.geocode({ 'address': searchName},
				 function(results, gcstatus)
		{
		    if (gcstatus == google.maps.GeocoderStatus.OK) {
			    displayMap(results[0].geometry.location, zoom);
		    } else {	// geocode failed
			    popupAlert("Temple.js: showMap: " +
        					"Geocode for '" + searchName +
    	    			    "' was not successful for the following reason: " +
    				       gcstatus,
    				       this);
		    }	        // geocode failed
		});	            // end of inline function and invocation of geocode
    }		// use Geocoder
    return false;
}		// function showMap

/************************************************************************
 *  function displayMap													*
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
    {		            // location resolved
		let	button	        	= document.getElementById('showMap');
		let	form	        	= document.locForm;
		let	readonly        	= form.Temple.readOnly;
		mapDiv		        	= document.getElementById("mapDiv");
		show(mapDiv);				// make visible

		let hideMapDiv	    	= document.getElementById("hideMapDiv");
		hideMapDiv.style.left	= "80px";
		hideMapDiv.style.top	= "0px";
		hideMapDiv.style.width	= "120px";
		let hideMapBtn	    	= document.getElementById("hideMap");
		hideMapBtn.onclick  	= hideMap;
		show(hideMapDiv);			// make visible

		let myOptions = {
			  zoom: zoomlevel,
			  center: latlng,
			  mapTypeId: google.maps.MapTypeId.ROADMAP,
			};

		try {		// try to create map
		    map	        = new google.maps.Map(mapDiv,
					                          myOptions);
		    try {
			let marker  = new google.maps.Marker({map:      map, 
							                      position: latlng });
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

		// change the show map button into a hide map button
		button.onclick		= hideMap;
		while(button.firstChild)
		    button.removeChild(button.firstChild);
		let template	= document.getElementById('hideMapTemplate');
		for(let childTemp = template.firstChild;
			childTemp;
			childTemp = childTemp.nextSibling)
		    button.appendChild(childTemp.cloneNode(true));
    }		        // location resolved
    else
    {		        // location not resolved
		popupAlert("Location.js: displayMap: location " + locn +
		    		" not resolved",
			       this);
    }		        // location not resolved
}		// function displayMap
