<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testCountyMarriageReport.php					*
 *									*
 *  Prompt the user to enter parameters for a search of the Ontario	*
 *  County Marriage Report database.					*
 *									*
 *  History:								*
 *	2017/09/24	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/CountyMarriageReport.inc';
require_once __NAMESPACE__ . '/common.inc';

$regDomain		= null;
$volume			= null;
$reportNo		= null;
$report			= null;

foreach($_POST as $fieldname => $value)
{			// loop through parameters
    switch($fieldname)
    {			// act on specific input fields
	case 'RegDomain':
	{
	    $regDomain	= $value;
	    break;
	}

	case 'Volume':
	{
	    $volume	= $value;
	    break;
	}

	case 'ReportNo':
	{
	    $reportNo	= $value;
	    break;
	}

    }			// act on specific input fields
}			// loop through parameters

if ($regDomain)
{
    $parms	= array('m_regdomain'	=> $regDomain,
			'm_volume'	=> $volume,
			'm_reportno'	=> $reportNo);
    try {
	$report	= new CountyMarriageReport($parms);
    } catch(Exception $e) {
	$msg	.= $e->getMessage();
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Ontario: Test County Marriage Report</title>
    <meta http-equiv='content-type' CONTENT='text/html; charset=UTF-8'>
    <meta http-equiv='default-style' CONTENT='text/css'>
    <meta name='author' content='James A. Cobban'>
    <meta name='copyright' content='&copy; 2017 James A. Cobban'>
    <script src='/jscripts/js20/http.js' language='JavaScript'>
    <script src='/jscripts/util.js' language='JavaScript'>
    </script>
    <link rel='stylesheet' type='text/css' href='/styles.css'/>
</head>
<body>
 <div class='body'>
  <div class='fullwidth'>
    <span class='h1'>
	Ontario: Test County Marriage Report
    </span>
    <span class='right'>
	<a href='testCountyMarriageReportHelp.html' target='_blank'>Help?</a>
    </span>
    <div style='clear: both;'></div>
  </div>
<?php
if (strlen($msg) > 0)
{
?>
    <p class='message'>
	<?php print $msg; ?> 
    </p>
<?php
}
else
{			// parameters OK
    showTrace();

    if ($report)
    {
	$debug		= true;
	$report->dump('Constructed Object');
	showTrace();
    }
?>
<form action='testCountyMarriageReport.php' 
	method='post' name='distForm' id='distForm'> <!-- invoke self -->
  <table id='formTable' class='form'>
    <tr>
      <th class='labelSmall'>Domain:</th>
      <td>
	<input name='RegDomain' id='RegDomain' type='text' value='CAON'
		class='white leftnc' size='4' maxlength='4'/></td>
    </tr>
    <tr>
      <th class='labelSmall'>Volume:</th>
      <td>
	<input name='Volume' id='Volume' type='text'
		class='white rightnc' size='4' maxlength='4'/></td>
      <th class='labelSmall'>Number:</th>
      <td>
	<input name='ReportNo' id='ReportNo' type='text'
		class='white rightnc' size='6' maxlength='6'/></td>
      <th class='labelSmall'>Count:</th>
    </tr>
  </table>
  <p>
	<button type='submit' id='Submit'>Submit</button>
  </p>
</form>
<?php
}			// parameters OK
?>
</div>
<div class='balloon' id='HelpVolume'>
The year the marriage was registered.
</div>
<div class='balloon' id='HelpReportNo'>
The registration number within the year.
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
