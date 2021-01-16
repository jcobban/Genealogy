<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  MarriageReg1869Fixup.php											*
 *																		*
 *  Change marriages which used the clerk's sequential number so they   *
 *  use the volume, page, item identification instead.                  *
 *																		*
 *  History: 															*
 *		2020/09/09      implemented                                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MarriageSet.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *																		*
 *  Open code.															*
 *																		*
 ************************************************************************/

// first extract the values of all supplied parameters
$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach ($_GET as $key => $value)
{			// loop through all parameters
    $value                          = trim($value);
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    if (strlen($value) > 0)
    {
        $fieldLc                    = strtolower($key);
        switch($fieldLc)
        {		// switch on parameter name
            case 'regnum':
            {		// limit number of rows returned
                $regnum	            = $value;
                break;
            }		// limit number of rows returned
    
            case 'last':
            {		// limit number of rows returned
                $last	            = $value;
                break;
            }		// limit number of rows returned
    
        }		// switch on parameter name
    }
}
// start the template
$template			= new FtTemplate("MarriageReg1869Fixupen.html");
$trtemplate         = $template->getTranslate();
$template->set('CONTACTTABLE',		'Marriage');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->set('DOMAIN',	    'CAON');
$template->set('DOMAINNAME',	'Ontario');
$template->set('CC',	        'CA');
$template->set('COUNTRYNAME',	'Canada');
$template->set('LANG',	        'en');
$template->set('REGYEAR',       1869);
$template->set('REGNUM',        1091);

$getparms           = array('regyear'   => 1869,
                            'regnum'    => array($regnum, $last),
                            'limit'     => 20);
$marriages          = new MarriageSet($getparms);
$info               = $marriages->getInformation();
$warn               = "<p class='label'>\$info</p>\n" .
	                        "<table class='summary'>\n" .
	                        "<tr><th class='colhead'>key</th>" .
	                        "<th class='colhead'>value</th></tr>\n";
foreach ($info as $key => $value)
{			// loop through all parameters
    $warn           .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>";
    if (is_array($value))
        $warn       .= print_r($value, true);
    else
        $warn       .= $value;
    $warn           .= "</td></tr>\n"; 
}
$warn               .= "</table>\n";

$numRows	        = count($marriages);
$totalrows          = $info['count'];
$body               = "<p>numRows=$numRows, totalrows=$totalrows</p>\n";

foreach($marriages as $marriage)
{
    $groom          = $marriage->getGroom();
    if ($groom && substr($groom['givennames'], 0, 1) == '[')
        continue;
    $volume         = 1;
    $page           = $marriage['originalpage'];
    $item           = $marriage['originalitem'];
    $regnum         = $marriage['regnum'];
    $newregnum      = 10000 + ($page * 10) + $item;
    $body           .= "<h4>Move 1869-$regnum to 1869-$newregnum</h4>\n";
    $oldmarriage    = new Marriage('CAON', 1869, $newregnum);
    if ($oldmarriage->isExisting())
    {                   // copy values
        $oldmarriage['originalvolume']      = 1;
        $oldmarriage['originalpage']        = $page;
        $oldmarriage['originalitem']        = $item;
        $oldmarriage['date']                = $marriage['date'];
		$oldmarriage['regcounty']	        = $marriage['regcounty'];
		$oldmarriage['regtownship']	        = $marriage['regtownship'];
		$oldmarriage['place']	            = $marriage['place'];
		$oldmarriage['licensetype']	        = $marriage['licensetype'];
		$oldmarriage['remarks']	            = $marriage['remarks'];
		$oldmarriage['image']	            = $marriage['image'];
		$oldmarriage['registrar']	        = $marriage['registrar'];
		$oldmarriage['regdate']	            = $marriage['regdate'];
        $oldmarriage->save(false);
        $lastcmd    = $oldmarriage->getLastSqlCmd();
        if (strlen($lastcmd) > 0)
            $body   .= "<p>$lastcmd</p>\n";
        $marriage->delete(false);
        $lastcmd    = $marriage->getLastSqlCmd();
        if (strlen($lastcmd) > 0)
            $body   .= "<p>$lastcmd</p>\n";
    }                   // copy values
    else
    {                   // move marriage
        $marriage['originalvolume']         = 1;
        $marriage['regnum']                 = $newregnum;
        $marriage->save(false);
        $lastcmd    = $marriage->getLastSqlCmd();
        if (strlen($lastcmd) > 0)
            $body   .= "<p>$lastcmd</p>\n";
    }                   // move marriage
    $citations      = new CitationSet(array('idsr'      => 99,
                                            'srcdetail' => "1869-0$regnum"));
    if (count($citations) > 0)
        $body       .= "<p>replace " . count($citations) . " citations '1869-0$regnum' with '1869 vol $volume page $page item $item'</p>\n";
    foreach($citations as $citation)
    {
        $citation['srcdetail']  = "1869 vol $volume page $page item $item";
        $citation->save(false);
        $body       .= "<p>" . $citation->getLastSqlCmd() . "</p>\n";
    }
}

$template->set('BODY',        $body);
$template->display();
