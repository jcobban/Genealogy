<?php
namespace Genealogy;
use \PDO;
use \Exception;

/************************************************************************
 *  test class Surname							                        *
 *									                                    *
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';
require_once __NAMESPACE__ . '/Surname.inc';

htmlHeader('Test Surname Class');
?>
<body>
<?php
pageTop(array());
showTrace();

$surname            = '';
$surnametext        = null;
$lang               = 'en';
$langtext           = null;

if (isset($_GET) && count($_GET) > 0)
{                   // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {               // loop through parameters
        $safevalue  = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        $fieldLc    = strtolower($key);
        switch($fieldLc)
        {           // act on specific parameter
            case 'lang':
            {       // lang
                $lang               = FtTemplate::validateLang($value);
                break;
            }       // lang

            case 'surname':
            {
                if (preg_match("/^[a-zA-Z ']*$/", $value))
                    $surname        = $value;
                else
                    $surnametext    = $safevalue;
                break;
            }
        }           // act on specific parameter
    }               // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by method=get

$surnameObj         = new Surname(array('surname' => $surname));
$nextobj            = $surnameObj->next();

if ($surnameObj->isExisting())
    print "<p>'$surname' is an existing Surname</p>\n";

print $surnameObj->dump('Surname Object');
print $nextObj->dump('Next Surname Object');

