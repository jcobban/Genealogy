/************************************************************************
 *  CensusForm.js														*
 *																		*
 *  This file contains the JavaScript functions that implement the		*
 *  dynamic functionality of the forms used to enter a page of census	*
 *  data.  This file is shared between all census forms because most	*
 *  of the functionality is common to all Canadian censuses.			*
 *																		*
 *  History:															*
 *		2010/11/18		expand '[' to [Blank]							*
 *		2011/02/05		blank out all cells in deleted rows				*
 *						on reset fill page to normal size				*
 *		2011/02/16		submit update on ctrl-S or alt-U				*
 *		2011/04/03		add relationships (Servant, Son-in-Law)			*
 *		2011/05/18		expand abbreviations in given names and			*
 *						address field word by word.						*
 *		2011/06/05		add "daughter-in-law" to relationships			*
 *						change individual words of birth date			*
 *						support hiding and revealing columns			*
 *		2011/07/01		add support for 1916 census						*
 *		2011/07/02		report id of transcriber						*
 *						permit transcriber to correct image URL			*
 *		2011/09/10		specify to invoke onLoad when page loaded		*
 *		2011/09/22		support census forms with no footer row			*
 *		2011/09/25		check month in calculating age from birth year	*
 *						use id of element if name not defined to find	*
 *						help.										 	*
 *						For prairie province censuses, replicate down	*
 *						the address subfields.input element				*
 *						Clean up and simplify the implementation of		*
 *						replicating a value down a column.				*
 *		2011/10/09		expand individual words of replicating fields	*
 *						expand individual words of occupation field		*
 *						copy value of CantRead as CantWrite default		*
 *						Use shared abbreviation tables from				*
 *						/jscripts/CommonForm.js							*
 *		2011/10/15		use shared displayHelp routine in 				*
 *						../jscripts/util.js								*
 *						pop up help balloon when mouse held				*
 *						over a field for more than a seconscd.			*
 *		2011/10/19		add mouseover for forward and backward links	*
 *		2011/10/20		add warning message for non-numeric number of	*
 *						employees										*
 *		2011/11/09		loosen check for explicit blank agsce and birth	*
 *		2011/12/15		allow age in days or weeks or ?					*
 *		2011/12/30		handle blank birth year10 text field better		*
 *		2012/01/02		support mouse selection for copy and paste		*
 *		2012/04/04		partial implementation of IDIR management.		*
 *		2012/04/17		remove obsolete getRowNum function				*
 *						clean up support of form-wide buttons			*
 *						add treeMatch button							*
 *						fix bug going to last cell on Ctrl-End			*
 *						use templates to support i18n					*
 *						popup search dialog for family tree Find button	*
 *		2012/04/22		eliminate local functions that duplicate		*
 *						functionality in CommonForm.js					*
 *		2012/05/02		reposition doIdir dialog panel so it does not	*
 *						cover the entry being searched for				*
 *		2012/05/03		handle non-numeric age							*
 *		2012/05/04		use common displayDialog function				*
 *		2012/07/15		permit other characters in birth year field		*
 *						for example "[1887]"							*
 *						set BDate field to "[Blank]" if it is empty		*
 *						when birth year field is changed.				*
 *		2012/07/16		fill in defaults for CanRead and CanWrite in	*
 *						1891 census if the fields are all blank.		*
 *		2012/07/22		expand 2 digit year of birth to 4 digit year	*
 *						insert estimate in birth year field if not		*
 *						supplied by the enumerator						*
 *		2012/07/27		parameters to getIndivNamesXml.php script		*
 *						changed so that GivenNames is always full value	*
 *		2012/07/30		add support for button to clear associated IDIR	*
 *		2012/08/25		smart initialization of new row in census		*
 *		2012/09/10		when the user selects an entry in the IDIR		*
 *						dialog for a census row, display a dialog that	*
 *						attempts matches to all of that person's family	*
 *						members.										*
 *		2012/10/23		flag fields with questionable values by			*
 *						displaying the text in red.						*
 *						fix bug in changeCantRead that it did not set	*
 *						the corresponding 'CantWrite' field				*
 *		2012/10/30		incorrect field names created for adding rows	*
 *						less than row 10.								*
 *		2012/10/31		move field value validation functions to		*
 *						jscripts/CommonForm.js							*
 *		2012/11/12		correct validation of 'NumFamilies' in 1861		*
 *		2013/02/25		avoid NaN birth year in search for match		*
 *						in family tree.									*
 *		2013/04/14		override default birth place in pre-confed		*
 *						censuses.										*
 *		2013/04/24		fill in defaults for Stories and NumFamilies	*
 *		2013/05/02		add range check on birth year column against age*
 *						set employer field to default 'N' for 			*
 *						self-employed.									*
 *		2013/05/07		popup loading indicator while waiting for		*
 *						matches to family tree.							*
 *						enable mouse-over help for all input elements	*
 *		2013/05/17		document.forms[0] is not the form enclosin4g	*
 *						the signon button								*
 *		2013/05/25		include date of birth in prompt for match		*
 *						restore onchange handler and keydown when		*
 *						restoring column								*
 *		2013/06/01		add 1916 census columns SpkEnglish and SpkFrench*
 *						to the list of flag columns.					*
 *						check for a list of foreign birthplaces and		*
 *						set default values for other columns			*
 *		2013/06/17		properly encode surname and given name for		*
 *						search for match in the family tree.			*
 *		2013/06/21		support defaults for CanRead/Write in 1901		*
 *						support Months fields in 1901					*
 *		2013/06/22		share initialization of new row with			*
 *						original initialization to ensure matches		*
 *						set onkeydown for fields in new row				*
 *		2013/07/02		add fields for 1906 census						*
 *		2013/07/06		validation of birth year against age			*
 *						accidentally removed in cleanup.				*
 *		2013/07/30		defer facebook initialization until after load  *
 *		2013/08/05		make reset button work							*
 *		2013/08/17		add support for 1921 census						*
 *		2013/08/21		use common function tableKeyDown				*
 *		2013/09/07		expand abbreviations for employment location	*
 *		2013/09/08		correct implementation for 'Township' field		*
 *						in the 1921 census								*
 *		2013/10/08		expand abbreviations in cause of death			*
 *		2013/10/17		only set place of employment to 'Farm' for		*
 *						'Farmer' in 1921 census.						*
 *		2013/11/01		use shared columnClick from CommonForm.js		*
 *		2013/11/09		add method checkNatYear and remove code from	*
 *						checkBYear that was to support double use		*
 *						enhance changeBYear to handle empty values		*
 *		2013/11/26		alert if gotIdir cannot find matching button	*
 *		2013/12/18		allow empty birth year field					*
 *		2014/03/24		only implement table key handling on input		*
 *						fields											*
 *						always invoke addRow function as a method of	*
 *						the addRow button								*
 *		2014/04/14		set class of image input field					*
 *		2014/04/24		support entering '+' to increment field value	*
 *						set flags to 'Y' if '1'							*
 *		2014/05/10		clear error flag for Stories and NumFamilies	*
 *						when set as a result of changing ResType		*
 *		2014/09/10		add 'Clear' button when IDIR resolved			*
 *		2014/10/03		only add IDIR Clear button if it is not already	*
 *						present											*
 *		2014/10/07		move IDIR dialog closer to Find button			*
 *		2014/10/11		getElementById returns null instead of undefin..*
 *		2014/10/15		change implementation of reset to defaults		*
 *						so it initializes from the last line of the		*
 *						previous page									*
 *		2014/12/16		to display image corresponding to current		*
 *						page of transcription, split the window			*
 *						horizontally instead of opening new window		*
 *		2015/01/13		on show Image remove button and replace with	*
 *						copyright notice.								*
 *						remove obsolete and unused addMsg function		*
 *		2015/01/20		include names of parents and spouses in matches	*
 *						for individual in census						*
 *		2015/04/20		use DisplayImage.php to show image				*
 *		2015/04/25		DisplayImage.php does not support Ancestry.com	*
 *		2015/05/06		Add functionality to OwnerRenter field			*
 *		2015/05/12		set 1851 and 1861 census attending school col	*
 *						to gender if possible							*
 *						initialize the 1851 and 1861 born in year col	*
 *						if age <= 1 year								*
 *		2015/05/26		use absolute URLs for AJAX requests				*
 *		2015/06/03		validate gender for relationship column			*
 *		2015/06/09		reset canRead and canWrite to 'Y'				*
 *		2015/06/15		revalidate Relation column if sex changes		*
 *		2015/07/08		in 1851 and 1861 census adjust the contents		*
 *						of the born this year column if the sex changes	*
 *						simplify activation of popups for hyper-links	*
 *						support right button click to widen column		*
 *						use CommonForm.js								*
 *		2015/07/27		for '+' entered in changeReplDown search for	*
 *						last numeric field value						*
 *		2015/08/14		set focus on <select> in IDIR selection dialog	*
 *						add workaround for bug in FF 40 and Chromium	*
 *		2016/02/24		add method changeDate to automatically separate	*
 *						the year and month if required					*
 *		2016/02/27		handle field 'MarDate' in 1861 census the same	*
 *						as field 'MInDate' in the other censuses		*
 *		2016/03/01		handling of date fields moved to CommonForm.js	*
 *		2016/09/31		permit blanking out birth year column			*
 *		2016/12/13		small change to handling of flag columns to		*
 *						ensure the value is always empty, 'N', or 'Y'	*
 *		2016/12/26		if the IDIR is set pass an indicator to			*
 *						CensusUpdate.php								*
 *		2017/06/12		add support for Show Important Button			*
 *		2017/08/16		script legacyIndivid.php renamed to Person.php	*
 *		2017/11/26		input class names changed to separate			*
 *          			background color			                	*	
 *		2018/01/14		use new class names for forms					*
 *		2018/02/09		use common method setErrorFlag to manipulate	*
 *						new class names for reporting errors			*
 *						pass language to other pages					*
 *		2018/04/19		correct splitting field names into col and row	*
 *		2018/10/30      use Node.textContent rather than getText        *
 *		2019/02/10      no longer need to call pageInit                 *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

// strings for determining and changing the case of letters
var	upper	= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
var	lower	= "abcdefghijklmnopqrstuvwxyz";

/************************************************************************
 *  CenPageSize															*
 *																		*
 *  Table for normal number of rows in full census page					*
 ************************************************************************/
var	CenPageSize = {
				"1851"	: 50,
				"1861"	: 50,
				"1871"	: 20,
				"1881"	: 25,
				"1891"	: 25,
				"1901"	: 50,
				"1906"	: 50,
				"1911"	: 50,
				"1916"	: 50,
				"1921"	: 50
				};

/************************************************************************
 *  ForeignBplaces														*
 *																		*
 *  A list of common foreign birthplaces								*
 *  This table is used to determine if a birthplace is outside Canada	*
 *  and therefore the year of immigration should be provided			*
 ************************************************************************/
var	ForeignBplaces = {
				'Africa'			: 'Africa',
				'African'			: 'Africa',
				'Australia'			: 'Australia',
				'Austria'			: 'Austria',
				'Belgium'			: 'Belgium',
				'Bermuda'			: 'Bermuda',
				'Borneo'			: 'Borneo',
				'C. C. Africa'		: 'C. C. Africa',
				'Cape Of Good Hope'	: 'Cape of Good Hope',
				'Channel Islands'	: 'Channel Islands',
				'China'				: 'China',
				'Corfu'				: 'Corfu',
				'Denmark'			: 'Denmark',
				'E. India'			: 'India',
				'East India'		: 'India',
				'East Indies'		: 'East Indies',
				'England'			: 'England',
				'Est India'			: 'East India',
				'France'			: 'France',
				'Germany'			: 'Germany',
				'Gibraltar'			: 'Gibraltar',
				'Glasgow, Scotland'	: 'Scotland',
				'Greece'			: 'Greece',
				'Guernsey'			: 'Guernsey',
				'Holland'			: 'Holland',
				'IA, US'			: 'U.States',
				'India'				: 'India',
				'India East'		: 'India East',
				'Indiana'			: 'U.States',
				'Ireland'			: 'Ireland',
				'Isle Guernsey'		: 'Guernsey',
				'Isle Of Jersey'	: 'Jersey',
				'Isle of Man'		: 'Isle of Man',
				'Italy'				: 'Italy',
				'Jersey'			: 'Jersey',
				'Jersey Island'		: 'Jersey',
				'Malta'				: 'Malta',
				'Mechlenburg'		: 'Mechlenburg',
				'Michigan'			: 'U.States',
				'Michigan, US'		: 'U.States',
				'Michigan, USA'		: 'U.States',
				'New York, USA'		: 'U.States',
				'New Zealand'		: 'New Zealand',
				'NJ, US'			: 'U.States',
				'North Wales'		: 'North Wales',
				'Norway'			: 'Norway',
				'NY, US'			: 'U.States',
				'OH, US'			: 'U.States',
				'Ohio, USA'			: 'U.States',
				'Poland'			: 'Poland',
				'Prussia'			: 'Prussia',
				'Russia'			: 'Russia',
				'Scotland'			: 'Scotland',
				'Scotland Isles'	: 'Scotland',
				'Spain'				: 'Spain',
				'Sweden'			: 'Sweden',
				'Switzerland'		: 'Switzerland',
				'Syria'				: 'Syria',
				'Trinidad'			: 'Trinidad',
				'U'				    : 'U.States',
				'U. States'			: 'U.States',
				'U.S.'			    : 'U.States',
				'U.S.A.'		    : 'U.States',
				'U.States'		    : 'U.States',
				'United States'		: 'U.States',
				'US'			    : 'U.States',
				'W. I.'			    : 'West Indies',
				'Wales'			    : 'Wales',
				'West Indies'		: 'West Indies'};

/************************************************************************
 *  EmpWhereAbbrs														*
 *																		*
 *  A table for expanding abbreviations for employment locations		*
 ************************************************************************/
var	EmpWhereAbbrs = {
					"And"		: "and",
					"At"		: "at",
					"By"		: "by",
					"F"		    : "Farm",
					"For"		: "for",
					"From"		: "from",
					"H"		    : "Home",
					"In"		: "in",
					"Of"		: "of",
					"On"		: "on",
					"Or"		: "or",
					"["	    	: "[blank]"
				};

/************************************************************************
 *  colNames2Blank														*
 *																		*
 *  A list of column names that are cleared when the surname is changed *
 *  to '[Blank]'.														*
 ************************************************************************/
var colNames2Blank  	= ["Family",
						   "Sex",
						   "Race",
						   "BPlace",
						   "BPlaceRu",
						   "Origin",
						   "Nationality",
						   "Religion"];

/************************************************************************
 *  bInYearMonth														*
 *																		*
 *  A list of month names that correspond to an age in months from		*
 *  the typical enumeration month of April.								*
 ************************************************************************/
var	bInYearMonth	= [ 'Apr',
						'Mar',
						'Feb',
						'Jan',
						'Dec',
						'Nov',
						'Oct',
						'Sep',
						'Aug',
						'Jul',
						'Jun',
						'May',
						'Apr'];

/************************************************************************
 *  RelationGender														*
 *																		*
 *  Table for determining the expected sex value for a relationship.	*
 ************************************************************************/
var	RelationGender = {"aunt"				: "F",
					  "adopted-daughter"	: "F",
					  "adopted-son"			: "M",
					  "brother-in-law"		: "M",
					  "brother"			    : "M",
					  "daughter"			: "F",
					  "daughter-in-law"		: "F",
					  "father"			    : "M",
					  "father-in-law"		: "M",
					  "grand-daughter"		: "F",
					  "grand-father"		: "M",
					  "grand-mother"		: "F",
					  "grand-son"			: "M",
					  "husband"			    : "M",
					  "mother"			    : "F",
					  "mother-in-law"		: "F",
					  "nephew"			    : "M",
					  "niece"				: "F",
					  "son"				    : "M",
					  "step-daughter"		: "F",
					  "sister"			    : "F",
					  "sister-in-law"		: "F",
					  "son-in-law"			: "M",
					  "step-son"			: "M",
					  "uncle"				: "M",
					  "wife"				: "F"
				};

/************************************************************************
 *  Invoke the function onLoad when the page has been completely loaded	*
 ************************************************************************/
window.onload	= onLoad;

/************************************************************************
 *  Count the number of "can read" fields that are set to determine if	*
 *  the fields should be initialized to their default values			*
 ************************************************************************/
var	numCanRead	= 0;
var	numCanReadFlds	= 0;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization after the web page has been loaded.			*
 *																		*
 *  Input:																*
 *		this			Window object									*
 ************************************************************************/
function onLoad()
{
    document.body.onresize	= onWindowResize;

    // activate functionality for individual input elements
    for(var i = 0; i < document.forms.length; i++)
    {			// loop through all forms
		var form	= document.forms[i];
		for(var j = 0; j < form.elements.length; j++)
		{
		    initElement(form.elements[j], false);
		}		// loop through all form elements

		// fill in some default values
		if (numCanReadFlds > 0 && numCanRead == 0)
		{		// 1891 census with no readers
		    for(var icr = 1; icr <= 50; icr++)
		    {		// loop through up to 50 rows
				var cr;		// CanRead element
				var cw;		// CanWrite element
				var age;	// Age
				if (icr < 10)
				{
				    cr	= form.elements["CanRead0" + icr];
				    cw	= form.elements["CanWrite0" + icr];
				    age	= form.elements["Age0" + icr];
				}
				else
				{
				    cr	= form.elements["CanRead" + icr];
				    cw	= form.elements["CanWrite" + icr];
				    age	= form.elements["Age" + icr];
				}
				if (cr && age && (age.value - 0) > 5)
				{	// have a CanRead field
				    cr.value		= 'Y';
				    if (cw)
						cw.value	= 'Y';
				}	// have a CanRead field
		    }		// loop through 25 rows
		}		// 1891 or 1901 census with no readers
    }			// loop through all forms

    // add mouseover actions for forward and backward links
    for (var il = 0; il < document.links.length; il++)
    {			// loop through all hyper-links
		var	linkTag		        = document.links[il];
		linkTag.onmouseover	    = linkMouseOver;
		linkTag.onmouseout	    = linkMouseOut;
    }			// loop through all hyper-links

    // enable support for hiding and revealing columns within a table
    var dataTbl		= document.getElementById("form");
    if (dataTbl)
    {
		var tblHdr		        = dataTbl.tHead;
		var tblHdrRow	        = tblHdr.rows[0];
		for(i = 0; i < tblHdrRow.cells.length; i++)
		{			// loop through cells of header row
		    var th			    = tblHdrRow.cells[i];
		    th.onclick		    = columnClick;
		    th.oncontextmenu	= columnWiden;
		}			// loop through cells of header row
    }
}		// onLoad

/************************************************************************
 *  onWindowResize														*
 *																		*
 *  This method is called when the browser window size is changed		*
 *  If the window is split between the main display and a second		*
 *  display, resize.													*
 *																		*
 *  Input:																*
 *		this	<body> element											*
 ************************************************************************/
function onWindowResize()
{
    if (iframe)
		openFrame(iframe.name, null, "right");
}		// onWindowResize

/************************************************************************
 *  getOffsetLeft														*
 *																		*
 *  Get the offsetLeft of an HTML element relative to the page.			*
 *																		*
 *  Input:																*
 *		elt		an element from an HTML form							*
 ************************************************************************/
function getOffsetLeft(elt)
{
    var	left	= 0;
    while(elt)
    {
		left	+= elt.offsetLeft;
		elt	= elt.offsetParent;
    }		// increment up to top element
    return left;
}	// getOffsetLeft

/************************************************************************
 *  getOffsetTop														*
 *																		*
 *  Get the offsetTop of an HTML element relative to the page.			*
 *																		*
 *  Input:																*
 *		elt		an element from an HTML form							*
 ************************************************************************/
function getOffsetTop(elt)
{
    // note that "top" is a reserved word
    var	y	= 0;
    while(elt)
    {
		y	+= elt.offsetTop;
		elt	= elt.offsetParent;
    }		// increment up to top element
    return y;
}	// getOffsetTop

/************************************************************************
 *  setClassByValue														*
 *																		*
 *  Set the class name for the indicated cell of the spreadsheet		*
 *  depending upon its value.  If the value is equal to the value of	*
 *  the same cell in the previous row of the spreadsheet, then the class*
 *  is set to indicate that the cell has inherited its value from the	*
 *  previous row.  														*
 *																		*
 *  Input:																*
 *		colName			the name of the column in the spreadsheet		*
 *		rowNum			the row number within the spreadsheet			*
 *		formElts		the associative array of form elements			*
 ************************************************************************/
function setClassByValue(colName,
						 rowNum,
						 formElts)
{
    if (rowNum > 1)
    {	// not first row of table
		var prevNum	= rowNum - 1;
		if (prevNum < 10)
		    prevNum	= '0' + prevNum;
		if (rowNum < 10)
		    rowNum	= '0' + (rowNum - 0);
		var field	= formElts[colName + rowNum];
		var prevField	= formElts[colName + prevNum];

		if (prevField && field.value == prevField.value)
		{	// change the presentation of this field
		    if (field.className.substring(0,12) == "black white")
		    {
				field.className = "same white " . field.className.substring(12);
		    }
		}	// change the presentation of this field
    }	// not first row of table
}	// setClassByValue

/************************************************************************
 *  changeReplDown														*
 *																		*
 *  Take action when the user changes a field whose value is			*
 *  replicated into subsequent fields in the same column whose			*
 *  value has not yet been explicitly set.								*
 *																		*
 *  Input:																*
 *		this	instance of <input type='text'>							*
 ************************************************************************/
function changeReplDown()
{
    var	form		= this.form;
    var	name		= this.name;
    if (this.id)
		name		= this.id;

    if (this.abbrTbl)
		expAbbr(this, this.abbrTbl);

    // shortcut for next incremental value
    if (this.value == '+')
    {				// get next incremental value
		var	result	= /\d+$/.exec(name);
		if (result)
		{			// got row number
		    var	rowNum		= result[0];
		    var rowNumLen	= rowNum.length;
		    var	columnName	= name.substring(0, name.length - rowNumLen);
		    var	prevElement	= null;
		    while(this.value == '+')
		    {			// find last non-empty field
				rowNum		= rowNum - 1;
				if (rowNum >= 10)
				{		// 2 digit row number
				    prevElement	= form.elements[columnName + rowNum];
				    if (/\d+/.exec(prevElement.value))
				    {
						this.value	= prevElement.value - 0 + 1;
						break;
				    }
				}		// 2 digit row number
				else
				if (rowNum > 0)
				{		// 1 digit row number
				    prevElement	= form.elements[columnName + '0' + rowNum];
				    if (/\d+/.exec(prevElement.value))
				    {
						this.value	= prevElement.value - 0 + 1;
						break;
				    }
				}		// 1 digit row number
				else
				{		// not found on this page
				    // take value from last element on previous page
				    this.value	= this.defaultValue - 0 + 1;
				    break;
				}		// not found on this page
		    }			// find last non-empty field
		}			// got row number
		else			// take value from last element on previous page
		    this.value	= this.defaultValue + 1;
    }				// get next incremental value

    // replicate the value into subsequent rows
    if (this.value.length > 0)
		replDown(this);

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeReplDown

/************************************************************************
 *  changeSex															*
 *																		*
 *  Take action when the user changes the Sex field.					*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this	an <input type='text'> element							*
 ************************************************************************/
function changeSex()
{
    changeElt(this);	// fold value to upper case if required
    var row	= this.name.substring(3);
    var	form		= this.form;

    // if the form includes a relation to head of household column
    var relation	= form.elements['Relation' + row];
    if (relation)
		relation.checkfunc();	// revalidate relation

    // for the 1851 and 1861 censuses if the form includes a born this year
    var	birthElt	= form.elements['Birth' + row];
    if (birthElt && birthElt.value == '?')
		birthElt.value	= this.value;

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeSex

/************************************************************************
 *  changeCanRead														*
 *																		*
 *  Take action when the user changes the CanRead field.				*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this	an <input type='text'> element							*
 ************************************************************************/
function changeCanRead()
{
    changeElt(this);	// fold value to upper case if required
    if (this.value == '1')
		this.value	= 'Y';
    else
    if (this.value == '0')
		this.value	= 'N';
    var	form	= this.form;
    form.elements['CanWrite' + this.name.substring(7)].value	= this.value;

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeCanRead

/************************************************************************
 *  changeCantRead														*
 *																		*
 *  Take action when the user changes the CantRead field.				*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this	an <input type='text'> element							*
 ************************************************************************/
function changeCantRead()
{
    changeElt(this);	// fold value to upper case if required
    if (this.value.length > 0)
    {
		if (this.value == '1')
		    this.value	= 'Y';
		else
		if (this.value == '0')
		    this.value	= 'N';
		var	form		= this.form;
		var	name		= 'CantWrite' + this.name.substring(8);
		var	writeElt	= form.elements[name];
		writeElt.value		= this.value;
		if (writeElt.checkfunc)
		    writeElt.checkfunc();
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeCantRead

/************************************************************************
 *  changeAddress														*
 *																		*
 *  Take action when the user changes the Address or Location field.	*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this	an <input type='text'> element							*
 ************************************************************************/
function changeAddress()
{
    changeElt(this);	// perform common processing
    var	name	= this.name;
    var	value	= this.value;
    var	row	= name.substring(7);
    if (name.substring(0,8) == 'Location')
		row	= name.substring(8);
    //alert("CensusForm.js: changeAddress: row='" + row +
    //			"', value='" + value + "'");
    var	form	= this.form;
    var	field	= form.elements['OwnerTenant' + row];
    if (field)
    {			// ResType field present in form
		if (value.length > 0)
		    field.value	= 'O';
		else
		    field.value	= '';
		field.onchange();
    }			// ResType field present in form

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeAddress

/************************************************************************
 *  changeResType														*
 *																		*
 *  Take action when the user changes the residence type field.			*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeResType()
{
    changeElt(this);	// perform common processing
    var	form	= this.form;
    var	value	= this.value;
    var	field;
    field	= form.elements['Stories' + this.name.substring(7)];
    if (field)
    {			// stories field present
		if (value.length > 0)
		    field.value	= 1;
		else
		    field.value	= '';
		if (field.checkfunc)
		    field.checkfunc();
    }			// stories field present
    field	= form.elements['NumFamilies' + this.name.substring(7)];
    if (field)
    {			// number of families field present
		if (value.length > 0)
		    field.value	= 1;
		else
		    field.value	= '';
		if (field.checkfunc)
		    field.checkfunc();
    }			// number of families field present
    field	= form.elements['NoFamilies' + this.name.substring(7)];
    if (field)
    {			// number of families field present
		if (value.length > 0)
		    field.value	= 1;
		else
		    field.value	= '';
		if (field.checkfunc)
		    field.checkfunc();
    }			// number of families field present

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeResType

/************************************************************************
 *  changeOwnerTenant														*
 *																		*
 *  Take action when the user changes the residence type field.				*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeOwnerTenant()
{
    changeElt(this);	// fold value to upper case if required
    var	form	= this.form;
    var	value	= this.value;
    var	field;
    var rownum	= this.name.substring(11);
    if (this.value.length > 0)
    {			// owner tenant set
		field	= form.elements['Address' + rownum];
		if (field && field.value == '')
		{
		    field.value	= '[blank]';
		    field.checkfunc();
		}
		field	= form.elements['HouseRent' + rownum];
		if (field && field.value == '' && value == 'R')
		{
		    field.value	= '[blank]';
		    field.checkfunc();
		}
		field	= form.elements['HouseClass' + rownum];
		if (field && field.value == '')
		    field.value	= 'S';
		field	= form.elements['HouseMaterial' + rownum];
		if (field && field.value == '')
		    field.value	= 'Wood';
		field	= form.elements['HouseRooms' + rownum];
		if (field && field.value == '')
		    field.value	= '6';
    }			// owner tenant set
    else
    {			// owner tenant cleared
		field	= form.elements['HouseClass' + rownum];
		if (field)
		    field.value	= '';
		field	= form.elements['HouseMaterial' + rownum];
		if (field)
		    field.value	= '';
		field	= form.elements['HouseRooms' + rownum];
		if (field)
		    field.value	= '';
    }			// owner tenant cleared

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeOwnerTenant

/************************************************************************
 *  changeSurname														*
 *																		*
 *  Take action when the user changes a field whose value is				*
 *  replicated into subsequent fields in the same column whose				*
 *  value has not yet been explicitly set.								*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeSurname()
{
    changeElt(this);

    // special action if value is blanked out
    if ((this.value.length == 0) ||
		this.value.substring(0, 1) == "[")
    {		// surname blanked out
		var td	= this.parentNode;
		var col	= td.cellIndex;
		var tr	= td.parentNode;
		var row	= tr.rowIndex;	// row index of current row
		for (var i = 0; i < tr.cells.length; i++)
		{
		    var cell	= tr.cells[i];
		    if (i != col)
		    {		// not surname cell
				var field	= cell.firstChild;

				// the first child may not be the desired input element
				// for example if there is some text at beginning of cell
				while(field && field.nodeType != 1)
				    field	= field.nextSibling;
				if (field && field.value)
				    field.value	= "";
		    }		// cell exists in this row
		}	// for each column name to blank
    }		// surname blanked out
    replDown(this);

    this.checkfunc();
}		// changeSurname

/************************************************************************
 *  changeOccupation														*
 *																		*
 *  Take action when the user changes the Occupation field.				*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeOccupation()
{
    changeElt(this);	// espand abbreviations and fold value to upper case
    var	occupation	= this.value;
    var	form		= this.form;
    var	censusId	= form.Census.value;
    var	censusYear	= censusId.substring(censusId.length - 4);
    var lineNum		= this.name.substring(10);
    var whereElement	= form.elements['EmpWhere' + lineNum];
    var eeElement	= form.elements['Employee' + lineNum];
    var oaElement	= form.elements['OwnAcct' + lineNum];

    // fill in default values in other columns
    if (oaElement &&
		occupation == 'Farmer')
    {
		oaElement.value		= 'Y';
		oaElement.onchange();
    }

    if (eeElement &&
		(occupation == 'Farm Laborer' ||
		 occupation == 'Laborer'))
    {
		eeElement.value		= 'Y';
		eeElement.onchange();
    }

    if (whereElement &&
		((occupation == 'Farmer' && censusYear > 1911) ||
		 occupation == 'Farm Laborer'))
		whereElement.value	= "Farm";

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeOccupation

/************************************************************************
 *  changeEmpType														*
 *																		*
 *  Take action when the user changes the EmpType field.				*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeEmpType()
{
    changeElt(this);	// fold value to upper case if required
    var	form		= this.form;
    var	empType		= this.value;
    var occElement	= form.elements['Occupation' + this.name.substring(7)];
    var whereElement	= form.elements['EmpWhere' + this.name.substring(7)];
    var	occupation	= occElement.value;
    if (whereElement && empType == 'O' && occupation == 'Farmer')
		whereElement.value	= "Own Farm";

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeEmpType

/************************************************************************
 *  changeGenderFlag														*
 *																		*
 *  Take action when the user changes the value of a flag column		*
 *  that displays a gender indicator.										*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeGenderFlag()
{
    changeElt(this);	// perform common functions
    var	form		= this.form;
    var	value		= this.value;
    var sexElement	= form.elements['Sex' + this.name.substring(6)];
    if ((value == '1' || value == 'Y') && sexElement)
    {			// sex column present
		var	sex	= sexElement.value;
		if (sex == 'M' || sex == 'F')
		    this.value	= sex;
    }			// sex column present

    // validate value
    if (this.checkfunc)
		this.checkfunc();
}		// changeGenderFlag

/************************************************************************
 *  changeSchoolMons														*
 *																		*
 *  Take action when the user changes the SchoolMons field.				*
 *  This is the onchange member function of the element.				*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeSchoolMons()
{
    changeElt(this);	// perform common functions
    var	form		= this.form;
    var	schoolMons	= this.value;
    var occElement	= form.elements['Occupation' + this.name.substring(10)];
    if (occElement)
    {			// occupation column present
		if (schoolMons.length > 0)
		    occElement.value	= 'Student';
		else
		    occElement.value	= '';
    }			// occupation column present

    // validate number of months
    if (this.checkfunc)
		this.checkfunc();
}		// changeSchoolMons

/************************************************************************
 *  changeFlag																*
 *																		*
 *  Take action when the user changes a field that is a yes/no flag.		*
 *																		*
 *  Input:																*
 *		this				an instance of an HTML input element. 				*
 ************************************************************************/
function changeFlag()
{
    changeElt(this);
    if (this.value == '' ||
		this.value == ' ')
		this.value	= ' ';
    else
    if (this.value == '0')
		this.value	= 'N';
    else
    if (this.value.toUpperCase() != 'N')
		this.value	= 'Y';

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeFlag

/************************************************************************
 *  changeFlagRace														*
 *																		*
 *  Take action when the user changes a field that is a yes/no flag		*
 *  or a race indicator.
 *																		*
 *  Input:																*
 *		this				an instance of an HTML input element. 				*
 ************************************************************************/
function changeFlagRace()
{
    changeElt(this);
    this.value		= this.value.toUpperCase();
    if (this.value == '0')
		this.value	= 'N';
    else
    if (this.value == '1')
		this.value	= 'Y';

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeFlagRace

/************************************************************************
 *  replDown																*
 *																		*
 *  Replicate the value of the current element into 						*
 *  subsequent elements in the current column whose						*
 *  value has not yet been explicitly set.								*
 *																		*
 *  Input:																*
 *		curr		current field in the spreadsheet						*
 ************************************************************************/
function replDown(curr)
{
    // change the presentation of the current field
    if (curr.className.substr(0,3) == "dft")
    {	// value has been modified
		curr.className = "black white " + curr.className.substr(3);
    }	// value has been modified
    else
    if (curr.className.substr(0,5) == 'same ')
    {	// value has been modified
		curr.className = "black white " + curr.className.substr(5);
    }	// value has been modified

    // update the presented values of curr field in subsequent rows
    var	cell		= curr.parentNode;
    if (cell.nodeName != "TD")
		throw new Error("CensusForm.js: replDown: curr is child of <" +
						cell.nodeName + ">");
    var	column		= cell.cellIndex;
    var	row		= cell.parentNode;
    if (row.nodeName != "TR")
		throw new Error("CensusForm.js: replDown: cell is child of <" +
						row.nodeName + ">");
    var rowNum		= row.sectionRowIndex;
    var	tbody		= row.parentNode;
    if (tbody.nodeName != "TBODY")
		throw new Error("CensusForm.js: replDown: row is child of <" +
						tbody.nodeName + ">");
    var	newValue	= curr.value;
    var blankrow	= newValue.toLowerCase() == '[delete]';

    for (rowNum++; rowNum < tbody.rows.length; rowNum++)
    {
		row		= tbody.rows[rowNum];
		cell		= row.cells[column];
		// field is first element under cell
		var field	= cell.firstChild;
		while(field && field.nodeType != 1)
		    field	= field.nextSibling;

		if (field === undefined)
		throw new Error("CensusForm.js: replDown: row.cells[" +
						column + "] is undefined");
		if (field.className.substr(0,4) == 'same' || 
		    field.className.substr(0,3) == 'dft')
		{	// alter value to match modified field
		    field.value	= curr.value;
		    if (field.checkfunc)
				field.checkfunc();
		    if (blankrow)
		    {		// blank out other cells
				for (var i = 0; i < row.cells.length; i++)
				{
				    var cell	= row.cells[i];
				    if (i != column)
				    {		// not surname cell
						var fld	= cell.firstChild;

						// the first child may not be the desired input element
						// for example if there is some text at start of cell
						while(fld && fld.nodeType != 1)
						    fld	= fld.nextSibling;
						if (fld && fld.value)
						    fld.value	= "";
				    }		// cell exists in this row
				}	// for each column name to blank
		    }		// blank out other cells
		}	// alter value to match modified fld
		else
		    break;	// stop replicating value on first explicit cell
    }		// loop to end of page

}		// changeReplDown

/************************************************************************
 *  changeFBPlace														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  Father's birth place field in the 1891 census.  If the Mother's		*
 *  birth place has not been explicitly set, change its default value		*
 *  to the Father's birthplace.												*
 *																		*
 *  Input:																*
 *		this		an <input type='text'> element								*
 ************************************************************************/
function changeFBPlace()
{
    // expand abbreviation
    changeElt(this);

    // the default for Mother's birth place is to be the same as the
    // Father's birth place.  If the Mother's birth place has not been
    // given an explicit value, make it match.
    var colName	= "MothersBPlace" + this.name.substring(this.name.length - 2);
    var field	= document.censusForm.elements[colName];
    if (field.className.substr(0,5) == 'same ' ||
		field.className.substr(0,3) == 'dft')
    {	// alter value to match modified field
		field.value	= this.value;
    }	// alter value to match modified field

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeFBPlace

/************************************************************************
 *  changeBPlace														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  birth place.														*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth place		*
 ************************************************************************/
function changeBPlace()
{
    // expand abbreviation
    changeElt(this);

    var	form	= this.form;
    var fldName;
    var	element;

    if (this.value == 'Canada West' ||
		this.value == 'Upper Canada')
    {		// for pre-confederation censuses change default birth place

		for (var ir = (this.name.substring(6) - 0 + 1);
		     ir < 51;
		     ir++)
		{	// go through remainder of column
		    fldName	= 'BPlace' + ir;
		    if (ir < 10)
				fldName	= 'BPlace0' + ir;
		    element	= form.elements[fldName];
		    if (element)
		    {
		    if (element.value == 'Ontario' || element.value == 'Canada West')
				element.value	= this.value;
		    }
		    else
				alert("CensusForm.js: changeBPlace: " +
				      "cannot find form.elements['" + fldName + "']");
		}	// go through remainder of column
    }		// for pre-confederation censuses change default birth place

    // check for foreign birthplace
    var	foreignBplace	= ForeignBplaces[this.value];
    if (foreignBplace)
    {
		var	row	= this.name.substring(6);
		fldName		= 'ImmYear' + row;
		element		= form.elements[fldName];
		if (element && element.value.length == 0)
		{
		    element.value	= '[Blank';
		    element.checkfunc();	// validate to turn on highlighting
		}

		fldName		= 'FathersBPlace' + row;
		element		= form.elements[fldName];
		if (element && element.value == 'Ontario')
		    element.value	= foreignBplace;

		fldName		= 'MothersBPlace' + row;
		element		= form.elements[fldName];
		if (element && element.value == 'Ontario')
		    element.value	= foreignBplace;
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeMBPlace

/************************************************************************
 *  changeMBPlace														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  Mother's birth place.												*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth place		*
 ************************************************************************/
function changeMBPlace()
{
    // expand abbreviation
    changeElt(this);

    // change the presentation of this field
    if (this.className == "same white left")
    {
		this.className = "black white left";
    }
    else
    if (this.className == "same white right")
    {
		this.className = "black white right";
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeMBPlace

/************************************************************************
 *  changeEmployee														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  Employee field.														*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth place		*
 ************************************************************************/
function changeEmployee()
{
    var	form	= this.form;
    var	row	= this.name.substring(this.name.length - 2);
    if (this.value == '1')
		this.value	= 'Y';
    if (this.value.toUpperCase() == 'Y')
    {
		if (form.elements["Employer" + row])
		    form.elements["Employer" + row].value	= "N";
		if (form.elements["OwnAcct" + row])
		    form.elements["OwnAcct" + row].value	= "N";
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeEmployee

/************************************************************************
 *  changeEmployer														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  OwnAcct field.														*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a flag				*
 ************************************************************************/
function changeEmployer()
{
    var	form	= this.form;
    var	row	= this.name.substring(this.name.length - 2);
    if (this.value == '1')
		this.value	= 'Y';
    if (this.value.toUpperCase() == 'Y')
    {
		if (form.elements["Employee" + row])
		    form.elements["Employee" + row].value	= "N";
		if (form.elements["OwnAct" + row] &&
		    form.elements["OwnAct" + row].value == "")
		    form.elements["OwnAct" + row].value	= "N";
		if (form.elements["NumHands" + row])
		{		// number of hands field present
		    var	numHands	= form.elements["NumHands" + row];
		    if (numHands.value == "")
		    {		// set default
				numHands.value	= "0";
				numHands.checkfunc();
		    }		// set default
		}		// number of hands field present
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeEmployer

/************************************************************************
 *  changeSelfEmployed														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  OwnAcct field.														*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a flag				*
 ************************************************************************/
function changeSelfEmployed()
{
    var	form	= this.form;
    var	row	= this.name.substring(this.name.length - 2);
    if (this.value == '1')
		this.value	= 'Y';
    if (this.value.toUpperCase() == 'Y')
    {
		if (form.elements["Employee" + row])
		    form.elements["Employee" + row].value	= "N";
		if (form.elements["Employer" + row] &&
		    form.elements["Employer" + row].value == "")
		    form.elements["Employer" + row].value	= "N";
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeSelfEmployed

/************************************************************************
 *  changeImmYear														*
 *																		*
 *  Take action when the user changes the value of the						*
 *  year of immigration.												*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth place		*
 ************************************************************************/
function changeImmYear()
{
    var	form		= this.form;
    var	censusId	= form.Census.value;
    var	censusYear	= censusId.substring(censusId.length - 4);
    var	immyear		= this.value;
    if (this.value == '[')
    {
		this.value	= '[Blank';
    }
    var	res	= immyear.match(/^[0-9]{4}$/);
    if (!res)
    {		// not a 4 digit number
		res	= immyear.match(/^[0-9]{2}$/);
		if (res)
		{	// 2 digit number
		    // expand to a 4 digit number which is a year in the
		    // century up to and including the census year
		    immyear		= (res[0] - 0) + 1900;
		    while (immyear > censusYear)
				immyear		-= 100;
		    this.value		= immyear;
		}	// 2 digit number
    }		// not a 4 digit number

    this.checkfunc();
}		// changeImmYear

/************************************************************************
 *  changeBYear																*
 *																		*
 *  Take action when the user changes the value of an						*
 *  explicit birth year field.												*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth year		*
 ************************************************************************/
function changeBYear()
{
    var	form		= this.form;
    var	censusId	= form.Census.value;
    var	censusYear	= censusId.substring(censusId.length - 4);
    var	byear		= this.value;
    var	row		= this.name.substring(this.name.length - 2);
    var	bDateElt	= form.elements['BDate' + row];
    var	ageElt		= form.elements['Age' + row];

    // validate the birth year value
    var	res		= byear.match(/^[0-9]{4}$/);
    if (res)
    {		// contains a 4 digit number
		byear		= res[0];
    }		// contains a 4 digit number
    else
    {		// not a 4 digit number
		res		= byear.match(/^[0-9]{2}$/);
		if (res)
		{	// 2 digit number
		    // expand to a 4 digit number which is a year in the
		    // century up to and including the census year
		    byear		= (res[0] - 0) + 1900;
		    while (byear > censusYear)
				byear		-= 100;
		    this.value		= byear;
		}	// 2 digit number
		else
		{	// not a 2 digit number
		    if (ageElt)
		    {
				var age		= ageElt.value;
				if (byear.length == 0)
				{
				    res		= age.match(/^\d+$/);
				    if (res && age > 0)
				    {
						byear	= (censusYear - 1) - (age - 0);
				    }
				}
				else
				    byear	= censusYear - 1;
		    }
		}	// not a 2 digit number
    }		// not a 4 digit number

    // update birth date field if current value should be adjusted for new year
    var	offset		= 1;	// birth date probably in previous year
    if (bDateElt)
    {		// birth date field present
		if (bDateElt.value.length == 0)
		{	// overwrite bDate value if user has not set it
		    bDateElt.value	= '[Blank]';
		}	// overwrite bDate value if user has not set it
		else
		{	// birth date value present
		    var bdate	= bDateElt.value.toLowerCase();
		    if ((bdate.indexOf('jan') != -1) ||
				(bdate.indexOf('feb') != -1) ||
				(bdate.indexOf('mar') != -1) ||
				(bdate.indexOf('apr') != -1))
		    {	// birth date in current year
				offset	= 0;
		    }	// birth date in current year
		}	// birth date value present
    }		// birth date field present

    // update Age field if not yet set
    if (ageElt)
    {		// form has an Age field
		// do not overwrite age value if user has already entered it
		if (ageElt.value.length == 0 || ageElt.value == 0)
		{
		    if (byear != '')
				ageElt.value	= censusYear - byear - offset;
		}
		else
		if (byear == censusYear - 1)
		    byear		= censusYear - offset - ageElt.value;
    }		// form has an Age field

    byear	= byear.toString();
    if (this.value.length == 0 && byear.length > 0)
    {
		this.value		= "[" + byear + "]";
    }

    this.checkfunc();
}		// changeBYear

/************************************************************************
 *  changeAge																*
 *																		*
 *  Take action when the user changes the value of an						*
 *  age field.  Expand abbreviations and set default value for				*
 *  birth year and birth date fields.										*
 *																		*
 *  Input:																*
 *		this		instance of <input type='text' name='Age...'>				*
 ************************************************************************/
function changeAge()
{
    var	form		= this.form;
    var	row		= this.name.substring(this.name.length - 2);
    var bInYear		= form.elements['BInYear' + row];

    // common functionality to expand abbreviations
    changeElt(this);

    // interpret the age value
    var	age		= this.value;
    var	ageInYears	= 0;
    var	res		= age.match(/^[0-9]+$/);
    if (res)
    {			// an integer
		ageInYears	= age - 0;
    }			// an integer
    else
    if (bInYear && (res = age.match(/^m?(\d+)m?$/)))
    {			// age in months
		var ageInMonths	= res[1] - 0;
		if (ageInMonths <= 12 && bInYear.value.length == 0)
		    bInYear.value	= bInYearMonth[ageInMonths];
    }			// age in months

    // update Birth Year text field if not yet set
    var	bYearElt	= form.elements['BYearTxt' + row];
    if (bYearElt)
    {			// birth year field present in form
		var	bYear		= bYearElt.value;
		if (bYear.length == 0 || bYear.substring(0,1) == '[')
		{		// numeric birth year not supplied
		    var	censusYear	= form.Census.value
		    censusYear	= censusYear.substring(form.Census.value.length - 4);

		    bYearElt.value	= "[" + (censusYear - 1 - ageInYears) + "]";
		}		// numeric birth year not supplied

		// revalidate the explicit year of birth field in order to
		// turn off error highlighting
		bYearElt.checkfunc();
    }			// birth year field present in form

    // check for born in year column in 1851 and 1861 census
    var	birthElt	= form.elements['Birth' + row];
    var	sexElt		= form.elements['Sex' + row];
    if (birthElt && ageInYears <= 1)
    {			// set default value for Birth column
		if (sexElt && sexElt.value.length > 0)
		    birthElt.value	= sexElt.value;
		else
		    birthElt.value	= 'Y';
    }			// set default value for Birth column

    // set default value for BDate field if not already set
    var	bDateElt	= form.elements['BDate' + row];
    if (bDateElt)
    {		// form has a BDate field
		// do not overwrite bDate value if user has already entered it
		if (bDateElt.value.length == 0)
		    bDateElt.value	= '[Blank]';
    }		// form has a BDate field

    // set default value for CanRead field if not already set
    var	canReadElt	= form.elements['CanRead' + row];
    if (canReadElt)
    {		// form has a CanRead field
		if (ageInYears > 4 && canReadElt.value.length == 0)
		    canReadElt.value	= '1';
		if (ageInYears < 5 && canReadElt.value == '1')
		    canReadElt.value	= '';
		if (ageInYears < 5 && canReadElt.value == 'Y')
		    canReadElt.value	= 'N';
    }		// form has a CanRead field

    // set default value for CanWrite field if not already set
    var	canWriteElt	= form.elements['CanWrite' + row];
    if (canWriteElt)
    {		// form has a CanWrite field
		if (ageInYears > 4 && canWriteElt.value.length == 0)
		    canWriteElt.value	= '1';
		if (ageInYears < 5 && canWriteElt.value == '1')
		    canWriteElt.value	= '';
		if (ageInYears < 5 && canWriteElt.value == 'Y')
		    canWriteElt.value	= 'N';
    }		// form has a CanWrite field

    // validate this field to set highlighting
    this.checkfunc();
}		// changeAge

/************************************************************************
 *  changeDefault														*
 *																		*
 *  Take action when the user changes a field whose value				*
 *  may be a default.  If it is, change the presentation of				*
 *  the field.																*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth place		*
 ************************************************************************/
function changeDefault()
{
    // common functionality
    changeElt(this);

    // change the presentation of this field
    if (this.className == "same white left")
    {
		this.className = "black white left";
    }
    else
    if (this.className == "same white right")
    {
		this.className = "black white right";
    }

    // validate the contents of the field
    if (this.checkfunc)
		this.checkfunc();
}		// changeDefault

/************************************************************************
 *  getRangeObject														*
 *																		*
 *  Get an object compatible with the W3C Range interface.				*
 *																		*
 *  Input:																*
 *		selectionObject		a Selection or TextRange object						*
 ************************************************************************/
function getRangeObject(selectionObject)
{
    if (selectionObject.getRangeAt)
		return selectionObject.getRangeAt(0);
    else
    {		// Safari 1.3
		var range = document.createRange();
		range.setStart(selectionObject.anchorNode,selectionObject.anchorOffset);
		range.setEnd(selectionObject.focusNode,selectionObject.focusOffset);
		return range;
    }
}

/************************************************************************
 *  checkRange																*
 *																		*
 *  On a keystroke check the selected range of the document.				*
 *  Under construction.														*
 *																		*
 *  Input:																*
 *		fNode		the node which currently has the focus						*
 ************************************************************************/
function checkRange(fNode)
{
    var userSelection;
    var	rangeObject;
		var attrs	= "";
    if (window.getSelection)
    {		// W3C compliant
		// this is a Selection object
		userSelection	= window.getSelection();
		for(var attr in userSelection)
		    if (userSelection[attr] instanceof HTMLTableCellElement)
				attrs += attr + "=" + tagToString(userSelection[attr]) + ", ";
		    else
		    if (typeof userSelection[attr] != "function")
				attrs += attr + "=" + userSelection[attr] + ", ";
		    alert("CensusForm.js: checkRange: typeof userSelection:\t" +
				Object.prototype.toString.apply(userSelection) +
		  "\n\t" + attrs);
    }		// W3C compliant
    else
    if (document.selection)
    {		// IE
		// this is an IE TextRange object
		userSelection	= document.selection.createRange();
		for(var attr in userSelection)
		    if (typeof userSelection[attr] != "function")
				attrs += attr + "=" + userSelection[attr] + ", ";
		alert("CensusForm.js: checkRange: typeof userSelection:\t" +
				Object.prototype.toString.apply(userSelection) +
				"\n\t" + attrs);
    }		// IE

}		// checkRange

/************************************************************************
 *  checkBYear																*
 *																		*
 *  Validate the current value of a field containing a birth year.		*
 *  Should be 4 digit numeric year, possibly enclosed in editorial		*
 *  square brackets, a question mark, or [blank] and not out of range		*
 *  of the age column.														*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a birth year		*
 ************************************************************************/
function checkBYear()
{
    var	elt		= this;
    var	byearTxt	= elt.value;
    var	form		= elt.form;
    var ageName		= 'Age' + elt.name.substring(8);
    var age		= form.elements[ageName].value;
    var censusYear	= form.Census.value.substring(2) - 0;

    // pre-confederation censuses report age at next birthday,
    // post-confederation censuses report age at time of enumeration
    if (censusYear > 1867)
		censusYear--;

    // get age in years
    if (/^\d+$/.test(age))
		age		= age - 0;
    else
		age		= 0;

    // calculate difference between expected age and actual age
    var	range		= 0;
    var	re		= /^(\[?([0-9]{4})\]?|\[blank\]|\[Blank\]|\?|)$/;
    var	rxResult	= re.exec(byearTxt);
    if (rxResult && rxResult[2])
    {
		var	byear	= rxResult[2];
		range		= Math.abs(censusYear - age - byear);
    }

    // clear or set the error indicator if required by changing class name
    setErrorFlag(elt, rxResult && range <= 1);
}		// checkBYear

/************************************************************************
 *  checkNatYear														*
 *																		*
 *  Validate the current value of a year of naturalization field.		*
 *  This may contain a 4 digit numeric year, possibly enclosed in		*
 *  editorial square brackets, a question mark, 						*
 *  a naturalized indicator, or [blank].								*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a naturalization		*
 ************************************************************************/
function checkNatYear()
{
    var	elt		= this;
    var	re		= /^(\[?[0-9]{4}\]?|nat?|\[blank\]?|\[Blank\]?|\?|)$/;
    var	year		= elt.value;
    setErrorFlag(elt, re.test(year));
}		// checkNatYear

/************************************************************************
 *  checkRelation														*
 *																		*
 *  Validate the value of the relation column against the sex column.		*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing a relationship		*
 ************************************************************************/
function checkRelation()
{
    var	elt			= this;
    var	relation		= this.value.toLowerCase();
    var relationGender		= RelationGender[relation];
    if (relationGender)
    {			// relationship is gender specific
		var form		= elt.form;
		var sexName		= 'Sex' + elt.name.substring(8);
		var sex			= form.elements[sexName].value;

		// clear or set the error indicator if required by changing class name
		setErrorFlag(elt, relationGender == sex);
    }			// relationship is gender specific
}		// checkRelation

/************************************************************************
 *  checkOwnerTenant														*
 *																		*
 *  Validate the current value of a field containing a sex.				*
 *																		*
 *  Input:																*
 *		this		<input type='text'> element containing an owner/tenant		*
 *				indicator.												*
 ************************************************************************/
function checkOwnerTenant()
{
    var	elt		= this;
    var	re		= /^[OPRopr?]?$/;
    var	type		= elt.value;
    setErrorFlag(elt, re.test(type));
}		// checkOwnerTenant

/************************************************************************
 *  checkDecimal														*
 *																		*
 *  Validate the current value of a field containing a number				*
 *  with a possible decimal point.										*
 *																		*
 *  Input:																*
 *		this				an instance of an HTML input element. 				*
 ************************************************************************/
function checkDecimal()
{
    var	elt		= this;
    var	re		= /^([0-9]*|[0-9]*\.[0-9]*)$/;
    var	number		= elt.value.trim();
    setErrorFlag(elt, re.test(number) && number > 0);
}		// checkDecimal

/************************************************************************
 *  addRow																*
 *																		*
 *  Add an extra row into the tabular portion of the current form.		*
 *																		*
 *  Input:																*
 *		this		<button id='addRow'>								*
 ************************************************************************/
function addRow(event)
{
    event.stopPropagation();
    // locate the last row of the existing table
    var form		= this.form
    var formElts	= form.elements;
    var	table		= document.getElementById("form");
    var	tbody		= table.tBodies[0];
    var lastRowNum	= tbody.rows.length;
    var rowNum		= lastRowNum + 1;
    if (rowNum < 10)
		rowNum		= '0' + rowNum;
    var	lastRow		= tbody.rows[lastRowNum - 1];
    var	newRow		= lastRow.cloneNode(true);

    // scan over the last row, and duplicate its contents into the new row
    for (var child = newRow.firstChild; child; child = child.nextSibling)
    {		// loop through all children of new row
		if (child.nodeType == 1)
		{	// element node
		    if (child.nodeName	== 'TH')
		    {	// <th> element, used only for row number
				child.innerHTML		= rowNum;
		    }	// <th> element
		    else
		    {	// some other element, should be <td>
				for (var gchild = child.firstChild;
				     gchild;
				     gchild = gchild.nextSibling)
				{	// loop through children
				    if ((gchild.nodeType == 1) &&
						(gchild.nodeName == 'INPUT' ||
						 gchild.nodeName == 'BUTTON'))
				    {		// <input> or <button> element
						var	name	= gchild.name;
						if (name.length > 2)
						{	// update name of new element
						    var	colName	= name.substring(0, name.length - 2);
						    gchild.name	= colName + rowNum;
						}	// update name of new element
				    }		// <input> or <button> element
				}	// loop through children
		    }	// some other element
		}	// element node
    }		// loop through all children of new row

    // add a new row to the end of the existing table
    tbody.appendChild(newRow);
    // I have to wait until now to activate the functionality of the
    // added elements because they are not part of the <form> until added
    // to the DOM
    for (var child = newRow.firstChild; child; child = child.nextSibling)
    {		// loop through all children of new row
		if (child.nodeType == 1)
		{	// element node
				for (var gchild = child.firstChild;
				     gchild;
				     gchild = gchild.nextSibling)
				{	// loop through children
				    if ((gchild.nodeType == 1) &&
						(gchild.nodeName == 'INPUT' ||
						 gchild.nodeName == 'BUTTON'))
				    {		// <input> or <button> element
						// initialize behavior
						initElement(gchild, true);
				    }		// <input> or <button> element
				}	// loop through children
		}	// element node
    }		// loop through all children of new row
}		// addRow

/************************************************************************
 *  function initElement												*
 *																		*
 *  Initialize the dynamic behavior of an element.						*
 *																		*
 *  Input:																*
 *		element			instance of HtmlInputElement					*
 *		clear			if true set value of non-inherited fields to	*
 *						empty											*
 ************************************************************************/
function initElement(element, clear)
{
    var form	= element.form;

    var	fldName	= element.name;
    if (fldName === undefined || fldName.length == 0)
		fldName	= element.id;

    if (fldName === undefined)
		return;

    // for individual data elements the field name generally
    // consists of a column name plus the line number as the last
    // two characters
    var colName		= fldName;
    var	rowNum		= '';
    var result		= /([a-zA-Z_$]+)(\d*)$/.exec(fldName);
    colName	= result[1];
    rowNum	= parseInt(result[2]);

    // override default key processing for input fields to provide
    // spreadsheet emulation
    if (element.nodeName.toUpperCase() == 'INPUT')
		element.onkeydown	= tableKeyDown;

    switch(colName)
    {	// column specific initialization
		case 'imageButton':
		{
		    element.onclick	= showImage;
		    break;
		}

		case 'correctImage':
		{
		    element.onclick	= correctImageUrl;
		    break;
		}

		case 'treeMatch':
		{
		    element.onclick	= matchCitations;
		    break;
		}

		case 'showImportant':
		{
		    element.onclick	= showImportant;
		    break;
		}

		case 'Family':
		{	// family number
		    element.onchange	= changeReplDown;
		    setClassByValue(colName,
						    rowNum,
						    form.elements);
		    element.checkfunc	= checkFamily;
		    element.checkfunc();
		    // focus on the first field in the form
		    if (rowNum == 1)
		    {
				element.focus();	// set the focus
				element.select();	// select all of the text
		    }
		    break;
		}	// family number replicates to subsequent rows

		case 'AddrSect':
		case 'AddrTwp':
		case 'AddrRng':
		case 'AddrMdn':
		case 'AddrMuni':
		case 'PostOffice':
		case 'Township':
		{	// fields that replicate to subsequent rows
		    element.onchange	= changeReplDown;
		    setClassByValue(colName,
						    rowNum,
						    form.elements);
		    element.checkfunc	= checkAddress;
		    element.checkfunc();
		    break;
		}	// fields that replicate to subsequent rows

		case 'Surname':
		{	// fields that replicate to subsequent rows
		    element.abbrTbl	    = SurnAbbrs;
		    element.onchange	= changeSurname;
		    setClassByValue(colName,
						    rowNum,
						    form.elements);
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// fields that replicate to subsequent rows

		case 'GivenNames':
		{	// capitalize and expand abbreviations for given names
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = GivnAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// capitalize given names

		case 'Age':
		{	// Age at time of census
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = AgeAbbrs;
		    element.onchange	= changeAge;
		    element.checkfunc	= checkAge;
		    element.checkfunc();
		    break;
		}	// Age at time of census

		case 'AgeAtDeath':
		{	// Age at time of death
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = AgeAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkAge;
		    element.checkfunc();
		    break;
		}	// Age at time of death

		case 'Sex':
		{	// capitalize flag values
		    if (clear)
				element.value	= "?";
		    element.onchange	= changeSex;
		    element.checkfunc	= checkSex;
		    element.checkfunc();
		    break;
		}	// capitalize flag values

		case 'MStat':
		{	// capitalize flag values
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkMStat;
		    element.checkfunc();
		    break;
		}	// capitalize flag values

		case 'French':
		case 'Deaf':
		case 'Blind':
		case 'Insane':
		case 'Idiot':
		case 'Lunatics':	// used in 1851
		case 'Lunatic':		// used in 1861
		case 'Idiot':		// used in 1911
		case 'Unemployed':
		case 'Member':
		case 'Absent':
		case 'SpkEnglish':
		case 'SpkFrench':
		case 'CanWrite':
		case 'CantWrite':
		{	// capitalize flag values
		    if (clear)
				element.value	= "";
		    element.onchange	= changeFlag;
		    element.checkfunc	= checkFlag;
		    element.checkfunc();
		    break;
		}	// capitalize flag values

		case 'Negro':		// used in 1851
		case 'Coloured':	// used in 1861
		case 'Indian':
		{	// capitalize flag values
		    if (clear)
				element.value	= "";
		    element.onchange	= changeFlagRace;
		    break;
		}	// capitalize flag values

		case 'School':
		case 'Illiterate':
		{	// capitalize flag values
		    if (clear)
				element.value	= "";
		    element.onchange	= changeGenderFlag;
		    element.checkfunc	= checkFlagSex;
		    element.checkfunc();
		    break;
		}	// capitalize flag values

		case 'Birth':
		case 'DeathSex':
		{	// fields that contain a sex value
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkFlagSex;
		    element.checkfunc();
		    break;
		}	// fields that contain a sex value

		case 'CanRead':
		{	// capitalize and copy to CanWrite
		    numCanReadFlds++;
		    if (element.value != '')
				numCanRead++;
		    element.onchange	= changeCanRead;
		    element.checkfunc	= checkFlag;
		    element.checkfunc();
		    break;
		}	// capitalize and copy to CanWrite

		case 'CantRead':
		{	// capitalize and copy to CantWrite
		    element.onchange	= changeCantRead;
		    element.checkfunc	= checkFlag;
		    element.checkfunc();
		    break;
		}	// capitalize and copy to CantWrite

		case 'Origin':
		case 'Nationality':
		case 'Language':
		{	// Expand abbreviations
		    element.abbrTbl	    = OrigAbbrs;
		    element.onchange	= changeReplDown;
		    setClassByValue(colName,
						    rowNum,
						    form.elements);
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// Expand abbreviations

		case 'CauseOfDeath':
		{	// Cause of Death in 1851 and 1861 population census
		    element.abbrTbl	    = CauseAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkText;
		    element.checkfunc();
		    break;
		}	// Cause of Death in 1851 and 1861 population census

		case 'SpkOther':
		{	// Expand abbreviations but don't repl down
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = OrigAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// Expand abbreviations

		case 'BInYear':
		case 'BDate':
		case 'MInYear':
		case 'MarYear':
		{	// Expand abbreviations
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = MonthAbbrs;
		    element.onchange	= dateChanged;
		    element.checkfunc	= checkDate;
		    element.checkfunc();
		    break;
		}	// Expand abbreviations

		case 'Relation':
		{	// Expand abbreviations
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = RelAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkRelation;
		    element.checkfunc();
		    break;
		}	// Expand abbreviations

		case 'BPlace':
		{	// expand abbreviations for birthplace
		    if (clear)
				element.value	= "Ontario";
		    element.abbrTbl	    = BpAbbrs;
		    element.onchange	= changeBPlace;
		    element.checkfunc	= checkAddress;
		    element.checkfunc();
		    break;
		}	// expand abbreviations for birthplace

		case 'ImmYear':
		{	// expand abbreviations for immigration or nat'zation
		    if (clear)
				element.value	= "";
		    element.onchange	= changeImmYear;
		    element.checkfunc	= checkYear;
		    element.checkfunc();
		    break;
		}	// expand abbreviations for immigration or nat'zation

		case 'NatYear':
		{	// expand abbreviations for immigration or nat'zation
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkNatYear;
		    element.checkfunc();
		    break;
		}	// expand abbreviations for immigration or nat'zation

		case 'FathersBPlace':
		{	// Father's birthplace default for Mother's birthplace
		    element.abbrTbl	    = BpAbbrs;
		    element.onchange	= changeFBPlace;
		    element.checkfunc	= checkAddress;
		    element.checkfunc();
		    break;
		}	// Father's birthplace default for Mother's birthplace

		case 'MothersBPlace':
		{	// Mother's birthplace
		    element.abbrTbl	    = BpAbbrs;
		    element.onchange	= changeMBPlace;
		    element.checkfunc	= checkAddress;
		    element.checkfunc();
		    break;
		}	// Mother's birthplace

		case 'Religion':
		{	// religion: expand defaults and replicate down
		    element.abbrTbl	    = RlgnAbbrs;
		    element.onchange	= changeReplDown;
		    setClassByValue(colName,
						    rowNum,
						    form.elements);
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// religion: expand defaults and replicate down

		case 'Occupation':
		case 'OccOther':
		{	// Occupation
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = OccAbbrs;
		    element.onchange	= changeOccupation;
		    element.checkfunc	= checkOccupation;
		    element.checkfunc();
		    break;
		}	// Occupation

		case 'EmpType':
		{	// employment type
		    if (clear)
				element.value	= "";
		    element.onchange	= changeEmpType;
		    element.checkfunc	= checkText;
		    element.checkfunc();
		    break;
		}	// employment type

		case 'EmpWhere':
		{	// employment location
		    element.abbrTbl	    = EmpWhereAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkText;
		    element.checkfunc();
		    break;
		}	// employment location

		case 'Employee':
		{	// Employee flag
		    if (clear)
				element.value	= "";
		    element.onchange	= changeEmployee;
		    element.checkfunc	= checkFlag;
		    element.checkfunc();
		    break;
		}	// Employee flag`

		case 'Employer':
		{	// Employer
		    if (clear)
				element.value	= "";
		    element.onchange	= changeEmployer;
		    element.checkfunc	= checkFlag;
		    element.checkfunc();
		    break;
		}	// Employer

		case 'OwnAcct':
		case 'OwnMeans':
		{	// self employed
		    if (clear)
				element.value	= "";
		    element.onchange	= changeSelfEmployed;
		    element.checkfunc	= checkFlag;
		    element.checkfunc();
		    break;
		}	// self employed

		case 'NumHands':
		case 'WksEmp':
		case 'WksOth':
		case 'HpWEmp':
		case 'HpWOth':
		case 'IncomeEmp':
		case 'IncomeOth':
		case 'MonthsFact':
		case 'MonthsHome':
		case 'MonthsOther':
		case 'MonthsSchool':
		case 'HouseRooms':
		case 'WeeksUnemp':
		case 'WeeksIll':
		case 'Horses':
		case 'MilkCows':
		case 'Cattle':
		case 'Sheep':
		case 'Pigs':
		case 'LifeInsurance':	// 1911
		case 'AccInsurance':	// 1911
		case 'SchoolMons':	// 1911
		{	// numeric fields
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}	// numeric fields

		case 'HourlyRate':	// 1911
		case 'CostInsurance':	// 1911
		case 'CostEducation':	// 1911
		{	// decimal fields
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkDecimal;
		    element.checkfunc();
		    break;
		}	// decimal fields

		case 'SchoolMons':
		{	// months of school in 1921 census
		    if (clear)
				element.value	= "";
		    element.onchange	= changeSchoolMons;
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}	// months of school in 1921 census

		case 'BYearTxt':
		{	// year of birth
		    element.onchange	= changeBYear;
		    element.checkfunc	= checkBYear;
		    element.checkfunc();
		    break;
		}	// year of birth

		case 'Location':
		case 'Address':
		{	// Address
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = AddrAbbrs;
		    element.onchange	= changeAddress;
		    element.checkfunc	= checkAddress;
		    element.checkfunc();
		    break;
		}	// Address


		case 'ResType':
		{	// capitalize and expand abbreviations for given names
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = ResTypeAbbrs;
		    element.onchange	= changeResType;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// residence type

		case 'OwnerTenant':
		{	// capitalize and expand abbreviations for given names
		    if (clear)
				element.value	= "";
		    element.onchange	= changeOwnerTenant;
		    element.checkfunc	= checkOwnerTenant;
		    element.checkfunc();
		    break;
		}	// residence type

		case 'HouseRent':
		{	// montly rent
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = StoriesAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}	// monthly rent

		case 'HouseMaterial':
		{	// capitalize and expand abbreviations for given names
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = ResTypeAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}	// handle house materials

		case 'Stories':
		{	// expand abbreviations for number of stories in residence
		    if (clear)
				element.value	= "";
		    element.abbrTbl	    = StoriesAbbrs;
		    element.onchange	= change;
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}	// expand abbreviations for number of stories

		case 'NumFamilies':
		{	// validate number of families
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkNumber;
		    element.checkfunc();
		    break;
		}	// validate number of families

		case 'Remarks':
		{	// remarks
		    if (clear)
				element.value	= "";
		    element.onchange	= change;
		    element.checkfunc	= checkText;
		    element.checkfunc();
		    break;
		}	// remarks

		case 'doIdir':
		{	// button to manage the IDIR element
		    element.onclick	    = doIdir;
		    break;
		}	// button to manage the IDIR element

		case 'clearIdir':
		{	// button to clear the IDIR element
		    element.onclick	    = clearIdir;
		    break;
		}	// button to clear the IDIR element

		case 'reset':
		{	// button to reset the form to defaults
		    element.onclick	    = reset;
		    break;
		}	// button to reset the form to defaults

		case 'addRow':
		{	// button to add an additional row to the end
		    element.onclick	    = addRow;
		    break;
		}	// button to add an additional row to the end

		default:
		{		// all other columns
		    if (element.className.substr(0,5) == 'same ' ||
				element.className.substr(0,3) == 'dft')
				element.onchange	= changeDefault;
		    else
				element.onchange	= change;
		    element.checkfunc	= checkName;
		    element.checkfunc();
		    break;
		}		// all other columns

    }			// column specific initialization
}	// function initElement

/************************************************************************
 *  function showImportant												*
 *																		*
 *  Take action when the user clicks on the 'showImportant' button.		*
 *																		*
 *  Input:																*
 *		this			<button id='showImportant'>						*
 ************************************************************************/
function showImportant(event)
{
    event.stopPropagation();
    var form		= this.form;
    var	table		= document.getElementById('form');
    if (table)
    {
		var	thead	= table.tHead;
		if (thead && thead.rows.length > 0)
		{
		    var	trow	= thead.rows[0];
		    for(var i = 0; i < trow.cells.length; i++)
		    {
				var th	= trow.cells[i];
				var label	= th.innerHTML.trim();
				switch(label.toLowerCase())
				{
				    case 'line':
				    case 'fam':
				    case 'surname':
				    case 'given names':
				    case 'sex':
				    case 'mst':
				    case 'bdate':
				    case 'byear':
				    case 'age':
				    case 'occupation':
				    case 'ft':
				    {
						break;
				    }

				    default:
				    {
						th.onclick();
						break;
				    }
				}	// act on label
		    }		// loop through column labels
		}		// table contains a <thead> which contains a <tr>
    }			// table present in form
}	// function showImportant

/************************************************************************
 *  function reset														*
 *																		*
 *  Take action when the user clicks on the 'reset' button.				*
 *																		*
 *  Input:																*
 *		this			<button id='reset'>								*
 ************************************************************************/
function reset(event)
{
    event.stopPropagation();
    var form		= this.form;
    var	census		= form.Census.value;
    var	censusYear	= census.substring(2);
    var	province	= form.Province.value;
    var	district	= form.District.value;
    var	subdistrict	= form.SubDistrict.value;
    var	division	= form.Division.value;
    var	page		= form.Page.value;
    var url	= "/getRecordXml.php?Table=Census" + censusYear +
						"&Census=" + census +
						"&District=" + district +
						"&SubDistrict=" + subdistrict +
						"&Division=" + division +
						"&Page=" + page +
						"&Line=0";
    HTTP.getXML(url,
				gotPrevLine,
				noPrevLine);
}		// reset

/************************************************************************
 *  gotPrevLine																*
 *																		*
 *  Take action when the last line of the previous page is retrieved		*
 *  from the server.														*
 *  Restore all fields in the form to their default values.				*
 *																		*
 *  Input:																*
 *		xmlDoc				XML document containing last line of prev page		*
 ************************************************************************/
function gotPrevLine(xmlDoc)
{
    var	rootNode	= xmlDoc.documentElement;

    var prevLine	= getParmsFromXml(rootNode);
    // alter the values and classes of the elements
    // in the form
    var form		= document.censusForm;
    var formElts	= form.elements;

    var famNum		= 0;
    var surname		= "[Unknown]";
    var origin		= "[Unknown]";
    var nationality	= "[Unknown]";
    var religion	= "[Unknown]";
    var	rowNum		= 0;
    for(key in prevLine)
    {				// loop through fields in prev line
		switch(key)
		{			// act on specific fields in prev line
		    case 'family':
		    {
				famNum		= prevLine[key];
				break;
		    }			// family

		    case 'surname':
		    {
				surname		= prevLine[key];
				break;
		    }			// surname

		    case 'origin':
		    {
				origin		= prevLine[key];
				break;
		    }			// origin

		    case 'nationality':
		    {
				nationality	= prevLine[key];
				break;
		    }			// nationality

		    case 'religion':
		    {
				religion	= prevLine[key];
				break;
		    }			// religion

		}			// act on specific fields in prev line
    }				// loop through fields in prev line

    for (var i = 0; i < formElts.length; i++)
    {
		var field	= formElts[i];
		// for individual data elements the field name includes the
		// line number from the original form as the last two characters
		var	fieldName	= field.name;
		if (fieldName.length > 2)
		{	// field name long enough to include row number
		    rowNum	= parseInt(fieldName.substr(fieldName.length - 2, 2));
		    if (!isNaN(rowNum))
		    {	// field name contains row number
				var colName	= fieldName.substr(0,fieldName.length - 2);
				switch(colName)
				{	// action depends upon the type of field
				    case 'Family':
				    {	// family number replicates down
						if (famNum == 0)
						    famNum	= field.value;
						else
						{
						    field.value		= famNum;	// replicate
						    field.className	= 'same white right';
						}
						break;
				    }	// family number replicates down

				    case 'Surname':
				    {	// surname replicates down
						if (surname == '[Unknown]')
						    surname	= field.value;
						else
						{
						    field.value		= surname;	// replicate
						    field.className	= 'same white left';
						}
						break;
				    }	// surname replicates down

				    case 'Origin':
				    {	// origin replicates down
						if (origin == '[Unknown]')
						    origin	= field.value;
						else
						{
						    field.value		= origin;	// replicate
						    field.className	= 'same white left';
						}
						break;
				    }	// origin replicates down

				    case 'Nationality':
				    {	// nationality replicates down
						if (nationality == '[Unknown]')
						    nationality	= field.value;
						else
						{
						    field.value		= nationality;	// replicate
						    field.className	= 'same white left';
						}
						break;
				    }	// nationality replicates down

				    case 'BPlace':
				    case 'FathersBPlace':
				    case 'MothersBPlace':
				    {	// birthplace
						field.value	= 'Ontario';
						field.className	= 'same white left';
						break;
				    }	// birthplace

				    case 'Religion':
				    {	// religion replicate down
						if (religion == '[Unknown]')
						    religion	= field.value;
						else
						{
						    field.value		= religion;	// replicate
						    field.className	= 'same white left';
						}
						break;
				    }	// religion replicate down

				    case 'Sex':
				    {	// Sex
						field.value		= '?';
						break;
				    }	// Sex

				    case 'Race':
				    {	// Race
						field.value		= 'W';
						break;
				    }	// Race

				    case 'BPlaceRu':
				    {	// BPlaceRu
						field.value		= 'r';
						break;
				    }	// BPlaceRu

				    case 'CanRead':
				    case 'CanWrite':
				    case 'SpkEnglish':
				    {	// CanRead, CanWrite, SpkEnglish
						field.value		= 'Y';
						break;
				    }	// CanRead

				    default:
				    {	// most fields have null default
						field.value		= '';
						break;
				    }	// most fields have null default
				}	// action depends upon the type of field
		    }	// field name contains row number
		}	// field name long enough to include row number
    }		// loop through all elements

    // locate the last row of the existing table
    var	cenYear		= form.Census.value.substring(2);
    var	pageSize	= CenPageSize[cenYear];
    var	addRowButton	= document.getElementById('addRow');
    for(rowNum += 1; rowNum <= pageSize; rowNum++)
		addRowButton.onclick();
    return false;
}		// gotPrevLine

/************************************************************************
 *  function noPrevLine													*
 *																		*
 *  The database server was unable to respond to the query.				*
 ************************************************************************/
function noPrevLine()
{
    alert("CensusForm.js: noPrevLine: " +
				"unable to find getRecordXml.php script on server");
}		// function noPrevLine

/************************************************************************
 *  function showImage													*
 *																		*
 *  Display the image of the original census page.						*
 *  This is the onclick method for the button with id 'imageButton'.	*
 *																		*
 *  Input:																*
 *		this			<button id='imageButton'>						*
 ************************************************************************/
var imageTypes	= ['jpg', 'jpeg', 'gif', 'png'];

function showImage(event)
{
    event.stopPropagation();
    var	form			= this.form;
    var image			= form.elements['Image'].value;
    var	lang			= 'en';
    if ('lang' in args)
		lang			= args['lang'];
    var imageUrl		= "/Canada/DisplayImage.php?src=" + image +
										"&lang=" + lang;
    var	dotPos			= image.lastIndexOf('.');
    if (dotPos >= 0)
    {
		var	imageType	= image.substring(dotPos + 1).toLowerCase();
		var	imageIndex	= imageTypes.indexOf(imageType);
		if (imageIndex == -1)
		    imageUrl		= image;
    }
    else
		imageUrl		= image;

    // replace button with copyright notice
    var copNotice		= document.getElementById('imageCopyrightNote');
    if (copNotice)
    {			// replace button with copyright notice
		var clone		= copNotice.cloneNode(true);
		var parentNode		= this.parentNode;
		var nextSibling		= this.nextSibling;
		parentNode.removeChild(this);
		parentNode.insertBefore(clone, nextSibling);
		// also remove correct image button
		var corrButton		= document.getElementById('correctImage');
		if (corrButton)
		{
		    parentNode		= corrButton.parentNode;
		    parentNode.removeChild(corrButton);
		}
    }			// replace button with copyright notice
    else
    {			// just disable button
		this.disabled		= true;
    }			// just disable button

    // display the image in the right half of the window
    openFrame("imageFrame",
		      imageUrl,
		      "right");
    return false;	// do not perform defaul action for button
}	// showImage

/************************************************************************
 *  function correctImageUrl											*
 *																		*
 *  Change the display so the user can modify the Uniform Record Locator*
 *  of the image for this page.  This is the onclick method for the		*
 *  button with id 'correctImage'.										*
 *																		*
 *  Input:																*
 *		this			<button id='correctImage'>						*
 ************************************************************************/
function correctImageUrl(event)
{
    event.stopPropagation();
    var	form		= this.form;
    var imageLine	= document.getElementById("ImageButton");
    var imageUrl	= '';
    var	nextSibling;
    for(var child = imageLine.firstChild;
		child;
		child = nextSibling)
    {
		if (child.nodeType == 1 &&
		    child.nodeName == 'INPUT' &&
		    child.name == 'Image')
		{		// <input name='Image' ...
		    imageUrl	= child.value;
		}		// <input name='Image' ...
		nextSibling	= child.nextSibling;
		imageLine.removeChild(child);
    }		// loop through children of imageLine

    // create new label and <input type='text'>
    imageLine.appendChild(
						document.createTextNode("Enter URL of Census Image:"));
    var	inputTag	= document.createElement("INPUT");
    inputTag.type	= 'text';
    inputTag.size	= '64';
    inputTag.maxlength	= '128';
    inputTag.name	= 'Image';
    inputTag.value	= imageUrl;
    inputTag.className	= 'black white leftnc';
    imageLine.appendChild(inputTag);
}	// correctImageUrl

/************************************************************************
 *  function matchCitations												*
 *																		*
 *  Match all citations to this page against the individuals in the page*
 *  to set the link to the appropriate entry in the family tree.		*
 *  This is the onclick method for the button with id 'treeMatch'.		*
 *																		*
 *  Input:																*
 *		this		<button id='treeMatch'>								*
 ************************************************************************/
function matchCitations(event)
{
    event.stopPropagation();
    var	form		= this.form;
    var	lang		= 'en';
    if ('lang' in args)
		lang		= args['lang'];
    var	url		= "matchCitations.php" +
						  "?Census=" + form.Census.value +
						  "&Province=" + form.Province.value +
						  "&District=" + form.District.value +
						  "&SubDistrict=" + form.SubDistrict.value +
						  "&Division=" + form.Division.value +
						  "&Page=" + form.Page.value +
						  "&lang=" + lang;

    window.open(url, "matchCitations");
    return false;
}	// function matchCitations

/************************************************************************
 *  function doIdir														*
 *																		*
 *  The user has requested to manage the IDIR value for the				*
 *  current line.														*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function doIdir(event)
{
    event.stopPropagation();
    var	agePattern	= /([0-9]+m)|([0-9]+)/;
    var	rxResults	= null;
    var	button		= this;
    var	name		= button.id;
    var	lineNum		= name.substring(name.length - 2);
    var	form		= button.form;
    var	eltName		= 'IDIR' + lineNum;
    var idir        = 0;
    if (form.elements[eltName])
        idir		= form.elements[eltName].value - 0;
    else
        alert("CensusForm.js: 2984 form.elements['" + eltName + "' undefined");

    if (idir > 0)
    {			// have an existing association
		window.open('/FamilyTree/Person.php?idir=' + idir,
				    '_blank');
    }			// have an existing association
    else
    {			// search for matches
		popupLoading(button);
		var	line		= button.id.substring(6);
		var	surname		= form.elements['Surname' + line].value;
		var	givennames	= form.elements['GivenNames' + line].value;
		var	age		    = form.elements['Age' + line].value;
		var	sex		    = form.elements['Sex' + line].value;
		var	censusYear	= form.Census.value.substring(2);
		var	birthYear	= censusYear;	// default

		rxResults	= agePattern.exec(age);
		if (rxResults)
		{		// parse matched
		    if (rxResults[1] !== undefined)
		    {		// age in months
				var months	= rxResults[1];
				months	= months.substring(0,months.length - 1) - 0;
				if (months < 5)
				    birthYear	= censusYear;
				else
				    birthYear	= censusYear - 1;
		    }		// age in months
		    else
		    if (rxResults[2] !== undefined)
		    {		// age in years
				birthYear	= censusYear - rxResults[2];
		    }		// age in years
		}		// parse matched

		var	lang	= 'en';
        if ('lang' in args)
		    lang		= args['lang'];
		var url	= "/FamilyTree/getIndivNamesXml.php?Surname=" +
						encodeURIComponent(surname) +
						"&GivenName=" + encodeURIComponent(givennames) +
						"&Sex=" + sex +
						"&BirthYear=" + birthYear +
						"&Range=5" +
						"&buttonId=" + name +
						"&includeParents=Y&includeSpouse=Y" +
						"&incMarried=yes&loose=yes" +
						"&lang=" + lang;
		if (debug.toLowerCase() == 'y')
		    alert("CensusForm.js: doIdir: HTTP.getXML('" + url + "')");
		HTTP.getXML(url,
				    gotIdir,
				    noIdir);
    }		// search for matches
}		// doIdir

/************************************************************************
 *  function gotIdir													*
 *																		*
 *  The XML response to the database query for matching individuals has	*
 *  been returned.														*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document										*
 ************************************************************************/
function gotIdir(xmlDoc)
{
    if (debug.toLowerCase() == 'y')
		alert("CensusForm.js: gotIdir: xmlDoc=" + tagToString(xmlDoc));
    var	rootNode	= xmlDoc.documentElement;
    var	buttonId	= rootNode.getAttribute("buttonId");
    var	button		= document.getElementById(buttonId);
    if (button === null)
    {
		hideLoading();
		alert("CensusForm.js: gotIdir: unable to find element with id='" +
				buttonId + "' rootNode=" + tagToString(rootNode));
		return;
    }

    var	form		= button.form;
    var	line		= buttonId.substring(6);
    var	surname		= form.elements['Surname' + line].value;
    var	givennames	= form.elements['GivenNames' + line].value;
    var	age		= form.elements['Age' + line].value;
    var	bdateElt	= form.elements['BDate' + line];
    var	byearElt	= form.elements['BYearTxt' + line];
    var	censusYear	= form.Census.value.substring(2);
    var	birthYear	= censusYear;
    var	birthDate	= censusYear;	// default
    var	agePattern	= /([0-9]+m)|([0-9]+)/;
    var	yearPattern	= /[0-9]{4}/;
    var	rxResults;

    if (byearElt && (rxResults = yearPattern.exec(byearElt.value)))
    {		// explicit birth year
		if (bdateElt)
		    birthDate	= bdateElt.value + ' ' + rxResults[0];
		else
		    birthDate	= rxResults[0];
    }		// explicit birth year
    else
    {		// estimate birth date from age
		rxResults	= agePattern.exec(age);
		if (rxResults)
		{		// parse matched
		    if (rxResults[1] !== undefined)
		    {		// age in months
				var months	= rxResults[1];
				months		= months.substring(0,months.length - 1) - 0;
				if (months < 5)
				    birthDate	= censusYear;
				else
				    birthDate	= censusYear - 1;
		    }		// age in months
		    else
		    if (rxResults[2] !== undefined)
				birthDate	= censusYear - rxResults[2];
		}		// parse matched
    }		// estimate birth date from age
    var	actionButton	= null;

    hideLoading();
    var	msgDiv	= document.getElementById('IdirDialog');
    if (msgDiv)
    {		// have popup <div> to display selection dialog in
		// substitutions into the template
		var parms	= {"sub"	: "",
						   "surname"	: surname,
						   "givenname"	: givennames,
						   "birthyear"	: birthDate,
						   "line"	: line};

		var matches	= xmlDoc.getElementsByTagName("indiv");
		if (matches.length > 0)
		{		// have some matching entries
		    return displaySelectIdir(msgDiv,
								     'idirChooserForm$sub',
								     parms,
								     button,
								     closeIdirDialog,
								     matches);
		}		// have some matching entries
		else
		{		// have no matching entries
		    var cmds	= xmlDoc.getElementsByTagName("cmd");
		    parms.cmd	= tagToString(cmds[0]).replace('<','&lt;');
		    return displayDialog(msgDiv,
								 'idirNullForm$sub',
								 parms,
								 button,
								 null,		// default close dialog
								 false);	// default show on open
		}		// have no matching entries

    }		// support for dynamic display of messages
}		// gotIdir

/************************************************************************
 *  function clearIdir													*
 *																		*
 *  The user has requested to clear the IDIR value for the current line.*
 *  Note that this only clears the value on the web page, the user must	*
 *  update the census page to apply the change to the database.			*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function clearIdir(event)
{
    event.stopPropagation();
    var	button		= this;
    var	name		= button.id;
    var	lineNum		= name.substring(name.length - 2);
    var	form		= button.form;
    var	idirElt		= form.elements['IDIR' + lineNum];
    idirElt.value	= 0;
    var findButton	= form.elements["doIdir" + lineNum];
    while(findButton.hasChildNodes())
    {	// remove contents of cell
		findButton.removeChild(findButton.firstChild);
    }	// remove contents of cell
    findButton.appendChild(document.createTextNode("Find"));
}		// clearIdir

/************************************************************************
 *  function displaySelectIdir											*
 *																		*
 *  This function displays a customized dialog for choosing from		*
 *  a list of individuals who match the individual described by the		*
 *  current line of the census.											*
 *																		*
 *  Input:																*
 *		msgDiv			an HTML element to modify and make visible.		*
 *						This is normally a <div> element				*
 *		templateId		identifier of an HTML element that provides the	*
 *						structure and constant strings to be laid out	*
 *						in the dialog									*
 *		parms			an object containing values to substitute for	*
 *						symbols ($xxxx) in the template					*
 *		element			an HTML element used for positioning the		*
 *						dialog for the user.  This is normally the 		*
 *						<button> for the user to request the dialog.	*
 *		action			onclick action to set for 1st (or only) button	*
 *						in the dialog.  If null the default action is	*
 *						to just hide the dialog.						*
 *		matches			array of XML <indiv> tags						*
 ************************************************************************/
function displaySelectIdir(dialog,
						   templateId,
						   parms,
						   element,
						   action,
						   matches)
{
    if (displayDialog(dialog,
				      templateId,
				      parms,
				      element,
				      action,
				      true))
    {
		// update the selection list with the matching individuals
		var select	        = document.getElementById("chooseIdir");
		select.onchange	    = idirSelected;
		//select.onclick	= function() {alert("select.onclick");};

		// add the matches
		for (var i = 0; i < matches.length; ++i)
		{	// loop through the matches
		    var	indiv	= matches[i];

		    // get the "id" attribute
		    var	value		= indiv.getAttribute("id");
		    var	surname		= "";
		    var	maidenname	= "";
		    var	givenname	= "";
		    var	gender		= "";
		    var	birthd		= "";
		    var	deathd		= "";
		    var	parents		= "";
		    var	spouses		= "";

		    for (var child = indiv.firstChild;
				 child;
				 child = child.nextSibling)
		    {		// loop through all children of indiv
				if (child.nodeType == 1)
				{	// element node
				    switch(child.nodeName)
				    {	// act on specific child
						case "surname":
						{
						    surname	= child.textContent;
						    break;
						}

						case "maidenname":
						{
						    maidenname	= child.textContent;
						    break;
						}

						case "givenname":
						{
						    givenname	= child.textContent;
						    break;
						}

						case "gender":
						{
						    gender	= child.textContent;
						    break;
						}

						case "birthd":
						{
						    birthd	= child.textContent;
						    break;
						}

						case "deathd":
						{
						    deathd	= child.textContent;
						    break;
						}

						case "parents":
						{
						    parents	= child.textContent;
						    break;
						}

						case "families":
						{
						    spouses	= child.textContent;
						    break;
						}

						default:
						{
						    // alert("CensusForm.js:displaySelectIdir: " +
						    //	  "nodeName='" + child.nodeName + "'");
						    break;
						}
				    }	// act on specific child
				}	// element node
		    }		// loop through all children of indiv

		    var text	= surname;
		    if (maidenname != surname)
				text	+= " (" + maidenname + ")";
		    text	    += ", " + givenname + "(" +
					    	   birthd + "-" +
					    	   deathd + ")";
		    if (parents.length > 0)
				text	+= ", child of " + parents;
		    if (spouses.length > 0)
				text	+= ", spouse of " + spouses;

		    // add a new HTML Option object
		    addOption(select,	// Select element
				      text,	// text value
				      value);	// unique key
		}	// loop through the matches

		select.selectedIndex	= 0;

		// show the dialog
		dialog.style.visibility	= 'visible';
		dialog.style.display	= 'block';
		// the following is a workaround for a bug in FF 40.0 and Chromium
		// in which the onchange method of the <select> is not called when
		// the mouse is clicked on an option
		for(var io=0; io < select.options.length; io++)
		{
		    var option	= select.options[io];
		    option.addEventListener("click", function(event) {event.stopPropagation(); this.selected = true; this.parentNode.onchange();});
		}
		select.focus();
		return true;
    }		// template OK
    else
		return false;
}		// function displaySelectIdir

/************************************************************************
 *  function idirSelected												*
 *																		*
 *  This is the onchange method of the select in the popup to choose	*
 *  the individual to associated with the current line.					*
 *																		*
 *  Input:																*
 *		this		<select id='chooseIdir'>							*
 ************************************************************************/
function idirSelected()
{
    var	select	= this;
    var	idir	= 0;
    var	index	= select.selectedIndex;
    if (index >= 0)
    {
		var	option	= select.options[index];
		idir	= option.value;
    }
    var	form	= this.form;	// <form name='idirChooserForm'>

    for(var ie = 0; ie < form.elements.length; ie++)
    {		// search for choose button
		var	element	= form.elements[ie];
		if (element != select &&
		    element.id && element.id.length >= 6 &&
		    element.id.substring(0,6) == "choose")
		{	// have the button
		    if (idir == 0)
				element.innerHTML	= 'Cancel';
		    else
				element.innerHTML	= 'Select';
		}	// have the button
    }		// search for choose button
}		// function idirSelected

/************************************************************************
 *  function noIdir														*
 *																		*
 *  The database server was unable to respond to the query.				*
 ************************************************************************/
function noIdir()
{
    alert("CensusForm.js: noIdir: " +
		  "unable to find getIndivNamesXml.php script on server");
}		// function noIdir

/************************************************************************
 *  function closeIdirDialog											*
 *																		*
 *  The user clicked on the button to close the IDIR dialog.			*
 *																		*
 *  Input:																*
 *		this		instance of <button>								*
 ************************************************************************/
function closeIdirDialog(event)
{
    event.stopPropagation();
    var	form	= this.form;
    var select	= form.chooseIdir;
    if (select)
    {		// select for IDIR present
		if (select.selectedIndex >= 0)
		{	// option chosen
		    var option	= select.options[select.selectedIndex];
		    var idir	= option.value;
		    if (idir > 0)
		    {	// individual chosen
				var line	= this.id.substring(6);
				var mainForm	= document.censusForm;
				var census	= mainForm.Census.value;
				var province	= mainForm.Province.value;
				var district	= mainForm.District.value;
				var subDistrict	= mainForm.SubDistrict.value;
				var division	= mainForm.Division.value;
				var page	= mainForm.Page.value;
				var family	= mainForm.elements["Family" + line].value;
				/* hide new code for moment
				HTTP.getXML("/FamilyTree/getFamilyOfXml.php?idir=" + idir +
								"&line=" + line +
								"&census=" + census +
								"&province=" + province +
								"&district=" + district +
								"&subDistrict=" + subDistrict +
								"&division=" + division +
								"&page=" + page +
								"&family=" + family,
						    gotFamily,
						    noFamily);
				*/
				mainForm.elements["IDIR" + line].value		= idir;
				var findButton	= mainForm.elements["doIdir" + line];
				while(findButton.hasChildNodes())
				{	// remove contents of cell
				    findButton.removeChild(findButton.firstChild);
				}	// remove contents of cell
				findButton.appendChild(document.createTextNode("Show"));
				var cell	= findButton.parentNode;
				var clearButton	= document.getElementById("clearIdir" + line);
				if (clearButton === undefined || clearButton === null)
				{		// need to add clear button
				    clearButton		= document.createElement("BUTTON");
				    clearButton.type		= 'button';
				    clearButton.id		= "clearIdir" + line;
				    clearButton.className	= 'button';
				    clearButton.appendChild(document.createTextNode("Clear"));
				    cell.appendChild(clearButton);
				    clearButton.onclick	= clearIdir;
				}		// need to add clear button
				var setFlag	= document.getElementById("setIdir" + line);
				if (setFlag === undefined || setFlag === null)
				{		// need to add set field
				    setFlag		= document.createElement("INPUT");
				    setFlag.type	= 'hidden';
				    setFlag.id		= "setIdir" + line;
				    setFlag.name	= "setIdir" + line;
				    cell.appendChild(setFlag);
				}		// need to add set field
				setFlag.value	= idir;
		    }	// individual chosen
		}	// option chosen
    }		// select for IDIR present

    // hide the dialog
    for (var div = this.parentNode; div; div = div.parentNode)
    {		// loop up the element tree
		if (div.nodeName.toLowerCase() == 'div')
		{
		    div.style.display	= 'none';	// hide
		    break;
		}
    }		// loop up the element tree

    // suppress default action
    return false;
}		// closeIdirDialog

/************************************************************************
 *  function gotFamily													*
 *																		*
 *  The XML response to the database query for matching members of a	*
 *  family from the family tree database against a family in a census.	*
 *																		*
 *  Input:																*
 *		xmlDoc		XML document										*
 ************************************************************************/
function gotFamily(xmlDoc)
{
    // alert("CensusForm.js: gotFamily: xmlDoc=" + tagToString(xmlDoc));

    var	rootNode	= xmlDoc.documentElement;
    var msgs		= xmlDoc.getElementsByTagName("msg");
    if (msgs.length > 0)
    {		// error messages
		alert("CensusForm.gotFamily: error response: " + msgs[0].textContent);
		return;
    }		// error messages

    // get the parameters used to invoke the script into an array
    var	parms		= xmlDoc.getElementsByTagName("parms");
    if (parms.length > 0)
		parms		= getParmsFromXml(parms[0]);
    else
		parms		= null;

    // display the dialog relative to the button
    var	buttonId	= rootNode.getAttribute("buttonId");
    var	button		= document.getElementById(buttonId);
    var	form		= button.form;
    var	actionButton	= null;

    var	msgDiv	= document.getElementById('IdirDialog');
    if (msgDiv)
    {		// have popup <div> to display selection dialog in
		while(msgDiv.hasChildNodes())
		{	// remove contents of cell
		    msgDiv.removeChild(findButton.firstChild);
		}	// remove contents of cell

		var matches	= xmlDoc.getElementsByTagName("indiv");
		if (matches.length > 0)
		{		// have some matching entries
		    return displayFamilyDialog(msgDiv,
								     'FamilyEntryForm$sub',
								     parms,
								     button,
								     closeFamilyDialog,
								     matches);
		}		// have some matching entries
		else
		{		// have no matching entries
		    // This should never occur because the response must
		    // contain all individuals in the identified family: CYA
		    return displayDialog(msgDiv,
								 'idirNullForm$sub',
								 parms,
								 button,
								 null,		// default close dialog
								 false);	// default show on open
		}		// have no matching entries

    }		// support for dynamic display of messages
}		// gotFamily

/************************************************************************
 *  function displayFamily												*
 *																		*
 *  This function displays a customized dialog for choosing from		*
 *  a list of individuals who match the individual described by the		*
 *  current line of the census.											*
 *																		*
 *  Input:																*
 *		msgDiv			an HTML element to modify and make visible.		*
 *						This is normally a <div> element				*
 *		templateId		identifier of an HTML element that provides the	*
 *						structure and constant strings to be laid out in*
 *						the dialog										*
 *		parms			an object containing values to substitute for	*
 *						symbols ($xxxx) in the template					*
 *		element			an HTML element used for positioning the		*
 *						dialog for the user.  This is normally the 		*
 *						<button> for the user to request the dialog.	*
 *		action			onclick action to set for 1st (or only) button	*
 *						in the dialog.  If null the default action is	*
 *						to just hide the dialog.						*
 *		matches			array of XML <indiv> tags						*
 ************************************************************************/
function displayFamily(dialog,
						templateId,
						parms,
						element,
						action,
						matches)
{
    if (displayDialog(dialog,
				      templateId,
				      parms,
				      element,
				      action,
				      true))
    {
		// update the selection list with the matching individuals
		var nextNode	= document.getElementById("FamilyButtonLine");
		var parentNode	= nextNode.parentNode;

		// add the matches
		for (var i = 0; i < matches.length; ++i)
		{	// loop through the matches
		    var	indiv	= matches[i];

		    // get the contents of the object
		    var fields		= getParmsFromXml(indiv);
		    var member;
		    if (fields['idir'].length > 0)
				member		= createFromTemplate("Match$idir",
											     fields,
											     null);
		    else
				member		= createFromTemplate("NoMatch$sub",
											     fields,
											     null);
		    parentNode.insertBefore(member,
								    nextNode);
		}	// loop through the matches

		// show the dialog
		dialog.style.display	= 'block';
		return true;
    }		// template OK
    else
		return false;
}		// function displayFamily

/************************************************************************
 *  function noFamily													*
 *																		*
 *  The database server was unable to respond to the query.				*
 ************************************************************************/
function noFamily()
{
    alert("CensusForm.js: noFamily: " +
		  "unable to find script 'getFamilyOfXml.php' on web server");
}		// function noFamily

/************************************************************************
 *  function idirFeedback												*
 *																		*
 *  This callback function is called by the script matchCitations.php	*
 *  when it has matched one or more individuals who have cited the		*
 *  current census page, to individuals on the page itself.  Those		*
 *  lines in the census are now linked to the family tree database		*
 *  and the existence of those linkages must be recorded on this page	*
 *  both to visually clue in the viewer that the linkages exist, and to	*
 *  ensure that if the current page is written into the database it will*
 *  not over-write the linkages.										*
 *																		*
 *  Input:																*
 *		parms	array in which each entry associates a line of the		*
 *				census page to the IDIR of the record in the family tree*
 ************************************************************************/
function idirFeedback(parms)
{
    location	= location;
    if (false)
    {
    var	form	= document.censusForm;
    var	msg	= "";
    for(var line in parms)
    {		// loop through all matched lines
		var idir		= parms[line];
		if (line.length == 1)
		     line		= '0' + line;
		var idirElt		= form.elements['IDIR' + line];
		if (idirElt)
		    idirElt.value	= idir;
		else
		    alert("CensusForm.js: idirFeedback: " +
				  "could not find form.elements['IDIR" + line + "']");
		var findButton		= form.elements["doIdir" + line];
		if (findButton)
		{
		    while(findButton.hasChildNodes())
		    {	// remove contents of cell
				findButton.removeChild(findButton.firstChild);
		    }	// remove contents of cell
		    findButton.appendChild(document.createTextNode("Show"));
		}
    }		// loop through all matched lines
    }		// if false
}		// function idirFeedback
