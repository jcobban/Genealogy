<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\TemplateTag;

/************************************************************************
 *  Blogs.php                                                           *
 *                                                                      *
 *  This script provides a common interface for account administration  *
 *  for an authorized user of the web site.                             *
 *                                                                      *
 *  History:                                                            *
 *      2018/09/15      Created                                         *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2019/05/17      exploit TemplateTag support for subscripts      *
 *                      do not set names set by class FtTemplate        *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// common
$lang               = 'en';

if (isset($_REQUEST) && count($_REQUEST) > 0)
{                   // invoked by URL to display current status of account
    foreach($_REQUEST as $key => $value)
    {
        switch(strtolower($key))
        {
            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
        }
    }
}                   // invoked by URL to display current status of account

// get template for displaying page
$template           = new FtTemplate("Blogs$lang.html");

// internationalization support
$trtemplate         = $template->getTranslate();
$months             = $trtemplate->getElementById('Months');
$lmonths            = $trtemplate->getElementById('LMonths');

// get top level Blog posts
$blogParms          = array('keyvalue'  => 0,
                            'table'     => 'Blogs');
$bloglist           = new RecordSet('Blogs', $blogParms);
$blogCount          = $bloglist->count();

foreach($bloglist as $blog)
{
    $datetime       = $blog->get('datetime');
    $matches        = array();
    if (preg_match('/^(\d+)-(\d+)-(\d+) *(.*)$/', $datetime, $matches) == 1)
    {
        $blog->set('year',      $matches[1]);
        $blog->set('month',     $months[$matches[2] - 0]);
        $blog->set('lmonth',    $lmonths[$matches[2] - 0]);
        $blog->set('day',       $matches[3]);
        $blog->set('time',      $matches[4]);
    }
    else
    {
        $blog->set('year',      '');
        $blog->set('month',     '');
        $blog->set('time',      '');
        $blog->set('lmonth',    '');
        $blog->set('time',      $datetime);
    }
}

$template->set('CONTACTTABLE',      'Blogs');
$template->set('CONTACTKEYVALUE',   0);

// display existing blog entries
$template->updateTag('blog$blid',
                     $bloglist);
$template->display();
