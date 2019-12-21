<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
use Templating\Template;
use Templating\TemplateTag;

/************************************************************************
 *  Picture.inc															*
 *																		*
 *  Definition of a class representing a picture/video/sound record 	*
 *  in a genealogical database.											*
 *  This class provides access to the information in a row of the		*
 *  table tblBR.														*
 *																		*
 *  Because Legacy Family Tree is intended for use by a single user on	*
 *  their personal computer, their record format supports referencing a	*
 *  file in the local file system.  However this web implementation		*
 *  only supports the use of URLs.  All image and sound files are		*
 *  therefore uploaded to the web and the file references changed to	*
 *  appropriate URLs.													*
 *																		*
 *  The following is based on the Legacy Family Tree internal			*
 *  documentation		Copyright 1995-2005, Millennia Corporation		*
 *		All Rights Reserved												*
 *		      Name					  Type						    	*
 *		      IDBR					INTEGER(10)						    *
 *		      IDIR					INTEGER(10)						    *
 *		  		    ID of Person table, Family table, etc..				*
 *				    See constants starting with IDTYPE defined below	*
 *		      IDType					TINYINT(3)					    *
 *				    See constants starting with IDTYPE defined below	*
 *		      PicType					TINYINT(3)					    *
 *		  		    0=picture, 1=sound, 2=video, 3=file					*
 *		  		    if PicType=0 then a sound can be linked to the		*
 *				    picture.											*
 *		      PicOrder					SHORTINT(5)					    *
 *		  		    used as sort order.									*
 *		      PicName					VARCHAR(255)					*
 *		  		    filename of picture  (without the path)				*
 *		      PicNameURL				VARCHAR(255)					*
 *		  		    full url to a picture, video or document on the		*
 *				    internet											*
 *		      IDBPPic					INTEGER(10)						*
 *		  		    --> tblBP.IDBP, ID to the Picture Path List			*
 *		      PicCaption				VARCHAR(255)					*
 *		      PicD						VARCHAR(255)					*
 *				(see Date)												*
 *		      PicSD						INTEGER(10)						*
 *				(see Sort Date)											*
 *		      PicDate					VARCHAR(30) DEFAULT NULL,		*
 *		      PicDesc					LONGTEXT				  -		*
 *		      PicPrint					TINYINT(3)						*
 *		  		    1=Tagged, 0=not tagged								*
 *		      PicSoundName				VARCHAR(255)					*
 *		  		    filename of Sound or Video file (without the path)	*
 *		      PicSoundNameURL			VARCHAR(255)					*
 *		  		    full url to a sound file on the internet			*
 *		      IDBPSound					INTEGER(10)						*
 *		  		    --> tblBP.IDBP, ID to the Picture Path List			*
 *		      Used						TINYINT(3)						*
 *		  		    temporary use, 0=no, 1=yes							*
 *		      PicPref					TINYINT(3)						*
 *		  		    1=preferred, 0=not									*
 *		      FilingRef					VARCHAR(50)						*
 *		  		    user filing number									*
 *																		*
 *  History:															*
 *		2011/05/21		created											*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/10/06		use LegacyRecord::setField to manipulate record	*
 *						fields so save method will correctly construct	*
 *						appropriate INSERT or UPDATE to synchronize the	*
 *						database record.								*
 *						All set methods return former value				*
 *		2012/10/20		standardize initialization of new record		*
 *		2013/01/30		idbr field missing from default record			*
 *		2013/02/10		add support for table tblBP to store website	*
 *						addresses										*
 *						missing initialization of field filingref		*
 *		2013/02/15		consolidate support for tblBP into a single		*
 *						static method, because that functionality is	*
 *						used in 3 different places by the class			*
 *						ensure non empty picture name					*
 *		2013/02/24		add new method getRecord and remove method		*
 *						getIdir											*
 *						add method delete to manage tblBP on deletion	*
 *		2013/08/09		base class renamed to Record					*
 *		2014/03/21		class LegacyPictureList deprecated and replaced	*
 *						by static method getPictures					*
 *						add static method deletePictures				*
 *						remove redundant "Pic" from method names		*
 *						change implementation of picture date to		*
 *						match implementation of all other dates			*
 *						so that pictures can be sorted based on date	*
 *		2014/10/01		add method addOwner								*
 *		2014/10/04		method delete erroneously used undefined		*
 *						function MDB2::update instead of exec			*
 *						getRecord returns instance of LegacyToDo		*
 *						instead of Record for IDTYPEToDo				*
 *						add method toHtml to centralize display of		*
 *						instance of LegacyPicture						*
 *						correct parse of URLs							*
 *						support audio and video files					*
 *		2014/12/25		exception from method getPictures gave wrong	*
 *						method name										*
 *		2015/03/21		support string parameter to delete				*
 *		2015/04/27		escape < and > in SQL commands displayed in		*
 *						XML or HTML										*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/15		use autoload to avoid unnecessary requires		*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/07/23		class renamed to Picture						*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		change class LegacySource to class Source		*
 *		2017/08/15		change class LegacyToDo to class ToDo			*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *		2017/11/11		support calling constructor with search			*
 *						parameters passed in the first argument			*
 *						add method get									*
 *						add field name translate table					*
 *		2017/11/18		make splitUrl a method							*
 *		2017/12/18		add support for temporary fields				*
 *		2018/11/04      use class Template to implement method toHtml   *
 *		                and extend toHtml so that immediate display     *
 *		                is suppressed and the method always returns     *
 *		                the generated HTML as a string                  *
 *		2019/02/18      change template name for displaying picture     *
 *		2019/04/15      replace is_a with instanceof                    *
 *		2019/07/18      use Person::getPerson                           *
 *		2019/07/21      use Location::getLocation                       *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/	
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';

/************************************************************************
 *  class Picture														*
 *																		*
 *  Definition of a class implementing behavior for Picture records		*
 *  within a genealogy database.										*
 ************************************************************************/	

class Picture extends Record
{
    /********************************************************************
     * values of IDType field											*
     ********************************************************************/
    const	IDTYPEPerson    = 0;	// Individual		tblIR.IDIR
    const	IDTYPEBirth     = 1;	// Birth		    tblIR.IDIR
    const	IDTYPEChris     = 2;	// Christening		tblIR.IDIR
    const	IDTYPEDeath     = 3;	// Death		    tblIR.IDIR
    const	IDTYPEBuried    = 4;	// Burial		    tblIR.IDIR
    const	IDTYPEMar       = 20;	// Marriage		    tblMR.IDMR
    const	IDTYPEEvent     = 30;	// Events		    tblER.IDER
    const	IDTYPESrcMaster = 40;	// Master Sources	tblSR.IDSR
    const	IDTYPESrcDetail = 41;	// Source Detail	tblSX.IDSX
    const	IDTYPEToDo      = 50;	// To Do		    tblTD.IDTD
    const	IDTYPEAddress   = 70;	// Address		    tblAR.IDAR
    const	IDTYPELocation  = 71;	// Location		    tblLR.IDLR
    const	IDTYPETemple    = 72;	// Temple		    tblTR.IDTR

    /********************************************************************
     * values of the PicType field										*
     ********************************************************************/
    const	PIC_TYPE_PICTURE= 0;	// image file
    const	PIC_TYPE_SOUND	= 1;	// sound file
    const	PIC_TYPE_VIDEO	= 2;	// video file
    const	PIC_TYPE_OTHER	= 3;	// other file	

    /********************************************************************
     *  Picture::$IdTypeNames											*
     *																	*
     * translate values of IDType field to text							*
     ********************************************************************/
    public static $IdTypeNames	= array(
						0	=>	'Person',
						1	=>	'Birth',
						2	=>	'Christening',
						3	=>	'Death',
						4	=>	'Burial',
						20	=>	'Marriage',
						30	=>	'Events',
						40	=>	'Master Source',
						41	=>	'Source Citation',
						50	=>	'To Do',
						70	=>	'Address',
						71	=>	'Location',
						72	=>	'Temple');

    /********************************************************************
     *  Picture::$PicTypeNames											*
     *																	*
     * translate values of PicType field to text						*
     ********************************************************************/
    public static $PicTypeNames	= array(
						0	=>	'Image File',
						1	=>	'Sound File',
						2	=>	'Video File',
						3	=>	'Other File');

    /********************************************************************
     *	Picture::$initRow												*
     *																	*
     *	array containing default values for a new record				*
     ********************************************************************/
    protected static	$initRow	= array(
						'idbr'				=> 0, 
						'idir'				=> 0, 
						'idtype'			=> 0, 
						'pictype'			=> 0, 
						'picorder'			=> 0, 
						'picname'			=> '', 
						'picnameurl'		=> '',
						'idbppic'			=> 1, 
						'piccaption'		=> '', 
						'picd'				=> '', 
						'picsd'				=> '', 
						'picdate'			=> '', 
						'picdesc'			=> '', 
						'picprint'			=> 0, 
						'picsoundname'		=> '', 
						'picsoundnameurl'	=> '', 
						'idbpsound'			=> 0, 
						'used'				=> 0, 
						'picpref'			=> 1,
						'filingref'			=> '');

    /********************************************************************
     *	Picture::$translate												*
     *																	*
     *	array containing alternate field names							*
     ********************************************************************/
    protected static	$translate	= array(
						'type'		    => 'pictype',
						'order'		    => 'picorder',
						'name'		    => 'picname',
						'nameurl'	    => 'picnameurl',
						'caption'	    => 'piccaption',
						'd'		        => 'picd',
						'sd'		    => 'picsd',
						'date'		    => 'picdate',
						'desc'		    => 'picdesc',
						'print'		    => 'picprint',
						'soundname'	    => 'picsoundname',
						'soundnameurl'	=> 'picsoundnameurl',
						'pref'		    => 'picpref');

    /********************************************************************
     *	Picture::$defaultOrder											*
     *																	*
     *	Specify default order for RecordSet								*
     ********************************************************************/
    protected static	$defaultOrder	= 'PicOrder';

    /********************************************************************
     *	function Picture::__construct									*
     *																	*
     *  Create an instance of a class that describes a					*
     *	picture/video/sound file.										*
     *																	*
     *  Input:															*
     *	    $id			array('idbr' => $idbr) to select specificc, or	*
     *					array('idtype'		=> $idtype,					*
     *					      'idir'		=> $idir,					*
     *					      'pictype'     => $pictype,				*
     *					      'picnameurl' => full URL of picture)		*
     *					to add a picture to a specific record			*
     *					or a full database record as an array			*
	 *		$idir		deprecated									    *
	 *		$idtype     deprecated									    *
	 *		$pictype	deprecated									    *
     *																	*
     *  Returns object containing picture/video/sound file record.		*
     *																	*
     *	Throws Exception if called with bad parameters					*
     ********************************************************************/
    function __construct($id,
						 $idir		= null,
						 $idtype	= Picture::IDTYPEPerson,
						 $pictype	= Picture::PIC_TYPE_PICTURE)
    {
		global		$debug;
		global		$warn;
        global		$connection;

		$dbrow				= null;
		$needInsert			= false;
		$where				= '';
		$and				= 'WHERE ';
		$sqlParms			= array();
		$picnameurl	        = null;

		// action depends upon type of first parameter
		if (is_array($id))
		    $parms		    = $id;
		else
		if (is_string($id))
		{		// string
		    if (ctype_digit($id))
		    {		// ignore other parameters
				$idbr		= intval($id);
				$parms		= array('idbr'	=> $idbr);
		    }		// ignore other parameters
		    else
		    {		// search for URL
				$picnameurl	= $id;		// to clarify following logic
				$parms	    = array('idir'		=> $idir,
							        'idtype'	=> $idtype,
							        'pictype'	=> $pictype);
				$this->row	= array();
				$this->splitUrl('picture',
							    $picnameurl);
				$parms		= $this->row;
		    }		// search for URL
		}		// string
		else
		if (is_int($id))
		    $parms		= array('idbr'	=> $id);
		else
		{
		    throw new Exception("Picture::__construct: called with " .
				 gettype($id) . ", value='" . print_r($id, true) . "'");
		}

		$fieldsValid		            = true;
		foreach($parms as $field => $value)
		{
		    $fieldLc		            = strtolower($field);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc	            = self::$translate[$fieldLc];
		    switch($fieldLc)
		    {
				case 'idbr':
				{
				    $where	    	    .= $and . 'IDBR=:idbr';
				    $idbr	    	    = $value;
				    $sqlParms['idbr']	= $value;
				    $and	    	    = ' AND ';
				    break;
				}

				case 'picnameurl':
				{	// create new record for picture/video/sound URL
				    $picnameurl 	    = $value;// to clarify following logic
				    break;
				}		// picture/video/sound file name

				case 'idtype':
				{
				    $idtype	    	    = $value;
				    break;
				}

				case 'pictype':
				{
				    $pictype		    = $value;
				    break;
				}

				case 'idir':
				{
				    $idir		        = $value;
				    break;
				}

				case 'picorder':
				case 'picname':
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
				{                   // not keys
				    break;
				}                   // not keys

				default:
				{
				    $fieldsValid	= false;
				    break;
				}
		    }
		}

		if (count($parms) >= count(self::$initRow) && $fieldsValid)
		{		        // full row returned from database query
		    $dbrow		            = $parms;
		    $needInsert		        = false;
		}		        // full row returned from database query
		else
		if (strlen($where) > 0)
		{		        // numeric identifier of record
		    // construct the query of the picture/video/sound file table
		    $query		        = 'SELECT * FROM tblBR ' . $where;

		    // query the database
		    $stmt		        = $connection->prepare($query);
		    $queryText		    = debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
            {		// success
                if ($debug)
				    $warn		.= "<p>Picture::__construct: " .
					    		      "'$queryText'</p>\n";
				$row	    	= $stmt->fetch(PDO::FETCH_ASSOC);

				if (is_array($row))
				{		// found picture/video/sound file record
				    $dbrow	= $row;
				    $needInsert	= false;
				}		// found picture/video/sound file record
				else
				{		// failed
				    $dbrow		= self::$initRow;
				    $needInsert	= true;
				}		// failed
		    }		    // success
		    else
		    {		    // error performing query
				throw new Exception('Picture::__construct: ' .
							    "'$queryText', " . 
							    print_r($stmt->errorInfo(),true));
		    }		    // error performing query
		}		        // numeric identifier of record
        else
		{		        // add record with new picture URL
		    // the new record is added after all of the existing records
		    // so the PicOrder field is initialized to the number of
		    // existing records for the current individual
		    $query	    = 'SELECT COALESCE(MAX(`PicOrder`),0)+1 FROM tblBR ' .
				                'WHERE IdType=:idtype AND IDIR=:idir';
			$sqlParms	            = array('idtype'	=> $idtype,
                                            'idir'	    => $idir);

		    $stmt	                = $connection->prepare($query);
		    $queryText	            = debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				if ($debug)
				    print "<p>Picture::__construct: '$queryText'</p>\n";

				$ctrow			    = $stmt->fetch(PDO::FETCH_NUM);
				$dbrow			    = self::$initRow;
				$dbrow['idir']		= $idir; 
				$dbrow['idtype']	= $idtype; 
				$dbrow['pictype']	= $pictype; 
				$dbrow['picorder']	= $ctrow[0];
				$this->row		    = $dbrow;
				$this->splitUrl('picture', $picnameurl);
				$dbrow			    = $this->row;
				$needInsert		    = true;
		    }		// success
		    else
		    {		// error performing query
				throw new Exception('Picture::__construct: ' .
							    "'$queryText', " . 
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'tblBR');
		$this->needInsert	= $needInsert;

		foreach($parms as $field => $value)
		{
		    $fieldLc		            = strtolower($field);
		    if (array_key_exists($fieldLc, self::$translate))
				$fieldLc	            = self::$translate[$fieldLc];
		    switch($fieldLc)
		    {
				case 'idbr':
				case 'idtype':
				case 'pictype':
				case 'idir':
				case 'picorder':
				{               // already handled
				    break;
				}               // already handled

				case 'picnameurl':
				case 'picname':
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
                {           // not keys
                    parent::set($fieldLc, $value);
				    break;
				}           // not keys

            }               // act on specific fields
        }                   // initialize other fields

		// diagnostic output if debug is set
		$this->dump('Picture Record constructed:');
    }		// Picture::__construct

    /********************************************************************
     *	function Picture::getIdbr										*
     *																	*
     *	Retrieve the unique numeric key of this instance of				*
     *	Picture.				        								*
     *																	*
     *	Returns:														*
     *	    IDBR key identifier											*
     ********************************************************************/
    function getIdbr()
    {
		if ($this->needInsert)
		    $this->save(false);

		return $this->row['idbr'];
    }	// function Picture::getIdbr

    /********************************************************************
     *	function Picture::getIdType										*
     *																	*
     *	Retrieve the numeric type of record associated with this		*
     *	picture.														*
     *																	*
     *	Returns:														*
     *	    Numeric code identifying the type of the associated record	*
     *	    or event.													*
     ********************************************************************/
    function getIdType()
    {
		return $this->row['idtype'];
    }	// function Picture::getIdType

    /********************************************************************
     *	function Picture::getRecord										*
     *																	*
     *	Retrieve the associated database record.						*
     *																	*
     *	Returns:														*
     *	    An instance of a class derived from Record.					*
     ********************************************************************/
    function getRecord()
    {
		$id		    = $this->row['idir'];
		$idtype		= $this->row['idtype'];

		switch ($idtype)
		{		// act on associated record type
		    case Picture::IDTYPEPerson:	// Person
		    case Picture::IDTYPEBirth:	// Birth Event
		    case Picture::IDTYPEChris:	// Christening Event
		    case Picture::IDTYPEDeath:	// Death Event
		    case Picture::IDTYPEBuried:	// Burial Event
		    {
                if (!class_exists(__NAMESPACE__ . '\\Person'))
                    require __NAMESPACE__ . "/Person.inc";
				return Person::getPerson($id);
		    }		// Person

		    case Picture::IDTYPEMar:	// Marriage
		    {
                if (!class_exists(__NAMESPACE__ . '\\Family'))
                    require __NAMESPACE__ . "/Family.inc";
				return new Family(array('idmr' => $id));
		    }		// Family

		    case Picture::IDTYPEEvent:	// Events
		    {
                if (!class_exists(__NAMESPACE__ . '\\Event'))
                    require __NAMESPACE__ . "/Event.inc";
				return new Event(array('ider' => $id));
		    }		// Event

		    case Picture::IDTYPESrcMaster:// Master Sources
		    {
                if (!class_exists(__NAMESPACE__ . '\\Source'))
                    require __NAMESPACE__ . "/Source.inc";
				return new Source(array('idsr' => $id));
		    }		// Source

		    case Picture::IDTYPESrcDetail:// Source Detail
		    {
                if (!class_exists(__NAMESPACE__ . '\\Citation'))
                    require __NAMESPACE__ . "/Citation.inc";
				return new Citation(array('idsx' => $id));
		    }		// Citation

		    case Picture::IDTYPEToDo:	// To Do
		    {
                if (!class_exists(__NAMESPACE__ . '\\ToDo'))
                    require __NAMESPACE__ . "/ToDo.inc";
				return new ToDo(array('idtd' => $id));
		    }		// To Do entry

		    case Picture::IDTYPEAddress:	// Address
		    {
                if (!class_exists(__NAMESPACE__ . '\\Address'))
                    require __NAMESPACE__ . "/Address.inc";
				return new Address(array('idar' => $id));
		    }		// Address

		    case Picture::IDTYPELocation:	// Location
		    {
                if (!class_exists(__NAMESPACE__ . '\\Location'))
                    require __NAMESPACE__ . "/Location.inc";
				return Location::getLocation($id);
		    }		// Location

		    case Picture::IDTYPETemple:	// Temple
		    {
                if (!class_exists(__NAMESPACE__ . '\\Temple'))
                    require __NAMESPACE__ . "/Temple.inc";
				return new Temple(array('idtr' => $id));
		    }		// Temple

		    default:
		    {
				throw new Exception('Picture::getRecord: ' .
							        "Invalid Record IdType=$idtype");
		    }		// Temple
		}		// act on specific record type
    }		// Picture::getRecord

    /********************************************************************
     *	function Picture::getIdTypeText									*
     *																	*
     *	Retrieve the type of record associated with this picture		*
     *	as a descriptive string.										*
     *																	*
     *	Returns:														*
     *	    One of the following Strings.								*
     *			'Person'												*
     *			'Birth'													*
     *			'Christening'											*
     *			'Death'													*
     *			'Burial'												*
     *			'Marriage'												*
     *			'Events'												*
     *			'Master Source'											*
     *			'Source Citation'										*
     *			'To Do'													*
     *			'Address'												*
     *			'Location'												*
     *			'Temple'												*
     ********************************************************************/
    function getIdTypeText()
    {
		return self::$IdTypeNames[$this->row['idtype']];
    }	// function Picture::getIdTypeText

    /********************************************************************
     *	function Picture::getType										*
     *																	*
     *	Retrieve the type of file described by this record		 		*
     *	as a numeric code.												*
     *																	*
     *	Returns:														*
     *	    0	a picture (image file)									*
     *	    1	a sound (audio file)									*
     *	    2	a video													*
     *	    3	other (document file)									*
     ********************************************************************/
    function getType()
    {
		return $this->row['pictype'];
    }		// Picture::getType

    /********************************************************************
     *	function Picture::getTypeText									*
     *																	*
     *	Retrieve the type of file (picture, sound, video, other) of 	*
     *	this record as a text string.									*
     *																	*
     *	Returns:														*
     *	    String interpretation of the PicType field					*
     ********************************************************************/
    function getTypetext()
    {
		return self::$PicTypeNames[$this->row['pictype']];
    }		// Picture::getTypeText

    /********************************************************************
     *	function Picture::getOrder										*
     *																	*
     *	Retrieve the numeric order of this picture.						*
     *																	*
     *	Returns															*
     *	    Number indicating the order in which this picture is to be	*
     *	    presented to the viewer.									*
     ********************************************************************/
    function getOrder()
    {
		return $this->row['picorder'];
    }		// Picture::getOrder

    /********************************************************************
     *	function Picture::getURL										*
     *																	*
     *	Retrieve the URL of the image file associated with this picture.*
     *																	*
     *	Returns:														*
     *	    A string containing the concatenation of the PicPath value	*
     *	    from the associated entry in tblBP identified by field		*
     *	    IDBPPic and the values of PicNameURL and PicName.			*
     ********************************************************************/
    function getURL()
    {
		global $connection;
		global $debug;

		$website	= '';

		$idbppic	= $this->row['idbppic'];
		if ($idbppic > 1)
		{		// get web site name from table tblBP
		    $query	= "SELECT PicPath FROM tblBP WHERE IDBP=:idbppic";
		    $sqlParms	= array('idbppic'	=> $idbppic);

		    // query the database
		    $stmt	= $connection->prepare($query);
		    $queryText	= debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				$bprow		= $stmt->fetch(PDO::FETCH_ASSOC);
				if ($bprow)
				    $website	= $bprow['picpath'];
		    }		// success
		    else
		    {		// error performing query
				throw new Exception('Picture::getName: ' . __LINE__ . '"' .
							    $queryText . '", ' . 
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}		// get web site name from table tblBP

		return $website . $this->row['picnameurl'] . $this->row['picname'];
    }		// Picture::getURL

    /********************************************************************
     *	function Picture::getCaption									*
     *																	*
     *	Retrieve the caption associated with this picture.				*
     *																	*
     *	Returns:														*
     *	    String containing the caption of the picture.				*
     ********************************************************************/
    function getCaption()
    {
		return $this->row['piccaption'];
    }		// Picture::getCaption

    /********************************************************************
     *	function Picture::getDate										*
     *																	*
     *	Retrieve the date associated with this picture.					*
     *																	*
     *	Returns:														*
     *	    String containing the date associated with the picture.		*
     ********************************************************************/
    function getDate()
    {
		if (strlen($this->row['picd']) == 0)
		{		// temporary fixup, remove this code ASAP
		    $daterec	= new LegacyDate(' ' . $this->row['picdate']);
		    parent::set('picd', $daterec->getDate());
		    parent::set('picsd', $daterec->getSortDate());
		}		// temporaryfixup
		$daterec	= new LegacyDate($this->row['picd']);

		return $daterec->toString();
    }		// Picture::getDate

    /********************************************************************
     *	function Picture::getDesc										*
     *																	*
     *	Retrieve the extended description associated with this picture.	*
     *																	*
     *	Returns:														*
     *	    String containing the extended description text of this		*
     *	    picture.													*
     ********************************************************************/
    function getDesc()
    {
		return $this->row['picdesc'];
    }		// Picture::getDesc

    /********************************************************************
     *	function Picture::getPrint										*
     *																	*
     *	Retrieve the print flag associated with this picture.			*
     *																	*
     *	Returns:														*
     *	   0	the picture is not tagged								*
     *	   1	the picture is tagged									*
     ********************************************************************/
    function getPrint()
    {
		return $this->row['picprint'];
    }		// Picture::getPrint

    /********************************************************************
     *	function Picture::getSoundURL									*
     *																	*
     *	Retrieve the URL of the sound file associated with this picture.*
     *																	*
     *	Returns:														*
     *	    A string containing the concatenation of the PicPath value	*
     *	    from the associated entry in tblBP identified by field		*
     *	    IDBPSound and the values of PicSoundNameURL and				*
     *	    PicSoundName.												*
     ********************************************************************/
    function getSoundURL()
    {
		global $connection;

		$website	= '';

		$idbpsound	= $this->row['idbpsound'];
		if ($idbpsound > 1)
		{		// get web site name from table tblBP
		    $query	= 'SELECT PicPath FROM tblBP WHERE IDBP=:idbpsound';
		    $sqlParms	= array('idbpsound'	=> $idbpsound);

		    // query the database
		    $stmt	= $connection->prepare($query);
		    $queryText	= debugPrepQuery($query, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				$bprow		= $stmt->fetch(PDO::FETCH_ASSOC);
				if ($bprow)
				    $website	= $bprow['picpath'];
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("Picture::getSoundURL: '$queryText', " . 
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}		// get web site name from table tblBP

		return $website . $this->row['picsoundnameurl'] .
						$this->row['picsoundname'];
    }		// Picture::getSoundURL

    /********************************************************************
     *	function Picture::getUsed										*
     *																	*
     *	Retrieve the temporary used flag associated with this picture.	*
     *																	*
     *	Returns:														*
     *	   0	the picture is not used									*
     *	   1	the picture is used										*
     ********************************************************************/
    function getUsed()
    {
		return $this->row['used'];
    }		// Picture::getUsed

    /********************************************************************
     *	function Picture::getPref										*
     *																	*
     *	Retrieve the preferred flag associated with this picture.		*
     *																	*
     *	Returns:														*
     *	   0	the picture is not preferred							*
     *	   1	the picture is preferred								*
     ********************************************************************/
    function getPref()
    {
		return $this->row['picpref'];
    }		// Picture::getPref

    /********************************************************************
     *	function Picture::getFilingRef									*
     *																	*
     *	Retrieve the user file number associated with this picture.		*
     *																	*
     *	Returns:														*
     *	    String containing user fileing number						*
     ********************************************************************/
    function getFilingRef()
    {
		return $this->row['filingref'];
    }	// Picture::getFilingRef

    /********************************************************************
     *	function Picture::setOrder										*
     *																	*
     *	Set the order of presentation of pictures within a record.		*
     *	A valid new order value is the order value for another picture	*
     *	associated with the same genealogical record.  It is therefore	*
     *	necessary to re-order other pictures so as to free up the new	*
     *	order value, and close the gap created by removing the current	*
     *	record from its current place in the order.						*
     *																	*
     *  Parameters:														*
     *	    $neworder		new order value for this picture			*
     *																	*
     *	Returns:														*
     *	    Former value of order.										*
     *																	*
     *	Throws exception if value is not numeric						*
     ********************************************************************/
    function setOrder($neworder)
    {
		global	$connection;
		global	$debug;

		if (is_string($neworder) && ctype_digit($neworder))
		    $neworder		= (int)$neworder;
		$oldorder		= $this->row['picorder'];
		if ($neworder == $oldorder)
		    return $oldorder;	// unchanged

		// new order must point within the existing list of pictures
		// to which this record belongs
		$sqlParms	= array('idir'		=> $this->row['idir'],
							'idtype'	=> $this->row['idtype']);
		$query		= 'SELECT COUNT(*) FROM tblBR ' .
							'WHERE IDIR=:idir && IdType=:idtype';
		$stmt		= $connection->prepare($query);
		$queryText	= debugPrepQuery($query, $sqlParms);
		if ($stmt->execute($sqlParms))
		{		// success
		    $ctrow			= $stmt->fetch(PDO::FETCH_NUM);
		    $count			= $ctrow[0];
		    $sqlParms['neworder']	= $neworder;
		    $sqlParms['oldorder']	= $oldorder;
		    $where		= 'WHERE IDIR=:idir AND IdType=:idtype';

		    if (is_int($neworder) && ($neworder >= 0) && ($neworder < $count))
		    {		// valid numeric order
				if ($neworder < $oldorder)
				{		// changing upwards
				    // renumber records from neworder to oldorder-1 up 1
				    $where	.= ' AND PicOrder>=:neworder' .
							   ' AND PicOrder<:oldorder';
				    $reorder	= 'UPDATE tblBR SET PicOrder=PicOrder+1 ' .
							    $where;
				}		// changing upwards
				else
				if ($neworder > $oldorder)
				{		// changing downwards
				    // renumber records from oldorder+1 to neworder down 1
				    $where	.= ' AND PicOrder>:oldorder' .
							   ' AND PicOrder<=:neworder';
				    $reorder	= "UPDATE tblBR SET PicOrder=PicOrder-1 " .
							  $where;
				}		// changing downwards
				$stmt		= $connection->prepare($reorder);
				$reorderText	= debugPrepQuery($reorder, $sqlParms);
				if ($stmt->execute($sqlParms))
				{		// success
				    parent::set('picorder', $neworder);
				    $this->save(false);
				    return $oldorder;
				}		// success
				else
				{		// error performing exec
				    throw new Exception("Picture::setOrder: '$reorderText', " . 
								    print_r($stmt->errorInfo(),true));
				}		// error performing query
		    }		// valid numeric order
		    else
				throw new Exception('Picture::setOrder: ' .
								'invalid parameter ' . $newvalue);
		}		// success
		else
		{		// error performing query
		    throw new Exception('Picture::setOrder: "' .
							"'$queryText', " . 
							print_r($stmt->errorInfo(),true));
		}		// error performing query
    }		// Picture::setOrder

    /********************************************************************
     *	function Picture::splitUrl										*
     *																	*
     *	Given the URL of a document split it into web site name,		*
     *	which is used to locate or create the associated entry in tblBP,*
     *	and the file within the web site.								*
     *																	*
     *  Parameters:														*
     *	    $part		= identify which fields to set					*
     *					  'picture' or 'sound'							*
     *	    $docurl		= URL for a document							*
     *																	*
     *	Side Effects:													*
     *	    $this->row['idbppic']		= $IDBP;						*
     *	    $this->row['picnameurl']		= $path;					*
     *	    $this->row['picname']		= $filename;					*
     *		or															*
     *	    $this->row['idbpsound']		= $IDBP;						*
     *	    $this->row['picsoundnameurl'= $path;						*
     *	    $this->row['picsoundname'	= $filename;					*
     ********************************************************************/
    private function splitUrl($part	= '',
						      $docurl	= '')
    {
		global $connection;

		$IDBP		= 1;
		$path		= '';
		$filename	= $docurl;

		if (strlen($docurl) > 0)
		{		// something to parse
		    $matches		= parse_url($docurl);
		    if (!array_key_exists('scheme', $matches))
		    {
				if ($part == 'picture')
				    $this->set('picname',	$docurl);
				else
				    $this->set('picsoundname',	$docurl);
				return;
		    }
		    $schema		= $matches['scheme'];
		    $host		= $matches['host'];
		    $path		= $matches['path'];
		    $prc		= preg_match("#[^/]*$#", $path, $matches);
		    $filename		= $matches[0];
print "<p>Picture.inc: " . __LINE__ . "path='$path', filename='$filename'</p>\n";
		    $path		= substr($path, 0, 
								 strlen($path) - strlen($filename));
print "<p>Picture.inc: " . __LINE__ . "path='$path', filename='$filename'</p>\n";
		    $filename		= $filename;

		    if (strlen($schema) > 0 && strlen($host) > 0)
		    {		// access at another server
				$website	= $schema . '://' . $host;
				$query		= 'SELECT IDBP FROM tblBP ' .
									'WHERE PicPath=:website';
				$sqlParms	= array('website'	=> $website);

				// query the database
				$stmt		= $connection->prepare($query);
				$queryText	= debugPrepQuery($query, $sqlParms);
				if ($stmt->execute($sqlParms))
				{		// success
				    $result		= $stmt->fetch(PDO::FETCH_NUM);
				    if (is_array($result))
				    {		// matched existing website
						$IDBP	= $result[0];
				    }		// matched existing website
				    else
				    {		// create new website entry
						$insert		= 'INSERT INTO tblBP ' .
								  '(PicPath, Used, Tag1, qsTag) ' .
								  'VALUES(:website,1,0,0)';
						$stmt		= $connection->prepare($insert);
						$insertText	= debugPrepQuery($insert, $sqlParms);
						if ($stmt->execute($sqlParms))
						{		// success
						    $count		= $stmt->rowCount();
						    if ($count == 1)
						    {		// record inserted
							$IDBP	= $connection->lastInsertId();
						    }		// record inserted
						    else
						    {
							throw new Exception("Picture::splitUrl: '" .
									    $insertText . "', " .
									    "inserted $count records");
						    }
						}		// success
						else
						{		// error performing insert
						    throw new Exception("Picture::splitUrl: '" .
									$insertText . "', " . 
								    print_r($stmt->errorInfo(),true));
						}		// error performing insert
				    }		// create new website entry
				}		// success
				else
				{		// error performing query
				    throw new Exception("Picture::splitUrl: '$queryText', " .
								print_r($stmt->errorInfo(),true));
				}		// error performing query

		    }			// access at another server
		    else
				$IDBP	= 1;	// empty entry
		}			// something to parse

		if ($part == 'picture')
		{
		    if (count($this->row) == 0)
		    {
				$this->row['idbppic']		= $IDBP;
				$this->row['picnameurl']	= $path;
				$this->row['picname']		= $filename;
		    }
		    else
		    {
				$this->set('idbppic',		$IDBP);
				$this->set('picnameurl',	$path);
				$this->set('picname',		$filename);
		    }
		}
		else
		{	
		    if (count($this->row) == 0)
		    {
				$this->row['idbpsound']		= $IDBP;
				$this->row['picsoundnameurl']	= $path;
				$this->row['picsoundname']	= $filename;
		    }
		    else
		    {
				$this->set('idbpsound',		$IDBP);
				$this->set('picsoundnameurl',	$path);
				$this->set('picsoundname',	$filename);
		    }
		}
    }			// Picture::splitUrl

    /********************************************************************
     *	function Picture::setURL										*
     *																	*
     *	Set the file URL for this picture								*
     *																	*
     *  Parameters:														*
     *	    $picnameurl		= new URL for this picture					*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setURL($picnameurl)
    {
		$retval			= $this->getURL(); // old full URL
		$matches		= parse_url($picnameurl);
		if (array_key_exists('scheme', $matches))
		    $result		= $this->splitUrl('picture', $picnameurl);
		else
		    parent::set('picnameurl', $picnameurl);
		return $retval;
    }			// Picture::setURL

    /********************************************************************
     *	function Picture::setCaption									*
     *																	*
     *	Set the caption text for this picture							*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new caption text for this picture				*
     *					  Maximum length 255 characters.				*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setCaption($newvalue)
    {
		return parent::set('piccaption', $newvalue);
    }			// Picture::setCaption

    /********************************************************************
     *	function Picture::setDate										*
     *																	*
     *	Set the date associated with this picture.						*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new date value for this picture				*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setDate($newvalue)
    {
		if ($newvalue instanceof LegacyDate)
		{	// parameter is an instance of LegacyDate
		    $date	= $value;
		}	// parameter is an instance of LegacyDate
		else
		{	// convert the value to instance of LegacyDate
		    $date	= new LegacyDate(' ' . $newvalue);
		}	// convert the value to instance of LegacyDate
		parent::set('picsd', $date->getSortDate());
		return parent::set('picd', $date->getDate());
    }			// Picture::setDate

    /********************************************************************
     *	function Picture::setDesc										*
     *																	*
     *	Set the extended descriptive text for this picture.				*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new descriptive text for this picture			*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setDesc($newvalue)
    {
		return parent::set('picdesc', $newvalue);
    }		// Picture::setDesc

    /********************************************************************
     *	function Picture::setPrint										*
     *																	*
     *	Set the print tag option for this picture						*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new print tag value for this picture			*
     *					  true or 1 Tagged						        *
     *					  false or 0 not tagged						    *
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setPrint($newvalue)
    {
        if ($newvalue)
            $newvalue       = 1;
        else
		    $newvalue	    = 0;
		return parent::set('picprint', $newvalue);
    }		// Picture::setPrint

    /********************************************************************
     *	function Picture::setSoundURL									*
     *																	*
     *	Set the URL of the sound name file associated with this picture.*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new sound URL for this picture				*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setSoundURL($newvalue)
    {
		$retval		= $this->getSoundURL();
		$matches	= parse_url($newvalue);
		if (array_key_exists('scheme', $matches))
		    $result	= $this->splitUrl('sound', $newvalue);
		else
		    parent::set('soundnameurl', $newvalue);
		return $retval;
    }		// Picture::setSoundURL

    /********************************************************************
     *	function Picture::setPref										*
     *																	*
     *	Set the preferred flag for this picture.						*
     *	The preferred flag is cleared from all other pictures			*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new preferred flag value for this picture		*
     *					  true or 1 preferred						    *
     *					  false or 0 not preferred						*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     *																	*
     *  Throws exception if unable to update database					*
     ********************************************************************/
    function setPref($newvalue)
    {
		if ($newvalue == 1)
		{		// clear all flags except this one
		    $clear	= 'UPDATE tblBR SET PicPref=0 WHERE IDIR=:idir AND ' .
								'IDType=:idtype AND ' .
								'IDBR!=:idbr';
		    $sqlParms	= array('idir'	=> $this->row['idir'],
							'idtype'=> $this->row['idtype'],
							'idbr'	=>  $this->row['idbr']);
		    $stmt	= $connection->prepare($clear);
		    $clearText	= debugPrepQuery($clear, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				return parent::set('picpref', 1);
		    }		// success
		    else
		    {		// error performing query
				throw new Exception("Picture::setPref: '$clear', " . 
								print_r($stmt->errorInfo(),true));
		    }		// error performing update
		}		// clear all flags except this one
		else
		    return parent::set('picpref', 0);
    }		// Picture::setPref

    /********************************************************************
     *	function Picture::setFilingRef									*
     *																	*
     *	Set the user filing number for this picture.					*
     *																	*
     *  Parameters:														*
     *	    $newvalue	= new user filing number for this picture		*
     *																	*
     *	Returns:														*
     *	    Former value.												*
     ********************************************************************/
    function setFilingRef($newvalue)
    {
		return parent::set('filingref', $newvalue);
    }		// Picture::setDate

    /********************************************************************
     *	function Picture::get											*
     *																	*
     *	Get the value of a field in the object.							*
     *	Equivalent is $picture[$field] on RHS of assignment.            *
     *																	*
     *  Parameters:														*
     *	    $field		name of field. Letter case is ignored.			*
     *	                the field name may be a defined synonym         *
     *																	*
     *	Returns:														*
     *	    value of field												*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($field)
		{		// act on specific field names
		    case 'idbr':
				return getIdbr();

		    case 'picdate':
		    {		// external date format
				$daterec	= new LegacyDate($this->row['picd']);
				return $daterec->toString();
		    }		// external date format

		    case 'idir':
		    case 'idtype':
		    case 'pictype':
		    case 'picorder':
		    case 'picname':
		    case 'picnameurl':
		    case 'idbppic':
		    case 'piccaption':
		    case 'picd':
		    case 'picsd':
		    case 'picdesc':
		    case 'picprint':
		    case 'picsoundname':
		    case 'picsoundnameurl':
		    case 'idbpsound':
		    case 'used':
		    case 'picpref':
		    case 'filingref':
		    {		// common action
				return $this->row[$fieldLc];
				break;
		    }		// common action

		    default:
		    {		// unsupported
				return parent::get($fieldLc);
		    }		// unsupported
		}		// act on specific field names
    }			// Picture::get

    /********************************************************************
     *	function Picture::set											*
     *																	*
     *	Change the value of a field in the object.						*
     *  The method validates that the supplied value is compatible with	*
     *	the target field.												*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		value to set it to								*
     *																	*
     *	Returns:														*
     *	    Former value of field										*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc	= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{		// act on specific field names
		    case 'picdate':
		    {		// legacy internal date format
				return $this->setDate(' ' . $value);
		    }		// legacy internal date format

		    case 'picorder':
		    {		// field specific action
				return $this->setOrder($value);
				break;
		    }		// field specific action

		    case 'picnameurl':
		    {		// field specific action
				return $this->setURL($value);
				break;
		    }		// field specific action

		    case 'piccaption':
		    {		// field specific action
				return $this->setCaption($value);
				break;
		    }		// field specific action


		    case 'picdesc':
		    {		// field specific action
				return $this->setDesc($value);
				break;
		    }		// field specific action

		    case 'picprint':
		    {		// field specific action
				return $this->setPrint($value);
				break;
		    }		// field specific action

		    case 'picsoundnameurl':
		    {		// field specific action
				return $this->setSoundURL($value);
				break;
		    }		// field specific action

		    case 'used':
		    {		// field specific action
				return $this->setUsed($value);
				break;
		    }		// field specific action

		    case 'picpref':
		    {		// field specific action
				return $this->setPref($value);
				break;
		    }		// field specific action

		    case 'filingref':
		    {		// field specific action
				return $this->setFilingRef($value);
				break;
		    }		// field specific action

		    case 'pictype':
		    {		// field specific action
				return parent::set('pictype', $value);
				break;
		    }		// field specific action

		    case 'picname':
		    {		// field specific action
				return parent::set('picname', $value);
				break;
		    }		// field specific action

		    case 'idbr':
		    case 'idir':
		    case 'idbppic':
		    case 'idbpsound':
		    case 'idtype':
		    {		// no special action
				return parent::set($field, $value);
				break;
		    }		// no special action

		    default:
		    {		// unsupported
				return parent::set($field, $value);
		    }		// unsupported
		}		// act on specific field names
    }			// Picture::set

    /********************************************************************
     *	function Picture::postUpdate									*
     *																	*
     *  Update the contents of the record based upon the contents		*
     *  of the $_POST variable.  Note that this does not update the		*
     *	the database until you call save.								*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format											*
     ********************************************************************/
    function postUpdate($xml)
    {
		global	$debug;

		if ($xml)
		    print "    <parms>\n";
		foreach($_POST as $key => $value)
		{
		    $key	= strtolower($key);
		    if ($xml)
				print "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";

		    switch ($key)
		    {		// not the identifying key of the record
				case 'picorder':
				{		// field specific action
				    $this->setOrder($value);
				    break;
				}		// field specific action

				case 'picnameurl':
				{		// field specific action
				    $this->setURL($value);
				    break;
				}		// field specific action

				case 'piccaption':
				{		// field specific action
				    $this->setCaption($value);
				    break;
				}		// field specific action

				case 'picdate':
				{		// field specific action
				    $this->setDate($value);
				    break;
				}		// field specific action

				case 'picdesc':
				{		// field specific action
				    $this->setDesc($value);
				    break;
				}		// field specific action

				case 'picprint':
				{		// field specific action
				    $this->setPrint($value);
				    break;
				}		// field specific action

				case 'picsoundnameurl':
				{		// field specific action
				    $this->setSoundURL($value);
				    break;
				}		// field specific action

				case 'used':
				{		// field specific action
				    $this->setUsed($value);
				    break;
				}		// field specific action

				case 'picpref':
				{		// field specific action
				    $this->setPref($value);
				    break;
				}		// field specific action

				case 'filingref':
				{		// field specific action
				    $this->setFilingRef($value);
				    break;
				}		// field specific action

				case 'idir':
				case 'idtype':
				case 'pictype':
				case 'picname':
				case 'idbppic':
				case 'picsoundname':
				case 'idbpsound':
				{		// no special action
				    $this->set($key, $value);
				    break;
				}		// no special action
		    }		// not the identifying key of the record
		}
		if ($xml)
		    print "    </parms>\n";
    }		// Picture::postUpdate

    /********************************************************************
     *	function Picture::delete										*
     *																	*
     *  Delete the database record associated with this instance.		*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					if a string use it as tag name					*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method deleted the database record	    *
     *	    0			if the record did not need to be deleted	    *
     ********************************************************************/
    function delete($xml = false)
    {
		global	$connection;
		global	$debug;

		// determine top level tag name for XML output
		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $xml	= true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		}		// true

		// get the key of the records in tblBP referenced by this record
		$idbppic	= $this->row['idbppic'];
		$idbpsound	= $this->row['idbpsound'];

		// invoke Record::delete to delete the record from the database
		$rc	= parent::delete($xml);

		// check to see if the tblBP records referenced by this instance
		// are still in use, and clear the 'Used' flag if not 
		if ($idbppic > 0)
		{		// if required update tblBP
		    $update	= 'UPDATE tblBP SET Used=0 ' . 
							'WHERE IDBP=:idbppic AND ' .
								'NOT EXISTS (SELECT * FROM tblBR ' .
							'WHERE IDBPPic=:idbppic OR IDBPSound=:idbppic)';
		    $sqlParms	= array('idbppic', $idbppic);

		    // query the database
		    $stmt	= $connection->prepare($update);
		    $updateText	= debugPrepQuery($update, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				$result		= $stmt->rowCount();
				if ($xml && $result > 0)
				    print "<$tagname result='$result'>$updateText</$tagname>\n";
		    }		// success
		    else
		    {		// error performing query
				throw new Exception('Picture::delete: "' .
							    $updateText . '", ' . 
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}		// if required update tblBP

		if ($idbpsound > 0)
		{		// if required update tblBP
		    $update	= 'UPDATE tblBP SET Used=0 ' .
							'WHERE IDBP=:idbpsound AND ' .
							'NOT EXISTS (SELECT * FROM tblBR ' .
						'WHERE IDBPPic=:idbpsound OR IDBPSound=:idbpsound)';
		    $sqlParms	= array('idbpsound'	=> $idbpsound);

		    // query the database
		    $stmt	= $connection->prepare($update);
		    $updateText	= debugPrepQuery($update, $sqlParms);
		    if ($stmt->execute($sqlParms))
		    {		// success
				$result		= $stmt->rowCount();
				if ($xml && $result > 0)
				    print "<$tagname result='$result'>$updateText</$tagname>\n";
		    }		// success
		    else
		    {		// error performing query
				throw new Exception('Picture::delete: \'$updateText\', ' . 
							    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}		// if required update tblBP

    }		// Picture::delete

    /********************************************************************
     *	function Picture::toHtml										*
     *																	*
     *  Generate the HTML to display the picture as part of a web page.	*
     *																	*
     *	Input:                                                          *
     *	    $template   boolean true to print result immediately        *
     *	                or boolean false to just return the result      *
     *	                or string containing an HTML template for       *
     *	                displaying the instance of Picture              *
     *																	*
     *	Returns:														*
     *	    String containing generated HTML                            *
     ********************************************************************/
    function toHtml($template   = true)
    {
        global $document_root;

        if (is_bool($template))
        {
            $tempBase		= $document_root . '/templates/';
            $template		= new Template($tempBase . 'PictureDefaulten.html');
            $print          = $template;
        }
        else
        if (is_string($template))
        {
            $template		= new Template($template);
            $print          = false;
        }
        else
        {
            throw new Exception("Picture::toHtml: invalid parameter " .
                print_r($template, true));
        }


		$picNameUrl	= $this->getURL();
		$picType	= $this->getType();
		$picCaption	= $this->getCaption();
		$picDate	= $this->getDate();
		$picDesc	= $this->getDesc();

		switch($picType)
		{		// interpret specific document types
		    case Picture::PIC_TYPE_PICTURE:
            {		// an image file
                $element    = $template->getElementById('pictureTemplate');
                if (is_null($element))
                {
                    print \Templating\escape($template->getRawTemplate());
                }
                $picTemp    = new Template($element->outerHTML());
		        $picTemp->set('PICNAMEURL', $picNameUrl);
		        $picTemp->set('PICCAPTION', $picCaption);
                $picTemp->set('PICDATE',    $picDate);
                if (strlen($picDesc) == 0)
                    $picTemp->updateTag('picDesc', null);
                else
                    $picTemp->set('PICDESC',    $picDesc);
				break;
		    }		// an image file

		    case Picture::PIC_TYPE_SOUND:
		    {		// a sound file
				$filetype	= substr($picNameUrl, strlen($picNameUrl)-3);
				if ($filetype == 'mp3')
				   $filetype	= 'mpeg';
                $element    = $template->getElementById('audioTemplate');
                $picTemp    = new Template($element->outerHTML());
		        $picTemp->set('PICNAMEURL', $picNameUrl);
		        $picTemp->set('FILETYPE',   $filetype);
				break;
		    }		// a sound file

		    case Picture::PIC_TYPE_VIDEO:
		    {		// video file
				$filetype	= substr($picNameUrl, strlen($picNameUrl)-3);
                $element    = $template->getElementById('videoTemplate');
                $picTemp    = new Template($element->outerHTML());
		        $picTemp->set('PICNAMEURL', $picNameUrl);
		        $picTemp->set('FILETYPE',   $filetype);
				break;
		    }		// a sound file

		    case Picture::PIC_TYPE_OTHER:
		    {		// other file
                $element    = $template->getElementById('otherTemplate');
                $picTemp    = new Template($element->outerHTML());
		        $picTemp->set('PICNAMEURL', $picNameUrl);
				break;
		    }		// other file

        }		// switch on document type

        $result     = $picTemp->compile();
        if ($print)
            print $result;
        return $result;
    }		// Picture::toHtml

}		// class Picture 
