<?php
namespace Genealogy;
use \PDO;
use \Exception;
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
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/common.inc';

// default parameter values
$idsr			= null;
$source			= null;
$cemeteryName	= 'Cemetery';
$plot			= '';	
$lang   		= 'en';
$parms			= array();

// get the parameters
foreach($_GET as $key => $value)
{	            	// loop through all parameters
	switch(strtolower($key))
	{	            // take action on specific parameter
	    case 'idsr':
	    {           // identify cemetery transcription source
			$idsr			    = (int)$value;
			$parms['idsr']		= $idsr;
			try {
                $source	        = new Source(array('idsr' => $idsr));
                // all cemetery transcription source names start with
                // 'Cemetery Transcription: '
			    $cemeteryName   = substr($source->getName(), 23);
			} catch(Exception $e) { }
			break;
	    }           // identify cemetery transcription source

	    case 'plot':
	    {           // plot identification
			$plot			= trim($value);
			if (preg_match('/^[-\w]+$/', $plot) == 1)
			{
			    $parms['srcdetail']	= $plot;
			}
			else
			{
			    $msg	.= "Unexpected value of plot = '$plot'. ";
			}
			break;
	    }           // plot identification

        case 'lang':
        {
            if (strlen($value) == 2)
                $lang           = strtolower($value);
        }
	}	            // take action on specific parameter
}		            // loop through all parameters

$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "CemeteryReference$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language	= new Language(array('code' => $lang));
	$langName	= $language->get('name');
	$nativeName	= $language->get('nativename');
    $sorry      = $language->getSorry();
    $warn       .= str_replace(array('$langName','$nativeName'),
                           array($langName, $nativeName),
                           $sorry);
	$includeSub	= 'CemeteryReferenceen.html';
}
$template->includeSub($tempBase . $includeSub, 'MAIN');

if (strlen($plot) == 0)
    $msg	.= $template->getElementById('missingPlot')->innerHTML();
if (is_null($idsr))
{
    $msg	        .=  $template->getElementById('missingIdsr')->innerHTML();
    $cemeteryName   = $template->getElementById('Cemetery')->innerHTML();
}

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
	    $person	= $citation->getPerson();
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
$template->set('TITLE',			    "$title  $cemeteryName: $plot");
$template->set('LANG',			    $lang);
$template->set('IDSR',		        $idsr);
$template->set('CONTACTKEY',		$idsr);
$template->set('CONTACTTABLE',		'tblSX');
$template->set('CONTACTSUBJECT',	'[FamilyTree]' . $_SERVER['REQUEST_URI']);

$template->display();
