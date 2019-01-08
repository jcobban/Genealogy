<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testCountyMarriageLinks.html					*
 *									*
 *  Prompt the user to enter parameters for a search of the Ontario	*
 *  County Marriage Report database.					*
 *									*
 *  History:								*
 *	2016/01/30	created						*
 *	2017/11/19	use CitationSet in place of getCitations	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/LegacyFamily.inc';
require_once __NAMESPACE__ . '/CountyMarriage.inc';
require_once __NAMESPACE__ . '/common.inc';

htmlHeader('Ontario: Test County Marriage Links',
		array(	'/jscripts/js20/http.js',
			'/jscripts/util.js'));
?>
<body>
 <div class='body'>
  <div class='fullwidth'>
    <span class='h1'>
	Ontario: Test County Marriage Links
    </span>
    <span class='right'>
	<a href='testCountyMarriageLinksHelp.html' target='_blank'>Help?</a>
    </span>
    <div style='clear: both;'></div>
  </div>
<?php
    $getParms	= array('idsr'	=> 85,	// pre-confederation Ontario marriages
			'type'	=> 20);	// marriage	
    $citations	= new CitationSet($getParms);
    foreach ($citations as $citation)
    {
	$detail		= $citation->get('srcdetail');
	$count	= preg_match('/(vol[^0-9]*([0-9]+)|).*(No|sect|sched)[^0-9]*([0-9]+)[^0-9]+([0-9]+|)/',
			     $detail,
			     $matches);
	if ($count == 1)
	{		// detail matches pattern
	    $volume		= $matches[2];
	    if ($volume == '')
		$volume		= 16;
	    $report		= $matches[4];
	    $item		= $matches[5];
	    if (strlen($item) > 0)
	    {		// single item requested
		$idmr		= $citation->get('idime');
		$family		= new LegacyFamily(array('idmr'	=> $idmr));
		$marParms	= array('volume'	=> $volume,
					'reportno'	=> $report,
					'itemno'	=> $item);
		$debug		= true;
		$spouses	= CountyMarriage::getCountyMarriages($marParms);
		if (count($spouses) == 2)
		{	// have match in registrations
		    $spouses[0]->set('idir', $family->get('idirhusb'));
		    $spouses[0]->save();
		    $spouses[1]->set('idir', $family->get('idirwife'));
		    $spouses[1]->save();
		}	// have match in registrations
		$debug		= false;
	    }		// single item requested
	}		// detail matches pattern
    }

    showTrace();
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
