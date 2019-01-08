/************************************************************************
 *  TestEtf.js								*
 *									*
 *  Implement the dynamic functionality of the Cobban.html page		*
 *									*
 *  History:								*
 *	2015/09/04	created						*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad								*
 *									*
 *  Perform initialization after page is loaded				*
 *									*
 ************************************************************************/
function onLoad()
{
    document.setupForm.service.onchange	= changeService;
    document.setupForm.action.onchange	= changeAction;
}		// onLoad

function changeService()
{
    document.testForm.action	= this.value;
}

function changeAction()
{
    var testForm		= document.testForm;
    testForm.api_service.value	= this.value;

    if (this.value == '19')
    {
	testForm.source_account.disabled	= true;
	testForm.amount.disabled		= true;
	testForm.currency.disabled		= true;
	testForm.destination_account.disabled	= true;
	testForm.merchant_external_id.disabled	= true;
    }
}
