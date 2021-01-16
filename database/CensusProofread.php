<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusProofread.php							                        *
 *									                                    *
 *  Proofread a page of a Canadian census.				                *
 *									                                    *
 *  History:								                            *
 *	    2011/07/02	    created						                    *
 *	    2011/10/22	    validate all parameters				            *
 *			            support pre-confederation censuses		        *
 *	    2013/01/26	    table SubDistTable renamed to SubDistricts	    *
 *	    2013/06/11	    correct URL for requesting next page to edit	*
 *	    2013/11/26	    handle database server failure gracefully	    *
 *	    2014/04/26	    remove formUtil.inc obsolete			        *
 *	    2015/05/09	    simplify and standardize <h1>			        *
 *	    2015/07/02	    access PHP includes using include_path		    *
 *	    2016/01/21	    use class Census to access census information	*
 *		    	        display debug trace				                *
 *			            include http.js before util.js			        *
 *	    2017/11/21	    use classes CensusLine and SubDistrict to	    *
 *  			        replace database queries			            *
 *	    2017/11/24	    separate ina style from other input styles	    *
 *		2020/12/01      eliminate XSS vulnerabilities                   *
 *									                                    *
 *  Copyright &copy; 2020 James A. Cobban				                *
 ************************************************************************/
    require_once __NAMESPACE__ . '/Census.inc';
    require_once __NAMESPACE__ . '/CensusLine.inc';
    require_once __NAMESPACE__ . '/SubDistrict.inc';
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function validate							                        *
 *									                                    *
 *  Input:							                                	*
 *	    $rownum		line on page				                    	*
 *	    $newrow		data entered by proofreader			                *
 ************************************************************************/
function validate($rownum, $newrow)
{
    global	$connection;	// connection to database server
    global	$Census;	    // census ID
    global	$CensusYear;	// year of the census
    global	$Province;	    // Province (pre 1867)
    global	$District;	    // District number
    global	$SubDistrict;	// Sub-District identifier
    global	$Division;	    // Division identifier
    global	$Page;		    // Page number
    global	$line;		    // line number in output

    // pre-confederation census is by colony
    if ($CensusYear < 1867)
		$parms	= array('Census'	=> $Census,
						'Province'	=> $province,
						'District'	=> $District,
						'SubDistrict'	=> $SubDistrict,
						'Division'	=> $division,
						'Page'		=> $Page,
						'Line'		=> $rownum);
	else
		$parms	= array('Census'	=> $Census,
						'District'	=> $District,
						'SubDistrict'	=> $SubDistrict,
						'Division'	=> $division,
						'Page'		=> $Page,
						'Line'		=> $rownum);

    $row		= new CensusLine($parms);
    if ($row->isExisting())
    {		// got results
		$oldrow		= $row;
		foreach($newrow as $fldname => $value)
		{		// loop through proofreader values
		    $fieldLc	= strtolower($fldname);
		    if ($oldrow->get($fieldLc) != $value)
		    {	// proofreader changed value
				++$line;
				if ($line < 10)
				    $lineText	= '0' . $line;
				else
				    $lineText	= $line;
				if ($line < 100)
				{		// max 99 rows
?>
    <tr>
      <td>
	<input type='text' size='2' name='Line<?php print $lineText; ?>'
	    readonly='readonly' class='ina label'
	    value='<?php print $rownum; ?>'>
      </td>
      <td>
	<input type='text' size='14' name='FldName<?php print $lineText; ?>'
	    readonly='readonly' class='ina label'
	    value='<?php print $fldname; ?>'>
      </td>
      <td>
	<input type='text' size='32' name='OldValue<?php print $lineText; ?>'
	    readonly='readonly' class='ina left'
	    value='<?php print $oldrow->get($fldname); ?>'>
      </td>
      <td>
	<input type='text' size='32' name='NewValue<?php print $lineText; ?>'
	    readonly='readonly' class='ina left'
	    value='<?php print $value; ?>'>
      </td>
      <td>
	<input type='text' size='40' name='Comment<?php print $lineText; ?>'
	    class='white leftnc'>
      </td>
    </tr>
<?php
		        }		// max 99 rows
		        else
		        if ($line == 100)
		        {		// report overflow
?>
    <tr>
      <th class='left' colspan='5'>
	Too many changes to page.  Remainder ignored.
      </th>
    </tr>
<?php
		        }		// report overflow
	        }	        // proofreader changed value
	    }		        // loop through proofreader values
    }		            // got results from DB
    else
    {
	    $msg	.= 'Logic Error: Query of database found no matching row. ';
    }
}		// validate

// open code
$CensusYear             = null;
$District               = null;
$SubDistrict            = null;
$Division               = null;
$Page                   = null;

if (canUser('edit'))
{
	// validate the parameters that identify the specific page to
	// be updated
	// validate census identifier
	if (array_key_exists('Census', $_POST))
	{		// census identifier supplied
	    $Census	        = $_POST['Census'];
	    try {
			$censusRec	= new Census(array('censusid'	=> $Census,
							               'collective'	=> 0));
			$CensusYear	= substr($Census, 2);
	    } catch (Exception $e) {
            $msg	.= 'Invalid Census identifier ' . 
                        htmlspecialchars($Census) . '. ';
	    }
	}		// census identifier supplied

	// Province is optional
	if (array_key_exists('Province', $_POST))
	    $Province	= $_POST['Province'];
	else
	    $Province	= '';

	// District is mandatory and must be numeric
	if (array_key_exists('District', $_POST))
	{		// District supplied
	    $District	= $_POST['District'];
	    if (!preg_match("/^[0-9]+(\.5|\.0)?$/", $District))
			$msg	.= 'District number not numeric. ';
	}		// District supplied
	else
    {		// District not supplied
        $msg	.= 'District number parameter not supplied. ';
	}		// District not supplied
	
	// SubDistrict is mandatory
	if (array_key_exists('SubDistrict', $_POST))
	{		// SubDistrict supplied
	    $SubDistrict= $_POST['SubDistrict'];
	}		// SubDistrict supplied
	else
	{		// SubDistrict not supplied
	    $msg	.= 'SubDistrict identifier parameter not supplied. ';
	}		// SubDistrict not supplied
	
	// Division is mandatory even though it is
	// not officially used in some censuses
	if (array_key_exists('Division', $_POST))
	{		// Division supplied
	    $Division	= $_POST['Division'];
	}		// Division supplied
	else
	{		// Division not supplied
	    $msg	.= 'Division number parameter not supplied. ';
	}		// Division not supplied
	
	// page number is mandatory and must be numeric
	if (array_key_exists('Page', $_POST))
	{		// Page supplied
	    $Page	= $_POST['Page'];
	    if (!preg_match("/^[0-9]+$/", $Page))
			$msg	.= 'Page number not numeric. ';
	}		// Page supplied
	else
	{		// Page not supplied
	    $msg	.= 'Page number parameter not supplied. ';
	}		// Page not supplied
	
	// image URL must be validated
	if (array_key_exists('Image', $_POST))
	{		// Image supplied
	    $Image	= $_POST['Image'];
	    if (!preg_match("/^[0-9a-zA-Z:_.\-\/]+$/", $Image))
        $msg	.= "Image URL '" .
                        htmlspecialchars($Image) . 
                        "' contains invalid characters. ";
	}		// Image supplied
	else
	{		// Image not supplied
	    $Image	= '';
	}		// Image not supplied
	
	// if no errors were encountered in validating the parameters
	// proceed to update the database
	if (strlen($msg) == 0)
	{		// no errors in validating page identifier
		    $provQuote	= $connection->quote($Province);
		    $sdQuote	= $connection->quote($SubDistrict);
		    $divQuote	= $connection->quote($Division);
	
		    $subject	= urlencode("CensusProofread.php Census=$Census Province=$Province District=$District, SubDistrict=$SubDistrict, Division=$Division, Page=$Page");
	
		    // identify the next page to update in sequence within this division
		    $nextPage	= intval($Page) + 1;
		    $subDist	= new SubDistrict(array(
							'SD_Census'	=> $Census,
							'SD_DistId'	=> $District,
							'SD_Id'		=> $SubDistrict,
							'SD_Div'	=> $Division));
	
		    $lastPage	= $subDist->get('page1') +
					  ($subDist->get('pages')- 1) * $subDist->get('bypage');
		    $sdName	= $subDist->get('name');
		    if ($nextPage > $lastPage)
				$nextPage	= 0;	// no next page
	}		// no errors in validating page identifier
}		// authorized
else
{		// not authorized
	$msg	.= 'Current user is not authorized to update the database. ';
}		// not authorized

htmlHeader("Canada: $CensusYear Census Proofread",
		    array(	'/jscripts/js20/http.js',
			    	'/jscripts/util.js',
				    'CensusProofread.js'));
?>
<body>
<?php
    pageTop(array(
		  '/genealogy.php'	=> 'Genealogy',
		  '/genCanada.html'	=> 'Canada',
		  '/genCensuses.php'	=> 'Censuses',
		  "ReqUpdate.php?Census=CA$CensusYear&Province=$Province&amp;District=$District&amp;SubDistrict=$SubDistrict&amp;Division=$Division&amp;Page=$Page" =>
						   'Specify next page',
		  "CensusUpdateStatusDetails.php?Census=$CensusYear&amp;District=$District&amp;SubDistrict=$SubDistrict&amp;Division=$Division"	=>
						   'Detailed Status of Division'));
?>
 <div class='body'>
    <h1>
      <span class='right'>
	<a href='CensusProofreadHelpen.html' target='help'>? Help</a>
      </span>
	<?php print $CensusYear; ?> Census Proofread Request
      <div style='clear: both;'></div>
    </h1>
  <p class='message'>This feature is under construction.</p> 
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
    <p class='message'>
	<?php print $msg; ?> 
    </p>
<?php
    }		// error messages
    else
    {		// no errors
?>
  <form name='proofForm' 
	action='CensusProofUpdate.php'
	method='post'
	autocomplete='off' 
	enctype='multipart/form-data'>
    <p>
      <input type='text' name='Census' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $Census; ?>'>
      <input type='text' name='Province' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $Province; ?>'>
      <input type='text' name='District' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $District; ?>'>
      <input type='text' name='SubDistrict' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $SubDistrict; ?>'>
      <input type='text' name='Division' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $Division; ?>'>
      <input type='text' name='Page' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $Page; ?>'>
      <input type='text' name='userid' size='6'
		readonly='readonly' class='ina label'
		value='<?php print $userid; ?>'>
    </p>
    <table class='form'>
      <thead>
	<tr>
	  <th>Line</th>
	  <th>Field Name</th>
	  <th>Old Value</th>
	  <th>New Value</th>
	  <th>Comment</th>
	</tr>
      </thead>
      <tbody>
<?php

	// process all of the input 
	$line		= 0;
	$oldrownum	= '';
	$newrow		= array();
	foreach($_POST as $key => $value)
	{
	    $rownum	= substr($key, strlen($key) - 2);
	    if (!ctype_digit($rownum))
		continue;
	    if ((strlen($oldrownum) > 0) && ($rownum != $oldrownum))
	    {		// have all fields for new row
		validate($oldrownum,	// validate changes to database row
				 $newrow);
		$newrow	= array();	// clear old values
	    }		//have all fields for new row
	    $oldrownum	= $rownum;

	    $fldname	= substr($key, 0, strlen($key) - 2);
	    $newrow[$fldname]	= $value;
	}		// loop through all parameters
	validate($oldrownum,	// validate changes to last database row
		 $newrow);
?>
      </tbody>
    </table>
      <p>
	<button type='submit' name='Submit'>
<u>S</u>ubmit Comments 
	</button>
      </p>
  </form>
<?php
    }		// update performed
?>
  </div>
<?php
    pageBot();
?>
</body>
</html>
