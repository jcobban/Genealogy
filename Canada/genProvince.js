/************************************************************************
 *  genProvince.js														*
 *																		*
 *  This file implements the dynamic functionality of                   *
 *  the main resource page of a state or province.			            *
 *																		*
 *  History:															*
 *		2018/03/07		add ability to display and hide parts of		*
 *						the information									*
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  Initialization code that is executed when this script is loaded.		*
 *																		*
 *  Define the function to be called once the web page is loaded.		*
 ************************************************************************/
    window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization functions once the page is loaded.				*
 *  Each field is enabled for the default keyboard and mouse support.		*
 ************************************************************************/
function onLoad()
{
    // scan through all forms and set dynamic functionality
    // for specific elements
    for(var i = 0; i < document.forms.length; i++)
    {
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    var element	= form.elements[j];

		    switch(element.id)
		    {			// act on specific element
				case 'showAll':
				{
				    element.onclick	= showAll;
				    break;
				}

				case 'showCounties':
				{
				    element.onclick	= showCounties;
				    break;
				}

				case 'showDbs':
				{
				    element.onclick	= showDatabases;
				    break;
				}

		    }			// act on specific element

		}	// loop through elements in form
    }		// iterate through all forms
}	// function onLoad

/************************************************************************
 *  function showAll													*
 *																		*
 *  Display the set ot links for resources specific to this state or	*
 *  province within a federal state.									*
 *																		*
 *  Input:																*
 *  	this			instance of <button id="showAll">				*
 ************************************************************************/
function showAll()
{
    var	division	= document.getElementById('provinceAll');
    if (division)
    {			// there is a <div id="provinceAll">
		division.style.display	= 'inline';
		this.innerHTML		= '-';
		this.onclick		= hideAll;
    }			// there is a <div id="provinceAll">
    return false;
}	// function showAll

/************************************************************************
 *  function hideAll														*
 *																		*
 *  Hide the set of links for resources specific to this state or		*
 *  province within a federal state.										*
 *																		*
 *  Input:																*
 *  		this				instance of <button id="showAll">				*
 ************************************************************************/
function hideAll()
{
    var	division	= document.getElementById('provinceAll');
    if (division)
    {			// there is a <div id="provinceAll">
		division.style.display	= 'none';
		this.innerHTML		= '+';
		this.onclick		= showAll;
    }			// there is a <div id="provinceAll">
    return false;
}	// function hideAll

/************************************************************************
 *  function showCounties														*
 *																		*
 *  Display the set ot links for resources specific to this state or		*
 *  province within a federal state.										*
 *																		*
 *  Input:																*
 *  		this				instance of <button id="showCounties">				*
 ************************************************************************/
function showCounties()
{
    var	division	= document.getElementById('counties');
    if (division)
    {			// there is a <div id="counties">
		division.style.display	= 'inline';
		this.innerHTML		= '-';
		this.onclick		= hideCounties;
    }			// there is a <div id="counties">
    return false;
}	// function showCounties

/************************************************************************
 *  function hideCounties														*
 *																		*
 *  Hide the set of links for resources specific to this state or		*
 *  province within a federal state.										*
 *																		*
 *  Input:																*
 *  		this				instance of <button id="showCounties">				*
 ************************************************************************/
function hideCounties()
{
    var	division	= document.getElementById('counties');
    if (division)
    {			// there is a <div id="counties">
		division.style.display	= 'none';
		this.innerHTML		= '+';
		this.onclick		= showCounties;
    }			// there is a <div id="counties">
    return false;
}	// function hideCounties

/************************************************************************
 *  function showDatabases														*
 *																		*
 *  Display the set ot links for resources specific to this state or		*
 *  province within a federal state.										*
 *																		*
 *  Input:																*
 *  		this				instance of <button id="showDatabases">				*
 ************************************************************************/
function showDatabases()
{
    var	division	= document.getElementById('databases');
    if (division)
    {			// there is a <div id="databases">
		division.style.display	= 'inline';
		this.innerHTML		= '-';
		this.onclick		= hideDatabases;
    }			// there is a <div id="databases">
    return false;
}	// function showDatabases

/************************************************************************
 *  function hideDatabases														*
 *																		*
 *  Hide the set of links for resources specific to this state or		*
 *  province within a federal state.										*
 *																		*
 *  Input:																*
 *  		this				instance of <button id="showDatabases">				*
 ************************************************************************/
function hideDatabases()
{
    var	division	= document.getElementById('databases');
    if (division)
    {			// there is a <div id="databases">
		division.style.display	= 'none';
		this.innerHTML		= '+';
		this.onclick		= showDatabases;
    }			// there is a <div id="databases">
    return false;
}	// function hideDatabases
