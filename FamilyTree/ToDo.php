<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  ToDo.php														    *
 *																	    *
 *  Display a web page containing details of an particular ToDo		    *
 *  from the Legacy database.  If the current user is authorized to		*
 *  edit the database, this web page supports that.						*
 *																	    *
 *  Parameters:															*
 *		idtd			Unique numeric identifier of the todo.	        *
 *						For backwards compatibility this can be			*
 *						specified using the 'id' parameter.				*
 *		idir    		Unique numeric identifier of the Person.	    *
 *		name			specify name of todo to display			        *
 *						Primarily for creation of a new record			*
 *		closeAtEnd		If set to 'y' or 'Y' then when the todo		    *
 *						has been updated, leave the frame blank			*
 *																	    *
 *  History:															*
 *		2019/08/13      created                                         *
 *																	    *
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// action depends upon whether the user is authorized to update
// this specific record
if (canUser('edit'))
    $action     				= 'Update';
else
    $action     				= 'Display';

// default values of parametets
$namestart						= '';
$idtd		    				= 0;		// default to create new
$idir		    				= 0;		// default to create new
$name		    				= '';
$todotype	    			    = null;
$idtc	        			    = null;
$idtl	        			    = null;
$location	        			= null;
$todoname           			= null;
$openeddate         			= null;
$reminderddate      			= null;
$closeddate         			= null;
$idar	        			    = null;
$status	        			    = null;
$priority	    			    = null;
$desc               			= null;
$results            			= null;
$filingref          			= null;
$tag1	        			    = null;
$qstag	        			    = null;
$used	        			    = null;
			
$closeAtEnd						= false;
$lang		    			    = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{		        	// loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		        // act on specific parameters
		    case 'idtd':
		    case 'id':
		    {           // numeric key of todo
				if (ctype_digit($value))
				    $idtd	        = $value;
				break;
		    }           // numeric key of todo
	
		    case 'idir':
		    {           // numeric key of Person
				if (ctype_digit($value))
				    $idir	        = $value;
				break;
	        }           // numeric key of Person
	
		    case 'name':
		    {           // name of todo
				$idtd		        = 0;
				$name		        = $value;
				break;
		    }           // name of todo
	
		    case 'lang':
		    {           // user's preferred language
				$lang	            = FtTemplate::validateLang($value);
				break;
		    }           // user's preferred language
	
		    case 'action':
		    {		    // request to only display the record
				if (strtolower($value) == 'display')
				    $action	        = 'Display';
				break;
		    }		    // request to only display the record
	
		    case 'closeatend':
		    {		    // close the frame when finished
				if (strtolower($value) == 'y')
				    $closeAtEnd	    = true;
				break;
		    }		    // close the frame when finished
	
		    case 'debug':
		    case 'text':
		    {		    // handled by common code
				break;
		    }		    // handled by common code
	
		    default:
		    {
				$warn	.= "<p>ToDo.php: Unexpected parameter $key='$value'</p>\n";
				break;
		    }
		}		        // act on specific parameters
	}			        // loop through all parameters
}	        	    // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{		            // invoked by submit to update todo item
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'idtd':
			{
				if (ctype_digit($value))
				    $idtd	        = $value;
				break;
			}

			case 'todotype':
			{
				if (ctype_digit($value))
				    $todotype	    = $value;
				break;
			}

			case 'idir':
			{
				if (ctype_digit($value))
				    $idir	        = $value;
				break;
			}

			case 'idtc':
			{
				if (ctype_digit($value))
				    $idtc	        = $value;
				break;
			}

			case 'idtl':
			{
				if (ctype_digit($value))
				    $idtl	        = $value;
				break;
			}

			case 'location':
			{
				$location	        = $value;
				break;
			}

			case 'todoname':
			{
                $todoname           = $value;
				break;
			}

			case 'openeddate':
			{
                $openeddate         = $value;
				break;
			}

			case 'reminderdate':
			{
                $reminderddate      = $value;
				break;
			}

			case 'closeeddate':
            {
                $closeddate         = $value;
				break;
			}

			case 'idar':
			{
				if (ctype_digit($value))
				    $idar	        = $value;
				break;
			}

			case 'status':
			{
				if (ctype_digit($value))
				    $status	        = $value;
				break;
			}

			case 'priority':
			{
				if (ctype_digit($value))
				    $priority	    = $value;
				break;
			}

			case 'desc':
			{
				$desc               = $value;
				break;
			}

			case 'results':
			{
				$results            = $value;
				break;
			}

			case 'filingref':
			{
				$filingref          = $value;
				break;
			}

			case 'tag1':
			{
				if (ctype_digit($value))
				    $tag1	        = $value;
				break;
			}

			case 'qstag':
			{
				if (ctype_digit($value))
				    $qstag	        = $value;
				break;
			}

			case 'used':
			{
				if (ctype_digit($value))
				    $used	        = $value;
				break;
			}

			case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value,0,2));
                break;
            }
	    }		    // act on specific parameter
    }	            // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		            // invoked by submit to update account


// get the requested todo
if ($idtd > 0)
{                   // IDTD of existing record specified
    $todo		    = new ToDo(array('idtd' => $idtd));
    $idir			= $todo['idir'];
    $name			= $todo->getName();
}                   // IDTD of existing record specified
else
{                   // IDTD not specified, create new
    $todo		    = new ToDo(array('idir' => $idir));
}                   // IDTD not specified

if ($todo->isExisting() && !$todo->isOwner())
    $action     	= 'Display';

if ($action == 'Update')
{
	if ($todotype !== null)
		$todo['todotype']			= $todotype;
	if ($idtc !== null)
		$todo['idtc']			    = $idtc;
    if ($location !== null)
    {
        $locobj                 = new Location(array('location' => $location));
        print "<p>$msg</p>\n";
        if (!$locobj->isExisting())
            $locobj->save(false);
        $idtl                       = $locobj->getIdlr();
		$todo['idtl']			    = $idtl;
    }
	if ($todoname !== null)
		$todo['todoname']			= $todoname;
	if ($openeddate !== null)
		$todo['openedd']			= $openeddate;
	if ($reminderddate !== null)
		$todo['reminderdd']		    = $reminderddate;
	if ($closeddate !== null)
		$todo['closedd']			= $closeddate;
	if ($idar !== null)
		$todo['idar']			    = $idar;
	if ($status !== null)
		$todo['status']			    = $status;
	if ($priority !== null)
		$todo['priority']			= $priority;
	if ($desc !== null)
		$todo['desc']			    = $desc;
	if ($results !== null)
		$todo['results']			= $results;
	if ($filingref !== null)
		$todo['filingref']			= $filingref;
	if ($tag1 !== null)
		$todo['tag1']			    = $tag1;
	if ($qstag !== null)
		$todo['qstag']			    = $qstag;
	if ($used !== null)
        $todo['used']			    = $used;

    $todo->save(false);
    $idtd                           = $todo['idtd'];
}               // update record

// get template
$template			                = new FtTemplate("ToDo$action$lang.html");
$tranTab                            = $template->getTranslate();
$tr                                 = $tranTab['tranTab'];

// customize title
if ($todo->isExisting())
{
	$idtd	    	                = $todo['idtd'];
}

// set up values for displaying in form
$template->set('IDTD',	            $idtd);
for($td = 0; $td <= 2; $td++)
{
    if ($td == $idtd)
        $template->set("TDSELECTED$td",		'selected="selected"');
    else
        $template->set("TDSELECTED$td",		'');
}
$template->set('IDIR',	            $idir);
if ($idir > 0)
{
    $person             		= Person::getPerson($idir);
    $personname         		= $person->getName($tr);   
}
else
    $personname                 = $template['General']->innerHTML;

$template->set('PERSONNAME',		$personname);
$todotype						= $todo['todotype'];
$template->set('TODOTYPE',			$todotype);
$idtc			    			= $todo['idtc'];
$template->set('IDTC',			    $idtc);
for($tc = 0; $tc <= 20; $tc++)
{
    if ($tc == $idtc)
        $template->set("TCSELECTED$tc",		'selected="selected"');
    else
        $template->set("TCSELECTED$tc",		'');
}
$idtl			    			= $todo['idtl'];
$template->set('IDTL',			    $idtl);
$location                       = Location::getLocation($idtl);
$template->set('LOCATION',			$location->getName());
$todoname						= $todo['todoname'];
$template->set('TODONAME',			$todoname);
$openedd						= $todo['openedd'];
$date		                    = new LegacyDate($openedd);
$template->set('OPENEDDATE',		$date->toString(9999, false, $tr));
$reminderd						= $todo['reminderd'];
$date		                    = new LegacyDate($reminderd);
$template->set('REMINDERDATE',		$date->toString(9999, false, $tr));
$closedd						= $todo['closedd'];
$date		                    = new LegacyDate($closedd);
$template->set('CLOSEDDATE',		$date->toString(9999, false, $tr));
$idar			    			= $todo['idar'];
$template->set('IDAR',			    $idar);
$status			    			= $todo['status'];
$template->set('STATUS',			$status);
if ($status)
    $template->set('STATUSCHECKED',	'checked="checked"');
for($st = 0; $st <= 1; $st++)
{
    if ($st == $status)
        $template->set("STSELECTED$st",		'selected="selected"');
    else
        $template->set("STSELECTED$st",		'');
}
$priority						= $todo['priority'];
$template->set('PRIORITY',			$priority);
for($pr = 0; $pr <= 2; $pr++)
{
    if ($pr == $priority)
        $template->set("STSELECTED$pr",		'selected="selected"');
    else
        $template->set("STSELECTED$pr",		'');
}
$desc			    			= $todo['desc'];
$template->set('DESC',			    $desc);
$results						= $todo['results'];
$template->set('RESULTS',			$results);
$filingref						= $todo['filingref'];
$template->set('FILINGREF',			$filingref);
$tag1			    			= $todo['tag1'];
$template->set('TAG1',			    $tag1);
if ($tag1)
    $template->set('TAG1CHECKED',   'checked="checked"');
else
    $template->set('TAG1CHECKED',   '');
$qstag			    			= $todo['qstag'];
$template->set('QSTAG',			    $qstag);
if ($qstag)
    $template->set('QSTAGCHECKED',  'checked="checked"');
else
    $template->set('QSTAGCHECKED',  '');
$used			    			= $todo['used'];
$template->set('USED',			    $used);
if ($used)
    $template->set('USEDCHECKED',   'checked="checked"');
else
    $template->set('USEDCHECKED',   '');

if ($closeAtEnd)
    $template->set('CLOSE',		    'y');
else
    $template->set('CLOSE',		    'n');

// display any media files associated with the todo
$picParms	        = array('idir'		=> $idtd,
					        'idtype'	=> Picture::IDTYPEToDo);
$picList	        = new RecordSet('Pictures', $picParms);
$element            = $template->getElementById('templates');
if (is_null($element))
    $template->getDocument()->printTag(0);
$tempText           = $element->innerHTML();
$pictures           = '';
foreach($picList as $idbr => $picture)
{		// loop through all pictures
    $pictures       .= $picture->toHtml($tempText);	// display the picture
}		// loop through all pictures
$template->set('PICTURES',  $pictures);

// if user requested to close the page automatically
if ($closeAtEnd)
{
    $template->updateTag('Close', null);
}

$template->display();
