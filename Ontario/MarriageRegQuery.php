<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  MarriageRegQuery.php                                                *
 *                                                                      *
 *  Prompt the user to enter parameters for a search of the             *
 *  Marriage Registration database.                                     *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      domain          ISO 3166-2 domain code                          *
 *      regdomain       synonym for domain                              *
 *      lang            ISO 639-1 preferred language                    *
 *      regyear         registration year                               *
 *      regnum          registration num                                *
 *      limit           default max number of rows in response          *
 *      count           synonym for limit                               *
 *                                                                      *
 *  History:                                                            *
 *      2011/01/09      change URL of transcription status page         *
 *      2011/03/15      use <button>, change quotes, separate JS & HTML *
 *                      enable topRight button                          *
 *      2011/06/17      use CSS to layout header and footer             *
 *                      use class=form for form layout table            *
 *      2011/08/10      add checkboxes for roles to include in search   *
 *                      pretty up table tags                            *
 *      2011/09/13      change name of response script                  *
 *      2011/10/24      support mouseover help for signon button        *
 *      2011/10/28      use button in place of link for statistics      *
 *      2012/05/06      set class for all input fields                  *
 *      2013/08/04      defer initialization of facebook link           *
 *      2014/01/03      replace tables with CSS for layout              *
 *      2014/02/10      change to PHP so it can exploit domains table   *
 *                      add <select name='RegDomain'> to choose domain  *
 *                      group options with <fieldset>                   *
 *      2014/04/01      do not warn for some parameters                 *
 *      2015/07/01      add Occupation field for search                 *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/04/25      replace ereg with preg_match                    *
 *      2017/02/07      use class Country                               *
 *      2017/02/18      add fields OriginalVolume, OriginalPage, and    *
 *                      OriginalItem                                    *
 *      2018/01/01      add language parameter                          *
 *      2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/07/28      accept ISO 3166 standard form for domain        *
 *                      list only domains that are in the same country  *
 *      2019/11/17      move CSS to <head>                              *
 *      2022/03/12      improve parameter validation                    *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc                     = 'CA';
$countryName            = 'Canada';
$domain                 = 'CAON';   // default domain
$domaintext             = null;
$domainName             = 'Canada: Ontario:';
$stateName              = 'Ontario';
$lang                   = 'en';
$langtext               = null;
$regyear                = null;
$regyeartext            = null;
$regnum                 = null;
$regnumtext             = null;
$limit                  = 20;

$parmsText              = "<p class='label'>\$_GET</p>\n" .
                            "<table class='summary'>\n" .
                            "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    foreach($_GET as $key => $value)
    {               // loop through all input parameters
        $safevalue      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                             "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {           // process specific named parameters
            case 'domain':
            case 'regdomain':
            {
                $value              = str_replace('-','',$value);
                if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                {
                    $domain         = strtoupper($value);
                    $cc             = substr($domain, 0, 2);
                }
                else
                    $domaintext     = $safevalue;
                break;
            }       // Registration Domain

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value, $langtext);
                break;
            }       // process requested language

            case 'regyear':
            {
                if (preg_match('/^[0-9]{4}$/', $value))
                    $regyear        = (int)$value;
                else
                    $regyeartext    = $safevalue;
                break;
            }

            case 'regnum':
            {
                if (ctype_digit($value))
                    $regnum         = (int)$value;
                else
                    $regnumtext     = $safevalue;
                break;
            }

            case 'count':
            {
                if (ctype_digit($value))
                    $limit          = (int)$value;
                break;
            }

            case 'debug':
            case 'userid':
            {
                break;
            }       // handled by common code

            default:
            {
                $warn   .= "<p>Unexpected parameter $key='$value'.</p>\n";
                break;
            }       // any other parameters
        }           // process specific named parameters
    }               // loop through all input parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by method=get

// create instance of Template
$template           = new FtTemplate("MarriageRegQuery$lang.html");
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'MarriageRegQuery'));

// validate domain
if (is_string($domaintext))
{               // invalid syntax for Domain
    $msg    .= $template['invalidDomain']->replace('$domain',$domaintext);
}               // invalid syntax for Domain
else
{
    $domainObj          = new Domain(array('domain'     => $domain,
                                           'language'   => $lang));
    $domainName         = $domainObj->getName(1);
    $stateName          = $domainObj->getName(0);
    if ($domainObj->isExisting())
    {
        $cc             = substr($domain, 0, 2);
        $countryObj     = $domainObj->getCountry();
        $countryName    = $countryObj->getName();
    }
    else
    {
        $msg    .= $template['unsupportedDomain']->replace('$domain',$domain);
    }
}

// report errors detected during parameter analysis
if (is_string($regyeartext))
    $msg    .= $template['invalidRegYear']->replace('$regyear',$regyeartext);
if (is_string($regnumtext))
    $msg    .= $template['invalidRegNum']->replace('$regnum',$regnumtext);
if (is_string($langtext))
    $warn    .= $template['invalidLang']->replace('$lang',$langtext);

// global substitutions
$template->set('LANG',          $lang);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('DOMAIN',        $domain);
$template->set('DOMAINNAME',    $domainName);
$template->set('STATENAME',     $stateName);
if ($debug)
    $template->set('DEBUG',     'Y');
else
    $template->set('DEBUG',     'N');
$template->set('REGYEAR',       $regyear);
$template->set('REGNUM',        $regnum);
$template->set('COUNT',         $limit);

// get list of domains for selection list
if (strlen($msg) == 0)
{                       // no errors were detected in parameters
    $getParms               = array('language'  => $lang,
                                    'cc'        => $cc);
    $domains                = new DomainSet($getParms);
    $optionElt              = $template['domain$code'];
    if ($optionElt)
    {
        $optionText         = $optionElt->outerHTML();
        $result             = '';
        foreach($domains as $code => $dom)
        {
            $ttemplate      = new Template($optionText);
            $ttemplate->set('code',         $code);
            $ttemplate->set('state',        $dom['state']);
            if ($code == $domain)
                $ttemplate->set('selected', 'selected="selected"');
            else
                $ttemplate->set('selected', '');
            $ttemplate->set('name',         $dom['name']);
            $result         .= $ttemplate->compile();
        }
        $optionElt->update($result);
    }
    else
        $msg            .= "Cannot find element with id='domain\$code'. ";
}                       // no errors were detected in parameters
else
    $template['distForm']->update(null);

$template->display();
