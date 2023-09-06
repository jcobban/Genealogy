/************************************************************************
 *  Addresses.js                                                        *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page Addresses.php.                                                 *
 *                                                                      *
 *  History:                                                            *
 *      2012/01/13      change class names                              *
 *      2013/02/23      move setting onload function here               *
 *      2013/05/29      use actMouseOverHelp common function            *
 *                      standardize initialization                      *
 *      2013/07/31      defer setup of facebook link                    *
 *      2017/08/04      class LegacyAddress renamed to Address          *
 *      2018/02/12      add support for passing language to scripts     *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2023/07/29      migrate to Es2015                               *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
import {keyDown, args}
            from "../jscripts6/util.js";
import {change}
            from "../jscripts6/CommonForm.js";

window.addEventListener('load', onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Initialize elements.                                                *
 ************************************************************************/
function onLoad()
{
    // activate dynamic functionality for elements
    for (let fi = 0; fi < document.forms.length; fi++)
    {               // loop through all forms in the document
        let form            = document.forms[fi];

        if (form.name == 'locForm')
        {           // main form
            // set action methods for elements
            form.addEventListener('submit', validateForm);
            form.addEventListener('reset', resetForm);
        }           // main form

        // activate handling of key strokes in text input fields
        let formElts        = form.elements;
        for (let i = 0; i < formElts.length; ++i)
        {           // loop through all elements in the form
            let element     = formElts[i];
    
            element.addEventListener('keydown', keyDown);
            element.addEventListener('change', change);  // default handler
            
            let name        = element.name;
            if (name === undefined || name.length == 0)
                name        = element.id;
            //let idar        = '';
            let results     = /^([a-zA-Z]+)(\d+)$/.exec(name);
            if (results)
            {
                name        = results[1];
                //idar        = results[2];
            }
            
            switch(name.toLowerCase())
            {       // act on specific elements
                case 'add':
                {
                    element.addEventListener('click', addAddress);
                    break;
                }   // add an address

                case 'delete':
                {
                    element.addEventListener('click', delAddress);
                    break;
                }   // add an address

            }       // act on specific elements
        }           // loop through all elements in the form
    }               // loop through all forms in the document
}       // function onLoadAddresses

/************************************************************************
 *  function validateForm                                               *
 *                                                                      *
 *  Ensure that the data entered by the user has been minimally         *
 *  validated before submitting the form.                               *
 ************************************************************************/
function validateForm()
{
    return true;
}       // function validateForm

/************************************************************************
 *  function resetForm                                                  *
 *                                                                      *
 *  This method is called when the user requests the form               *
 *  to be reset to default values.                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id="Reset">                             *
 ************************************************************************/
function resetForm()
{
    return true;
}   // function resetForm

/************************************************************************
 *  function addAddress                                                 *
 *                                                                      *
 *  This method is called when the user requests to add a new address.  *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id="Add">                               *
 *      ev              click Event                                     *
 ************************************************************************/
function addAddress(ev)
{
    let lang                = 'en';
    if ('lang' in args)
        lang                = args['lang'];
    location                = 'Address.php?idar=0&kind=2&lang=' + lang;

    ev.stopPropagation;
    return false;
}   // function addAddress

/************************************************************************
 *  function delAddress                                                 *
 *                                                                      *
 *  This method is called when the user requests to delete an address.  *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id="Delete9999">                            *
 *      ev              click Event                                     *
 ************************************************************************/
function delAddress(ev)
{
    let form                = this.form;
    let idar                = this.id.substring(6);
    let cell                = this.parentNode;
    let row                 = cell.parentNode;
    row.style.display       = 'none';
    let actionTag           = form.elements['action' + idar];
    actionTag.value         = 'delete';
    this.disabled           = true;
    
    ev.stopPropagation;
    return false;
}   // function delAddress
