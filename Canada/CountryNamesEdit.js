/************************************************************************
 *  CountryNamesEdit.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  CountryNamesEdit.php												*
 *																		*
 *  History:															*
 *		2017/10/27		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/07/22      add close button                                *
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
    var trace	        = '';

    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	    = document.forms[fi];
		trace	        += "<form ";
		if (form.name.length > 0)
		    trace	    += "name='" + form.name + "' ";
		if (form.id.length > 0)
		    trace	    += "id='" + form.id + "' ";
		trace	        += ">";

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		= form.elements[i];
		    trace += "<" + element.nodeName + " ";
			if (element.name.length > 0)
			    trace	+= "name='" + element.name + "' ";
			if (element.id.length > 0)
			    trace	+= "id='" + element.id + "' ";
		    trace	+= ">";
            var name            = element.id;
            if (name.length == 0)
                name            = element.name;
            var id              = '';
            var result          = /^(Code|Name|Article|Possessive|Delete)([a-z]{2})/.exec(name);
            if (result)
            {
                name            = result[1].toLowerCase();
                id              = result[2];
            }
            else
                name            = name.toLowerCase();

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

            switch (name)
            {
		        case 'add':
		        {
				    element.onclick	= addName;
                    break;
		        }

		        case 'code':
		        {
				    element.helpDiv	= 'Code';
                    break;
		        }

		        case 'name':
    		    {
    				element.helpDiv	= 'Name';
    				element.change	= change;
                    break;
    		    }

                case 'article':
		        {
			    	element.helpDiv	= 'Article';
			    	element.change	= change;
                    break;
		        }

                case 'possessive':
    		    {
    				element.helpDiv	= 'Possessive';
    				element.change	= change;
                    break;
    		    }

                case 'delete':
    		    {
    				element.helpDiv	= 'Delete';
    				element.onclick	= deleteName;
                    break;
    		    }

                case 'close':
    		    {
    				element.onclick	= closeForm;
                    break;
    		    }
            }           // act on specific fields
		}	            // loop through all elements in the form
    }		            // loop through all forms
}		// function onLoad

/************************************************************************
 *  function deleteName													*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		$this				<button type=button id='Delete....'			*
 ************************************************************************/
function deleteName(ev)
{
    ev.stopPropagation();

    var	rownum			= this.id.substring(6);	// row number
    var	form			= this.form;
    var	cell			= this.parentNode;	// <td> containing button
    var	row			    = cell.parentNode;	// <tr> containing button
    var inputs			= row.getElementsByTagName('input');
    for(var ii = 0; ii < inputs.length; ii++)
    {
		child	        = inputs[ii];
		if (child.name.substring(0,4) == 'Name')
		{
		    child.setAttribute('value', '');
		}
		child.type		= 'hidden';
    }
    row.removeChild(cell);
    return false;
}		// function deleteName

/************************************************************************
 *  function addName													*
 *																		*
 *  When the Add country button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Add'>					*
 ************************************************************************/
function addName(ev)
{
    ev.stopPropagation();

    var	form			= this.form;
    var table           = document.getElementById('dataTable');
    var	tbody			= table.tBodies[0];	// <tbody> containing row
    var	rownum	    	= tbody.rows.length + 1;
    var	parms	    	= {"rownum"         : rownum};
    var	template		= document.getElementById("RowTemplate");
    var	newRow			= createFromTemplate(template,
						    			     parms,
							    		     null);
    var	table			= document.getElementById("dataTable");
    tbody.appendChild(newRow);

    // take action when the user changes the code of the added country
    var	codeElt		    = form.elements['Code' + rownum];
    codeElt.focus();
    codeElt.select();
    codeElt.onchange	= change;

    return false;
}		// function addName

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
