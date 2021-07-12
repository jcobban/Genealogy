<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \DirectoryIterator;
use \NumberFormatter;
/************************************************************************
 *  OntarioHospitals.php                                                *
 *                                                                      *
 *  Display links to information about all supported Ontario Hospitals. *
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
$cc                 = 'CA';
$countryName        = 'Canada';
$domainName         = 'Ontario';
$lang               = 'en';

if (isset($_GET) && count($_GET) > 0)
{
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
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);     
                break;
            }               // any other paramters
    
        }                   // process specific named parameters
    }                       // loop through all input parameters
    if ($debug)
        $warn                       .= $parmsText . "</table>\n";
}                           // parameters passed

$template       = new FtTemplate("OntarioHospitals$lang.html");

$country                    = new Country(array('code'      => $cc));
$countryName                = $country->getName($lang);

$hospitals                  = new RecordSet('Locations',
                                            array('location'=> '^Ontario.*Hospital'));
$information                = $hospitals->getInformation();

// add display of data
$rowElement                 = $template['hospital$location'];
$rowHtml                    = $rowElement->outerHTML();
$rowData                    = "";

foreach($hospitals as $row)
{                       // continue until finished
    $name                   = $row['location'];
    if (preg_match('/^Ontario (.*)Hospital, ([^,]+)/', $name, $matches))
    {
        $modifier           = $matches[1];
        $location           = $matches[2];
    }
    else
    {
        $warn               .= "<p>location '$name' does not match pattern</p>\n";
        $location           = $name;
        $modifier           = '';
    }
    $rowData                .= str_replace(array('$location','$modifier'),
                                           array($location,$modifier),
                                           $rowHtml);
}               // continue until finished

$rowElement->update($rowData);

$template->set('LANG',          $lang);
$template->set('CC',            $cc);
$template->set('COUNTRYNAME',   $countryName);
$template->set('DOMAIN',        'CAON');
$template->set('DOMAINNAME',    $domainName);
$template->set('PROVINCE',      'ON');

$template->display();
