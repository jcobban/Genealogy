<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusGetSubDistsJSON.php                                           *
 *                                                                      *
 *  Generate a JSON document with information about a set of census     *
 *  enumeration sub-districts.                                          *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      Census          census identifier including domain              *
 *      District        district number                                 *
 *                      if <select name='District[]' multiple='multiple'>*
 *                      this is an array.                               *
 *      Sched           schedule number, default '1'                    *
 *                                                                      *
 *  History:                                                            *
 *      2023/08/25      derived from CensusGetSubDists.php              *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
header("Content-Type: application/json");
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$distpattern                    = '/^[0-9]+(\.5)?$/';

// process input parameters
$census                         = 'CA1881';
$distsel                        = '';
$distList                       = '';
$or                             = '';       
$comma                          = '';
$parmList                       = '';
$amp                            = '';
$sched                          = '1';
$getParms                       = array();

print "{\n";
if (isset($_GET) && count($_GET) > 0)
{                           // invoked by URL 
    $parmsText                  = "\"get\" : {";
    $comma                      = '';

    foreach ($_GET as $key => $value)
    {                       // loop through district numbers
        $parmsText              .= "$comma\n    \"$key\": ";
        // format the parameter list for reply
        if (is_array($value))
        {
            $parmsText          .= '{';
            $acomma             = '';
            foreach($value as $avalue)
            {
                $parmsText      .= "$acomma $avalue";
                $acomma         = ',';
            }
            $parmsText          .= '}';
        }
        else
            $parmsText          .=  "\"$value\"";
        $comma                  = ', ';
	    $value                  = trim($value);

        switch($key)
        {       // act on specific key
            case 'Census':
            {
                $census             = $value;
                $censusRec      = new Census(array('censusid'   => $value,
                                                   'collective' => 0));
                if ($censusRec->isExisting())
                    $getParms['census'] = $census;
                else
                    $msg        .= "Census='" . htmlspecialchars($value) .
                                        "' is invalid. ";
                break;
            }
    
            case 'District':
            {
                if (is_array($value))
                    $dists          = $value;
                else
                    $dists          = explode(",", $value);
        
                if (count($dists) > 0)
                {       // District value is not empty
                    if (count($dists) == 1)
                        $getParms['distid'] = $dists[0];
                    else
                        $getParms['distid'] = $dists;
                }       // District value is not empty
                $distList           = implode(',', $dists);
                break;
            }
    
            case 'Sched':
            {                   // schedule identifier
                $sched              = $value;
                break;
            }                   // schedule identifier
    
            default:
            {                   // anything else
                $msg    .= "Unexpected parameter $key='" .
                                htmlspecialchars($value) . "'. ";
                break;
            }                   // anything else
        }                       // act on specific key
    }                           // loop through parameters
    $parmsText                  .= "}";
}                               // have parameters passed by GET

if (count($getParms) == 0)
    $msg        .= "No parameters passed by method='get'. ";
$getParms['sched']              = $sched;

// display the results

// top node of result
print("$comma\n    \"select\" : {\n" .
            "        \"Census\" : \"$census\",\n" . 
            "        \"District\" : \"$distList\"\n" .
            "    },\n");
print "    $parmsText\n";

if (strlen($msg) == 0)
{       // no errors
    $result                     = new RecordSet('SubDistricts',
    $information                = $result->getInformation();
    $query                      = $information['query'];
    print "<query>" . htmlentities($query, ENT_XML1) . "</query>\n";

    $oldId                      = "";
    if (count($result) > 0)
    {   // at least one division returned
        foreach($result as $row)
        {   // loop through all result rows
            $distId             = $row->get('sd_distid');   // district id
            if (substr($distId,strlen($distId) - 2) == ".0")
                $distId         = substr($distId, 0, strlen($distId) - 2); 
            $sdId               = $row->get('sd_id');   // subdistrict id
            $sdName             = htmlentities($row->get('sd_name'),ENT_XML1);
            $division           = $row->get('sd_div');
            $lacReel            = $row->get('sd_lacreel');
            $imageBase          = $row->get('sd_imagebase');
            $relFrame           = $row->get('sd_relframe');
            $frameCt            = $row->get('sd_framect');
            $pages              = $row->get('sd_pages');
            $page1              = $row->get('sd_page1');
            $byPage             = $row->get('sd_bypage');

            if ($sdId != $oldId)
            {
                if ($oldId != "")
                    print("</option>\n");
                print("<option value='$sdId'>$sdName\n");
                $oldId          = $sdId;
            }
            if (strlen($division) > 0)
                print "<div id='$division'></div>\n";
            //$warn .= "<p> dist='$distId' sdid='$sdId' id='$division' sched='$sched' reel='$lacReel' base='$imageBase' frame='$relFrame' count='$frameCt' pages='$pages' page1='$page1' bypage='$byPage'</p>\n";
        }                   // loop through all result rows
        
        print "</option>\n";
    }                       // at least one division returned
}                           // no errors
else
{                           // report errors
    print "<message>$msg</message>\n";
}                           // report errors
print("</select>\n");       // close off top node of XML result
?>
