/************************************************************************
 *  showAddedIndividuals.js												*
 *																		*
 *  Javascript code to implement dynamic functionality of				*
 *  showAddedIndividuals.php.											*
 *																		*
 *  History:															*
 *		2014/03/11		created											*
 *		2014/10/12		use method show to display dialog				*
 *		2019/02/11      scroll just the main section, leaving header    *
 *		                and footer visible always                       *
 *		                calling pageInit no longer required             *
 *		2022/07/07      add paging of results                           *
 *																		*
 *  Copyright &copy; 2022 James A. Cobban								*
 ************************************************************************/

// identify function to invoke when page loaded
window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  This function is called once the page has been completely loaded.	*
 *																		*
 *  Parameters:															*
 *		this		window object										*
 ************************************************************************/
function onLoad()
{
    // add mouseover actions for forward and backward links
    let element	= document.getElementById('toPrevWeek');
    if (element)
    {		// defined
		element.onmouseover	= linkMouseOver;
		element.onmouseout	= linkMouseOut;
    }		// defined
    element	= document.getElementById('toNextWeek');
    if (element)
    {		// defined
		element.onmouseover	= linkMouseOver;
		element.onmouseout	= linkMouseOut;
    }		// defined
    element	= document.getElementById('prevPage');
    if (element)
    {		// defined
		element.onmouseover	= linkMouseOver;
		element.onmouseout	= linkMouseOut;
    }		// defined
    element	= document.getElementById('nextPage');
    if (element)
    {		// defined
		element.onmouseover	= linkMouseOver;
		element.onmouseout	= linkMouseOut;
    }		// defined

    // scroll main portion of page if it does not fit without scrolling
    let headSection         = document.getElementById('headSection');
    let headHeight          = headSection.offsetHeight;
    let mainSection         = document.getElementById('mainSection');
    let mainHeight          = mainSection.offsetHeight;
    let footSection         = document.getElementById('footSection');
    let footHeight          = footSection.offsetHeight;
    let windHeight          = window.innerHeight;
    if (mainHeight + headHeight + footHeight > windHeight)
    {
        mainSection.style.height    = (windHeight - headHeight - footHeight - 12) + 'px';
        mainSection.style.overflowY = 'auto';
    }
}		// onLoad

/************************************************************************
 *  function linkMouseOver												*
 *																		*
 *  This function is called if the mouse moves over a forward or		*
 *  backward hyperlink on the invoking page.							*
 *																		*
 *  Parameters:															*
 *		this		element the mouse moved on to						*
 ************************************************************************/
function linkMouseOver()
{
    let	msgDiv	= document.getElementById('mouse' + this.id);
    if (msgDiv)
    {		// support for dynamic display of messages
		// display the messages balloon in an appropriate place on the page
		var leftOffset		= getOffsetLeft(this);
		if (leftOffset > 500)
		    leftOffset	-= 350;
		msgDiv.style.left	= leftOffset + "px";
		msgDiv.style.top	= (getOffsetTop(this) - 30) + 'px';
//alert("msgDiv.style.left=" + msgDiv.style.left + " msgDiv.style.top=" + msgDiv.style.top);
		// so key strokes will close window
		helpDiv			    = msgDiv;
		helpDiv.onkeydown	= keyDown;
		show(msgDiv);

    }		// support for dynamic display of messages
    else
		alert("showAddedIndividuals.js: linkMouseOver could not find <div id='mouse" + this.id + "'>");
}		// linkMouseOver

/************************************************************************
 *  function linkMouseOut												*
 *																		*
 *  This function is called if the mouse moves off a forward or			*
 *  backward hyperlink on the invoking page.							*
 *																		*
 *  Parameters:															*
 *		this		element the mouse moved off of						*
 ************************************************************************/
function linkMouseOut()
{
    if (helpDiv)
    {
		helpDiv.style.display	= 'none';
		helpDiv			= null;
    }
}		// function linkMouseOut

