<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testEvent.php							*
 *									*
 *  Test driver for the Event class					*
 * 									*
 *  History: 								*
 *	2014/11/30	add debug option				*
 *			enclose comment blocks				*
 *	2014/12/22	remove obsolete formUtil.inc			*
 *	2015/02/18	correct include names				*
 *			pass list of IDERs as array to 			*
 *			Event::getEvents				*
 *	2017/09/28	change class LegacyEvent to class Event		*
 *	2017/10/31	use RecordSet instead of Event::getEvents	*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
    require_once __NAMESPACE__ . '/RecordSet.inc';
    require_once __NAMESPACE__ . '/Event.inc';
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Event::getEvents',
		array("/jscripts/js20/http.js",
			"/jscripts/CommonForm.js",
			"/jscripts/util.js"));
?>
<body>
<?php
    pageTop(array("/genealogy.php"		=> 'Genealogy',
		  "/genCountry.php?cc=CA"		=> 'Canada',
		  '/Canada/genProvince.php?domain=CAON'	=> 'Ontario',
		  '/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
    <h1>Test Event::getEvents </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
	<?php print $msg; ?> 
  </p>
<?php
    }	// error message to display
    else
    {
?>
    <form name='indForm' action='testEvent.php' method='post'>
<?php
	if ($debug)
	{
?>
      <input name='Debug' type='hidden' value='Y'>
<?php
	}	// debugging

	if (array_key_exists('IDER', $_POST))
	{
	    print "<p>IDER=" . print_r($_POST['IDER'], true);
	    $eparms	= array('IDER' => $_POST['IDER']);
	    $elist	= new RecordSet('Events',$eparms);
	    $information= $elist->getInformation();
print "<p>query='" . $information['query'] . "'</p>\n";
	    foreach($elist as $ider => $event)
	    {
?>
      <p>IDER=<?php print $ider; ?> is <?php print $event->getName(); ?>
      </p>
<?php
	    }
	    $nextIder	= $ider + 1;
	}
	else
	{
	    $nextIder	= 1;
	    if (array_key_exists('NextIDER', $_POST))
		$nextIder	= intval($_POST['NextIDER']);
	    for($i = $nextIder; $i < $nextIder + 10; ++$i)
	    {		// display sequence of events
		try {
		    $event	= new Event($i);
?>
      <p>
	<input name='IDER[]' type='text' value='<?php print $i; ?>'
		class='ina rightnc'>
	<?php print $event->getName(); ?>
      </p>
<?php
		} catch(Exception $e) {
?>
      <p class='message'>
	No record for IDER=<?php print $i; ?>
      </p>
<?php
		}	// catch
	    }		// display sequence of events
	    $nextIder	= $i;
	}		// array of IDERs not passed
    }			// no errors
?>
      <p>
	<input type='text' name='NextIDER' class='white rightnc'
		value='<?php print $nextIder; ?>'>
      </p>
      <p>
	<button type='submit' id='Submit'>Submit</button>
      </p>
    </form>
  </div>
<?php
    pageBot();
?>
</body>
</html>
