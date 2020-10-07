<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  RecordPic.inc														*
 *																		*
 *  Definition of a base class representing a record in a genealogical	*
 *  database that can have dependent instances of class Picture.		*
 *  This provides a shared implementation of the method displayPictures	*
 *																		*
 *  History:															*
 *		2015/12/08		Split out from class Record						*
 *		2017/07/23		class LegacyPicture renamed to Picture			*
 *		2017/10/17		use class RecordSet								*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Record.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/Picture.inc";

/************************************************************************
 *  class RecordPic														*
 *																		*
 *  Definition of a base class implementing common behavior for a		*
 *  record within a genealogy database.									*
 *																		*
 ************************************************************************/
class RecordPic extends Record
{
    /********************************************************************
     *	RecordPic::$picId												*
     *																	*
     *	This table maps table name to default Picture type				*
     ********************************************************************/
    public static $picId = array(
				'tblAR'		=> Picture::IDTYPEAddress,
				'tblER'		=> Picture::IDTYPEEvent,
				'tblIR'		=> Picture::IDTYPEPerson,
				'tblLR'		=> Picture::IDTYPELocation,
				'tblMR'		=> Picture::IDTYPEMar,
				'tblSR'		=> Picture::IDTYPESrcMaster,
				'tblSX'		=> Picture::IDTYPESrcDetail,
				'tblTR'		=> Picture::IDTYPETemple);

    /********************************************************************
     *	function RecordPic::__construct									*
     *																	*
     *	Create an instance of the class RecordPic from 					*
     *  a row returned from a database query.							*
     *																	*
     *	Input:															*
     *	    $dbrow		row returned from database query or				*
     *					complete constructed row						*
     *	    $table		name of database table containing records for	*
     *					this class										*
     ********************************************************************/
    function __construct($dbrow,
						 $table)
    {
		// invoke constructor of base class Record
		parent::__construct($dbrow,
						    $table);
    }		// __construct

    /********************************************************************
     *  function RecordPic::displayPictures								*
     *																	*
     *  Display pictures relating to this record.						*
     *																	*
     *  Parameters:														*
     *	    $type		type of event pictures are associated with		*
     *					if more than one event type for this instance	*
     *																	*
     *	Throws:															*
     *	    Exception if the current table does not support pictures.	*
     ********************************************************************/
    function displayPictures($type = null)
    {
		// if record does not already exist in the database then it cannot
		// have any pictures associated with it
		if ($this->needInsert)
		    return;

		$table		= $this->getTableName();
		if (!isset(self::$picId[$table]) ||
		    is_null($deftype = self::$picId[$table]))
		{		// unsupported table
		    throw new Exception("RecordId::displayPictures: " .
							"Function not supported for table '$table'");
		}		// unsupported table

		if (is_null($type))
		{			// idtype not specified
		    $type	= $deftype;
		}			// idtype not specified

		// show any images/video files
		$picParms	= array('idir'		=> $this->getId(),
							'idtype'	=> $type);
		$picList	= new RecordSet('Pictures', $picParms);

		foreach($picList as $idbr => $picture)
		{		// loop through all pictures
		    $picture->toHtml();	// display the picture
		}		// loop through all pictures
    }			// RecordPic::displayPictures

}		// class RecordPic
