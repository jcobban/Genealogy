/************************************************************************
 *  DisplayImage.js							*
 *									*
 *  Implement dynamic functionality of page DisplayImage.php.		*
 *									*
 *  History:								*
 *	2015/04/19	created						*
 *	2015/05/01	update value of Image field in the calling	*
 *			page when user changes image selection through	*
 *			forward and backward links			*
 *	2015/05/09	permit invoker to pass name of field to update	*
 *			with new image file name			*
 *	2018/02/10	add close frame button				*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/

window.onload	= onLoadImage;

/************************************************************************
 *  onLoadImage								*
 *									*
 *  Initialize dynamic functionality of page.				*
 ************************************************************************/
function onLoadImage()
{
    pageInit();

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
	var form	= document.forms[i];

	for(var j = 0; j < form.elements.length; j++)
	{	// loop through all elements of a form
	    element		= form.elements[j];

	    element.onkeydown	= keyDown;

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    // an element whose value is passed with the update
	    // request to the server is identified by a name= attribute
	    // but elements which are used only by this script are
	    // identified by an id= attribute
	    var	name	= element.name;
	    if (name.length == 0)
		name	= element.id;

	    // set up dynamic functionality based on the name of the element
	    switch(name.toLowerCase())
	    {
		case 'plus':
		{	// button to increase magnification of image
		    element.onclick	= zoomIn;
		    break;
		}	// button to increase magnification of image

		case 'minus':
		{	// button to decrease magnification of image
		    element.onclick	= zoomOut;
		    break;
		}	// button to decrease magnification of image

		case "close":
		{
		    element.onclick	= close;
		    break;
		}	// incLocsSet

	    }	// switch on field name
	}		// loop through all elements in the form
    }		// loop through forms in the page

    // activate popup help for forward and backward links
    element	= document.getElementById('goToPrevImg');
    actMouseOverHelp(element);
    element	= document.getElementById('goToNextImg');
    actMouseOverHelp(element);

    // update the name of the image in the invoking page
    var	opener		= null;
    if (window.frameElement && window.frameElement.opener)
	opener		= window.frameElement.opener;
    else
	opener		= window.opener;
    if (opener)
    {		// opened from another window
	var fldName		= 'Image';
	if ('fldname' in args)
	    fldName		= args.fldname;
	var imageElement	= opener.document.getElementById(fldName);
	if ('src' in args)
	{
	    if (imageElement)
	    {
		if (args.src.substring(0,7) == 'Images/')
		    imageElement.value	= args.src.substring(7);
		else
		    imageElement.value	= args.src;
	    }
	    else
		alert("DisplayImage.js: " +
		    "opener does not have a field with name '" + fldName + "'");
	}
	else
	    alert("DisplayImage.js: missing src= argument");
    }		// opened from another window
    else
	alert("DisplayImage.js: no opener");
}		// onLoadImage

/************************************************************************
 *  zoomIn								*
 *									*
 *  Increase the size of the image element which has the effect of	*
 *  zooming in to the image.						*
 *									*
 *  Input:								*
 *	$this		<button id='plus'>				*
 ************************************************************************/
function zoomIn()
{
    var	image		= document.getElementById('image');
    image.style.height	= 'auto';
    image.style.width	= Math.floor(image.width * 3 / 2) + 'px';
    return false;
}		// zoomIn

/************************************************************************
 *  zoomOut								*
 *									*
 *  Decrease the size of the image element which has the effect of	*
 *  zooming out from the image.						*
 *									*
 *  Input:								*
 *	$this		<button id='minus'>				*
 ************************************************************************/
function zoomOut()
{
    var	image		= document.getElementById('image');
    image.style.height	= 'auto';
    image.style.width	= Math.floor(image.width * 2 / 3) + 'px';
    return false;
}		// zoomIn

/************************************************************************
 *  close								*
 *									*
 *  This method is called when the user clicks on the button to close	*
 *  the dialog.								*
 *									*
 *  Parameters:								*
 *	this	<button id='Close'>					*
 ************************************************************************/
function close()
{
    closeFrame();
}		// close

