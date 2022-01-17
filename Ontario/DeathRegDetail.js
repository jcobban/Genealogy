/************************************************************************
 *  DeathRegDetail.js                                                   *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page DeathRegDetail.php.                                            *
 *                                                                      *
 *  History:                                                            *
 *      2011/04/07      support keyboard shortcuts                      *
 *      2011/04/23      change name of file                             *
 *                      add code to compute birth date from age         *
 *      2011/07/15      expand abbreviations for locations              *
 *      2011/08/13      use switch on field names to make the code      *
 *                      independent of which fields the designed chose  *
 *                      to include in the form.                         *
 *      2011/09/04      support month name abbreviations in date of     *
 *                      function death                                  *
 *                      use real buttons for next, previous, and        *
 *                      new query                                       *
 *                      assign Alt-key combos to the buttons            *
 *      2011/11/06      use <button> in place of links                  *
 *                      support mouseover help                          *
 *      2012/05/15      correct spelling error in field name InfoOccn   *
 *      2012/06/06      handle age 0 correctly                          *
 *                      support age in hours                            *
 *      2012/06/15      correct spelling of father's and mother's birth *
 *                      place fields                                    *
 *      2012/07/01      support 1/2 and ½ in numeric portions of ages   *
 *      2012/10/06      support month abbreviations in birth and        *
 *                      burial dates                                    *
 *      2012/11/01      validate individual fields                      *
 *      2012/11/13      expand abbreviations in death cause field       *
 *      2013/03/23      expand abbreviations in cause duration field    *
 *      2013/06/27      use tinyMCE for editing remarks                 *
 *                      remove use of Select for informant relationship *
 *                      replacing with abbreviation support             *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2013/10/13      correct action of Ctl-S                         *
 *      2013/12/14      use common age abbreviations table              *
 *      2014/03/08      sex select statement initialized by PHP         *
 *                      marital status select initialized by PHP        *
 *                      keyboard Alt-I did not work                     *
 *      2014/04/07      set default for husband's name if married       *
 *                      set default for father's name if informant      *
 *                      is father                                       *
 *      2014/05/10      changeAge did not invoke checkFunction          *
 *                      if calculated birth date is only year           *
 *      2014/08/07      add support for clearing IDIR association       *
 *      2014/09/11      update marital status based on occupation       *
 *                      and age                                         *
 *      2014/09/22      fraction "1/2" replaced with "½" in age and     *
 *                      duration of fatal illness                       *
 *      2014/10/11      get counties list using domain                  *
 *      2015/05/01      use new dialog DisplayImage.php to show image   *
 *                      in right side of window if the image is on the  *
 *                      web site.                                       *
 *                      new parameter ShowImage directs the script      *
 *                      to immediately display the image                *
 *                      the previous and next registration buttons      *
 *                      pass the ShowImage flag                         *
 *                      pass RegDomain parameter when going to new      *
 *                      function registration                           *
 *      2015/06/11      correct too small font in rich-text editor      *
 *      2015/10/06      support image URL with https                    *
 *      2015/11/18      display "hours" for abbreviation "h"            *
 *                      all standard durations words made singular      *
 *                      after "1".                                      *
 *      2015/01/09      add "or" abbreviation for duration              *
 *      2016/05/20      counties list script moved to folder Canada     *
 *      2016/05/30      use common function dateChanged                 *
 *      2017/07/12      use function locationChanged                    *
 *      2019/02/10      no longer need to call pageInit                 *
*       2019/03/29      do not alter marital status based on informant  *
*                       occupation                                      *
*                       if informant is undertaker update undertaker    *
*       2019/04/12      loosen syntax for age                           *
 *      2019/05/19      call element.click to trigger button click      *
 *      2020/06/01      correct handling of 9m9d age                    *
 *      2020/06/17      DisplayImage moved to top folder                *
 *      2020/11/21      move showImage to common utilities script       *
 *      2021/02/23      add abbreviations for durations                 *
 *      2021/04/24      add Residence field                             *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  daysinmonth                                                         *
 *                                                                      *
 *  Array containing the number of days in each month.                  *
 *      daysinmonth[0] is for December of the preceding year            *
 *      daysinmonth[1] is for January                                   *
 *      ...                                                             *
 *      daysinmonth[12] is for December                                 *
 ************************************************************************/
//                      0   1   2   3   4   5   6
var daysinmonth     = [31, 31, 28, 31, 30, 31, 30,
//                          7   8   9  10  11  12
                           31, 31, 30, 31, 30, 31];


/************************************************************************
 *  monthIndex                                                          *
 *                                                                      *
 *  object translating month names to month numbers                     *
 ************************************************************************/

var monthIndex  = {"jan"            : 1,
                   "jany"           : 1,
                   "january"        : 1,
                   "feb"            : 2,
                   "feby"           : 2,
                   "february"       : 2,
                   "mar"            : 3,
                   "march"          : 3,
                   "apr"            : 4,
                   "apl"            : 4,
                   "april"          : 4,
                   "may"            : 5,
                   "jun"            : 6,
                   "june"           : 6,
                   "jul"            : 7,
                   "july"           : 7,
                   "aug"            : 8,
                   "augt"           : 8,
                   "august"         : 8,
                   "sep"            : 9,
                   "sept"           : 9,
                   "september"      : 9,
                   "oct"            : 10,
                   "october"        : 10,
                   "nov"            : 11,
                   "november"       : 11,
                   "dec"            : 12,
                   "december"       : 12};

/************************************************************************
 *  monthNames                                                          *
 *                                                                      *
 *  Array containing the name of each month.                            *
 ************************************************************************/

var monthNames  = ["Dec",
                   "Jan",
                   "Feb",
                   "Mar",
                   "Apr",
                   "May",
                   "Jun",
                   "Jul",
                   "Aug",
                   "Sep",
                   "Oct",
                   "Nov",
                   "Dec"];

/************************************************************************
 *  DurationAbbrs                                                       *
 *                                                                      *
 *  Table for expanding abbreviations for age to standardize            *
 *  representation of fractional ages and to define words which are     *
 *  not capitalized.                                                    *
 ************************************************************************/
var DurationAbbrs = {
                    "1/4" :                 "¼",
                    "1/2" :                 "½",
                    "3/4" :                 "¾",
                    "Abt" :                 "about",
                    "About" :               "about",
                    "After" :               "after",
                    "Before" :              "before",
                    "D" :                   "days",
                    "Day" :                 "day",
                    "Days" :                "days",
                    "Few" :                 "few",
                    "From" :                "from",
                    "H" :                   "hours",
                    "Hour" :                "hour",
                    "Hours" :               "hours",
                    "M" :                   "months",
                    "Month" :               "month",
                    "Months" :              "months",
                    "Of" :                  "of",
                    "One" :                 "one",
                    "Or" :                  "or",
                    "Sev" :                 "several",
                    "Sev." :                "several",
                    "Several" :             "several",
                    "Some" :                "some",
                    "To" :                  "to",
                    "W" :                   "weeks",
                    "Week" :                "week",
                    "Weeks" :               "weeks",
                    "Y" :                   "years",
                    "Year" :                "year",
                    "Years" :               "years",
                    "[" :                   "[blank]"
                    };

/************************************************************************
 *  Invoke initialization once the entire page is loaded                *
 ************************************************************************/
window.onload   = onLoadDeath;

/************************************************************************
 *  function onLoadDeath                                                *
 *                                                                      *
 *  This function is called when the web page has been loaded into the  *
 *  browser.  Initialize dynamic functionality of elements.             *
 ************************************************************************/
function onLoadDeath()
{
    document.body.onkeydown     = ddKeyDown;

    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {       // loop through all forms
        var form        = document.forms[i];
        form.onsubmit   = validateForm;
        form.onreset    = resetForm;

        for(var j = 0; j < form.elements.length; j++)
        {   // loop through all elements of a form
            var element             = form.elements[j];

            element.onkeydown       = keyDown;
            element.onchange        = change;   // default handling

            // an element whose value is passed with the update
            // request to the server is identified by a name= attribute
            // but elements which are used only by this script are
            // identified by an id= attribute
            var name                = element.name;
            if (name.length == 0)
                name                = element.id;

            // set up dynamic functionality based on the name of the element
            switch(name.toLowerCase())
            {       // act on specific fields
                case 'regcounty':
                {
                    var domain      = form.RegDomain.value;
                    // get the counties information file
                    HTTP.getXML("/Canada/CountiesListXml.php?Domain=" + domain,
                                gotCountiesFile,
                                noCountiesFile);
                    break;
                }

                case 'surname':
                {
                    element.abbrTbl     = SurnAbbrs;
                    element.onchange    = changeSurname;
                    element.onkeydown   = keyDown;  // special key handling
                    element.checkfunc   = checkName;
                    element.checkfunc();            // check initial value
                    break;
                }   // surname field

                case 'givennames':
                {
                    element.abbrTbl     = GivnAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = change;   // default handler
                    element.checkfunc   = checkName;
                    element.checkfunc();            // check initial value
                    // give focus to the given names field if present
                    element.focus();
                    break;
                }   // given names field

                case 'fathername':
                case 'mothername':
                case 'husbandname':
                case 'informant':
                case 'phys':
                case 'registrar':
                {
                    element.abbrTbl     = GivnAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = change;   // default handler
                    element.checkfunc   = checkName;
                    element.checkfunc();            // check initial value
                    break;
                }   // other names

                case 'sex':
                {
                    element.disabled    = form.Surname.readOnly;
                    break;
                }   // Sex field

                case 'birthplace':
                case 'fatherbplce':
                case 'motherbplce':
                {
                    element.abbrTbl     = BpAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = locationChanged;
                    element.checkfunc   = checkAddress;
                    element.checkfunc();            // check initial value
                    break;
                }   // birthplace fields

                case 'place':
                case 'residence':
                case 'physaddr':
                case 'infres':
                case 'burplace':
                case 'undertkraddr':
                {
                    element.abbrTbl     = LocAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = locationChanged;
                    element.checkfunc   = checkAddress;
                    element.checkfunc();            // check initial value
                    break;
                }   // location fields

                case 'occupation':
                {
                    element.abbrTbl     = OccAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = changeOccupation;
                    element.checkfunc   = checkOccupation;
                    element.checkfunc();            // check initial value
                    break;
                }           // occupation field

                case 'infocc':
                {
                    element.abbrTbl     = OccAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = changeInfOcc;
                    element.checkfunc   = checkOccupation;
                    element.checkfunc();            // check initial value
                    break;
                }           // informant occupation field

                case 'marstat':
                {
                    element.disabled    = form.Surname.readOnly;
                    element.onchange    = changeMarStat;
                    break;
                }   // marital status field

                case 'regtownshiptxt':
                {
                    element.onchange    = changeTownship;
                    element.onkeydown   = keyDown;  // special key handling
                    break;
                }   // township name as text field

                case 'age':
                {
                    element.abbrTbl     = AgeAbbrs;
                    element.onchange    = changeAge;
                    element.checkfunc   = checkAge;
                    element.checkfunc();            // check initial value
                    break;
                }   // age field

                case 'birthdate':
                {
                    element.abbrTbl     = MonthAbbrs;
                    element.onchange    = dateChanged;
                    if (element.value.length == 0)
                        form.Age.onchange();
                    element.checkfunc   = checkDate;
                    element.checkfunc();            // check initial value
                    break;
                }   // age field

                case 'religion':
                {
                    element.abbrTbl     = RlgnAbbrs;
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = change;   // default handler
                    element.checkfunc   = checkName;
                    element.checkfunc();            // check initial value
                    break;
                }   // religion field

                case 'infrel':
                {
                    element.abbrTbl     = RelAbbrs;
                    element.onchange    = changeInfRel;
                    element.checkfunc   = checkName;
                    element.checkfunc();            // check initial value
                    element.disabled    = form.Surname.readOnly;
                    break;
                }   // marital status field

                case 'reset':
                {
                    element.onclick     = resetForm;
                    break;
                }   // reset button

                case 'date':
                case 'regdate':
                case 'burdate':
                {
                    element.abbrTbl     = MonthAbbrs;
                    element.onchange    = dateChanged;  // default handler
                    element.checkfunc   = checkDate;
                    element.checkfunc();            // check initial value
                    break;
                }

                case 'cause':
                {
                    element.abbrTbl     = CauseAbbrs;
                    element.onchange    = change;   // default handler
                    element.checkfunc   = checkText;
                    element.checkfunc();            // check initial value
                    break;
                }

                case 'duration':
                {
                    element.abbrTbl     = DurationAbbrs;
                    element.onchange    = changeDuration;
                    element.checkfunc   = checkText;
                    element.checkfunc();            // check initial value
                    break;
                }

                case 'image':
                {
                    element.checkfunc   = checkURL;
                    element.checkfunc();            // check initial value
                    break;
                }       // Image URL

                case 'clearidir':
                {   // clear IDIR association
                    element.onclick     = clearIdir;
                    break;
                }   // clearIDIR association

                case 'showimage':
                {   // display image button
                    element.onclick     = showImage;
                    if (typeof(args.showimage) == 'string' &&
                        args.showimage.toLowercase() == 'yes')
                        element.click();
                    break;
                }   // display image button

                case 'previous':
                {   // display previous registration button
                    element.onclick     = showPrevious;
                    break;
                }   // display previous registration button

                case 'next':
                {   // display next registration button
                    element.onclick     = showNext;
                    break;
                }   // display next registration button

                case 'skip5':
                {   // skip 5 registrations button
                    element.onclick     = showSkip5;
                    break;
                }   // skip 5 registrations button

                case 'newquery':
                {   // display query dialog button
                    element.onclick     = showNewQuery;
                    break;
                }   // display query dialog button

                default:
                {
                    element.onkeydown   = keyDown;  // special key handling
                    element.onchange    = change;   // default handler
                    element.checkfunc   = checkText;
                    element.checkfunc();            // check initial value
                    break;
                }   // other fields
            }       // act on specific fields
        }           // loop through all elements in the form
    }               // loop through forms in the page

}       // function onLoadDeath

/************************************************************************
 *  function changeSurname                                              *
 *                                                                      *
 *  Take action when the surname of the deceased is changed.            *
 *  The surname is capitalized, and abbreviations are expanded and      *
 *  the default value of the father's name is set.                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='Surname'>                          *
 ************************************************************************/
function changeSurname()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    var form                    = this.form;
    var fatherName              = form.FatherName;
    if (this.value.length > 0 && fatherName.value.length == 0)
    {       // father's name not set yet
        // default to surname of deceased
        fatherName.value        =  "[" + this.value + "]";
    }       // father's name not set yet

    this.checkfunc();   // validate
}       // function changeSurname

/************************************************************************
 *  function changeMarStat                                              *
 *                                                                      *
 *  Act when the marital status of the deceased is changed.             *
 *  Set defaults for the father's name and the husband's name.          *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input name='MarStat'>                              *
 ************************************************************************/
function changeMarStat()
{

    var form                    = this.form;
    var surname                 = form.Surname;
    var sex                     = form.Sex;
    var fatherName              = form.FatherName;
    var husbandName             = form.HusbandName;
    if (this.value == 'S')
    {
        if (fatherName && fatherName.value.length == 0)
            fatherName.value    =  "[" + surname.value + "]";
        if (husbandName)
            husbandName.value   =  '';
    }       // single
    else
    if (sex.value == 'F' && (this.value == 'M' || this.value == 'W'))
    {       // married
        if (husbandName && husbandName.value.length == 0)
            husbandName.value   =  "[" + surname.value + "]";
        if (fatherName)
            fatherName.value    =  '';
    }       // married
}       // function changeMarStat

/************************************************************************
 *  function changeInfRel                                               *
 *                                                                      *
 *  Take action when the informant's relation to the deceased changes.  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='InfRel'>                           *
 ************************************************************************/
function changeInfRel()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    var form                    = this.form;
    var informantName           = form.Informant;
    if (this.value == 'Father')
        form.FatherName.value   = informantName.value;
    else
    if (this.value == 'Mother')
        form.MotherName.value   = informantName.value;
    else
    if (this.value == 'Husband')
        form.HusbandName.value  = informantName.value;

    this.checkfunc();   // validate
}       // function changeInfRel

/************************************************************************
 *  function changeFatherName                                           *
 *                                                                      *
 *  Take action when the user changes the name of the father            *
 *  of the deceased.                                                    *
 *  Note that this function is not currently referenced.                *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='FatherName'>                       *
 ************************************************************************/
function changeFatherName()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    var form        = this.form;
    var informant   = form.Informant;
    if (informant.value.length == 0)
    {   // informant name has not yet been set
        // set informant name default to father's name
        informant.value     = this.value;
        // set informant relation default to Father
        form.InformantRel.value = "Father";
    }   // informant name has not yet been set

    this.checkfunc();   // validate
}       // function changeFatherName

/************************************************************************
 *  function changeAge                                                  *
 *                                                                      *
 *  Take action when the user changes the age at death.                 *
 *  Compute the birth date.                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this                <input name='Age'>                          *
 ************************************************************************/
function changeAge()
{
    var rey     = /^[[a-zA-Z ]*(([0-9½]+)\s*([a-zA-Z]*|))(.*)/;

    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    // replace 1/2 with symbol ½
    var form                    = this.form;
    var age                     = form.Age.value.replace('1/2','½');
    form.Age.value              = age;

    this.checkfunc();   // validate

    var birthDate               = form.BirthDate;
    if (birthDate.value.length == 0 ||
        birthDate.value.substring(0,1) == '[')
    {       // birth date not explicitly set
        age                     = age.toLowerCase();

        // extract the components of the age
        var years, months, weeks, days, hours, t;
        years                   = 0;
        months                  = 0;
        weeks                   = 0;
        days                    = 0;
        hours                   = 0;

        var results             = rey.exec(age);
        if (results)
        {
            years               = getNumeric(results[2]);
            if (results[3].length > 0) 
                t               = results[3].substring(0,1).toLowerCase();
            else
                t               = 'y';
            if (t == 'y')
            {
                var rest            = results[4];
                results             = rey.exec(rest);
                if (results)
                {
                    months          = getNumeric(results[2]);
                    if (results[3].length > 0)
                        t           = results[3].substring(0,1).toLowerCase();
                    else
                        t           = 'm';
                    if (t == 'm')
                    {
                        rest                    = results[4];
                        results                 = rey.exec(rest);
                        if (results)
                        {
                            days                = getNumeric(results[2]);
                            if (results[3].length > 0)
                            {
                                var t           = results[3].substring(0,1).toLowerCase();
                                if (t == 'd')
                                {
                                }
                                if (t == 'w')
                                {
                                    weeks       = days;
                                    days        = 0;
                                }
                            }
                        }
                    }
                    else
                    if (t == 'w')
                    {
                        weeks       = months;
                        months      = 0;
                    }
                    else
                    if (t == 'd')
                    {
                        days        = months;
                        months      = 0;
                    }
                }
            }               // first number is year
            else
            if (t == 'm')
            {
                months          = years;
                years           = 0;
                if (results)
                {
                    var rest    = results[4];
                    results     = rey.exec(rest);
                    if (results)
                    {
                        days    = getNumeric(results[2]);
                    }
                }
            }
            else
            if (t == 'w')
            {
                weeks           = years;
                years           = 0;
            }
            else
            if (t == 'd')
            {
                days            = years;
                years           = 0;
            }
            else
            if (t == 'h')
            {
                hours           = years;
                years           = 0;
            }
        }

        // include the number of weeks and any fractional portion of
        // the months in the days portion
        // For Example:
        //  "1½m" is interpreted as 1m15d
        //  "1½w" is interpreted as 10d
        //  "1½y" is interpreted as 1y6m
        days                    = Math.floor(days + 7 * weeks +
                                    30 * (months - Math.floor(months)));
        months                  = Math.floor(months +
                                    12 * (years - Math.floor(years)));
        years                   = Math.floor(years);

        // extract the components of the death date
        var deathDate           = form.elements["Date"].value.toLowerCase();
        var day                 = 0;
        var month               = 0;
        var name                = "";   // name of month
        var year                = 0;
        var num                 = 0;

        for(var i = 0; i < deathDate.length; i++)
        {
            var c               = deathDate.charAt(i);
            switch(c)
            {       // act on character
                case '0':
                case '1':
                case '2':
                case '3':
                case '4':
                case '5':
                case '6':
                case '7':
                case '8':
                case '9':
                {       // numeric digit
                    num         = (num * 10) + (c - '0');
                    break;
                }       // numeric digit

                case ' ':
                case '\t':
                {       // space
                    if (num > 31)
                    {   // must be year
                        year    = num;
                        num     = 0;
                    }   // must be year
                    else
                    if (num > 0)
                    {   // must be day of month
                        day     = num;
                        num     = 0;
                    }   // must be day of month
                    break;
                }       // space

                default:
                {       // part of name of month
                    name        += c;
                    break;
                }       // part of name of month
            }
        }               // scan the date

        if (num > 31)
        {               // must be year
            year                = num;
            num                 = 0;
        }               // must be year
        else
        if (num > 0)
        {               // must be day of month
            day                 = num;
            num                 = 0;
        }               // must be day of month

        // interpret the month name
        month                   = monthIndex[name];
        if (month === undefined || month === null)
            month               = 0;

        // handle missing year in date (e.g. date given as "21 mar")
        if (year == 0)
            year                = form.RegYear.value - 0;

        // subtract age from death date to get birth date
        var bDay                = day - days;
        var bMon                = month - months;
        var bYear               = year - years;

        // if necessary carry from year
        while(bMon < 1)
        {       // carry from year
            bMon                += 12;
            bYear--;
        }       // carry from year

        // if necessary carry from month column
        while(bDay < 1)
        {       // carry from months
            bMon--;
            if (bMon < 1)
            {
                bMon            += 12;
                bYear--;    // carry from year
            }   // from previous year
            bDay                += daysinmonth[bMon];
        }       // carry from months

        if (month == 0 || (years != 0 && months == 0 && days == 0))
        {       // only show year of birth
            birthDate.value     = "[" + bYear + "]";
        }       // only show year of birth
        else
        {       // show calculated date of birth
            // display calculated birth date
            birthDate.value     = "[" + bDay + " " +
                                  monthNames[bMon] + " " + 
                                  bYear + "]";
        }       // show calculated date of birth

        // also set internal calculated birth date
        if (form.CalcBirth)
            form.CalcBirth.value= bYear + '-' + bMon + '-' + bDay;

        var marStat = form.MarStat;
        var optIndex            = MarStat.selectedIndex;
        var marStatVal          = '?';
        if (optIndex >= 0)
        {
            var option          = marStat.options[optIndex];
            marStatVal          = option.value;
        }

        // adjust marital status based on age
        if (age.length > 0 && marStatVal == '?' && years < 16)
        {       // too young to be married
            marStat.value       = 'S';
            // apply changes implied by change in marital status
            marStat.onchange();
        }       // too young to be married
    }           // birth date not explicitly set
}       // function changeAge

/************************************************************************
 *  function changeDuration                                             *
 *                                                                      *
 *  Take action when the user changes the duration of illness.          *
 *  Compute the birth date.                                             *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='Duration'>                         *
 ************************************************************************/
function changeDuration()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    var val     = this.value;
    val         = val.replace(/\b1\shours/i,'1 hour');
    val         = val.replace(/\b1\sdays/i,'1 day');
    val         = val.replace(/\b1\sweeks/i,'1 week');
    val         = val.replace(/\b1\smonths/i,'1 month');
    val         = val.replace(/\b1\syears/i,'1 year');
    val         = val.replace(/\bone\shours/i,'1 hour');
    val         = val.replace(/\bone\sdays/i,'1 day');
    val         = val.replace(/\bone\sweeks/i,'1 week');
    val         = val.replace(/\bone\smonths/i,'1 month');
    this.value      = val.replace(/\bone\syears/i,'1 year');
    this.checkfunc();   // validate
}       // function changeDuration

/************************************************************************
 *  function changeOccupation                                           *
 *                                                                      *
 *  Take action when the user changes the occupation of the deceased.   *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='Occupation'>                       *
 ************************************************************************/
function changeOccupation()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    this.checkfunc();   // validate entered value

    // some values suggest marital status
    var form        = this.form;
    var occupation  = this.value;
    var marStat     = form.MarStat;
    var optIndex    = marStat.selectedIndex;
    var marStatVal  = '?';
    if (optIndex >= 0)
    {
        var option  = marStat.options[optIndex];
        marStatVal  = option.value;
    }
    if (marStatVal == '?')
    {           // check for occupation that implies status
        if (occupation.indexOf('Spinster') >= 0 ||
            occupation.indexOf('Son') >= 0 ||
            occupation.indexOf('Daughter') >= 0 ||
            occupation.indexOf('Infant') >= 0 ||
            occupation.indexOf('Baby') >= 0 ||
            occupation.indexOf('Child') >= 0)
        {
            marStat.value   = 'S';
        }
        else
        if (occupation.indexOf('Wife') >= 0 ||
            occupation.indexOf('wife') >= 0 ||
            occupation.indexOf('Matron') >= 0)
        {
            marStat.value   = 'M';
        }
        else
        if (occupation.indexOf('Widow') >= 0)
        {
            marStat.value   = 'W';
        }

        // apply changes implied by change in marital status
        marStat.onchange();
    }           // check for occupation that implies status

}       // function changeOccupation

/************************************************************************
 *  function changeInfOcc                                               *
 *                                                                      *
 *  Take action when the user changes the occupation of the informant.  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='InfOcc'>                           *
 ************************************************************************/
function changeInfOcc()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);

    this.checkfunc();   // validate entered value

    if (this.value == 'Undertaker')
    {                   // informant is the undertaker
        var form        = this.form;
        form.Undertkr.value = form.Informant.value;
    }                   // informant is the undertaker
}       // function changeInfoOcc

/************************************************************************
 *  validate Form                                                       *
 *                                                                      *
 *  Ensure that the data entered by the user has been minimally         *
 *  validated before submitting the form.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this            instance of <form>                              *
 ************************************************************************/
function validateForm()
{
    var form        = this;
    var yearPat     = /^\d{4}$/;
    var numPat      = /^\d{1,8}$/;
    var countPat    = /^\d{1,2}$/;

    var msg = "";
    if ((form.RegYear.value.length > 0) && 
        form.RegYear.value.search(yearPat) == -1)
        msg = "Year is not 4 digit number. ";
    if ((form.RegNum.value.length > 0) &&
        form.RegNum.value.search(numPat) == -1)
        msg += "Number is not a valid number. ";
    var Count   = form.Count;
    if (Count &&
        (Count.value.length > 0) &&
        (Count.value.search(countPat) == -1))
        msg += "Count is not a 1 or 2 digit number. ";

    if (msg != "")
    {
        alert(msg);
        return false;
    }
    return true;
}       // function validateForm

/************************************************************************
 *  function resetForm                                                  *
 *                                                                      *
 *  This method is called when the user requests the form               *
 *  to be reset to default values.                                      *
 *  This is required because the browser does not call the              *
 *  onchange method for form elements that have one.                    *
 *                                                                      *
 *  Input:                                                              *
 *      this            instance of <form>                              *
 ************************************************************************/
function resetForm()
{
    changeTownship();
    return true;
}       // function resetForm

/************************************************************************
 *  function clearIdir                                                  *
 *                                                                      *
 *  This function is called when the user selects the clearIdir button  *
 *  with the mouse.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      $this           <button id='clearIdir'>                         *
 ************************************************************************/
function clearIdir()
{
    var form        = this.form;
    var idirElement = document.getElementById('IDIR');
    var showElement = document.getElementById('showLink');
    if (idirElement)
    {           // have IDIR element
        var parentNode  = idirElement.parentNode;
        if (showElement)
            parentNode.removeChild(showElement);// remove old <a href=''>
        idirElement.value   = 0;
        parentNode.appendChild(document.createTextNode("Cleared"));
        this.parentNode.removeChild(this);  // remove the button
    }           // have IDIR element
    return false;
}       // function clearIdir

/************************************************************************
 *  function showPrevious                                               *
 *                                                                      *
 *  This function is called when the user clicks the Previous           *
 *  button with the mouse or types Alt-P.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Previous'>                          *
 ************************************************************************/
function showPrevious()
{
    var form        = this.form;
    var regDomain   = form.RegDomain.value;
    var regYear     = form.RegYear.value;
    var regNum      = Number(form.RegNum.value) - 1;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args['lang'];
    var prevUrl     = "DeathRegDetail.php?RegDomain=" + regDomain +
                          "&RegYear=" + regYear +
                          "&RegNum=" + regNum + '&lang=' + lang;
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        prevUrl     += "&ShowImage=Yes";
    location        = prevUrl;
    return false;
}       // function showPrevious

/************************************************************************
 *  function showNext                                                   *
 *                                                                      *
 *  This function is called when the user selects the Next button       *
 *  with the mouse or types Alt-N.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Next'>                              *
 ************************************************************************/
function showNext()
{
    var form        = this.form;
    var regDomain   = form.RegDomain.value;
    var regYear     = form.RegYear.value;
    var regNum      = 1 + Number(form.RegNum.value);
    var lang        = 'en';
    if ('lang' in args)
        lang        = args['lang'];
    var nextUrl     = "DeathRegDetail.php?RegDomain=" + regDomain +
                          "&RegYear=" + regYear +
                          "&RegNum=" + regNum + '&lang=' + lang;
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        nextUrl     += "&ShowImage=Yes";
    location        = nextUrl;
    return false;
}       // function showNext

/************************************************************************
 *  function showSkip5                                                  *
 *                                                                      *
 *  This function is called when the user selects the Skip5 button      *
 *  with the mouse.                                                     *
 *  This is not currently referenced.                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='Skip5'>                             *
 ************************************************************************/
function showSkip5()
{
    var form        = this.form;
    var regDomain   = form.RegDomain.value;
    var regYear     = form.RegYear.value;
    var regNum      = 5 + Number(form.RegNum.value);
    var lang        = 'en';
    if ('lang' in args)
        lang        = args['lang'];
    var nextUrl     = "DeathRegDetail.php?RegDomain=" + regDomain +
                          "&RegYear=" + regYear +
                          "&RegNum=" + regNum + '&lang=' + lang;
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        nextUrl     += "&ShowImage=Yes";
    location        = nextUrl;
    return false;
}       // function showSkip5

/************************************************************************
 *  function showNewQuery                                               *
 *                                                                      *
 *  This function is called when the user clicks the NewQuery           *
 *  button with the mouse or types Alt-Q.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='NewQuery'>                          *
 ************************************************************************/
function showNewQuery()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args['lang'];
    location    = "DeathRegQuery.php?lang=" + lang;
    return false;
}       // function showNewQuery

/************************************************************************
 *  function ddKeyDown                                                  *
 *                                                                      *
 *  Handle key strokes that apply to the entire dialog window.  For     *
 *  example the key combinations Ctrl-S and Alt-U are interpreted to    *
 *  apply the update, as shortcut alternatives to using the mouse to    *
 *  click the Update Individual button.                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function ddKeyDown(e)
{
    if (!e)
    {       // browser is not W3C compliant
        e   =  window.event;    // IE
    }       // browser is not W3C compliant
    var form    = document.distForm;
    var code    = e.keyCode;

    // take action based upon code
    if (e.ctrlKey)
    {       // ctrl key shortcuts
        if (code == 83)
        {       // letter 'S'
            form.submit();
            return false;   // do not perform standard action
        }       // letter 'S'
    }       // ctrl key shortcuts

    if (e.altKey)
    {       // alt key shortcuts
        switch (code)
        {
            case 67:
            {       // letter 'C'
                form.Reset.click();
                break;
            }       // letter 'C'

            case 73:
            {       // letter 'I'
                if (form.ShowImage)
                    form.ShowImage.click();
                break;
            }       // letter 'I'

            case 78:
            {       // letter 'N'
                form.Next.click();
                break;
            }       // letter 'N'

            case 80:
            {       // letter 'P'
                form.Previous.click();
                break;
            }       // letter 'P'

            case 81:
            {       // letter 'Q'
                form.NewQuery.click();
                break;
            }       // letter 'Q'

            case 85:
            {       // letter 'U'
                form.submit();
                break;
            }       // letter 'U'

        }       // switch on key code
    }       // alt key shortcuts

    return;
}       // function ddKeyDown

