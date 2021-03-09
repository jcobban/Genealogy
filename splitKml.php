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

/************************************************************************
 *  function matched                                                    *
 ************************************************************************/
function matched($name, $lname, $linkstart, $linkend)
{
    global  $response;
    global  $results;
    global  $distname;
    global  $location;
    global  $dcontext;

    $loclabel           = "LOCATION:</strong>";
    $loclabellen        = strlen($loclabel);
    $detlabel           = "Historic (Geographic) Location*: ";
    $detlabellen        = strlen($detlabel);

    $results            .= "<p>matched '$lname' in Ontario Locator to '$name' in KML file</p>\n";
    $link               = substr($response,
                                 $linkstart, 
                                 $linkend - $linkstart);
    $link               = str_replace('href="',
                    ' target="_blank" href="http://www.geneofun.on.ca', 
                            $link);
    $results            .= "<p>Ontario Locator: $link</p>\n";

    // load page pointed to by this link
    $matches            = array();
    preg_match('/href="([^"]*)"/', $link, $matches);
    $detlink            = $matches[1];
    $details            = file_get_contents($detlink, false, $dcontext);
    $locstart           = strpos($details, $detlabel);
    if ($locstart > 0)
    {
        $locstart       = $locstart + $detlabellen;
        $locend         = strpos($details, '<', $locstart);
        $locname        = substr($details,
                                 $locstart, 
                                 $locend - $locstart);
        $locname        = trim($locname);
        $locname        = str_replace(' County', '', $locname);
        $locname        = str_replace(' County of', '', $locname);
        $locname        = str_replace(' Township','',$locname);
        $locname        = str_replace(' Durham Regional Municipality',' Ontario',$locname);
        $locname        = str_replace(' Regional Municipality','',$locname);
        $lnamestart     = strtolower(substr($locname, 0, strlen($name)));
        if ($lnamestart == strtolower($name))
        {
            $comma          = strrpos($locname, ',');
            $distname       = trim(substr($locname, $comma + 1));
            $locname        .= ", ON, CA";
        }
        else
        {
            $distname       = $locname;
            $locname        = "$name, $distname, ON, CA";
        }
        $location       = new Location(array('location' => $locname));
        $location['shortname']  = $locname;
    }
    else
    {
        $locstart   = strpos($details, $loclabel);
        if ($locstart > 0)
        {
            $locstart   = strpos($details, ': ', $locstart + $loclabellen);
            $rest       = substr($details, $locstart + 2);
            $diststart  = strpos($rest, ', ') + 2;
            $rest       = substr($rest, $diststart);
            if (preg_match('/[a-zA-Z- ]+/', $rest , $lmatches))
            {
                $distname   = $lmatches[0];
                $distname   = trim(str_replace(' County', '', $distname));
                $locname    = "$name, $distname, ON, CA";
            }
            $location   = new Location(array('location' => $locname));
        }
        else
        {
            $results    .= "<p>'$loclabel' not found</p>\n";
            $results    .= "<p>" . htmlspecialchars($details) . "</p>\n";
        }
        $results        .= "<p>" . __LINE__ . " distname='$distname', locname='$locname'</p>\n";
    }
}       // function matched

/************************************************************************
 *      open code                                                       *
 ************************************************************************/
$results                = '';       // data to display
$offset                 = 0;
foreach($_GET as $key => $value)
{
    switch(strtolower($key))
    {
        case 'offset':
            if (ctype_digit($value))
                $offset = $value;
            break;

        case 'distname':
            if (preg_match('/^[a-zA-Z& ]+$/', $value))
                $distname   = $value;
            break;
    }
}

// get the template
$template		        = new FtTemplate("splitKmlen.html");

if (canUser('yes'))
{
$filename       = "/home/jamescobban/backups/Geographic_Township_Improved.kml";
$pfx            = '<SimpleData name="OFFICIAL_NAME">';
$pfxlen         = strlen($pfx);
$inx            = '<SimpleData name="LOCATION_DESCR">';
$inxlen         = strlen($inx);
$tag            = '<Placemark>';
$taglen         = strlen($tag);
$endtag         = '</Placemark>';
$endtaglen      = strlen($endtag);
$coordtag       = '<coordinates>';
$coordtaglen    = strlen($coordtag);

$file           = fopen($filename, "r");
$template->set('FILENAME', $filename);
$template->set('FILESIZE', number_format(filesize($filename),0,'.',','));
$data           = fread($file, filesize($filename));
$start          = strpos($data, $tag, $offset);
$limit          = $start + 200000;
$template->set('START', number_format($start,0,'.',','));
$template->set('LIMIT', $limit);

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
    $diststart  = strpos($entry, $inx);
    if ($diststart)
    {               // district name supplied
        $diststart  += $inxlen;
        $distend    = strpos($entry, '<', $diststart);
        $distname       = ucwords(strtolower(substr($entry, $diststart, $distend - $diststart)));
        $results    .= "<p>District='$distname'</p>\n";
    }               // district name supplied
    // note that if the LOCATION_DESCR parameter is not present the
    // value of $distname remains unchanged.  This is intentional because
    // the townships are ordered by County/District in the KML file

    // convert KML coordinates to format used by Location
    $coordstart     = strpos($entry, $coordtag) + $coordtaglen;
    if ($coordstart == 0)
        $results    .= "<p>could not find '$coordtag'</p>\n";
    $coordend   	= strpos($entry, '<', $coordstart);
    $coords     	= substr($entry, $coordstart, $coordend - $coordstart);
    $coordsnum  	= '';
    $comma      	= '';
    $oldstart   	= 0;
    $oldpair    	= 0;
    $coordstart 	= 0;
    $lontot     	= 0;
    $lattot     	= 0;
    $ccount     	= 0;
    $oldlat     	= 0;
    $oldlon     	= 0;
    $skipcount      = 0;
    while(preg_match('/\s*(-?\d+\.\d+),(-?\d+\.\d+)/',
                     substr($coords,$coordstart),
                     $matches))
    {
        $pair           = $matches[0];
        $lon            = floatval($matches[1]);
        $lat            = floatval($matches[2]);
        $distance       = abs($lon - $oldlon) + abs($lat - $oldlat);
        //$results        .= "<p>abs($lon - $oldlon) + abs($lat - $oldlat)</p>\n";
        //$results        .= "<p>distance=$distance</p>\n";
        if ($distance > 0.002)
        {           // greater than 200m from old position
	        $look       = substr($coords,$coordstart,20);
	        $coordsnum  .= $comma . '(' . number_format($lat, 6) .
	                                ',' . number_format($lon, 6) . ')';
	        $comma      = ',';
	        $ccount++;
            $oldlat     = $lat;
            $oldlon     = $lon;
	        $lontot     += $lon;
	        $lattot     += $lat;
	        $prevstart   = $oldstart;
	        $prevpair   = $oldpair;
	        $oldstart   = $coordstart;
	        $oldpair    = $pair;
	        if (strlen($coordsnum) > 32000)
	            break;
        }           // greater than 200m from old position
        else
        {
            $skipcount++;
        }
        $coordstart     += strlen($pair);
    }
    $loncen             = $lontot / $ccount;
    $latcen             = $lattot / $ccount;

    // check for an existing record for this location 
    $matches        = new RecordSet('Locations',
                        array('location' => "^$name, $distname"."[a-zA-Z& ]*, ON, CA"));
    $count          = $matches->count();
    $results        .= "<h2>Name='$name'</h2>\n";
    if ($skipcount > 0)
        $results        .= "<p>skipped $skipcount boundary points</p>\n";
    if ($count > 0)
    {               // existing instance of Location
        $results    .= "<p>matches '^$name, [a-zA-Z& ]+, ON, CA' count=$count</p>\n";
        if ($count == 1)
        {
            $location   = $matches->rewind();
            $idlr       = $location['idlr'];
            $results    .= "<p>Location: <a href=\"FamilyTree/Location.php?id=$idlr&lang=en\" target=\"_blank\">$locname</a></p>\n";
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

        $context            = stream_context_create($postopts);

        $response           = file_get_contents($url, false, $context);
        $sname              = substr($name, 0, 4);
        $linkstart          = 0;
        $nonecreated        = true;
        $simplelinkstart    = null;
        $simplelinkend      = null;
        $simplename         = null;

        $getopts = array(   'http'    => array(
					            'method'    => "GET",
					            'header'    => "Accept-language: en\r\n"
					            )
                        );
        $dcontext       = stream_context_create($getopts);

        while($linkstart = strpos($response, '<a href="/db.php', $linkstart))
        {                   // examine responses
            $lnamestart = strpos($response, '>', $linkstart+16) + 1;
            $lnameend   = strpos($response, '<', $lnamestart);
            $linkend    = $lnameend + 4;
            $lname      = trim(substr($response,
                                      $lnamestart, 
                                      $lnameend - $lnamestart));
            $lcname     = strtolower($lname);
            if ($lcname == strtolower($name))
            {               // some townships do not contain word Township
                $simplelinkstart    = $linkstart;
                $simplelinkend      = $linkend;
                $simplename         = $lname;
            }               // some townships do not contain word Township
            else if ($lcname == strtolower("$name Township"))
            {               // most townships contain word Township
                matched($name,
                        $lname, 
                        $linkstart, 
                        $linkend);
                $nonecreated        = false;
                break;
            }               // most townships contain word Township
            else
                $results    .= "<p>ignored '$lname'</p>\n";
            $linkstart      = $linkend;
        }                   // examine responses

        if ($nonecreated)
        {                   // no match on Ontario Locator
            if ($simplelinkstart)
            {
                matched($name,
                        $simplename, 
                        $simplelinkstart, 
                        $simplelinkend);
            }
            else
            {
                $results    .= 
                        "<p>no match on Ontario Locator for $name</p>\n";
                $locname    = "$name, $distname, ON, CA";
                $location   = new Location(array('location' => $locname));
                $location['shortname']  = $locname;
            }
        }                   // no match on Ontario Locator
    }               // create new instance

    if ($location instanceof Location)
    {
        $location['latitude']   = $latcen;
        $location['longitude']  = $loncen;
        $oldboundary            = $location['boundary'];
        if ($oldboundary == '')
        {
            if ($location->isExisting())
                $results        .= "<p>update boundary</p>\n";
	        $location['boundary']   = $coordsnum;
	        $location['zoom']       = 12;
	        $location['notes']      = "Township. ";
	        $locname                = $location['location'];
	        $ucount             = $location->save(false);
	        if ($ucount)
	        {
	            $last           = $location->getLastSqlCmd();
	            if (strlen($last) > 200)
                    $last       = substr($last,0,187) . '...' .
                                    substr($last,-20);
	            $results        .= "<p>$locname: $last</p>\n";
            }
            $idlr               = $location['idlr'];
            $results            .= "<p>Location: <a href=\"FamilyTree/Location.php?id=$idlr&lang=en\" target=\"_blank\">$locname</a></p>\n";
        }
    }
    if ($start > $limit)
        break;
}
fclose($file);
$template->set('DATA', $results);
$template->set('DISTNAME', $distname);
}
else
{
    $msg    .= "You are not authorized to update the database. ";
    $template->set('DATA', '');
    $template->set('DISTNAME', '');
    $template->set('FILENAME', '');
    $template->set('FILESIZE', 'None');
    $template->set('START', '0');
    $template->set('LIMIT', $limit);
    $template['results']->update(null);
}

$template->display();
