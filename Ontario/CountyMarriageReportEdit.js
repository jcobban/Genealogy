/************************************************************************
 *  CountyMarriageReportEdit.js											*
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  CountyMarriageReportEdit.php										*
 *																		*
 *  History:															*
 *		2016/01/30		created											*
 *		2017/01/12		add display image button						*
 *		2017/03/11		add edit details button							*
 *		2017/07/18		invoke DistrictMarriagesEdit.php for CAUC		*
 *		2017/10/12		report errors from failed attempt to delete		*
 *						a report										*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of Window                                  *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    var	element;
    var trace	= '';
    for (var fi = 0; fi < document.forms.length; fi++)
    {		// loop through all forms
		var form	= document.forms[fi];

		for (var i = 0; i < form.elements.length; ++i)
		{	// loop through all elements of form
		    element		        = form.elements[i];
		    element.onkeydown	= keyDown;

		    var namePattern	= /^([a-zA-Z_]+)(\d*)$/;
		    var	id		        = element.id;
		    if (id.length == 0)
			    id		        = element.name;
		    var rresult		    = namePattern.exec(id);
		    var	column		    = id;
		    var	rownum		    = '';
		    if (rresult !== null)
		    {
			    column		    = rresult[1];
			    rownum		    = rresult[2];
		    }

		    trace += "column='" + column + "', ";
		    switch(column.toLowerCase())
		    {		// act on specific fields
				case 'volume':
				case 'reportno':
				case 'page':
				{
				    element.onchange	= change;
				    element.checkfunc	= checkNumber;
				    element.checkfunc();
				    break;
				}

				case 'domain':
				case 'residence':
				case 'remarks':
				{
				    element.onchange	= change;
				    break;
				}

				case 'givennames':
				{
				    element.abbrTbl	= GivnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}

				case 'surname':
				{
				    element.abbrTbl	= SurnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}

				case 'faith':
				{
				    element.abbrTbl	= RlgnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}

				case 'image':
				{
				    element.onchange	= change;
				    //element.checkfunc();
				    break;
				}

				case 'details':
				{
				    element.onclick	= editReport;
				    break;
				}

				case 'delete':
				{
				    element.onclick	= deleteReport;
				    break;
				}

				case 'editmarriages':
				{
				    element.onclick	= editMarriages;
				    break;
				}

				case 'displayimage':
				{
				    element.onclick	= displayImage;
				    break;
				}

				default:
				{
				    //alert("unexpected column='" + column + "'");
				    break;
				}
		    }		// act on specific fields
		}		    // loop through all elements in the form
    }			    // loop through all forms
}		// function onLoad

/************************************************************************
 *  fnction deleteReport												*
 *																		*
 *  When a Delete button is clicked this function removes the			*
 *  row from the table.													*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='Delete....'				*
 ************************************************************************/
function deleteReport()
{
    var	form	= this.form;
    var	rownum	= this.id.substring(6);
    var	domain	= form.Domain.value;
    var	volume	= form.elements['Volume' + rownum].value;
    var	report	= form.elements['ReportNo' + rownum].value;
    //alert("deleteReport: domain='" + domain + "', volume=" + volume + ", reportNo=" + report);
    var script	= 'deleteCountyMarriageReportXml.php';
    var	parms	= { 'Domain'	: domain,
				    'Volume'	: volume,
				    'ReportNo'	: report,
				    'rownum'	: rownum};
    if (debug != 'n')
    {
		parms["debug"]	= debug;
		alert("CountyMarriageReportEdit.js: deleteReport: 180 " +
				"parms	= { 'Domain'	: " + domain + "," +
				    "'Volume'	: "+volume+","+
				    "'ReportNo'	: "+report+","+
				    "'rownum'	: "+rownum+"}");
    }

    // update the citation in the database
    HTTP.post(  script,
				parms,
				gotDeleteReport,
				noDeleteReport);
    return false;
}		// function `deleteReport

/************************************************************************
 *  function gotDeleteReport											*
 *																		*
 *  This method is called when the XML file representing				*
 *  the deletion of the report from the database is retrieved.			*
 *																		*
 *  Input:																*
 *		xmlDoc		response document									*
 ************************************************************************/
function gotDeleteReport(xmlDoc)
{
    if (xmlDoc === undefined)
    {
		alert("CountyMarriageReportEdit.js: gotDeleteReport: xmlDoc is undefined!");
    }
    else
    {			// xmlDoc is defined
		var	root	= xmlDoc.documentElement;
		if (debug != 'n')
		    alert("CountyMarriageReportEdit.js: gotDeleteReport: " +
	        	  tagToString(root));

		var	msgs	    = root.getElementsByTagName('msg');
		if (msgs.length > 0)
		{
		    var msg	    = msgs[0].textContent.trim();
		    alert(msg);
		    return;
		}

		var	parms	    = root.getElementsByTagName('parms');
		if (parms.length > 0)
		{		// have at least 1 parms element
		    parms	    = parms[0];
		    var rownums	= parms.getElementsByTagName('rownum');
		    if (rownums.length > 0)
		    {		// have at least 1 rownum element
				var child	= rownums[0];
				var rownum	= child.textContent.trim();
				// remove identified row
				var rowid	= 'Row' + rownum;
				var row		= document.getElementById(rowid);
				var section	= row.parentNode;
				section.removeChild(row);
		    }		// have at least 1 rownum element
		}		// have at least 1 parms element
    }			// xmlDoc is defined
}		// function gotDeleteReport

/************************************************************************
 *  function noDeleteReport												*
 *																		*
 *  This method is called if there is no delete registration script.	*
 ************************************************************************/
function noDeleteReport()
{
    alert("CountyMarriageReportEdit.js: noDeleteReport: " +
				"script 'deleteCountyMarriageReportXml.php' not found on server");
}		// function noDeleteReport

/************************************************************************
 *  function editReport													*
 *																		*
 *  When a Report button is clicked this function displays the			*
 *  edit dialog for an individual report.								*
 *																		*
 *  Input:																*
 *		$this		<button type=button id='Details....'				*
 ************************************************************************/
function editReport()
{
    var	form	= this.form;
    var	rownum	= this.id.substring(this.id.length - 2);
    var	domain	= form.Domain.value;
    var	volume	= form.elements['Volume' + rownum].value;
    var	report	= form.elements['ReportNo' + rownum].value;
    window.open('CountyReportDetails.php?Domain=' + domain + '&Volume=' + volume + '&ReportNo=' + report,
				'_blank');
    return false;
}		// editReport

/************************************************************************
 *  function editMarriages												*
 *																		*
 *  When a Marriages button is clicked this function displays the		*
 *  edit dialog for the list of marriages in a report.					*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='EditMarriages....'		*
 ************************************************************************/
function editMarriages()
{
    var	form	= this.form;
    var	rownum	= this.id.substring(this.id.length - 2);
    var	domain	= form.Domain.value;
    var	volume	= form.elements['Volume' + rownum].value;
    var	report	= form.elements['ReportNo' + rownum].value;
    if (domain == 'CAUC')
		window.open('DistrictMarriagesEdit.php?Domain=' + domain +
					'&Volume=' + volume + '&ReportNo=' + report,
				    '_blank');
    else
		window.open('CountyMarriagesEdit.php?Domain=' + domain +
					'&Volume=' + volume + '&ReportNo=' + report,
				    '_blank');
    return false;
}		// editMarriages

/************************************************************************
 *  function displayImage												*
 *																		*
 *  When a Show Image button is clicked this function displays the		*
 *  image associated with this record.									*
 *																		*
 *  Input:																*
 *		$this			<button type=button id='DisplayImage....'		*
 ************************************************************************/
function displayImage()
{
    var	form	= this.form;
    var	rownum	= this.id.substring(this.id.length - 2);
    var	image	= form.elements['Image' + rownum].value;
    window.open(image,
				'_blank');
    return false;
}		// displayImage
