<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  TownshipsEdit.php                                                   *
 *                                                                      *
 *  Display form for editting information about townships for           *
 *  vital statistics records                                            *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      Domain      two letter country code                             *
 *                  + 2/3 letter province/state code                    *
 *      Prov        two letter code                                     *
 *      County      three letter code                                   *
 *                                                                      *
 *  History:                                                            *
 *      2012/05/07      created                                         *
 *      2013/08/04      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/11/27      handle database server failure gracefully       *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/09/29      use classes County and Township                 *
 *                      pass debug flag to update script                *
 *                      interpret country, province/state, and county   *
 *                      for title                                       *
 *      2014/10/19      prov keyword didn't work after last change      *
 *                      code field was not readonly for casual visitor  *
 *                      delete button was not disabled for visitor      *
 *      2014/11/03      minor change to title                           *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/19      debug trace was not shown                       *
 *                      include http.js before util.js                  *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2016/11/13      escape code value                               *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/02/07      use class Country                               *
 *                      correct row numbers                             *
 *                      remove duplicate <tbody>                        *
 *      2017/09/12      use get( and set(                               *
 *      2017/12/20      use class TownshipSet                           *
 *      2018/10/21      use class Template                              *
 *      2019/02/21      use new FtTemplate constructor                  *
 *      2019/04/12      merge in TownshipsUpdate.php                    *
 *                      simplify update                                 *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/06/30      add location field to Township record           *
 *      2021/01/16      correct XSS vulnerabilities                     *
 *                      improve parameter checking                      *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Township.inc';
require_once __NAMESPACE__ . '/TownshipSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$prov                           = 'ON';         // postal abbreviation
$provtext                       = null;
$statetext                      = null;
$domainCode                     = 'CAON';       // administrative domain
$domaintext                     = null;
$cc                             = 'CA';
$countryName                    = 'Canada';
$domainName                     = 'Ontario';
$countyCode                     = null;         // county abbreviation
$countytext                     = null;
$countyName                     = "Unknown";    // full name
$lang                           = 'en';
$offset                         = 0;
$limit                          = 20;

if (isset($_GET) && count($_GET) > 0)
{                           // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                                "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                       // loop through all parameters
        $valuetext          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                 "<td class='white left'>$valuetext</td></tr>\n";
        switch(strtolower($key))
        {                   // act on specific keys
            case 'domain':
            {
                if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                    $domainCode     = strtoupper($value);
                else
                    $domaintext     = htmlspecialchars($value);
                break;
            }
        
            case 'prov':
            case 'province':
            {
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                {
                    $prov           = strtoupper($value);
                    $domainCode     = 'CA' . $prov;
                }
                else
                    $provtext       = htmlspecialchars($value);
                break;
            }
        
            case 'state':
            {
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                {
                    $prov           = strtoupper($value);
                    $domainCode     = 'US' . $prov;
                }
                else
                    $statetext      = htmlspecialchars($value);
                break;
            }
        
            case 'county':
            {
                if (preg_match('/^[a-zA-Z]{3}$/', $value))
                    $countyCode     = $value;
                else
                    $countytext     = htmlspecialchars($value);
                break;
            }
    
            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
        
            case 'offset':
            {
                if (ctype_digit($value))
                    $offset     = intval($value);
                break;
            }
        
            case 'limit':
            {
                if (ctype_digit($value))
                    $limit      = intval($value);
                break;
            }

        }                   // act on specific keys
    }                       // loop through all parameters
    if ($debug)
        $warn                   .= $parmsText . "</table>\n";
}                           // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{                           // invoked by method=post
    $parmsText                  = "<p class='label'>\$_POST</p>\n" .
                                  "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {                       // loop through all parameters
        $parmsText              .= "<tr><th class='detlabel'>$key</th>" .
                                    "<td class='white left'>$value</td></tr>\n";
        switch(strtolower($key))
        {                   // act on specific keys
            case 'domain':
            {               // administrative domain
                if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                    $domainCode     = strtoupper($value);
                else
                    $domaintext     = htmlspecialchars($value);
                break;
            }               // administrative domain
    
            case 'prov':
            {               // administrative domain
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                {
                    $prov           = strtoupper($value);
                    $domainCode     = 'CA' . $prov;
                }
                else
                    $provtext       = htmlspecialchars($value);
                break;
            }               // administrative domain
    
            case 'county':
            {               // county abbreviation
                if (preg_match('/^[a-zA-Z]{3}$/', $value))
                    $countyCode     = $value;
                else
                    $countytext     = htmlspecialchars($value);
                break;
            }               // county abbreviation
    
            case 'lang':
            {               // language code
                $lang       = FtTemplate::validateLang($value);
                break;
            }               // language code
        
            case 'offset':
            {
                if (ctype_digit($value))
                    $offset     = intval($value);
                break;
            }
        
            case 'limit':
            {
                if (ctype_digit($value))
                    $limit      = intval($value);
                break;
            }
    
            default:
            {               // other input fields
                $matches        = array();
                $rres           = preg_match('/^([a-zA-Z]+)([0-9]+)$/', 
                                             $key, 
                                             $matches);
                if ($rres == 1)
                {           // name includes row number
                    $colname    = $matches[1];
                    $rownum     = $matches[2];
                    switch(strtolower($colname))
                    {       // act on column name
                        case 'code':
                        case 'name':
                        case 'oldcode':
                        case 'deletecode':
                        case 'idlr':
                        {
                            break;
                        }
    
                        default:
                        {   // other keywords
                            $warn   .= "<p>Unrecognized parameter $key='$value'.</p>\n";
                            break;
                        }   // other keywords
                    }       // act on column name
                }           // name includes row number
            }               // other input fields
        }                   // act on specific keys
    }                       // loop through all parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                   // invoked by method=post

// get the template
if (canUser('edit'))
    $action                 = 'Update';
else
    $action                 = 'Display';

$template                   = new FtTemplate("TownshipsEdit$action$lang.html");

// process domain
if (is_string($domaintext))
{
    $text                   = $template['invalidDomain']->outerHTML;
    $warn                   .= str_replace('$domain', $domaintext, $text);
}
if (is_string($provtext))
{
    $text                   = $template['invalidProv']->outerHTML;
    $warn                   .= str_replace('$prov', $provtext, $text);
}
if (is_string($statetext))
{
    $text                   = $template['invalidState']->outerHTML;
    $warn                   .= str_replace('$state', $statetext, $text);
}

$domain                     = new Domain(array('domain'     => $domainCode,
                                                'language'   => $lang));
$cc                         = $domain['cc'];
if ($domain->isExisting())
{
    $countryObj             = $domain->getCountry();
    $countryName            = $countryObj->getName();
    $domainName             = $domain->get('name');
}
else
{
    $text                   = $template['badDomain']->innerHTML();
    $msg                    .= str_replace('$value', $domainCode, $text);
}

if (is_string($countytext))
{
    $text                   = $template['badCounty']->innerHTML();
    $msg                    .= str_replace('$value', $countytext, $text);
}
else
if ($countyCode)
{                       // interpret county code
    $county                 = new County(array('domain' => $domain,
                                               'code'   => $countyCode));
    $countyName             = $county->get('name');
    if (!$county->isExisting())
    {
        $text               = $template['unknownCounty']->innerHTML();
        $msg                .= str_replace(array('$county', '$domain'),
                                           array($countyCode, $domainCode),
                                           $text);
    }
}                       // no errors
else
{
    $county                 = null;
    $msg                    .= $template['noCounty']->innerHTML();
    $townships              = array();
    $count                  = 0;
}

// if authorized and requested update the Townships taable
if (canUser('edit') && strlen($msg) == 0 &&
    isset($_POST) && count($_POST) > 0)
{                       // apply updates
    $township               = null;
    $code                   = null;
    $oldrownum              = '1';
    $data                   = '';
    foreach($_POST as $key => $value)
    {                   // loop through all parameters
        $matches            = array();
        $rres               = preg_match('/^([a-zA-Z]+)([0-9]+)$/', 
                                         $key, 
                                         $matches);
        if ($rres == 1)
        {               // name includes row number
            $colname        = $matches[1];
            $rownum         = $matches[2];
            switch(strtolower($colname))
            {           // act on column name
                case 'code':
                {       // first field in row
                    if ($township instanceof Township)
                        $township->save(false);
                    $code       = $value;
                    break;
                }       // first field in row
    
                case 'oldcode':
                {
                    $township   = new Township(array('county'   => $county,
                                                     'code'     => $value));
                    if ($code == 'delete')
                    {
                        $township->delete(false);
                        $township       = null;
                    }
                    else
                        $township['code']   = $code;
                    break;
                }
    
                case 'name':
                {
                    $township['name']       = $value;
                    break;
                }
    
                case 'idlr':
                {
                    $township['location']   = $value;
                    break;
                }
    
                case 'deletecode':
                {
                    break;
                }
    
            }           // act on column name
        }               // name includes row number
    }                   // loop through all parameters
}                       // apply updates

if (is_string($countyCode))
{                       // get set of Townships after update
    $getParms                   = array('county'    => $county);
    $townships                  = new TownshipSet($getParms);
    $info                       = $townships->getInformation();
    $count                      = $info['count'];
}                       // get set of Townships after update

$template->set('CONTACTTABLE',      'Counties');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('DOMAIN',            $domainCode);
$template->set('DOMAINNAME',        $domainName);
$template->set('COUNTYCODE',        $countyCode);
$template->set('COUNTYNAME',        $countyName);
$template->set('LANG',              $lang);
$template->set('OFFSET',            $offset);
$template->set('LIMIT',             $limit);
if (strlen($msg) == 0)
{
	$template->set('TOTALROWS',         $count);
	$template->set('FIRST',             $offset + 1);
	$template->set('LAST',              min($count, $offset + $limit));
	$template->set('$line',             '$line');
	//if ($offset > 0)
	//  $template->set('npPrev', "&offset=" . ($offset-$limit) . "&limit=$limit");
	//else
	//  $template->updateTag('prenpprev', null);
	//if ($offset < $count - $limit)
	//  $template->set('npNext', "&offset=" . ($offset+$limit) . "&limit=$limit");
	//else
	//  $template->updateTag('prenpnext', null);
	
	$rowElt                     = $template->getElementById('Row$line');
	$rowHtml                    = $rowElt->outerHTML();
	$data                       = '';
	$line                       = 1;
	foreach($townships as $township)
	{
	    $code                   = $township->get('code');
	    $name                   = $township->get('name');
	    $location               = $township->get('location');
	    $rtemplate              = new Template($rowHtml);
	    $rtemplate->set('line',     $line);
	    $rtemplate->set('code',     $code);
	    $rtemplate->set('name',     $name);
	    $rtemplate->set('location', $location);
	    $data                   .= $rtemplate->compile();
	    $line++;
	}
	$rowElt->update($data);
}
else
{
    $template['townshipForm']->update(null);
}
$template->display();

