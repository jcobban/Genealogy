/************************************************************************
 *  Name.js															    *
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page Name.php.														*
 *																		*
 *  History:															*
 *		2018/11/04      created                                         *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize elements.												*
 ************************************************************************/
function onLoad()
{
    // activate functionality of form elements
    var trace   = '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var	form		= document.forms[fi];
		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{
		    var element	= formElts[i];

		    var	name			= element.name;
		    if (name.length == 0)
		    {		// button elements usually have id not name
				name			= element.id;
		    }		// button elements usually have id not name
            trace       += form.id + ':' + name + ',';

		    switch(name)
		    {		// act on specific element
				case 'PostBlog':
				{	// post blog button
				    element.onclick		= postBlog;
				    break;
				}	// post blog button

				case 'Pattern':
				{
				    element.onchange		= changePattern;
				    break;
				}	// Pattern input field

				case 'message':
				{
                    var frame       = document.getElementById('message_ifr');
                    frame.helpDiv   = 'message';
				    actMouseOverHelp(frame);    // tinymce
				    break;
				}	// Pattern input field

		    }		
		// act on specific element
		}		    // loop through all elements in the form
    }			    // loop through all forms
}		// function onLoad
