<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  BirthRegQuery.php                                                   *
 *                                                                      *
 *  Generalized dialog for querying the table of birth registrations    *
 *  for any province.                                                   *
 *                                                                      *
 *  History:                                                            *
 *      2014/12/18      created                                         *
 *      2015/01/23      misspelled variable $domainname                 *
 *                      uninitialized variable $domain                  *
 *      2015/01/26      add birth date range field                      *
 *      2015/05/11      location of help page changed                   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/17      display debug trace                             *
 *                      pass debug option to search scripts             *
 *      2016/01/19      add id to debug trace                           *
 *      2016/05/20      use class Domain to validate domain code        *
 *      2017/02/07      use class Country                               *
 *      2018/10/05      use class Template                              *
 *      2019/02/21      use new FtTemplate constructor                  *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/11/28      correct XSS error                               *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *      2022/03/09      improve parameter checking                      *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
    require_once __NAMESPACE__ . "/Domain.inc";
    require_once __NAMESPACE__ . "/Country.inc";
    require_once __NAMESPACE__ . "/Language.inc";
    require_once __NAMESPACE__ . "/FtTemplate.inc";
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *                                                                      *
 *  Open code.                                                          *
 *                                                                      *
 ************************************************************************/

// validate all parameters passed to the server script
$code                   = 'ON';
$codetext               = null;
$cc                     = 'CA';
$domain                 = 'CAON';
$domaintext             = null;
$countryName            = 'Canada';
$domainName             = 'Canada: Ontario';
$stateName              = 'Ontario';
$lang                   = 'en';
$langtext               = null;

if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                              "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {               // loop through all parameters
        $safevalue      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                           "<td class='white left'>$safevalue</td></tr>\n";
        switch(strtolower($key))
        {           // act on specific parameters
            case 'code':
            {       // state postal abbreviation
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value))
                {
                    $code           = $value;
                    $cc             = 'CA';
                    $domain         = 'CA' . $code;
                }
                else
                {
                    $codetext       = $safevalue;
                }
                break;
            }       // state postal abbreviation
    
            case 'domain':
            case 'regdomain':
            {       // state postal abbreviation
                if (preg_match('/^[a-zA-Z]{4,5}$/', $value))
                {
                    $domain         = $value;
                    $cc             = substr($domain, 0, 2);
                    if ($cc == 'UK')
                        $cc         = 'GB';
                }
                else
                {
                    $domaintext     = $safevalue;
                }
                break;
            }       // state postal abbreviation
    
            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value, $langtext);
                break;
            }
        }           // act on specific parameters
    }               // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by method=get

$template               = new FtTemplate("BirthRegQuery$lang.html");
$template->updateTag('otherStylesheets',    
                     array('filename'   => 'BirthRegQuery'));

if (is_string($codetext))
    $warn       .= $template['invalidCode']->replace('$code', $codetext);
if (is_string($domaintext))
    $warn       .= $template['invalidDomain']->replace('$domain', $domaintext);
if (is_string($langtext))
    $warn       .= $template['invalidLang']->replace('$lang', $langtext);

$domainObj              = new Domain(array('domain'     => $domain,
                                           'language'   => $lang));
if ($domainObj->isExisting())
    $template['unsupportedDomain']->update(null);
$countryObj             = new Country(array('code' => $cc));
if ($countryObj->isExisting())
    $template['unsupportedCountry']->update(null);

$countryName            = $countryObj->getName();
$domainName             = $domainObj->getName(1);
$stateName              = $domainObj->getName(0);
$code                   = substr($domain, 2, 2);

$template->set('COUNTRYNAME',       $countryName);
$template->set('CC',                $cc);
$template->set('DOMAINNAME',        $domainName);
$template->set('DOMAIN',            $domain);
$template->set('STATENAME',         $stateName);
$template->set('LANG',              $lang);
$template->set('CONTACTTABLE',      'Births');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
if ($debug)
    $template->set('DEBUG',         'Y');
else
    $template->set('DEBUG',         'N');

$template->display();
