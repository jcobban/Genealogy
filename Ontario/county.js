/************************************************************************
 *  county.js																*
 *																		*
 *  Implement the dynamic functionality of the county specific pages		*
 *  GenOntXxx.html														*
 *																		*
 *  History:																*
 *		2011/08/13		created												*
 *		2013/12/28		add facebook link								*
 *		2018/01/24		remove getRightTop								*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization after page is loaded							*
 *																		*
 *  Input:																*
 *		this		window object										*
 ************************************************************************/
function onLoad()
{
    var	mapImage	= document.getElementById('map');
    if (mapImage)
    {
		if (mapImage.width > window.innerWidth)
		    mapImage.width	= window.innerWidth - 30;
    }
}		// onLoad
