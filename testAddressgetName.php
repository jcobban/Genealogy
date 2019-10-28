<?php
namespace Genealogy;
use \PDO;
use \Exception;

require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

    $template           = new FtTemplate("ancestorReportde.html");
    $translate          = $template->getTranslate();
    $t                  = $translate['tranTab'];
    $months             = $translate['Months'];
    $address            = new Address(array('idar' => 504));
    print $address->getName() . "\n";
    print $address->getName($t) . "\n";
    print "<p>deleted -> " . $t['deleted'];
    print "<p>Oct -> " .$months[10];
