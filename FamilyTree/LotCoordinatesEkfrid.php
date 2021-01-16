<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  LotCoordinatesEkfrid.php											*
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

$township	= 'Ekfrid';

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
    // lot 1 con 1
    $SElatBase      = 42.834123;
    $SElongBase     = -81.575670;
    $NElatBase      = 42.842875625;
    $NElongBase     = -81.587443875;
    $NWlatBase      = 42.83904617;
    $NWlongBase     = -81.59268517;
    $SWlatBase      = 42.8302935417;
    $SWlongBase     = -81.5809112917;
        
    $midlatBase     = ($SElatBase + $NWlatBase)/2;
    $midlongBase    = ($SElongBase + $NWlongBase)/2;
    
	$latbycon	    = $NElatBase - $SElatBase;
	$latbylot	    = $SWlatBase - $SElatBase ;
	$longbycon	    = $NElongBase - $SElongBase;
    $longbylot	    = $SWlongBase - $SElongBase ;
    $cons           	= array(
                                '7RS'	    => array(-8,1,3),
                                '6RS'	    => array(-7,1,3),
                                '5RS'	    => array(-6,1,10),
                                '4RS'	    => array(-5,1,24),
                                '3RS'	    => array(-4,1,24),
                                '2RS'	    => array(-3,1,24),
				    			'1RS'		=> array(-2,1,24),
				    			'1RN'		=> array(-1,1,24),
				    			'2RN'		=> array(0,1,24),
				    			'con 1'		=> array(1,1,24),
				    			'con 2'		=> array(2,1,24),
				    			'con 3'		=> array(3,1,24),
				    			'con 4'		=> array(4,1,24),
				    			'con 5'		=> array(5,1,24));

	foreach ($cons as $con => $info)
    {
        $c          = $info[0];
        $firstlot   = $info[1];
        $lastlot    = $info[2];
        for ($lot = $firstlot; $lot <= $lastlot; $lot++)
        {
            $name       	= "lot $lot $con, Ekfrid, Middlesex, ON, CA";
            $location   	= new Location($name);
 			$dlat			= $latbylot * ($lot - 1) + $latbycon * ($c - 1);
 			$dlong			= $longbylot * ($lot - 1) + $longbycon * ($c - 1);
 			$SElat			= $SElatBase + $dlat;
 			$SElong			= $SElongBase + $dlong;
 			$NElat			= $NElatBase + $dlat;
 			$NElong			= $NElongBase + $dlong;
 			$NWlat			= $NWlatBase + $dlat;
 			$NWlong			= $NWlongBase + $dlong;
 			$SWlat			= $SWlatBase + $dlat;
 			$SWlong	    	= $SWlongBase + $dlong;
 			$midlat	    	= $midlatBase + $dlat;
 			$midlong		= $midlongBase + $dlong;
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
	    }	        // loop through lots
    }               // loop through concession identifiers
}		// no errors in parameters
pageBot();
