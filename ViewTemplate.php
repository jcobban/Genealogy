<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ViewTemplate.php													*
 *																		*
 *  Display the contents and behavior of a template file.               *
 *																		*
 *	Parameters (passed by method=get):                                  *
 *	    template        path to the template file within the common     *
 *	                    templates directyory.                           *
 *	                    The filetype (e.g. .html) may be omitted        *
 *	                    If "adlang=Y" is specified the language is      *
 *	                    omitted                                         *
 *	    addLang         specify 'Y' to indicate that the value of       *
 *	                    template does not include the language          *
 *	    lang            user's preferred language of communication      *
 *																		*
 *  History:															*
 *		2019/04/16		created											*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$lang		                = 'en';
$templateName               = '';
$addLang                    = false;

// initial invocation by method='get'
if (isset($_GET) && count($_GET) > 0)
{			// method='get'
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {
			case 'lang':
			{
			    if (strlen($value) >= 2)
					$lang		= strtolower(substr($value, 0, 2));
			    break;
			}		// language

			case 'addlang':
			{
                if (strlen($value) > 0 && 
                    strtolower(substr($value,0,1)) == 'y')
					$addLang    = true;
			    break;
			}		// language

			case 'template':
			{
                $templateName	= $value;
			    break;
			}		// pattern match

	    }			// act on specific parameters
	}			// loop through parameters
	if ($debug)
	    $warn   	    .= $parmstext . "</table>\n";
}				// method='get'

$template		        = new FtTemplate("ViewTemplate$lang.html");

$period                 = strpos($templateName, '.');
if ($period === false)
{
    if ($addLang)
        $templateName   .= $lang;
    $period             = strlen($templateName);
    $templateName       .= '.html';
}
else
if ($addLang)
    $templateName       = substr($templateName, 0, $period) . $lang .
                          substr($templateName, $period);

$template->set('TEMPLATENAME',		 $templateName);
$template->set('CONTACTTABLE',	    'Templates');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',              $lang);

$tempBase		        = $document_root . '/templates/';
if (file_exists($tempBase . $templateName))
    $text               = file_get_contents($tempBase . $templateName);
else
{
    $templateName       = substr($templateName, 0, $period - 2) . 'en' .
                          substr($templateName, $period);
    if (file_exists($tempBase . $templateName))
        $text           = file_get_contents($tempBase . $templateName);
    else
        $text           = '<p class="error">File name "' .
                          $tempBase . $templateName . "\" not found</p>\n";
}

if (strtoupper(substr($text, 0, 9)) == '<!DOCTYPE')
{                       // master template
    $ib             = strpos($text, '<body');
    $ib             = strpos($text, '>', $ib);
    $ib++;
    $ie             = strpos($text, '</body>', $ib);
    if ($ie)
        $temp       = substr($text, $ib, $ie - $ib);
    else
        $temp       = substr($text, $ib);
}                       // master template
else
    $temp           = $text;
$temp       = str_replace('$TRACE', '<div class="warning">$TRACE</div>', $temp);
$temp       = str_replace('$MSG',   '<p class="error">$MSG</p>', $temp);
$template->set('TEMPLATE',          $temp);

// adjust tabs
$start              = 0;
$line               = 0;
$col                = 0;
$temp               = '';
while($start < strlen($text))
{
    $l              = strcspn($text, "\t\n", $start);
    $temp           .= substr($text, $start, $l);
    $start          += $l;
    $col            += $l;
    if ($start >= strlen($text))
        break;
    $c              = substr($text, $start, 1);
    if ($c == "\n")
    {                   // end of line
        $temp       .= $c;
        $start++;
        $line       = $start;
        $col        = 0;
    }                   // end of line
    else
    {                   // tab
        //$warn       .= "<p>" . __LINE__ . " '" . substr($temp, -$col);
        $numsp      = 4 - ($col % 4);
        $temp       .= substr('    ', 0, $numsp);
        $col        += $numsp;
        $start++;
    }                   // tab
}
$text               = $temp;

// highlight 
$html               = '';
$start              = 0;
while(($lt = strpos($text, '<', $start)) !== false)
{
    $temp           = substr($text, $start, $lt - $start);
    $temp           = str_replace(' ',
                                  '&nbsp;',
                                  $temp);
    $temp           = str_replace("\t",
                                  '&nbsp;&nbsp;&nbsp;&nbsp;',
                                  $temp);
    $html           .= $temp;
    if (substr($text, $lt, 4) == '<!--')
    {               // start of comment
        $html       .= '<span style="color: green;">&lt!--';
        $lt         += 4;
        $end        = strpos($text, '-->', $lt);
        $temp       = substr($text, $lt, $end - $lt);
        $temp       = str_replace(' ',
                                  '&nbsp;',
                                  $temp);
        $temp       = str_replace("\t",
                                  '&nbsp;&nbsp;&nbsp;&nbsp;',
                                  $temp);
        $temp       = str_replace('<', '&lt;', 
                                   $temp);
        $html       .= $temp .  "--&gt;</span>";
        $start      = $end + 3;
    }
    else
    if (ctype_alpha(substr($text, $lt + 1, 1)))
    {               // start tag
        $html       .= '&lt;<span style="color: purple; font-weight: bold;">';
        $lt++;
        $result     = preg_match('#^\w+#', substr($text, $lt), $matches);
        if ($result == 0)
            $warn   .= "<p>pattern match failed at $lt '" . substr($text, $lt, 10) . "</p>\n";
        $html       .= $matches[0] . "</span><span style=\"font-weight: bold;\">";
        $lt         += strlen($matches[0]);
        $end        = strpos($text, '>', $lt);
        $temp       = substr($text, $lt, $end - $lt);
        $temp       = str_replace(' ',
                                  '&nbsp;',
                                  $temp);
        $temp       = str_replace("\t",
                                  '&nbsp;&nbsp;&nbsp;&nbsp;',
                                  $temp);
        $html       .= $temp . '</span>&gt;';
        $start      = $end + 1;
    }               // start tag
    else
    if (substr($text, $lt + 1, 1) == '/')
    {               // end tag
        $html       .= '&lt;/<span style="color: purple; font-weight: bold;">';
        $lt         += 2;;
        $result     = preg_match('#^\w*#', substr($text, $lt), $matches);
        $html       .= $matches[0] . "</span>";
        $lt         += strlen($matches[0]);
        $end        = strpos($text, '>', $lt);
        $html       .= substr($text, $lt, $end - $lt) . '&gt;';
        $start      = $end + 1;
    }               // end tag
    else
    {
        $html       .= '&lt;';
        $start      = $lt + 1;
    }
}
$html           .= substr($text, $start);

$html           = str_replace("\n",
                              "<br>\n",
                              $html);

$template->set('HTML',          $html);

$template->display();
