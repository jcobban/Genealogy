<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdate.php							*
 *									*
 *  Request a page of the census to be editted.				*
 *									*
 *  Parameters:								*
 *	Census		identifier of census, country code plus year	*
 *			for example: CA1881				*
 *	Province	identifier of province to select		*
 *									*
 *  History:								*
 *	2010/10/01	Reformat to new page layout.			*
 *	2011/01/22	Add help URL					*
 *			move onload specification to ReqUpdate1901.js	*
 *	2011/05/18	use CSS instead of tables for layout		*
 *	2013/05/07	use common PHP instead of specific html files	*
 *	2013/06/01	only include prairie provinces in 1906 and 1916	*
 *			censuses					*
 *	2013/08/17	add support for 1921 census			*
 *	2013/11/16	gracefully handle lack of database server	*
 *			connection					*
 *	2013/11/29	let common.inc set initial value of $debug	*
 *			use valid list of censuses from common.inc	*
 *	2014/09/07	use shared table of province names		*
 *	2015/05/09	simplify and standardize <h1>			*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *	2015/12/10	escape values from global table $provinceNames	*
 *	2016/01/22	use class Census to get census information	*
 *			show debug trace				*
 *			include http.js before util.js			*
 *	2016/12/28	support requesting collective census id		*
 *	2017/09/05	use Country and Domain objects to get		*
 *			information about country and province		*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Census.inc";
    require_once __NAMESPACE__ . "/Country.inc";
    require_once __NAMESPACE__ . "/Domain.inc";
    require_once __NAMESPACE__ . "/common.inc";

    $censusId		= null;
    $censusYear		= null;
    $cc			= 'CA';
    $countryName	= 'Canada';

    // get parameter values into local variables
    // validate all parameters passed to the server
    foreach ($_GET as $key => $value)
    {			// loop through all parameters
	switch($key)
	{		// switch on parameter name
	    case 'Census':
	    {		// census identifier
		$censusId		= $value;
		if (strlen($censusId) == 4)
		    $censusId	= 'CA' . $censusId;
		try {
		    $cc			= substr($censusId, 0, 2);
		    $country		= new Country(array('code' => $cc));
		    $countryName	= $country->get('name');
		    $censusRec	= new Census(array('censusid'	=> $censusId,
						   'create'	=> true));
		    $censusYear		= intval(substr($censusId, 2));
		    $provinces		= $censusRec->get('provinces');
		} catch (Exception $e) {
		    $msg .= "new Census '$censusId' failed with $e. ";
		}
		break;
	    }		// census identifier
	}		// switch on parameter name
    }			// loop through all parameters

    if (!is_null($censusYear) && strlen($msg) == 0)
    {
	$title	= $censusYear . " Census of $countryName: Update Database";
    }
    else
    {
	$title	= "Invalid or Missing Parameters";
	$msg	.= "Invalid or Missing Parameters";
    }

    htmlHeader($title,
		array(	'/jscripts/js20/http.js',
			'/jscripts/util.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'	=> 'Genealogy', 
		  "/gen$cc.html"	=> $countryName,
        	  '/genCensuses.php'	=> 'Censuses'));
?>
  <div class='body'>
    <h1>
      <span class='right'>
	<a href='ReqUpdate<?php print $censusYear; ?>Helpen.html' target='help'>? Help</a>
      </span>
	<?php print $title; ?>
      <div style='clear: both;'></div>
    </h1>
<?php
    if (!$censusRec->isExisting())
	$warn	.= "<p>Census '$censusId' not pre-defined.</p>\n";
    showTrace();

    if (strlen($msg) == 0)
    {			// no errors
	for($io = 0; $io < strlen($provinces); $io += 2)
	{		// loop through provinces
	    $province		= substr($provinces, $io, 2);
	    $domainObj	= new Domain(array('domain' => $cc . $province));
	    $provinceName	= $domainObj->get('name');
	    $provinceName	= htmlspecialchars($provinceName);
	    if ($censusRec->get('collective'))
	    {
?>
    <p class='label'><a href='CensusUpdateStatus.php?Census=<?php print $province . $censusYear; ?>&Province=<?php print $province; ?>'>
	    Edit <?php print $censusYear; ?> Census
	    for <?php print $provinceName; ?>
	</a>
<?php
	    }
	    else
	    {
?>
    <p class='label'><a href='CensusUpdateStatus.php?Census=<?php print $censusId; ?>&Province=<?php print $province; ?>'>
	    Edit <?php print $censusYear; ?> Census
	    for <?php print $provinceName; ?>
	</a>
<?php
	    }
	}		// loop through provinces
?>
    <p class='label'><a href='CensusUpdateStatus.php?Census=<?php print $censusId; ?>'>
	    Summary of Completed Transcriptions
	</a>
<?php
    }			// no errors
    else
    {			// errors
?>
    <p class='message'><?php print $msg; ?></p>
<?php
    }			// errors
?>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='HelprightTop'>
Click on this button to signon to access extended features of the web-site
or to manage your account with the web-site.
</div>
<div class='balloon' id='HelpProvince'>
Use this selection list to limit the response to a single province within 
the census.
</div>
<div class='balloon' id='HelpDistrict'>
Use this selection list to limit the response to a census district.
</div>
<div class='balloon' id='HelpSubDistrict'>
Use this selection list to limit the response to an enumeration sub-district.
</div>
<div class='balloon' id='HelpDivision'>
Use this selection list to limit the response to an enumeration division.
</div>
<div class='balloon' id='HelpPage'>
Use this selection list to limit the response to a specific page.
</div>
<div class='balloon' id='HelpshowForm'>
Click on this button to display a form for viewing or updating the field
level transcription of the selected page.
</div>
<div class='balloon' id='Helpprogress'>
Click on this button to display a summary of the progress of the project
to transcribe the <?php print $censusYear; ?> census.
</div>
<div class='popup' id='loading'>
loading
</div>
</body>
</html>
