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

$township	= 'Westminster';

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
    // the coordinates of lot 1 con 2
    $SElatBase      	=  42.936043;
    $SElongBase     	= -81.101132;
    $NElatBase      	=  42.954208;
    $NElongBase     	= -81.105660;
    $NWlatBase      	=  42.953738;
    $NWlongBase     	= -81.110658;
    $SWlatBase      	=  42.935596;
    $SWlongBase     	= -81.106152;
    $midlatBase     	= ($SElatBase + $NWlatBase)/2;
    $midlongBase    	= ($SElongBase + $NWlongBase)/2;

	$latbycon	    	= $SElatBase - $NElatBase;
	$latbylot	        = $SWlatBase - $SElatBase;
	$longbycon	        = $SElongBase - $NElongBase;
    $longbylot	        = $SWlongBase - $SElongBase;

    // the coordinates of lot 1 con 3
    $SEcon3latBase		=  42.92415;
	$SEcon3longBase		= -81.098208;
	$NEcon3latBase		=  42.936043;
	$NEcon3longBase		= -81.101132;
	$NWcon3latBase		=  42.935385;
	$NWcon3longBase		= -81.108619;
	$SWcon3latBase		=  42.92352;
	$SWcon3longBase		= -81.105267;
    $midcon3latBase     = ($SEcon3latBase + $NWcon3latBase)/2;
    $midcon3longBase    = ($SEcon3longBase + $NWcon3longBase)/2;
    
	$con3latbycon	    = $SEcon3latBase - $NEcon3latBase;
	$con3latbylot	    = $SWcon3latBase - $SEcon3latBase;
	$con3longbycon	    = $SEcon3longBase - $NEcon3longBase;
    $con3longbylot	    = $SWcon3longBase - $SEcon3longBase;

    // the coordinates of lot 50 WTR
    $SEwtrlatBase		=  42.834773;
	$SEwtrlongBase		= -81.267455;
	$NEwtrlatBase		=  42.838322;
	$NEwtrlongBase		= -81.269025;
	$NWwtrlatBase		=  42.832484;
	$NWwtrlongBase		= -81.29253;
	$SWwtrlatBase		=  42.828928;
	$SWwtrlongBase		= -81.291069;
    $midwtrlatBase      = ($SEwtrlatBase + $NWwtrlatBase)/2;
    $midwtrlongBase     = ($SEwtrlongBase + $NWwtrlongBase)/2;
    
	$wtrlatbycon	    = $SEwtrlatBase - $SWwtrlatBase;
	$wtrlatbylot	    = $NEwtrlatBase - $SEwtrlatBase;
    $wtrlongbycon	    = $SEwtrlongBase - $SWwtrlongBase;
	$wtrlongbylot	    = $NEwtrlongBase - $SEwtrlongBase;

    $cons           	= array('BF'	    => array(-1, 1, 50),
				    			'con 1'		=> array( 0, 1, 49),
				    			'con 2'		=> array( 1, 1, 38),
				    			'con 3'		=> array( 2, 1, 24),
				    			'con 4'		=> array( 3, 1, 24),
				    			'con 5'		=> array( 4, 1, 23),
				    			'con 6'		=> array( 5, 1, 23),
				    			'con 7'		=> array( 6, 1, 23),
				    			'con 8'		=> array( 7, 1, 22),
				    			'con 9'		=> array( 8, 1, 22),
				    			'WTR'		=> array( 9,49, 79),
				    			'ETR'		=> array(10,47, 78));

	foreach ($cons as $con => $opt)
    {
        $c              = $opt[0];
        $lotfirst       = $opt[1];
        $lotlast        = $opt[2];

        for ($lot = $lotfirst; $lot <= $lotlast; $lot++)
        {
            $name       	= "lot $lot $con, Westminster, Middlesex, ON, CA";
            $location   	= new Location($name);
            if ($c < 2)
            {
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
            }
            else
            if ($c < 9)
            {
    			$dlat		= $con3latbylot * ($lot - 1) + $con3latbycon * ($c - 2);
    			$dlong		= $con3longbylot * ($lot - 1) + $con3longbycon * ($c - 2);
    			$SElat		= $SEcon3latBase + $dlat;
    			$SElong		= $SEcon3longBase + $dlong;
    			$NElat		= $NEcon3latBase + $dlat;
    			$NElong		= $NEcon3longBase + $dlong;
    			$NWlat		= $NWcon3latBase + $dlat;
    			$NWlong		= $NWcon3longBase + $dlong;
    			$SWlat		= $SWcon3latBase + $dlat;
    			$SWlong	    = $SWcon3longBase + $dlong;
    			$midlat	    = $midcon3latBase + $dlat;
                $midlong	= $midcon3longBase + $dlong;
            }
            else
            {
    			$dlat		= $wtrlatbylot * ($lot - 50) + $wtrlatbycon * ($c - 9);
    			$dlong		= $wtrlongbylot * ($lot - 50) + $wtrlongbycon * ($c - 9);
    			$SElat		= $SEwtrlatBase + $dlat;
    			$SElong		= $SEwtrlongBase + $dlong;
    			$NElat		= $NEwtrlatBase + $dlat;
    			$NElong		= $NEwtrlongBase + $dlong;
    			$NWlat		= $NWwtrlatBase + $dlat;
    			$NWlong		= $NWwtrlongBase + $dlong;
    			$SWlat		= $SWwtrlatBase + $dlat;
    			$SWlong	    = $SWwtrlongBase + $dlong;
    			$midlat	    = $midwtrlatBase + $dlat;
                $midlong	= $midwtrlongBase + $dlong;
            }

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
