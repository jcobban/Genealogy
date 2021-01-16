<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  CemeteryReference.php												*
 *																		*
 *  Display a web page containing all of the individuals matching		*
 *  a particular cemetery plot reference.								*
 *																		*
 *  History:															*
 *		2016/06/06		created											*
 *		2017/07/07		add syntax checking of plot value				*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/02/24		undefined $list									*
 *		2018/11/01      use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2020/12/06      fix XSS vulnerabilities                         *
 *		                get more message texts from template            *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default parameter values
$idsr						= null;
$idsrtext                   = null;
$plot						= null;	
$plottext                   = null;
$source						= null;     // instance of class Source
$cemeteryName				= 'Cemetery';
$lang   					= 'en';
$parms						= array();  // parameters for CitationSet

// get the parameters
foreach($_GET as $key => $value)
{	            	// loop through all parameters
    $value                          = trim($value);
	switch(strtolower($key))
	{	            // take action on specific parameter
	    case 'idsr':
        {           // identify cemetery transcription source
            if (ctype_digit($value))
            {
				$idsr			    = (int)$value;
				$parms['idsr']		= $idsr;
	            $source	            = new Source(array('idsr' => $idsr));
	            // all cemetery transcription source names start with
                // 'Cemetery Transcription: '
                $srcName            = $source->getName();
                if (substr($srcName, 0, 22) == 'Cemetery Transcription')
                    $cemeteryName   = substr($srcName, 24);
                else
                {
                    $idsrtext       = "$value Source='$srcName'";
                    $cemeteryName   = $srcName;
                }
            }
            else
                $idsrtext           = htmlspecialchars($value);
			break;
	    }           // identify cemetery transcription source

	    case 'plot':
	    {           // plot identification
			$count      = preg_match('/^[a-zA-Z0-9\s.,-]+$/', $value);
			if ($count == 1)
			{
			    $plot			    = trim($value);
			    $parms['srcdetail']	= $plot;
			}
			else
            {
                $plottext           = htmlspecialchars($value);
			}
			break;
	    }           // plot identification

        case 'lang':
        {
            $lang           = FtTemplate::validateLang($value);
			break;
        }
	}	            // take action on specific parameter
}		            // loop through all parameters

$template		    = new FtTemplate("CemeteryReference$lang.html");

// issue customized messages
if (is_string($idsrtext))
{
    $text	    = $template->getElementById('invalidIdsr')->innerHTML();
    $msg	    .= str_replace('$value', $idsrtext, $text);
}
else
if (is_null($idsr))
{
    $msg	        .=  $template->getElementById('missingIdsr')->innerHTML();
    $cemeteryName   = $template->getElementById('Cemetery')->innerHTML();
}

if (is_string($plottext))
{
    $text	    = $template->getElementById('invalidPlot')->innerHTML();
    $msg	    .= str_replace('$value', $plottext, $text);
}
else
if (is_null($plot))
    $msg	.= $template->getElementById('missingPlot')->innerHTML();

if (strlen($msg) == 0)
{			// get the matching citations
    $parms['order']     = 'SrcDetail';
	$list		        = new CitationSet($parms);
	$info		        = $list->getInformation();
	$count		        = $list->count();
	$totalcount	        = $info['count'];
}			// get the matching citations
else
{
	$list		        = array();
	$count		        = 0;
	$totalcount	        = 0;
}

$element                = $template->getElementById('amatch');
$mtemplateHtml          = $element->outerHTML();
if ($count > 0)
{		                // query issued and retrieved some records
	// display the results
    $oldidir	        = 0;
    $data               = '';
	foreach($list as $idsx => $citation)
	{		            // loop through results
	    $person	        = $citation->getPerson();
	    if ($person)
	    {		        // instance of Person
			$idir	    = $person->getIdir();
            $name       = $person->getName(Person::NAME_INCLUDE_DATES);
			if ($idir != $oldidir)
            {
                $mtemplate  = new Template($mtemplateHtml);
                $mtemplate->set('IDIR',     $idir);
                $mtemplate->set('NAME',     $name);
                $mtemplate->set('LANG',     $lang);
                $data       .= $mtemplate->compile();
			}
			$oldidir	= $idir;
	    }		        // instance of Person
    }		            // loop through results
    $element->update($data);
    $template->updateTag('nomatches', null);
}			            // have results
else
{
    $element->update(null);
}
$title          = $template->getElementById('Cemetery')->innerHTML();
$template->set('TITLE',			    "$title $cemeteryName: $plot");
$template->set('LANG',			    $lang);
$template->set('IDSR',		        $idsr);
$template->set('CONTACTKEY',		$idsr);
$template->set('CONTACTTABLE',		'tblSX');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
