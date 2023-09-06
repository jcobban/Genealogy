/************************************************************************
 *  CountyMarriageReportEdit.js                                         *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  CountyMarriageReportEdit.php                                        *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/30      created                                         *
 *      2017/01/12      add display image button                        *
 *      2017/03/11      add edit details button                         *
 *      2017/07/18      invoke DistrictMarriagesEdit.php for CAUC       *
 *      2017/10/12      report errors from failed attempt to delete     *
 *                      a report                                        *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2020/11/21      show Image moved to shared library              *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *      2023/08/10      do not open new window                          *
 *                      use addEventListener                            *
 *                      migrate to ES6                                  *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {keyDown, debug, showImage}
            from "../jscripts6/util.js";
import {checkName, checkNumber,
        change, 
        GivnAbbrs, SurnAbbrs, RlgnAbbrs}
            from "../jscripts6/CommonForm.js";
import {HTTP}
            from "../jscripts6/js20/http.js";

window.addEventListener('load', onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize the dynamic functionality once the page is loaded        *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of Window                                  *
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

            let namePattern = /^([a-zA-Z_]+)(\d*)$/;
            let id              = element.id;
            if (id.length == 0)
                id              = element.name;
            let rresult         = namePattern.exec(id);
            let column          = id;
            if (rresult !== null)
            {
                column          = rresult[1];
            }

            //trace += "column='" + column + "', ";
            switch(column.toLowerCase())
            {       // act on specific fields
                case 'volume':
                case 'reportno':
                case 'page':
                {
                    element.addEventListener('change', change);
                    element.checkfunc   = checkNumber;
                    element.checkfunc();
                    break;
                }

                case 'domain':
                case 'residence':
                case 'remarks':
                {
                    element.addEventListener('change', change);
                    break;
                }

                case 'givennames':
                {
                    element.abbrTbl = GivnAbbrs;
                    element.addEventListener('change', change);
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;
                }

                case 'surname':
                {
                    element.abbrTbl = SurnAbbrs;
                    element.addEventListener('change', change);
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;
                }

                case 'faith':
                {
                    element.abbrTbl = RlgnAbbrs;
                    element.addEventListener('change', change);
                    element.checkfunc   = checkName;
                    element.checkfunc();
                    break;
                }

                case 'image':
                {
                    element.addEventListener('change', change);
                    //element.checkfunc();
                    break;
                }

                case 'details':
                {
                    element.addEventListener('click', editReport);
                    break;
                }

                case 'delete':
                {
                    element.addEventListener('click', deleteReport);
                    break;
                }

                case 'editmarriages':
                {
                    element.addEventListener('click', editMarriages);
                    break;
                }

                case 'displayimage':
                {
                    element.addEventListener('click', showImage);
                    break;
                }

                default:
                {
                    //alert("unexpected column='" + column + "'");
                    break;
                }
            }       // act on specific fields
        }           // loop through all elements in the form
    }               // loop through all forms
}       // function onLoad

/************************************************************************
 *  fnction deleteReport                                                *
 *                                                                      *
 *  When a Delete button is clicked this function removes the           *
 *  row from the table.                                                 *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button type=button id='Delete....'             *
 *      ev              'click' Event                                   *
 ************************************************************************/
function deleteReport(ev)
{
    let form            = this.form;
    let rownum          = this.id.substring(6);
    let domain          = form.Domain.value;
    let volume          = form.elements['Volume' + rownum].value;
    let report          = form.elements['ReportNo' + rownum].value;
    let script          = 'deleteCountyMarriageReportXml.php';
    let parms           = { 'Domain'    : domain,
                            'Volume'    : volume,
                            'ReportNo'  : report,
                            'rownum'    : rownum};
    if (debug != 'n')
    {
        parms["debug"]  = debug;
        alert("CountyMarriageReportEdit.js: deleteReport: 180 " +
                "parms  = { 'Domain'    : " + domain + "," +
                    "'Volume'   : "+volume+","+
                    "'ReportNo' : "+report+","+
                    "'rownum'   : "+rownum+"}");
    }

    // update the citation in the database
    HTTP.post(  script,
                parms,
                gotDeleteReport,
                noDeleteReport);

    ev.stopPropagation();
    return false;
}       // function deleteReport

/************************************************************************
 *  function gotDeleteReport                                            *
 *                                                                      *
 *  This method is called when the XML file representing                *
 *  the deletion of the report from the database is retrieved.          *
 *                                                                      *
 *  Input:                                                              *
 *      xmlDoc      response document                                   *
 ************************************************************************/
function gotDeleteReport(xmlDoc)
{
    if (xmlDoc === undefined)
    {
        alert("CountyMarriageReportEdit.js: gotDeleteReport: xmlDoc is undefined!");
    }
    else
    {                       // xmlDoc is defined
        let root                = xmlDoc.documentElement;
        if (debug != 'n')
            alert("CountyMarriageReportEdit.js: gotDeleteReport: " +
                  new XMLSerializer().serializeToString(root));

        let msgs                = root.getElementsByTagName('msg');
        if (msgs.length > 0)
        {
            let msg             = msgs[0].textContent.trim();
            alert(msg);
            return;
        }

        let parms               = root.getElementsByTagName('parms');
        if (parms.length > 0)
        {                   // have at least 1 parms element
            parms               = parms[0];
            let rownums         = parms.getElementsByTagName('rownum');
            if (rownums.length > 0)
            {               // have at least 1 rownum element
                let child       = rownums[0];
                let rownum      = child.textContent.trim();
                // remove identified row
                let rowid       = 'Row' + rownum;
                let row         = document.getElementById(rowid);
                let section     = row.parentNode;
                section.removeChild(row);
            }               // have at least 1 rownum element
        }                   // have at least 1 parms element
    }                       // xmlDoc is defined
}       // function gotDeleteReport

/************************************************************************
 *  function noDeleteReport                                             *
 *                                                                      *
 *  This method is called if there is no delete registration script.    *
 ************************************************************************/
function noDeleteReport()
{
    alert("CountyMarriageReportEdit.js: noDeleteReport: " +
                "script 'deleteCountyMarriageReportXml.php' not found on server");
}       // function noDeleteReport

/************************************************************************
 *  function editReport                                                 *
 *                                                                      *
 *  When a Report button is clicked this function displays the          *
 *  edit dialog for an individual report.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button type=button id='Details....'                *
 *      ev          'click' Event                                       *
 ************************************************************************/
function editReport(ev)
{
    let form            = this.form;
    let rownum          = this.id.substring(this.id.length - 2);
    let domain          = form.Domain.value;
    let volumeElt       = form.elements['Volume' + rownum];
    let volume          = volumeElt.value;
    let report          = form.elements['ReportNo' + rownum].value;
    location            = 'CountyReportDetails.php?Domain=' + domain +
                                        '&Volume=' + volume + 
                                        '&ReportNo=' + report;
    ev.stopPropagation();
    return false;
}       // function editReport

/************************************************************************
 *  function editMarriages                                              *
 *                                                                      *
 *  When a Marriages button is clicked this function displays the       *
 *  edit dialog for the list of marriages in a report.                  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button type=button id='EditMarriages....'      *
 *      ev              'click' Event                                   *
 ************************************************************************/
function editMarriages(ev)
{
    let form            = this.form;
    let rownum          = this.id.substring(this.id.length - 2);
    let domain          = form.Domain.value;
    let volume          = form.elements['Volume' + rownum].value;
    let report          = form.elements['ReportNo' + rownum].value;
    if (domain == 'CAUC')
        location        = 'DistrictMarriagesEdit.php?Domain=' + domain +
                            '&Volume=' + volume + '&ReportNo=' + report;
    else
        location        = 'CountyMarriagesEdit.php?Domain=' + domain +
                            '&Volume=' + volume + '&ReportNo=' + report;
    ev.stopPropagation();
    return false;
}       // editMarriages
