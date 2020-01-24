<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  DeathRegStats.php								                    *
 *										                                *
 *  Display statistics about the transcription of death registrations.	*
 *										                                *
 *  Parameters:									                        *
 *		RegDomain	domain consisting of country code and state	        *
 *										                                *
 *  History:								                            *
 *		2011/01/09	    created						                    *
 *		2011/03/16	    display in 3 columns				            *
 *				        include 2nd level breakdown			            *
 *		2011/08/10	    add help					                    *
 *		2011/10/27	    use <button> instead of <a> for view action	    *
 *				        support mouseover help				            *
 *	    2013/08/04	    use pageTop and pageBot to standardize appearance
 *		2013/11/27	    handle database server failure gracefully	    *
 *		2013/12/07	    $msg and $debug initialized by common.inc	    *
 *		2013/12/24	    use CSS for layout instead of tables		    *
 *				        support RegDomain parameter			            *
 *		2014/01/13	    use CSS for table style				            *
 *		2015/07/02	    access PHP includes using include_path		    *
 *		2015/09/28	    migrate from MDB2 to PDO			            *
 *		2016/04/25	    replace ereg with preg_match			        *
 *		2016/05/20	    use class Domain to validate domain code	    *
 *		2017/02/07	    use class Country				                *
 *		2018/06/01	    add support for lang parameter			        *
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *		2019/01/19      use class Template                              *
 *		2019/12/14      use class DeathSet                              *
 *		2020/01/22      internationalize numbers                        *
 *										                                *
 *  Copyright &copy; 2020 James A. Cobban					            *
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/DeathSet.inc";
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$cc			    = 'CA';		// default country code
$countryName	= 'Canada';	// default country name
$domain	        = 'CAON';	// default domain
$domainName		= 'Ontario';
$lang		    = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    switch(strtolower($key))
    {		// process specific named parameters
        case 'regdomain':
        {
            $domain	    = $value;
            break;
        }		// RegDomain

        case 'lang':
        {
            if (strlen($value) == 2)
                $lang		= strtolower($value);
            break;
        }		//lang

        case 'debug':
        {
            break;
        }		// handled by common code

        default:
        {
            $warn	.= "Unexpected parameter $key='$value'. ";
            break;
        }		// any other paramters
    }		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

$template		= new FtTemplate("DeathRegStats$lang.html");

$template->set('CC',                $cc);
$template->set('COUNTRYNAME',		$countryName);
$template->set('DOMAINNAME',		$domainName);
$template->set('DOMAIN',	        $domain);
$template->set('LANG',		        $lang);
$template->set('CONTACTTABLE',		'Deaths');
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
if ($debug)
    $template->set('DEBUG',		    'Y');
else
    $template->set('DEBUG',		    'N');

// validate domain
$domainObj	            = new Domain(array('domain'	    => $domain,
                                           'language'	=> 'en'));
if ($domainObj->isExisting())
{
    $cc		            = substr($domain, 0, 2);
    $countryObj		    = new Country(array('code' => $cc));
    $countryName	    = $countryObj->getName();
    $domainName	        = $domainObj->get('name');
}
else
{
    $domainName	        = $domainObj->get('name');
    $msg	            .= "Domain '$domain' must be a supported two character country code followed by a state or province code. ";
    $domain             = 'CAON';
}

// execute the query
$births	        	= new DeathSet(array('domain' => $domain));
$result		        = $births->getSummary();

// $dataRow is the template for displaying a single year of statistics
$dataRow            = $template['dataRow'];
$yearHTML           = $dataRow->innerHTML();

$thRow              = $template->getElementById('thRow');
$numCols            = (int)((count($thRow->children) - 2) / 4);
$col		        = 0;
$total		        = 0;
$totalLinked	    = 0;
$rownum		        = 0;
$yearClass		    = "odd right";
$data               = '';
$formatter                          = $template->getFormatter();

foreach($result as $row)
{
    if ($col == 0)
        $data       .= "    <tr>\n";
    $rownum++;
    $ttemplate      = new Template($yearHTML);
    $regYear	    = $row[0];
    $count		    = $row[1];
    $linked		    = $row[2];
    if ($count == 0)
        $pctLinked	= 0;
    else
        $pctLinked	= 100 * $linked / $count;
    $total		    += $count;
    $totalLinked	+= $linked;
    $ttemplate->set('DOMAIN',       $domain);
    $ttemplate->set('REGYEAR',      $regYear);
    $ttemplate->set('YEARCLASS',    $yearClass . ' right');
    $ttemplate->set('COUNT',        $formatter->format($count));
    $ttemplate->set('LINKED',       $formatter->format($linked));
    $ttemplate->set('PCTLINKED',    pctClass($pctLinked));
    $col++;
    if ($col >= $numCols)
    {	// at column limit, end row
        $col	= 0;
        if ($yearClass == "odd right")
            $yearClass	= "even right";
        else
            $yearClass	= "odd right";
        $ttemplate["columnSep"]->update(null);
        $data           .= $ttemplate->compile();
        $data           .= "    </tr>\n";
    }	// at column limit, end row
    else
    {	// start new column
        $data           .= $ttemplate->compile();
    }	// start new column
}		// process all rows

// end last row if necessary
if ($col != 0)
{		// partial last column
    $data               .= "    </tr>\n";
}		// partial last column

$dataRow->update($data);
$template->set('TOTAL',         $formatter->format($total));
$template->set('TOTALLINKED',   $formatter->format($totalLinked));
 
$template->display();
