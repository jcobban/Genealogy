/************************************************************************
 *  Sources.js                                                          *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Sources.php.                                                   *
 *                                                                      *
 *  History:															*
 *      2010/10/17      set size of edit dialog to avoid scrolling		*
 *      2011/01/31      implement creation of new source				*
 *      2012/01/13      change class names								*
 *      2013/02/21      increase size of dialog for editing a source	*
 *      2013/03/28      support mouseover help							*
 *                      separate HTML and Javascript                    *
 *      2013/05/29      use actMouseOverHelp common function			*
 *      2013/06/19      add code to delete visible row when source is	*
 *                      function deleted                                *
 *      2013/08/01      defer facebook initialization until after load	*
 *      2014/12/12      enclose comment blocks							*
 *      2015/05/28      display source in split window					*
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/11/06      support opening windows with language           *
 *      2020/04/29      support fields and buttons for creating new     *
 *                      source and match behavior of Locations          *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/

window.onload           = onloadSources;

/************************************************************************
 *  function onLoadSources                                              *
 *                                                                      *
 *  Initialize dynamic functionality of the page.                       *
 ************************************************************************/
function onloadSources()
{
    var  form			= document.srcForm;

    // set action methods for form
    form.onsubmit           = validateForm;
    form.onreset            = resetForm;
    form.sourceCreated      = sourceCreated;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    var formElts            = form.elements;
    for (var i = 0; i < formElts.length; ++i)
    {
		var element         = formElts[i];

		element.onkeydown   = keyDown;
		element.onchange    = change;	// default handler

		// take action specific to element
		var  name;
		if (element.name && element.name.length > 0)
		    name            = element.name;
		else
		    name            = element.id;

        var column          = name;
        var idsr            = '';
        var result          = /^([a-zA-Z@#$%_]+)(\d*)/.exec(name);
        if (result)
        {
            column          = result[1].toLowerCase();
            idsr            = result[2];
        }

		switch(column)
		{               // act on field name
			case 'pattern':
			{
			    element.onkeydown   = keyDown;
			    element.onchange    = patternChanged;
			    element.focus();
			    break;
			}

			case 'namefld':
			{
			    element.onkeydown   = keyDown;
			    element.onchange    = nameChanged;
			    break;
			}

			case 'close':
			{
			    element.onclick     = closeDialog;
			    break;
			}

		    case 'createnew':
		    case 'new':
		    {
				element.onclick     = createSource;
				break;
		    }

            case 'edit':
			{
			    element.onclick     = editSource;
			    var row             = element.parentNode.parentNode;
			    if (row)
				    row.feedback    = sourceUpdated;
				break;
			}

		    case 'show':
			{
			    element.onclick     = showSource;
				break;
		    }

            case 'delete':
			{
			    element.onclick     = deleteSource;
				break;
		    }
		}               // act on field name
    }                   // loop through all elements in the form

}       // function onLoadSources

/************************************************************************
 *  function validateForm                                               *
 *                                                                      *
 *  Ensure that the data entered by the user has been minimally         *
 *  validated before submitting the form.                               *
 *                                                                      *
 *  Input:																*
 *      this            <form ...>		    							*
 ************************************************************************/
function validateForm()
{
    return true;
}       // function validateForm

/************************************************************************
 *  function resetForm                                                  *
 *                                                                      *
 *  This method is called when the user requests the form               *
 *  to be reset to default values.                                      *
 *                                                                      *
 *  Input:																*
 *      this              <form ...>									*
 ************************************************************************/
function resetForm()
{
    return true;
}   // resetForm

/************************************************************************
 *  function patternChanged                                             *
 *                                                                      *
 *  Take action when the value of the pattern field changes.  This      *
 *  specifically means that changes have been made and the focus has    *
 *  then left the field.                                                *
 *                                                                      *
 *  Input:																*
 *      this        <input type='text' id='pattern'>				    *
 ************************************************************************/
function patternChanged()
{
    var  form	= this.form;

    // expand abbreviations
    if (this.abbrTbl)
        expAbbr(this,
                this.abbrTbl);
    else
    if (this.value == '[')
        this.value  = '[Blank]';

    form.submit();
}       // function patternChanged

/************************************************************************
 *  function nameChanged                                                *
 *                                                                      *
 *  Take action when the value of the name field changes.  This         *
 *  specifically means that changes have been made and the focus has    *
 *  then left the field.                                                *
 *                                                                      *
 *  Input:																*
 *      this          <input type='text' id='namefld'>				    *
 ************************************************************************/
function nameChanged()
{
    var form	        = this.form;
    var lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;

    // expand abbreviations
    if (this.abbrTbl)
        expAbbr(this,
                this.abbrTbl);
    else
    if (this.value == '[')
        this.value      = '[Blank]';

    // open the individual location in a new tab or window
    var name            = encodeURIComponent(this.value);
    openFrame("source",
		      "/FamilyTree/editSource.php?idsr=0&form=srcForm&name=" + name + 
                        '&lang=' + lang,
		      "right");
}           // function nameChanged

/************************************************************************
 *  function sourceCreated                                              *
 *                                                                      *
 *  This method is called when a child window notifies this script      *
 *  that a new source has been created.                                 *
 *                                                                      *
 *  Input:																*
 *      this              <form ...>									*
 ************************************************************************/
function sourceCreated()
{
    var  createButton	= document.getElementById('CreateNew');
    if (createButton)
    {
		var  parentNode	= createButton.parentNode;
		var  textNode	= document.createTextNode(
					"Reloading page to display new source in order.");
		parentNode.insertBefore(textNode, createButton.nextSibling);
    }
    location.reload(true);
    return false;
}       // function sourceCreated

/************************************************************************
 *  function sourceUpdated                                              *
 *                                                                      *
 *  This method is called when a child window notifies this script      *
 *  that an existing source has been updated.                           *
 *                                                                      *
 *  Input:																*
 *      this            <tr id='Row...'>							    *
 *      parms           associative array of field values			    *
 ************************************************************************/
function sourceUpdated(parms)
{
    var text                = "parms={";

    var  idsr		        = this.id.substring(9);
    var  cell;
    for(fldname in parms)
    {                   // loop through all parameters
		text                += fldname + "='" + parms[fldname] + "',";
		switch(fldname.toLowerCase())
		{               // act on specific parameters
		    case 'srcname':
		    {           // public name of source
				cell        = document.getElementById('Name' + idsr);
				if (cell)
				    cell.innerHTML  = parms[fldname];
				break;
		    }           // public name of source

		    case 'idst':
		    {           // type of source
				cell        = document.getElementById('Type' + idsr);
				var idst    = parms[fldname];
				var type    = document.getElementById('IDST' + idst);
				if (cell)
				{
				    if (type)
				    {
					    cell.innerHTML  = type.innerHTML.trim();
				    }
				    else
				    {
				        alert("Sources.js:sourceUpdated: no element 'IDST" +
                                idst + "'");
					    cell.innerHTML  = parms[fldname];
				    }   // act on specific types
				}
				else
				    alert("Sources.js:sourceUpdated: " +
                            "no <element id='Type" + idsr + "'");
				break;
		    }           // type of source
		}               // act on specific parameters
    }                   // loop through all parameters
    return false;
}           // function sourceUpdated

/************************************************************************
 *  function createSource                                               *
 *                                                                      *
 *  This method is called when the user requests to create              *
 *  a new Source.                                                       *
 *                                                                      *
 *  Input:																*
 *      this          <button id='CreateNew'>							*
 ************************************************************************/
function createSource()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    openFrame("source",
		      "/FamilyTree/editSource.php?idsr=0&form=srcForm&lang=" + lang,
		      "right");
    return false;
}       // function createSource

/************************************************************************
 *  function showSource                                                 *
 *                                                                      *
 *  This method is called when the user requests to show                *
 *  an existing Source.  It pops up a child window.                     *
 *                                                                      *
 *  Input:																*
 *      this              <button id='Show....'>						*
 ************************************************************************/
function showSource()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var  idsr	    = this.id.substring(4);
    openFrame("source",
		      "/FamilyTree/Source.php?idsr=" + idsr + "&lang=" + lang,
		      "right");
    return false;
}           // function showSource

/************************************************************************
 *  function editSource                                                 *
 *                                                                      *
 *  This method is called when the user requests to edit                *
 *  an existing Source.  It pops up a child window.                     *
 *                                                                      *
 *  Input:																*
 *      this        <button id='Edit....'>								*
 ************************************************************************/
function editSource()
{
    var     lang    = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var  idsr	= this.id.substring(4);
    openFrame("source",
		      "/FamilyTree/editSource.php?idsr=" + idsr +
							"&elementid=sourceRow" + idsr +
                            "&lang=" + lang,
		      "right");
    return false;
}       // editSource

/************************************************************************
 *  function deleteSource                                               *
 *                                                                      *
 *  This method is called when the user requests to delete              *
 *  an unreferenced existing Source.                                    *
 *                                                                      *
 *  Input:																*
 *      this            <button id='Delete...'>							*
 ************************************************************************/
function deleteSource()
{
    var  idsr	= this.id.substring(6);
    var parms      = { "idsr" : idsr};

    // invoke script to delete the record
    HTTP.post("/FamilyTree/deleteSourceXml.php",
		      parms,
		      gotDelete,
		      noDelete);
    return false;
}           // function deleteSource

/************************************************************************
 *  function gotDelete                                                  *
 *                                                                      *
 *  This method is called when the response to the request to delete    *
 *  an event is received.                                               *
 *                                                                      *
 *  Parameters:															*
 *      xmlDoc          reply as an XML document						*
 ************************************************************************/
function gotDelete(xmlDoc)
{
    var  evtForm	        = document.evtForm;
    var  root	            = xmlDoc.documentElement;
    if (root && root.nodeName && root.nodeName == 'deleted')
    {
		var msglist         = root.getElementsByTagName('msg');
		if (msglist.length == 0)
		{
		    var idsr        = root.getAttribute('idsr');
		    var row         = document.getElementById('Row' + idsr);
		    if (row)
		    {           // have row to delete
				var  sect	= row.parentNode;
				sect.removeChild(row);
		    }           // have row to delete
		}
		else
		{
		    alert(tagToString(msglist.item(0)));
		}
    }
    else
    {                   // error
		var  msg	        = "Error: ";
		if (root && root.childNodes)
		    msg             += tagToString(root)
		else
		    msg             += xmlDoc;
		alert (msg);
    }                   // error
}       // function gotDelete

/************************************************************************
 *  function noDelete                                                   *
 *                                                                      *
 *  This method is called if there is no response to the AJAX           *
 *  delete event request.                                               *
 ************************************************************************/
function noDelete()
{
    alert("Sources.js: noDelete: " +
		  "script /FamilyTree/deleteSourceXml.php not found");
}       // function noDelete

/************************************************************************
 *  function closeDialog                                                *
 *                                                                      *
 *  Take action to close the dialog.                                    *
 *                                                                      *
 *  Input:																*
 *      this        <button id='Close'> 								*
 ************************************************************************/
function closeDialog()
{
    closeFrame();
}       // closeDialog
