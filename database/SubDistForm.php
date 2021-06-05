<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  SubDistForm.php                                                     *
 *                                                                      *
 *  Display form for editting sub-district information for a district   *
 *  of a Census of Canada                                               *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      Census          census identifier CCYYYY                        *
 *      Province        two letter code, required on pre-confederation  *
 *                      censuses                                        *
 *      District        district number within census                   *
 *      NameWidth       explicit width of name column                   *
 *      RemarksWidth    explicit width of remarks column                *
 *      FcAuto          automatic update of frame count, page count     *
 *                      and population                                  *
 *                                                                      *
 *  History:                                                            *
 *      2010/10/01      use new format                                  *
 *      2010/10/04      correct call to genCell in new page             *
 *      2010/10/27      move connection establishment to common.inc     *
 *      2010/11/20      use htmlHeader                                  *
 *                      add page increment column                       *
 *      2010/11/20      improve validation                              *
 *      2010/11/23      improve separation of HTML and PHP              *
 *                      add delete row button                           *
 *                      add button to display page table                *
 *      2011/03/31      post 1901 censuses use numeric sub-districts    *
 *      2011/04/20      escape name of district and subdistrict         *
 *      2011/06/05      add capability to hide columns                  *
 *                      improve and clarify parameter validation        *
 *                      determine previous and next district from DB    *
 *                      correct sort order                              *
 *      2011/06/27      add 1916 census support                         *
 *      2012/09/15      include province in URI for forward and back    *
 *                      links                                           *
 *                      use census identifier, not just year in links   *
 *      2013/01/26      table SubDistTable renamed to SubDistricts      *
 *                      set explicit maximum lengths on input fields    *
 *      2013/04/13      support being invoked without edit              *
 *                      authorization better                            *
 *      2013/07/02      permit explicit setting of width of name column *
 *      2013/08/18      do not split label of "Add Division" button     *
 *      2013/08/21      add support for 1921 census                     *
 *                      improve title of dialog                         *
 *      2013/08/26      use selective capitalization on name            *
 *      2013/08/27      pass full census identifier and province to     *
 *                      ReqUpdateSubDists.html                          *
 *      2013/09/16      add FcAuto parameter                            *
 *                      add RemarksWidth parameter                      *
 *      2013/11/22      handle lack of database server connection       *
 *      2014/04/26      remove formUtil.inc obsolete                    *
 *      2014/09/22      permit a district number ending in ".0"         *
 *                      add id= attribute to all form elements          *
 *      2015/03/28      autocorrect population in 1861 census           *
 *      2015/05/09      remove use of <table> for layout                *
 *      2015/06/05      use class District instead of SQL               *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/20      add id to debug trace div                       *
 *                      include http.js before util.js                  *
 *                      field names changed in $censusInfo              *
 *                      extra ampersand in prev and next links          *
 *                      use class Census                                *
 *      2016/12/26      do not generate fatal error on bad ident        *
 *      2017/02/07      use class Country                               *
 *      2017/08/06      permit updating id and division in existing     *
 *                      record.                                         *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/15      use class Template                              *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/10      split administration and display templates      *
 *      2018/01/24      use SubDistrictSet                              *
 *                      correct null relative frame number              *
 *      2018/02/03      correct handling of $censusObj['partof']        *
 *      2018/05/22      choose display class for id, name, lacreel,     *
 *                      ldsreel, and image base to distinguish new      *
 *                      from unchanged values                           *
 *      2019/02/21      use new FtTemplate constructor                  *
 *      2020/05/03      correct updating of Page1                       *
 *      2020/10/10      use numeric subdistrict ids for 1851 and 1861   *
 *      2020/11/25      save changes to last subdistrict                *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/SubDistrictSet.inc';
require_once __NAMESPACE__ . '/common.inc';
 
// default values for parameters
$censusId               = '';
$censusYear             = 9999;
$cc                     = 'CA';
$countryName            = 'Canada';
$province               = '';
$provinceName           = '';
$distId                 = '';
$DName                  = '';
$data                   = array();
$name                   = '';
$provList               = '';
$nameWidth              = 20;       // default width of name column
$remarksWidth           = 16;       // default width of remarks column
$fcAuto                 = false;    // control automatic update of
$lang                   = 'en';     // default english
$update                 = canUser('admin');
$npuri                  = '';       // for next and previous links
$npand                  = '?';      // adding parms to $npuri
$npPrev                 = '';       // previous selection
$npNext                 = '';       // next selection

// validate all parameters passed to the server 
// if invoked by method=get process the parameters
if (count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        if ($value == '?')
        {       // value explicitly not supplied
            $msg    .= $key. ' must be selected. ';
        }       // value explicitly not supplied
        else
        switch(strtolower($key))
        {       // act on parameter name
            case 'census':
            case 'censusid':
            {       // Census Identifier
                $censusId           = $value;
                $censusYear         = substr($censusId, -4);
                break;
            }       // Census year
    
            case 'province':
            {       // province code
                $province           = $value;
                break;
            }       // province code
    
            case 'district':
            {       // district number
                $distId             = $value;
                break;
            }       // District number
    
            case 'namewidth':
            {       // explicit width of name column
                $nameWidth          = $value;
                break;
            }       // explicit width of name column
    
            case 'remarkswidth':
            {       // explicit width of remarks column
                $remarksWidth       = $value;
                break;
            }       // explicit width of remarks column
    
            case 'fcauto':
            {       // automatic update of frame count and page count
                $fcAuto             = strtolower(substr($value,0,1)) == 'y';
                break;
            }       // automatic update of frame count and page count
    
            case 'lang':
            {       // debug handled by common code
                $lang           = FtTemplate::validateLang($value);
                break;
            }       // debug handled by common code
    
            default:
            {       // unexpected
                if (strlen($value) > 0)
                {
                    $npuri          .= "{$npand}{$key}={$value}";
                    $npand          = '&amp;'; 
                }
                break;
            }       // unexpected
        }       // act on parameter name
    }       // foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{                   // invoked by submit to update account
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                  "<th class='colhead'>value</th></tr>\n";
    $subDistrict                    = null;

    foreach($_POST as $key => $value)
    {               // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        // each name in $_POST consists of a database field name and
        // a row number which may be either 2 or 3 digits long
        $numMatches = preg_match('/^([a-zA-Z_]+)(\d*)$/',
                                 $key,
                                 $matches);
        if ($numMatches == 1)
        {
            $column                 = strtolower($matches[1]);
            $rownum                 = $matches[2];
        }
        else
        {
            $column                 = strtolower($key);
            $rownum                 = '';
        }
        if ($column == 'sd_pagei')
            $column                 = 'sd_page1';   // old fixup
        else
        if ($column == 'sd_page' && substr($row, 0, 1) == '1')
        {
            $column                 = 'sd_page1';   // fixup
            $row                    = substr($row, 1);
        }

        switch($column)
        {           // act on specific parameter
            case 'census':
            {       // Census Identifier
                $censusId           = $value;
                $censusYear         = substr($censusId, -4);
                $sdParms['sd_census']   = $value;
                break;
            }       // Census year
    
            case 'province':
            {       // province code
                $province           = $value;
                break;
            }       // province code
    
            case 'district':
            {       // district number
                $distId             = $value;
                $sdParms['sd_distid']   = $value;
                break;
            }       // District number
    
            case 'namewidth':
            {       // explicit width of name column
                $nameWidth          = $value;
                break;
            }       // explicit width of name column
    
            case 'remarkswidth':
            {       // explicit width of remarks column
                $remarksWidth       = $value;
                break;
            }       // explicit width of remarks column
    
            case 'fcauto':
            {       // automatic update of frame count and page count
                $fcAuto             = strtolower(substr($value,0,1)) == 'y';
                break;
            }       // automatic update of frame count and page count
    
            case 'lang':
            {       // requested language
                $lang           = FtTemplate::validateLang($value);
                break;
            }       // requested language

            case 'sd_id':
            {
                $sd_id                  = $value;
                break;
            }

            case 'orig_id':
            {
                if ($subDistrict)
                {                   // apply pending changes
                    if ($subDistrict->save() > 0)
                    {               // database changed
                        $sqlcmd         = $subDistrict->getLastSqlCmd();
                        $warn   .= "<p>SubDistForm.php: " . __LINE__ .
                                        " issued '$sqlcmd'</p>\n";
                    }               // database changed
                    $subDistrict        = null;
                }                   // apply pending changes
                $sdParms['sd_id']       = $value;
                break;
            }

            case 'orig_div':
            {
                $sdParms['sd_div']      = $value;
                break;
            }

            case 'orig_sched':
            {
                $sdParms['sd_sched']    = $value;
                $subDistrict            = new SubDistrict($sdParms);
                $subDistrict->set('sd_id', $sd_id);
                break;
            }

            case 'sd_name':
            {
                if (strtolower($value) == '[delete]')
                {                   // user deleted the subdistrict
                    if ($subDistrict->delete(false))
                    {               // database changed
                        $sqlcmd         = $subDistrict->getLastSqlCmd();
                        $warn   .= "<p>SubDistForm.php: " . __LINE__ .
                                        " issued '$sqlcmd'</p>\n";
                    }               // database changed
                    $subDistrict        = null;
                }                   // user deleted the subdistrict
                else                // update the name
                    $subDistrict->set($column, $value);
                break;
            }

            case 'sd_div':
            case 'sd_sched':
            case 'sd_pages':
            case 'sd_page1':
            case 'sd_population':
            case 'sd_lacreel':
            case 'sd_ldsreel':
            case 'sd_imagebase':
            case 'sd_relframe':
            case 'sd_framect':
            case 'sd_bypage':
            case 'sd_remarks':
            {
                if ($subDistrict)
                {
                    $subDistrict->set($column, $value);
                }
                break;
            }
    
        }           // act on specific parameter
    }               // loop through all parameters
    if ($debug)
        $warn           .= $parmsText . "</table>\n";
}                   // invoked by submit to update account

// create Template
$tempBase               = $document_root . '/templates/';
if ($update)
    $action             = 'Update';
else
    $action             = 'Display';
$includeSub             = "SubDistForm$action$censusId$lang.html";
if (!file_exists($tempBase . $includeSub))
{                           // no census and language specific form
    $includeSub         = "SubDistForm$action{$censusId}en.html";
    if (file_exists($tempBase . $includeSub))
    {                       // let common language handling recover
        $includeSub     = "SubDistForm$action$censusId$lang.html";
    }                       // let common language handling recover
    else
    {                       // no census specific form
        if ($censusYear < 1867)
        {                   // pre-confederation
            $includeSub = "SubDistFormPre$action$lang.html";
        }                   // pre-confederation
        else
        {                   // post-confederation
            $includeSub = "SubDistForm$action$lang.html";
        }                   // post-confederation
    }                       // no census specific form
}                           // no census and language specific form

$template               = new FtTemplate($includeSub);

// validate census
if (strlen($censusId) == 4)
    $censusId           = 'CA' . $censusId;

$census                 = new Census(array('censusid' => $censusId,
                                           'collective' => 0));
$cc                     = $census['cc'];
$country                = new Country(array('code' => $cc));
if (is_null($province))
    $province           = $census['province'];
$countryName            = $country->getName();
$censusYear             = $census['year'];
$name                   = $census['name'];
$provList               = $census['provinces'];
$npuri                  .= "{$npand}Census=$censusId";
$npand                  = '&amp;'; 

// validate district identifier
if (preg_match('/^[0-9]+(\.[05]|)$/', $distId) == 1)
{       // matches pattern of a district number
    if ($distId == floor($distId))
        $distId         = intval($distId);
}       // matches pattern of a district number
else
    $msg                .= "District value '$distId' invalid. ";

// if no error messages display the query
if (strlen($msg) == 0)
{
    $getParms                   = array();
    if ($censusYear == 1851 || $censusYear == 1861)
        $getParms['d_census']   = $province . $censusYear;
    else
        $getParms['d_census']   = $censusId;
    $getParms['d_id']           = $distId;
    $district                   = new District($getParms);

    $DName                      = $district['d_name']; 
    $province                   = $district['d_province'];
    $prev                       = $district->getPrev();
    if ($prev)
    {       // there is a previous district
        $prevDist               = $prev['d_id'];
        if ($prevDist == floor($prevDist))
            $prevDist           = intval($prevDist);
        $npPrev                 .= $npand . 'District=' . $prevDist;
    }       // there is a previous district
    $next   = $district->getNext();
    if ($next)
    {       // there is a next row
        $nextDist               = $next['d_id'];
        if ($nextDist == floor($nextDist))
            $nextDist           = intval($nextDist);
        $npNext                 .= $npand . 'District=' . $nextDist;
    }       // there is a next row

    // get the set of SubDistricts for this District
    $subdistList                = $district->getSubDistricts();
    $info                       = $subdistList->getInformation();
    $count                      = $info['count'];
    $query                      = $info['query'];
    if ($debug)
    {
        $warn   .= "<p>\$district->getSubDistricts() used query='$query' and returned $count divisions</p>\n";
    }

    $domain                     = new Domain(array('domain' => "$cc$province"));
    $provinceName               = $domain['name'];
    // load the results into a parameter array
    if (count($subdistList) > 0)
    {               // page already exists in database
        $line                       = 1;
        $prevSubDistrict            = null;
        $data                       = array();
        $oldid                      = '';
        $oldname                    = '';
        $oldlac                     = '';
        $oldlds                     = '';
        $oldbase                    = '';
        foreach($subdistList as $ip => $subDistrict)
        {           // loop through all subdistricts
            $line                   = str_pad($line, 2, "0", STR_PAD_LEFT);
            $id                     = $subDistrict['sd_id'];
            if ($id == $oldid)
                $idclass            = 'same';
            else
                $idclass            = 'black';
            $div                    = $subDistrict['sd_div'];
            $name                   = $subDistrict['sd_name'];
            if ($name == $oldname)
                $nameclass          = 'same';
            else
                $nameclass          = 'black';
            $pages                  = $subDistrict['sd_pages'];
            $page1                  = $subDistrict['sd_page1'];
            $bypage                 = $subDistrict['sd_bypage'];
            $population             = $subDistrict['sd_population'];
            $lacreel                = $subDistrict['sd_lacreel'];
            if ($lacreel == $oldlac)
                $lacclass           = 'same';
            else
                $lacclass           = 'black';
            $ldsreel                = $subDistrict['sd_ldsreel'];
            if ($ldsreel == $oldlds)
                $ldsclass           = 'same';
            else
                $ldsclass           = 'black';
            if ($ldsreel === null || $ldsreel === 'NULL')
                $ldsreel            = 0;
            $imagebase              = $subDistrict['sd_imagebase'];
            if ($imagebase == $oldbase)
                $baseclass          = 'same';
            else
                $baseclass          = 'black';
                    $relframe       = $subDistrict['sd_relframe'];
            $framect                = $subDistrict['sd_framect'];
            $remarks                = $subDistrict['sd_remarks'];
    
            $oldid                  = $id;
            $oldname                = $name;
            $oldlac                 = $lacreel;
            $oldlds                 = $ldsreel;
            $oldbase                = $imagebase;
    
            // if requested, calculate the frame count and page count
            if ($fcAuto && $prevSubDistrict)
            {       // automatically calculate frame count and page count
                $framect            = $relframe -   
                              $prevSubDistrict['sd_relframe'];  
                if ($censusYear == 1901 ||
                    $censusYear == 1911)
                    $pages          = $framect;
                else
                if ($censusYear == 1921)
                    $pages          = $framect - 1;
                else
                if (($censusYear == 1851 || $censusYear == 1861))
                    $pages          = ceil($framect / 2);
                else
                    $pages          = $framect * 2;
                    $population     = floor(($pages - 0.5) *
                                            $census['linesperpage']);
            }       // automatically calculate frame count and page count
    
            // autocorrect population if the current value is the default
            if ($censusYear == 1861 && $population == 500) 
                $population         = floor(($pages - 0.5) *
                                            $census['linesperpage']);
            if ($framect == 0)
            {           // frame count not initialized yet
                if ($censusYear == 1901 ||
                    $censusYear == 1911 ||
                    $censusYear == 1921 )
                    $framect        = $pages;
                else
                if (($censusYear == 1851 || $censusYear == 1861))
                    $framect    = $pages * 2;
                else
                    $framect    = ceil($pages / 2);
            }           // frame count not initialized yet
            if (is_null($relframe))
                $relframe       = 0;
    
            $data[]     = array('line'      => $line,   
                            'id'            => $id,     
                            'idclass'       => $idclass,    
                            'div'           => $div,    
                            'name'          => $name,   
                            'nameclass'     => $nameclass,  
                            'pages'         => $pages,      
                            'page1'         => $page1,  
                            'bypage'        => $bypage,     
                            'population'    => $population, 
                            'lacreel'       => $lacreel,    
                            'lacclass'      => $lacclass,   
                            'ldsreel'       => $ldsreel,    
                            'ldsclass'      => $ldsclass,   
                            'imagebase'     => $imagebase,  
                            'baseclass'     => $baseclass,  
                            'relframe'      => $relframe,   
                            'framect'       => $framect,    
                            'nameWidth'     => $nameWidth,
                            'remarksWidth'  => $remarksWidth,
                            'remarks'       => $remarks);   
            $line++;
            $prevSubDistrict    = $subDistrict;
        }           // loop through all subdistricts
    }               // subdistricts already exists in database
    else
    {               // fill in empty district
        $censusYear     = intval(substr($censusId, 2, 4));
        $framect        = floor(500 / $census['linesperpage']);
    
        $lineCt         = 26;   // number of initial entries
        for ($i = 1; $i <= $lineCt; $i++)
        {           // loop through simulated sub-districts
                    // ensure that line number is always 2 digits
            $line       = (string) $i;
            if (strlen($line) == 1)
                $line   = "0".$line;
            if ($censusYear >= 1871 && $censusYear < 1906)
                $sd_id  = substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                             $i - 1, 1);
            else
                $sd_id  = $i;
    
            $data[]     = array('line'          => $line,   
                                'id'            => $sd_id,      
                                'idclass'       => 'black', 
                                'div'           => '',      
                                'name'          => 'SubDistrict ' . $sd_id,
                                'nameclass'     => 'black', 
                                'pages'         => '10',    
                                'page1'         => '1',     
                                'bypage'        => '1',     
                                'population'    => 475, 
                                'lacreel'       => 'C-9999',    
                                'lacclass'      => 'same',  
                                'ldsreel'       => 0,   
                                'ldsclass'      => 'same',  
                                'imagebase'     => '0',
                                'baseclass'     => 'same',  
                                'relframe'      => '0',     
                                'framect'       => $framect,    
                                'nameWidth'     => $nameWidth,
                                'remarksWidth'  => $remarksWidth,
                                'remarks'       => '');
        }           // loop through simulated sub-districts
    }               // fill in empty district
}                   // no errors in validation


// parameters to ReqUpdateSubDists.html
$search = "?Census=$censusId&amp;Province=$province&amp;District=$distId&amp;lang=$lang";

// notify the invoker if they are not authorized
$title  = "Census Administration: $countryName: $censusYear Census: Sub-District Table";

$template->set('CENSUSYEAR',        $censusYear);
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('CENSUSID',          $censusId);
$template->set('PROVINCE',          $province);
$template->set('PROVINCENAME',      $provinceName);
$template->set('DISTID',            $distId);
$template->set('DNAME',             $DName);
$template->set('SEARCH',            $search);
$template->set('NAMEWIDTH',         $nameWidth);
$template->set('REMARKSWIDTH',      $remarksWidth);
$template->set('FCAUTO',            $fcAuto);
$template->set('CONTACTTABLE',      'SubDistricts');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$linkHdr    = $template->getElementById('linkHdr');

if (strlen($npPrev) > 0)
{
    $template->updateTag('topPrev', array('npPrev' => $npuri . $npPrev));
    $template->updateTag('botPrev', array('npPrev' => $npuri . $npPrev));
}
if (strlen($npNext) > 0)
{
    $template->updateTag('topNext', array('npNext' => $npuri . $npNext));
    $template->updateTag('botNext', array('npNext' => $npuri . $npNext));
}
$template->updateTag('Row$line',
                     $data);
$template->display();
showTrace();
