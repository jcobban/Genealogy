<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DomainsEdit.php                                                     *
 *                                                                      *
 *  Display form for editting information about administrative          *
 *  domains for managing vital statistics records.                      *
 *                                                                      *
 *  Parameters (passed by method=get):                                  *
 *      cc      2 letter country code                                   *
 *                                                                      *
 *  History:                                                            *
 *      2016/05/20      created                                         *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/02/07      use class Country                               *
 *      2017/08/13      correct display of country name                 *
 *                      add header link to services menu                *
 *      2017/12/05      correct order of options in language selection  *
 *      2018/01/04      remove Template from template file names        *
 *      2018/02/02      page through results if more than limit         *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/21      use new FtTemplate constructor                  *
 *                      support ISO presenationof domain codes          *
 *      2019/12/02      if partof is not requested return only          *
 *                      the top level domains of a country              *
 *      2019/12/05      separate processing of $_GET and $_POST         *
 *                      add support for Category                        *
 *      2021/01/13      correct XSS vulnerabilities                     *
 *                      improve parameter validation                    *
 *                      get message texts from template                 *
 *      2021/03/29      correct handling of partof                      *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *      2022/03/09      avoid Creating default object from empty value  *
 *                      issue warning for unsupported language          *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/DomainSet.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc                         = 'CA';
$cctext                     = null;
$partof                     = null;
$partoftext                 = null;
$countryName                = 'Canada';
$domainType                 = 'Province';
$lang                       = 'en';
$langtext                   = null;
$offset                     = 0;
$offsettext                 = null;
$limit                      = 20;
$limittext                  = null;
$parmsDebug                 = '';
$newCountry                 = false;

if (isset($_GET) && count($_GET) > 0)
{                       // invoked by method=get
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                                  "<table class='summary'>\n" .
                                    "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                   // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n";
        $key                = strtolower($key);
        $value              = trim($value);

        if (strlen($value) > 0)
        switch($key)
        {               // check supported parameters
            case 'cc':
            {
                if (preg_match('/^[a-zA-Z]{2}$/', $value) == 1)
                    $cc             = strtoupper($value);
                else
                    $cctext         = $safevalue;
                break;
            }           // country code
    
            case 'partof':
            {
                if (preg_match('/^[a-zA-Z]{2}(-|)[a-zA-Z]{2,4}$/', $value) == 1)
                    $partof         = strtoupper($value);
                else
                    $partoftext     = $safevalue;
                break;
            }           // country code
    
            case 'lang':
            case 'language':
            {
                $lang               = FtTemplate::validateLang($value, $langtext);
                break;
            }           // language code
    
            case 'offset':
            {
                if (ctype_digit($value))
                    $offset         = $value;
                else
                    $offsettext     = $safevalue;
                break;
            }
    
            case 'limit':
            {
                if (ctype_digit($value))
                    $limit          = $value;
                else
                    $limittext      = $safevalue;
                break;
            }
    
        }               // check supported parameters
    }                   // loop through all parameters
    if ($debug)
    {                   // ensure listing of parameters not interrupted
        $warn   .= $parmsText . "  </table>\n";
    }                   // ensure listing of parameters not interrupted
}                       // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{                       // invoked by method=post
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                          "<table class='summary'>\n" .
                            "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {
        $safevalue                  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                           "<td class='white left'>$safevalue</td></tr>\n"; 
        $matches                    = array();
        if (strlen($key) > 4 && preg_match("/[A-Z\-]+$/", $key, $matches))
        {
            $code                   = $matches[0];
            $key                    = strtolower(
                            substr($key, 0, strlen($key) - strlen($code)));
            if ($key == 'lang')
                $key                = 'rowlang';
        }
        else
        {
            $code                   = '';
            $key                    = strtolower($key);
        }

        $value                      = trim($value);

        if (strlen($value) > 0)
        switch($key)
        {                   // act on column identifier
            case 'cc':
            {               // country code
                if (preg_match('/^[a-zA-Z]{2}$/', $value) == 1)
                    $cc             = strtoupper($value);
                else
                    $cctext         = $safevalue;
                break;
            }               // country code
    
            case 'lang':
            case 'language':
            {               // language code
                $lang               = FtTemplate::validateLang($value, $langtext);
                break;
            }               // language code
    
            case 'code':
            {               // record identifier, domain code
                if (preg_match('/^[a-zA-Z]{2,3}$/', $value) == 1)
                {           // valid entry
                    $value          = strtoupper($value);
                    $newCode        = "$cc$value";
                    $domain         = new Domain(array('domain'     => $code,
                                                       'language'   => $lang));
                    if ($newCode != $code)
                    {       // user has changed the code
                        $chkdomain  = new Domain(array('domain'     => $newCode,
                                                       'language'   => $lang));
                        if ($chkdomain->isExisting())
                        {   // duplicates existing record
                            $warn   .= "<p>You cannot change the code from '$code' to '$newCode' because that value is already in use.</p>\n";
                        }   // duplicates existing record
                        else
                        {   // change the code
                            $domain->set('domain', $newCode);
                        }   // change the code
                    }       // user has changed the code
                }           // new value is valid
                else
                    $cctext         = $safevalue;
                break;
            }               // record identifier, domain code
    
            case 'rowlang':
            {
                $rowlang            = $safevalue;
                break;
            }
    
            case 'name':
            {           // name of domain
                if ($newCountry || $rowlang != $lang)
                    break;
                if (strlen($value) == 0)
                {
                    $domain->delete(false);
                }
                else
                {
                    $domain->set('name', $safevalue);
                }
                break;
            }           // name of domain
    
            case 'partof':
            {
                $partof             = strtoupper($safevalue);
                $sep                = substr($partof, 2, 1);
                if (strlen($partof) > 2 && substr($partof, 2, 1) != '-')
                {
                    $partof         = substr($partof, 0, 2) . '-' .
                                      substr($partof, 2);
                }
                $domain->set('partof', $partof);
                break;
            }           // part of
    
            case 'category':
            {
                $domain->set('category', $safevalue);
                $domain->save();
                break;
            }           // category
    
            case 'resourcesurl':
            {           // link to other info
                if ($newCountry || $rowlang != $lang)
                    break;
                $domain->set('resourcesurl', $safevalue);
                $domain->save();
                break;
            }           // link to other info
    
            case 'offset':
            {
                if (ctype_digit($value))
                    $offset         = $value;
                else
                    $offsettext     = $safevalue;
                break;
            }
    
            case 'limit':
            {
                if (ctype_digit($value))
                    $limit          = $value;
                else
                    $limittext      = $safevalue;
                break;
            }
    
        }               // check supported parameters
    }                   // loop through all parameters
    if ($debug)
    {                   // ensure listing of parameters not interrupted
        $warn               .= $parmsText . "  </table>\n";
    }                   // ensure listing of parameters not interrupted
}                       // invoked by method=post

if (canUser('edit'))
    $action             = 'Update';
else
    $action             = 'Display';

$tempBase               = $document_root . '/templates/';
$includeSub             = "DomainsEdit$action$cc$lang.html";
if (!file_exists($tempBase . "DomainsEdit$action{$cc}en.html"))
{                           // country code not supported
    $includeSub         = "DomainsEdit{$action}CA$lang.html";      
}                           // country code not supported
$template               = new FtTemplate($includeSub);
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];

if (is_string($cctext))
{
    $text               = $template['countryInvalid']->innerHTML;
    $msg                .= str_replace('$cc', $cctext, $text);
}
if (is_string($partoftext))
{
    $text               = $template['partofInvalid']->innerHTML;
    $msg                .= str_replace('$partof', $partoftext, $text);
}
if (is_string($langtext))
    $warn   .= $template['languageInvalid']->replace('$lang', $langtext);
if (is_string($offsettext))
    $warn   .= $template['offsetIgnored']->replace('$offset', $offsettext);
if (is_string($limittext))
    $warn   .= $template['limitIgnored']->replace('$limit', $limittext);

$country                = new Country(array('code' => $cc));
if (!$country->isExisting())
{
    $text               = $template['countryInvalid']->innerHTML;
    $msg                .= str_replace('$cc', $cc, $text);
}

if ($partof)
{
    $domain                     = new Domain(array('domain'     => $partof,
                                                   'language'   => $lang));
    $countryName                = $domain->getName(1);
}
else
    $countryName                = $country->getName($lang);

if ($partof)
    $domainType                 = 'County';
else
if ($cc != 'CA')
    $domainType                 = 'State';


if (strlen($msg) == 0)
{           // no errors detected
    // create an array of language information for select <options>
    $languageSet                = new RecordSet('Languages');
    if ($languageSet->offsetExists($lang))
        $language               = $languageSet[$lang];
    else
    {
        $warn   .= $template['languageUnsupported']->replace('$lang',$lang);
        $language               = $languageSet['en'];
    }
    if ($language)
        $language->selected     = true;

    // get the set of administrative domains for the country
    $getParms                   = array('cc'        => $cc,
                                        'language'  => $lang,
                                        'order'     => 'Name',
                                        'offset'    => $offset,
                                        'limit'     => $limit);
    if ($partof)
    {
        if ($partof == 'GB-IRE')
            $getParms['partof']     = 'GB-NIR';
        else
            $getParms['partof']     = $partof;
    }
    $domains                    = new DomainSet($getParms);
    $information                = $domains->getInformation();
    $totcount                   = $information['count'];
    $count                      = $domains->count();

    if ($totcount == 0 && strtolower($lang) != 'en')
    {       // get domains in default language
        $getParms               = array('cc'        => $cc,
                                        'order'     => 'Name',
                                        'offset'    => $offset,
                                        'limit'     => $limit);
        if ($partof)
        {
            if ($partof == 'GB-IRE')
                $getParms['partof']     = 'GB-NIR';
            else
                $getParms['partof']     = $partof;
        }
        $domains                = new DomainSet($getParms);
        foreach($domains as $domain)
            $domain->set('lang', $lang);
        $information            = $domains->getInformation();
        $totcount               = $information['count'];
        $count                  = $domains->count();
    }       // get domains in default language

    if ($totcount == 0)
    {       // no existing defined domains
        $docodes                = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $totcount               = min(26, $limit);
        $count                  = $totcount;
        for($do = 0; $do < $totcount; $do++)
        {
            $code               = 'A' . substr($docodes,$do,1);
            $domains[$code]     = new Domain(array('domain'     => "$cc-$code",
                                                   'language'   => $lang,
                                                   'name'       => $code));
            $domains[$code]->set('lang', $lang);
        }
    }       // no existing defined domains
}           // no errors detected
else
{
    $domains                    = array();
}

$template->set('CONTACTTABLE',      'Domains');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
if ($partof)
    $template->set('PARTOF',        $partof);
else
    $template->set('PARTOF',        '');
$template->set('DOMAINTYPE',        $t[$domainType]);
$template->set('DOMAINTYPEPLURAL',  $t[$domainType . 's']);
$template->set('OFFSET',            $offset);
$template->set('LIMIT',             $limit);
if (is_null($partof))
    $partof             = '';

if (strlen($msg) == 0)
{
    $template->updateTag('languageOpt',
                         $languageSet);

    if (($offset - $limit) >= 0)
        $template->updateTag('topPrev',
                             array('cc'     => $cc,
                                   'lang'   => $lang,
                                   'partof' => $partof,
                                   'offset' => $offset - $limit,
                                   'limit'  => $limit));
    else
        $template->updateTag('topPrev', null);
            
    if (($offset + $limit) < $totcount)
        $template->updateTag('topNext',
                             array('cc'     => $cc,
                                   'lang'   => $lang,
                                   'partof' => $partof,
                                   'offset' => $offset + $limit,
                                   'limit'  => $limit));
    else
        $template->updateTag('topNext', null);
    
    $template->updateTag('respdescrows',
                         array('first'      => $offset + 1,
                               'last'       => min($totcount, $offset+$limit),
                               'totalrows'  => $totcount));
    
    $template->updateTag('Row$code',
                         $domains);
}
else
{
    $template->updateTag('topBrowse',
                         null);
    $template->updateTag('domainForm',
                         null);
}

$template->display();
