<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testBirthClass.html							*
 *									*
 *  Test driver for the class Birth and class BirthSet.			*
 *									*
 *  History:								*
 *	2017/09/30	created						*
 *									*
 *  Copyright &copy; 2017 James Alan Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Birth.inc';
require_once __NAMESPACE__ . '/BirthSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$debug		= true;
$domain		= '';
$volume		= '';
$reportNo	= '';
$itemNo		= '';
$regYear	= '';
$regNum		= '';
$regNumMax	= '';
$givenNames	= '';
$surname	= '';
$birthDate	= '';
$range		= '';
$birthPlace	= '';
$limit		= '';
$birthSet	= null;

if (count($_POST) > 0)
{			// process parameters
    $getParms	= array();
    foreach($_POST as $name => $value)
    {
	$warn	.= "<p>\$_POST['$name']='$value'</p>\n";
	if (strlen($value) > 0)
	{		// value specified
	    switch($name)
	    {
		case 'RegDomain':
		{
		    $domain		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'Volume':
		{
		    $volume		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'ReportNo':
		{
		    $reportNo		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'ItemNo':
		{
		    $itemNo		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'RegYear':
		{
		    $regYear		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'RegNum':
		{
		    $regNum		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'RegNumMax':
		{
		    $regNumMax		= $value;
		    $getParms['RegNum']	= array($regNum, ':' . $value);
		    break;
		}

		case 'Limit':
		{
		    $limit		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'GivenNames':
		{
		    $givenNames		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'Surname':
		{
		    $surname		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'BirthDate':
		{
		    $birthDate		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'Range':
		{
		    $range		= $value;
		    $getParms[$name]	= $value;
		    break;
		}

		case 'BirthPlace':
		{
		    $birthPlace		= $value;
		    $getParms[$name]	= $value;
		    break;
		}
	    }
	}		// value specified
    }

    $birthSet	= new BirthSet($getParms);
    
}			// process parameters

htmlHeader('Ontario: Test Birth Class',
	   array('../jscripts/js20/http.js',
		 '../jscripts/util.js'));
?>
<body>
 <div class='body'>
  <div class='fullwidth'>
    <span class='h1'>
	Ontario: Test Birth Class
    </span>
    <div style='clear: both;'></div>
  </div>
<?php
showTrace();

if ($birthSet && $birthSet->count() > 0)
{			// matching registrations to display
?>
  <table class='details'>
    <thead>
      <tr>
	<th class='colhead'>Domain</th>
	<th class='colhead'>Year</th>
	<th class='colhead'>Num</th>
	<th class='colhead'>Given Names</th>
	<th class='colhead'>Surname</th>
	<th class='colhead'>Birth Date</th>
	<th class='colhead'>Birth Place</th>
      </tr>
    </thead>
    <tbody>
<?php
    foreach($birthSet as $birth)
    {
?>
      <tr>
	<th><?php print $birth->get('domain'); ?></th>
	<th><?php print $birth->get('year'); ?></th>
	<th><?php print $birth->get('num'); ?></th>
	<td class='odd bold left'><?php print $birth->get('givennames'); ?></td>
	<td class='odd bold left'><?php print $birth->get('surname'); ?></td>
	<td class='odd bold left'><?php print $birth->get('birthdate'); ?></td>
	<td class='odd bold left'><?php print $birth->get('birthplace'); ?></td>
      </tr>
<?php
    }
?>
  </table>
<?php
}			// matching registrations to display
?>
<form action='testBirthClass.php' 
	method='post' name='distForm'>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Domain:</label>
      <td>
	<input name='RegDomain' type='text' value='<?php print $domain; ?>'
		class='white leftnc' size='4' maxlength='4'/>
    </div>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Volume:</label>
      <td>
	<input name='Volume' type='text' value='<?php print $volume; ?>'
		class='white rightnc' size='4' maxlength='4'/>
      </div>
      <div class='column1'>
	<label class='labelSmall'>Report Number:</label>
      <td>
	<input name='ReportNo' type='text' value='<?php print $reportNo; ?>'
		class='white rightnc' size='6' maxlength='6'/>
    </div>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Item No:</label>
      <td>
	<input name='ItemNo' type='text' value='<?php print $itemNo; ?>'
		class='white rightnc' size='4' maxlength='4'/>
      </div>
      <div class='column1'>
	<label class='labelSmall'>Count:</label>
      <td>
	<input name='Limit' type='text' value=''
		class='white rightnc' size='4' maxlength='4'/>
    </div>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Year:</label>
      <td>
	<input name='RegYear' type='text' value='<?php print $regYear; ?>'
		class='white rightnc' size='4' maxlength='4'/>
      </div>
      <div class='column1'>
	<label class='labelSmall'>Number:</label>
      <td>
	<input name='RegNum' type='text' value='<?php print $regNum; ?>'
		class='white rightnc' size='6' maxlength='9'/>
      <div class='column1'>
	<label class='labelSmall'>High Number:</label>
      <td>
	<input name='RegNumMax' type='text' value='<?php print $regNumMax; ?>'
		class='white rightnc' size='6' maxlength='9'/>
    </div>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Given Names:</label>
      <td>
	<input name='GivenNames' type='text' value='<?php print $givenNames; ?>'
		class='white left' size='16' maxlength='64'/>
      </div>
      <div class='column1'>
	<label class='labelSmall'>Surname:</label>
      <td>
	<input name='Surname' type='text' value='<?php print $surname; ?>'
		class='white left' size='16' maxlength='64'/>
      </div>
      <div class='column1'>
	<label class='labelSmall'>Soundex:</label>
      <td>
	<input name='SurnameSoundex' type='checkbox' value='Y'/>
      </div>
    </div>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Birth Date:</label>
      <td>
	<input name='BirthDate' type='text' value='<?php print $birthDate; ?>'
		class='white left' size='16' maxlength='64'/>
      </div>
      <div class='column1'>
	<label class='labelSmall'>Range:</label>
      <td>
	<input name='Range' type='text' value='<?php print $range; ?>'
		class='white rightnc' size='4' maxlength='4'/>
    </div>
    <div class='row'>
      <div class='column1'>
	<label class='labelSmall'>Birth Place:</label>
      <td>
	<input name='BirthPlace' type='text' value='<?php print $birthPlace; ?>'
		class='white left' size='16' maxlength='64'/>
      </div>
    </div>
  <p>
	<button type='submit' id='Delete'>Submit</button>
  </p>
</form>
</div>
<div class='balloon' id='HelpVolume'>
The volume identifier of the original archived records
</div>
<div class='balloon' id='HelpReportNo'>
The report number within the volume.
</div>
<div class='balloon' id='HelpItemNo'>
The item number within the report.
</div>
<div class='balloon' id='HelpCount'>
The maximum number of items to return.
</div>
<div class='balloon' id='HelpDelete'>
Clicking on this button performs the delete.
</div>
<div class='balloon' id='HelprightTop'>
Click on this button to signon to access extended features of the web-site
or to manage your account with the web-site.
</div>
<div class='popup' id='loading'>
Loading...
</div>
</body>
</html>
