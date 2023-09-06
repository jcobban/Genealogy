/************************************************************************
 *  MarriageRegDetail.js                                                *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  MarriageRegDetail.php.                                              *
 *                                                                      *
 *  History:                                                            *
 *      2011/03/14      add keyboard shortcut support                   *
 *      2011/03/27      for cities default location does not include    *
 *                      function county                                 *
 *      2011/04/07      support keyboard shortcuts                      *
 *      2011/04/10      use selection list for license type             *
 *      2011/06/08      add abbreviation table for residence fields     *
 *                      set default value of Bride's religion to Groom's*
 *      2011/07/15      expand abbreviations for locations              *
 *      2011/08/08      rename to MarriageRegDetail                     *
 *      2011/09/04      support month name abbreviations in date of     *
 *                      function marriage                               *
 *                      use real buttons for next, previous, and new    *
 *                      function query                                  *
 *                      assign Alt-key combos to the buttons            *
 *      2011/09/11      open image in named window                      *
 *      2011/10/01      add abbreviations for father and mother's names *
 *                      and for witness names                           *
 *      2011/11/09      if age is not an integer, assume 20 in estimating*
 *                      birth year                                      *
 *                      Note that type of age column has been changed to*
 *                      VARCHAR(7) to allow for the value '[blank]'     *
 *      2012/02/13      expand county abbreviations                     *
 *      2012/07/14      use common getDefaultLocation function          *
 *      2013/02/05      add help for birth year display                 *
 *      2013/03/15      wrong help displayed for Reset button           *
 *      2013/06/27      use tinyMCE for editing remarks                 *
 *      2013/07/05      remove debug code from key handler              *
 *      2013/08/01      defer facebook initialization until after load  *
 *      2013/11/06      correct action of Ctl-S                         *
 *      2014/01/22      remove help division overrides                  *
 *                      support <fieldset>                              *
 *      2014/02/05      initialize functionality of MOccupation and     *
 *                      function MReligion                              *
 *      2014/02/28      permit age in years enclosed in square brackets *
 *      2014/09/12      remove use of obsolete selectOptByValue         *
 *      2014/10/11      get counties list using domain                  *
 *      2015/01/06      implement clear of IDIR association             *
 *      2015/05/01      use new dialog DisplayImage.php to show image   *
 *                      in right side of window if the image is on the  *
 *                      web site.                                       *
 *                      new parameter ShowImage directs the script      *
 *                      to immediately display the image                *
 *                      the previous and next registration buttons      *
 *                      pass the ShowImage flag                         *
 *                      pass RegDomain parameter when going to new      *
 *                      function registration                           *
 *      2015/06/11      correct too small text in rich-text editor      *
 *      2015/07/10      correct handling of previous and next buttons   *
 *                      for 1869, 1870, and 1871                        *
 *      2015/10/06      support image URL with https                    *
 *      2016/05/31      use common method dateChanged                   *
 *      2017/02/17      add fields OriginalVolume, OriginalPage, and    *
 *                      function OriginalItem                           *
 *      2017/03/17      birth year is displayed as text not <input>     *
 *      2017/07/12      use function locationChanged                    *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/05/19      call element.click to trigger button click      *
 *      2020/06/17      DisplayImage moved to top folder                *
 *      2020/07/03      add button to select Ontario Marriage License   *
 *      2020/11/21      move showImage to common utilities script       *
 *      2021/05/09      expand regdate                                  *
 *      2021/05/28      add a button to select default form layout      *
 *      2022/01/21      disable ShowImage button if image URL is empty  *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban.                              *
 ************************************************************************/

window.onload   = onLoadMarriage;

/************************************************************************
 *  function onLoadMarriage                                             *
 *                                                                      *
 *  This function is called after the associated web page,              *
 *  MarriageRegDetail.php, is completely loaded into the DOM.           *
 *                                                                      *
 *  Obtain the list of counties in the province as an XML file.         *
 *  Set appropriate event handlers on the form.                         *
 *  Initialize dynamic functionality of form elements.                  *
 ************************************************************************/
function onLoadMarriage()
{
    document.body.onkeydown     = mdKeyDown;

    // activate event handlers for the form
    var theForm         = document.distForm;
    theForm.onsubmit        = validateForm;

    // activate event handlers for individual elements
    var names   = '';
    var comma   = '';
    for (var i = 0; i < theForm.elements.length; i++)
    {               // act on every element within the form
        var element = theForm.elements[i];

        if (element.nodeName.toUpperCase() == 'FIELDSET')
            continue;

        // field is identified by attribute name if its value is
        // passed to the action script, otherwise by the id attribute
        var fldName = element.name;
        if (fldName.length == 0)
            fldName = element.id;
        names   += comma + fldName;
        comma   = ',';
        // default change handler
        element.onchange    = change;

        // activate common keystroke support
        element.onkeydown   = keyDown;

        // set up references to tables for expanding abbreviations
        // and set defaults for field values
        switch(fldName.toLowerCase())
        {           // switch on fldName
            case 'licensetypetxt':
            {       // hidden license type text field
                if (element.value.length == 0)
                    element.value   = 'L';  // default license
                theForm.LicenseType.value   = element.value;
                element.onchange    = change;
                break;
            }       // hidden license type text field

            case 'regcounty':
            {       // County of registration
                element.onchange    = changeCounty;
                var domain          = theForm.RegDomain.value;
                // get the counties information file
                HTTP.getXML("/Canada/CountiesListXml.php?Domain=" + domain,
                            gotCountiesFile,
                            noCountiesFile);
                break;
            }       // County of registration

            case 'regtownship':
            {       // County of registration
                element.onchange    = changeTownship;
                break;
            }       // County of registration

            case 'place':
            {       // place of marriage
                element.abbrTbl     = LocAbbrs;
                element.onchange    = locationChanged;
                element.checkfunc   = checkAddress;
                element.checkfunc();
                break;
            }       // place of marriage

            case 'date':
                element.focus();
            case 'regdate':
            {       // date of the marriage or registration
                element.abbrTbl             = MonthAbbrs;
                element.onchange            = dateChanged;
                element.checkfunc           = checkDate;
                element.checkfunc();
                break;
            }       // date of the marriage or registration

            case 'showimage':
            {   // display image button
                element.onclick             = showImage;
                    if (element.form.Image.value == '')
                        element.disabled    = true;
                    else
                if (typeof(args.showimage) == 'string' &&
                        args.showimage.toLowerCase() == 'yes')
                        element.click();
                break;
            }   // display image button

            case 'next':
            {       // button to go to next marriage registration
                element.onclick             = showNext;
                break;
            }       // button to go to next marriage registration

            case 'previous':
            {       // button to go to previous marriage registration
                element.onclick             = showPrevious;
                break;
            }       // button to go to previous marriage registration

            case 'newquery':
            {       // button to issue new query
                element.href                = theForm.newQueryHref.value;
                element.onclick             = goToLink;
                break;
            }       // button to issue new query

            case 'reset':
            {       // Reset button
                element.onclick             = resetForm;
                break;
            }       // Reset button

            case 'baselayout':
            {       // button to choose Ontario Marriage Licence layout
                element.onclick             = chooseBaseLayout;
                break;
            }       // Reset button

            case 'ontariolicense':
            {       // button to choose Ontario Marriage Licence layout
                element.onclick             = chooseOntarioLicenceLayout;
                break;
            }       // Reset button

            case 'gsurname':
            case 'bsurname':
            case 'msurname':
            {       // surname of a participant
                element.abbrTbl             = SurnAbbrs;
                element.checkfunc           = checkName;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // surname of a participant

            case 'gresidence':
            case 'bresidence':
            case 'mresidence':
            {       // current residence of a participant
                element.abbrTbl             = LocAbbrs;
                element.onchange            = locationChanged;
                element.checkfunc           = checkAddress;
                element.checkfunc();
                break;
            }       // current residence of a participant

            case 'gbirthplace':
            case 'bbirthplace':
            {       // birth place of participant
                element.abbrTbl             = BpAbbrs;
                element.onchange            = locationChanged;
                element.checkfunc           = checkAddress;
                element.checkfunc();
                break;
            }       // birth place of participant

            case 'greligion':
            {       // religious affiliation of groom
                element.onchange            = changeGReligion;
                element.abbrTbl             = RlgnAbbrs;
                element.checkfunc           = checkName;
                element.checkfunc();
                break;
            }       // religious affiliation of groom

            case 'breligion':
            case 'mreligion':
            {       // religious affiliation of bride
                element.abbrTbl             = RlgnAbbrs;
                element.checkfunc           = checkName;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // religious affiliation of bride

            case 'goccupation':
            {       // occupation of groom
                // for the groom set the default occupation as Farmer
                if (element.value.length == 0)
                    element.value           = 'Farmer';
                element.abbrTbl             = OccAbbrs;
                element.checkfunc           = checkOccupation;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // occupation of groom

            case 'boccupation':
            case 'moccupation':
            {       // occupation of bride
                element.abbrTbl             = OccAbbrs;
                element.checkfunc           = checkOccupation;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // occupation of bride

            case 'gmarstat':
            {       // marital status of groom
                if (element.value.length == 0)
                    element.value           = 'B';  // Bachelor
                element.checkfunc           = checkMStat;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // marital status of groom

            case 'bmarstat':
            {       // marital status of bride
                if (element.value.length == 0)
                    element.value           = 'S';  // Spinster
                element.checkfunc           = checkMStat;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // marital status of bride

            case 'ggivennames':
            case 'bgivennames':
            case 'mgivennames':
            case 'registrar':
            {       // given names of a participant
                element.abbrTbl             = GivnAbbrs;
                element.checkfunc           = checkName;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // given names of a participant

            case 'clearg':
            case 'clearb':
            case 'clearm':
            {       // clear IDIR association
                element.onclick             = clearIdir;
                break;
            }       // clear IDIR association

            case 'gfathername':
            case 'bfathername':
            {       // father's name
                element.abbrTbl             = GivnAbbrs;
                element.checkfunc           = checkName;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // father's name

            case 'gmothername':
            case 'bmothername':
            {       // mother's name
                element.abbrTbl             = GivnAbbrs;
                element.checkfunc           = checkName;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // mother's name

            case 'witness1':
            case 'witness2':
            {       // witness names
                element.abbrTbl             = GivnAbbrs;
                element.checkfunc           = checkName;
                element.onchange            = change;
                element.checkfunc();
                break;
            }       // witness names

            case 'witness1res':
            case 'witness2res':
            {       // witness address
                element.abbrTbl             = LocAbbrs;
                element.onchange            = locationChanged;
                element.checkfunc           = checkAddress;
                element.checkfunc();
                break;
            }       // witness address

            case 'gage':
            case 'bage':
            {       // age of bride or groom
                element.abbrTbl             = AgeAbbrs;
                element.onchange            = changeAge;
                element.checkfunc           = checkAge;
                element.checkfunc();
                break;
            }       // age of bride or groom

            case 'image':
            {
                element.checkfunc           = checkURL;
                element.onchange            = changeImage;
                element.checkfunc();
                break;
            }       // Image URL

            case 'gbirthyear':
            case 'bbirthyear':
            {       // birth year of bride or groom
                element.onchange            = change;
                break;
            }       // birth year of bride or groom

            default:
            {
                element.onchange    = change;
                break;
            }
        }           // switch on fldName
    }               // act on every element within the form
}       // function onLoadMarriage

/************************************************************************
 *  function changeAge                                                  *
 *                                                                      *
 *  Take action when the user changes the age of a partner in the       *
 *  marriage to update the display of the calculated birth year of      *
 *  the partner.                                                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text'>                                 *
 ************************************************************************/
function changeAge()
{
    var form            = this.form;
    var regYear         = form.RegYear.value;
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);
    var age             = this.value.trim();
    if (/^\[\d+\]$/.test(age))
        age             = age.substring(1,age.length - 1) - 0;
    else
    if (/^\d+$/.test(age))
        age             = age - 0;
    else
        age             = 20;
    var birthYear       = regYear - age;
    var role            = this.name.substring(0,1);
    var birthYearElt    = form.elements[role + 'BirthYear'];
    birthYearElt.value  = birthYear;
    var textElt         = document.getElementById(role + 'BirthYearText');
    textElt.innerHTML   = birthYear
    this.checkfunc();
}       // function changeAge

/************************************************************************
 *  function changeImage                                                *
 *                                                                      *
 *  Take action when the user changes the image URL.                    *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input name='Image'>                            *
 ************************************************************************/
function changeImage()
{
    let button                  = document.getElementById('ShowImage');
    if (this.value == '')
        button.disabled         = true;
    else
        button.disabled         = false;
    this.checkfunc();   // validate
}       // function changeImage

/************************************************************************
 *  function changeGReligion                                            *
 *                                                                      *
 *  This is the onchange handler for the Groom's religion.              *
 *  Take action when the user changes the religion of the Groom.        *
 *  Set the default value of the religion of the Bride to match.        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text'>                                 *
 ************************************************************************/
function changeGReligion()
{
    // expand abbreviations
    expAbbr(this,
            this.abbrTbl);
    var form                    = this.form;
    if (form.BReligion.value.length == 0)
        form.BReligion.value    = this.value;
    this.checkfunc();
}       // function changeGReligion

/************************************************************************
 *  validate Form                                                       *
 *                                                                      *
 *  Ensure that the data entered by the user has been minimally         *
 *  validated before submitting the form.                               *
 ************************************************************************/
function validateForm()
{
    var yearPat         = /^\d{4}$/;
    var numPat          = /^\d{1,6}$/;

    var msg             = "";
    if ((document.distForm.RegYear.value.length > 0) &&
        document.distForm.RegYear.value.search(yearPat) == -1)
        msg             = "Year is not 4 digit number. ";
    if ((document.distForm.RegNum.value.length > 0) &&
        document.distForm.RegNum.value.search(numPat) == -1)
        msg             += "Number is not a valid number. ";

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
 ************************************************************************/
function resetForm()
{
    var form                = document.distForm;
    var township            = form.RegTownship.value;
    var county              = form.RegCounty.value;
    var dftLocation         = getDefaultLocation(county, township);
    changeCounty(); // repopulate Township selection
    form.Date.value         = '';
    form.Place.value        = dftLocation;
    form.LicenseType.value  = 'L';
    form.Remarks.value      = '';
    form.GGivenNames.value  = '';
    form.GSurname.value     = '';
    form.GAge.value         = '';
    form.GResidence.value   = dftLocation;
    form.GBirthPlace.value  = dftLocation;
    form.GOccupation.value  = 'Farmer';
    form.GMarStat.value     = 'B';
    form.GReligion.value    = '';
    form.GFatherName.value  = '';
    form.GMotherName.value  = '';
    form.BGivenNames.value  = '';
    form.BSurname.value     = '';
    form.BAge.value         = '';
    form.BResidence.value   = dftLocation;
    form.BBirthPlace.value  = dftLocation;
    form.BOccupation.value  = '';
    form.BMarStat.value     = 'S';
    form.BReligion.value    = '';
    form.BFatherName.value  = '';
    form.BMotherName.value  = '';
    form.MGivenNames.value  = '';
    form.MSurname.value     = '';
    form.MResidence.value   = dftLocation;
    form.Witness1.value     = '';
    form.Witness1Res.value  = dftLocation;
    form.Witness2.value     = '';
    form.Witness2Res.value  = dftLocation;
    form.Date.focus();

    return false;   // do not perform default reset
}   // function resetForm

/************************************************************************
 *  function clearIdir                                                  *
 *                                                                      *
 *  This function is called when the user selects the clear IDIR        *
 *  assocation button with the mouse.                                   *
 *                                                                      *
 *  Input:                                                              *
 *      $this       <button id='ClearX'>                                *
 ************************************************************************/
function clearIdir(ev)
{
    ev.stopPropagation();
    var form                = this.form;
    var role                = this.id.substring(5,6);
    var idirElement         = document.getElementById(role + 'IDIR');
    var showElement         = document.getElementById(role + 'ShowLink');
    if (idirElement)
    {           // have IDIR element
        var parentNode      = idirElement.parentNode;
        if (showElement)
            parentNode.removeChild(showElement);// remove old <a href=''>
        idirElement.value   = 0;
        parentNode.appendChild(document.createTextNode("Cleared"));
        this.parentNode.removeChild(this);  // remove the button
    }           // have IDIR element
    else
        alert ("Cannot find '" + role + "IDIR' id='" + this.id + "'");
    return false;   // suppress any standard functionality
}       // function clearIdir

/************************************************************************
 *  function showPrevious                                               *
 *                                                                      *
 *  This function is called when the user clicks the Previous           *
 *  button with the mouse or types Alt-P.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Previous'>                              *
 ************************************************************************/
function showPrevious(ev)
{
    ev.stopPropagation();
    var form            = this.form;
    var prevUrl         = form.previousHref.value;
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        prevUrl         += "&ShowImage=Yes";
    location            = prevUrl;
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
function showNext(ev)
{
    ev.stopPropagation();
    var form        = this.form;
    var nextUrl     = form.nextHref.value;
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        nextUrl     += "&ShowImage=Yes";
    location        = nextUrl;
    return false;
}       // function showNext

/************************************************************************
 *  function chooseBaseLayout                                           *
 *                                                                      *
 *  This function is called when the user selects the default layout    *
 *  button with the mouse.                                              *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='baseLayout'>                        *
 ************************************************************************/
function chooseBaseLayout(ev)
{
    ev.stopPropagation();
    var form        = this.form;
    const tempPatt  = /&template=\w+/;
    var nextUrl     = location.href.replace(tempPatt, '');
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        nextUrl     += "&ShowImage=Yes";
    location        = nextUrl;
    return false;
}       // function chooseBaseLayout

/************************************************************************
 *  function chooseOntarioLicenceLayout                                 *
 *                                                                      *
 *  This function is called when the user selects the Ontario Marriage  *
 *  Licence button with the mouse.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='ontarioLicence'>                    *
 ************************************************************************/
function chooseOntarioLicenceLayout(ev)
{
    ev.stopPropagation();
    var form        = this.form;
    var nextUrl     = location.href + '&template=OntarioMarriageLicence';
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        nextUrl     += "&ShowImage=Yes";
    location        = nextUrl;
    return false;
}       // function chooseOntarioLicenceLayout

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
function showSkip5(ev)
{
    ev.stopPropagation();
    var form        = this.form;
    var regDomain   = form.RegDomain.value;
    var regYear     = form.RegYear.value;
    var regNum      = 5 + Number(form.RegNum.value);
    var nextUrl     = "MarriageRegDetail.php?RegDomain=" + regDomain +
                          "&RegYear=" + regYear +
                          "&RegNum=" + regNum;
    if (typeof(args.showimage) == 'string' &&
        args.showimage.toLowerCase() == 'yes')
        nextUrl     += "&ShowImage=Yes";
    location        = nextUrl;
    return false;
}       // function showSkip5

/************************************************************************
 *  function mdKeyDown                                                  *
 *                                                                      *
 *  Handle key strokes that apply to the entire dialog window.  For     *
 *  example the key combinations Ctrl-S and Alt-U are interpreted to    *
 *  apply the update, as shortcut alternatives to using the mouse to    *
 *  click the Update Individual button.                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function mdKeyDown(e)
{
    if (!e)
    {                   // browser is not W3C compliant
        e                   =  window.event;    // IE
    }                   // browser is not W3C compliant
    var form                = document.distForm;
    var code                = e.keyCode;

    // take action based upon code
    if (e.ctrlKey)
    {
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
                if (form.viewImage)
                    form.viewImage.click();
                break;
            }       // letter 'I'

            case 78:
            {       // letter 'N'
                form.next.click();
                break;
            }       // letter 'N'

            case 80:
            {       // letter 'P'
                form.previous.click();
                break;
            }       // letter 'P'

            case 81:
            {       // letter 'Q'
                form.newQuery.click();
                break;
            }       // letter 'Q'

            case 85:
            {       // letter 'U'
                form.submit();
                break;
            }       // letter 'U'

            default:
            {
                //alert("mdKeyDown: alt-" + code);
                break;
            }
        }       // switch on key code
    }       // alt key shortcuts

    return;
}       // function mdKeyDown

