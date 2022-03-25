<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  EditCensuses.php                                                    *
 *                                                                      *
 *  Display form for editting information about a Census                *
 *                                                                      *
 *  History:                                                            *
 *      2016/01/21      created                                         *
 *      2017/09/12      use get( and set(                               *
 *      2018/01/12      use class Template                              *
 *      2019/02/19      use new FtTemplate constructor                  *
 *                      add support for multiple countries              *
 *                      Delete requested by name='Delete'               *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *      2022/02/18      validate parameters and issue safe messages     *
 *                      internationalize messages                       *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/CensusSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang                   = 'en';
$langtext               = null;
$cc                     = 'CA';
$cctext                 = null;
$offset                 = 0;
$offsettext             = null;
$limit                  = 20;
$limittext              = null;
$getParms               = array();          // default all Censuses

if (isset($_GET) && count($_GET) > 0)
{           // method=get invoked from URL
    $census             = null;
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $name => $value)
    {       // loop through parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$name</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($name))
        {       // act on parameter name
            case 'cc':
            {
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                    $cc             = strtoupper($value);
                else
                    $cctext         = $safevalue;
                break;
            }   // country code

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value,
                                                               $langtext);
                break;
            }   // language code

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
        }       // act on parameter name
    }           // loop through parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

}               // method=get
else
if (isset($_POST) && count($_POST) > 0)
{               // method=post, update
    $census                 = null;
    $parmsText              = "<p class='label'>\$_POST</p>\n" .
                              "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
    foreach ($_POST as $name => $value)
    {       // loop through parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText          .= "<tr><th class='detlabel'>$name</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n";
        if (preg_match("/^([a-zA-Z_]+)(\d*)$/",
                       $name,
                       $matches) == 1)
            $column                 = strtolower($matches[1]);
        else
            $column                 = strtolower($name);

        switch($column)
        {       // act on column name
            case 'censusid':
            {
                if ($census instanceof Census)
                {
                    if (strtolower($census['name']) == 'delete')
                        $census->delete(false);
                    else
                        $census->save();
                }
                if (strlen($value) >= 6)
                    $census     = new Census(array('censusid'   => $value));
                else
                    $census             = null;
                break;
            }

            case 'name':
            case 'linesperpage':
            case 'grouplines':
            case 'lastunderline':
            case 'idsr':
            {
                $census->set($column, $value);
                break;
            }

            case 'collective':
            {
                if (strtoupper($value) == 'Y')
                    $value          = 1;
                else
                    $value          = 0;
                $census->set('collective', $value);
                break;
            }

            case 'partof':
            {
                if (strlen($value) >= 2)
                    $census->set('partof', strtoupper($value));
                else
                    $census->set('partof', null);
                break;
            }

            case 'provinces':
            {
                $census->set('provinces',
                             strtoupper(str_replace(',','',$value)));
                break;
            }

            case 'cc':
            {
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                    $cc                 = strtoupper($value);
                else
                    $cctext             = $safevalue;
                break;
            }   // country code

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value,
                                                               $langtext);
                break;
            }

            case 'offset':
            {
                if (ctype_digit($value))
                    $offset             = $value;
                else
                    $offsettext         = $safevalue;
                break;
            }

            case 'limit':
            {
                if (ctype_digit($value))
                    $limit              = $value;
                else
                    $limittext          = $safevalue;
                break;
            }
        }       // act on column name
    }           // loop through parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}               // update

// create the template
$update                     = canUser('admin');
if ($update)
    $action                 = 'Update';
else
    $action                 = 'Display';

$template                   = new FtTemplate("EditCensuses$action$lang.html");
$translate                  = $template->getTranslate();
if ($translate)
    $t                      = $translate['tranTab'];
else
    print "<p>\$translate is null</p>\n";
if ($t === null)
    print "<p>\$t is null</p>\n";

// report errors detected by examining parameters
if (is_string($cctext))
{
    $msg        .= $template['ccInvalid']->replace('$cctext', $cctext);
    $unknown    = $t['Unknown'];
    $template->set('COUNTRYNAME',       $t['Unknown']);
}
if (is_string($offsettext))
    $warn       .= $template['offsetIgn']->replace('$offsettext', $offsettext);
if (is_string($limittext))
    $warn       .= $template['limitIgn']->replace('$limittext', $limittext);
if (is_string($langtext))
    $warn       .= $template['langIgn']->replace(array('$langtext',
                                                        '$lang'),
                                                  array($langtext,
                                                        $lang));

if (strlen($msg) == 0)
{                       // no errors detected in parameters
    // get the censuses in the correct order
    $getParms               = array('cc'        => $cc,
                                    'offset'    => $offset,
                                    'limit'     => $limit);
    $censuses               = new CensusSet($getParms);
    $info                   = $censuses->getInformation();
    $total                  = $info['count'];
    $count                  = $censuses->count();
    $template->set('OFFSET',    $offset);
    $template->set('LIMIT',     $limit);
    $template->set('TOTAL',     $total);
    $template->set('FIRST',     $offset+1);
    $template->set('LAST',      $offset + $count);
    if ($offset == 0)
        $template['topPrev']->update(null);
    else
    {
        $npPrev         = "EditCensuses.php?cc=$cc&limit=$limit&offset=" .
                            ($offset - $limit);
        if ($debug)
            $npPrev         .= "&debug=Y";
        $template->set('NPPREV',        $npPrev);
    }
    $last                   = $offset + $limit;
    if ($last >= $total)
    $template['topNext']->update(null);
    else
    {
        $npNext         = "EditCensuses.php?cc=$cc&limit=$limit&offset=" .
                            ($offset + $limit);
        if ($debug)
            $npNext         .= "&debug=Y";
        $template->set('NPNEXT',        $npNext);
    }

    $line                   = '01';
    foreach($censuses as $census)
    {
        $census->set('line', $line);
        if (is_null($census->get('partof')))
            $census->set('partof', '');
        if (is_null($census->get('idsr')))
            $census->set('idsr', '');
        if ($census->get('collective') == 0)
            $census->set('collective', '');
        else
            $census->set('collective', 'Y');
        $line++;
        if (strlen($line) == 1)
            $line           = '0' . $line;
    }

    $title                  = "Table of Censuses";
    $template->set('CC',                $cc);
    $country                = new Country(array('code'  => $cc));
    $template->set('COUNTRYNAME',       $country->getName($lang));
    $template->set('LANG',              $lang);
    $template->set('CONTACTTABLE',      'Censuses');
    $template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
    $template->updateTag('Row$line',
                         $censuses);
}                       // no errors detected in parameters
else
{
    $topBrowse         = $template['topBrowse'];
    if ($topBrowse)
        $topBrowse->update();
    else
        $msg    .= "Cannot find element id='topBrowse' in template=EditCensuses$action$lang.html'";
    $censusForm         = $template['censusForm'];
    if ($censusForm)
        $censusForm->update();
    else
        $msg    .= "Cannot find element id='CensusForm' in template=EditCensuses$action$lang.html'";
}

$template->display();
showTrace(); 
