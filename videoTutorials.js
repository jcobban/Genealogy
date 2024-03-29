/************************************************************************
 *  videoTutorials.js                                                   *
 *                                                                      *
 *  Implement the dynamic functionality of the video tutorials page     *
 *                                                                      *
 *  History:                                                            *
 *      2015/07/29      created                                         *
 *      2018/01/31      embed Show/Hide in web-page for I18N            *
 *      2019/02/10      no longer need to call pageInit                 *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/

window.addEventListener("load", onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization after page is loaded.  This page is          *
 *  frequently invoked by the signon script.  If this is the case then  *
 *  the page that invoked the signon script should be refreshed to      *
 *  reflect the change in user status.                                  *
 *                                                                      *
 ************************************************************************/
function onLoad()
{
    var listOfVideos    = document.getElementById('listOfVideos');
    var videos          = listOfVideos.getElementsByTagName("LI");
    for (var vi = 0; vi < videos.length; vi++)
    {           // loop through LI tags under UL
        videos[vi].onclick  = selectVideo;
    }           // loop through LI tags under UL
}       // function onLoad

/************************************************************************
 *  function selectVideo                                                *
 *                                                                      *
 *  This is the function called when the user uses the mouse to         *
 *  click on one of the videos displayed by this page.                  *
 *                                                                      *
 *  Input:                                                              *
 *      this        <li>                                                *
 ************************************************************************/
function selectVideo()
{
    var videoFrame      = document.getElementById('display' + this.id);
    var show            = document.getElementById('Show' + this.id);
    var hide            = document.getElementById('Hide' + this.id);
    if (videoFrame)
    {
        if (videoFrame.className == 'hidden')
        {
            videoFrame.className    = 'center';
            show.style.display      = 'none';
            hide.style.display      = 'inline';
        }
        else
        {
            videoFrame.className    = 'hidden';
            show.style.display      = 'inline';
            hide.style.display      = 'none';
        }
    }
    else
        alert("Can't find element id='display" + this.id + "'");
}       // function selectVideo  
