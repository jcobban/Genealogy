/************************************************************************
 *  CountyMarriageEditQuery.js                                          *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  CountyMarriageEditQuery.php                                         *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/30      created                                         *
 *      2016/05/31      use common function dateChanged                 *
 *      2017/10/22      display result from Upper Canada using          *
 *                      DistrictMarriagesEdit.php                       *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2023/08/10      use addEventListener                            *
 *                      migrate to ES6                                  *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {keyDown}
            from "../jscripts6/util.js";
import {checkName, checkAddress, checkDate, checkNumber, checkText,
        change, dateChanged,
        GivnAbbrs, SurnAbbrs, MonthAbbrs}
            from "../jscripts6/CommonForm.js";

window.addEventListener('load', onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize the dynamic functionality once the page is loaded        *
 *                                                                      *
 *  Input:                                                              *
 *      this            Window object                                   *
 *      ev              'load' Event                                    *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    let element;
    //let trace                   = '';
    for (let fi = 0; fi < document.forms.length; fi++)
    {       // loop through all forms
        let form                = document.forms[fi];

        for (let i = 0; i < form.elements.length; ++i)
        {   // loop through all elements of form
            element             = form.elements[i];
            element.addEventListener('keydown', keyDown);

            let namePattern     = /^([a-zA-Z_]+)(\d*)$/;
            let id              = element.id;
            if (id.length == 0)
                id              = element.name;
            let rresult         = namePattern.exec(id);
            let column          = id;
            if (rresult !== null)
            {
                column          = rresult[1];
            }

            switch(column.toLowerCase())
            {           // act on specific fields
                case 'givennames':
                case 'witnessname':
                    element.abbrTbl     = GivnAbbrs;
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', changeAction);
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;      // given names field

                case 'surname':
                    element.abbrTbl     = SurnAbbrs;
                    element.addEventListener('keydown', keyDown);   
                    element.addEventListener('change', changeAction);
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;      // surname field

                case 'soundex':
                    element.addEventListener('change', changeAction);
                    break;      // soundex field


                case 'residence':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', changeAction);
                    element.checkfunc   = checkAddress;
                    element.checkfunc(); 
                    break;      // location fields

                case 'date':
                    element.abbrTbl     = MonthAbbrs;
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', dateChanged);
                    element.checkfunc   = checkDate;
                    element.checkfunc();
                    break;

                case 'volume':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', change); 
                    element.checkfunc   = checkNumber;
                    element.checkfunc();
                    break;

                case 'reportno':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', changeAction);
                    element.checkfunc   = checkNumber;
                    element.checkfunc();
                    break;

                case 'itemno':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', changeAction);
                    element.checkfunc   = checkNumber;
                    element.checkfunc();
                    break;

                case 'domain':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', change); 
                    element.checkfunc   = checkText;
                    element.checkfunc();
                    break;

                case 'remarks':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', change); 
                    element.checkfunc   = checkText;
                    element.checkfunc();
                    break;

                case 'role':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', change); 
                    //element.checkfunc = checkFlagBG;
                    //element.checkfunc();
                    break;

                case 'licensetype':
                    element.addEventListener('keydown', keyDown);
                    element.addEventListener('change', change);
                    //element.checkfunc = checkFlagBL;
                    //element.checkfunc();
                    break;

                case 'status':
                case 'stats':
                    element.addEventListener('click', showStatus);
                    break;

            }           // act on specific fields
        }               // loop through all elements in the form
    }                   // loop through all forms
}       // function onLoad

/************************************************************************
 *  function changeAction                                               *
 *                                                                      *
 *  If search values are entered in any of the fields referencing       *
 *  individuals, then the query is directed to CountyMarriagesEdit.php  *
 *  instead of CounryMarriageReportEdit.php.                            *
 *                                                                      *
 *  Input:                                                              *
 *      this            <input type="text"                              *
 *      ev              'change' Event                                  *
 ************************************************************************/
function changeAction()
{
    let form            = this.form;
    if (form.RegDomain.value == 'CAUC')
        form.action     = 'DistrictMarriagesEdit.php';
    else
        form.action     = 'CountyMarriagesEdit.php';
}       // function changeAction

/************************************************************************
 *  function showStatus                                                 *
 *                                                                      *
 *  Display the volume summary.                                         *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button is='status'                             *
 *      ev              'click' Event                                   *
 ************************************************************************/
function showStatus()
{
    let form    = this.form;
    let domain  = form.RegDomain.value;
    location    = 'CountyMarriageVolumeSummary.php?Domain=' + domain;
}       // function showStatus
