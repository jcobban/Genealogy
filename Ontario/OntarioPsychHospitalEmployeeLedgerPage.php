<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
/************************************************************************
 *  OntarioPsychHospitalEmployeeLedgerPage.php                          *
 *                                                                      *
 *  Display one page of the transcription of an Ontario Psychiatric     *
 *  Hospital Employee Register.                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      Location        location of the Hospital                        *
 *      page            page to display                                 *
 *                                                                      *
 *  History:                                                            *
 *      2021/06/12      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/HospitalPage.inc';
require_once __NAMESPACE__ . '/HospitalEmployeeLedgerEntry.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$location           = 'London'; // default location
$cc                 = 'CA';
$countryName        = 'Canada';
$domainName         = 'Ontario';
$page               = 1;
$pagetext           = null;
$image              = null;
$employee           = null;
$lang               = 'en';

// get parameters
if (isset($_GET) && count($_GET) > 0)
{                           // parameters passed by URL
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
            }               // location of hospital
    
            case 'page':
            {               // page to display
                if (ctype_digit($value) && $value > 0)
                    $page           = intval($value);
                else
                    $pagetext       = $safevalue;
                break;
            }
    
            case 'image':
            {
                $image              = $image;
                break;
            }               // image
    
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
}                           // parameters passed by URL
else
if (isset($_POST) && count($_POST) > 0)
{                           // parameters passed for updating
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                            "<table class='summary'>\n" .
                            "<tr><th class='colhead'>key</th>" .
                            "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {                       // loop through all input parameters
        $safevalue      = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n";
        if (preg_match('/^([a-zA-Z_]+)(\d+)$/', $key, $matches))
        {
            $key        = $matches[1];
            $index      = $matches[2];  // employee number
        }
        else
            $index      = '';

        switch(strtolower($key))
        {                   // process specific named parameters
            case 'location':
            {
                $location           = $safevalue;
                break;
            }               // location of hospital
    
            case 'page':
            {               // page to display
                if (ctype_digit($value) && $value > 0)
                {
                    $page           = intval($value);
                }
                else
                    $pagetext       = $safevalue;
                break;
            }
    
            case 'image':
            {
                $image              = $image;
                break;
            }               // image
    
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);     
                break;
            }               // any other paramters
    
            case 'debug':
            {
                break;
            }               // handled by common code

            case 'employee':
            {               // employee number input field
                if ($employee)
                {
                    $count          = $employee->save();
                    if ($count)
                        $warn       .= "<p>" . $employee->getLastSqlCmd() .
                                        "</p>\n";
                    else
                        $warn       .= "<p>" . $employee->getErrors() .
                                        "</p>\n";
                }
                $employee           = new HospitalEmployeeLedgerEntry(
                                            array('location'=> $location,
                                                  'ledger'  => 'E',
                                                  'page'    => $page,
                                                  'employee'=> $value));
                break;
            }               // employee number input field

            case 'givennames':
            case 'surname':
            case 'age':
            case 'prevocc':
            case 'prevres':
            case 'service':
            case 'dateemploy':
            case 'datedisch':
            case 'religion':
            case 'remarks':
            {               // other fields in the record
                $employee->set($key, $value);
                break;
            }               // other fields in the record
    
            case 'idir':
            {               // link to family tree
                if ($value)
                    $employee->set($key, $value);
                else
                    $employee->set($key, null);
                break;
            }               // link to family tree

            case 'wages':
            {               // other fields in the record
                if (preg_match('/\d+(\.\d\d|)/', $value))
                    $employee->set($key, $value);
                else
                    $employee->set($key, 0.0);
                break;
            }               // other fields in the record
    
            default:
            {
                $warn   .= "Unexpected parameter $key='$safevalue'. ";
                break;
            }               // any other paramters
        }                   // process specific named parameters
    }                       // loop through all input parameters
    if ($debug)
        $warn                       .= $parmsText . "</table>\n";
}                           // parameters passed for updating

$template       = new FtTemplate("OntarioPsychHospitalEmployeeLedgerPageUpdate$lang.html");
$formatter                  = $template->getFormatter();
$translate                  = $template->getTranslate();
$t                          = $translate['tranTab'];

$country                    = new Country(array('code'      => $cc));
$countryName                = $country->getName($lang);

$pageEntry                  = new HospitalPage(
                                            array('location'=> $location,
                                                  'ledger'  => 'E',
                                                  'page'    => $page));
$image                      = $pageEntry['image'];

$employees                  = new RecordSet('HospitalEmployeeLedgerEntries',
                                            array('location'=>"^$location$",
                                                  'ledger'  => '^E$',
                                                  'page'    => $page));
$information                = $employees->getInformation();
if ($information['count'] == 0)
{
    $employee               = 39 * $page - 38;
    for ($i = 39; $i; $i--)
    {
        $newentry           = new HospitalEmployeeLedgerEntry(
                                array('location'        => $location,
                                      'ledger'          => 'E',
                                      'page'            => $page,
                                      'employee'        => $employee));
        $employees[]            = $newentry;
        $employee++;
    }
}

// add display of data
$matches                    = array('$employee',
                                    '$givennames',
                                    '$surname',
                                    '$age',
                                    '$prevocc',
                                    '$prevres',
                                    '$service',
                                    '$dateemploy',
                                    '$datedisch',
                                    '$wages',
                                    '$religion',
                                    '$remarks',
                                    '$idir',
                                    '$buttonclass',
                                    '$buttonlabel',
                                    '$pclass');
$rowElement                 = $template['detail$employee'];
$rowHtml                    = $rowElement->outerHTML();
$rowData                    = "";
$total                      = 0;
$prevPage                   = $page - 1;
$nextPage                   = $page + 1;
$rowClass                   = 'odd';
foreach($employees as $row)
{               // continue until finished
    $idir                   = $row['idir'];
    if ($idir)
    {
        $buttonclass        = 'green';
        $buttonlabel        = $t['Show'];
    }
    else
    {
        $buttonclass        = 'gray';
        $buttonlabel        = $t['Find'];
    }
    $replace                = array($row['employee'],
                                    $row['givennames'],
                                    $row['surname'],
                                    $row['age'],
                                    $row['prevocc'],
                                    $row['prevres'],
                                    $row['service'],
                                    $row['dateemploy'],
                                    $row['datedisch'],
                                    $row['wages'],
                                    $row['religion'],
                                    $row['remarks'],
                                    $idir,
                                    $buttonclass,
                                    $buttonlabel,
                                    $rowClass);
    $rowData                .= str_replace($matches,
                                           $replace,
                                           $rowHtml);
    $lastPage               = $row['page'];
    if ($rowClass == 'odd')
        $rowClass           = 'even';
    else
        $rowClass           = 'odd';
}               // continue until finished
if ($prevPage < 1)
    $template['topPrev']->update(null);
$rowElement->update($rowData);

$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
$template->set('TOTAL',         $formatter->format(0));
$template->set('LANG',          $lang);
$template->set('CC',            $cc);
$template->set('PAGE',          $page);
$template->set('IMAGE',         $image);
$template->set('COUNTRYNAME',   $countryName);
$template->set('LOCATION',      $location);
$template->set('DOMAIN',        'CAON');
$template->set('DOMAINNAME',    $domainName);
$template->set('PROVINCE',      'ON');
$template->set('PREVPAGE',      $prevPage);
$template->set('NEXTPAGE',      $nextPage);
$template->set('LASTPAGE',      $lastPage);
$template->set('DONE',          '0');
$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
$template->set('PCTDONE',       '0.00');
$template->set('PCTLINKED',     '0.00');

$template->display();
