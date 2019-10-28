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
 *		2019/07/22      add Close button                                *
 *		                support ISO format for domain code with hyphen  *
 *		                support return, up and down arrows in table     *
 *		                add new row for return out of last row          *
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
    var trace		        = '';
    var	namePattern	        = /[A-Z\-]*$/;    // trailing upper case letters
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		        = form.elements[i];

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {	// set mouseover on containing cell
				element.parentNode.onmouseover	= eltMouseOver;
				element.parentNode.onmouseout	= eltMouseOut;
		    }	// set mouseover on containing cell
		    else
		    {	// set mouseover on input element itself
				element.onmouseover		        = eltMouseOver;
				element.onmouseout		        = eltMouseOut;
		    }	// set mouseover on input element itself

		    var name					= element.id;
		    var column					= name;
		    var code					= '';
		    if (name.length == 0)
				name					= element.name;
		    var pattResult				= namePattern.exec(name);
		    if (pattResult)
		    {
				code					= pattResult[0];
				var nameLen		        = name.length - code.length;
				column		            = name.substring(0, nameLen);
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
				    element.onclick	    = addDomain;
				    break;
				}

				case 'Close':
				{
				    element.onclick	    = closeForm;
				    break;
				}

				case 'Code':
				{
				    element.helpDiv	    = 'Code';
				    element.onchange	= changeCode;
		            element.addEventListener('keydown',	goToNext);
				    break;
				}

				case 'Name':
				{
				    element.helpDiv	    = 'Name';
				    element.onchange	= changeName;
		            element.addEventListener('keydown',	goToNext);
				    break;
				}

				case 'Delete':
				{
				    element.helpDiv	    = 'Delete';
				    element.onclick	    = deleteDomain;
				    break;
				}

				case 'EditCounties':
				{
				    element.helpDiv	= 'EditCounties';
				    element.onclick	= showCounties;
				    break;
				}
		    }

		    element.addEventListener('keydown',	keyDown);
		}	        // loop through all elements in the form
    }		        // loop through all forms

}		// function onLoad

/************************************************************************
 *  function updateDisplay												*
 *																		*
 *  Take action when the user changes a main selection, so the display	*
 *  should be updated.													*
 *																		*
 *  Input:																*
 *		$this			instance of <select >							*
 ************************************************************************/
function updateDisplay()
{
    this.form.submit();
}		// function updateDisplay

/************************************************************************
 *  function changeCode													*
 *																		*
 *  Take action when the user changes the domain code.					*
 *  Ensure that the change is applied to the record for the currently	*
 *  selected language, and not the English record which may				*
 *  be currently displayed as a default value for another language		*
 *																		*
 *  Input:																*
 *		$this			instance of <input name='Code...'>				*
 ************************************************************************/
function changeCode()
{
    var	name		= this.name;
    var language	= this.form.language.value;
    var	code		= name.substring(4);
    this.form.elements['Lang' + code].value	= language;
    changeElt(this);
}		// function changeCode

/************************************************************************
 *  function changeName													*
 *																		*
 *  Take action when the user changes the domain name.					*
 *  Ensure that the change is applied to the record for the currently	*
 *  selected language, and not the English record which may				*
 *  be currently displayed as a default value for another language		*
 *																		*
 *  Input:																*
 *		$this			instance of <input name='Name...'>				*
 ************************************************************************/
function changeName()
{
    var	name		= this.name;
    var language	= this.form.language.value;
    var	code		= name.substring(4);
    this.form.elements['Lang' + code].value	= language;
    changeElt(this);
}		// function changeName

/************************************************************************
 *  function deleteDomain												*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table, and sets up values to cause the submit to		*
 *  delete the record from the table.                                   *
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Delete....'				*
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
}		// function deleteDomain

/************************************************************************
 *  function showCounties												*
 *																		*
 *  When a Counties button is clicked this function displays the		*
 *  edit dialog for the list of counties in a domain.					*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Edit....'				*
 ************************************************************************/
function showCounties()
{
    var	form	= this.form;
    var	domain	= this.id.substring(12);
    window.open('CountiesEdit.php?Domain=' + domain,
				'_blank');
    return false;
}		// function showCounties

/************************************************************************
 *  function addDomain													*
 *																		*
 *  When the Add Domain button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Add'>					*
 ************************************************************************/
function addDomain()
{
    this.disabled   	= true;	// only permit one row to be added
    var	form	    	= this.form;
    var	parms	    	= {"domain"	    : form.cc.value + "-XX",
                           "cc"         : form.cc.value,
				    	   "language"	: form.language.value};
    var	template    	= document.getElementById("Row$domain");
    var	newRow	    	= createFromTemplate(template,
				    					     parms,
				    					     null);
    var	table		    = document.getElementById("dataTable");
    var	tbody		    = table.tBodies[0];
    tbody.appendChild(newRow);

    // take action when the user changes the code of the added domain
    var eltName         = 'Code' + form.cc.value + "-XX";
    var	codeElt		    = form.elements[eltName];
    codeElt.focus();
    codeElt.select();
    codeElt.onchange	= changeCode;

    return false;
}		// function addDomain

/************************************************************************
 *  function closeForm          										*
 *																		*
 *  This method is called when the user requests to close the			*
 *  window without updating the event									*
 *																		*
 *  Input:																*
 *		this		the <button id='close'> element						*
 ************************************************************************/
function closeForm(ev)
{
    closeFrame();
}		// function closeForm

/************************************************************************
 *  function goToNext													*
 *																		*
 *  On the Enter key go to the Code field of the next line of the       *
 *  table.
 *																		*
 *  Parameters:															*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/

var codePattern     = /([a-zA-Z]{2}-[a-zA-Z]{2,3}|[a-zA-Z]{4,5})$/;
function goToNext(e)
{
    var form            		= this.form;

    if (!e)
    {		// browser is not W3C compliant
		e   	        		=  window.event;	// IE
    }		// browser is not W3C compliant

    var	code	        		= e.key;
    var cell            		= this.parentNode;
    var row             		= cell.parentNode;
    var tbody           		= row.parentNode;
    var table           		= tbody.parentNode;
    var numrows         		= tbody.rows.length;
    var nextRow         		= null;
    if (row.sectionRowIndex == 0)
        prevRow         		= tbody.rows[numrows - 1];
    else
        prevRow         		= tbody.rows[row.sectionRowIndex - 1];
    var onLast          		= row.sectionRowIndex == numrows - 1;
    if (onLast)
        nextRow         		= tbody.rows[0];
    else
        nextRow         		= tbody.rows[row.sectionRowIndex + 1];
    var domain;

    // take action based upon code
    switch (code)
    {                           // Enter key
        case "Enter":
		{
            if (onLast)
            {                   // add new row
                var d1              = Math.floor(row.sectionRowIndex / 26);
                var d2              = (row.sectionRowIndex % 26);
                domain              = form.cc.value + '-' +
                                      String.fromCharCode(d1 + 65) + 
                                      String.fromCharCode(d2 + 65); 
			    var	parms	    	= {"domain"	    : domain,
							    	   "language"	: form.language.value};
			    var	template    	= document.getElementById("Row$domain");
			    var	newRow	    	= createFromTemplate(template,
							    					     parms,
							    					     null);
			    tbody.appendChild(newRow);
            }                   // add new row
            else
            {                   // go to next row
                domain              = nextRow.id.substring(3);
            }                   // go to next row

			var eltName             = 'Code' + domain;
			var	codeElt		        = form.elements[eltName];
			codeElt.focus();
            codeElt.selectionStart  = codeElt.selectionEnd      = 3;
		    codeElt.addEventListener('keydown',	goToNext);

			eltName                 = 'Name' + domain;
			var	nameElt		        = form.elements[eltName];
		    nameElt.addEventListener('keydown',	goToNext);

            e.preventDefault();
            e.stopPropagation();
		    return false;		// suppress default action
		}	                    // Enter

        case "ArrowDown":
        {
            var name                = this.name;
            var matches             = codePattern.exec(name);
            var dlen                = matches[1].length;
            var column              = name.substring(0, name.length - dlen);
            domain                  = nextRow.id.substring(3);

			var eltName             = column + domain;
			var	codeElt		        = form.elements[eltName];
			codeElt.focus();
            codeElt.selectionStart  = 0;
            codeElt.selectionEnd    = codeElt.value.length;
            e.preventDefault();
            e.stopPropagation();
		    return false;		// suppress default action
        }

        case "ArrowUp":
        {
            var name                = this.name;
            var matches             = codePattern.exec(name);
            var dlen                = matches[1].length;
            var column              = name.substring(0, name.length - dlen);
            domain                  = prevRow.id.substring(3);

			var eltName             = column + domain;
			var	codeElt		        = form.elements[eltName];
			codeElt.focus();
            codeElt.selectionStart  = 0;
            codeElt.selectionEnd    = codeElt.value.length;
            e.preventDefault();
            e.stopPropagation();
		    return false;		// suppress default action
        }

    }	    // switch on key code

    return;
}		// function goToNext


