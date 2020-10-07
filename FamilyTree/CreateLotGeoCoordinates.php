<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  SetLotGeoCoordinates.php						*
 *									*
 *  This script updates locations within a township that		*
 *  contain a lot and concession number.				*
 * 									*
 *  Parameters (passed by method=get) 					*
 *	Township	name of township				*
 *									*
 *  History:								*
 *	2013/04/25	created						*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2015/02/21	use Location::getLocations			*
 *			use Location::set and ::save			*
 *			correct missing </body> and </html>		*
 *	2015/07/02	access PHP includes using include_path		*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Location.inc';

    $township	= 'Caradoc';

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

	    default:
	    {			// all others
		$msg	.= "Unexpected parameter $parm='$value'. ";
	    }			// all others
	}	// act on specific parameters
    }

    htmlHeader('Update Lot Geocoordinates', array());
?>
<body>
<h1>Create Lot Geocoordinates for <?php print $township; ?></h1>
<?php
    if (strlen($msg) > 0)
    {
?>
<p class='message'><?php print $msg; ?></p>
<?php
    }
    else
    {		// no errors in parameters

	print "<p>#___________________________ " .
		"Domain County Township Con Latitude Longitude LatByLot LongByLot";
	$township	= 'Caradoc';
	
	$latbase	= 42.8201637268;
	$latbycon	= 0.0088475928;
	$latbylot	= 0.0038421397;
	$longbase	= -81.5566291809;
	$longbycon	= -0.0117906843;
	$longbylot	= 0.005235672;
	$cons		= array(	'6rs'	=> -6,
					'5rs'	=> -5,
					'4rs'	=> -4,
					'3rs'	=> -3,
					'2rs'	=> -2,
					'1rs'	=> -1,
					'1rn'	=> 0,
					'con 1'	=> 1,
					'con 2'	=> 2,
					'con 3'	=> 3,
					'con 4'	=> 4,
					'con 5'	=> 5,
					'con 6'	=> 6,
					'con 7'	=> 7,
					'con 8'	=> 8,
					'con 9'	=> 9,
					'con 10'=> 10);

	foreach ($cons as $con => $c)
	{
	    $lat			= $latbase +
					  ($c * $latbycon) +
					  (1 - 0.5)* $latbylot;

	    $long 			= $longbase +
					  ($c * $longbycon) +
					  (1 - 0.5) * $longbylot;

	    print "<p>INSERT INTO GeoCodes " .
		"VALUES('CAON','Msx','$township', '$con', " .
		 number_format($lat,6) . ", " .
		 number_format($long,6) . ", " .
		 number_format($latbylot,6) . ", " .
		 number_format($longbylot,6) . ");\n";
	}	// loop through concession identifiers

	$township	= 'Ekfrid';
	$latbase	= 42.8201637268;
	$latbycon	= 0.0088475928;
	$latbylot	= -0.0038421397;
	$longbase	= -81.5566291809;
	$longbycon	= -0.0117906843;
	$longbylot	= -0.005235672;
	$cons		= array(	'7rs'	=> -7,
					'6rs'	=> -5,
					'5rs'	=> -5,
					'4rs'	=> -4,
					'3rs'	=> -3,
					'2rs'	=> -2,
					'1rs'	=> -1,
					'1rn'	=> 0,
					'2rn'	=> 1,
					'con 1'	=> 2,
					'con 2'	=> 3,
					'con 3'	=> 4,
					'con 4'	=> 5,
					'con 5' => 6);


	foreach ($cons as $con => $c)
	{
	    $lat			= $latbase +
					  ($c * $latbycon) +
					  (1 - 0.5)* $latbylot;
		//if ($lat >= 0)
		//{
		//    $deglat		= floor($lat);
		//    $fraclat		= $lat - $deglat;
		//    $minlat		= floor($fraclat * 60.0);
		//    $lat		= $fraclat * 3600.0 +
		//			  40.0 * $minlat +
		//			  10000.0 * $deglat;
		//}
		//else
		//{
		//    $lat		= abs($lat);
		//    $deglat		= floor($lat);
		//    $fraclat		= $lat - $deglat;
		//    $minlat		= floor($fraclat * 60.0);
		//    $lat		= $fraclat * 3600.0 +
		//			  40.0 * $minlat +
		//			  10000.0 * $deglat;
		//    $lat		= -$lat;
		//}

	    $long 			= $longbase +
					  ($c * $longbycon) +
					  (1 - 0.5) * $longbylot;
		//if ($long >= 0)
		//{
		//    $deglong		= floor($long);
		//    $fraclong		= $long - $deglong;
		//    $minlong		= floor($fraclong * 60.0);
		//    $long		= $fraclong * 3600.0 +
		//			  40.0 * $minlong +
		//			  10000.0 * $deglong;
		//}
		//else
		//{
		//    $long		= abs($long);
		//    $deglong		= floor($long);
		//    $fraclong		= $long - $deglong;
		//    $minlong		= floor($fraclong * 60.0);
		//    $long		= $fraclong * 3600.0 +
		//		      40.0 * $minlong +
		//		      10000.0 * $deglong;
		//    $long		= -$long;
		//}

	    print "<p>INSERT INTO GeoCodes " .
		"VALUES('CAON','Msx','$township', '$con', " .
		 number_format($lat,6) . ", " .
		 number_format($long,6) . ", " .
		 number_format($latbylot,6) . ", " .
		 number_format($longbylot,6) . ");\n";
	}	// loop through concession identifiers
    }		// no errors in parameters
?>
  </body>
</html>
