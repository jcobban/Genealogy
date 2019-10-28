<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editCitation.php													*
 * 																		*
 *  Display a web page for editting a specific citation to a source.	*
 * 																		*
 * 	Parameters:															*
 * 		idsx            key of existing Citation						*
 * 		idime           event record to create new Citation             *
 * 		type            event type to create new Citation               *
 * 																		*
 *  History: 															*
 * 		2010/08/21		Change to use new page format					*
 *		2010/09/05		Eliminate space characters from <textarea>s		*
 *		2010/10/15		Remove header and trailer from dialogs.			*
 *						Add field level help information.				*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/04		generate common HTML header tailored to browser	*
 *		2011/01/10		use LegacyRecord::getField method				*
 *						handle exception from new Citation				*
 *		2011/01/28		do no permit modifying type						*
 *		2011/02/27		improve separation of HTML and Javascript		*
 *		2012/01/13		change class names								*
 *		2013/03/31		clean up parameter handling						*
 *		2013/09/20		add help text for update button					*
 *						correct class of text field for citation detail	*
 *						display event type as simple text, not select	*
 *						pretty up the generated HTML 					*
 *						add testing option								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/06		label class name changed to column1				*
 *						add labels on prompts for source and page		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/10/05		add support for associating instances of		*
 *						Picture with a Citation							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/03/08		class Citation no longer has method				*
 *						getTitle										*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/28      use Template                                    *
 *		2019/07/30      use $record->selected                           *
 * 																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// locate the required citation based upon the parameters passed
// to the page
$idsx		            = null;
$idsxtext	            = 'missing';
$idime                  = null;
$type                   = null;
$citation		        = null;
$lang                   = 'en';

// get parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{			// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific parameter
		    case 'idsx':
		    {		// identifier of instance of Citation
				if (ctype_digit($value))
	                $idsx	        = (int)$value;
	            else
	                $idsxtext       = $value;
				break;
		    }		// identifier of instance of Citation

            case 'idime':
            {
				if (ctype_digit($value))
	                $idime	        = (int)$value;
				break;
            }

            case 'type':
            {
				if (ctype_digit($value))
	                $type	        = (int)$value;
                break;
            }

	        case 'lang':
	        {
	            $lang               = FtTemplate::validateLang($value);
	            break;
	        }
	
		    case 'tableid':		// table id in invoking page
		    case 'formid':		// form id in invoking page
		    case 'submit':		// submit button
		    case 'text':		// used by Javascript
		    case 'debug':       // handled by common code
		    {
				break;
		    }
	
		    default:
		    {		// anything else
				$warn	        .= "<p>Unexpected parameter $key='$value'.</p>";
				break;
		    }		// anything else
		}		// act on specific parameter
	}			// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

if (canUser('edit'))
    $action         = 'Update';
else
    $action         = 'Display';

$template           = new FtTemplate("editCitation$action$lang.html");
$translate          = $template->getTranslate();
$citText            = $translate['typeText'];

if (is_null($idsx))
    $citation	    = new Citation(array('idime'    => $idime,
                                         'type'     => $type));
else
    $citation	    = new Citation(array('idsx'     => $idsx));

$citmsg             = $citation->getErrors();
if (strlen($citmsg) == 0)
{
    $template->set('IDSX',              $idsx);
    $template->set('IDIME',             $citation['idime']);
	$citType	    = $citation['type'];
    $template->set('CITTYPE',           $citType);
    $typeText	    = $citText[$citType];
    $template->set('CITTYPETEXT',       $typeText);
    $name           = $citation->getRecord()->getName() . ', ' . $typeText;
    $template->set('NAME',              $name);
	$key		    = Citation::$recType[$citation->getCitType()];
    $template->set('CITKEY',            $key);
	$template->set('DETAIL',			$citation['srcdetail']); 
	$template->set('DETAILTEXT',		$citation['srcdettext']);
    $template->set('DETAILNOTE',		$citation['srcdetnote']);
    $sources                        = new RecordSet('Sources');
    $citidsr                        = $citation['idsr'];
    $template->set('IDSR',              $citidsr);
    $sources[$citidsr]->selected    = true;
    $template['source$idsr']->update($sources);
}
else
{
    $name           = "Invalid";
    $template['citForm']->update(null);
}

$template->display();
