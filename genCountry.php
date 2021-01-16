<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genCountry.php                                                      *
 *                                                                      *
 *  Display the home page of a country.                                 *
 *                                                                      *
 *  History:                                                            *
 *      2017/10/23      created                                         *
 *      2018/01/04      remove Template from template file names        *
 *      2018/01/25      common functionality moved to class FtTemplate  *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/18      use new FtTemplate constructor                  *
 *		2021/01/03      correct XSS vulnerability                       *
 *                                                                      *
 *  Copyright &copy; 2021 James Alan Cobban                             *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *      open code                                                       *
 ***********************************************************************/
$cc                         = 'CA';
$cctext                     = null;
$countryName                = 'Canada';
$lang                       = 'en';     // default english

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                   // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'cc':
            case 'code':
            case 'countrycode':
            {
                if (strlen($value) == 2)
                {
                    $cc             = strtoupper($value);
                    if ($cc == 'UK')
                        $cc         = 'GB';
                }
                else
                    $cctext         = htmlspecialchars($value);
                break;
            }

            case 'lang':
           {            // language code
                $lang           = FtTemplate::validateLang($value);
                break;
            }           // language code

            default:
            {           // unexpected
                $warn   .= "<p>Unexpected parameter $key=" . 
                            htmlspecialchars($value) . ".</p>";
                break;
            }           // unexpected
        }               // switch on parameter name
    }                   // foreach parameter
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

$countryObj         = new Country(array('code' => $cc));
$countryName        = $countryObj->getName($lang);

$tempBase           = $document_root . '/templates/';
$baseName           = "genCountry{$cc}en.html";
if (file_exists($tempBase . $baseName))
    $includeSub     = "genCountry$cc$lang.html";
else
    $includeSub     = "genCountry$lang.html";
$template           = new FtTemplate($includeSub);
if (is_string($cctext))
    $warn           .= "<p>Invalid value for cc=$cctext ignored.</p>\n";

$template->set('COUNTRYNAME',   $countryName);
$template->set('CC',            $cc);

$template->display();
