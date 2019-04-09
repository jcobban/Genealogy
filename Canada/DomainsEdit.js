/************************************************************************
 *  editDomains.js														*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  editDomains.php														*
 *																		*
 *  History:															*
 *		2016/05/20		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/04/07      ensure that the paging lines can be displayed   *
 *		                within the visible portion of the browser.      *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace		= '';
    var	namePattern	= /[A-Z]*$/;
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];
		    element.onkeydown	= keyDown;

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

		    var name		= element.id;
		    var column		= name;
		    var code		= '';
		    if (name.length == 0)
				name		= element.name;
		    var pattResult	= namePattern.exec(name);
		    if (pattResult)
		    {
				code		= pattResult[0];
				column		= name.substring(0,name.length - code.length);
		    }

		    switch(column)
		    {
				case 'cc':
				{
				    element.onchange	= updateDisplay;
				    break;
				}

				case 'language':
				{
				    element.onchange	= updateDisplay;
				    break;
				}

				case 'Add':
				{
				    element.onclick	= addDomain;
				    break;
				}

				case 'Code':
				{
				    element.helpDiv	= 'Code';
				    element.onchange	= changeCode;
				    break;
				}

				case 'Name':
				{
				    element.helpDiv	= 'Name';
				    element.onchange	= changeName;
				    break;
				}

				case 'Delete':
				{
				    element.helpDiv	= 'Delete';
				    element.onclick	= deleteDomain;
				    break;
				}

				case 'EditCounties':
				{
				    element.helpDiv	= 'EditCounties';
				    element.onclick	= showCounties;
				    break;
				}
		    }
		}	        // loop through all elements in the form
    }		        // loop through all forms

    var dataTable           = document.getElementById('dataTbl');
    var dataWidth           = dataTable.offsetWidth;
    var windowWidth             = document.body.clientWidth - 8;
    if (dataWidth > windowWidth)
        dataWidth               = windowWidth;
    var topBrowse           = document.getElementById('topBrowse');
    topBrowse.style.width   = dataWidth + "px";
    var botBrowse               = document.getElementById('botBrowse');
    if (botBrowse)
        botBrowse.style.width   = dataWidth + "px";
}		// onLoad

/************************************************************************
 *  updateDisplay														*
 *																		*
 *  Take action when the user changes a main selection, so the display		*
 *  should be updated.														*
 *																		*
 *  Input:																*
 *		$this				instance of <select >								*
 ************************************************************************/
function updateDisplay()
{
    this.form.submit();
}		// updateDisplay

/************************************************************************
 *  changeCode																*
 *																		*
 *  Take action when the user changes the domain code.						*
 *  Ensure that the change is applied to the record for the currently		*
 *  selected language, and not the English record which may				*
 *  be currently displayed as a default value for another language		*
 *																		*
 *  Input:																*
 *		$this				instance of <input name='Code...'>				*
 ************************************************************************/
function changeCode()
{
    var	name		= this.name;
    var language	= this.form.language.value;
    var	code		= name.substring(4);
    this.form.elements['Lang' + code].value	= language;
    changeElt(this);
}		// changeCode

/************************************************************************
 *  changeName																*
 *																		*
 *  Take action when the user changes the domain name.						*
 *  Ensure that the change is applied to the record for the currently		*
 *  selected language, and not the English record which may				*
 *  be currently displayed as a default value for another language		*
 *																		*
 *  Input:																*
 *		$this				instance of <input name='Name...'>				*
 ************************************************************************/
function changeName()
{
    var	name		= this.name;
    var language	= this.form.language.value;
    var	code		= name.substring(4);
    this.form.elements['Lang' + code].value	= language;
    changeElt(this);
}		// changeName

/************************************************************************
 *  deleteDomain														*
 *																		*
 *  When a Delete button is clicked this function removes the				*
 *  row from the table, and sets up values to cause the submit to		*
 *  delete the record from the table.
 *																		*
 *  Input:																*
 *		$this				<button type=button id='Delete....'				*
 ************************************************************************/
function deleteDomain()
{
    var	form		= this.form;
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;
    var	code		= this.id.substring(6);
    var	rowlang		= form.elements['Lang' + code].value;
    row.innerHTML	= "<td><input type='hidden' name='Code" + code +
						  "' id='Code" + code + "' value='" + 
								code + "'>\n" +
						  "<input type='hidden' name='Lang" + code +
						  "' id='Lang" + code + "' value='" + rowlang + "'>\n" +
						  "<input type='hidden' name='Name" + code +
						  "' id='Name" + code + "' value=''></td>\n";
    return false;
}		// deleteDomain

/************************************************************************
 *  showCounties														*
 *																		*
 *  When a Counties button is clicked this function displays the		*
 *  edit dialog for the list of counties in a domain.						*
 *																		*
 *  Input:																*
 *		$this				<button type=button id='Edit....'				*
 ************************************************************************/
function showCounties()
{
    var	form	= this.form;
    var	domain	= this.id.substring(12);
    window.open('CountiesEdit.php?Domain=' + domain,
				'_blank');
    return false;
}		// showCounties

/************************************************************************
 *  addDomain																*
 *																		*
 *  When the Add Domain button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		$this				<button type=button id='Add'>						*
 ************************************************************************/
function addDomain()
{
    this.disabled	= true;	// only permit one row to be added
    var	form		= this.form;
    var	parms		= {"domain"	: form.cc.value + "XX",
						   "language"	: form.language.value};
    var	template	= document.getElementById("Row$domain");
    var	newRow		= createFromTemplate(template,
									     parms,
									     null);
    var	table		= document.getElementById("dataTbl");
    var	tbody		= table.tBodies[0];
    tbody.appendChild(newRow);

    // take action when the user changes the code of the added domain
    var	codeElt		= form.CodeXxx;
    codeElt.focus();
    codeElt.select();
    codeElt.onchange	= changeCode;

    return false;
}		// addDomain
