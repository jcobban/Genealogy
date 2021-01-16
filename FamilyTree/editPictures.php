<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editPictures.php													*
 *																		*
 *  Display a web page for editing the pictures of a particular 		*
 *  record in the genealogy database									*
 * 																		*
 *  Parameters (passed by method=get) 									*
 *		idir			unique numeric key of Person, or			    *
 *		idmr			unique numeric key of Family, or			    *
 *		ider			unique numeric key of Event, or					*
 *		idsr			unique numeric key of Source, or				*
 *		idsx			unique numeric key of Citation, or				*
 *		idtd			unique numeric key of ToDo, or					*
 *		idar			unique numeric key of Address, or				*
 *		idlr			unique numeric key of Location, or				*
 *		idtr			unique numeric key of Temple, or				*
 *		idtype			type of record, only required for idir			*
 *																		*
 *  History:															*
 *		2011/05/26		created											*
 *		2012/01/13		change class names								*
 *		2013/02/24		use standard presentation classes				*
 *						field names use IDBR, not rownum				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *						fully support all associated record types		*
 *		2014/10/03		support both numeric and textual identifiers	*
 *		2014/10/05		prompt for confirmation of delete				*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/03/07		use getName to get identification of all records*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/08/04		class LegacyToDo renamed to ToDo				*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/17		use class RecordSet								*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/Temple.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the record identifier
$idir               = null;
$idirtext           = '';
$idtypetxt          = 'Indiv';
$lang               = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
	        case 'idir':
            {            // idir reference to Person
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            break;
	        }            // idir reference to Person
	
	        case 'idmr':
	        {            // idir reference to Family
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Marriage';
	            break;
	        }            // idir reference to Family
	
	        case 'ider':
	        {            // idir reference to Event
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Event';
	            break;
	        }            // idir reference to Event
	
	        case 'idsr':
	        {            // idir reference to Source
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Source';
	            break;
	        }            // idir reference to Source
	
	        case 'idsx':
	        {            // idir reference to Citation
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Citation';
	            break;
	        }            // idir reference to Citation
	
	        case 'idtd':
	        {            // idir reference to ToDo
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'To Do';
	            break;
	        }            // idir reference to ToDo
	
	        case 'idar':
	        {            // idir reference to Address
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Address';
	            break;
	        }            // idir reference to Address
	
	        case 'idlr':
	        {            // idir reference to Location
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Location';
	            break;
	        }            // idir reference to Location
	
	        case 'idtr':
	        {            // idir reference to Temple
                if (is_int($value) || ctype_digit($value))
                    $idir   		    = (int)$value;
                else
                    $idirtext           = htmlspecialchars($value);
	            $idtypetxt   		    = 'Temple';
	            break;
	        }            // idir reference to Temple
	
	        case 'idtype':
	        {            // type identifier
	            $idtypetxt   		    = htmlspecialchars($value);
	            break;
            }            // type identifier

            case 'lang':
            {
                $lang                   = FtTemplate::validateLang($value);
            }
	    }            // act on specific keys
	}                // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

if (canUser('edit'))
    $action             = 'Update';
else
    $action             = 'Display';

$template               = new FtTemplate("editPictures$action$lang.html");

$template->set('LANG',              $lang);
if ($debug)
    $template->set('DEBUG',         'Y');
else
    $template->set('DEBUG',         'N');

// set the numeric record type based upon the parameter
// and initialize the appropriate record pointer
$record    = null;

switch(strtolower($idtypetxt))
{
    case '0':            // 0  Person    tblIR.IDIR
    case 'indiv':        // 0  Person    tblIR.IDIR
    {
        $idtype       	= Picture::IDTYPEPerson;
        $record       	= new Person(array('idir' => $idir));
        $name       	= "";
        $script         = 'Person';
        $key            = 'idir';
        $idime          = $idir;
        break;
    }

    case '1':
    case 'birth':        // 1  Birth         tblIR.IDIR
    {
        $idtype       	= Picture::IDTYPEBirth;
        $record       	= new Person(array('idir' => $idir));
        $name       	= "Birth of ";
        $script         = 'Person';
        $key            = 'idir';
        $idime          = $idir;
        break;
    }

    case '2':
    case 'chris':        // 2  Chr        tblIR.IDIR
    {
        $idtype       	= Picture::IDTYPEChris;
        $record       	= new Person(array('idir' => $idir));
        $name       	= "Christening of ";
        $script         = 'Person';
        $key            = 'idir';
        $idime          = $idir;
        break;
    }

    case '3':
    case 'death':        // 3  Death        tblIR.IDIR
    {
        $idtype       	= Picture::IDTYPEDeath;
        $record       	= new Person(array('idir' => $idir));
        $name       	= "Death of ";
        $script         = 'Person';
        $key            = 'idir';
        $idime          = $idir;
        break;
    }

    case '4':
    case 'buried':        // 4  Buried        tblIR.IDIR
    {
        $idtype       	= Picture::IDTYPEBuried;
        $record       	= new Person(array('idir' => $idir));
        $name       	= "Burial of ";
        $script         = 'Person';
        $key            = 'idir';
        $idime          = $idir;
        break;
    }

    case '20':
    case 'mar':            // 20 Marriage        tblMR.IDMR
    {
        $idtype       	= Picture::IDTYPEMar;
        $record       	= new Family(array('idmr' => $idir));
        $name       	= "Marriage of ";
        $script         = 'Person';
        $key            = 'idir';
        $idime          = $record['idirhusb'];
        if ($idime == 0)
            $idime      = $record['idirwife'];
        break;
    }

    case '30':
    case 'event':        // 30 Event        tblER.IDER
    {
        $idtype       	= Picture::IDTYPEEvent;
        $record       	= new Event(array('ider' => $idir));
        $name       	= "Event ";
        $script         = 'editEvent';
        $key            = 'ider';
        $idime          = $idir;
        break;
    }

    case '40':
    case 'srcmaster':        // 40 Master Source    tblSR.IDSR
    {
        $idtype       	= Picture::IDTYPESrcMaster;
        $record       	= new Source(array('idsr' => $idir));
        $name       	= "Master Source ";
        $script         = 'Source';
        $key            = 'idsr';
        $idime          = $idir;
        break;
    }

    case '41':
    case 'srcdetail':        // 41 Source Detail    tblSX.IDSX
    {
        $idtype       	= Picture::IDTYPESrcDetail;
        $record       	= new Citation(array('idsx' => $idir));
        $name       	= "Source Citation ";
        $script         = 'editCitation';
        $key            = 'idsx';
        $idime          = $idir;
        break;
    }

    case '50':
    case 'to do':        // 50 To Do        tblTD.IDTD
    {
        $idtype       	= Picture::IDTYPEToDo;
        $record       	= new ToDo(array('idtd' => $idir));
        $name       	= "To Do Item ";
        $script         = 'editEvent';  // not implemented
        $key            = 'idtd';
        $idime          = $idir;
        break;
    }

    case '70':
    case 'address':        // 70 Address        tblAR.IDAR
    {
        $idtype       	= Picture::IDTYPEAddress;
        $record       	= new Address(array('idar' => $idir));
        $name       	= "Address ";
        $script         = 'Address';
        $key            = 'idar';
        $idime          = $idir;
        break;
    }

    case '71':
    case 'location':        // 71 Location        tblLR.IDLR
    {
        $idtype       	= Picture::IDTYPELocation;
        $record       	= new Location(array('idlr' => $idir));
        $name       	= "Location ";
        $script         = 'Location';
        $key            = 'idlr';
        $idime          = $idir;
        break;
    }

    case '72':
    case 'temple':        // 72 Temple        tblTR.IDTR
    {
        $idtype       	= Picture::IDTYPETemple;
        $record       	= new Temple(array('idtr' => $idir));
        $name       	= "Temple ";
        $script         = 'Temple';
        $key            = 'idtr';
        $idime          = $idir;
        break;
    }

    default:
    {
        $msg            .= 'Invalid value of idtype=\'' . $idtypetxt . "'. ";
        $name           = "Unknown Type $idtypetxt"; 
    }        // default
}        // switch on idtype text

if (is_object($record))
{
    if ($record instanceof Person)
        $name           .=  $record->getName(Person::NAME_INCLUDE_DATES);
    else
        $name           .=  $record->getName();

    $template->set('NAME',          $name);
    $template->set('IDIR',          $idir);
    $template->set('IDTYPE',        $idtype);
    $template['reference']->update(array('script'           => $script,
                                         'key'              => $key,
                                         'idir'             => $idime,
                                         'name'             => $name));

	$picParms           = array('IDIR'          => $idir,
	                            'IDType'        => $idtype);
	$pictureSet         = new RecordSet('Pictures', $picParms);
	$rowElt             = $template['PictureRow$IDBR'];
	$rowHtml            = $rowElt->outerHTML();
	$data               = '';
	
	foreach($pictureSet as $idbr => $picture)
	{        // loop through pictures
	    $rtemplate  = new \Templating\Template($rowHtml);
	    $rtemplate->set('IDBR',         $idbr);
	    $rtemplate->set('PICTYPE',      $picture->getTypeText());
	    $rtemplate->set('DATE',         $picture->getDate());
	    $rtemplate->set('CAPTION',      $picture->getCaption()); 
	    $data           .= $rtemplate->compile();
	}
	$rowElt->update($data);
}
else
{
    $template->set('NAME',          'Invalid');
    $template['reference']->update(null);
    $template['picsForm']->update(null);
}

$template->display();
