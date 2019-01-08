/************************************************************************
 *  commonMarriage.js													*
 *																		*
 *  Javascript code to implement dynamic functionality shared between	*
 *  the pages editMarriages.php and editParents.php.					*
 *																		*
 *  History:															*
 *		2012/01/13		split off from editMarriage.js and				*
 *						editParents.js									*
 *						change class names								*
 *						change orderChildren.php to orderChildrenXml.php*
 *						change updateMarriage.php to					*
 *						updateMarriageXml.php							*
 *						add eventFeedback method to encapsulate updates	*
 *						to this form as a result of user actions in the	*
 *						editEvent form.									*
 *						all buttons use id= rather than name= to avoid a*
 *						problem with IE passing their values			*
 *						support updating all fields of Family			*
 *						record											*
 *		2012/02/25		change ids of fields in marriage list to contain*
 *						IDMR instead of row number						*
 *						support mouseover for edit and					*
 *						detach child buttons							*
 *		2012/05/29		identify child row by IDCR instead of IDIR to	*
 *						handle the same child appearing more than once	*
 *		2012/09/19		invoke editIndivid.php with the IDCR value		*
 *						rather than the IDMR							*
 *		2012/10/07		only enable edit spouse buttons if IDIR not		*
 *						equal zero										*
 *						define constants for event types				*
 *		2012/10/16		fix bugs handling feedback						*
 *		2012/11/20		change implementation of passing parameters		*
 *						to the script deleteEventXml.php				*
 *						completely lay out marriage using javascript to	*
 *						eliminate duplication of functionality with PHP	*
 *						always display a marriage						*
 *		2013/01/20		use encodeURI on surname passed to				*
 *						chooseIndivid.php								*
 *		2013/03/04		handle additional field names that support		*
 *						changing names and dates of children			*
 *		2013/03/21		sort children by date within the display		*
 *						without updating the database until the			*
 *						marriage is saved								*
 *		2013/03/23		child management buttons now just change		*
 *						appearance and database is updated only when	*
 *						marriage is updated								*
 *		2013/05/02		if the wife or mother in a family does not have	*
 *						a surname, the given name is qualified with the	*
 *						husband's name									*
 *		2013/05/09		bug in updating marriage ended date				*
 *		2013/05/20		add support for never married fact and			*
 *						no children fact								*
 *						adding row if necessary to display the value,	*
 *						and applying new value from eventFeedback		*
 *		2013/05/29		disable edit spouse button for individual for	*
 *						which the marriage was invoked, since that		*
 *						individual's edit dialog should still be open.	*
 *		2013/07/31		chooseIndivid.php now passes birthsd value		*
 *						for child										*
 *		2013/08/02		restore button functions on changed child		*
 *		2013/09/23		notify opener if marriage deleted and 			*
 *						no marriages left								*
 *		2013/10/15		if the user modified the name of a child before	*
 *						clicking on the "Edit Child" button, pass the	*
 *						new name										*
 *		2013/10/25		also pass changes to the birth and death date	*
 *						when editing an existing child					*
 *		2013/12/12		display birth sort dates of spouses				*
 *		2014/01/01		clean up comment blocks							*
 *						remove unused function validateMarrForm			*
 *						remove unused function resetMarrForm			*
 *						merge function addChildRow into addChildToPage	*
 *						which is the only function that called it		*
 *		2014/02/04		format of XML response to get family changed	*
 *						function addChild renamed to addExistingChild	*
 *						to clarify its purpose							*
 *		2014/02/25		The feedback method for editIndivid to update	*
 *						a child is made a method of the row containing	*
 *						the child, rather than the table of children.	*
 *						This makes it consistent with how feedback		*
 *						for the parents is handled.						*
 *						Each child row, and each input field in the		*
 *						child row, is now identified by the position	*
 *						of the child as displayed on the page.  This 	*
 *						is to permit all child rows to be identified	*
 *						in the same way regardless of whether the row	*
 *						is backed by an instance of Child and/or		*
 *						an instance of Person.  This eliminates			*
 *						the need for there to be separate templates		*
 *						depending upon whether the Child record			*
 *						has been created, and allows for simplified		*
 *						addition of children.							*
 *						Remove dependencies upon layout using tables	*
 *						Consolidate support for feedback from			*
 *						editIndivid.php by using the same style of		*
 *						feedback routine for any individual in the		*
 *						family											*
 *						validate dates, expand locations				*
 *						fix bug in sorting children after add existing	*
 *						simplify addition of children					*
 *						use same internal field names for both			*
 *						editMarriages.php and editParents.php			*
 *		2014/03/14		set gender on name change of existing children	*
 *						not just those added by the user				*
 *		2014/06/02		pass idcr to editIndivid for a child			*
 *		2014/06/16		do not update the family and close the dialog	*
 *						if there is an edit child window open			*
 *						because this caused 2 copies of the child to	*
 *						be defined, one from the edit child window		*
 *						and one from the default family update action	*
 *		2014/07/15		only prevent saving marriage once for open		*
 *						child edit windows, and use popupAlert			*
 *		2014/07/16		better support for checking for open child		*
 *						windows											*
 *		2014/07/19		if not opened as a dialog go back to previous	*
 *						page instead of closing the window				*
 *		2014/09/12		remove use of obsolete selectOptByValue			*
 *		2014/09/27		deleteMarriage.php script renamed to			*
 *						deleteMarriageXml.php							*
 *		2014/10/02		prompt for confirmation before deleting an		*
 *						event.											*
 *		2014/10/10		child windows set to position on top of current	*
 *						if husband's name changed 						*
 *						then change married names as well				*
 *		2014/10/16		popup a loading indicator while waiting for		*
 *						family record to be retrieved from the server	*
 *		2014/11/15		missing function to reorder events				*
 *		2014/11/16		rename function marrAdd to addFamily			*
 *						rename function marrEdit to editFamily			*
 *						refresh to switch to new family rather than AJAX*
 *						support clicking on details for event when		*
 *						family has not been saved to database yet		*
 *		2014/11/28		if the user updates the date of birth of a		*
 *						child, including adding a date of birth for		*
 *						a new child, the sort version of the date		*
 *	            		is set so Order Children by Birth Date works	* 
 *		2014/12/22		script renamed to orderMarriagesByDateXml.php	*
 *		2015/02/01		get event type text from web page				*
 *		2015/02/02		disable and enable edit buttons when editing	*
 *						family member in <iframe>						*
 *		2015/02/10		open all child windows in left hand frame		*
 *		2015/02/19		support extended response from addFamilyXml.php	*
 *						comments still referred to orderMarriagesByDate	*
 *		2015/02/23		openFrame returns instance of Window			*
 *						track open windows for spouse or child to		*
 *						prevent updating family while open				*
 *						identify open windows by title					*
 *		2015/02/28		call checkfunc for change birth date			*
 *		2015/03/02		add alert for bad return from addChildToPage	*
 *		2015/03/06		if IDCR is passed to method changeChild then	*
 *						update the field in the form.  This permits		*
 *						the script editIndivid.php to add the child		*
 *						and present the relationship to parents fields	*
 *						for an added child								*
 *		2015/03/20		birthsd was not passed to addChildToPage for	*
 *						addition of new child							*
 *		2015/03/25		pass debug flag to editIndivid.php				*
 *						when updating family member						*
 *		2015/04/17		use closeFrame to close edit dialog when there	*
 *						are not families left to display				*
 *		2015/04/28		do not permit editing the child for whom a set	*
 *						of parents is being created or edited			*
 *		2015/05/27		use absolute URLs for AJAX						*
 *		2015/06/19		add method childKeyDown which handles key		*
 *						strokes in input fields in a child row			*
 *						so Enter key moves to first field of next row	*
 *						or adds another child, and up and down arrow	*
 *						move the focus up and down a column				*
 *						make the notes field a rich-text editor			*
 *		2015/08/12		add support for tree divisions of database		*
 *		2015/08/21		fix failure if delete currently displayed		*
 *						marriage and there is at least one marriage		*
 *						left, because deleted family still displayed	*
 *		2015/11/08		parameter removed from function marrDel			*
 *		2016/01/27		value of field CGender in a child was not		*
 *						changed when the sex of the child was changed	*
 *						by editIndivid.php								*
 *		2016/05/08		prevent loop creating marriages					*
 *		2016/05/09		correct output									*
 *		2016/05/31		use common function dateChanged					*
 *		2017/01/09		put "Wifeof" comment into surname field			*
 *		2017/09/08		section not defined when calling				*
 *						editIndivid::marriageUpdated					*
 *						handle addition of first family when calling	*
 *						editIndivid::marriageUpdated					*
 *		2018/05/16		update all child IDIR and IDCR fields on		*
 *						receipt of update of family so that newly		*
 *						created children are properly represented		*
 *						Among other things this permits sorting			*
 *						children by birth date without requiring that	*
 *						the user manually edit new children first		*
 *		2018/10/30      use Node.textContent rather than getText        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *  constants															*
 *																		*
 *  Note that Microsoft Internet Explorer does not support the const	*
 *  keyword prior to IE 11, therefore these constants are declared		*
 *  as variables.														*
 ************************************************************************/

var CHILD_PREFIX		= "child";
var CHILD_PREFIX_LEN		= CHILD_PREFIX.length;
var EDIT_CHILD_PREFIX		= "editChild";
var EDIT_CHILD_PREFIX_LEN	= EDIT_CHILD_PREFIX.length;
var DELETE_PREFIX		= "Delete";
var DELETE_PREFIX_LEN		= DELETE_PREFIX.length;
var DELETE_EVENT_PREFIX		= "DelEvent";
var DELETE_EVENT_PREFIX_LEN	= DELETE_EVENT_PREFIX.length;
var EDIT_EVENT_PREFIX		= "EditEvent";
var EDIT_EVENT_PREFIX_LEN	= EDIT_EVENT_PREFIX.length;

/************************************************************************
 *  "constants" for event types											*
 *  These definitions must match those in the PHP file					*
 *  includes/LegacyCitition.php											*
 ************************************************************************
 *		IDIME points to Marriage Record tblMR.idmr						*
 ************************************************************************/
var STYPE_LDSS		= 18;	// Sealed to Spouse
var STYPE_NEVERMARRIED	= 19;	// This individual never married 
var STYPE_MAR		= 20;	// Marriage	
var STYPE_MARNOTE	= 21;	// Marriage Note
var STYPE_MARNEVER	= 22;	// Never Married	     
var STYPE_MARNOKIDS	= 23;	// This couple had no children
var STYPE_MAREND	= 24;	// Marriage ended
				
/************************************************************************
 *		IDIME points to Event Record tblER.ider							*
 ************************************************************************/
var STYPE_EVENT		= 30;	// Individual Event
var STYPE_MAREVENT	= 31;	// Marriage Event

/************************************************************************
 * specify the style for tinyMCE editing								*
 ************************************************************************/
tinyMCE.init({
		mode			: "textareas",
		theme			: "advanced",
		plugins 		: "spellchecker,advhr,preview", 

		// Theme options - button# indicates the row# only
		theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,fontselect,fontsizeselect,formatselect",
		theme_advanced_buttons2 : "cut,copy,paste,|,bullist,numlist,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,|,forecolor,backcolor",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,
		forced_root_block	: false,
		forced_root_block	: false,
		content_css		: "/styles.css",
		onchange_callback	: "changeOccupation"

});


/************************************************************************
 *  childWindows														*
 *																		*
 *  This array keeps track of all child windows opened by the current	*
 *  edit family dialog.													*
 ************************************************************************/
var	childWindows	= [];

/************************************************************************
 *  editChildButtons													*
 *																		*
 *  This array keeps track of all editChild buttons in the current		*
 *  dialog.																*
 ************************************************************************/
var	editChildButtons	= [];

/************************************************************************
 *  pendingButton														*
 *																		*
 *  This is a reference to an instance of <button> that should be		*
 *  "clicked" when the family is updated.								*
 *  This permits deferring functionality until after the Family record	*
 *  is updated in the database.											*
 ************************************************************************/
var	pendingButton	= null;

/************************************************************************
 *  function clickPref													*
 *																		*
 *  This method is called when the user clicks on a preferred marriage	*
 *  checkbox to identify a new preferred marriage.						*
 *																		*
 *  Input:																*
 *		this	<input name='Pref9999' type='checkbox'>					*
 ************************************************************************/
function clickPref()
{
    if (this.checked)
    {		// the current marriage is preferred
		var idmr		= this.name.substring(4);

		// notify the invoking page that the preferred marriage has changed
		var	opener	= null;
		if (window.frameElement && window.frameElement.opener)
		    opener	= window.frameElement.opener;
		else
		    opener	= window.opener;
		if (opener)
		    opener.document.indForm.setIdmrPref(idmr);
    
		var form	= this.form;
		var formElts	= form.elements;
		for (var i = 0; i < formElts.length; ++i)
		{
		    var element	= formElts[i];
    
		    // uncheck all other checkboxes in the preference set
		    if ((element.name.substring(0,4) == 'Pref') && (element != this))
				element.checked		= false;
		}		// loop through all elements
    }		// the current marriage is preferred
    else
    {		// do not permit turning the preference off
		this.checked	= true;
    }		// do not permit turning the preference off
    return true;
}	// function clickPref

/************************************************************************
 *  function editFamily													*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about a specific marriage.								*
 *																		*
 *  Input:																*
 *		this	<button id='Edit9999'>									*
 ************************************************************************/
function editFamily()
{
    // disable buttons in the main form until update is complete
    var	idmr		= this.id.substring(4);
    var	href		= location.href;
    if (href.indexOf("?") >= 0)
		location.href	= location.href + "&idmr=" + idmr;
    else
		location.href	= location.href + "?idmr=" + idmr;
}		// function editFamily

/************************************************************************
 *  function insertSealedRow											*
 *																		*
 *  This method is called to insert a row to represent the sealed to	*
 *  spouse event into the displayed page.								*
 ************************************************************************/
function insertSealedRow(parms)
{
    var	newRow		= createFromTemplate('SealedRow$temp',
								     parms,
								     null);
    var	marrRow		= document.getElementById("MarriageRow");
    var	tbody		= marrRow.parentNode;
    var	nextRow		= marrRow.nextSibling;
    tbody.insertBefore(newRow, nextRow);
    var	button		= document.getElementById('EditIEvent18');
    button.onclick	= editIEvent;
    button		= document.getElementById('DelIEvent18');
    button.onclick	= delIEvent;
}	// function insertSealedRow

/************************************************************************
 *  function insertEndedRow												*
 *																		*
 *  This method is called to insert a row to represent the marriage		*
 *  ended event into the displayed page.								*
 ************************************************************************/
function insertEndedRow(parms)
{
    var	newRow	= createFromTemplate('EndedRow$temp',
							     parms,
							     null);
    var	nextRow		= document.getElementById("AddEventRow");
    var	tbody		= nextRow.parentNode;
    tbody.insertBefore(newRow, nextRow);
    var	button		= document.getElementById('EditIEvent24');
    button.onclick	= editIEvent;
    button		= document.getElementById('DelIEvent24');
    button.onclick	= delIEvent;
}	// function insertEndedRow

/************************************************************************
 *  function gotFamily													*
 *																		*
 *  This method is called when the XML document representing			*
 *  a family is retrieved from the server.								*
 *																		*
 *  Parameters:															*
 *		xmlDoc			Family record as an XML document				*
 ************************************************************************/
function gotFamily(xmlDoc)
{
    var	notMarriedChecked	= '';
    var	noChildrenChecked	= '';
    var	noChildrenDisabled	= false;

    // some actions depend upon the value of the idir parameter passed
    // to the script
    var	idir	= 0;
    if (args['idir'])
		idir	= args['idir'] - 0;
    else
    if (args['id'])
		idir	= args['id'] - 0;

    hideLoading();	// hide loading indicator
    // get information from XML document
    if (xmlDoc.documentElement)
    {		// XML document
		var	root	= xmlDoc.documentElement;
		if (root.tagName == 'added')
		{		// format with enclosing information
		    for (var i = 0; i < root.childNodes.length; i++)
		    {		// loop through all children
				var	node	= root.childNodes[i];
				if (node.tagName == 'family')
				{	// <family>
				    root	= node;
				    break;
				}	// <family>
		    }		// loop through all children
		}		// format with enclosing information
		if (root.tagName == 'tblMR' || root.tagName == 'family')
		{		// correctly formatted response
		    //alert("commonMarriage.js: gotFamily: root=" + tagToString(root));
		    var	childTable	= document.getElementById('children');
		    var	eventSet	= document.getElementById('EventSet');
		    var	famForm		= document.famForm;

		    for (var i = 0; i < root.childNodes.length; i++)
		    {		// loop through all children
				var	node	= root.childNodes[i];
				if (node.nodeType == 1)
				{	// element Node
				    var	value	= node.textContent;

				    switch(node.nodeName)
				    {	// take action depending upon tag name
						case 'idmr':
						{	
						    famForm.idmr.value	= value;
						    break; 
						}	// idmr

						case 'idirhusb':
						{
						    famForm.IDIRHusb.value	= value;
						    document.getElementById('editHusb').disabled =
								(value == 0) || (value == idir);
						    break; 
						}	// idmr

						case 'husbsurname':
						{
						    famForm.HusbSurname.value	= value;
						    break; 
						}	// husbsurname

						case 'husbgivenname':
						{
						    famForm.HusbGivenName.value	= value;
						    break; 
						}	// husbgivenname

						case 'husbbirthsd':
						{
						    famForm.HusbBirthSD.value	= value;
						    break; 
						}	// husbbirthsd

						case 'idirwife':
						{
						    famForm.IDIRWife.value	= value;
						    document.getElementById('editWife').disabled =
								(value == 0) || (value == idir);
						    break; 
						}	// idirwife

						case 'wifesurname':
						{
						    famForm.WifeSurname.value	= value;
						    break; 
						}	// wifesurname

						case 'wifegivenname':
						{
						    famForm.WifeGivenName.value	= value;
						    break; 
						}	// wifegivenname

						case 'wifebirthsd':
						{
						    famForm.WifeBirthSD.value	= value;
						    break; 
						}	// wifebirthsd

						case 'mard':
						{
						    famForm.MarD.value		= value;
						    break; 
						}	// marriage date

						case 'marloc':
						{
						    famForm.MarLoc.value	= value;
						    break; 
						}	// marloc

						case 'marendd':
						{
						    if (famForm.MarEndD)
						    {	// event already displayed
							famForm.MarEndD.value	= value;
						    }	// event already displayed
						    else
						    if (value.length > 0)
						    {	// need to add ended event
							var	parms	= {"eventd"	: value,
									   "temp"	: ""};  
							insertEndedRow(parms)
						    }	// need to add ended event
						    break; 
						}	// marendd

						case 'seald':
						{
						    if (famForm.SealD)
						    {	// event already displayed
							famForm.SealD.value	= value;
						    }	// event already displayed
						    else
						    if (value.length > 0)
						    {	// need to add sealed to spouse line
							var	parms	= {"eventd"	: value,
									   "eventloc"	: "",
									   "idtrseal"	: "1",
									   "temp"	: ""};  
							insertSealedRow(parms)
						    }	// need to add sealed to spouse line
						    break; 
						}	// seald

						case 'idtrseal':
						{
						    if (famForm.IDTRSeal)
						    {	// event already displayed
							famForm.IDTRSeal.value	= value;
						    }	// event already displayed
						    else
						    if (/\d+/.test(value) &&
							value > '1')
						    {	// need to add sealed to spouse line
							var	parms	= {"eventd"	: "",
									   "eventloc"	: "",
									   "idtrseal"	: value,
									   "temp"	: ""};  
							insertSealedRow(parms)
							famForm.IDTRSeal.value	= value;
						    }	// need to add sealed to spouse line
						    break; 
						}	// idtrseal

						case 'idms':
						{
						    famForm.IDMS.value	= value;
						    break; 
						}	// idms

						case 'marriednamerule':
						{
						    famForm.MarriedNameRule.value	= value;
						    break; 
						}	// marriednamnerule

						case 'notes':
						{
						    tinyMCE.get('Notes').setContent(value);
						    break; 
						}	// notes

						case 'notmarried':
						{	// not married indicator
						    if (value > 0)
							addNotMarriedRow();
						    break; 
						}	// not married indicator

						case 'nochildren':
						{	// no children indicator
						    if (value > 0)
							addNoChildrenRow();
						    break; 
						}	// no children indicator

						case 'children':
						{
						    var numChildren	= node.getAttribute('count');
						    if (numChildren > 0)
						    {		// at least one child
							noChildrenChecked	= false;
							noChildrenDisabled	= true;
						    }		// at least one child
						    else
							noChildrenDisabled	= false;
						    addChildrenFromXml(node,
									childTable);
						    break;
						}	// children tag

						case 'events':
						{
						    addEventsFromXml(node,
								     eventSet);
						    break;
						}	// events tag
				    }	// take action depending upon tag name
				}	// element Node
		    }		// loop through all first level children
		}		// correctly formatted response
		else
		    popupAlert('commonMarriage.js: gotFamily: ' + tagToString(root),
						this);
    }		// XML document
    else
		popupAlert('commonMarriage.js: gotFamily: ' + xmlDoc,
				   this);
}	// function gotFamily

/************************************************************************
 *  function addNotMarriedRow											*
 *																		*
 *  Insert a row to display the never married fact just before			*
 *  the row to display the date and location of the marriage.			*
 *  This is called by functions gotMarried and eventFeedback.			*
 ************************************************************************/
function addNotMarriedRow()
{
    var	famForm	= document.famForm;
    if (famForm.NotMarried)
		return;		// already displayed

    var	parms	= {'temp'	: ''};
    var	newrow	= createFromTemplate('NotMarriedRow$temp',
							     parms,
							     null);
    var	nextRow	= document.getElementById('Marriage');
    if (nextRow === undefined)
		throw "commonMarriage.js: addNotMarriedRow: no element with id 'Marriage'";
    var	tbody	= nextRow.parentNode;
    tbody.insertBefore(newrow, nextRow);

    if (famForm.NotMarried)
		actMouseOverHelp(famForm.NotMarried);
    var	button	= document.getElementById('neverMarriedDetails');
    if (button)
    {
		actMouseOverHelp(button);
		button.onclick	= neverMarriedDetails;
    }
}		// function addNotMarriedRow

/************************************************************************
 *  function addNoChildrenRow											*
 *																		*
 *  Add a row to display the no children fact at the end of the form.	*
 ************************************************************************/
function addNoChildrenRow()
{
    var	famForm	= document.famForm;
    if (famForm.NoChildren)
		return;		// already displayed

    var	parms	= {'temp'	: ''};
    var	newrow	= createFromTemplate('NoChildrenRow$temp',
							     parms,
							     null);
    var	tbody	= document.getElementById('formBody');
    tbody.appendChild(newrow);

    if (famForm.NoChildren)
		actMouseOverHelp(famForm.NoChildren);
    var	button	= document.getElementById('noChildrenDetails');
    if (button)
    {
		actMouseOverHelp(button);
		button.onclick	= noChildrenDetails;
    }
}		// function addNoChildrenRow

/************************************************************************
 *  function addChildrenFromXml											*
 *																		*
 *  Input:																*
 *		node			<children> tag from XML							*
 *		childTable		<table id='children'> from page					*
 ************************************************************************/
function addChildrenFromXml(node,
						    childTable)
{
    // cleanup existing display
    var	tbody	= document.getElementById('childrenBody');
    var	child;
    while((child = tbody.firstChild) != null)
    {		// remove all children
		tbody.removeChild(child);
    }		// remove all children

    // add children from XML database record
    var	rownum		= 1;
    for(child = node.firstChild;
		child;
		child = child.nextSibling)
    {		// loop through child tags in XML
		// extract parameters from XML element
		if (child.nodeType == 1 && child.nodeName == 'child')
		{	// element node
		    var	parms	= getParmsFromXml(child);
		    if (parms.gender == 0 || parms.gender == 'M')
				parms.gender	= 'male';
		    else
		    if (parms.gender == 1 || parms.gender == 'F')
				parms.gender	= 'female';
		    else
				parms.gender	= 'unknown';
		    parms.rownum	= parms.order;

		    // if the parms parameter is invalid throw an exception
		    if (parms.givenname === undefined)
		    {
				var msg	= "";
				for(parm in parms) { msg += parm + "='" + parms[parm] + "',"; }
				throw "commonMarriage.js: addChildrenFromXml: parms={" + msg +  
						"} child=" + tagToString(child);
		    }
    
		    childTable.addChildToPage(parms,
							      false);
		}	// element node
    }		// loop through children
}		// addChildrenFromXml

/************************************************************************
 *  addEventsFromXml														*
 *																		*
 *  Add rows to the display to represent family events from tblER.		*
 *																		*
 *  Input:																*
 *		node				<events> tag from XML								*
 *		fieldSet		<fieldset id='EventSet'> from page				*
 ************************************************************************/
function addEventsFromXml(node,
						  fieldSet)
{
    var	form		= document.famForm;
    // cleanup existing display
    var	msg		= "";
    for(var member in fieldSet)
		msg	+= member + ",";
    var row 		= fieldSet.firstChild;
    msg			= "";
    for(var member in row)
		msg	+= member + ",";
    while(row)
    {
		var	nextChild	= row.nextSibling;
		if (row.id && row.id.substring(0,8) == 'EventRow')
		    tbody.removeChild(row);
		row	= nextChild;
    }		// loop through existing rows in table

    // find the position at which to add new event rows
    var	nextRow	= document.getElementById('EndedRow');
    if (nextRow === undefined || nextRow === null)
		nextRow	= document.getElementById('AddEventRow');

    // add events from database record
    for(var child = node.firstChild;
		child;
		child = child.nextSibling)
    {	// loop through children
		// extract parameters from XML element
		if (child.nodeType == 1)
		{	// element node
		    var	parms	= getParmsFromXml(child);
		    var	typeText	= 'Unknown ' + idet;
		    var	eventTextElt	= document.getElementById('EventText' + idet);
		    if (eventTextElt)
		    {				// have element from web page
				typeText	= eventTextElt.innerHTML.trim() + ':';
				typeText	= typeText.substring(0,1).toUpperCase() +
									  typeText.substring(1);
		    }				// have element from web page
		    parms['type']	= typeText;
		    var descn	= parms['description'];
		    if (descn.length > 0)
		    {
				descn	= descn.substring(0,1).toUpperCase() + 
						  descn.substring(1);
				parms['description']	= descn;
		    }
		     
		    var	newrow		= createFromTemplate('EventRow$ider',
									     parms,
									     null);
		    fieldSet.insertBefore(newrow, nextRow);

		    // add handlers for added buttons
		    var	ider		= parms['ider'];
		    var eltName		= "citType" + ider;
		    var element		= form.elements[eltName];
		    actMouseOverHelp(element);

		    eltName		= "Date" + ider;
		    element		= form.elements[eltName];
		    element.abbrTbl	= MonthAbbrs;
		    element.onchange	= dateChanged;
		    element.checkfunc	= checkDate;
		    actMouseOverHelp(element);

		    eltName		= "EventLoc" + ider;
		    element		= form.elements[eltName];
		    element.abbrTbl	= evtLocAbbrs;
		    element.onchange	= locationChanged;
		    actMouseOverHelp(element);

		    eltName		= "EditEvent" + ider;
		    var button		= document.getElementById(eltName);
		    button.onclick	= editEvent;    
		    actMouseOverHelp(button);

		    eltName		= "DelEvent" + ider;
		    button		= document.getElementById(eltName);
		    button.onclick	= delEvent;    
		    actMouseOverHelp(button);

		}	// element node
    }		// loop through children
}		// addEventsFromXml

/************************************************************************
 *  noFamily																*
 *																		*
 *  This method is called if there is no family response				*
 *  from the server.														*
 ************************************************************************/
function noFamily()
{
    alert('commonMarriage.js: noFamily: Unable to obtain family record from server');
}		// noFamily

/************************************************************************
 *  marrDel																*
 *																		*
 *  This method is called when the user requests to delete				*
 *  information about a specific marriage.								*
 *																		*
 *  Input:																*
 *		this		<button id='Delete9999'>								*
 ************************************************************************/
function marrDel()
{
    var	idmr		= this.id.substring(DELETE_PREFIX_LEN);
    var parms		= { "idmr"	: idmr};

    var idirElement	= document.getElementById('idir');
    if (idirElement)
		parms['idir']	= idirElement.value;
    var childElement	= document.getElementById('child');
    if (childElement)
		parms['child']	= childElement.value;

    // invoke script to update Event and return XML result
    popupLoading(this);	// display loading indicator
    HTTP.post('/FamilyTree/deleteMarriageXml.php',
		      parms,
		      gotDelMarr,
		      noDelMarr);
}	// marrDel

/************************************************************************
 *  gotDelMarr																*
 *																		*
 *  This method is called when the XML document representing				*
 *  a successful delete marriage is retrieved from the database.		*
 *																		*
 *  Parameters:																*
 *		xmlDoc				response as an XML document						*
 *  						with the following structure:						*
 *																		*
 *		<deleted>														*
 *		  <parms>														*
 *		    <idmr> requested IDMR to delete </idmr>						*
 *		  </parms>														*
 *		  <cmd> an SQL DELETE command </cmd> ... or						*
 *		  <msg> error message </msg>										*
 *		</deleted>														*
 ************************************************************************/
function gotDelMarr(xmlDoc)
{
    hideLoading();	// hide loading indicator
    if (xmlDoc.documentElement)
    {		// XML document
		var	root	= xmlDoc.documentElement;
		if (root.tagName == "deleted")
		{		// correctly formatted response
		    var msgs	= root.getElementsByTagName("msg");
		    if (msgs.length == 0)
		    {		// no errors detected
				var parms	= root.getElementsByTagName("parms");
				var idmrtag	= parms[0].getElementsByTagName("idmr");
				var idmr	= idmrtag[0].textContent.trim();
				var row		= document.getElementById("marriage" + idmr);
				var section	= row.parentNode;
				section.removeChild(row);
				if (section.rows.length < 1)
				{			// deleted last marriage
				    // notify the opener (editIndivid.php)
				    // that there are no marriages left
				    var	opener	= null;
				    if (window.frameElement && window.frameElement.opener)
						opener	= window.frameElement.opener;
				    else
						opener	= window.opener;
				    if (opener && opener.document.indForm)
				    {
						try {
						    opener.document.indForm.marriageUpdated(0,
										section.rows.length);
						} catch(e) { alert("commonMarriage.js: 928 e=" + e); }
				    }
				    if (opener)
						closeFrame();
				    else
						window.history.back();
				}			// deleted last marriage
				else
				{			// at least one marriage left
				    var famForm	= document.famForm;
				    var	currIdmr= famForm.idmr.value;
				    if (idmr == currIdmr)
				    {			// deleted currently displayed family
						var row	= section.rows[0];
						idmr	= row.id.substring(8);
						var edit= document.getElementById('Edit' + idmr);
						edit.onclick();
				    }			// deleted currently displayed family
				}			// at least one marriage left
		    }		// no errors detected
		    else
		    {		// report message
				alert("commonMarriage.js: gotDelMarr: " + tagToString(msgs[0]));
		    }		// report message
		}		// correctly formatted response
		else
		    alert("commonMarriage.js: gotDelMarr: " + tagToString(root));
    }		// XML document
    else
		alert("commonMarriage.js: gotDelMarr: " + xmlDoc);
}		// gotDelMarr

/************************************************************************
 *  noDelMarr																*
 *																		*
 *  This method is called if there is no delete marriage response		*
 *  file.																*
 ************************************************************************/
function noDelMarr()
{
    alert('commonMarriage.js: noDelMarr: ' +
		  'No response from server to deleteMarriageXml.php');
}		// noDelMarr

/************************************************************************
 *  addFamily																*
 *																		*
 *  This method is called when the user requests to add						*
 *  a new family to an individual										*
 *																		*
 *  Input:																*
 *		this		<button id='Add'>										*
 ************************************************************************/
function addFamily()
{
    location.href	= location.href + "&new=Y";
}	// addFamily

/************************************************************************
 *  marrReorder																*
 *																		*
 *  This method is called when the user requests to reorder				*
 *  marriages by date.														*
 *																		*
 *  Input:																*
 *		this		<button id='Reorder'>										*
 ************************************************************************/
function marrReorder()
{
    var	form		= document.indForm;
    var	idir		= form.idir.value;
    var	sex		= form.sex.value;

    var parms		= {
				"idir"		: idir,
				"sex"		: sex};

    // invoke script to update Event and return XML result
    popupLoading(this);	// display loading indicator
    HTTP.post('/FamilyTree/orderMarriagesByDateXml.php',
		      parms,
		      gotReorderMarr,
		      noReorderMarr);
}	// marrReorder

/************************************************************************
 *  gotReorderMarr														*
 *																		*
 *  This method is called when the XML document representing				*
 *  a successful marriage reorder is retrieved from the database.		*
 *																		*
 *  Parameters:																*
 *		xmlDoc				response from orderMarriagesByDateXml.php as an		*
 *						XML document										*
 ************************************************************************/
function gotReorderMarr(xmlDoc)
{
    window.location.reload();
}		// gotReorderMarr

/************************************************************************
 *  noReorderMarr														*
 *																		*
 *  This method is called if there is no reorder marriage response		*
 *  file.																*
 ************************************************************************/
function noReorderMarr()
{
    alert('commonMarriage.js: noReorderMarr: ' +
		  'script orderMarriagesByDateXml.php not found on server');
}		// noReorderMarr

/************************************************************************
 *  marriageDetails														*
 *																		*
 *  This method is called when the user requests to edit the 				*
 *  details, including citations, for the marriage event.				*
 *																		*
 *  Input:																*
 *		this		<button id='marriageDetails'> element 						*
 ************************************************************************/
function marriageDetails()
{
    editEventMar(STYPE_MAR, this);
}

/************************************************************************
 *  noteDetails																*
 *																		*
 *  This method is called when the user requests to edit the 				*
 *  details, including citations, for the note event.						*
 *																		*
 *  Input:																*
 *		this		<button id='noteDetails'> element 						*
 ************************************************************************/
function noteDetails()
{
    editEventMar(STYPE_MARNOTE, this);
}

/************************************************************************
 *  noChildrenDetails														*
 *																		*
 *  This method is called when the user requests to edit the 				*
 *  details, including citations, for the No Children fact.				*
 *																		*
 *  Input:																*
 *		this		<button id='noChildrenDetails'> element						*
 ************************************************************************/
function noChildrenDetails()
{
    editEventMar(STYPE_MARNOKIDS, this);
}

/************************************************************************
 *  neverMarriedDetails														*
 *																		*
 *  This method is called when the user requests to edit the 				*
 *  details, including citations, for the never married fact				*
 *																		*
 *  Input:																*
 *		this		<button id='neverMarriedDetails'> element				*
 ************************************************************************/
function neverMarriedDetails()
{
    editEventMar(STYPE_MARNEVER, this);
}

/************************************************************************
 *  changeHusb																*
 *																		*
 *  This is a feedback method from a sub dialog to change the displayed *
 *  information about the husband in a family.								*
 *																		*
 *  Parameters:																*
 *		this				<div id='Husb'>										*
 *		parms				object with at least the following members		*
 *		    idir		IDIR of husband as individual						*
 *		    givenname		new given name of husband						*
 *		    surname		new surname of husband								*
 *		    birthd		new birth date of husband						*
 *		    deathd		new death date of husband						*
 ************************************************************************/
function changeHusb(parms)
{
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {			// enable all editChild buttons
		editChildButtons[ib].disabled	= false;
    }			// enable all editChild buttons

    for(var iw = 0; iw < childWindows.length; iw++)
    {
		var cw		= childWindows[iw];
		var cloc	= cw.location;
		if (cloc && cloc.search.indexOf('rowid=Husb') >= 0)
		{
		    childWindows.splice(iw, 1);
		    break;
		}
		else
		alert("changeHusb: cw=" + cw.constructor.name);
    }

    var	form				= document.famForm;
    if (form.IDIRHusb)
		form.IDIRHusb.value		= parms['idir'];
    if (form.HusbSurname)
		form.HusbSurname.value		= parms['surname'];
    if (form.HusbMarrSurname)
		form.HusbMarrSurname.value	= parms['surname'];
    if (form.WifeMarrSurname &&
		form.MarriedNameRule &&
		form.MarriedNameRule.value == '1')
		form.WifeMarrSurname.value	= parms['surname'];
    if (form.HusbGivenName)
		form.HusbGivenName.value	= parms['givenname'];
    if (form.HusbBirth)
		form.HusbBirth.value		= parms['birthd'];
    if (form.HusbDeath)
		form.HusbDeath.value		= parms['deathd'];
    if (form.WifeMarrSurname &&
		form.MarriedNameRule && form.MarriedNameRule.value == '1')
		form.WifeMarrSurname.value	= parms['surname'];
    document.getElementById('editHusb').disabled	= false;
    document.getElementById('chooseHusb').disabled	= false;
    document.getElementById('createHusb').disabled	= false;
}		// changeHusb

/************************************************************************
 *  changeWife																*
 *																		*
 *  This is a feedback method from a sub dialog to change the displayed *
 *  information about the wife in a family.								*
 *																		*
 *  Parameters:																*
 *		this				<div id='Wife'>										*
 *		parms				object with at least the following members		*
 *		    idir		IDIR of wife as individual						*
 *		    givenname		new given name of wife								*
 *		    surname		new surname of wife								*
 *		    birthd		new birth date of wife								*
 *		    deathd		new death date of wife								*
 ************************************************************************/
function changeWife(parms)
{
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {			// enable all editChild buttons
		editChildButtons[ib].disabled	= false;
    }			// enable all editChild buttons

    for(var iw = 0; iw < childWindows.length; iw++)
    {
		var cw		= childWindows[iw];
		var cloc	= cw.location;
		if (cloc && cloc.search.indexOf('rowid=Wife') >= 0)
		{
		    childWindows.splice(iw, 1);
		    break;
		}
		else
		    alert("changeWife: cw=" + cw.constructor.name)
    }

    var	form				= document.famForm;
    if (form.IDIRWife)
		form.IDIRWife.value		= parms['idir'];
    if (form.WifeSurname)
		form.WifeSurname.value		= parms['surname'];
    if (form.WifeGivenName)
		form.WifeGivenName.value	= parms['givenname'];
    if (form.WifeBirth)
		form.WifeBirth.value		= parms['birthd'];
    if (form.WifeDeath)
		form.WifeDeath.value		= parms['deathd'];
    document.getElementById('editWife').disabled	= false;
    document.getElementById('chooseWife').disabled	= false;
    document.getElementById('createWife').disabled	= false;
}		// changeWife`

/************************************************************************
 *  changeChild																*
 *																		*
 *  Change the displayed information about a child.						*
 *  This is a callback method of <div id='child$rownum'>				*
 *  that is called by editIndivid.js to update the displayed information*
 *  about a child in the summary list on this page.						*
 *																		*
 *  Parameters:																*
 *		this				<div id='child$rownum'>								*
 *		parms				object with at least the following members		*
 *		    idir		IDIR of child as individual						*
 *		    idcr		IDCR of child relationship record				*
 *		    givenname		new given name of child								*
 *		    surname		new surname of child								*
 *		    birthd		new birth date of child								*
 *		    deathd		new death date of child								*
 *		    gender		new gender of child: "male" or "female"				*
 *		    gender		new sex code of child: 0 or 1						*
 ************************************************************************/
function changeChild(parms)
{
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {			// enable all editChild buttons
		editChildButtons[ib].disabled	= false;
    }			// enable all editChild buttons

    for(var iw = 0; iw < childWindows.length; iw++)
    {
		var cw		= childWindows[iw];
		var cloc	= cw.location;
		
		if (cloc && cloc.search.indexOf('rowid=child') >= 0)
		{
		    childWindows.splice(iw, 1);
		    break;
		}
		else
		    alert("changeChild: cw=" + cw.constructor.name)
    }

    var	famForm		= document.famForm;
    var	row		= this;
    var	tableBody	= row.parentNode;
    var	rownum		= row.id.substring(CHILD_PREFIX_LEN);
    var	cIdir		= famForm.elements["CIdir" + rownum];
    var	cIdcr		= famForm.elements["CIdcr" + rownum];
    var	cGender		= famForm.elements["CGender" + rownum];
    var	cGiven		= famForm.elements["CGiven" + rownum];
    var	cSurname	= famForm.elements["CSurname" + rownum];
    var	cBirth		= famForm.elements["Cbirth" + rownum];
    var	cBirthsd	= famForm.elements["Cbirthsd" + rownum];
    var	cDeath		= famForm.elements["Cdeath" + rownum];
    var	cDeathsd	= famForm.elements["Cdeathsd" + rownum];

    var parmstr	= '';
    var	linkstr	= '{';
    for (var key in parms)
    {			// loop through parameters
		var	value		= parms[key];
		parmstr	+= linkstr + key + "='" + value + "'";
		linkstr	= ',';
		switch(key.toLowerCase())
		{
		    case 'idir':	// IDIR of individual
		    {
				row.idir		= value;
				if (cIdir)
				    cIdir.value		= value;
				break;
		    }

		    case 'idcr':	// IDCR of child relationship record
		    {
				row.idcr		= value;
				if (cIdcr)
				    cIdcr.value		= value;
				break;
		    }

		    case 'givenname':	// new given name of child
		    {
				if (cGiven)
				    cGiven.value		= value;
				break;
		    }

		    case 'surname':	// new surname of child	
		    {
				if (cSurname)
				    cSurname.value		= value;
				break;
		    }

		    case 'birthd':	// new birth date of child
		    {
				if (cBirth)
				    cBirth.value		= value;
				if (cBirthsd)
				    cBirthsd.value		= getSortDate(value);
				break;
		    }

		    case 'deathd':	// new death date of child
		    {
				if (cDeath)
				    cDeath.value		= value;
				if (cDeathsd)
				    cDeathsd.value	= getSortDate(value);
				break;
		    }

		    case 'gender':	// new gender of child: "male" or "female"
		    {
				if (cGiven)
				    cGiven.className	= value;
				if (cSurname)
				    cSurname.className	= value;
				if (cGender)
				{
				    if (value == 'male')
						cGender.value	= 0;
				    else
				    if (value == 'female')
						cGender.value	= 1;
				    else
						cGender.value	= 2;
				}
				break;
		    }

		    case 'sex':		// new sex code of child: 0 or 1
		    {
				if (cGender)
				    cGender.value	= value;
				break;
		    }

		}		// act on specific parm fields
    }			// loop through parameters
    return	row;
}		// changeChild

/************************************************************************
 *  eventFeedback														*
 *																		*
 *  This is a method of the form object that is called by the script		*
 *  editEvent.php to feedback changes to an event that should be		*
 *  reflected in this form.												*
 *																		*
 *  Parameters:																*
 *		this		<form> object												*
 *		parms		the values of fields from the editEvent.php dialog		*
 ************************************************************************/
function eventFeedback(parms)
{
    var	form		= this;
    var	type		= parseInt(parms['type']) - 0;

    // var msg	= "";
    // for(fn in parms)
    //     msg	+= fn + "='" + parms[fn] + "', ";
    // alert("commonMarriage.js: eventFeedback: parms=" + msg);

    // update field values in the current dialog based upon values
    // returned from the editEvent.php dialog
    switch(type)
    {		// source fields changed depend on type of event
		case STYPE_MAREVENT: // marriage event in tblER
		{		// marriage event
		    redisplayFamily();	// refresh to display
		    break;
		}		// marriage event in Event table

		case STYPE_MAR: // Marriage event in tblMR	
		{		// marriage event 
		    form.MarD.value	= parms['date'];
		    form.MarLoc.value	= parms['location'];
		    break;
		}		// marriage event

		case STYPE_MARNOTE: // Marriage Note
		{		// marriage event 
		    form.Notes.value	= parms['note'];
		    break;
		}		// marriage event

		case STYPE_MARNEVER:	// Never Married
		{
		    var	notMarried	= parms['notmarried'];
		    if (notMarried)
				addNotMarriedRow();
		    else
		    if (form.NotMarried)
				form.NotMarried.checked	= false;
		    break;
		}

		case STYPE_MARNOKIDS:	// No Children  
		{
		    var	noChildren	= parms['nochildren'];
		    if (noChildren)
				addNoChildrenRow();
		    else
		    if (form.NoChildren)
				form.NoChildren.checked	= false;
		    break;
		}

		case STYPE_LDSS: 	// Sealed to Spouse
		case STYPE_NEVERMARRIED: // Never married 
		case STYPE_MAREND:	// marriage ended
		{		// marriage event 
		    redisplayFamily();	// refresh to display
		    break;
		}		// marriage event

    }		// source fields to refresh depend on type
}		// eventFeedback

/************************************************************************
 *  redisplayFamily														*
 *																		*
 *  Refresh the dialog and redisplay the current family.				*
 ************************************************************************/
function redisplayFamily()
{	
    var	idmr	= document.famForm.idmr.value;
    var	url	= window.location.search;
    if (url.indexOf('idmr') == -1)
		url	= url + '&idmr=' + idmr;
    window.location.search	= url;
}		// redisplayFamily

/************************************************************************
 *  addExistingChild														*
 *																		*
 *  Prompt the user to choose an existing individual to add as a child		*
 *  of this family.														*
 *																		*
 *  Input:																*
 *		this		<button id='addChild'> element								*
 ************************************************************************/
function addExistingChild()
{
    var	form		= this.form;
    var	surname		= encodeURI(form.HusbSurname.value);
    var idmr		= form.idmr.value;
    var	url		= 'chooseIndivid.php?parentsIdmr=' + idmr + 
								   '&name=' + surname +
								   '&treeName=' +
							encodeURIComponent(form.treename.value);
    var	childWindow	= openFrame("chooserFrame",
							    url,
							    "left");
}		// addExistingChild

/************************************************************************
 *  detachHusb																*
 *																		*
 *  This method is called when the user requests that the current		*
 *  husband be detached with no replacement.								*
 *																		*
 *  Input:																*
 *		this		<button id='detachHusb'> element						*
 ************************************************************************/
function detachHusb()
{
    var	famForm			= this.form;

    famForm.IDIRHusb.value	= 0;
    famForm.HusbGivenName.value	= '';
    famForm.HusbSurname.value	= '';
    document.getElementById('detachHusb').disabled	= true;
    document.getElementById('editHusb').disabled	= true;
}		// detachHusb

/************************************************************************
 *  detachWife																*
 *																		*
 *  This method is called when the user requests that the current		*
 *  wife be detached with no replacement.								*
 *																		*
 *  Input:																*
 *		this		<button id='detachWife'> element						*
 ************************************************************************/
function detachWife()
{
    var	famForm			= this.form;

    famForm.IDIRWife.value	= 0;
    famForm.WifeGivenName.value	= '';
    famForm.WifeSurname.value	= '';
    document.getElementById('detachWife').disabled	= true;
    document.getElementById('editWife').disabled	= true;
}		// detachWife

/************************************************************************
 *  detChild																*
 *																		*
 *  Detach an existing individual as a child of this family.				*
 *																		*
 *  Input:																*
 *		this		<button id='detChild....'>								*
 ************************************************************************/
function detChild()
{
    var	cell		= this.parentNode;
    var	row		= cell.parentNode;

    // remove the editChild button for this row from the array
    // of editChild buttons
    var	buttons		= row.getElementsByTagName("BUTTON");
    var	button		= null;
    var	ib;
    for (ib = 0; ib < buttons.length; ib++)
    {			// loop through <button> tags
		button		= buttons[ib];
		if (button.id.substring(0,9) == 'editChild')
		    break;
		button		= null;
    }			// loop through <button> tags

    if (button)
    {			// found editChild button
		for (ib = 0; ib < editChildButtons.length; ib++)
		{		// loop through existing editChild buttons
		    if (editChildButtons[ib] == button)
		    {		// found matching button
				editChildButtons.splice(ib, 1);	// remove from array
				break;
		    }		// found matching button
		}		// loop through existing editChild buttons
    }			// found editChild button

    // remove the row from the DOM
    var	tableBody	= row.parentNode;
    tableBody.removeChild(row);
}		// detChild

/************************************************************************
 *  editChild																*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about an individual (child) in a family.				*
 *																		*
 *  Input:																*
 *		this		the <button id='editChild...'> element						*
 ************************************************************************/
function editChild()
{
    var msg		= 'args={';
    var	initIdir	= 0;
    for (attr in args)
    {
		msg	+= attr + "='" + args[attr] + "',";
		if (attr == 'id' || attr == 'idir')
		    initIdir	= args[attr];
    }

    var	button		= this;
    var	form		= button.form;
    var	rownum		= button.id.substr(EDIT_CHILD_PREFIX_LEN);
    var	cell		= button.parentNode;
    var	row		    = cell.parentNode;
    var	rowid		= row.id;
    var script		= 'editIndivid.php?rowid=' + rowid;
    var	idmr		= form.idmr.value - 0;
    var	cIdir		= form.elements['CIdir' + rownum];
    if (cIdir)
		script		+= "&idir=" + cIdir.value;
    if (initIdir && initIdir == cIdir.value)
    {				// edit dialog for this child already open
		// ask user to confirm delete
		dialogDiv	= document.getElementById('msgDiv');
		if (dialogDiv)
		{		// have popup <div> to display message in
		    var parms	= {
				'givenname'	: form.elements['CGiven' + rownum].value,
				'surname'	: form.elements['CSurname' + rownum].value,
				'idir'		: cIdir,
				'template'	: ''};
		    displayDialog(dialogDiv,
						  'AlreadyEditing$template',
						  parms,
						  this,			// position relative to
						  null,			// just close on any button
						  false);		// default show on open
		}		// have popup <div> to display message in
		else
		    alert("commonMarriage.js: editChild: " +
				  form.elements['CGiven' + rownum].value + ' ' +
				  form.elements['CSurname' + rownum].value +
				  " is already being edited");
		return;
    }				// edit dialog for this child already open
    var	cIdcr		= form.elements['CIdcr' + rownum];
    var	idcr		= 0;
    if (cIdcr)
    {
		idcr		= cIdcr.value - 0;
		script		+= "&idcr=" + idcr;
		if (idcr == 0)
		    script	+= "&parentsIdmr=" + idmr;	// add child
    }
 
    // pass the values of the fields in this row to the edit form
    var	cGiven		= form.elements['CGiven' + rownum];
    var	cSurname	= form.elements['CSurname' + rownum];
    var	cBirth		= form.elements['Cbirth' + rownum];
    var	cDeath		= form.elements['Cdeath' + rownum];
    if (cGiven)
    {			// child given name present in row
		script		+= '&initGivenName=' + 
							encodeURIComponent(cGiven.value) +
						   '&initGender=' +
							cGiven.className;
    }			// child given name present in row
    else
    {			// logic error
		var	msg	    = "";
		var	comma	= "";
		for(var ie=0; ie < form.elements.length; ie++)
		{
		    var element	= form.elements[ie];
		    msg		+= comma + element.name + "='" + element.value + "'";
		    comma	= ",";
		}
		alert("editChild: unable to get form.element['CGiven" +
		      rownum + "'] elements={" + msg + "}");
    }			// logic error

    if (cSurname)
		script		+= '&initSurname=' + 
							encodeURIComponent(cSurname.value);
    if (cBirth)
		script		+= '&initBirthDate=' + 
							encodeURIComponent(cBirth.value);
    if (cDeath)
		script		+= '&initDeathDate=' + 
							encodeURIComponent(cDeath.value);
    script		+= '&treeName=' +
							encodeURIComponent(form.treename.value);
    script		+= '&debug=' + debug;

    // disable all of the edit family member buttons
    for (var ib = 0; ib < editChildButtons.length; ib++)
    {			// disable all editChild buttons
		editChildButtons[ib].disabled	= true;
    }			// disable all editChild buttons

    // open a dialog window to edit the child
    var childWindow	= openFrame("childFrame",
							    script,
							    "left");
    childWindows.push(childWindow);
    return true;
}	// function editChild

/************************************************************************
 *  function addNewChild												*
 *																		*
 *  This method is called when the user requests to add 				*
 *  a new individual to the marriage as a child.						*
 *																		*
 *  Input:																*
 *		this		<button id='addNewChild'> element					*
 ************************************************************************/
function addNewChild()
{
    var	form		= this.form;
    var	childTable	= document.getElementById('children');
    var	parms	= {
				'idir'		: 0,
				'givenname'	: '',
				'surname'	: '',
				'birthd'	: '',
				'birthsd'	: -99999999,
				'deathd'	: '',
				'gender'	: 'unknown'};
    parms.surname		= form.HusbSurname.value;

    var	row		= childTable.addChildToPage(parms,
									    false);

    if (row.id)
    {
		var	rownum		= row.id.substring(CHILD_PREFIX_LEN);
		var givenName	= form.elements['CGiven' + rownum];
		givenName.onchange	= givenChanged;
		givenName.focus();		// move the cursor to the new name
    }
    else
		alert("commonMarriage.js: addNewChild: row=" +
				tagToString(row));
}	// function addNewChild

/************************************************************************
 *  function givenChanged												*
 *																		*
 *  This method is called when the user modifies the value of the		*
 *  given name of a child.  It adjusts the default gender based			*
 *  upon the name.														*
 *																		*
 *  Input:																*
 *		this	instance of <input id="CGiven...">			            *
 ************************************************************************/
function givenChanged()
{
    var	form		= this.form;
    var	rownum		= this.name.substring(6);
    var	surnameElt	= form.elements['CSurname' + rownum];
    var genderElt	= form.elements['CGender' + rownum];
    var	givenName	= this.value.toLowerCase();
    var	names		= givenName.split(" ");
    for (var i = 0; i < names.length; i++)
    {		// loop through individual given names
		if (femaleNames[names[i]] > 0)
		{
		    this.className		= 'female'
		    if (surnameElt)
				surnameElt.className	= 'female';
		    if (genderElt)
				genderElt.value		= 1;
		    break;
		}
		else
		if (maleNames[names[i]] > 0)
		{
		    this.className		= 'male'
		    if (surnameElt)
				surnameElt.className	= 'male';
		    if (genderElt)
				genderElt.value		= 0;
		    break;
		}
    }		// loop through individual given names

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
		this.checkfunc();
}	// function givenChanged

/************************************************************************
 *  function changeHusbSurname											*
 *																		*
 *  This function is called when the surname of the husband is changed	*
 *  This may required changing the married surnames of the husband and	*
 *  wife.																*
 *																		*
 *  Input:																*
 *		this	<input type='text' id='HusbSurname'> element			*
 ************************************************************************/
function changeHusbSurname()
{
    var	form		= this.form;
    var	surname		= this.value;
    if (form.HusbMarrSurname)
		form.HusbMarrSurname.value	= surname;
    if (form.WifeMarrSurname &&
		form.MarriedNameRule &&
		form.MarriedNameRule.value == '1')
		form.WifeMarrSurname.value	= surname;

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
		this.checkfunc();
}		// function changeHusbSurname

/************************************************************************
 *  function changeCBirth												*
 *																		*
 *  This function is called when the date of birth of a child changes.	*
 *  This requires updating the sorted birth date used for ordering		*
 *  children.															*
 *																		*
 *  Input:																*
 *		this		<input type='text' id='Cbirth...'> element			*
 ************************************************************************/
function changeCBirth()
{
    var	form		= this.form;
    var	rowid		= this.name.substring(6);
    var	birthd		= this.value;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    birthd		= birthd.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    birthd		= birthd.replace(/(\d)([a-zA-Z])/g,"$1 $2");
    this.value		= birthd;

    var	y		= 0;
    var	m		= 6;
    var	d		= 15;

    var datePattern	= /(\d*)\s*([a-zA-Z]+)\s*(\d*)/;
    var pieces		= datePattern.exec(birthd);
    if (pieces !== null)
    {
		if (pieces[1].length > 0)
		{
		    d		= parseInt(pieces[1]);
		}
		var	month	= pieces[2].toLowerCase();
		m		= monTab[month];
		if (m === undefined)
		    m		= 6;
		if (pieces[3].length > 0)
		{
		    y		= parseInt(pieces[3]);
		}
		if (d > 31 && y <= 31)
		{
		    var	temp	= d;
		    d		= y;
		    y		= temp;
		}
		form.elements['Cbirthsd' + rowid].value	= y * 10000 + m * 100 + d;
    }

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
		this.checkfunc();
}		// function changeCBirth

/************************************************************************
 *  function updateMarr													*
 *																		*
 *  This method is called when the user requests to update				*
 *  the marriage.  A request is sent to the server to perform the		*
 *  update.  This request returns an XML document reporting the results.*
 *																		*
 *  Input:																*
 *		this	<button id='update'> element							*
 ************************************************************************/
var	updateMarriageParms	= "";
var	updatingMarriage	= false;

function updateMarr()
{
    if (updatingMarriage)
    {
		updatingMarriage	= false;
		return;
    }
    updatingMarriage		= true;

    // do not submit the update if there are open child edit windows
    // count the number of open child edit windows
    var	numOpenChildWindows	= 0;
    var	childWindowNames	= "";
    var	comma			= "";
    for(var i = 0; i < childWindows.length; i++)
    {		// loop through all edit child windows
		var childWindow		= childWindows[i];
		if (!(childWindow.closed))
		{
		    numOpenChildWindows++;
		    childWindowNames	+= comma + "'" +
								childWindow.document.title + "'"; 
		    comma		= ' and ';
		}
    }		// loop through all edit child windows

    // if there are open child edit windows warn the user and skip save
    childWindowNames		= childWindowNames.trim();
    if (childWindowNames.length > 2)
    {		// at least one child window still open
		popupAlert("Warning: subordinate edit window " +
						childWindowNames + " is still open",
				   this);
		return;
    }		// at least one child window still open

    // request the update of the marriage record in the database
    var	form		= this.form;
    var parms		= {};
    var	msg		= "";

    // expand incomplete wife or mother's name
    var wifeSurname	= form.WifeSurname;
    var wifeGiven	= form.WifeGivenName;
    var husbSurname	= form.HusbSurname;
    var husbGiven	= form.HusbGivenName;
    if (wifeGiven.value.length > 0 && 
		wifeSurname.value.length == 0 &&
		wifeGiven.value.indexOf("Wifeof") < 0)
    {
		var wifeSurnameStr	= "Wifeof" +
							  husbGiven.value.toLowerCase() + 
							  husbSurname.value.toLowerCase();
		wifeSurnameStr		= wifeSurnameStr.replace(/\s+/g, '');
		wifeSurname.value	= wifeSurnameStr;
    }

    // copy selected information from the form to the parameters
    for (var ei = 0; ei < form.elements.length; ei++)
    {		// loop through all elements in the form
		var	element	= form.elements[ei];
		var	name	= element.name;
		if (name == 'Notes')
        {
            try {
		        parms[name]	= tinyMCE.get(name).getContent();
            } catch(err) {
                parms[name] = element.value; 
                alert(err.message); 
            }
        }
		else
		if (name.length > 0)
		    parms[name]	= element.value;
    }				// loop through all elements in the form
    // alert(JSON.stringify(parms));
    updateMarriageParms	= "parms={";
    for(parm in parms)
    {
		updateMarriageParms += parm + "='" + parms[parm] + "',";
    }

    popupLoading(this);	// display loading indicator
    HTTP.post('/FamilyTree/updateMarriageXml.php',
		      parms,
		      gotUpdatedFamily,
		      noUpdatedFamily);
}	// function updateMarr

/************************************************************************
 *  function gotUpdatedFamily											*
 *																		*
 *  This method is called when the XML document representing			*
 *  the updated marriage is returned.									*
 *																		*
 *  Parameters:															*
 *		xmlDoc			response from server script						*
 *						updateMarriageXml.php as an XML document		*
 *						containing a LegacyMarriage record.				*
 ************************************************************************/
function gotUpdatedFamily(xmlDoc)
{
    if (xmlDoc === null)
    {
		hideLoading();	// hide loading indicator
		alert("gotUpdateMarr: xmlDox is null");
		return;
    }
    var	root		= xmlDoc.documentElement;
    //	alert("gotUpdatedFamily: root=" + 
    //	 tagToString(root).replace('/</g', '&lt;').replace('/>/g', '&lt;'));
    var idmr		= 0;
    var spsIdir		= 0;
    var fatherid	= 0;
    var motherid	= 0;
    var	spsSurname	= '';
    var	spsGivenname	= '';
    var	fatherSurname	= '';
    var	fatherGiven	= '';
    var	motherSurname	= '';
    var	motherGiven	= '';
    var	spsclass	= 'male';
    var	marDate		= 'Unknown';

    hideLoading();	// hide loading indicator
    updatingMarriage	= false;
    if (root)
    {			// XML document
		if (root.nodeName == 'marriage')
		{		// normal response
		    var	form	= document.indForm;
		    var	sex	= 0;			// 0 for male, 1 for female
		    if (form.sex)
				sex	= form.sex.value;
		    if (sex == 0)
				spsclass	= 'female';
		    else
				spsclass	= 'male';

		    for (var i = 0; i < root.childNodes.length; i++)
		    {			// loop through top level nodes of the response
				var	node	= root.childNodes[i];
				if (node.nodeType != 1)
				    continue;
				var	value	= node.textContent.trim();
				switch(node.nodeName)
				{		// act on individual children
				    case 'msg':
				    {		// error message
						alert ("commonMarriage.js: gotUpdatedFamily: Error: " +
							value + ", " + updateMarriageParms);
						var para	= document.getElementById('MarrButtonLine');
						para.appendChild(document.createTextNode(
		 tagToString(root).replace('/</g', '&lt;').replace('/>/g', '&lt;')));
						return;
				    }		// error message

				    case 'idmr':
				    {		// key of the record
						idmr			= value;
						break;
				    }		// key of the record

				    case 'parms':
				    {		// parameter processing
						processParms(node);
						break;
				    }		// parameter processing

				}		// act on individual children
		    }			// check the children for error message text

		    // take appropriate action
		    var	opener	= null;
		    if (window.frameElement && window.frameElement.opener)
				opener	= window.frameElement.opener;
		    else
				opener	= window.opener;

		    if (pendingButton)
		    {		// another action to perform
				form		= pendingButton.form;
				form.idmr.value	= idmr;
				var	tmp	= pendingButton;
				pendingButton	= null;
				tmp.onclick();
		    }		// another action to perform
		    else
		    if (opener)
		    { // notify the opener (editIndivid.php) of the updated marriage
				if (opener.document.indForm)
				{
				    try {
						var section	= document.getElementById('marriageListBody');
						var numFamilies	= section.rows.length;
						if ('new' in args && args['new'].toLowerCase() == 'y')
						    numFamilies++;
						else
						if (numFamilies == 0)
						    numFamilies	= 1;	// adding first family
						opener.document.indForm.marriageUpdated(idmr,
											numFamilies);
				    } catch(e) { alert("commonMarriage.js: 2031 e=" + e); }
				}

				closeFrame();
		    } // notify the opener (editIndivid.php) of the updated marriage
		    else
				window.history.back();
		}		// normal response
		else
		{	// unexpected root node
		    alert("commonMarriage.js: gotUpdatedFamily: Unexpected: " +
				  tagToString(root) + ", " + updateMarriageParms);
		}	// unexpected root node
    }		// XML document
    else
    {		// not an XML document, display text
		alert("commonMarriage.js: gotUpdatedFamily: Unexpected: " + xmlDoc +
							 ", " + updateMarriageParms);
    }		// not an XML document, display text
}		// function gotUpdatedFamily

/************************************************************************
 *  function processParms												*
 *																		*
 *  This method is called to process the <parms> element from the		*
 *  XML document response from the script updateMarriageXml.php.		*
 *																		*
 *  Parameters:															*
 *		parms			XML node <parms>                                *
 ************************************************************************/
function processParms(parms)
{
    for (var i = 0; i < parms.childNodes.length; i++)
    {			// loop through individual parameters
		var	parm		= parms.childNodes[i];
		if (parm.nodeType != 1)
		    continue;
		var	value		= parm.textContent.trim();
		var	namePattern	= /^([a-zA-Z_]+)(\d*)$/;
		var	pieces		= namePattern.exec(parm.nodeName);
		var	name		= parm.nodeName.toLowerCase();
		var	rowNum		= '';
		if (pieces)
		{		// separate column and row
		    name		= pieces[1].toLowerCase();
		    rowNum		= pieces[2];
		}		// separate column and row

		// pop up help balloon if the mouse hovers over a field
		switch(name)
		{		// act on individual parm
		    case 'cidir':
		    {		// original IDIR of a child
				break;
		    }		// original IDIR of a child

		    case 'cidcr':
		    {		// IDCR of a child
				for (var j = 0; j < parm.childNodes.length; j++)
				{	// loop through children
				    var	child	= parm.childNodes[j];
				    if (child.nodeType != 1)
						continue;
				    var	pieces	= namePattern.exec(child.nodeName);
				    var	cname	= parm.nodeName.toLowerCase();
				    var crowNum	= '';
				    if (pieces)
				    {		// separate column and row
						cname	= pieces[1].toLowerCase();
						crowNum	= pieces[2];
				    }		// separate column and row

				    if (cname == 'idir')
				    {
						var	value		= child.textContent.trim();
						var fldId	= 'CIdir' + crowNum;
						var idirElt	= document.getElementById(fldId)
						if (idirElt && idirElt.value == 0)
						{
						    idirElt.value	= value;
						}
				    }
				    else
				    if (cname == 'newidcr')
				    {
						var	value		    = child.textContent.trim();
						var fldId	        = 'CIdcr' + rowNum;
						var idcrElt	        = document.getElementById(fldId)
						if (idcrElt && idcrElt.value == 0)
						{
						    idcrElt.value	= value;
						}
				    }
				}	// loop through children
				break;
		    }		// IDCR of a child

		}		// act on individual parm
    }			// loop through individual parameters
}		// function processParms

/************************************************************************
 *  function noUpdatedFamily											*
 *																		*
 *  This method is called if the server does not return					*
 *  an XML document response from the script updateMarriageXml.php.		*
 ************************************************************************/
function noUpdatedFamily()
{
    alert("commonMarriage.js: noUpdatedFamily: script updateMarriageXml.php not found on server");
}		// function noUpdatedFamily

/************************************************************************
 *  function orderChildren												*
 *																		*
 *  This method is called when the user requests to reorder 			*
 *  the children by birth date.  This method only changes the order		*
 *  in which the children appear in the display.  The family must be	*
 *  updated to apply the change to the database.						*
 *																		*
 *  Input:																*
 *		this		<button id='orderChildren'>							*
 ************************************************************************/

function orderChildren()
{
    var	children	= document.getElementById('children');
    var	body		= children.tBodies[0];
    var	bodyRows	= Array();
    for (var i = 0; i < body.rows.length; i++)
    {
		var	row	= body.rows[i];
		var	rowId	= row.id.substring(5);
		var	idirElt	= document.getElementById('CIdir' + rowId);
		if (typeof(idirElt) != 'undefined' && idirElt.value == 0)
		{		// child is not yet in database
		    pendingButton	= this;
		    this.form.update.onclick();	// save the family first
		}
		bodyRows[i]	= body.rows[i];
    }
    bodyRows.sort(childOrder);
    while (body.hasChildNodes())
		body.removeChild(body.firstChild);
    for (var ri = 0; ri < bodyRows.length; ri++)
		body.appendChild(bodyRows[ri]);
}	// function orderChildren

/************************************************************************
 *  function childOrder													*
 *																		*
 *  This function is called by the Array sort method to determine		*
 *  the relative order of a pair of children in the array of children	*
 *  based upon their dates of birth.									*
 *																		*
 *  Input:																*
 *		first			instance of <tr>								*
 *		second			instance of <tr>								*
 *																		*
 *  Returns:															*
 *		>0 if the first child was born first							*
 *		0 if the children were born on the same date					*
 *		<0 if the second child was born first							*
 ************************************************************************/

function childOrder(first, second)
{
    var	e1, e2;
    var	sd1, sd2;
    var	firstElements	= first.getElementsByTagName("input");
    for(e1 = 0; e1 < firstElements.length; e1++)
    {
		var	e1Name	= firstElements[e1].name.substring(0,8);
		if (e1Name == 'Cbirthsd')
		{
		    sd1		= firstElements[e1].value;
		    break;
		}
    }		// loop through input elements
    var	secondElements	= second.getElementsByTagName("input");
    for(e2 = 0; e2 < secondElements.length; e2++)
    {
		var	e2Name	= secondElements[e2].name.substring(0,8);
		if (e2Name == 'Cbirthsd')
		{
		    sd2		= secondElements[e2].value;
		    break;
		}
    }		// loop through input elements
    // alert("childOrder: sd1=" + sd1 + ", sd2=" + sd2 +
    //		", return=" + (sd1 - 0 - sd2));
    return sd1 - 0 - sd2;
}		// function childOrder

/************************************************************************
 *  function editEvent													*
 *																		*
 *  This is the onclick method of the "Edit Event" button.  			*
 *  It is called when the user requests to edit							*
 *  information about an event of the current family that is			*
 *  recorded in an instance of Event.									*
 *																		*
 *  Input:																*
 *		this	<button id='EditEvent9999'> where the number is the		*
 *				key of an instance of Event.							*
 ************************************************************************/
function editEvent()
{
    var	form		= this.form;
    var	ider		= this.id.substring(9);

    var idmr		= form.idmr.value;
    if (idmr && idmr > 0)
    {			// existing family
		var url		= 'editEvent.php?idmr=' + idmr +
							    '&ider=' + ider +
							    '&type=31';
		var eventWindow	= openFrame("eventLeft",
							    url,
							    "left");
    }			// existing family
    else
    {			// family needs to be saved first
		pendingButton	= this;
		form.update.onclick();	// save the family first
    }			// family needs to be saved first
    return true;
}	// function editEvent

/************************************************************************
 *  editIEvent																*
 *																		*
 *  This is the onclick method of an "Edit Event" button.  				*
 *  It is called when the user requests to edit								*
 *  information about an event of the current family that is				*
 *  recorded in the instance of Family.								*
 *																		*
 *  Parameters:																*
 *		this		<button id='EditIEvent9999'> where the number is 		*
 *				a citation type as defined in Citation.inc				*
 ************************************************************************/
function editIEvent()
{
    var	form		= this.form;
    var	citType		= this.id.substring(10);

    var idmr		= form.idmr.value;
    if (idmr && idmr > 0)
    {			// existing family
		var url		= 'editEvent.php?idmr=' + idmr +
							'&type=' + citType;
		var eventWindow	= openFrame("openLeft",
							    url,
							    "left");
    }			// existing family
    else
    {			// family needs to be saved first
		pendingButton	= this;
		form.update.onclick();	// save the family first
    }			// family needs to be saved first
    return true;
}	// editIEvent

/************************************************************************
 *  delEvent																*
 *																		*
 *  This is the onclick method of the "Delete Event" button.  				*
 *  It is called when the user requests to delete						*
 *  information about an existing event in the current family that is		*
 *  recorded by an instance of Event.										*
 *																		*
 *  Parameters:																*
 *		this		<button id='DelEvent9999'> where the number is the		*
 *				key of an instance of Event								*
 ************************************************************************/
function delEvent()
{
    var	form	= this.form;
    var	ider	= this.id.substring(DELETE_EVENT_PREFIX_LEN);
    var parms		= {"type"	: ider,
						   "formname"	: form.name, 
						   "template"	: "",
						   "msg"	:
						"Are you sure you want to delete this event?"};

    // ask user to confirm delete
    dialogDiv	= document.getElementById('msgDiv');
    if (dialogDiv)
    {		// have popup <div> to display message in
		displayDialog(dialogDiv,
				      'ClrInd$template',
				      parms,
				      this,		// position relative to
				      confirmEventDel,	// 1st button confirms Delete
				      false);		// default show on open
    }		// have popup <div> to display message in
    else
		alert("commonMarriage.js: delEvent: Error: " + msg);
}		// delEvent

/************************************************************************
 *  confirmEventDel														*
 *																		*
 *  This method is called when the user confirms the request to delete		*
 *  an event which is defined in an instance of Event.						*
 *  A request is sent to the server to delete the instance.				*
 *																		*
 *  Input:																*
 *		this				<button id='confirmClear...'>						*
 ************************************************************************/
function confirmEventDel()
{
    // get the parameter values hidden in the dialog
    var	form		= this.form;
    var	ider		= this.id.substr(12);
    var	formname	= form.elements['formname' + ider].value;
    var	form		= document.forms[formname];
    dialogDiv.style.display	= 'none';


    if (form)
    {		// have the form
		var parms	= {"idime"	: ider,
						   "cittype"	: 31};

		// invoke script to update Event and return XML result
		popupLoading(this);	// display loading indicator
		HTTP.post('/FamilyTree/deleteEventXml.php',
				  parms,
				  gotDelEvent,
				  noDelEvent);
    }		// have the form
    else
		alert("commonMarriage.js: confirmEventDel: unable to get form");
    return true;
}	// confirmEventDel

/************************************************************************
 *  delIEvent																*
 *																		*
 *  This is the onclick method of the "Delete Internal Event" button.  		*
 *  It is called when the user requests to delete						*
 *  information about an existing event in the current family that is		*
 *  recorded by data inside the instance of Family.				*
 *																		*
 *  Parameters:																*
 *		this		<button id='DelIEvent9999'> where the number is				*
 *				a citation type												*
 ************************************************************************/
function delIEvent()
{
    var	form	= this.form;
    var	citType	= this.id.substring(9);
    var parms		= {"type"	: citType,
						   "formname"	: form.name, 
						   "template"	: "",
						   "msg"	:
						"Are you sure you want to delete this event?"};

    // ask user to confirm delete
    dialogDiv	= document.getElementById('msgDiv');
    if (dialogDiv)
    {		// have popup <div> to display message in
		displayDialog(dialogDiv,
				      'ClrInd$template',
				      parms,
				      this,		// position relative to
				      confirmClearInd,	// 1st button confirms Delete
				      false);		// default show on open
    }		// have popup <div> to display message in
    else
		alert("commonMarriage.js: delIEvent: Error: " + msg);
}		// delIEvent

/************************************************************************
 *  confirmDelIEvent														*
 *																		*
 *  This method is called when the user confirms the request to delete		*
 *  an event which is defined inside the Familyrecord.				*
 *  The contents of the fields describing the event are cleared.		*
 *  The user still needs to update the individual to apply the changes.		*
 *																		*
 *  Input:																*
 *		this				<button id='confirmClear...'>						*
 ************************************************************************/
function confirmDelIEvent()
{
    // get the parameter values hidden in the dialog
    var	form		= this.form;
    var	citType		= this.id.substr(12);
    var	formname	= form.elements['formname' + type].value;
    var	form		= document.forms[formname];

    dialogDiv.style.display	= 'none';

    if (form)
    {		// have the form
		var parms	= {
						"idime"		: form.idmr.value,
						"cittype"	: citType};

		// invoke script to update Event and return XML result
		popupLoading(this);	// display loading indicator
		HTTP.post('/FamilyTree/deleteEventXml.php',
				  parms,
				  gotDelEvent,
				  noDelEvent);
    }		// have the form
    else
		alert("commonMarriage.js: confirmDelIEvent: unable to get form");
    return true;
}	// confirmDelIEvent

/************************************************************************
 *  gotDelEvent																*
 *																		*
 *  This method is called when the XML document representing				*
 *  a successful delete family event is retrieved from the database.		*
 *																		*
 *  Parameters:																*
 *		xmlDoc				response from the server script						*
 *						deleteEventXml.php as an XML document				*
 ************************************************************************/
function gotDelEvent(xmlDoc)
{
    hideLoading();	// hide loading indicator
    if (xmlDoc.documentElement)
    {		// XML document
		var	root	= xmlDoc.documentElement;
		if (root.tagName == 'deleted')
		{		// correctly formatted response
		    var msgs	= root.getElementsByTagName('msg');
		    if (msgs.length == 0)
		    {		// no errors detected
				redisplayFamily();
				// notify the opener (editIndivid.php) of the updated marriage
				var	opener	= null;
				if (window.frameElement && window.frameElement.opener)
				    opener	= window.frameElement.opener;
				else
				    opener	= window.opener;
				if (opener && opener.document.indForm)
				{
				    try {
						var section	= document.getElementById('marriageListBody');
						opener.document.indForm.marriageUpdated(0,
										section.rows.length);
				    } catch(e) { alert("commonMarriage.js: 2388 e=" + e); }
				}
		    }		// no errors detected
		    else
		    {		// report message
				alert('commonMarriage.js: gotDelEvent: ' + tagToString(msgs[0]));
		    }		// report message
		}		// correctly formatted response
		else
		    alert('commonMarriage.js: gotDelEvent: ' + tagToString(root));
    }		// XML document
    else
		alert('commonMarriage.js: gotDelEvent: ' + xmlDoc);
}		// gotDelEvent

/************************************************************************
 *  noDelEvent																*
 *																		*
 *  This method is called if there is no server response from the		*
 *  deleteEventXml.php script												*
 ************************************************************************/
function noDelEvent()
{
    alert('commonMarriage.js: noDelEvent: No server response from deleteEventXml.php');
}		// noDelEvent

/************************************************************************
 *  addEvent																*
 *																		*
 *  This is the onclick method of the "Add Event" button.  				*
 *  It is called when the user requests to add								*
 *  information about a new event to the current family that is				*
 *  recorded by an instance of Event or by a normally hidden				*
 *  event recorded in the instance of Family.						*
 *																		*
 *  Parameters:																*
 *		this		<button id='addEvent'>										*
 ************************************************************************/
function addEvent()
{
    var	form	= this.form;

    if (form)
    {
		var idmr	= form.idmr.value;
		var url		= 'editEvent.php?idmr=' + idmr + '&ider=0&type=0';
		var eventWindow	= openFrame("eventLeft",
							    url,
							    "left");
    }
    else
		alert("commonMarriage.js: addEvent: unable to get form");
    return true;
}	// addEvent

/************************************************************************
 *  orderEvents																*
 *																		*
 *  This method is called when the user requests to reorder 				*
 *  the events by event date.  This method only changes the order		*
 *  in which the events appear in the display.				* 
 *																		*
 *  Input:																*
 *		this		<button id='orderEvents'>								*
 ************************************************************************/
function orderEvents()
{
    popupAlert("Sorry, this functionality is not yet implemented", this);
}		// orderEvents

/************************************************************************
 *  editPictures														*
 *																		*
 *  This is the onclick method of the "Edit Pictures" button.  				*
 *  It is called when the user requests to edit								*
 *  information about the Pictures of the current family that are		*
 *  recorded by instances of Picture.										*
 *																		*
 *  Parameters:																*
 *		this		<button id='Pictures'										*
 ************************************************************************/
function editPictures()
{
    var	form	= this.form;

    if (form)
    {
		var	idmr	= form.idmr.value;
		if (idmr && idmr > 0)
		{
		    var url	= "editPictures.php?idir=" + idmr + "&idtype=Mar"; 
		    var childWindow	= openFrame("picturesLeft",
								    url,
								    "left");
		}			// existing family
		else
		{			// family needs to be saved first
		    pendingButton	= this;
		    form.update.onclick();	// save the family first
		}			// family needs to be saved first
    }
    else
		alert("commonMarriage.js: editPictures: unable to get form");
    return true;
}	// editPictures

/************************************************************************
 *  changeNameRule														*
 *																		*
 *  The user has altered the selection of MarriageNameRule.				*
 *																		*
 *  Input:																*
 *		this		<select name='MarriedNameRule'>								*
 ************************************************************************/
function changeNameRule()
{
    if (this.selectedIndex >= 0)
    {		// user has selected a rule
		var	option		= this.options[this.selectedIndex];
		var	form		= this.form;
		var	husbMarrSurname	= form.HusbMarrSurname;
		var	wifeMarrSurname	= form.WifeMarrSurname;
		var	husbSurname	= form.HusbSurname.value;
		var	wifeSurname	= form.WifeSurname.value;

		if (option.value == 0)
		{	// display explicit married surname fields
		    husbMarrSurname.readonly	= false;
		    husbMarrSurname.className	= 'actleft';
		    husbMarrSurname.value	= husbSurname;
		    wifeMarrSurname.readonly	= false;
		    wifeMarrSurname.className	= 'actleft';
		    wifeMarrSurname.value	= wifeSurname;
		}	// display explicit married surname fields
		else
		if (option.value == 1)
		{	// hide traditional married surname fields
		    husbMarrSurname.readonly	= true;
		    husbMarrSurname.className	= 'ina left';
		    husbMarrSurname.value	= husbSurname;
		    wifeMarrSurname.readonly	= true;
		    wifeMarrSurname.className	= 'ina left';
		    wifeMarrSurname.value	= husbSurname;
		}	// hide traditional married surname fields
		else
		{	// display explicit married surname fields
		    husbMarrSurname.readonly	= false;
		    husbMarrSurname.className	= 'white left';
		    husbMarrSurname.value	= husbSurname;
		    wifeMarrSurname.readonly	= false;
		    wifeMarrSurname.className	= 'white left';
		    wifeMarrSurname.value	= wifeSurname;
		}	// display explicit married surname fields
    }		// user has selected a rule
}		// changeNameRule

/************************************************************************
 *  gotAddChild																*
 *																		*
 *  This method is called when the XML document representing				*
 *  a child added to the family is retrieved from the server.				*
 *																		*
 *  Parameters:																*
 *		xmlDoc				Family record as an XML document				*
 ************************************************************************/
function gotAddChild(xmlDoc)
{
    hideLoading();	// hide loading indicator
    // get information from XML document
    if (xmlDoc.documentElement)
    {		// XML document
		var	root	= xmlDoc.documentElement;
		if (root.tagName == 'child')
		{		// correctly formatted response
		    // alert("commonMarriage.js: gotAddChild: root=" + tagToString(root));
		    var	parms		= getParmsFromXml(root);
		    var childTable	= document.getElementById('children');
		    childTable.addChildToPage(parms);
		}		// correctly formatted response
    }		// XML document
    else
		alert("gotAddChild: " + xmlDoc);
}		// gotAddChild

/************************************************************************
 *  noAddChild																*
 *																		*
 *  This method is called if there is no add child response				*
 *  from the server.														*
 ************************************************************************/
function noAddChild()
{
    alert('commonMarriage.js: noAddChild: script addChildXml.php not found on server');
}		// noAddChild

/************************************************************************
 *  addChildToPage														*
 *																		*
 *  This method is called to add information about a child				*
 *  as a visible row in the web page.  If requested it also adds the		*
 *  child to the database. This is a callback method of the				*
 *  <table id='children'> element that is called by editIndivid.js		*
 *  to display information about a child that is being added to the		*
 *  family.																*
 *																		*
 *  Parameters:																*
 *		this				table element with id='children'				*
 *		parms				object with at least the following membets		*
 *		    idir		IDIR of child to update or object				*
 *		    givenname		given name of new child								*
 *		    surname		surname of new child								*
 *		    birthd		birth date of new child as text						*
 *		    birthsd		birth date of new child as yyyymmdd				*
 *		    deathd		death date of new child as text						*
 *		    gender		gender of new child: "male" or "female"				*
 *		updateDb		no longer used										*
 *																		*
 *  Returns:																*
 *		<div id='childnnn> or <tr id='childnnn'> element added to page		*
 ************************************************************************/
function addChildToPage(parms,
						updateDb,
						debug)
{
    var msg	= "";
    for(parm in parms) { msg += parm + "='" + parms[parm] + "',"; }
    //alert("common Marriage.js: addChildToPage(parms={" + msg + "},updateDb=" + updateDb + ")");
    if (parms.givenname === undefined)
		throw "commonMarriage.js: addChildToPage: parms=" + msg;
    if (parms.idcr === undefined)
		parms.idcr	= '';
    
    // add information about the  child as a visible row in the web page. 
    var	table		= this;
    var	famForm		= document.famForm;

    // ensure that No Children checkbox is cleared and disabled
    // so the user cannot accidentally set it
    if (famForm.NoChildren)
    {
		famForm.NoChildren.checked	= false;
		document.getElementById('NoChildren').disabled	= true;
    }

    // get the IDMR value for the current family
    var	idmr		= famForm.idmr.value;

    // get the body of the table of children
    var	tableBody	= table.tBodies[0];
    
    // insert new row of information into the web page 
    // at the end of the body section of the table
    var	rownum		= tableBody.rows.length;
    parms.rownum	= rownum;
    if (parms.gender == 'male')
		parms.sex	= 0;
    else
    if (parms.gender == 'female')
		parms.sex	= 1;
    else
		parms.sex	= 2;
    var	row		= createFromTemplate('child$rownum',
								     parms,
								     null,
								     debug);
    row			= tableBody.appendChild(row);	// add to end of body
    if (parms.idir)
		row.idir	= parms.idir;
    if (parms.idcr)
		row.idcr	= parms.idcr;
    row.changePerson	= changeChild;		// feedback method
    var	inputElements	= row.getElementsByTagName("*");
    for(var ei = 0; ei < inputElements.length; ei++)
    {
		var	element		= inputElements[ei];
		var	nodeName	= element.nodeName.toLowerCase();
		var	name;
		if (element.name && element.name.length > 0)
		    name	= element.name;
		else
		    name	= element.id;
		if (nodeName != 'input' && nodeName != 'button')
		    continue;

		var rowNum	= '';
		var namePattern	= /^([a-zA-Z_]+)(\d+)$/;
		var pieces	= namePattern.exec(name);
		if (pieces)
		{		// separate column and row
		    name	= pieces[1];
		    rowNum	= pieces[2];
		}		// separate column and row

		// pop up help balloon if the mouse hovers over a field
		// for more than 2 seconds
		actMouseOverHelp(element);
		element.onkeydown	= keyDown;
		switch(name)
		{		// act on specific fields
		    case "CGiven":
		    {
				element.onkeydown	= childKeyDown;
				element.checkfunc	= checkName;
				element.onchange	= givenChanged;
				break;
		    }

		    case "CSurname":
		    {
				element.onkeydown	= childKeyDown;
				element.checkfunc	= checkName;
				element.onchange	= change;	// default handler
				break;
		    }

		    case "Cbirth":
		    {
				element.onkeydown	= childKeyDown;
				element.abbrTbl		= MonthAbbrs;
				element.checkfunc	= checkDate;
				element.onchange	= changeCBirth;
				break;
		    }

		    case "Cbirthsd":
		    {
				break;
		    }

		    case "Cdeath":
		    {
				element.onkeydown	= childKeyDown;
				element.abbrTbl		= MonthAbbrs;
				element.checkfunc	= checkDate;
				element.onchange	= dateChanged;
				break;
		    }

		    case "Cdeathsd":
		    {
				break;
		    }

		    case "editChild":
		    {
				editChildButtons.push(element);
				element.onclick		= editChild;
				break;
		    }

		    case "detChild":
		    {
				editChildButtons.push(element);
				element.onclick		= detChild;
		    }

		    default:
		    {
				element.onchange	= change;	// default handler
				break;
		    }
		}		// act on specific fields
    
    }		// loop through input tags

    return	row;
}		// addChildToPage

/************************************************************************
 *  editEventMar														*
 *																		*
 *  This method is called when the user requests to edit				*
 *  information about an event of the current family						*
 *  that is described by fields within the Family record itself.		*
 *																		*
 *  Parameters:																*
 *		type				the event type, used to distinguish between the		*
 *						events that are recorded inside the				*
 *						Family record										*
 *		button				invoking instance of <button>						*
 *																		*
 ************************************************************************/
function editEventMar(type, button)
{
    var	form	= document.famForm;
    if (form)
    {
		var	idmr		= form.idmr.value;
		if (idmr && idmr > 0)
		{			// existing family
		    var	url	= "editEvent.php?idmr=" + idmr +
								"&type=" + type;

		    switch(type)
		    {		// add parameters dependent upon type
				case 18:
				{	// sealed event
				    url	+= "&date=" +
						   encodeURIComponent(form.SealD.value);
				    break;
				}	// sealed event

				case 20:
				{	// marriage event
				    url	+= "&date=" +
						   encodeURIComponent(form.MarD.value) +
						   "&location=" +
						   encodeURIComponent(form.MarLoc.value);
				    break;
				}	// marriage event

		    }		// add parameters dependent upon type

		    var childWindow	= openFrame("eventLeft",
								    url, 
								    "left");
		}			// existing family
		else
		{			// family needs to be saved first
		    pendingButton	= button;
		    form.update.onclick();	// save the family first
		}			// family needs to be saved first
    }		// have form
    else
		alert("editEventMar: unable to get form");
    return true;
}	// editEventMar

/************************************************************************
 *  childKeyDown														*
 *																		*
 *  Handle key strokes in text input fields in a child line.				*
 *																		*
 *  Parameters:																*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function childKeyDown(e)
{
    if (!e)
    {		// browser is not W3C compliant
		e	=  window.event;	// IE
    }		// browser is not W3C compliant
    var	code		= e.keyCode;
    var	element		= e.target;
    var	form		= element.form;

    // hide the help balloon on any keystroke
    if (helpDiv)
    {		// helpDiv currently displayed
		helpDiv.style.display	= 'none';
		helpDiv			= null;	// no longer displayed
    }		// helpDiv currently displayed
    clearTimeout(helpDelayTimer);	// clear pending help display
    helpDelayTimer		= null;

    // take action based upon code
    switch (code)
    {
		case KEY_F1:		// F1
		{
		    displayHelp(this);		// display help page
		    return false;		// suppress default action
		}			// F1

		case KEY_ENTER:
		{			// enter key
		    if (element)
		    {
				var	cell	= element.parentNode;
				var	row	= cell.parentNode;
				var	body	= row.parentNode;
				var	rownum	= row.sectionRowIndex;
				if (rownum < (body.rows.length - 1))
				{		// not the last row
				    rownum++;
				    row		= body.rows[rownum];
				    cell	= row.cells[0];
				    var	children= cell.children;
				    for(var ic = 0; ic < children.length; ic++)
				    {		// loop through children of cell
						var child	= children[ic];
						if (child.nodeName.toLowerCase() == 'input' &&
						    child.type == 'text')
						{	// first <input type='text'>
						    child.focus();
						    break;
						}	// first <input type='text'>
				    }		// loop through children of cell
				}		// not the last row
				else
				    form.addNewChild.onclick();
		    }
		    else
				alert("commonMarriage.js: childKeyDown: element is null.");
		    return false;		// suppress default action
		}			// enter key

		case ARROW_UP:
		{			// arrow up key
		    if (element)
		    {
				var	cell	= element.parentNode;
				var	row	= cell.parentNode;
				var	body	= row.parentNode;
				var	rownum	= row.sectionRowIndex;
				if (rownum > 0)
				{		// not the first row
				    rownum--;
				    row		= body.rows[rownum];
				    cell	= row.cells[cell.cellIndex];
				    var	children= cell.children;
				    for(var ic = 0; ic < children.length; ic++)
				    {		// loop through children of cell
						var child	= children[ic];
						if (child.nodeName.toLowerCase() == 'input' &&
						    child.type == 'text')
						{	// first <input type='text'>
						    child.focus();
						    break;
						}	// first <input type='text'>
				    }		// loop through children of cell
				}		// not the first row
		    }
		    else
				alert("commonMarriage.js: childKeyDown: element is null.");
		    return false;		// suppress default action
		}			// arrow up key

		case ARROW_DOWN:
		{			// arrow down key
		    if (element)
		    {
				var	cell	= element.parentNode;
				var	row	= cell.parentNode;
				var	body	= row.parentNode;
				var	rownum	= row.sectionRowIndex;
				if (rownum < (body.rows.length - 1))
				{		// not the last row
				    rownum++;
				    row		= body.rows[rownum];
				    cell	= row.cells[cell.cellIndex];
				    var	children= cell.children;
				    for(var ic = 0; ic < children.length; ic++)
				    {		// loop through children of cell
						var child	= children[ic];
						if (child.nodeName.toLowerCase() == 'input' &&
						    child.type == 'text')
						{	// first <input type='text'>
						    child.focus();
						    break;
						}	// first <input type='text'>
				    }		// loop through children of cell
				}		// not the last row
		    }
		    else
				alert("commonMarriage.js: childKeyDown: element is null.");
		    return false;		// suppress default action
		}			// arrow down key
    }	    // switch on key code

    return;
}		// childKeyDown
