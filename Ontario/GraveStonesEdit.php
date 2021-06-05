<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  GraveStonesEdit.php                                                 *
 *                                                                      *
 *  Display form for editting grave stone information.                  *
 *  administered records                                                *
 *                                                                      *
 *  Parameters (passed by method=post):                                 *
 *      Domain          state/province domain code                      *
 *      County          three letter code                               *
 *      Township        township name                                   *
 *      Cemetery        cemetery name                                   *
 *      Zone            zone within cemetery for search                 *
 *      Row             row within zone for search                      *
 *      Plot            plot within Row for search                      *
 *      Zone999         zone within cemetery for update                 *
 *      Row999          row within zone for update                      *
 *      Plot999         plot within Row for update                      *
 *      Side000         side of stone for update                        *
 *      Surname999      surname for update                              *
 *      GivenName999    given name for update                           *
 *      Text999         text for update                                 *
 *      BirthDate999    yyyymmdd for update                             *
 *      DeathDate999    yyyymmdd for update                             *
 *                                                                      *
 *  History:                                                            *
 *      2012/05/16      created                                         *
 *      2013/08/04      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/11/27      handle database server failure gracefully       *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/01/23      use CSS instead of <table> for form layout      *
 *                      cleaner handling of SQL errors                  *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2018/02/03      change breadcrumbs to new standard              *
 *      2018/02/17      use Template                                    *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . "/County.inc";
require_once __NAMESPACE__ . "/CountySet.inc";
require_once __NAMESPACE__ . "/Township.inc";
require_once __NAMESPACE__ . "/TownshipSet.inc";
require_once __NAMESPACE__ . "/GraveStone.inc";
require_once __NAMESPACE__ . "/common.inc";

$domain         = 'CAON';
$provinceName       = 'Ontario';
$cc         = 'CA';
$countryName        = 'Canada';
$county         = 'MSX';
$township       = '';
$cemetery       = '';
$zone           = '';
$rownum         = '';
$plot           = '';
$newzone        = '';
$newrownum      = '';
$newplot        = '';
$lang           = 'en';
$oldindex       = '';
$gravestone     = null;

// loop through all of the passed parameters to validate them
// and save their values into local variables, overriding
// the defaults specified above
if (count($_GET) > 0)
{                       // invoked by method=get
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {               // switch on parameter name
            case 'domain':
            {           // language code
                $domain     = $value;
                break;
            }           // language code
    
            case 'county':
            {
                $county         = strtoupper($value);
                break;
            }
    
            case 'township':
            {
                $township           = ucwords($value);
                break;
            }
    
            case 'cemetery':
            {
                $cemetery           = ucwords($value);
                break;
            }
    
            case 'zone':
            {           // zone identifier
                $zone           = strtoupper($value);   
                break;
            }           // zone identifier
    
            case 'row':
            {           // row identifier
                $rownum         = strtoupper($value);   
                break;
            }           // row identifier
    
            case 'plot':
            {           // plot number in row
                $plot           = strtoupper($value);   
                break;
            }           // plot number in row
    
            case 'lang':
            case 'language':
            {           // language code
                $lang       = FtTemplate::validateLang($value);
                break;
            }           // language code
    
        }               // switch on parameter name
    }                   // foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                       // invoked by method=get
else
if (count($_POST) > 0)
{                       // invoked by method=get
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    
    foreach($_POST as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
        if (preg_match('/([a-zA-Z]+)(\d*)/',
                       $key,
                       $matches))
        {
            $fieldLc    = strtolower($matches[1]);
            $index      = $matches[2];
        }
        else
        {
            $fieldLc    = strtolower($key);
            $index      = '';
        }
        if ($debug)
            $warn   .= "GraveStonesEdit.php: " . __LINE__ .
                    " column='$fieldLc' index='$index' value='$value'</p>\n";
        switch($fieldLc)
        {               // act on individual parameter
            case 'domain':
            {           // province/state
                $domain         = strtoupper($value);
                break;
            }           // province/state
    
            case 'county':
            {           // county abbreviation
                $county         = strtoupper($value);
                break;
            }           // county abbreviation
    
            case 'township':
            {           // township name
                $township           = ucwords($value);
                break;
            }           // township name
    
            case 'cemetery':
            {           // cemetery name
                $cemetery           = ucwords($value);
                break;
            }           // cemetery name
    
            case 'zone':
            {           // zone identifier
                $zone           = strtoupper($value);   
                break;
            }           // zone identifier
    
            case 'zoneselect':
            {           // zone identifier
                $newzone            = strtoupper($value);   
                break;
            }           // zone identifier
    
            case 'newzone':
            {           // zone identifier
                $newzone            = strtoupper($value);   
                $warn   .= "<p>set newzone='$nozone'</p>\n";
                break;
            }           // zone identifier
    
            case 'row':
            {           // row identifier
                $rownum         = strtoupper($value);   
                break;
            }           // row identifier
    
            case 'newrow':
            {           // row identifier
                $newrownum          = strtoupper($value);   
                break;
            }           // row identifier
    
            case 'plot':
            {           // plot number in row
                $plot           = strtoupper($value);   
                break;
            }           // plot number in row
    
            case 'newplot':
            {           // plot number in row
                $newplot            = strtoupper($value);   
                break;
            }           // plot number in row
    
            case 'index':
            {           // internal record number
                $gs_index           = intval($value);   
                if ($gravestone)
                {           // building a record
                    if ($gravestone['givenname'] != '' ||
                        $gravestone['text'] != '' ||
                        $gravestone['birthdate'] != 0 ||
                        $gravestone['birthdate'] != 0)
                    {       // not empty
        if ($debug)
            $warn   .= "GraveStonesEdit.php: " . __LINE__ .
                            " update</p>\n";
                        $gravestone->save();
                    }       // not empty
                    else
                    {       // empty
                        if ($gravestone['gs_index'] > 0)
                        {       // existing record has become empty
        if ($debug)
            $warn   .= "GraveStonesEdit.php: " . __LINE__ .
                            " delete</p>\n";
                            $gravestone->delete(false);
                        }       // existing record has become empty
                    }       // empty
                    $gravestone     = null;
                }           // building a record
                if ($index != '999999999')
                {           // not delimiter 
                    $gravestone = new GraveStone(
                                array('gs_index'    => $gs_index,
                                      'county'      => $county,
                                      'township'    => $township,
                                      'cemetery'    => $cemetery,
                                      'zone'        => $zone,
                                      'row'     => $rownum,
                                      'plot'        => $plot));
                }           // not delimiter
                break;
            }           // internal record number
    
            case 'side':
            {           // side of stone
                if ($index == '')
                    $side           = strtoupper($value);   
                else
                    $gravestone->set('side', strtoupper($value));
                break;
            }           // side of stone
    
            case 'surname':
            {           // surname of individual
                if ($index == '')
                    $surname        = ucwords($value);   
                else
                {
                    $gravestone->set('surname', ucwords($value));
                }
                break;
            }           // surname of individual
    
            case 'givenname':
            {
                if ($index == '')
                    $givenname      = ucwords($value);   
                else
                    $gravestone->set('givenname', ucwords($value));
                break;
            }
    
            case 'birthdate':
            {
                if ($index == '')
                    $birthdate      = $value;   
                else
                    $gravestone->set('birthdate', $value);
                break;
            }
    
            case 'deathdate':
            {
                if ($index == '')
                    $deathdate      = $value;   
                else
                    $gravestone->set('deathdate', $value);
                break;
            }
    
            case 'text':
            {           // text other than birth and death
                if ($index == '')
                    $text           = $value;   
                else
                    $gravestone->set('text', $value);
                break;
            }           // text other than birth and death

            case 'lang':
            {           // language code
                if (strlen($value) >= 2)
                    $lang           = strtolower(substr($value,0,2));
                break;
            }           // language code
    
        }               // act on individual parameter
    }               // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                       // invoked by method=get

$domainObj      = new Domain(array('domain' => $domain,
                                   'language'   => $lang));
$provinceName       = $domainObj->get('name');
$province       = substr($domain, 2);
$cc         = substr($domain, 0, 2);
$country        = new Country(array('code' => $cc));
$countryName        = $country->get('name');

$counties       = new CountySet(array('domain' => $domainObj));
foreach($counties as $acounty)
{
    if (strtoupper($acounty['code']) == $county)
        $acounty['selected']    = 'selected="selected"';
    else
        $acounty['selected']    = '';
}

// if county specified get a list of townships
if ($county != '')
{
    $townships  = new TownshipSet(array('domain'    => $domainObj,
                                'county'    => $county));
    foreach($townships as $townshipObj)
    {
        if ($townshipObj->get('code') == $township)
            $townshipObj['selected']    = 'selected="selected"';
        else
            $townshipObj['selected']    = '';
    }
}
else
    $townships  = null;

// if township specified get a list of cemeteries
if ($county != '' && $township != '')
{
    $set    = new RecordSet('GraveStones',
                            array('county'      => "^$county$",
                                  'township'    => "^$township$"));
    $cemeteryNames  = $set->getDistinct('cemetery');
    $cemeteries     = array();
    $i          = 0;
    foreach($cemeteryNames as $name)
    {
        $instance   = array('i'     => $i,
                            'name'      => $name,
                            'selected'  => '');
        if ($name == $cemetery)
            $instance['selected']   = 'selected="selected"';
        $cemeteries[]           = $instance;
        $i++;
    }
}
else
    $cemeteries = null;

if ($newzone != '')
{
    $zone       = $newzone;
    $rownum     = $newrownum;
    $plot       = $newplot;
}

// if cemetery specified get a list of zones
if ($county != '' && $township != '' && $cemetery != '')
{
    $set    = new RecordSet('GraveStones',
                            array('county'      => "^$county$",
                                  'township'    => "^$township$",
                                  'cemetery'    => "^$cemetery$"));
    $zonelist       = $set->getDistinct('zone');
    $zones      = array();
    $i          = 0;
    foreach($zonelist as $zoneName)
    {
        $instance   = array('i'     => $i,
                            'name'      => $zoneName,
                            'selected'  => '');
        if ($zoneName == $zone)
            $instance['selected']   = 'selected="selected"';
        $zones[]            = $instance;
        $i++;
    }
}
else
    $zones  = null;

// execute the query to get the contents of the page
if ($county != '' && $township != '' && $cemetery != '' && $zone != '')
{
    $getParms   = array('county'    => "^$county$",
                        'township'  => "^$township$",
                        'cemetery'  => "^$cemetery$",
                        'zone'      => "^$zone$",
                        'row'       => "^$rownum$",
                        'plot'      => "^$plot$");
 
    $results        = new RecordSet('GraveStones', $getParms);
    $rowclass       = 'odd';
    $i          = 1;
    foreach($results as $stone)
    {
        $stone['rowclass']  = $rowclass;
        $stone['i']     = $i;
        $i++;
        if ($rowclass == 'odd')
            $rowclass       = 'even';
        else
            $rowclass       = 'odd';
    }

    if ($results->count() == 0)
    {               // no matching records
        $row        = array('county'    => $county,
                            'township'  => $township,
                            'cemetery'  => $cemetery,
                            'zone'      => $zone,
                            'row'       => $rownum,
                            'plot'      => $plot);

        for($i = 1; $i <= 10; $i++)
        {           // create 10 new lines
            $stone      = new GraveStone($row);
            $stone['rowclass']  = $rowclass;
            $stone['i']     = $i;
            if ($rowclass == 'odd')
                $rowclass       = 'even';
            else
                $rowclass       = 'odd';
            $results[]      = $stone;
        }           // create 10 new lines
    }               // no matching records
}
else
    $results    = null;

if (canUser('edit'))
    $action     = 'Edit';
else
    $action     = 'Display';
$tempBase       = $document_root . '/templates/';
$template       = new FtTemplate("GraveStones$action$lang.html");
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'GraceStonesEdit'));

$template->set('COUNTRYNAME',   $countryName);
$template->set('PROVINCENAME',  $provinceName);
$template->set('DOMAIN',    $domain);
$template->set('LANG',      $lang);
$template->set('COUNTY',    $county);
$template->set('TOWNSHIP',  $township);
$template->set('CEMETERY',  $cemetery);
$template->set('ZONE',      $zone);
$template->set('ROW',       $rownum);
$template->set('PLOT',      $plot);

// check file uploads
$filePrefix = "$domain-$county-$township-$cemetery-$zone-$rownum-$plot-";
$docRoot    = $_SERVER['DOCUMENT_ROOT'];
$images     = glob("$docRoot/Images/GraveStones/$filePrefix*");
$next       = count($images) + 1;
foreach($_FILES as $afile)
{
    $error      = intval($afile['error']);
    if ($error == 0)
    {               // no error
        $warn   .= "<p>file='" . $afile['name'] . "' uploaded to site: ";
        $check  = getimagesize($afile["tmp_name"]);
        if ($check !== false)
        {
            $warn   .= "File is an image - " . $check["mime"] . "</p>\n";
            if (preg_match('/\.(\w+)$/', $afile['name'], $matches))
            {
                $newName    = "$filePrefix$next.{$matches[1]}";
                move_uploaded_file($afile['tmp_name'],
                               "$docRoot/Images/GraveStones/$newName");
                $warn   .= "<p>Moved to '$docRoot/Images/GraveStones/$newName'</p>\n";
                $images[]   = "$docRoot/Images/GraveStones/$newName";
            }
            else
                $warn   .= "<p>Unable to get file extension from '" .
                            $afile['name'] . "'</p>\n";
        }
        else 
        {
            $warn   .= "File is not an image.</p>\n";
        }
    }               // no error
    else
    if ($error != 4)
    {               // error on this file
        $messageTag = $template->getElementById('phpFileUploadErrors');
        $messages   = $messageTag->childNodes();
        $message    = $messages[$error]->innerHTML();
        $warn   .= "<p>Unable to upload file='" . $afile['name'] . "' because $message</p>\n";
    }               // error on this file
}               // loop through uploaded files

$template->updateTag('County$code', $counties);
$template->updateTag('Township$code', $townships);
$template->updateTag('Cemetery$i', $cemeteries);
$template->updateTag('Zone$i', $zones);

if (count($images) > 0)
{
    $imgArray       = array();
    $i          = 1;
    foreach($images as $filename)
    {
        $imgArray[] = array('i' => $i,
                            'image' => substr($filename, strlen($docRoot)));
        $i++;
    }
    $template->updateTag('showImage$i', $imgArray);
}
else
    $template->updateTag('showImage$i', null);
if ($results)
    $template->updateTag('Row$i', $results);
else
    $template->updateTag('Row$i', $results);
    //$template->updateTag('dataTable', null);

$template->display();
