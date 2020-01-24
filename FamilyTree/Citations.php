<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  Citations.php														*
 *																		*
 *  Display a web page containing all of the citations matching a		*
 *  pattern.															*
 *																		*
 *  History:															*
 *		2011/07/05		created											*
 *		2011/10/02		cleanup											*
 *		2012/01/13		change class names								*
 *		2012/02/01		name of marriage edit script changed			*
 *		2012/02/25		use switch for parameter names					*
 *						add support for popup help bubbles				*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/08/21		use htmlHeader function to standardize <head>	*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/14		use CSS rather than tables for layout			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/04		use Citation::getCitations to obtain			*
 *						list of citations to display instead of			*
 *						accessing SQL directly							*
 *						Initialize selected option of event type		*
 *						selection list in PHP rather than javascript	*
 *		2014/10/02		incorrect parameter list to getCitations		*
 *		2015/05/04		display the name of the associated record		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js	before util.js					*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/15		class LegacyToDo renamed to class ToDo			*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2020/01/22      internationalize numbers                        *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Child.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/********************************************************************
 *  Identify type of record containing event details				*
 ********************************************************************/
static $urlname = array(
				0	=> "Person.php?idir=",
				1	=> "Person.php?idir=",
				2	=> "Person.php?idir=",
				3	=> "Person.php?idir=",
				4	=> "Person.php?idir=",
				5	=> "Person.php?idir=",
				6	=> "Person.php?idir=",
				7	=> "Person.php?idir=",
				8	=> "Person.php?idir=",
				9	=> "Person.php?idir=",
				10	=> "getRecordXml.php?idnx=",
				11	=> "getRecordXml.php?idcr=",
				12	=> "getRecordXml.php?idcr=",
				13	=> "getRecordXml.php?idcr=",
				15	=> "Person.php?idir=",
				16	=> "Person.php?idir=",
				17	=> "getRecordXml.php?idcr=",
				18	=> "editMarriages.php?idmr=",
				19	=> "editMarriages.php?idmr=",
				20	=> "editMarriages.php?idmr=",
				21	=> "editMarriages.php?idmr=",
				22	=> "editMarriages.php?idmr=",
				23	=> "editMarriages.php?idmr=",
				26	=> "Person.php?idir=",
				27	=> "Person.php?idir=",
				30	=> "editEvent.php?type=30&amp;ider=",
				31	=> "editEvent.php?type=31&amp;ider=",
				40	=> "getRecordXml.php?idtd=");

// get the parameters
$pattern			= '';
$count	    		= 0;
$offset	    		= 0;
$limit	    		= 20;
$type	    		= null;		// so it will fail to match on default
$idsr	    		= null;		// so it will fail to match on default
$lang               = 'en';
$parms	    		= array();

if (count($_GET) > 0)
{
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {		// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch($key)
		{	// take action on specific parameter
		    case 'pattern':
		    {
				$pattern		    = $value;
				$parms['srcdetail']	= $value;
				break;
		    }

		    case 'type':
		    {
                if (ctype_digit($value))
                {
				    $type			= (int)$value;
                    $parms['type']	= $type;
                }
				break;
		    }

		    case 'idsr':
		    {
                if (ctype_digit($value))
                {
				    $idsr			= (int)$value;
                    $parms['idsr']	= $idsr;
                }
				break;
		    }

		    case 'offset':
            {
                if (ctype_digit($value))
				    $offset			= (int)$value;
				break;
		    }

		    case 'limit':
		    {
                if (ctype_digit($value))
				    $limit			= (int)$value;
				break;
            }

            case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang           = strtolower(substr($value, 0, 2));
            }
		}	    // take action on specific parameter
    }		    // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by method=get

$parms['offset']	= $offset;
$parms['limit']	    = $limit;

$template           = new FtTemplate("Citations$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'Citations'));
$formatter                          = $template->getFormatter();

if (strlen($pattern) == 0)
	$msg	.= "Please specify a pattern for citations. ";
if (is_null($idsr))
	$msg	.= "Please identify source. ";
if (is_null($type))
	$msg	.= "Please identify citation or event type. ";

$prevoffset	    = $offset - $limit;
$nextoffset	    = $offset + $limit;

if (strlen($msg) == 0)
{			// get the matching citations
	$list		= new CitationSet($parms,
								  'SrcDetail');
	$info		= $list->getInformation();
	$count		= $list->count();
	$totalcount	= $info['count'];
}			// get the matching citations

if ($count > 0)
    $template->set('COUNT',     $formatter->format($totalcount));
else
    $template->set('COUNT',     'NO');

$template['otherStylesheets']->update(array('filename'  => 'Citations'));

$template->set('IDSR',              $idsr);
$template->set('TYPE',              $type);
$template->set('PATTERN',           $pattern);
$template->set('LIMIT',             $limit);
$template->set('SELECTED0',		    '');
$template->set('SELECTED1',		    '');
$template->set('SELECTED2',		    '');
$template->set('SELECTED3',		    '');
$template->set('SELECTED4',		    '');
$template->set('SELECTED5',		    '');
$template->set('SELECTED6',		    '');
$template->set('SELECTED7',		    '');
$template->set('SELECTED8',		    '');
$template->set('SELECTED9',		    '');
$template->set('SELECTED10',		'');
$template->set('SELECTED11',		'');
$template->set('SELECTED12',		'');
$template->set('SELECTED13',		'');
$template->set('SELECTED15',		'');
$template->set('SELECTED16',		'');
$template->set('SELECTED17',		'');
$template->set('SELECTED18',		'');
$template->set('SELECTED19',		'');
$template->set('SELECTED20',		'');
$template->set('SELECTED21',		'');
$template->set('SELECTED22',		'');
$template->set('SELECTED23',		'');
$template->set('SELECTED26',		'');
$template->set('SELECTED27',		'');
$template->set('SELECTED30',		'');
$template->set('SELECTED31',		'');
$template->set('SELECTED40',		'');
if (is_null($type) || $type < 0)
    $template->set('UNSELECTED',		 'selected="selected"');
else
{
    $template->set('UNSELECTED',		 '');
    $template->set("SELECTED$type",		 'selected="selected"');
}

if ($count > 0)
{		// query issued and retrieved some records
	if ($prevoffset < 0)
	    $template['topPrev']->update(null);
	if ($nextoffset >= $totalcount)
	    $template['topNext']->update(null);
    $last           	= min($nextoffset, $totalcount);
    $template->set('FIRST',         $offset + 1);
    $template->set('LAST',          $last);
    $template->set('PREVOFFSET',    $prevoffset);
    $template->set('NEXTOFFSET',    $nextoffset);
    $template->set('TOTALCOUNT',    $formatter->format($totalcount));

    $dataRow                = $template['dataRow'];
    $dataRowText            = $dataRow->outerHTML();
    $data                   = '';
    $class                  = 'odd';

	// display the results
	foreach($list as $idsx => $citation)
    {
        $rtemplate          = new Template($dataRowText);
	    $idime	            = $citation->get('idime'); 
        $type	            = $citation->get('type');

	    // get the appropriate object instance
	    switch($type)
	    {
			case Citation::STYPE_UNSPECIFIED:
			case Citation::STYPE_NAME:
			case Citation::STYPE_BIRTH:
			case Citation::STYPE_CHRISTEN:
			case Citation::STYPE_DEATH:
			case Citation::STYPE_BURIED:
			case Citation::STYPE_NOTESGENERAL:
			case Citation::STYPE_NOTESRESEARCH:
			case Citation::STYPE_NOTESMEDICAL:
			case Citation::STYPE_DEATHCAUSE:
			case Citation::STYPE_LDSB:
			case Citation::STYPE_LDSE:
			case Citation::STYPE_LDSC:
			case Citation::STYPE_LDSI:
			{
			    $record	= new Person(array('idir' => $idime));
			    $href	= "Person.php?idir=$idime";
			    break;
			}		// instance of Person

/********************************************************************
 *		IDIME points to Alternate Name Record tblNX						*
 ********************************************************************/
			case Citation::STYPE_ALTNAME:
			{
			    $record	= new Name(array('idnx' => $idime));
			    $href	= "getRecordXml.php?idnx=" . $idime;
			    break;
			}		// instance of Name

/********************************************************************
 *		IDIME points to Child Record tblCR.IDCR								*
 ********************************************************************/
			case Citation::STYPE_CHILDSTATUS:
			case Citation::STYPE_CPRELDAD:
			case Citation::STYPE_CPRELMOM:
			case Citation::STYPE_LDSP:
			{
			    $record	= new Child(array('idcr' => $idime));
			    $href	= "getRecordXml.php?idcr=$idime";
			    break;
			}		// instance of Child

/********************************************************************
 *		IDIME points to Marriage Record tblMR.idmr						*
 ********************************************************************/
			case Citation::STYPE_LDSS:
			case Citation::STYPE_NEVERMARRIED:
			case Citation::STYPE_MAR:
			case Citation::STYPE_MARNOTE:
			case Citation::STYPE_MARNEVER:
			case Citation::STYPE_MARNOKIDS:
			case Citation::STYPE_MAREND:
			{
			    $record	= new Family(array('idmr' => $idime));
			    $href	= "editMarriages.php?idmr=$idime";
			    break;
			}		// instance of Family

/********************************************************************
 *		IDIME points to Event Record tblER.ider								*
 ********************************************************************/
			case Citation::STYPE_EVENT:
			case Citation::STYPE_MAREVENT:
			{
			    $record	= new Event(array('ider' => $idime));
			    $href	= "editEvent.php?type=$type&amp;ider=$idime";
			    break;
			}		// instance of Event

/********************************************************************
 *  IDIME points to To-Do records tblTD.IDTD						*
 ********************************************************************/
			case Citation::STYPE_TODO:
			{
			    $record	= new ToDo(array('idtd' => $idime));
			    $href	= "getRecordXml.php?idtd=$idime";
			    break;
			}		// instance of To Do
        }			// act on specific types
        $href           .= "&amp;lang=$lang";
        $page	        = $citation->get('srcdetail');
        $name           = $record->getName();
        $rtemplate['dataRow']->update(array('page'      => $page,
                                            'href'      => $href,
                                            'name'      => $name,
                                            'idime'     => $idime,
                                            'type'      => $type,
                                            'idsx'      => $idsx,
                                            'class'     => $class));
        $data           .= $rtemplate->compile();

        if ($class == 'odd')
            $class      = 'even';
        else
            $class      = 'odd';
    }	// loop through results

    $dataRow->update($data);
}
else
{
    $template['topBrowse']->update(null);
    $template['dataTable']->update(null);
}

$template->display();
