<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \NumberFormatter;
/************************************************************************
 *  Location.php														*
 *																	    *
 *  Display a web page containing details of an particular Location		*
 *  from the Legacy database.  If the current user is authorized to		*
 *  edit the database, this web page supports that.						*
 *																	    *
 *  Parameters:															*
 *		idlr			Unique numeric identifier of the location.	    *
 *						For backwards compatibility this can be			*
 *						specified using the 'id' parameter.				*
 *		name			specify name of location to display			    *
 *						Primarily for creation of a new record			*
 *		feedback        name of field in calling page to update         *
 *		closeAtEnd		If set to 'y' or 'Y' then when the location		*
 *						has been updated, leave the frame blank			*
 *																	    *
 *  History:															*
 *		2010/08/22		use new layout									*
 *						escape strings with quotes						*
 *		2010/09/25		Add merge duplicates function					*
 *						Support idlr= parameter							*
 *		2010/09/27		Add feature to display individuals using		*
 *						location										*
 *		2010/09/30		Add 'Used' field								*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/04		add help page									*
 *		2010/12/21		handle exception thrown by new LegacyLocation	*
 *						improve error reporting							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/09/26		improve separation of HTML and Javascript		*
 *						add some Google Maps support					*
 *		2011/10/24		fix Google Maps supportWellington				*
 *						add additional help bubbles						*
 *						convert "Display Individuals using this			*
 *						Location" to a <button>							*
 *		2011/11/12		add button to obtain info from the Google Map	*
 *						add support for recording zoom level for the map*
 *						add additional documentation of map display		*
 *						feature											*
 *		2011/11/17		suppress all functionality if errors detected	*
 *		2012/01/13		change class names								*
 *		2012/03/08		templatize replacement of button text			*
 *		2012/05/06		change button types to 'button' from default	*
 *						set appropriate class for each					*
 *						<input type='text'>								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/08/18		do not use browser capitalization on input		*
 *						fields											*
 *		2012/11/05		add support for tinyMCE editing of notes		*
 *		2012/11/08		do not double expand Short Name field value		*
 *		2013/02/22		do not capitalize preposition					*
 *						change presentation of some fields if			*
 *						not editing										*
 *						correct minor HTML errors						*
 *		2013/04/12		make boundary string available to javascript	*
 *		2013/04/16		do not capitalize latitude and longitude		*
 *		2013/04/23		grey out readonly input fields					*
 *						support "Hide Map" button overlaying map		*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/04/26		add pattern to locations link in top and bottom	*
 *		2013/04/27		add help text for Boundary field				*
 *		2013/05/18		support display of location by name				*
 *						support creation of new location by name		*
 *		2013/05/23		include IDLR in e-mail subject line				*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *		2013/06/13		add non-breaking space to Short Name label		*
 *						compress the form horizontally					*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/02/12		use CSS instead of tables for form layout		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/19		use LegacyCitation::getCitations to get list	*
 *						of duplicates of current location				*
 *		2014/09/29		limit displayed values of latitude and			*
 *						longitude to 6 decimal places (1m precision)	*
 *		2014/10/01		use method isOwner to determine if current		*
 *						user can update a location						*
 *		2014/10/05		add support for associating instances of		*
 *						LegacyPicture with a location					*
 *						display any associated media files				*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/05/18		do not escape contents of textarea, it may		*
 *						contain HTML tags from rich-text editor			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/30		add option to close the frame after update		*
 *		2016/01/19		add id to debug trace							*
 *		2016/03/16		use https to load googleapis					*
 *		2016/04/05		simplify creating new location					*
 *		2016/05/31		pass debug flag to updateLocation.php			*
 *		2016/12/09		determine geocoder search parm					*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/09/07		add "Close" button to update					*
 *		2017/09/09		rename to Location.php							*
 *		2017/09/12		use get( and set(								*
 *		2017/11/04		use RecordSet in place of getLocations			*
 *		2018/01/25		do not fail if lang parameter passed			*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/02      pass authentication key to GoogleApis           *
 *		2018/11/19      only display coordinates to 6 decimal places    *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/04/10      support county names containing an &            *
 *		2019/11/02      use Location to get search name and county      *
 *      2019/11/17      move CSS to <head>                              *
 *		2020/01/22      internationalize numbers                        *
 *		2020/02/14      add loose check for "name, .*, county..."       *
 *		2020/06/30      add feedback parameter                          *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *																	    *
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/CountySet.inc';
require_once __NAMESPACE__ . '/TownshipSet.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// action depends upon whether the user is authorized to update
// this specific record
if (canUser('edit'))
    $action     			= 'Update';
else
    $action     			= 'Display';

// default values of parametets
$namestart					= '';
$idlr		    			= 0;		// default to create new
$idlrtext                   = null;
$name		    			= '';
$feedback                   = null;
$closeAtEnd					= false;
$lang		    			= 'en';

// get requested parameter values
foreach($_GET as $key => $value)
{		        	// loop through all parameters
	switch(strtolower($key))
	{		        // act on specific parameters
	    case 'idlr':
	    case 'id':
	    {           // numeric key of location
			if (strlen($value) > 0 &&
			    ctype_digit($value))
                $idlr	    = $value;
            else
                $idlrtext   = htmlspecialchars($value);
			break;
	    }           // numeric key of location

	    case 'name':
	    {           // name of location
			$idlr		    = 0;
			$name		    = htmlspecialchars($value);
			break;
	    }           // name of location

	    case 'feedback':
	    {           // name of feedback field in calling page
			$feedback	    = htmlspecialchars($value);
			break;
	    }           // name of location

	    case 'lang':
	    {           // user's preferred language
			$lang	        = FtTemplate::validateLang($value);
			break;
	    }           // user's preferred language

	    case 'action':
	    {		    // request to only display the record
			if (strtolower($value) == 'display')
			    $action	    = 'Display';
			break;
	    }		    // request to only display the record

	    case 'closeatend':
	    {		    // close the frame when finished
			if (strtolower($value) == 'y')
			    $closeAtEnd	= true;
			break;
	    }		    // close the frame when finished

	    case 'debug':
	    case 'text':
	    {		    // handled by common code
			break;
	    }		    // handled by common code

	    default:
	    {
            $warn	.= "<p>Location.php: Unexpected parameter $key='" .
                        htmlspecialchars($value) . "'</p>\n";
			break;
	    }
	}		        // act on specific parameters
}			        // loop through all parameters

// get the requested location
if ($idlr > 0)
{                   // IDLR of existing record specified
    $location		        = new Location(array('idlr' => $idlr));
    $name			        = $location->getName();
}                   // IDLR of existing record specified
else
{                   // IDLR not specified
    $location		        = new Location(array('location' => $name));
}                   // IDLR not specified

if (!$location->isOwner())
    $action     	        = 'Display';

// get template
$template			        = new FtTemplate("Location$action$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'Location'));
$formatter                  = $template->getFormatter();

// customize title
if ($location->isExisting())
{
	$idlr	    	        = $location->getIdlr();
    $title	    	        = $template['locationTitle']->innerHTML();
}
else
if ($idlr == 0)
{                   // search by name
    $comma                  = strpos($name, ', ');
    $search                 = '^' . substr($name, 0, $comma) . ',.*' . 
                              substr($name, $comma + 2);
    $locations              = new RecordSet('Locations',
                                            array('location' => $search));
    if ($locations->count() == 1)
    {
        $location           = $locations->rewind();
        $idlr               = $location['idlr'];
        $name               = $location->getName();
        $title	    	    = $template['locationTitle']->innerHTML();
    }
    else
    {
	    $idlr	    	    = 0;
        $title	    	    = $template['newlocationTitle']->innerHTML();
    }
}                   // search by name
else
{                   // not found
	$idlr	    	        = 0;
    $title	    	        = $template['newlocationTitle']->innerHTML();
}                   // not found
$title          	= str_replace('$NAME', htmlspecialchars($name), $title);
$template->set('TITLE',             $title);

// set up values for displaying in form
$template->set('IDLR',	            $idlr);
$template->set('FEEDBACK',	        $feedback);
$locname			= $location->get('location');
$template->set('LOCATION',		    str_replace('"','&quote;',$locname));
$shortName			= $location->getShortName();
$template->set('SHORTNAME',		    str_replace('"','&quote;',$shortName));
$sortedLoc			= $location->get('sortedlocation');
$template->set('SORTEDLOC',		    str_replace('"','&quote;',$sortedLoc));
$fsPlaceId			= $location->get('fsplaceid'); 
$template->set('FSPLACEID',		    str_replace('"','&quote;',$fsPlaceId));
$template->set('PREPOSITION',	    $location->get('preposition'));
$template->set('BOUNDARY',		    $location->get('boundary'));
$formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 6);
$template->set('LATITUDE',		    $formatter->format($location->get('latitude')));
$template->set('LONGITUDE',		    $formatter->format($location->get('longitude')));
$template->set('ZOOM',		        $location->get('zoom'));
$template->set('NOTES',		        $location->get('notes'));
if ($closeAtEnd)
    $template->set('CLOSE',		    'y');
else
    $template->set('CLOSE',		    'n');

// update breadcrumbs depending upon location name
if (strlen($name) > 5)
    $namestart	    = substr($name, 0, 5);
else
    $namestart	    = $name;
if (strlen($namestart) == 0)
	$template->updateTag('nameStart', null);
else
    $template->set('NAMESTART',     htmlspecialchars($namestart));

// set up selection lists on form
$fsresolved		    = $location->get('fsresolved');
for ($i = 0;$i <= 2; $i++)
{
    if ($i == $fsresolved)
        $template->set("FSRESOLVED{$i}SELECTED", 'selected="selected"');
    else
        $template->set("FSRESOLVED{$i}SELECTED", '');
}
$veresolved		    = $location->get('veresolved');
for ($i = 0;$i <= 2; $i++)
{
    if ($i == $veresolved)
        $template->set("VERESOLVED{$i}SELECTED", 'selected="selected"');
    else
        $template->set("VERESOLVED{$i}SELECTED", '');
}

// handled checkboxes
if ($location->get('used'))
    $template->set('USEDCHECKED', 'checked="checked"');
else
    $template->set('USEDCHECKED', '');
if ($location->get('tag1'))
    $template->set('TAG1CHECKED', 'checked="checked"');
else
    $template->set('TAG1CHECKED', '');
if ($location->get('verified'))
    $template->set('VERIFIEDCHECKED', 'checked="checked"');
else
    $template->set('VERIFIEDCHECKED', '');
if ($location->get('qstag'))
    $template->set('QSCHECKED', 'checked="checked"');
else
    $template->set('QSCHECKED', '');

// handle idiosyncracies of Google geocoder implementation
$searchName	    	= $location['searchname'];
$county		        = $location['county'];

$template->set('SEARCHNAME',	str_replace('"', '&quote;', $searchName));
$template->set('COUNTY',		str_replace('"', '&quote;',
                                    str_replace('&', '&amp;', $county)));

// display duplicate entries if any
$duplicateRow           = $template->getElementById('duplicateRow');
if ($duplicateRow)
{                   // template requests display of duplicates
    $duplicateLabel     = $template->getElementById('duplicateLabel');
    $mergeRow           = $template->getElementById('mergeRow');

	// check for duplicates of this location
	$getParms		    = array('idlr'		    => '>' . $idlr,
			            		'Location'		=> "^$name$");
	$dupResult		    = new RecordSet('Locations', $getParms);
	$info			    = $dupResult->getInformation();
    $dupQuery		    = $info['query'];
    if (count($dupResult) > 0)
    {			    // there are duplicates of current location
        $text       = $duplicateRow->outerHTML();
        $result     = '';
	    foreach($dupResult as $dupidlr => $duplicate)
        {		    // rows that duplicate current row
            $rtemplate   = new Template($text);
            $rtemplate->set('DUPIDLR',       $dupidlr);
            $result     .= $rtemplate->compile();
        }		    // rows that duplicate current row
        $duplicateRow->update($result);
    }		        // there are duplicates of current location
    else
    {
        $duplicateLabel->update(null);
        $duplicateRow->update(null);
        $mergeRow->update(null);
    }
}                    // template requests display of duplicates

// display any media files associated with the location
$picParms	        = array('idir'		=> $idlr,
					        'idtype'	=> Picture::IDTYPELocation);
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
