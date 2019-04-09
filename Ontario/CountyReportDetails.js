/************************************************************************
 *  CountyReportDetails.js												*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page CountyReportDetails.php.										*
 *																		*
 *  History:															*
 *		2017/03/11		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/


/************************************************************************
 *  Invoke initialization once the entire page is loaded				*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 * specify style for tinyMCE editing									*
 ************************************************************************/
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
		content_css		: "/styles.css"

});

/************************************************************************
 *  function onLoad														*
 *																		*
 *  This function is called when the web page has been loaded into the	*
 *  browser.  Initialize dynamic functionality of elements.				*
 ************************************************************************/
function onLoad()
{
    document.body.onkeydown		= keyDown;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		//form.onsubmit 	= validateForm;
		//form.onreset 	= resetForm;

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handling

		    // an element whose value is passed with the update
		    // request to the server is identified by a name= attribute
		    // but elements which are used only by this script are
		    // identified by an id= attribute
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;

		    // set up dynamic functionality based on the name of the element
		    switch(name)
		    {		// act on specific fields
				case 'Surname':
				{
				    element.abbrTbl	= SurnAbbrs;
				    element.onchange	= change;
				    element.onkeydown	= keyDown;	// special key handling
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}	// surname field

				case 'GivenNames':
				{
				    element.abbrTbl	= GivnAbbrs;
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    // give focus to the given names field if present
				    element.focus();
				    element.select();
				    break;
				}	// given names field

				case 'Residence':
				{
				    element.abbrTbl	= LocAbbrs;
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    element.checkfunc	= checkAddress;
				    element.checkfunc(); 
				    break;
				}	// location fields

				case 'Faith':
				{
				    element.abbrTbl	= RlgnAbbrs;
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}	// religion field

				case 'Image':
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    break;
				}	// other fields

				default:
				{
				    element.onkeydown	= keyDown;	// special key handling
				    element.onchange	= change;	// default handler
				    element.checkfunc	= checkText;
				    element.checkfunc();
				    break;
				}	// other fields
		    }		// act on specific fields
		}		// loop through all elements in the form
    }			// loop through forms in the page

}		// onLoad

