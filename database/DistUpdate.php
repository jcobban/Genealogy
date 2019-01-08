<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DistUpdate.php							*
 *									*
 *  Update a portion of the Districts table.				*
 *									*
 *  History:								*
 *	2010/11/22	created						*
 *	2011/06/06	improve separation of HTML & PHP		*
 *			improve validation of input			*
 *	2013/11/26	handle database server failure gracefully	*
 *	2013/12/28	use CSS for layout				*
 *	2014/04/26	remove formUtil.inc obsolete			*
 *	2015/06/05	use class District instead of SQL		*
 *			update the database instead of deleting and	*
 *			inserting replacement				*
 *	2015/07/02	access PHP includes using include_path		*
 *	2016/01/20	add id to debug trace div			*
 *	2017/09/12	use get( and set(				*
 *	2017/10/29	use RecordSet					*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/District.inc';
    require_once __NAMESPACE__ . '/RecordSet.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // variables for constructing the main SQL SELECT statement

    $debug	= false;

    // process parameters
    $censusId		= null;
    $province		= '';
    $uri		= '';
    $uriand		= '?';
    $censusRec		= null;
    foreach($_POST as $key => $value)
    {				// loop through all parameters
	switch(strtolower($key))
	{			// act on specific keys
	    case 'census':
	    {	 	  	// identify the specific Census
		$censusId	= $value;
		try
		{
		    $censusRec	= new Census(array('censusid'	=>$censusId,
						   'collective'	=> 0));
		    $censusYear	= intval(substr($censusId, 2, 4));
		}
		catch (Exception $e)
		{
		    $msg	.= "Unsupported Census Identifier '$censusId'.";
		}
		$uri		.= $uriand . $key . '=' . $value;
		$uriand		= '&';
		break;
	    }	   		// identify the specific Census

	    case 'province':
	    {			// identify the optional Province
		$province	= $value;
		if (strlen($province) != 2)
		    $msg	.= "Province='$province' invalid. ";
		else
		if ($censusRec)
		{		// already processed Census
		    $ppos	= strpos($province, $censusRec->get('provinces'));
		    if ($ppos < 0 || ($ppos & 1) == 1)
			$msg	.= "Province='$province' invalid for Census '$censusId'. ";
		    if ($censusYear == 1851 || $censusYear == 1861)
			$censusId	= $province . $censusYear;
		}		// already processed Census
		else
		    $msg	.= "Province parm does not follow valid Census parm. ";
		$uri		.= $uriand . $key . '=' . $value;
		$uriand		= '&';
		break;
	    }			// identify the optional Province

	    case 'debug':
	    {			// debugging requested
		$uri		.= $uriand . $key . '=' .  $value;
		$uriand		= '&';
		break;
	    }			// debugging requested
	}			// act on specific keys
    }				// loop through all parameters

    // validate combinations of parameters
    if (is_null($censusId))
    {			// missing Census parameter
	$msg	.= 'Missing mandatory parameter Census. ';
    }			// missing Census parameter

    if (($censusYear == 1851 || $censusYear == 1861) &&
	$province == '')
	$msg	.= 'Missing mandatory parameter Province. ';

    // verify that user is authorized to edit the table
    if (!canUser('edit'))
	$msg	.= 'You are not authorized to edit this table. ';

    if (strlen($msg) == 0)
    {				// no errors
	$getParms	= array('census'	=> $censusId,
				'order'		=> 'D_Name');
	$districtList	= new RecordSet('Districts', $getParms);
	$oldrownum	= '01';
	$district	= null;
	foreach ($_POST as $name => $value)
	{			// loop through input
	    $fldname	= substr(strtolower($name), 0, strlen($name) - 2);
	    $rownum	= substr($name, strlen($name) - 2, 2);
	    switch($fldname)
	    {			// act on specific parameters
		case 'd_id':
		{		// district identifier
		    if ($district)
		    {
			$district->save(false);
			unset($district);
		    }
		    $d_id		= $value;
		    $getParms['d_id']	= $d_id;
		    if (array_key_exists($value, $districtList))
		    {
			$district	= $districtList[$d_id];
			unset($districtList[$d_id]);
		    }
		    else
			$district	= new District($getParms);
		    break;
		}		// district identifier

		case 'd_name':
		case 'd_nom':
		case 'd_province':
		{
		    $district->set($fldname, $value);
		    break;
		}		// row number changed, insert new record
	    }			// act on specific parameters
	}			// loop through input

	// handle last district
	if ($district)
	{
	    $district->save(false);
	    unset($district);
	    if ($d_id)
		unset($districtList[$d_id]);
	}

	// delete any unprocessed districts
	foreach($districtList as $d_id => $district);
	    $district->delete(false);
    }			// no errors
 
    htmlHeader('Canada: District Table Update');
?>
<body>
<?php
    pageTop(array(
	'/genealogy.php'	=> 'Genealogy', 
	'/genCanada.html'	=> 'Canada',
	'genCensuses.php'	=> 'Censuses',
	"ReqUpdateDists.html$uri"=>'Specify next census to update'));
?>	
<div class='body'>
  <h1>Census District Table Update
      <span class='right'>
	<a href='DistUpdateHelpen.html' target='help'>? Help</a>
      </span>
      <div style='clear: both;'></div>
  </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
    <?php print $msg; ?> 
  </p>
<?php
    }
    else
    {		// expand only if no errors
?>
    <p class='label'>
	<a href='DistForm.php<?php print $uri; ?>'>
	    <span class='button'>Edit Census <?php print $censusId; ?></span>
	</a>
    </p>
<?php
    }		// authorized
?>
  </div> <!-- end of <div id='body'> -->
<?php
    pageBot();
?>
<!-- The remainder of the page consists of context specific help text.
-->
</body>
</html>
