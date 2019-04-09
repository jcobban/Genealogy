/************************************************************************
 *  CensusResponse.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of				*
 *  CensusResponse.php.													*
 *																		*
 *  History (of QueryResponse.js, which is superceded):					*
 *		2011/08/26		created											*
 *		2011/09/03		merge subdistricts with the same township		*
 *						name for the 1911 and 1916 censuses in the		*
 *						sub-district selection list						*
 *		2012/04/01		add display image button if full page requested	*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2013/08/25		use pageInit common function					*
 *		2014/08/10		update the same cookie used by QueryDetail.js	*
 *						to track the last district referenced for		*
 *						each census										*
 *		2014/10/14		indices of args array are now lower case		*
 *																		*
 *  History (of CensusResponse.js):										*
 *		2015/01/13		merge functionality of all query response		*
 *						scripts											*
 *						add functionality to display image in split		*
 *		2015/04/20		use DisplayImage.php to show image				*
 *		2015/05/15		censusId not extracted from args				*
 *		2015/07/08		simplify activation of popups for hyper-links	*
 *						use CommonForm.js								*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  onLoadSub															*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 *																		*
 *  Input:																*
 *		this	window object											*
 ************************************************************************/
function onLoad()
{
    var form	= document.buttonForm;

    if (form)
    for(var i = 0; i < form.elements.length; i++)
    {
		// perform element specific processing
		var	element	= form.elements[i];

		// perform element specific processing
		var	name	= element.name;
		if (name === undefined || name.length == 0)
		    name	= element.id;
		switch(name)
		{			// act on specific fields
		    case 'seeAllFields':
		    {
				element.href	= form.search.value;
				element.onclick	= goToLink;
				break;
		    }

		    case 'displayImage':
		    {
				element.onclick	= showImage;
				break;
		    }

		    default:
		    {
				if (name.substring(0, 7) == 'Details')
				{
				    var	srcName		= 'href' + element.name.substring(7);
				    element.href	= form.elements[srcName].value;
				    element.onclick	= goToLink;
				}
		    }
		}			// act on specific fields
    }		// loop through all form elements

    // add mouseover actions for forward and backward links
    for (var il = 0; il < document.links.length; il++)
    {			// loop through all hyper-links
		var	linkTag		= document.links[il];
		linkTag.onmouseover	= linkMouseOver;
		linkTag.onmouseout	= linkMouseOut;
    }			// loop through all hyper-links

    // if implied by the environment, set the initial selection
    var	district	= args["district"];
    var	censusId	= args["census"];
    var	cookie		= new Cookie("familyTree");
    if (district)
    {		// initial district passed as parameter
		// set the initially selected district number in a cookie
		cookie[censusId]	= district;
		cookie.store(10);		// keep for 10 days
    }		// initial district passed as parameter
    else
    {		// initial district not explicitly set
		// get the initially selected district number from a cookie
		try {
		district		= cookie[censusId];
		} catch(e) {}
		if (district === undefined || district === null)
		    district	= "";
    }		// initial district not explicitly set
}		// onLoad

/************************************************************************
 *  showImage															*
 *																		*
 *  Display the image of the original census page.						*
 *  This is the onclick method for the button with id 'displayImage'.	*
 *																		*
 *  Input:																*
 *		this			<button id='displayImage'>						*
 ************************************************************************/
function showImage()
{
    var	form			= this.form;
    var image			= form.image;
    var imageUrl		= "../Canada/DisplayImage.php?src=" +
							  image.value;

    // replace button with copyright notice
    var copNotice		= document.getElementById('imageCopyrightNote');
    if (copNotice)
    {			// replace button with copyright notice
		var clone		= copNotice.cloneNode(true);
		var parentNode		= this.parentNode;
		var nextSibling		= this.nextSibling;
		parentNode.removeChild(this);
		parentNode.insertBefore(clone, nextSibling);
		// also remove correct image button
		var corrButton		= document.getElementById('correctImage');
		if (corrButton)
		{
		    parentNode		= corrButton.parentNode;
		    parentNode.removeChild(corrButton);
		}
    }			// replace button with copyright notice
    else
    {			// just disable button
		this.disabled		= true;
    }			// just disable button

    var	iframe			= document.getElementById("imageFrame");
    if (!iframe)
    {
		iframe			= document.createElement("IFRAME");
		iframe.name		= "imageFrame";
		iframe.id		= "imageFrame";
		document.body.appendChild(iframe);
    }
    iframe.src			= imageUrl;
    var	w			= document.documentElement.clientWidth;
    var	h			= document.documentElement.clientHeight;
    // resize the display of the transcription
    var transcription		= document.getElementById('transcription');
    transcription.style.width	= w/2 + "px";
    transcription.style.height	= h + "px";

    // size and position the image
    iframe.style.width		= w/2 + "px";
    iframe.style.height		= h + "px";
    iframe.style.position	= "fixed";
    iframe.style.left		= w/2 + "px";
    iframe.style.top		= 0 + "px";
    iframe.style.visibility	= "visible";
    return false;
}	// showImage
