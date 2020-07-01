/************************************************************************
 *  MarriageRegStats.js					                                *
 *																		*
 *  This file implements the dynamic functionality of the web page		*
 *  MarriageRegStats.php					                            *
 *																		*
 *  History:					                                        *
 *	    2011/00/27	    created					                        *
 *	    2013/08/01	    defer facebook initialization until after load	*
 *		2019/02/10      no longer need to call pageInit                 *
 *		2020/06/22      hide right column                               *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban					            *
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  function onLoad			                                            *
 *																		*
 *  Initialize the dynamic functionality once the page is loaded		*
 ************************************************************************/
function onLoad()
{
    if (!('columns' in args))
    {                       // invoker did not explicitly specify width
        var dataTable       = document.getElementById('dataTable');
        if (dataTable)
        {                   // found table
            var tableWidth  = dataTable.offsetWidth;
            var colWidth    = tableWidth / 3;
            var optColumns  = Math.floor(window.innerWidth / colWidth);
            location.href   = location.href + "&columns=" + optColumns;
            return;
        }                   // found table
    }                       // invoker did not explicitly specify width

    // activate handling of key strokes in text input fields
    var	element;
    for (fi = 0; fi < document.forms.length; fi++)
    {		                // loop through all forms
		var form	            = document.forms[fi];
		for (var i = 0; i < form.elements.length; ++i)
		{	                // loop through all elements of form
		    element		        = form.elements[i];
            var name;
            var col             = '';
            if ('id' in element)
                name            = element.id;
            else
                name            = element.name;
            var results         = name.match(/^([a-zA-Z_$]+)(\d*)$/);
            if (results)
            {
                name            = results[1];
                col             = results[2];
            }

		    element.onkeydown	= keyDown;
    
		    if (name.toLowerCase() == 'yearstats')
		    {
			    element.helpDiv	= 'YearStats';
			    element.onclick	= showYearStats;
		    }
		}	                // loop through all elements in the form
    }		                // loop through all forms

    hideRightColumn();
}		// function onLoad

/************************************************************************
 *  function showYearStats			                                    *
 *																		*
 *  When a YearStats button is clicked this function displays the		*
 *  statistics for specific year.					                    *
 ************************************************************************/
function showYearStats()
{
    var lang        = 'en';
    if ('lang' in args)
        lang        = args.lang;
    var	rownum	    = this.id.substring(9);
    var year	    = document.getElementById('RegYear' + rownum).value;
    location	    = 'MarriageRegYearStats.php?regyear=' + year +
                                                '&lang=' + lang;
    return false;
}		// function showYearStats
