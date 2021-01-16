<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genealogy.php                                                       *
 *                                                                      *
 *  This script displays the main entry point to the web site.          *
 *                                                                      *
 *    History:                                                          *
 *      2010/08/23      change to new standard layout                   *
 *      2010/09/24      enable research requests                        *
 *      2010/11/10      add help page                                   *
 *      2010/11/16      add direct links to family tree                 *
 *      2011/07/30      improve separation of HTML and JS               *
 *      2011/10/24      support mouseover help for signon button        *
 *      2012/01/07      refresh invoking page if any                    *
 *      2012/01/14      change to file names                            *
 *      2013/02/23      change recent updates link                      *
 *      2013/04/29      change recent updates link                      *
 *      2013/06/01      change recent updates link                      *
 *      2013/07/30      defer facebook initialization until after load  *
 *                      provide link to July status                     *
 *      2013/08/28      provide link to August status                   *
 *      2013/10/10      provide link to September 2013 status           *
 *      2013/11/07      provide link to October 2013 status             *
 *      2013/12/01      provide link to November 2013 status            *
 *      2013/12/10      move status reports to bottom of page           *
 *      2013/12/28      use CSS for layout                              *
 *      2014/01/01      provide link to December 2013 status            *
 *      2014/03/31      provide link to March 2014 status               *
 *      2014/03/31      provide link to April 2014 status               *
 *      2014/06/30      provide link to June 2014 status                *
 *      2014/07/18      switch to php to automate management of links   *
 *                      to newsletters and functional updates           *
 *      2014/07/28      add link to intro to updating                   *
 *      2014/09/23      display messages from initialization            *
 *      2015/02/14      add document on digital file formats            *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/19      add id to debug trace                           *
 *      2017/02/04      add link to manage countries list               *
 *      2017/10/23      use class Template                              *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/31      ignore unexpected parameters                    *
 *      2018/10/15      get language apology text from Languages        *
 *      2018/11/28      use language specific page layout               *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2019/05/30      use new common translation table indexes        *
 *		2020/10/31      default to browser's preferred language         *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . "/common.inc";

/************************************************************************
 *      open code                                                       *
 ***********************************************************************/
$cc             = 'CA';
$countryName    = 'Canada';
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
    $lang       = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
else
    $lang       = 'en';

// process parameters passed by caller
foreach ($_GET as $key => $value)
{           // loop through all parameters
    switch(strtolower($key))
    {
        case 'lang':
        {       // requested language
            if (strlen($value) >= 2)
                $lang   = strtolower(substr($value, 0, 2));
            break;
        }       // requested language

        case 'debug':
        {       // requested debug
            break;
        }       // requested debug
    }       // switch on parameter name
}           // foreach parameter
$update             = canUser('edit');

$template           = new FtTemplate("genealogy$lang.html");
$trtemplate         = $template->getTranslate();

// create list of newsletters
$names              = array();
$dh                 = opendir('Newsletters');
if ($dh)
{                   // found Newsletters directory
    while (($filename = readdir($dh)) !== false)
    {               // loop through files
        if (strlen($filename) > 4 &&
            substr($filename, strlen($filename) - 4) == '.pdf')
            $names[]    = $filename;
    }               // loop through files
    rsort($names);
}                   // found Newsletters directory

$monthnames         = $trtemplate->getElementById('Months');

$newsletters        = array();
for ($i = 0; $i < min(count($names),5); $i++)
{                   // loop through newsletters in order
    $filename       = $names[$i];
    $y              = substr($filename,10,4);
    $m              = substr($filename,15,2);
    $month          = $monthnames[$m - 0];
    $newsletters[]  = array('filename'  => $filename,
                            'mm'        => $m,
                            'month'     => $month,
                            'year'      => $y);
}                   // loop through newsletters in order
$template->updateTag('newsletter$mm$year',
                     $newsletters);

// create list of reports
$names              = array();
$dh                 = opendir('MonthlyUpdates');
if ($dh)
{                   // found MonthlyUpdates directory
    while (($filename = readdir($dh)) !== false)
    {               // loop through files
        if (strlen($filename) > 4 &&
            substr($filename, strlen($filename) - 4) == '.pdf')
            $names[]    = $filename;
    }               // loop through files
    rsort($names);
}                   // found Newsletters directory

$reports            = array();
for ($i = 0; $i < min(count($names),5); $i++)
{                   // loop through reports in order
    $filename       = $names[$i];
    $y              = substr($filename,6,4);
    $m              = substr($filename,11,2);
    $month          = $monthnames[$m - 0];
    $reports[]      = array('filename'      => $filename,
                            'mm'        => $m,
                            'month'     => $month,
                            'year'      => $y);
}                   // loop through reports in order
$template->updateTag('report$mm$year',
                     $reports);
$template->display();
