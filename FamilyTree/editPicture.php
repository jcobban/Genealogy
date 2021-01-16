<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editPicture.php														*
 *																		*
 *  Display a web page for editting one picture for an					*
 *  record from the Legacy database which is represented				*
 *  by an instance of Picture (a record in table tblBR).				*
 *																		*
 *  Parameters (passed by method="get"):								*
 *		idbr	unique numeric key of instance of Picture				*
 *				if set to zero											*
 *				causes new Picture record to be created.				*
 *				if not available from record identified by idir 		*
 *		idtype	numeric type value as used by the Picture				*
 *				record to identify the record type and event a new		*
 *				picture is associated with								*
 *																		*
 *				IDTYPEPerson    = 0		tblIR.IDIR						*
 *				IDTYPEBirth     = 1		tblIR.IDIR						*
 *				IDTYPEChris     = 2		tblIR.IDIR						*
 *				IDTYPEDeath     = 3		tblIR.IDIR						*
 *				IDTYPEBuried    = 4		tblIR.IDIR						*
 *				IDTYPEMar       = 20	tblMR.IDMR						*
 *				IDTYPEEvent     = 30	tblER.IDER						*
 *				IDTYPESrcMaster = 40	tblSR.IDSR						*
 *				IDTYPESrcDetail = 41	tblSX.IDSX						*
 *				IDTYPEToDo      = 50	tblTD.IDTD						*
 *				IDTYPEAddress   = 70	tblAR.IDAR						*
 *				IDTYPELocation  = 71	tblLR.IDLR						*
 *				IDTYPETemple    = 72	tblTR.IDTR						*
 *																		*
 *		pictype		type of document									*
 *				PIC_TYPE_PICTURE= 0		image file						*
 *				PIC_TYPE_SOUND	= 1		sound file						*
 *				PIC_TYPE_VIDEO	= 2		video file						*
 *				PIC_TYPE_OTHER	= 3		other file						*
 *																		*
 *		idir	unique numeric key of instance of record as defined		*
 *				above to which a new image is associated				*
 * 																		*
 *  History: 															*
 *		2011/06/26		created											*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/12		replace tables with CSS for layout				*
 *		2014/03/06		label class name changed to column1				*
 *						for= attributes added to all labels				*
 *		2014/03/21		interface to Picture made more intuitive		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/09/29		support all associated record types				*
 *		2014/10/02		add prompt to confirm deletion					*
 *						improve titles for previously unused types		*
 *		2014/10/06		add support for audio and video files and for	*
 *						an audio caption on an image					*
 *			            improve parameter validation			        * 
 *		2014/11/29		do not reinitialize global variables set by		*
 *						common.inc										*
 *						print $warn, which may contain debug trace		*
 *		2015/05/18		do not escape contents of textarea, HTML is		*
 *						used by rich-text editor						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *						use preferred parameters for new LegacyFamily	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/08/15		class LegacyToDo renamed to ToDo				*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/07/26      use Template                                    *
 *		                merge update logic from updatePicture.php       *
 *      2019/11/17      move CSS to <head>                              *
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
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// safely get parameter values
// defaults
$idtype	                = Picture::IDTYPEPerson;		
$pictype	            = Picture::PIC_TYPE_PICTURE;
$idbr	                = null;
$idir	                = null;
$picture	            = null;
$record	                = null;
$setParms               = null;
$idbrtext               = null;
$idtypetext             = "missing";
$pictypetext            = "0";
$idirtext               = "missing";
$lang                   = 'en';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	                // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
	    switch(strtolower($key))
		{		        // act on specific parameter
		    case 'idbr':
		    {		    // the unique record identifier of existing record
				if (ctype_digit($value))
                    $idbr	        = (int)$value;
                else
                    $idbrtext       = htmlspecialchars($value);
				break;
		    }		    // the unique record identifier of existing record

		    case 'idtype':
		    {		// identifier of associated record type and event
				if (ctype_digit($value) &&
				    array_key_exists($value, Picture::$IdTypeNames))
                    $idtype	        = (int)$value;
                else
                    $idtypetext     = htmlspecialchars($value);
				break;
		    }		// identifier of associated record type and event

		    case 'pictype':
		    {		// type of media
				if (ctype_digit($value) && $value <= 3 &&
				    array_key_exists($value, Picture::$PicTypeNames))
                    $pictype	    = (int)$value;
                else
                    $pictypetext    = htmlspecialchars($value);
				break;
		    }		// type of media

		    case 'idir':
		    {		// key of associated database record
				if (ctype_digit($value) && $value > 0)
                    $idir	        = (int)$value;
                else
                    $idirtext       = htmlspecialchars($value);
				break;
		    }		// key of associated database record

			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }

		    case 'debug':
		    case 'text':
		    {		// handled by common code
				break;
		    }		// handled by common code

		    default:
		    {
	            $warn	.= "<p>editPicture.php: " . __LINE__ . 
                            " Unexpected parameter $key='" .
                            htmlspecialchars($value) . "'</p>\n";
				break;
		    }
		}		// switch
	}			// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display Picture
else
if (count($_POST) > 0)
{	        	    // invoked by method=post to update Picture
    $setParms       = array();

    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
    {	                // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
	    switch(strtolower($key))
		{		        // act on specific parameter
		    case 'idbr':
		    {			// identifier present
				if (is_int($value) || ctype_digit($value))
                {		// existing picture
                    $idbr                   = (int)$value;
				}		// existing picture
                else
                    $idbrtext       = htmlspecialchars($value);
				break;
		    }			// IDBR

		    case 'idir':
		    {			// reference to associated record
                if (is_int($value) || ctype_digit($value) && $value > 0)
                {
                    $idir	                = (int)$value;
                    $setParms['idir']       = $idir;
                }
                else
                    $idirtext       = htmlspecialchars($value);
				break;
		    }			// reference to associated record

		    case 'idtype':
		    {			// type of event the picture is associated with
				if ((is_int($value) || ctype_digit($value)) &&
                    array_key_exists($value, Picture::$IdTypeNames))
                {
                    $idtype	                = (int)$value;
                    $setParms['idtype']     = $idtype;
                }
                else
                    $idtypetext       = htmlspecialchars($value);
				break;
		    }			// type of event the picture is associated with

		    case 'pictype':
		    {		    // type of media
				if ((is_int($value) || ctype_digit($value)) && 
                    array_key_exists($value, Picture::$PicTypeNames))
                {
				    $pictype	            = (int)$value;
                    $setParms['pictype']    = $pictype;
                }
                else
                    $pictypetext       = htmlspecialchars($value);
				break;
		    }		    // type of media

			case 'picorder':
			case 'picname':
			case 'picnameurl':
			case 'idbppic':
			case 'piccaption':
			case 'picd':
			case 'picsd':
			case 'picdate':
			case 'picdesc':
			case 'picprint':
			case 'picsoundname':
			case 'picsoundnameurl':
			case 'idbpsound':
			case 'used':
			case 'picpref':
			case 'filingref':
			{		// update field values
			    $setParms[$key]         = $value;
			    break;
            }		// update field values

			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }
		}			// act on specific parameters
	}			// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display Picture

// collective validation and get instance of Picture
if ($idbr > 0)
{                           // existing picture
    $picture    			= new Picture(array('idbr'  => $idbr));
    if ($picture->isExisting())
    {
        $idir       		= $picture->get('idir');
        $record     		= $picture->getRecord();
        $idtype     		= $picture->getIdType();
    }
    else
	{                       // invalid IDBR
        $idbrtext           = $idbr;
        $picture            = null;
	}                       // invalid IDBR
}                           // existing picture
else
if ($idir)
{                           // request to create new picture
    $picture                = new Picture(array('idtype'    => $idtype,
                                                'idir'      => $idir,
                                                'pictype'   => $pictype));
    $record     		    = $picture->getRecord();
    $idbr                   = null;
}                           // request to create new picture
else
    $idbrtext               = 'missing';

// identify version of template to load
$action                     = 'Display';
if ($record && $record->isOwner())
    $action                 = 'Update';
$template           = new FtTemplate("editPicture$action$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => 'editPicture'));
$translate          = $template->getTranslate();
$t                  = $translate['tranTab'];

$template->set('LANG',              $lang);
if ($debug)
    $template->set('DEBUG',         'Y');
else
    $template->set('DEBUG',         'N');


if (is_object($picture))
{                               // have instance of Picture
    if (isset($setParms) && count($setParms) > 0)
    {                           // apply updates
        foreach ($setParms as $field => $value)
        {
            $picture[$field]        = $value;
        }
        $picture->save(false);
    }                           // apply updates

	if (is_object($record))
    {                           // associate record
        if ($record instanceof Person)
            $name               = $record->getName(Person::NAME_INCLUDE_DATES);
        else
	        $name               = $record->getName();
	    $template->set('NAME',              $name);
	    $template['badRecord']->update(null);
	
        switch($idtype)
        {
			case Picture::IDTYPEPerson:	    // Individual		tblIR.IDIR
			case Picture::IDTYPEBirth:  	// Birth		    tblIR.IDIR
			case Picture::IDTYPEChris:  	// Christening		tblIR.IDIR
			case Picture::IDTYPEDeath:  	// Death		    tblIR.IDIR
            case Picture::IDTYPEBuried: 	// Burial		    tblIR.IDIR
            {
                $template['reference']->update(array('script'   => 'Person',
                                                     'key'      => 'idir',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPEMar:    	// Marriage		    tblMR.IDMR
            {
                $idime              = $record['idirhusb'];
                if ($idime == 0)
                    $idime          = $record['idirwife'];
                $template['reference']->update(array('script'   => 'Person',
                                                     'key'      => 'idir',
                                                     'idir'     => $idime,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPEEvent:  	// Events		    tblER.IDER
            {
                $template['reference']->update(array('script'   => 'editEvent',
                                                     'key'      => 'ider',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPESrcMaster:	// Master Sources	tblSR.IDSR
            {
                $template['reference']->update(array('script'   => 'Source',
                                                     'key'      => 'idsr',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPESrcDetail:	// Source Detail	tblSX.IDSX
            {
                $template['reference']->update(array('script'   => 'editCitation',
                                                     'key'      => 'idsx',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPEToDo:   	// To Do		    tblTD.IDTD
            {
                $template['reference']->update(null);
                break;
            }

			case Picture::IDTYPEAddress:	// Address		    tblAR.IDAR
            {
                $template['reference']->update(array('script'   => 'Address',
                                                     'key'      => 'idar',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPELocation:	// Location		    tblLR.IDLR
            {
                $template['reference']->update(array('script'   => 'Location',
                                                     'key'      => 'idlr',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

			case Picture::IDTYPETemple:	    // Temple		    tblTR.IDTR
            {
                $template['reference']->update(array('script'   => 'Temple',
                                                     'key'      => 'idtr',
                                                     'idir'     => $idir,
                                                     'name'     => $name));
                break;
            }

            default:
            {
                $template['reference']->update(null);
                break;
            }
        }

	    // get information from record for display
	    $template->set('IDBR',			    $idbr);
	    $template->set('IDIR',			    $idir);
	    $template->set('IDTYPE',			$idtype);
		$picType   		    = $picture->getType();
	    $template->set('PICTYPE',			$picType);
		$location   		= $picture->getURL();
	    $template->set('LOCATION',			$location);
		$caption   		    = $picture->getCaption();
	    $template->set('CAPTION',			$caption);
		$date       		= $picture->getDate();
	    $template->set('DATE',			    $date);
		$desc       		= $picture->getDesc();
	    $template->set('DESC',			    $desc);
		$print       		= $picture->getPrint();
	    $template->set('PRINT',			    $print);
        if ($print)
            $template->set('PRINTCHECKED',			'checked="checked"');
        else
            $template->set('PRINTCHECKED',			'');
		$sound       		= $picture->getSoundURL();
	    $template->set('SOUND',			    $sound);
		$pref       		= $picture->getPref();
	    $template->set('PREF',			    $pref);
        if ($pref)
            $template->set('PREFCHECKED',			'checked="checked"');
        else
            $template->set('PREFCHECKED',			'');
		$userref   		    = $picture->getFilingRef();
	    $template->set('USERREF',			$userref);
	    $picSelected   		= array('','','','');
        $picSelected[$picType]   		= ' selected="selected"';
		$template->set('PICSELECTED0',		$picSelected[0]);
		$template->set('PICSELECTED1',		$picSelected[1]);
		$template->set('PICSELECTED2',		$picSelected[2]);
		$template->set('PICSELECTED3',		$picSelected[3]);
	}                       // have instance of Record
	else
	{
	    $template['goodRecord']->update(null);
	    $template['picForm']->update(null);
        $template['reference']->update(null);
	}
}                           // have instance of Picture
else
{                           // no Picture
    if (strlen($idbrtext) > 0 && $idbrtext != 'missing')
    {                       // request existing Picture
        $msg	    .= str_replace('$text',
                                   $idbrtext, 
                                   $template['badidbr']->innerHTML());
    }                       // request existing Picture
    else
    {                       // request create new Picture
        if ($idbrtext == 'missing')
        {
            $idbrtext           = $t['missing'];
            $msg	    .= str_replace('$text',
                                   $idbrtext, 
                                   $template['badidbr']->innerHTML());
        }
	    if (is_null($idtype) || strlen($idtypetext) > 0)
	    {
	        if ($idtypetext == 'missing')
	            $idtypetext     = $t['missing'];
	        $msg	.= str_replace('$text',
	                               $idtypetext, 
	                               $template['badidtype']->innerHTML());
	    }
	    if (is_null($pictype) || strlen($pictypetext) > 0)
	    {
	        if ($pictypetext == 'missing')
	            $pictypetext    = $t['missing'];
	        $msg	.= str_replace('$text', 
	                               $pictypetext, 
	                               $template['badpictype']->innerHTML());
	    }
	    if (is_null($idir) || strlen($idirtext) > 0)
	    {
	        if ($idirtext == 'missing')
	            $idirtext       = $t['missing'];
	        $msg	.= str_replace('$text', 
	                               $idirtext, 
	                               $template['badidir']->innerHTML());
	    }
    }                       // request create new Picture
    $template['goodRecord']->update(null);
    $template['picForm']->update(null);
    $template['reference']->update(null);
}                           // no Picture

$template->display();
