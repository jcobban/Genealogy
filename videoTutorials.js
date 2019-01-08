/************************************************************************
 *  videoTutorials.js							*
 *									*
 *  Implement the dynamic functionality of the video tutorials page	*
 *									*
 *  History:								*
 *	2015/07/29	created						*
 *	2018/01/31	embed Show/Hide in web-page for I18N		*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Perform initialization after page is loaded.  This page is		*
 *  frequently invoked by the signon script.  If this is the case then	*
 *  the page that invoked the signon script should be refreshed to	*
 *  reflect the change in user status.					*
 *									*
 ************************************************************************/
function onLoad()
{
    pageInit();
    var listOfVideos	= document.getElementById('listOfVideos');
    var videos		= listOfVideos.getElementsByTagName("LI");
    for (var vi = 0; vi < videos.length; vi++)
    {			// loop through LI tags under UL
	videos[vi].onclick	= selectVideo;
    }			// loop through LI tags under UL
}		// onLoad

function selectVideo()
{
    var videoFrame		= document.getElementById('display' + this.id);
    var show			= document.getElementById('Show' + this.id);
    var hide			= document.getElementById('Hide' + this.id);
    if (videoFrame)
    {
	if (videoFrame.className == 'hidden')
	{
	    videoFrame.className	= 'center';
	    show.style.display		= 'none';
	    hide.style.display		= 'inline';
	}
	else
	{
	    videoFrame.className	= 'hidden';
	    show.style.display		= 'inline';
	    hide.style.display		= 'none';
	}
    }
    else
	alert("Can't find element id='display" + this.id + "'");
}
