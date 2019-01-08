/************************************************************************
 *  DistForm.js								*
 *									*
 *  Dynamic functionality of DistForm.php				*
 *									*
 *  History:								*
 *	2010/11/23	created						*
 *	2013/07/30	defer facebook initialization until after load	*
 *	2013/08/21	implement table functionality for arrow keys	*
 *	2013/08/27	selectively capitalize name and nom		*
 *			translate some English words to French in nom	*
 *	2013/08/30	popup help for subdistrict buttons		*
 *									*
 *  Copyright &copy; 2013 James A. Cobban				*
 ************************************************************************/

// Table for expanding abbreviations for French district name
var	LocAbbrsFr = {
		"And" :		"et",
		"Au" :		"au",
		"De" :		"de",
		"Et" :		"et",
		"North" :	"Nord",
		"East" :	"Est",
		"South" :	"Sud",
		"West" :	"Ouest",
		"City" :	"Ville",
		"Town" :	"Ville",
		"(City)" :	"(Ville)",
		"(Town)" :	"(Ville)"};

window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  The onload method of the web page.  This is invoked after the	*
 *  web page has been loaded into the browser. 				*
 ************************************************************************/
function onLoad()
{
    var	namePattern	= /^([^0-9]*)([0-9]{2,3})$/

    // perform common page initialization
    pageInit();

    // activate functionality for individual input elements
    for(var i = 0; i < document.forms.length; i++)
    {			// loop through all forms
	var form	= document.forms[i];
	for(var j = 0; j < form.elements.length; j++)
	{
	    var	element		= form.elements[j];
	    var name		= element.name;
	    if (!name || name.length == 0)
		name		= element.id;
	    var colName		= name;		// column name
	    var rowNum		= '';		// row number if present
	    var rgxResult	= namePattern.exec(name);
	    if (rgxResult)
	    {
		colName		= rgxResult[1];
		rowNum		= rgxResult[2];
	    }

	    // pop up help balloon if the mouse hovers over a element
	    // for more than 2 seconds
	    actMouseOverHelp(element);

	    // identify change action for each cell
	    switch(colName)
	    {		// switch on column name
		case 'D_Id':
		{	// District identifier
		    element.checkfunc	= checkNumber;
		    element.onchange	= change;
		    element.onkeydown	= tableKeyDown;
		    break;
		}	// District identifier

		case 'D_Name':
		{	// English name of district
		    element.checkfunc	= checkName;
		    element.abbrTbl	= LocAbbrs;
		    element.onchange	= changeName;
		    element.onkeydown	= tableKeyDown;
		    break;
		}	// English name of district

		case 'D_Nom':
		{	// French name of district
		    element.checkfunc	= checkName;
		    element.abbrTbl	= LocAbbrsFr;
		    element.onchange	= change;
		    element.onkeydown	= tableKeyDown;
		    break;
		}	// French name of district

		case 'D_Province':
		{	// Province Code
		    element.checkfunc	= checkProvince;
		    element.onchange	= changeProvince;
		    element.onkeydown	= tableKeyDown;
		    break;
		}	// Province Code

		case 'Submit':
		{	// submit update
		    break;
		}	// submit update

		default:
		{	// other fields
		    element.onchange	= change;
		    element.onkeydown	= tableKeyDown;
		    break;
		}	// other fields
	    }		// switch on column name
	}		// loop through all elements
    }			// loop through all forms

    // activate help for links
    for (var il=0; il < document.links.length; il++)
    {			// loop through all links
	var	link	= document.links[il];
	// pop up help balloon if the mouse hovers over a link
	// for more than 2 seconds
	if (link.id.substring(0,12) == 'ShowSubDists')
	    actMouseOverHelp(link);
    }			// loop through all links
}		// onLoad

/************************************************************************
 *  changeName								*
 *									*
 *  This function is called when the user changes the value of the	*
 *  English name of a district.						*
 *									*
 *  Input:								*
 *	this		an <input type='text'> element			*
 ************************************************************************/
function changeName()
{
    // perform common functonality
    changeElt(this);

    // if French district name is not set, synch it to English name
    var	nameNom		= "D_Nom" + this.name.substring(6);
    var elementNom	= this.form.elements[nameNom];
    if (elementNom && 
	(elementNom.value == "" ||
		elementNom.value.substring(0,9) == "District "))
    {
	elementNom.value	= this.value;
	elementNom.onchange();
    }

    if (this.checkfunc)
	this.checkfunc();
}		// changeName

/************************************************************************
 *  changeProvince							*
 *									*
 *  This function is called when the user changes the value of the	*
 *  code for a province.						*
 *									*
 *  Input:								*
 *	this		an <input type='text'> element			*
 ************************************************************************/
function changeProvince()
{
    this.value	= this.value.toUpperCase();

    if (this.checkfunc)
	this.checkfunc();
}		// changeProvince
