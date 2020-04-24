<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  SetLotGeoCoordinates.php											*
 *																		*
 *  This script updates locations within a township that				*
 *  contain a lot and concession number.								*
 * 																		*
 *  Parameters (passed by method=get) 									*
 *		Township		name of township								*
 *																		*
 *  History:															*
 *		2013/04/25		created											*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2015/02/21		use Location::getLocations						*
 *						use Location::set and ::save					*
 *						correct missing </body> and </html>				*
 *		2015/07/02		access PHP includes using include_path			*
 *																		*
 *  Copyright &copy; 2015 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/common.inc';

$township	= 'London';

if (!canUser('edit'))
	$msg	.= 'You are not authorized to update the database. ';

// get the record identifier
foreach($_GET as $parm => $value)
{
	switch(strtolower($parm))
	{	// act on specific parameters
	    case 'township':
	    {
			$township	= $value;
			break;
	    }			// Township

	}	// act on specific parameters
}

htmlHeader('Update Lot Geocoordinates', array());
?>
<body>
<h1>Create Lot Geographic Coordinates for <?php print $township; ?></h1>
<?php
if (strlen($msg) > 0)
{
?>
<p class='message'><?php print $msg; ?></p>
<?php
}
else
{		// no errors in parameters
    $SElatBase      = 43.01227;
    $SElongBase     = -81.147793;
    $NElatBase      = 43.024216;
    $NElongBase     = -81.153747;
    $NWlatBase      = 43.02237;
    $NWlongBase     = -81.160336;
    $SWlatBase      = 43.010619;
    $SWlongBase     = -81.154457;
    $midlatBase     = ($SElatBase + $NWlatBase)/2;
    $midlongBase    = ($SElongBase + $NWlongBase)/2;
    
	$latbycon	    = 0.0117;
	$latbylot	    = -0.00202;
	$longbycon	    = -0.00598;
    $longbylot	    = -0.006936;
    $cons           	= array('con A'	    => -2,
				    			'con B'		=> -1,
				    			'con C'		=> 0,
				    			'con 1'		=> 1,
				    			'con 2'		=> 2,
				    			'con 3'		=> 3,
				    			'con 4'		=> 4,
				    			'con 5'		=> 5,
				    			'con 6'		=> 6,
				    			'con 7'		=> 7,
				    			'con 8'		=> 8,
				    			'con 9'		=> 9,
				    			'con 10'	=> 10,
				    			'con 11'	=> 11,
				    			'con 12'	=> 12,
				    			'con 13'	=> 13,
				    			'con 14'	=> 14,
				    			'con 15'	=> 15,
				    			'con 16'	=> 16);

	foreach ($cons as $con => $c)
    {
        for ($lot = 1; $lot <= 32; $lot++)
        {
            $name       	= "lot $lot $con, London, Middlesex, ON, CA";
            $location   	= new Location($name);
            showTrace();
            if ($location->isExisting())
            {       // already exists
    			$dlat		= $latbylot * ($lot - 1) + $latbycon * ($c - 1);
    			$dlong		= $longbylot * ($lot - 1) + $longbycon * ($c - 1);
    			$SElat		= $SElatBase + $dlat;
    			$SElong		= $SElongBase + $dlong;
    			$NElat		= $NElatBase + $dlat;
    			$NElong		= $NElongBase + $dlong;
    			$NWlat		= $NWlatBase + $dlat;
    			$NWlong		= $NWlongBase + $dlong;
    			$SWlat		= $SWlatBase + $dlat;
    			$SWlong	    = $SWlongBase + $dlong;
    			$midlat	    = $midlatBase + $dlat;
    			$midlong	= $midlongBase + $dlong;
                $location->set('latitude', $midlat);
                $location->set('longitude', $midlong);
                $location->set('zoom', 14);
                $location->set('boundary',
                    "($SElat,$SElong),($NElat,$NElong),($NWlat,$NWlong),($SWlat,$SWlong)");
                $location->save(false);
                print "<p>name='" . $location->getName() .
                    "', sql='" . $location->getLastSqlCmd() . "'</p>\n";

                $locset     = new RecordSet('Locations',
                                            array('location' => "^.+$name"));
                foreach($locset as $location)
                {
                    if ($location['latitude'] == 0)
                    {
                        $location->set('latitude', $midlat);
                        $location->set('longitude', $midlong);
                        $location->set('zoom', 14);
                        $location->save(false);
                        print "<p>name='" . $location->getName() .
                            "', sql='" . $location->getLastSqlCmd() . "'</p>\n";
                    }
                }
            }       // already exists
            else
            {       // not defined
                print "<p>'$name' is not defined</p>\n";
            }       // not defined

	    }	        // loop through lots
    }               // loop through concession identifiers
}		// no errors in parameters
pageBot();
