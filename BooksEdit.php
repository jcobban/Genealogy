<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  BooksEdit.php                                                       *
 *                                                                      *
 *  Display form for editting information about                         *
 *  books for managing record transcriptions.                           *
 *                                                                      *
 *  History:                                                            *
 *      2021/05/16      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/Book.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$getParms           = array();
$pattern            = '';
$lang               = 'en';
$offset             = 0;
$offsettext         = null;
$limit              = 20;
$limittext          = null;
$deleted            = array();

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{           // method='get'
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {           // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // language

            case 'pattern':
            {
                $pattern            = $value;
                $getParms['title']  = $value;
                break;
            }       // pattern match

            case 'offset':
            {
                if (is_numeric($value) || ctype_digit($value))
                    $offset         = $value;
                else
                    $offsettext     = htmlspecialchars($value);
                break;
            }

            case 'limit':
            {
                if (is_numeric($value) || ctype_digit($value))
                    $limit          = $value;
                else
                    $limittext      = htmlspecialchars($value);
                break;
            }
        }       // act on specific parameters
    }           // loop through parameters
    if ($debug)
        $warn   .= $parmsText . "</table>";
}                   // method='get'
else
if (isset($_POST) && count($_POST) > 0)
{                   // when submit button is clicked invoked by method='post'
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
    $book       = null;
    foreach($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        $matches    = array();
        $pres       = preg_match("/^(\w+)([0-9]{10,13})$/", $key, $matches);
        if ($pres)
        {                   // last characters are decimal digits
            $key    = $matches[1];
            $isbn   = $matches[2];
        }                   // last characters are decimal digits
        else
        {
            $isbn   = '';
        }

        switch(strtolower($key))
        {                   // act on specific column titles
            case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }               // language

            case 'isbn':
            {
                if ($book)
                {
                    $count      = $book->save();
                    if ($count > 0)
                        $warn   .= "<p>" . $book->getLastSqlCmd() ."</p>\n";
                }
                $book           = new Book(array('code' => $isbn));
                $messages       = $book->getErrors();
                if (strlen($messages) > 0)
                    $warn       .= "<p>new Book(array('isbn'    => $isbn)) constructor failed $messages</p>\n";
                break;
            }

            case 'title':
            {
                $book->set('title', $value);
                break;
            }

            case 'deletebook':
            {
                $deleted[]          = $isbn;
                break;
            }

            case 'pattern':
            {
                $pattern            = $value;
                $getParms['title']  = $value;
                break;
            }       // pattern match

            case 'offset':
            {
                if (is_numeric($value) || ctype_digit($value))
                    $offset         = $value;
                else
                    $offsettext     = htmlspecialchars($value);
                break;
            }

            case 'limit':
            {
                if (is_numeric($value) || ctype_digit($value))
                    $limit          = $value;
                else
                    $limittext      = htmlspecialchars($value);
                break;
            }

        }           // check supported parameters
    }               // loop through all parameters

    if ($book)
    {
        $count      = $book->save();
        if ($count > 0)
            $warn   .= "<p>" . $book->getLastSqlCmd() . "</p>\n";
    }
    if ($debug)
        $warn   .= $parmsText . "</table>";
}       // when submit button is clicked invoked by method='post'

if (canUser('all'))
    $action     = 'Update';
else
    $action     = 'Display';

$template       = new FtTemplate("Books$action$lang.html");

if (is_string($offsettext))
{
    $text       = $template['offsetIgnored']->outerHTML;
    $warn       .= str_replace('$value', $offsettext, $text);
}
if (is_string($limittext))
{
    $text       = $template['limitIgnored']->outerHTML;
    $warn       .= str_replace('$value', $limittext, $text);
}

// report on books deleted by administrator
$text           = $template['bookDeleted']->outerHTML;
foreach($deleted as $delisbn)
{
    $book       = new Book(array('isbn' => $delisbn));
    $book->delete(false);
    $warn       .= "<p>" . $book->getLastSqlCmd() . "</p>\n";
    $warn       .= str_replace('$delisbn', $delisbn, $text);
}

$template->set('CONTACTTABLE',  'Books');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('lang',          $lang);
$template->set('offset',        $offset);
$template->set('limit',         $limit);
$template->set('PATTERN',       htmlspecialchars($pattern));

if (strlen($msg) == 0)
{           // no errors detected
    $getParms['offset'] = $offset;
    $getParms['limit']  = $limit;
    $books              = new RecordSet('Books', $getParms);

    $info               = $books->getInformation();
    $count              = $info['count'];
    $template->set('totalrows',     $count);
    $template->set('first',         $offset + 1);
    $template->set('last',          min($count, $offset + $limit));
    if ($offset > 0)
        $template->set('npPrev',    "&offset=" . ($offset-$limit) . "&limit=$limit");
    else
        $template->updateTag('prenpprev', null);
    if ($offset < $count - $limit)
        $template->set('npNext',    "&offset=" . ($offset+$limit) . "&limit=$limit");
    else
        $template->updateTag('prenpnext', null);
    $template->updateTag('Row$isbn',
                         $books);
}           // no errors detected
else
    $template->updateTag('bookForm',
                         null);

$template->display();
