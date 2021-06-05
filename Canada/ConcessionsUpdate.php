<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  ConcessionsUpdate.php                                               *
 *                                                                      *
 *  Update the database to reflect changes from ConcessionsEdit.php.    *
 *                                                                      *
 *  Parameters (passed by method=post):                                 *
 *      Domain          2-letter country code plus state/province code  *
 *      County          three letter code for county                    *
 *      Township        name of township                                *
 *                                                                      *
 *  History:                                                            *
 *      2016/06/19      created                                         *
 *      2017/02/07      use class Country                               *
 *      2017/09/12      use get(                                        *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Township.inc';
require_once __NAMESPACE__ . '/Concession.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$domain                 = 'CAON';
$domaintext             = null;
$prov                   = 'ON';
$countryName            = 'Unknown';
$domainName             = 'Unknown';
$county                 = null;
$countytext             = null;
$countyName             = null;
$township               = null;
$townshiptext           = null;
$lang                   = 'en';

// organize the parameters as an associative array
$parms              = array();
$setparms           = array();
$parmsText          = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
if (isset($_POST) && count($_POST) > 0)
{                   // invoked by method=get
    foreach($_POST as $key => $value)
    {                       // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {                   // act on specific keys
            case 'domain':
            {               // administrative domain
                if (preg_match('/^\w{4,5}$/', $value))
                    $domain         = strtoupper($value);
                else
                    $domaintext     = htmlspecialchars($value);
                break;
            }               // administrative domain
    
            case 'county':
            {               // county abbreviation
                if (preg_match('/^\w+$/', $value))
                    $county         = $value;
                else
                    $countytext     = htmlspecialchars($value);
                break;
            }               // county abbreviation
    
            case 'township':
            {               // township abbreviation
                if (preg_match('/^[^<>]+$/', $value))
                    $township       = $value;
                else
                    $townshiptext   = htmlspecialchars($value);
                break;
            }               // township abbreviation
    
            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
    
            default:
            {               // other input fields
                $matches    = array();
                $rres       = preg_match('/^([a-zA-Z]+)([0-9]+)$/', 
                                         $key, 
                                         $matches);
                if ($rres == 1)
                {           // name includes row number
                    $colname    = $matches[1];
                    $rownum     = $matches[2];
                    switch(strtolower($colname))
                    {       // act on column name
                        case 'conid':
                        case 'oldconid':
                        case 'order':
                        case 'firstlot':
                        case 'lastlot':
                        case 'latitude':
                        case 'longitude':
                        case 'latbylot':
                        case 'longbylot':
                        {
                            break;
                        }
    
                        default:
                        {   // other keywords
                            $msg   .= "Unrecognized parameter $key='" .
                                        htmlspecialchars($value) . "'. ";
                            break;
                        }   // other keywords
                    }       // act on column name
                }           // name includes row number
            }               // other input fields
        }                   // act on specific keys
    }                       // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                           // invoked by method=post

// notify the invoker if they are not authorized
if (canUser('edit'))
    $action         = 'Auth';       // authorized to update database
else
    $action         = 'Not';        // not authorized to update database

$template           = new FtTemplate("ConcessionsUpdate$action$lang.html");

if (is_string($domaintext))
{
    $text                   = $template['domainInvalid']->innerHTML;
    $msg                    .= str_replace('$value', $domaintext, $text);
    $domainName             = 'Invalid ' . $domaintext;
}
else
{
    $domainObj      = new Domain(array('domain'     => $domain,
                                       'language'   => $lang));
    if ($domainObj->isExisting())
    {
        $cc                 = substr($domain, 0, 2);
        $countryObj         = $domainObj->getCountry();
        $countryName        = $countryObj->getName();
        $domainName         = $domainObj->getName();
        $parms['domain']    = $domainObj;
    }
    else
    {
        $text               = $template['domainInvalid']->innerHTML;
        $msg                .= str_replace('$value', $domain, $text);
        $domainName         = $domainObj->getName();
    }
}
        
// validate county abbreviation
if (is_string($countytext))
{
    $text               = $template['countyInvalid']->innerHTML;
    $msg                .= str_replace(array('$county', '$domain'), 
                                       array($countytext, $domainName),
                                       $text);
    $countyName         = "Invalid $countytext";
}
else
if (is_null($county))
{
    $msg                .= $template['countyMissing']->innerHTML;
}
else
if ($domainObj && is_string($county))
{
    $countyObj          = new County(array('domain' => $domainObj, 
                                           'county' => $county));
    $countyName         = $countyObj->getName();
    if ($countyObj->isExisting())
        $parms['county']    = $countyObj;
    else
    {
        $text           = $template['countyInvalid']->innerHTML;
        $msg            .= str_replace(array('$county', '$domain'), 
                                       array($county, $domainName),
                                       $text);
    }
}

// validate township
if (is_string($townshiptext))
{
    $text               = $template['townshipInvalid']->innerHTML;
    $msg                .= str_replace(array('$township', '$county'),
                                       array($townshiptext, $countyName),
                                       $text);
    $townshipName       = "Invalid $townshiptext";
}
else
if (is_null($township))
{
    $msg                .= $template['townshipMissing']->innerHTML;
}
else
if ($domainObj && $countyObj && is_string($township))
{
    $townshipObj        = new Township(array('domain'   => $domainObj, 
                                             'county'   => $countyObj,
                                             'code'     => $township));
    $townshipName       = $townshipObj->getName();
    if ($townshipObj->isExisting())
        $parms['township']  = $townshipObj;
    else
    {
        $text           = $template['townshipInvalid']->innerHTML;
        $msg            .= str_replace(array('$township', '$county'),
                                       array($township, $countyName),
                                       $text);
    }
}

$template->set('CONTACTTABLE',  'Concessions');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('DOMAIN',        $domain);
$template->set('DOMAINNAME',    $domainName);
$template->set('COUNTYCODE',    $county);
$template->set('COUNTY',        $county);
$template->set('COUNTYNAME',    $countyName);
$template->set('TOWNSHIP',      $townshipName);
$template->set('TOWNSHIPNAME',  $townshipName);
$template->set('LANG',          $lang);

$data                   = '';
$changeCount            = 0;
if (canUser('edit') && strlen($msg) == 0)
{       // authorized and no errors
    $concession         = null;
    $conid              = null;
    $oldrownum          = '1';
    foreach($_POST as $key => $value)
    {                   // loop through all parameters
        $matches        = array();
        $rres           = preg_match('/([a-zA-Z]+)([0-9]+)/', $key, $matches);
        if ($rres == 1)
        {               // name includes row number
            $colname    = $matches[1];
            $rownum     = $matches[2];
            if ($rownum != $oldrownum)
            {
                $oldrownum  = $rownum;
                if (!is_null($concession))
                {       // collected change data
                    $changed        = $concession->save();
                    if ($changed)
                    {       // concession changed
                        $changeCount++;
                        $conid      = $concession->get('conid');
                        $temElt     = $template->getElementById($action);
                        $atemplate  = new Template($temElt->outerHTML());
                        $atemplate->set('CONID', $conid);
                        $data       .= $atemplate->compile();
                    }       // concession changed
                    $concession = null;
                }       // collected change data
            }       // start new row

            switch(strtolower($colname))
            {       // act on column name
                case 'conid':
                {   // new concession name
                    $newconid   = $value;
                    break;
                }   // new concession name

                case 'oldconid':
                {   // original concession name
                    if (substr($value,0,2) == '??')
                        $conid          = $newconid;// adding new row
                    else
                        $conid          = $value;   // lookup key
                    $parms['conid']     = $conid;
                    $setparms['conid']  = "^$conid$";
                    $concession         = new Concession($parms);
                    if ($newconid == 'deleted')
                    {
                        if ($concession->isExisting())
                            $concession->delete(false);
                        $temElt         = $template->getElementById('deleted');
                        $atemplate      = new Template($temElt->outerHTML());
                        $atemplate->set('CONID', $conid);
                        $data           .= $atemplate->compile();
                        $concession     = null;
                        $changeCount++;
                    }
                    else
                    {           // possible change to conid
                        if (strtolower($newconid) != strtolower($conid))
                            $concession->set('conid', $newconid); 
                        if ($concession->isExisting())
                        {
                            $action     = 'changed';
                        }
                        else
                        {
                            $action     = 'added';
                        }
                    }           // possible change to conid
                    break;
                }               // original concession name

                default:
                {
                    if (!is_null($concession))
                        $concession->set($colname, $value);
                    break;
                }

            }                   // act on column name
        }                       // field name includes row number
    }                           // loop through all parameters

    switch($changeCount)
    {                               // act on values of changeCount 
        case 0:
        {
            $temId          = "count0";
            break;
        }
    
        case 1:
        {
            $temId          = "count1";
            break;
        }
    
        default:
        {
            $temId          = "countmany";
            break;
        }
    }                               // act on values of changeCount 
    $temElt             = $template->getElementById($temId);
    $atemplate          = new Template($temElt->outerHTML());
    $atemplate->set('CHANGECOUNT',  $changeCount);
    $data               .= $atemplate->compile();
}                               // authorized and no errors
else
    $data               = '';
$template->set('DATA',          $data);

$template->display();
