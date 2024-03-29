/************************************************************************
 *  WmbDetail.js	                            						*
 *									                                    *
 *  This file implements the dynamic functionality of the web page	    *
 *  WmbDetail.php							                            *
 *									                                    *
 *  History:								                            *
 *	    2016/03/11	    created						                    *
 *	    2016/11/02	    next and prev buttons stay within page		    *
 *		2019/02/10      no longer need to call pageInit                 *
 *		2020/01/04      pass language to next page                      *
 *									                                    *
 *  Copyright &copy; 2020 James A. Cobban.				                *
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad						                                *
 *									                                    *
 *  Initialize the dynamic functionality of the script.			        *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    for(var i = 0; i < document.forms.length; i++)
    {		                    // loop through all forms
		var form	= document.forms[i];
		if (form.name == "distForm")
		{
		    form.onsubmit 	= validateForm;
		    form.onreset 	= resetForm;
		}

		for(var j = 0; j < form.elements.length; j++)
		{	                    // loop through all elements of a form
		    var element		    = form.elements[j];

		    element.onkeydown	= keyDown;
		    element.onchange	= change;	// default handling

		    // an element whose value is passed with the update
		    // request to the server is identified by a name= attribute
		    // but elements which are used only by this script are
		    // identified by an id= attribute
		    var	name	        = element.name;
		    if (name.length == 0)
				name	        = element.id;

		    // set up dynamic functionality based on the name of the element
		    switch(name)
		    {                   // act on specific elements
				case "Surname":
				{
				    element.focus();
				    element.abbrTbl	    = SurnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}

				case "GivenName":
				case "Father":
				case "Mother":
				case "Minister":
				{
				    element.abbrTbl	    = GivnAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkName;
				    element.checkfunc();
				    break;
				}

				case "Place":
				case "BaptismPlace":
				{
				    element.abbrTbl	    = BpAbbrs;
				    element.onchange	= change;
				    element.checkfunc	= checkAddress;
				    element.checkfunc();
				    break;
				}

				case "Date":
				case "BaptismDate":
				{
				    element.abbrTbl	    = MonthAbbrs;
				    element.onchange	= dateChanged;
				    element.checkfunc	= checkDate;
				    element.checkfunc();
				    break;
				}

				case "Volume":
				case "Page":
				{
				    element.onchange	= change;
				    element.checkfunc	= checkNumber;
				    element.checkfunc();
				    break;
				}

				case "clearIdir":
				{
				    element.onclick	    = clearIdir;
				    break;
				}

				case "searchIdir":
				{
				    element.onclick	    = searchIdir;
				    break;
				}

				case "Previous":
				{
				    element.onclick	    = gotoPrev;
				    break;
				}

				case "Next":
				{
				    element.onclick	    = gotoNext;
				    break;
				}

				case "NewQuery":
				{
				    element.onclick	    = gotoQuery;
				    break;
				}


		    }	                // switch on field name
		}		                // loop through all elements in the form
    }		                    // loop through forms in the page

}		// onLoad

/************************************************************************
 *  function validateForm							                    *
 *									                                    *
 *  Ensure that the data entered by the user has been minimally		    *
 *  validated before submitting the form.				                *
 ************************************************************************/
function validateForm()
{
    return true;
}		// validateForm

/************************************************************************
 *  function resetForm						                            *
 *									                                    *
 *  This method is called when the user requests the form		        *
 *  to be reset to default values.					                    *
 *  This is required because the browser does not call the		        *
 *  onchange method for form elements that have one.			        *
 ************************************************************************/
function resetForm()
{
    //var	countySelect	= document.distForm.District;
    //changeDistrict();	// repopulate Area selection
    return true;
}	// resetForm

/************************************************************************
 *  function clearIdir						                            *
 *									                                    *
 *  Clear an existing link from this record to an individual in the	    *
 *  family tree.							                            *
 ************************************************************************/
function clearIdir()
{
    var	form		        = this.form;
    var	idirElement	        = document.getElementById('IDIR');
    var	showElement	        = document.getElementById('showLink');
    if (idirElement)
    {			// have IDIR element
		var	parentNode	    = idirElement.parentNode;
		if (showElement)
		    parentNode.removeChild(showElement);// remove old <a href=''>
		idirElement.value	= 0;
		parentNode.appendChild(document.createTextNode("Cleared"));
		this.parentNode.removeChild(this);	// remove the button
    }			// have IDIR element
    return false;
}		// function clearIdir

/************************************************************************
 *  function searchIdir						                            *
 *									                                    *
 *  Search for a matching individual in the family tree.		        *
 ************************************************************************/
function searchIdir()
{
    alert("WmbDetail.js:searchIdir: to do");
}		// function searchIdir

/************************************************************************
 *  function gotoPrev						                            *
 *									                                    *
 *  Go to the preceding registration in the table.			            *
 ************************************************************************/
function gotoPrev()
{
    var	form	    = this.form;
    var	vol	        = form.Volume.value;
    var	page	    = form.Page.value;
    var	idmb	    = form.IDMB.value;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;

    location	    = "WmbDetail.php?Volume=" + vol + "&Page=" + page +
			    	  "&idmb=<" + idmb + '&lang=' + lang;
}		// function gotoPrev

/************************************************************************
 *  function gotoNext												    *
 *																		*
 *  Go to the next registration in the table.							*
 ************************************************************************/
function gotoNext()
{
    var	form	    = this.form;
    var	vol	        = form.Volume.value;
    var	page	    = form.Page.value;
    var	idmb	    = form.IDMB.value;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    location	    = "WmbDetail.php?Volume=" + vol + "&Page=" + page +
		    		  "&idmb=>" + idmb + '&lang=' + lang;
}		// function gotoNext

/************************************************************************
 *  function gotoQuery												    *
 *																		*
 *  Go to issue a new query of the database.							*
 ************************************************************************/
function gotoQuery()
{
    var	form	    = this.form;
    var	vol	        = form.Volume.value;
    var	page	    = form.Page.value;
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    location	    = "WmbQuery.php?Volume=" + vol + "&Page=" + page + 
                        '&lang=' + lang;
}		// function gotoQuery
