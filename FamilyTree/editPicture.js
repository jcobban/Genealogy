/************************************************************************
 *  editPicture.js														*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page editPicture.php, which implements the ability to edit			*
 *  details of a picture that is recorded in a Picture record			*
 *  representing one record in the table tblBR.							*
 *																		*
 *  History:															*
 *		2011/05/28		created											*
 *		2012/01/13		change class names								*
 *		2013/02/15		add mouseover help								*
 *		2013/05/29		use actMouseOverHelp common function			*
 *		2014/03/06		failed because element sometimes refered to by	*
 *						variable "elt" and sometime by "element"		*
 *		2016/02/06		call pageInit on load							*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
window.onload	= loadEdit;
var	image;
var	sound;

/************************************************************************
 *  function loadEdit													*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function loadEdit()
{
    document.body.onkeydown	= epKeyDown;

    var	form		= document.picForm;
    var	type		= parseInt(form.idtype.value);

    // set action methods for elements
    form.onsubmit	= validateForm;
    form.onreset 	= resetForm;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts	= form.elements;

    for (var i = 0; i < formElts.length; ++i)
    {
		var element	= formElts[i];

		var	name;
		if (element.name && element.name.length > 0)
		    name	= element.name;
		else
		    name	= element.id;

		switch(name)
		{	// action depends upon element name
		    case 'pictype':
		    {
				element.onkeydown	= keyDown;
				element.onchange	= changePicType;
				break;
		    }	// etype

		    case 'picnameurl':
		    {
				element.onkeydown	= keyDown;
				element.onchange	= change;	// default handler
				element.checkfunc	= checkURL;
				element.focus();
				break;
		    }	// picnameurl
		    
		    case 'updPicture':
		    {
				element.onkeydown	= keyDown;
				element.onchange	= change;	// default handler
				break;
		    }	// updPicture
		    
		    case 'browseFile':
		    {
				element.onkeydown	= keyDown;
				element.onclick		= browseFile;
				element.onchange	= change;	// default handler
				element.checkfunc	= checkURL;
				break;
		    }	// browseFile

		    case 'picdate':
		    {		// media file date
				element.onkeydown	= keyDown;
				element.onchange	= change;	// default handler
				element.checkfunc	= checkDate;
				break;
		    }		// media file date
		    
		    case 'browseSound':
		    {
				element.onkeydown	= keyDown;
				element.onclick		= browseSound;
				element.onchange	= change;	// default handler
				break;
		    }	// browseSound
		    
		    case 'picdesc':
		    {
				element.onkeydown	= taKeyDown;
				element.onchange	= change;	// default handler
				break;
		    }	// desc

		    default:
		    {
				element.onkeydown	= keyDown;
				element.onchange	= change;	// default handler
				break;
		    }	// default

		}	// action depends upon element name
    }		// loop through all elements in the form

}		// loadEdit

/************************************************************************
 *  function changePicType												*
 *																		*
 *  Take action when the user selects an item in the name='etype' list	*
 *  of picture types.													*
 *																		*
 *  Input:																*
 *		this	<select id='picType'> element							*
 ************************************************************************/
function changePicType()
{
    // to do
}		// changePicType

/************************************************************************
 *  function getRelUrl													*
 *																		*
 *  Compare the "location" of another window to the "location" of the	*
 *  current window and return an appropriate "href" string.				*
 ************************************************************************/
function getRelUrl(child)
{
    if (window.location.host == child.location.host)
    {		// same host
		var	childpath	= child.location.pathname.split('/');
		var	mypath		= window.location.pathname.split('/');
		var	brklevel	= mypath.length - 1;
		for(var i = 0; i < childpath.length && i < mypath.length; i++)
		{
		    if (childpath[i] != mypath[i])
		    {
				brklevel	= i;
				break;
		    }
		}		// search for divergent path
		var	newpath	= "";
		for(i = mypath.length - 1; i > brklevel; i--)
		    newpath	+= "../";
		for(i = brklevel; i < childpath.length; i++)
		    newpath += childpath[i] + "/";
		newpath	= newpath.substring(0, newpath.length - 1);
		return newpath;
    }		// same host
    else
		return child.location.href;
}		// getRelUrl

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 *																		*
 *  Input:																*
 *		this				instance of <form>							*
 ************************************************************************/
function validateForm()
{
    var	form	= this;
    if (image)
    {
		form.picnameurl.value	= getRelUrl(image);
		image.close();
    }

    if (sound)
    {
		form.picsoundnameurl.value	= getRelUrl(sound);
		sound.close();
    }
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *																		*
 *  Input:																*
 *		this			instance of <form>								*
 ************************************************************************/
function resetForm()
{
    return true;
}	// resetForm

/************************************************************************
 *  function browseFile													*
 *																		*
 *  This method is called when the user requests to browse for a new	*
 *  picture to associate with a database record.						*
 *																		*
 *  Input:																*
 *		this		instance of <button id='browseFile'>				*
 ************************************************************************/
function browseFile()
{
    var	form	= document.picForm;
    image	= window.open("Images", "image");
}		// browseFile

/************************************************************************
 *  function browseSound													*
 *																		*
 *  This method is called when the user requests to browse for 			*
 *  a sound file to associate with the picture.							*
 *																		*
 *  Input:																*
 *		this		instance of <button id='browseSound'>				*
 ************************************************************************/
function browseSound()
{
    var	form	= document.picForm;
    sound	= window.open("Images", "sound");
}		// browseSound

/************************************************************************
 *  function taKeyDown													*
 *																		*
 *  Key pressed in notes textarea.										*
 *  This function is under development.  The intention is that pressing	*
 *  the ctrl-key combinations used by the CUA to alter the formatting	*
 *  of the currently selected text will work.							*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function taKeyDown(e)
{
    if (!e)
		e	= window.picture;
    var	code	= e.keyCode;

    // take action based upon code
    if (e.ctrlKey)
    {		// intercept Ctrl-key combinations
		switch (code)
		{
		    case 66:
		    {	// B
		 	alert('set bold');
		    }	// B
    
		    default:
		    {
				keyDown(e);
				break;
		    }
		}	// switch on key code
    }		// intercept Ctrl-key combinations
    else
		keyDown(e);

    return;
}		// taKeyDown

/************************************************************************
 *  function epKeyDown													*
 *																		*
 *  Handle key strokes that apply to the dialog as a whole.  For example*
 *  the key combinations Ctrl-S and Alt-U are interpreted to apply the	*
 *  update, as shortcut alternatives to using the mouse to click the 	*
 *  Update Picture button.												*
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function epKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.picture;	// IE
    }		// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.picForm;

    // take action based upon code
    switch (code)
    {

		case 83:
		{		// letter 'S'
		    if (e.ctrlKey)
		    {		// ctrl-S
				form.submit();
				return false;	// do not perform standard action
		    }		// ctrl-S
		    break;
		}		// letter 'S'

		case 85:
		{		// letter 'U'
		    if (e.altKey)
		    {		// alt-U
				form.submit();
		    }		// alt-U
		    break;
		}		// letter 'U'

    }	    // switch on key code

    return;
}		// epKeyDown

