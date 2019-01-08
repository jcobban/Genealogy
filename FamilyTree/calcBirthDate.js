/**
 *  calcBirthDate.js
 *
 *  Javascript code to implement dynamic functionality of the
 *  page calcBirthDate.php.
 *
 *  History:
 *	2010/12/12	created
 *	2012/01/13	change class names
 *	2013/07/31	defer setup of facebook link
 *
 *  Copyright &copy; 2013 James A. Cobban
 **/

    window.onload	= onLoad;

/**
 *  onLoad
 *
 *  This is the onload method of the page.  Initialize the dynamic
 *  functionality of all form elements.
 **/
function onLoad()
{
    pageInit();

    for (var fi = 0; fi < document.forms.length; fi++)
    {			// loop through all forms
	var	form		= document.forms[fi];
	if (form.name == "calcForm")
	{
	    // set action methods for elements
	    form.onsubmit		 	= validateForm;
	    form.onreset 			= resetForm;
	}	// main form

	for(var j = 0; j < form.elements.length; j++)
	{
	    var element	= form.elements[j];

	    // pop up help balloon if the mouse hovers over a field
	    // for more than 2 seconds
	    actMouseOverHelp(element);
	    element.onkeydown	= keyDown;

	    var	name	= element.name;
	    if (name === undefined || name.length == 0)
		name	= element.id;

	    // take action specific to the element based on its name
	    switch(name)
	    {		// switch on name
		case 'day':
		    element.focus();
		case 'mon':
		case 'year':
		case 'days':
		case 'mons':
		case 'years':
		{
		    element.onchange	= recalculate;
		    break;
		}
	    }		// switch on name
	}		// loop through all form elements
    }			// loop through all forms
}		// onLoad

/**
 *  validateForm
 *
 *  Ensure that the data entered by the user has been minimally validated
 *  before submitting the form.
 **/
function validateForm()
{
    // initially put the focus on the event day field
    // this permits the user to use the keyboard to enter the
    // information rather than the mouse
    var	form		= document.calcForm;
    form.day.focus();

    return false;
}		// validateForm

/**
 *  resetForm
 *
 *  This method is called when the user requests the form
 *  to be reset to default values.
 **/
function resetForm()
{
    return true;
}	// resetForm

/**
 *  daysinmonth
 *
 *  Array containing the number of days in each month.
 **/

var daysinmonth	= [31, 31, 28, 31, 30, 31, 30,
			   31, 31, 30, 31, 30, 31];

/**
 *  monthNames
 *
 *  Array containing the name of each month.
 **/

var monthNames	= ["December of previous year",
			   "January",
			   "February",
			   "March",
			   "April",
			   "May",
			   "June",
			   "July",
			   "August",
			   "September",
			   "October",
			   "November",
			   "December"];

/**
 *  recalculate
 *
 *  This method is called when the user modifies the value of
 *  a parameter to the calculation.
 *
 *  Parameters:
 *	this points to the input element whose value has been changed.
 **/
function recalculate()
{
    var form		= this.form;

    // get input for calculation
    var	eventDay	= form.day.value - 0;
    var	eventMon	= form.mon.value - 0;
    var	eventYear	= form.year.value - 0;

    var	ageYears	= form.years.value - 0;
    var	ageMonsGiven	= true;
    var	ageMons		= form.mons.value - 0;
    if (ageMons == -1)
    {
	ageMons	= 6;
	ageMonsGiven	= false;
    }
    var	ageDaysGiven	= true;
    var	ageDays		= form.days.value - 0;
    if (ageDays == -1)
    {
	if (ageMonsGiven)
	    ageDays	= 15;
	else
	    ageDays	= 0;
	ageDaysGiven	= false;
    }

    // subtract age from event date
    var	birthDay	= eventDay - ageDays;
    var birthMon	= eventMon - ageMons;
    var birthYear	= eventYear - ageYears;

    while(birthMon < 1)
    {		// carry from year
	birthMon	+= 12;
	birthYear	--;
    }		// carry from year

    while(birthDay < 1)
    {		// carry from month
	birthMon	--;
	if (birthMon < 1)
	{
	    birthMon	+= 12;
	    birthYear	--;
        }	// carry from year
	birthDay	+= daysinmonth[birthMon];
    }		// carry from month

    // update output
    var	tables	= form.getElementsByTagName('TABLE');
    var	birthDayCell	= document.getElementById('birthDay');
    while(birthDayCell.firstChild)
	birthDayCell.removeChild(birthDayCell.firstChild);
    if (ageDaysGiven)
	birthDayCell.appendChild(document.createTextNode(birthDay));

    var	birthMonCell	= document.getElementById('birthMon');
    while(birthMonCell.firstChild)
	birthMonCell.removeChild(birthMonCell.firstChild);
    if (ageMonsGiven)
	birthMonCell.appendChild(document.createTextNode(monthNames[birthMon]));

    var	birthYearCell	= document.getElementById('birthYear');
    while(birthYearCell.firstChild)
	birthYearCell.removeChild(birthYearCell.firstChild);
    birthYearCell.appendChild(document.createTextNode(birthYear));

    // if the cell that was changed is the age in years than set the
    // focus on the event day field, otherwise the focus would move into
    // one of the hyperlinks in the page trailer and the user would have
    // to use the mouse to reposition on the date.
    if (this.name == 'years')
	form.day.focus();
}		// recalculate

