/************************************************************************
 *  editTownships.js													*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  editTownships.php													*
 *																		*
 *  History:															*
 *		2012/05/08		created											*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/10/19		support multiple countries and states/provinces	*
 *						move focuse to ID field of added township		*
 *						copy updated value of ID field to name if empty	*
 *		2016/11/13		add row even if existing table is empty			*
 *		2017/02/07		simplify implementation of delete township		*
 *		2018/10/21      change name of new township dialog template     *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/04/12      fix bug in adding multiple townships            *
 *		                simplify delete                                 *
 *		                validate new township for unique id             *
 *		2020/02/14      add edit location button                        *
 *		                use addEventListener                            *
 *		2020/06/30      use new field 'location'                        *
 *		                pass language selection to child windows        *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

window.addEventListener('load',	onLoad);

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 *																		*
 *  Input:																*
 *		this		window												*
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace	= '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		                    // loop through all forms
		var form	                = document.forms[fi];
		trace	                    += "<form ";
		if (form.name.length > 0)
		    trace	                += "name='" + form.name + "' ";
		if (form.id.length > 0)
		    trace	                += "id='" + form.id + "' ";
		trace	                    += ">";

		for (var i = 0; i < form.elements.length; ++i)
		{	                    // loop through all elements of form
		    element		            = form.elements[i];
		    trace                   += "<" + element.nodeName + " ";
		    if (element.name.length > 0)
				trace	            += "name='" + element.name + "' ";
		    if (element.id.length > 0)
				trace	            += "id='" + element.id + "' ";
		    trace	                += ">";
		    element.addEventListener('keydown',	keyDown);

		    // pop up help balloon if the mouse hovers over a field
		    // for more than 2 seconds
		    if (element.parentNode.nodeName == 'TD')
		    {	                // set mouseover on containing cell
				element.parentNode.addEventListener('mouseover', eltMouseOver);
				element.parentNode.addEventListener('mouseout',	eltMouseOut);
		    }	                // set mouseover on containing cell
		    else
		    {	                // set mouseover on input element itself
				element.addEventListener('mouseover',		eltMouseOver);
				element.addEventListener('mouseout',		eltMouseOut);
		    }	                // set mouseover on input element itself

		    var name		        = element.id;
		    if (name == '')
				name		        = element.name;
		    var	line		        = '';
		    var regexResult	        = /^([a-zA-Z_$]+)(\d*)/.exec(name);
		    if (regexResult)
		    {
				name		        = regexResult[1];
				line		        = regexResult[2];
		    }

		    switch(name.toLowerCase())
		    {			        // act on column name
				case 'add':
				{
				    element.addEventListener('click',	    addTownship);
				    break;
				}

				case 'code':
				{
				    element.helpDiv	    = 'Code';
				    element.change	    = changeCode;
                    element.checkfunc   = checkCode;
				    break;
				}

				case 'name':
				{
				    element.helpDiv	    = 'Name';
				    element.change	    = change;
                    element.checkfunc   = checkName;
				    break;
				}

				case 'delete':
				{
				    element.helpDiv	    = 'Delete';
				    element.addEventListener('click',	deleteTownship);
				    break;
				}

				case 'location':
				{
				    element.helpDiv	    = 'Location';
				    element.addEventListener('click',	showLocation);
				    break;
				}

				case 'concessions':
				{
				    element.helpDiv	    = 'Concessions';
				    element.addEventListener('click',	showConcessions);
				    break;
				}
		    }			        // act on column name
		}	                    // loop through all elements in the form
    }		                    // loop through all forms
}		// function onLoad

/************************************************************************
 *  function deleteTownship												*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		this		<button id='Delete...'>								*
 *		ev          instance of Javascript click Event                  *
 ************************************************************************/
function deleteTownship(ev)
{
    var	form		    = this.form;
    var	rowid	    	= this.id.substring(6);
    var	township	    = form.elements['Code' + rowid];
    township.value      = 'delete';
    township.type       = 'hidden';
    var	name    	    = form.elements['Name' + rowid];
    name.type           = 'hidden';
    var	cell	    	= this.parentNode;
    cell.removeChild(this);
    var	concessions	    = form.elements['Concessions' + rowid];
    cell	    	    = concessions.parentNode;
    cell.removeChild(concessions);
    var	row	        	= cell.parentNode;
}		// function deleteTownship

/************************************************************************
 *  function showLocation												*
 *																		*
 *  When a Location button is clicked this function opens a child		*
 *  dialog to display the Location.                                     *
 *																		*
 *  Input:																*
 *		this		<button id='Location...'>							*
 *		ev          instance of Javascript click Event                  *
 ************************************************************************/
function showLocation(ev)
{
    let lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    let	form		    = this.form;
    let	rowid	    	= this.id.substring(8);
    let	idlr	        = form.elements['IDLR' + rowid].value;
    if (idlr > 0)
    {                   // already defined
	    window.open("/FamilyTree/Location.php?idlr=" + idlr + '&lang=' + lang,
					'_blank');
    }                   // already defined
    else
    {                   // search
	    let	township	    = form.elements['Code' + rowid].value;
	    if (township.substring(township.length - 4) == ' Twp')
	        township        = township.substring(0, township.length - 4);
	    let	name    	    = form.elements['Name' + rowid].value;
	    let stateName       = form.StateName.value;
	    let cc              = form.Domain.value.substr(0,2);
	    if (cc == 'US')
	        cc              = 'USA';
	    else
	    if (cc == 'CA')
	        stateName       = form.Domain.value.substr(2);
	    let countyName      = form.CountyName.value;
	    let locationName    = township + ', ' + countyName + ', ' + stateName + ', ' + cc;
	    window.open("/FamilyTree/Location.php?name=" + encodeURI(locationName) +
                                            '&feedback=IDLR' + rowid +
                                            '&lang=' + lang,
					'_blank');
    }                   // search
}		// function showLocation

/************************************************************************
 *  function addTownship												*
 *																		*
 *  When the Add county button is clicked this function adds a row		*
 *  into the table.														*
 *																		*
 *  Input:																*
 *		this		<button id='Add...'>								*
 *		ev          instance of Javascript click Event                  *
 ************************************************************************/
function addTownship(ev)
{
    var	form	    	= this.form;
    var	table	    	= document.getElementById("dataTable");
    var	tbody	    	= table.tBodies[0];
    var	lastRow	    	= tbody.rows[tbody.rows.length - 1];
    var	numTownships	= tbody.rows.length;
    if (lastRow instanceof Node)
		numTownships	= lastRow.id.substring(3) - 0 + 1;
    var	domain		    = document.townshipForm.Domain.value;
    var	cc	    	    = domain.substring(0,2);
    var	province	    = domain.substring(2,2);
    var	parms	        = {"domain"	: domain,
		        		   "prov"	: province,
		        		   "cc"		: cc,
		        		   "code"	: '',
		        		   "name"	: '',
		        		   "line"	: numTownships};
    var	newRow		    = createFromTemplate("NewTownship$line",
				        				     parms,
				        				     null);
    newRow.id           = "Row" + numTownships;
    tbody.appendChild(newRow);

    // initialize dynamic functionality of new row
    var	codeField   	= form.elements["Code" + numTownships];
    var nameField	    = form.elements["Name" + numTownships];
    var	deleteBtn	    = document.getElementById("Delete" + numTownships);
    codeField.addEventListener('change',	changeCode);
    codeField.checkfunc	= checkCode;
    codeField.focus();
    nameField.addEventListener('change',	change);
    nameField.checkfunc	= checkName;
    deleteBtn.addEventListener('click',	    deleteTownship);

    return false;
}		// function addTownship

/************************************************************************
 *  function changeCode													*
 *																		*
 *  Take special action when the user changes the identifier field.		*
 *																		*
 *  Input:																*
 *		this		<input id='Code...'>								*
 *		ev          instance of Javascript change Event                 *
 ************************************************************************/
function changeCode(ev)
{
    var form	    = this.form;
    var	line		= this.name.substring(4);
    capitalize(this);

    // expand abbreviations
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);

    var nameField	= form.elements['Name' + line];
    if (nameField && nameField.value == '')
		nameField.value	= this.value;

    if (this.checkfunc)
		this.checkfunc();
}		// function changeCode

/************************************************************************
 *  function checkCode													*
 *																		*
 *  Validate changes to the identifier field.		                    *
 *																		*
 *  Input:																*
 *		this		<input id='Code...'>								*
 ************************************************************************/
function checkCode()
{
    var	element		= this;
    form            = this.form;
    var	re		    = /^[a-zA-Z7\u00c0-\u00ff .,'"()\-&\[\]?]*$/;
    var	name		= element.value;
    setErrorFlag(element, re.test(name));   // invalid contents
    var rownum      = this.id.substring(4) - 0;
    for (var line = 1; line < rownum; line++)
    {                       // loop through existing townships
        var idfield = form.elements['Code' + line];
        if (idfield && idfield.value == element.value)
        {                   // duplicate code found
            setErrorFlag(element, false);
            popupAlert("ID '" + element.value + "' duplicates an existing township", this);
            break;
        }                   // duplicate code found
    }                       // loop through existing townships
}		// function checkCode

/************************************************************************
 *  function showConcessions											*
 *																		*
 *  When a Concessions button is clicked this function displays the		*
 *  edit concessions application.										*
 *																		*
 *  Input:																*
 *		this		<button id='Concessions...'>						*
 *		ev          instance of Javascript click Event                  *
 ************************************************************************/
function showConcessions(ev)
{
    let lang            = 'en';
    if ('lang' in args)
        lang            = args.lang;
    var	line		= this.id.substring(11);
    var form		= this.form;
    var	domain		= form.Domain.value;
    var	county		= form.County.value;
    var	township	= form.elements['Code' + line].value;
    var	url	    	= 'ConcessionsEdit.php?domain=' + domain +
								'&county=' + county +
								'&township=' + township +
                                '&lang=' + lang;
    window.open(url,
				'_blank');
    return false;
}		// function showConcessions

