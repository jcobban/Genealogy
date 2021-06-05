<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateLocation.php                                                  *
 *                                                                      *
 *  Handle a request to update an individual location in                *
 *  the Legacy family tree database.                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      idlr    unique numeric identifier of the LegacyLocation record  *
 *              to update                                               *
 *      others  any field name defined in the LegacyLocation record     *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/16      Add information into log                        *
 *      2010/10/05      redirect to locations list, not updated record  *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/12/05      improve error handling                          *
 *      2011/09/26      support geo-locator format latitude and         *
 *                      longitude                                       *
 *      2012/01/13      change class names                              *
 *      2013/04/12      use possibly updated location name for          *
 *                      search pattern                                  *
 *      2013/04/16      adjusting latitude and longitude to internal    *
 *                      values is moved to LegacyLocation::postUpdate   *
 *      2013/05/18      permit creation of new location signalled       *
 *                      by IDLR=0                                       *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/10/01      use method isOwner to determine authorization   *
 *                      to update                                       *
 *      2015/01/06      diagnostic information redirected to $warn      *
 *                      if creating new location and no errors detected *
 *                      redirect to menu of locations                   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/08/21      restore redirect to menu at end if not          *
 *                      invoked in child window                         *
 *      2016/01/19      add id to debug trace                           *
 *      2016/05/31      correct setting of latitude and longitude       *
 *      2016/06/06      addOwner fails for new location                 *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2017/09/12      use set(                                        *
 *      2018/12/12      use class Template                              *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/06/30      support feedback                                *
 *      2020/12/27      get message texts from template                 *
 *                      cover XSS vulnerabilities                       *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$idlr                       = null;     // primary key
$idlrtext                   = null;     // key if syntactically invalid
$location                   = null;     // instance of Location
$locationName               = null;     // location name
$latitude                   = null;     // latitude
$longitude                  = null;     // longitude
$updates                    = array();  // field updates
$feedback                   = null;     // name of field to set new IDLR
$lang                       = 'en';
$closeAtEnd                 = false;

// get the requested Location record
// override from passed parameters
if (isset($_POST) && count($_POST) > 0)
{                       // invoked by method=post
    $parmsText          = "<p class='label'>\$_POST</p>\n" .
                          "<table class='summary'>\n" .
                          "<tr><th class='colhead'>key</th>" .
                              "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n";
        $key        = strtolower($key);
        $value      = trim($value);
        switch($key)
        {               // act on specific keys
            case 'idlr':
            {           // identifier present
                if (ctype_digit($value))
                {       // positive integer
                    $idlr           = $value;
                }       // positive integer
                else
                    $idlrtext       = htmlspecialchars($value);
                break;
            }           // identifier present

            case 'location':
            {           // location name
                if (strlen($value) > 0)
                    $locationName   = $value;
                break;
            }           // location name supplied

            case 'latitude':
            {
                if (is_numeric($value))
                    $latitude       = $value;
                break;
            }           // latitude

            case 'longitude':
            {
                if (is_numeric($value))
                    $longitude      = $value;
                break;
            }           // longitude

            case 'fsplaceid':
            case 'used':
            case 'sortedlocation':
            case 'tag1':
            case 'shortname':
            case 'preposition':
            case 'notes':
            case 'verified':
            case 'fsresolved':
            case 'veresolved':
            case 'qstag':
            case 'zoom':
            case 'boundary':
            {
                $updates[$key]      = $value;
                break;
            }           // longitude

            case 'closeatend':
            {           // close the frame after update
                if (strtolower($value) == 'y')
                    $closeAtEnd     = true;
                break;
            }           // close the frame after update

            case 'feedback':
            {
                $feedback           = $value;
                break;
            }

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }
        }               // act on specific keys
    }                   // loop through all parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                       // invoked by method=post

$template               = new FtTemplate("updateLocationError$lang.html");

if (is_string($idlrtext))
{
    $text               = $template['invalidIDIR']->innerHTML;
    $msg                .= str_replace('$idlrtext', $idlrtext, $text);
}

if ($idlr > 0)
{       // IDLR of existing location
    $location   = new Location(array('idlr' => $idlr));
}       // IDLR of existing location
else
if (is_string($locationName))
{       // create new location
    $location   = new Location(array('location' => $locationName));

    // make the current user an owner of this location
    if (!$location->isExisting())
        $location->save();
    $location->addOwner();
}       // create new location
else
    $msg        .= $template['missingBoth']->innerHTML;

// use possibly updated location name
// for search pattern
if ($location instanceof Location)
{
    if (is_string($locationName))
        $location->setName($locationName);
    if (is_numeric($latitude))
        $location->setLatitude($latitude);
    if (is_numeric($longitude))
        $location->setLongitude($longitude);
    foreach($updates as $field => $value)
        $location[$field]   = $value;
    $pattern                = $location->getName();
    if (strlen($pattern) > 5)
        $pattern            = substr($pattern, 0, 5);

    if ($location->isOwner())
        $location->save();
}
else
    $pattern                = '';


if (strlen($msg) > 0 || strlen($warn) > 0)
{
    $template->set('LANG',          $lang);
    $template->set('NAMESTART',     htmlspecialchars($pattern));
    $template->set('IDLR',          $idlr);
    $template->display();
}
else
{                       // update was successful
    if ($closeAtEnd)
    {                   // close the dialog
        $template           = new FtTemplate("updateLocationOK$lang.html");
        $template->set('LANG',      $lang);
        $template->set('NAMESTART', htmlspecialchars($pattern));
        $template->set('IDLR',      $idlr);
        $template->display();
    }                   // close the dialog
    else
    {                   // redirect to main page for locations
        $url    = 'Locations.php?pattern=^' . urlencode($pattern) .
                                "&lang=$lang" .
                                "&idlr=$idlr";
        if (is_string($feedback) && strlen($feedback) > 0)
           $url .= "&feedback=$feedback";
        header("Location: $url");
    }                   // redirect to main page for locations
}                       // update was successful
