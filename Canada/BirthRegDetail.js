/************************************************************************
 *  BirthRegDetail.js													*
 *																		*
 *  Implement dynamic functionality of page BirthRegDetail.php.			*
 *																		*
 *  History:															*
 *		2011/02/21		set initial focus on given name field			*
 *		2011/05/21		rename to BirthRegDetail.js						*
 *						add fields for place of work of parents			*
 *						use table of location abbreviations				*
 *						Update href if image file name changed			*
 *		2011/06/24		default residence of informant to residence		*
 *						of father										*
 *		2011/08/09		take action on fields that are present in form	*
 *		2011/08/13		smarter default place of occupation				*
 *		2011/11/06		use <button> in place of links					*
 *						support mouseover help							*
 *		2012/02/10		support short-cut key strokes					*
 *		2012/03/16		add Accoucheur to list of fields with given name*
 *						abbreviation support							*
 *		2012/06/21		clean up reset action							*
 *		2012/07/01		add Registrar name and registration date		*
 *		2012/07/11		use common routine default location				*
 *		2012/10/27		expand month abbreviations in marriage date		*
 *		2012/11/01		validate individual fields						*
 *		2013/06/27		use tinyMCE for editing remarks					*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/01/24		Sex selection list initialized by PHP			*
 *		2014/03/22		change informant when father's name changes		*
 *						if the informant was the same as the father		*
 *						before the value changed						*
 *		2014/04/02		invoke checkfunc for InformantName when			*
 *						changed by FatherName							*
 *		2014/10/11		get counties list using domain					*
 *		2015/01/24		wrong URL for new query							*
 *		2015/04/19		use new dialog DisplayImage.php to show image	*
 *						in right side of window if the image is on the	*
 *						web site.										*
 *		2015/05/01		new parameter ShowImage directs the script		*
 *						to immediately display the image				*
 *						the previous and next registration buttons		*
 *						pass the ShowImage flag							*
 *						pass RegDomain parameter when going to new		*
 *						registration									*
 *		2015/06/11		correct too small text in rich-text editor		*
 *		2015/10/06		support image URL with https					*
 *		2015/03/01		use common method dateChanged to improve		*
 *						handling of date fields							*
 *		2017/07/12		use function locationChanged					*
 *		2017/07/30		use locationChanged for father's occupation		*
 *						place and add afterChange handler				*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2019/05/18      call element.click() to trigger button click    *
 *		2020/06/02      correct handling of image URL starting with     *
 *		                /Images/                                        *
 *		                move DisplayImage.php to top of hierarchy       *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoadBirths;

/************************************************************************
 *  function onLoadBirths												*
 *																		*
 *  Initialize dynamic functionality of page.							*
 ************************************************************************/
function onLoadBirths()
{
    document.body.onresize	= onWindowResize;

    // activate global keystroke handling
    document.body.onkeydown		= ebKeyDown;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {		// loop through all forms
		var form	= document.forms[i];
		if (form.name == 'distForm')
		{	// main form
		    form.onsubmit 	= validateForm;
		    form.onreset 	= resetForm;
		}	// main form

		for(var j = 0; j < form.elements.length; j++)
		{	// loop through all elements of a form
		    var element		= form.elements[j];

		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handling

		    // an element whose value is passed with the update
		    // request to the server is identified by a name= attribute
		    // but elements which are used only by this script are
		    // identified by an id= attribute
		    var	name	= element.name;
		    if (name.length == 0)
				name	= element.id;

		    // set up dynamic functionality based on the name of the element
		    switch(name)
		    {
				case 'RegCounty':
				{	// county of registration selection list
				    element.disabled	= form.Surname.readOnly;
				    var	domain		= form.RegDomain.value;
				    // get the counties information file
				    HTTP.getXML("CountiesListXml.php?Domain=" + domain,
							gotCountiesFile,
							noCountiesFile);
				    break;
				}	// county of registration selection list

				case 'RegTownshipTxt':
				{	// township of registration text value
				    // select the desired entry in the township selection list
				    element.onchange	= changeTownship;
				    break;
				}	// township of registration text value

				case 'Surname':
				{	// surname
				    element.abbrTbl	= SurnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}	// surname

				case 'GivenNames':
				{
				    element.abbrTbl	= GivnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    // give focus to the given names field if present
				    element.focus();
				    break;
				}		// given name

				case 'Sex':
				{
				    element.disabled	= form.Surname.readOnly;
				    break;
				}		// gender

				case 'MotherName':
				case 'FormerHusband':
				case 'Informant':
				case 'Accoucheur':
				case 'Registrar':
				{
				    element.abbrTbl	= GivnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}		// other name fields

				case 'FatherName':
				{
				    element.abbrTbl	= GivnAbbrs;
				    element.onchange	= changeFatherName;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}		// father's name field

				case 'FatherOccupation':
				{
				    element.abbrTbl	= OccAbbrs;
				    element.onchange	= changeFatherOccupation;
				    element.checkfunc	= checkOccupation;
				    element.checkfunc();
				    break;
				}		// father's occupation field

				case 'MotherOccupation':
				{
				    element.abbrTbl	= OccAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkOccupation;
				    element.checkfunc();
				    break;
				}		// occupation fields

				case 'InformantRel':
				{
				    element.abbrTbl	= RelAbbrs;
				    element.onchange	= changeInformantRel;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    element.disabled	= form.Surname.readOnly;
				    break;
				}		// Informant Relation field

				case 'BirthPlace':
				case 'MotherOccPlace':
				case 'MarriagePlace':
				case 'InformantRes':
				{		// location fields
				    element.abbrTbl	= LocAbbrs;
				    element.onchange	= locationChanged;
				    element.checkfunc	= checkAddress;
				    element.checkfunc();
				    break;
				}		// location fields

				case 'BirthDate':
				case 'RegDate':
				case 'MarriageDate':
				{		// date fields
				    element.abbrTbl	= MonthAbbrs;
				    element.onchange	= dateChanged;
				    element.checkfunc	= checkDate;
				    element.checkfunc();
				    break;
				}		// date fields

				case 'FatherOccPlace':
				{
				    element.abbrTbl	= LocAbbrs;
				    element.onchange	= locationChanged;
				    element.afterChange	= afterChangeFatherOccPlace;
				    element.checkfunc	= checkAddress;
				    element.checkfunc();
				    break;
				}		// location fields

				case 'Image':
				{
				    element.checkfunc	= checkURL;
				    element.checkfunc();
				    break;
				}		// Image URL

				case 'clearIdir':
				{	// clear IDIR association
				    element.onclick	= clearIdir;
				    break;
				}	// clearIDIR association

				case 'ShowImage':
				{	// display image button
				    element.onclick	= showImage;
				    if (typeof(args.showimage) == 'string' &&
						args.showimage.toLowerCase() == 'yes')
						element.click();
				    break;
				}	// display image button

				case 'Previous':
				{	// display previous registration button
				    element.onclick	= showPrevious;
				    break;
				}	// display previous registration button

				case 'Next':
				{	// display next registration button
				    element.onclick	= showNext;
				    break;
				}	// display next registration button

				case 'Skip5':
				{	// skip 5 registrations button
				    element.onclick	= showSkip5;
				    break;
				}	// skip 5 registrations button

				case 'NewQuery':
				{	// display query dialog button
				    element.onclick	= showNewQuery;
				    break;
				}	// display query dialog button

		    }	// switch on field name
		}		// loop through all elements in the form
    }		// loop through forms in the page

}		// onLoadBirths

/************************************************************************
 *  function onWindowResize												*
 *																		*
 *  This method is called when the browser window size is changed		*
 *  If the window is split between the main display and a second		*
 *  frame, resize all of the half-window iframes.						*
 *																		*
 *  Input:																*
 *		this		Window object												*
 ************************************************************************/
function onWindowResize()
{
    var	body		= document.body;
    var	iframes		= body.getElementsByTagName('iframe');
    for(var fi = 0; fi < iframes.length; fi++)
    {			// loop through all iframes
		var iframe	= iframes[fi];
		if (iframe.src.substring(iframe.src.length - 10) == 'blank.html')
		    continue;
		if (iframe.className == "right")
		    openFrame(iframe.name, null, "right");
		else
		if (iframe.className == "left")
		    openFrame(iframe.name, null, "left");
    }			// loop through all iframes
}		// onWindowResize

/************************************************************************
 *  function validateForm												*
 *																		*
 *  Ensure that the data entered by the user has been minimally			*
 *  validated before submitting the form.								*
 ************************************************************************/
function validateForm()
{
    var	form	= this;
    var yearPat	= /^\d{4}$/;
    var numPat	= /^\d{1,6}$/;
    var countPat= /^\d{1,2}$/;

    var	msg	= "";
    if (form.RegYear)
    {
		if ((form.RegYear.value.length > 0) && 
		    form.RegYear.value.search(yearPat) == -1)
		    msg	= "Year is not 4 digit number. ";
    }
    else
		msg	= "RegYear field missing from input form. ";
    if (form.RegNum)
    {
		if ((form.RegNum.value.length > 0) &&
		    form.RegNum.value.search(numPat) == -1)
		    msg	+= "Number is not a valid number. ";
    }
    else
		msg	= "RegNum field missing from input form. ";
    if ((form.Count !== undefined) &&
		(form.Count.value.length > 0) &&
		(form.Count.value.search(countPat) == -1))
		msg	+= "Count is not a 1 or 2 digit number. ";

    if (msg != "")
    {
		alert(msg);
		return false;
    }
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm													*
 *																		*
 *  This method is called when the user requests the form				*
 *  to be reset to default values.										*
 *  This is required because the browser does not call the				*
 *  onchange method for form elements that have one.					*
 ************************************************************************/
function resetForm()
{
    var	form		= document.distForm;
    changeCounty();	// repopulate Township selection
    changeTownship();	// set defaults
    for(var j = 0; j < form.elements.length; j++)
    {	// loop through all elements of a form
		var element	= form.elements[j];
		var name	= element.name;
		if (name.length == 0)
		    name	= element.id;

		// set up dynamic functionality based on the name of the element
		switch(name)
		{
		    case "RegYear":
		    case "RegNum":
		    case "RegId":
		    case "MsVol":
		    {		// do not reset ident fields
				break;
		    }		// do not reset ident fields

		    case "ParentsMarried":
		    {		// do not reset ident fields
				element.checked		= true;
				break;
		    }		// do not reset ident fields

		    case "Sex":
		    {		// undetermined sex is default
				break;
		    }		// undetermined sex is default

		    default:
		    {
				if (element.type == "text")
				{		// <input type='text'>
				    element.value	= "";
				}		// <input type='text'>
				break;
		    }		// default
		}		// switch on name
    }	// loop through all elements of a form
    return false;
}	// resetForm

/************************************************************************
 *  function changeFatherName											*
 *																		*
 *  Take special action when the user changes the Father's Name field.	*
 *  If the Informant has not yet been set, initialize it to the Father.	*
 *																		*
 *  Input:																*
 *		$this		<input id='FatherName'>								*
 ************************************************************************/
function changeFatherName()
{
    capitalize(this);

    // expand abbreviations
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);

    var	form		= document.distForm;
    var informant	= form.Informant;
    if (informant && 
		(informant.value.length < 2 || 
		 (this.defaultValue == informant.defaultValue)))
    {
		informant.value		= this.value;
		informant.checkfunc();
		form.InformantRel.value	= "Father";
    }

    var surname		= form.Surname;
    if (surname && surname.value.length == 0)
    {		// copy father's surname into child's surname
		var names	= this.value.split(" ");
		surname.value	= names[names.length - 1];
    }		// copy father's surname into child's surname

    this.checkfunc();
}		// changeFatherName

/************************************************************************
 *  function changeFatherOccupation										*
 *																		*
 *  Take special action when the user changes the Father's Occupation	*
 *  field. If the place of occupation has not yet been set, initialize	*
 *  it to the registration township										*
 *																		*
 *  Input:																*
 *		$this			<input id='FatherOccupation'>					*
 ************************************************************************/
function changeFatherOccupation()
{
    capitalize(this);
    var	form		= document.distForm;
    var occPlace	= form.FatherOccPlace;
    var	county		= form.RegCountyTxt.value;
    var	township	= '';

    if (occPlace.value.length < 2)
    {		// occupation place not already set
		if (form.RegTownship.selectedIndex)
		{		// selection list
		    var	optIndex	= form.RegTownship.selectedIndex;
		    if (optIndex >= 0)
				township	= form.RegTownship.options[optIndex].value;
		}		// selection list
		else
		if (form.RegTownshipTxt)
		{		// text field
		    township	= form.RegTownshipTxt.value;
		}		// text field

		occPlace.value	= getDefaultLocation(county, township);
    }		// occupation place not already set

    // expand abbreviations in occupation
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);

    this.checkfunc();
}		// changeFatherOccupation

/************************************************************************
 *  function afterChangeFatherOccPlace									*
 *																		*
 *  Take special action when the user changes the Father's place of		*
 *  work.																*
 *																		*
 *  Input:																*
 *		$this			<input id='FatherOccPlace'>						*
 ************************************************************************/
function afterChangeFatherOccPlace()
{
    var	form			= this.form;
    form.InformantRes.value	= this.value;

    this.checkfunc();
}		// afterChangeFatherOccPlace

/************************************************************************
 *  function changeInformantRel 										*
 *																		*
 *  Take special action when the user changes the InformantRelation 	*
 *  field. If the new value is 'Mother' and the Informant is the same   *
 *  as the FatherName, then set the Informant to the given name portion *
 *  of the MotherName plus the Surname.                                 *
 *																		*
 *  Input:																*
 *		$this			<input id='InformantRelation'>					*
 ************************************************************************/
function changeInformantRel()
{
    capitalize(this);
    var	form		                = this.form;

    // expand abbreviations in InformantRelation
    if (this.abbrTbl)
		expAbbr(this,
				this.abbrTbl);

    var informant	                = form.Informant.value;
    if (this.value == 'Mother' &&
        informant == form.FatherName.value)
    {                           // changed to 'Mother'
        var motherName              = form.MotherName.value;
        var lastSpace               = motherName.lastIndexOf(' ');
        if (lastSpace > 0)
        {           
            var surname             = form.Surname.value;
            form.Informant.value    = motherName.substring(0, lastSpace) +
                                      ' ' + surname;
        }
    }                           // changed to 'Mother'

    this.checkfunc();           // validate
}		// changeInformantRel

/************************************************************************
 *  function clearIdir													*
 *																		*
 *  This function is called when the user selects the clearIdir button	*
 *  with the mouse.														*
 *																		*
 *  Input:																*
 *		$this		<button id='clearIdir'>								*
 ************************************************************************/
function clearIdir()
{
    var	form		= this.form;
    var	idirElement	= document.getElementById('IDIR');
    var	showElement	= document.getElementById('showLink');
    if (idirElement)
    {			// have IDIR element
		var	parentNode	= idirElement.parentNode;
		if (showElement)
		    parentNode.removeChild(showElement);// remove old <a href=''>
		idirElement.value	= 0;
		parentNode.appendChild(document.createTextNode("Cleared"));
		this.parentNode.removeChild(this);	// remove the button
    }			// have IDIR element
    return false;
}		// clearIdir

/************************************************************************
 *  function showImage													*
 *																		*
 *  This function is called when the user selects the ShowImage button	*
 *  with the mouse.														*
 *																		*
 *  Input:																*
 *		$this		<button id='ShowImage'>								*
 ************************************************************************/
function showImage()
{
    var	form		= this.form;
    if (form.Image)
    {		// Image field defined
		args.showimage	= 'yes';	// previous and next request image
		var imageUrl	= form.Image.value;
		if (imageUrl.length == 0)
		    popupAlert("DeathRegDetail.js: showImage: " +
				            "no image defined for this registration",
                        this);
		else
		if (imageUrl.length > 5 &&
		    (imageUrl.substring(0,5) == "http:" ||
		     imageUrl.substring(0,6) == "https:"))
		    openFrame("Images",
				      imageUrl,
				      "right");
		else
        if (imageUrl.substring(0,8) == '/Images/')
		    openFrame("Images",
				      '/DisplayImage.php?src=' + imageUrl,
				      "right");
        else
        if (imageUrl.substring(0,1) == '/')
		    openFrame("Images",
				      '/DisplayImage.php?src=/Images' + imageUrl,
				      "right");
        else
		    openFrame("Images",
				      '/DisplayImage.php?src=/Images/' + imageUrl,
				      "right");
    }		// Image field defined
    return false;
}		// showImage

/************************************************************************
 *  function showPrevious												*
 *																		*
 *  This function is called when the user selects the ShowPrevious		*
 *  button with the mouse.												*
 *																		*
 *  Input:																*
 *		$this			<button id='ShowPrevious'>						*
 ************************************************************************/
function showPrevious()
{
    var	form		= this.form;
    var regDomain	= form.RegDomain.value;
    var regYear		= form.RegYear.value;
    var regNum		= Number(form.RegNum.value) - 1;
    var	prevUrl		= "BirthRegDetail.php?RegDomain=" + regDomain +
						  "&RegYear=" + regYear +
						  "&RegNum=" + regNum;
    if (typeof(args.showimage) == 'string' &&
		args.showimage.toLowerCase() == 'yes')
		prevUrl		+= "&ShowImage=Yes";
    location		= prevUrl;
    return false;
}		// showPrevious

/************************************************************************
 *  function showNext													*
 *																		*
 *  This function is called when the user selects the ShowNext button	*
 *  with the mouse.														*
 *																		*
 *  Input:																*
 *		$this		<button id='ShowNext'>								*
 ************************************************************************/
function showNext()
{
    var	form		= this.form;
    var regDomain	= form.RegDomain.value;
    var regYear		= form.RegYear.value;
    var regNum		= 1 + Number(form.RegNum.value);
    var	nextUrl		= "BirthRegDetail.php?RegDomain=" + regDomain +
						  "&RegYear=" + regYear +
						  "&RegNum=" + regNum;
    if (typeof(args.showimage) == 'string' &&
		args.showimage.toLowerCase() == 'yes')
		nextUrl		+= "&ShowImage=Yes";
    location		= nextUrl;
    return false;
}		// showNext

/************************************************************************
 *  function showSkip5													*
 *																		*
 *  This function is called when the user selects the ShowSkip5 button	*
 *  with the mouse.														*
 *																		*
 *  Input:																*
 *		$this		<button id='ShowSkip5'>								*
 ************************************************************************/
function showSkip5()
{
    var	form		= this.form;
    var regDomain	= form.RegDomain.value;
    var regYear		= form.RegYear.value;
    var regNum		= 5 + Number(form.RegNum.value);
    var	nextUrl		= "BirthRegDetail.php?RegDomain=" + regDomain +
						  "&RegYear=" + regYear +
						  "&RegNum=" + regNum;
    if (typeof(args.showimage) == 'string' &&
		args.showimage.toLowerCase() == 'yes')
		nextUrl		+= "&ShowImage=Yes";
    location		= nextUrl;
    return false;
}		// showSkip5

/************************************************************************
 *  function showNewQuery												*
 *																		*
 *  This function is called when the user selects the ShowNewQuery		*
 *  button with the mouse.												*
 *																		*
 *  Input:																*
 *		$this			<button id='ShowNewQuery'>						*
 ************************************************************************/
function showNewQuery()
{
    var	form		= this.form;
    var	domain		= form.RegDomain.value;
    location	= "BirthRegQuery.php?Domain=" + domain;
    return false;	// suppress default action
}		// showNewQuery

/************************************************************************
 *  function ebKeyDown													*
 *																		*
 *  Handle key strokes that apply to the entire dialog window.  For		*
 *  example the key combinations Ctrl-S and Alt-U are interpreted to	*
 *  apply the update, as shortcut alternatives to using the mouse to 	*
 *  click the Update Individual button.									*
 *																		*
 *  Parameters:																*
 *		e		W3C compliant browsers pass an event as a parameter		*
 ************************************************************************/
function ebKeyDown(e)
{
    if (!e)
    {			// browser is not W3C compliant
		e	=  window.event;	// IE
    }			// browser is not W3C compliant
    var	code	= e.keyCode;
    var	form	= document.distForm;

    // take action based upon code
    if (e.ctrlKey)
    {			// ctrl key shortcuts
		if (code == 83)
		{		// letter 'S'
		    if (form.onsubmit())
				form.submit();
		    return false;	// do not perform standard action
		}		// letter 'S'
    }			// ctrl key shortcuts
    
    if (e.altKey)
    {			// alt key shortcuts
        switch (code)
        {
		    case 73:
		    {		// letter 'I'
		        document.getElementById('ShowImage').click();
		        return false;
		    }		// letter 'I'
    
		    case 78:
		    {		// letter 'N'
		        document.getElementById('Next').click();
		        return false;
		    }		// letter 'N'

		    case 80:
		    {		// letter 'P'
		        document.getElementById('Previous').click();
		        return false;
		    }		// letter 'P'

		    case 81:
		    {		// letter 'Q'
		        document.getElementById('NewQuery').click();
		        return false;
		    }		// letter 'Q'
    
		    case 85:
		    {		// letter 'U'
				if (form.onsubmit())
				    form.submit();
		        return false;
		    }		// letter 'U'

        }		// switch on key code
    }			// alt key shortcuts

    return true;	// do default action
}		// ebKeyDown
