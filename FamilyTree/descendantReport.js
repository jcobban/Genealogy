/************************************************************************
 *  descendantReport.js													*
 *																		*
 *  Javascript code to implement dynamic functionality of the			*
 *  page descendantReport.php.											*
 *																		*
 *  History:															*
 *		2010/12/29		created											*
 *		2011/04/06		add option to display locations					*
 *		2012/01/13		change class names								*
 *		2013/07/31		defer setup of facebook link					*
 *						standardize initialization						*
 *						activate popup help for all fields				*
 *		2015/01/23		add close button								*
 *		2015/02/10		use closeFrame									*
 *	    2015/06/03	    add full screen button                          *
 *	    2018/10/26      checkfunc not defined for descdepth             *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

    window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Initialize dynamic functionality of elements.						*
 ************************************************************************/
function onLoad()
{
    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
		var	form		= document.forms[fi];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    var	name	= element.name;
		    if (name === undefined || name.length == 0)
				name	= element.id;

		    // take action specific to the element based on its name
		    switch(name)
		    {		// switch on name
				case "descDepth":
				{
				    element.onchange	= recalculate;
				    element.checkfunc	= checkNumber;
				    break;
				}

				case "incLocsSet":
				{
				    element.onchange	= changeLocs;
				    break;
				}

				case "Close":
				{
				    element.onclick	= close;
				    break;
				}	// incLocsSet

				case "FullScreen":
				{
				    element.onclick	= fullScreen;
				    break;
				}	// incLocsSet
		    }		// switch on name
		}		// loop through all form elements
    }			// loop through all forms

}		// onLoad

/************************************************************************
 *  changeLocs															*
 *																		*
 *  This method is called when the user modifies the value of			*
 *  the include locations checkbox.										*
 *																		*
 *  Parameters:															*
 *		this points to the input element whose value has been changed.	*
 ************************************************************************/
function changeLocs()
{
    var form		= this.form;
    if (this.checked)
		form.incLocs.value	= 1;
    else
		form.incLocs.value	= 0;
    form.submit();
}		// changeLocs

/************************************************************************
 *  recalculate															*
 *																		*
 *  This method is called when the user modifies the value of			*
 *  the tree depth.														*
 *																		*
 *  Parameters:															*
 *		this points to the input element whose value has been changed.	*
 ************************************************************************/
function recalculate()
{
    var	re		= /^[0-9]+$/;
    this.checkfunc();
    if (!re.test(this.value.trim()))
		return;
    var form		= this.form;
    form.submit();
}		// recalculate

/************************************************************************
 *  close																*
 *																		*
 *  This method is called when the user clicks on the button to close	*
 *  the dialog.															*
 *																		*
 *  Parameters:															*
 *		this		<button id='Close'>									*
 ************************************************************************/
function close()
{
    closeFrame();
}		// close

/************************************************************************
 *  fullScreen															*
 *																		*
 *  This method is called when the user clicks on the button to 		*
 *  open the dialog in a new window or tab.								*
 *																		*
 *  Parameters:															*
 *		this		<button id='FullScreen'>							*
 ************************************************************************/
function fullScreen()
{
    window.open(location.href, '_blank');
}		// fullScreen






