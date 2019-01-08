/************************************************************************
 *  CensusUpdateStatus.js						*
 *									*
 *  Javascript code to implement dynamic functionality of		*
 *  CensusUpdateStatus.php.						*
 *									*
 *  History:								*
 *	2011/12/10	created						*
 *	2012/09/17	pass census identifier to Update script		*
 *	2013/05/23	add button to display surnames			*
 *	2013/07/30	defer facebook initialization until after load	*
 *	2013/08/25	use pageInit common function			*
 *	2018/01/18	id portion of button ids now districtid		*
 *			pass language to other scripts			*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  This function is called when the page is completely loaded into	*
 *  the browser.							*
 *									*
 *  Parameters:								*
 *	this		Window object					*
 ************************************************************************/
function onLoad()
{
    // perform common page initialization
    pageInit();

    // add mouseover actions for forward and backward links
    for (var il = 0; il < document.links.length; il++)
    {			// loop through all hyper-links
	var	linkTag		= document.links[il];
	linkTag.onmouseover	= linkMouseOver;
	linkTag.onmouseout	= linkMouseOut;
    }			// loop through all hyper-links

    for(var i = 0; i < document.forms.length; i++)
    {
	var form	= document.forms[i];
	for(var j = 0; j < form.elements.length; j++)
	{
	    var element	= form.elements[j];

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    if (element.parentNode.nodeName == 'TD')
	    {	// set mouseover on containing cell
		element.parentNode.onmouseover	= eltMouseOver;
		element.parentNode.onmouseout	= eltMouseOut;
	    }	// set mouseover on containing cell
	    else
	    {	// set mouseover on input element itself
		element.onmouseover		= eltMouseOver;
		element.onmouseout		= eltMouseOut;
	    }	// set mouseover on input element itself

	    if (element.id.substring(0,7) == 'Details')
		element.onclick	= editDist;
	    else
	    if (element.id.substring(0,8) == 'Surnames')
		element.onclick	= showSurnames;
	}	// loop through all elements in form
    }		// loop through all forms
}		// onLoad

/************************************************************************
 *  editDist								*
 *									*
 *  This function is called if the user clicks on the "Details" button	*
 *  for a row.								*
 *									*
 *  Parameters:								*
 *	this		<button id='Details...'>			*
 ************************************************************************/
function editDist()
{
    var	district	= this.id.substring(7);
    var censusId	= document.getElementById('Census').value;
    var url		= 'CensusUpdateStatusDist.php?Census=' + censusId +
			  '&District=' + district;
    if ('lang' in args)
    {
	var lang	= args['lang'];
	if (lang.length == 2)
	    url		+= "&lang=" + lang;
    }
    location		= url;
    return false;
}		// editDist

/************************************************************************
 *  showSurnames							*
 *									*
 *  This function is called if the user clicks on the "Surnames" button	*
 *  for a row.								*
 *									*
 *  Parameters:								*
 *	this		<button	id='Surnames...'>			*
 ************************************************************************/
function showSurnames()
{
    var	district	= this.id.substring(8);
    var censusId	= document.getElementById('Census').value;
    var url		= 'QuerySurnamesTop.php?Census=' + censusId +
			  '&District=' + district;
    if ('lang' in args)
    {
	var lang	= args['lang'];
	if (lang.length == 2)
	    url		+= "&lang=" + lang;
    }
    location		= url;
    return false;
}		// showSurnames
