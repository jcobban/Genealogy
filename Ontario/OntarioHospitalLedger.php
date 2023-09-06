<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \DirectoryIterator;
use \NumberFormatter;
/************************************************************************
 *  OntarioHospitalLedger.php                                           *
 *                                                                      *
 *  Display the status of the transcription of Ontario                  *
 *  Hospital Ledger.                                                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      Location        location of the Hospital                        *
 *      page            number of columns to display                    *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/12      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$location           = 'London'; // default location
$ledger             = 'E';      // employee ledger
$cc                 = 'CA';
$countryName        = 'Canada';
$domainName         = 'Ontario';
$page               = 1;
$pagetext           = null;
$lang               = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{                       // loop through all input parameters
    $safevalue  = htmlspecialchars($value);
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$safevalue</td></tr>\n"; 
    switch(strtolower($key))
    {                   // process specific named parameters
        case 'location':
        {
            $location           = $safevalue;
            break;
        }               // RegDomain

        case 'ledger':
        {
            $ledger             = $safevalue;
            break;
        }               // RegDomain

        case 'page':
        {
            if (ctype_digit($value) && $value > 0)
                $page           = intval($value);
            else
                $pagetext       = $safevalue;
            break;
        }

        case 'lang':
        {
            $lang               = FtTemplate::validateLang($value);     
            break;
        }               // any other paramters

        case 'debug':
        {
            break;
        }               // handled by common code

        default:
        {
            $warn   .= "Unexpected parameter $key='$safevalue'. ";
            break;
        }               // any other paramters
    }                   // process specific named parameters
}                       // loop through all input parameters
if ($debug)
    $warn                       .= $parmsText . "</table>\n";

$template       = new FtTemplate("OntarioHospitalLedgerUpdate$lang.html");
$formatter                  = $template->getFormatter();

$country                    = new Country(array('code'      => $cc));
$countryName                = $country->getName($lang);

$pages                      = new RecordSet('HospitalPages',
                                            array('location'=> '^London$',
                                                  'ledger'  => '^E$',
                                                  'page'    => ">=$page",
                                                  'limit'   => 20));
$information                = $pages->getInformation();
$totalPages                 = $information['count'];

// add display of data
$matches                    = array('$location',
                                    '$page',
                                    '$image',
                                    '$pclass',
                                    '$namecount',
                                    '$pctdone',
                                    '$pctclassdone',
                                    '$pctlinked',
                                    '$pctclasslinked',
                                    '$transcriber',
                                    '$proofreader');
$rowElement                 = $template['detail$page'];
$rowHtml                    = $rowElement->outerHTML();
$rowData                    = "";
$total                      = 0;
$totalDone                  = 0;
$prevPage                   = $page - 20;
$nextPage                   = $page + 20;
$rowClass                   = 'odd';
foreach($pages as $row)
{               // continue until finished
    $row['pclass']          = $rowClass;
    $p                      = $row['page'];
    $employees              = new RecordSet('HospitalEmployeeLedgerEntries',
                                            array('location'=>"^$location$",
                                                  'ledger'  => '^E$',
                                                  'page'    => $p,
                                                  'surname' => 'length>0'));
    $namecount              = $employees->count();
    $linkcount              = 0;
    foreach($employees as $employee)
    {
        if ($employee['idir'] > 0)
            $linkcount++;
    }
    $total                  += 49;
    $totalDone              = $totalDone + $namecount;
    $row['namecount']       = $namecount;
    $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
    $pctDone                = ($namecount * 100)/39;
    $row['pctdone']         = $formatter->format($pctDone);
    $row['pctclassdone']    = pctClass($pctDone);
    $pctLinked              = $linkcount/39;
    $row['pctlinked']       = $formatter->format($pctLinked);
    $row['pctclasslinked']  = pctClass($pctLinked);
    $values                 = array($row['location'],
                                    $row['page'],
                                    $row['image'],
                                    $row['pclass'],
                                    $row['namecount'],
                                    $row['pctdone'],
                                    $row['pctclassdone'],
                                    $row['pctlinked'],
                                    $row['pctclasslinked'],
                                    $row['transcriber'],
                                    $row['proofreader']);
    $rowData                .= str_replace($matches,
                                           $values,
                                           $rowHtml);
    $lastPage               = $row['page'];
    if ($rowClass == 'odd')
        $rowClass           = 'even';
    else
        $rowClass           = 'odd';
}               // continue until finished

if ($prevPage < 1)
    $template['topPrev']->update('&nbsp;');
if ($totalPages < 21)
    $template['topNext']->update('&nbsp;');
$rowElement->update($rowData);

$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
$template->set('LANG',          $lang);
$template->set('CC',            $cc);
$template->set('PAGE',          $page);
$template->set('COUNTRYNAME',   $countryName);
$template->set('LOCATION',      $location);
$template->set('DOMAIN',        'CAON');
$template->set('DOMAINNAME',    $domainName);
$template->set('PROVINCE',      'ON');
$template->set('PREVPAGE',      $prevPage);
$template->set('NEXTPAGE',      $nextPage);
$template->set('LASTPAGE',      $lastPage);
$template->set('DONE',          $formatter->format($totalDone));
$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
$template->set('PCTDONE',       $formatter->format(100.0*$totalDone/$total));
$template->set('PCTLINKED',     '0.00');
$template->set('PCTCLASSDONE',  pctClass(100.0*$totalDone/$total));
$template->set('PCTCLASSLINKED',pctClass(0));

$template->display();
