<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  splitKml.php													    *
 *																		*
 *  This script processes the Ontario Townships Boundaries KML file.    *
 *																		*
 *  History:															*
 *		2021/01/14      Created											*
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/Location.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/common.inc";

$offset         = 0;
foreach($_GET as $key => $value)
{
    switch(strtolower($key))
    {
        case 'offset':
            if (ctype_digit($value))
                $offset = $value;
            break;
    }
}

// get the template
$template		        = new FtTemplate("splitKmlen.html");

$filename       = "/home/jcobban/Downloads/Townships/Geographic_Township_Improved.kml";
$pfx            = '"OFFICIAL_NAME">';
$pfxlen         = strlen($pfx);
$tag            = '<Placemark>';
$taglen         = strlen($tag);
$endtag         = '</Placemark>';
$endtaglen      = strlen($endtag);
$coordtag       = '<coordinates>';
$coordtaglen    = strlen($coordtag);
$detlabel       = "Historic (Geographic) Location*: ";
$detlabellen    = strlen($detlabel);
$loclabel       = "LOCATION:</strong>";
$loclabellen    = strlen($loclabel);

$file           = fopen($filename, "r");
$template->set('FILENAME', $filename);
$template->set('FILESIZE', number_format(filesize($filename),0,'.',','));
$data           = fread($file, filesize($filename));
$start          = strpos($data, $tag, $offset);
$limit          = $start + 200000;
$template->set('START', number_format($start,0,'.',','));
$template->set('LIMIT', $limit);

$results        = '';       // data to display
while($start)
{
    $end        = strpos($data, $tag, $start + $taglen);
    if ($end == 0)
    {               // last instance
        $end    = strpos($data, $endtag, $start) + $endtaglen;
        $entry  = substr($data, $start, $end - $start);
        $start  = null;
    }               // last instance
    else
    {
        $entry  = substr($data, $start, $end - $start);
        $start  = $end;
    }
    $namestart  = strpos($entry, $pfx) + $pfxlen;
    $nameend    = strpos($entry, '<', $namestart);
    $name       = ucwords(strtolower(substr($entry, $namestart, $nameend - $namestart)));

    // convert KML coordinates to format used by Location
    $coordstart = strpos($entry, $coordtag) + $coordtaglen;
    if ($coordstart == 0)
        $results    .= "<p>could not find '$coordtag'</p>\n";
    $coordend   = strpos($entry, '<', $coordstart);
    $coords     = substr($entry, $coordstart, $coordend - $coordstart);
    $coordsnum  = '';
    $comma      = '';
    $coordstart = 0;
    $lontot     = 0;
    $lattot     = 0;
    $ccount     = 0;
    while(preg_match('/\s*(-?\d+\.\d+),(-?\d+\.\d+)/',
                     substr($coords,$coordstart),
                     $matches))
    {
        $pair       = $matches[0];
        $lon        = floatval($matches[1]);
        $lat        = floatval($matches[2]);
        $look       = substr($coords,$coordstart,20);
        $coordsnum  .= $comma . '(' . number_format($lat, 6) .
                                ',' . number_format($lon, 6) . ')';
        $comma      = ',';
        $ccount++;
        $lontot     += $lon;
        $lattot     += $lat;
        $prevstart   = $oldstart;
        $prevpair   = $oldpair;
        $oldstart   = $coordstart;
        $oldpair    = $pair;
        $coordstart += strlen($pair);
        if (strlen($coordsnum) > 64000)
            break;
    }
    $loncen         = $lontot / $ccount;
    $latcen         = $lattot / $ccount;

    // check for an existing record for this location 
    $matches    = new RecordSet('Locations',
                        array('location' => "^$name, [a-zA-Z& ]+, ON, CA"));
    $count      = $matches->count();
    $results    .= "<p>name='$name'</p>\n";
    if ($count > 0)
    {               // existing instance of Location
        $results    .= "<p>matches '^$name, [a-zA-Z& ]+, ON, CA' count=$count</p>\n";
        if ($count == 1)
        {
            $location   = $matches->rewind();
            $results    .= "<p>Location: " . $location['location'] . "</p>\n";
        }
    }               // existing instance of Location
    else
    {               // create new instance
        // search Ontario Locator web site
        $url            = "http://www.geneofun.on.ca/db.php";
        $parms          = array("account"   => "spettit",
								"database"  => "onlocator",
								"template" => "ontariolocator-results.html",
								"sort"      => "PLACE",
								"search"    => "PLACE",
								"max"       => "150",
								"find"      => $name);
        $postopts = array(
	        'http' => array (
	            'method'    => "POST",
	            'header'    =>
	              "Accept-language: en\r\n".
	              "Content-type: application/x-www-form-urlencoded\r\n",
	            'content'   => http_build_query($parms)
	            )
	        );

        $context        = stream_context_create($postopts);

        $response       = file_get_contents($url, false, $context);
        $sname          = substr($name, 0, 4);
        if (preg_match("/<a href=\"\/db.php[^>]*>$sname/",
            $response, $nmatches))
        {
            $linkstart  = strpos($response, $nmatches[0]);
        }
        else
        {
            $linkstart  = strpos($response, '<a href="/db.php');
        }
        $linkend        = strpos($response, '</a>', $linkstart + 10) + 4;
        $link           = substr($response, $linkstart, $linkend - $linkstart);
        $link           = str_replace('href="', ' target="_blank" href="http://www.geneofun.on.ca', $link);
        if (preg_match('/>([^<]+)</', $link, $lmatches))
            $name       = trim($lmatches[1]);
        $results        .= $link;
        $matches        = array();

        // load page pointed to by this link
        preg_match('/href="([^"]*)"/', $link, $matches);
        $detlink        = $matches[1];
        $getopts = array(
					  'http'    => array(
					    'method'    => "GET",
					    'header'    => "Accept-language: en\r\n"
					                )
                      );

        $dcontext       = stream_context_create($getopts);
        $details        = file_get_contents($detlink, false, $dcontext);
        $locstart       = strpos($details, $detlabel);
        if ($locstart > 0)
        {
            $locstart   = $locstart + $detlabellen;
            $locend     = strpos($details, '<', $locstart);
            $locname    = substr($details, $locstart, $locend-$locstart);
            $locname    = str_replace(' Township','',$locname) . ", ON, CA";
            $location   = new Location(array('location' => $locname));
            $location['shortname']  = $locname;
        }
        else
        {
            $locstart   = strpos($details, $loclabel);
            if ($locstart > 0)
            {
                $locstart   = $locstart + $loclabellen;
                $rest       = substr($details, $locstart);
                if (preg_match('/[a-zA-Z- ]+/', $rest , $lmatches))
                    $locname    = "$name, {$lmatches[0]}, ON, CA";
                $location   = new Location(array('location' => $locname));
            }
            else
            {
                $results    .= "<p>'$loclabel' not found</p>\n";
                $results    .= "<p>" . htmlspecialchars($details) . "</p>\n";
            }
        }
    }               // create new instance

    if ($location instanceof Location)
    {
        $location['latitude']   = $latcen;
        $location['longitude']  = $loncen;
        $oldboundary            = $location['boundary'];
        if ($oldboundary == '')
        {
	        $location['boundary']   = $coordsnum;
	        $ucount         = $location->save(false);
	        if ($ucount)
	        {
	            $name       = $location['location'];
	            $last       = $location->getLastSqlCmd();
	            if (strlen($last) > 200)
                    $last   = substr($last,0,187) . '...' .
                                substr($last,-20);
	            $results    .= "<p>$location: $last</p>\n";
	        }
        }
    }
    if ($start > $limit)
        break;
}
fclose($file);
$template->set('DATA', $results);

$template->display();
