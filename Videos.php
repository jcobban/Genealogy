<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  VideosEdit.php                                                      *
 *                                                                      *
 *  Display form for editting information about tutorial videos.        *
 *                                                                      *
 *  History:                                                            *
 *      2018/02/01      created                                         *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2021/01/03      correct XSS vulnerability                       *
 *      2023/01/10      there is no table Videos                        *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$getParms               = array();
$pattern                = '';
$lang                   = 'en';
$offset                 = 0;
$limit                  = 20;

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{           // method='get'
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {           // loop through parameters
        $safevalue                  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }       // language

            case 'pattern':
            {
                $pattern            = $value;
                if (strlen($value) > 0)
                    $getParms['filename']   = $value;
                break;
            }       // pattern match

            case 'offset':
            {
                if (is_numeric($value) || ctype_digit($value))
                    $offset     = $value;
                break;
            }

            case 'limit':
            {
                if (is_numeric($value) || ctype_digit($value))
                    $limit      = $value;
                break;
            }
        }           // act on specific parameters
    }           // loop through parameters
    if ($debug)
        $warn       .= $parmstext . "</table>\n";
}               // method='get'
else
if (isset($_POST) && count($_POST) > 0)
{       // when submit button is clicked invoked by method='post'
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    $video          = null;
    foreach($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" . 
                            htmlspecialchars($value) . "</td></tr>\n"; 
        $fieldLc        = strtolower($key);
        $matches        = array();
        if (preg_match('/^([a-zA-Z]+)(\d+)$/', $fieldLc, $matches))
        {
            $column     = $matches[1];
            $row        = $matches[2];
        }
        else
        {
            $column     = $fieldLc;
            $row        = '';
        }

        switch($column)
        {
            case 'pattern':
                $pattern            = $value;
                if (strlen($value) > 0)
                    $getParms['filename']   = $value;
                break;

            case 'filename':
                $filename       = $value;
                break;

            case 'lang':
                $lang           = FtTemplate::validateLang($value);
                if (strlen($row) > 0)
                {
//                  $video      = new Record(array('filename'   => $filename,
//                                                 'lang'       => $lang),
//                                           'Videos');
                }
                break;

            case 'description':
                if ($video)
                    $video->set('description', $value);
                break;

            case 'display':
                if ($video)
                {
                    if ($value === 0 || $value === '0' || 
                        strtoupper($value[0]) == 'N')
                        $video->set('display', 0);
                    else
                        $video->set('display', 1);
                }
                break;

            case 'delete':
                if ($video)
                    if ($value == 'Y')
                        $video->delete(false);
                break;

            case 'offset':
                if (is_numeric($value) || ctype_digit($value))
                    $offset     = $value;
                break;

            case 'limit':
                if (is_numeric($value) || ctype_digit($value))
                    $limit      = $value;
                break;

            case 'debug':
                break;

        }           // check supported parameters
    }               // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}               // when submit button is clicked invoked by method='post'

if (strlen($msg) == 0)
{                   // no errors detected
    $getParms['offset'] = $offset;
    $getParms['limit']  = $limit;
    $handle             = opendir('Videos');
    $videos             = array();
    $row                = 1;
    $even               = 'odd';
    while($filename = readdir($handle))
    {               // loop through files in directory
        if (substr($filename, 0, 1) == '.')
            continue;
        if (preg_match('/(.*)(\.\w+)$/', $filename, $matches))
            $description        = $matches[1];
        else
            $description        = $filename;
        $description            = preg_replace('/([a-z])([A-Z])/',
                                               '\1 \2',
                                               $description);
        $videos[$row]   = array("row"           => $row,
                                "even"          => $even,
                                "filename"      => $filename,
                                "description"   => $description,
                                "display"       => 'N'); 
        if ($even == 'odd')
            $even       = 'even';
        else
            $even       = 'odd';
        $row++;
    }               // loop through files in directory
}                   // no errors detected

if (canUser('edit'))
{
    $action     = 'Edit';
}
else
{
    $action     = 'Display';
}

$template       = new FtTemplate("Videos$action$lang.html");

$template->set('PATTERN',        htmlspecialchars($pattern));
$template->set('CONTACTTABLE',  'Videos');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',          $lang);
$template->set('OFFSET',        $offset);
$template->set('LIMIT',         $limit);
$count              = count($videos);
$template->set('COUNT',         $count);
$template->set('FIRST',         $offset + 1);
$template->set('LAST',          min($count, $offset + $limit));
if ($offset > 0)
    $template->updateTag('topPrev',
                         array("offset"     => ($offset - $limit),
                               "limit"      => $limit,
                               "lang"       => $lang,
                               "pattern"    => $pattern));
else
    $template->updateTag('topPrev', '&nbsp;');
if ($offset < $count - $limit)
    $template->updateTag('topNext',
                         array("offset"     => ($offset + $limit),
                               "limit"      => $limit,
                               "lang"       => $lang,
                               "pattern"    => $pattern));
else
    $template->updateTag('topNext', '&nbsp;');
$template->updateTag('mousetopPrev',
                     array('prevoffset' => ($offset - $limit)));
$template->updateTag('mousetopNext',
                     array('nextoffset' => ($offset + $limit)));

$template->updateTag('video$row',
                     $videos);
$template->display();
