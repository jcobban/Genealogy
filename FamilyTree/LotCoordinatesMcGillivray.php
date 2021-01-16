<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  LotCoordinatesWestminster.php										*
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

$township	= 'McGillivray';

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
    // the coordinates of lot 10 con 5 ECR

    $SElatBase      	=  43.159343;
    $SElongBase     	= -81.60758;

    $latbycon	    	= 0.00899525;
	$latbylot	        = 0.000803;
	$longbycon	        = 0.00281495;
    $longbylot	        = -0.00496;
    $midlatBase         = $SElatBase + (($latbycon + $latbylot)/2);
    $midlongBase        = $SElongBase + (($longbycon + $longbylot)/2);

    $cons           	= array('con 5'		=> array( 0, -36, 30),
				    			'con 6'		=> array( 1, -32, 22),
				    			'con 7'		=> array( 2, -31, 22),
				    			'con 8'		=> array( 3, -30, 22));

	foreach ($cons as $con => $opt)
    {
        $c              = $opt[0];
        $lotfirst       = $opt[1];
        $lotlast        = $opt[2];

        for ($lot = $lotfirst; $lot <= $lotlast; $lot++)
        {
            if ($lot < 0)
            {
                $part       = 'ECR';
                $lotc       = -$lot;
            }
            else
            {
                $part       = 'WCR';
                $lotc       = $lot;
            }
            $lotr           = $lot + 10;
            $name       	= "lot $lotc $con $part, McGillivray, Middlesex, ON, CA";
            $location   	= new Location($name);
			$SElat			= $SElatBase + $lotr * $latbylot + $c * $latbycon;
			$SElong			= $SElongBase + $lotr * $longbylot + $c * $longbycon;
			$NElat			= $SElat + $latbycon;
			$NElong			= $SElong + $longbycon;
			$NWlat			= $NElat + $latbylot;
			$NWlong			= $NElong + $longbylot;
			$SWlat			= $SElat + $latbylot;
			$SWlong	    	= $SElong + $longbylot;
			$midlat	    	= ($SElat + $NWlat) / 2;
            $midlong		= ($SElong + $NWlong) / 2;

            if ($location['latitude'] == 0)
            {
                $location->set('latitude', $midlat);
                $location->set('longitude', $midlong);
                $location->set('zoom', 14);
                $location->set('boundary',
    "($SElat,$SElong),($NElat,$NElong),($NWlat,$NWlong),($SWlat,$SWlong)");
            }
            if ($location->save(false) > 0)
                print "<p>name='" . $location->getName() .
                    "', sql='" . $location->getLastSqlCmd() . "'</p>\n";

            $locset     = new RecordSet('Locations',
                                        array('location' => "$name$"));
            foreach($locset as $location)
            {
                if ($location['latitude'] == 0)
                {
                    $location->set('latitude', $midlat);
                    $location->set('longitude', $midlong);
                    $location->set('zoom', 14);
                    if ($location->save(false) > 0)
                        print "<p>name='" . $location->getName() .
                        "', sql='" . $location->getLastSqlCmd() . "'</p>\n";
                }
            }

	    }	        // loop through lots
    }               // loop through concession identifiers
}		            // no errors in parameters
pageBot();
