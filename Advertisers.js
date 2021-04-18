/************************************************************************
 *  Advertisers.js                                                      *
 *                                                                      *
 *  This file implements the dynamic functionality of the web page      *
 *  Advertisers.php                                                     *
 *                                                                      *
 *  History:                                                            *
 *      2020/01/13      created                                         *
 *      2021/04/01      use ES2015 imports                              *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
import {eltMouseOver, eltMouseOut, keyDown, createFromTemplate}
        from "../jscripts6/util.js";
import {changeElt} from "../jscripts6/CommonForm.js";

window.addEventListener("load", onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize the dynamic functionality once the page is loaded        *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    let element;
    for (var fi = 0; fi < document.forms.length; fi++)
    {       // loop through all forms
        let form        = document.forms[fi];

        for (var i = 0; i < form.elements.length; ++i)
        {   // loop through all elements of form
            element                     = form.elements[i];
            element.onkeydown           = keyDown;

            let name                    = element.name;
            if (name.length == 0)
                name                    = element.id;
            let column                  = name;
            //let row                     = '';
            let results                 = /^([a-zA-Z_$#]+)(\d*)/.exec(name);
            if (results)
            {
                column                  = results[1];
                //row                     = results[2];
            }
            column                      = column.toLowerCase();

            switch (column)
            {       // act on a field from a table row
                case 'adname':
                {       // advertiser name
                    element.onchange    = changeName;
                    break;
                }       // advertiser name

                case 'ademail':
                {       // advertiser email
                    break;
                }       // advertiser email

                case 'delete':
                {       // delete this advertiser
                    element.onclick     = deleteAdvertiser;
                    break;
                }       // delete this advertiser

                case 'add':
                {
                    element.onclick     = addAdvertiser;
                    break;
                }
            }           // act on a field
        }               // loop through all elements in the form
    }                   // loop through all forms

    let nameHead            = document.getElementById('NameHead');
    let actionsHead         = document.getElementById('ActionsHead');
    let nameFoot            = document.getElementById('NameFoot');
    let actionsFoot         = document.getElementById('ActionsFoot');
    nameFoot.style.width    = nameHead.clientWidth + 'px'; 
    actionsFoot.style.width = actionsHead.clientWidth + 'px'; 
}       // function onLoad

/************************************************************************
 *  function changeName                                                 *
 *                                                                      *
 *  Take action when the user changes the advertiser name.              *
 *                                                                      *
 *  Input:                                                              *
 *      $this           instance of <input name='AdName...'>            *
 ************************************************************************/
function changeName()
{
    changeElt(this);
}       // function changeName

/************************************************************************
 *  function deleteAdvertiser                                           *
 *                                                                      *
 *  When a Delete button is clicked this function removes the           *
 *  row from the table.                                                 *
 *                                                                      *
 *  Input:                                                              *
 *      $this           <button type=button id='Delete....'             *
 ************************************************************************/
function deleteAdvertiser()
{
    let trownum             = this.id.substring(6);
    let rownumelt           = document.getElementById('RowNum' + trownum);
    let recid               = rownumelt.value;
    let form                = this.form;
    let cell                = this.parentNode;
    let row                 = cell.parentNode;
    let section             = row.parentNode;
    section.removeChild(row);
    let operator            = document.createElement('input');
    operator.type           = 'hidden';
    operator.name           = 'deleteAdvertiser' + recid;
    operator.id             = 'deleteAdvertiser' + recid;
    operator.value          = 'deleteAdvertiser';
    form.appendChild(operator);

    return false;
}       // function deleteAdvertiser

/************************************************************************
 *  function addAdvertiser                                              *
 *                                                                      *
 *  When the Add advertiser button is clicked this function adds a row  *
 *  into the table.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      $this       <button type=button id='Add'>                       *
 ************************************************************************/
function addAdvertiser()
{
    this.disabled       = true; // only permit one row to be added
    let table           = document.getElementById("dataTable");
    let tbody           = table.tBodies[0];
    let rowId           = tbody.rows.length + 1;
    let className       = 'even';
    if ((rowId % 2) == 1)
        className       = 'odd';
    let parms           = {'adname' :       'New Advertiser',
                           'ademail' :      '',
                           'id' :           rowId,
                           'row' :          rowId,
                           'rowtype' :      className,
                           'count01':       '',
                           'count02':       '',
                           'count03':       '',
                           'count04':       '',
                           'count05':       '',
                           'count06':       '',
                           'count07':       '',
                           'count08':       '',
                           'count09':       '',
                           'count10':       '',
                           'count11':       '',
                           'count12':       '' };
    let template        = document.getElementById("Row$id");
    let newRow          = createFromTemplate(template,
                                             parms,
                                             null);
    tbody.appendChild(newRow);

    // take action when the user changes the name of the added advertiser
    let nameElt         = document.getElementById('AdName' + rowId);
    nameElt.focus();
    nameElt.select();
    nameElt.onchange    = changeName;

    return false;
}       // function addAdvertiser
