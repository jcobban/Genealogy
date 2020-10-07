<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Source.inc															*
 *																		*
 *  Definition of a class representing a source in a genealogical		*
 *  database.  This class provides access to the information in a row	*
 *  of the table tblSR.													*
 *																		*
 *	Table: tblSR - Master Source List								    *
 *																		*
 *		IDSR					INTEGER(10)								*
 *								used by: tblSX.IDSR						*
 *		IDST					INTEGER(10)								*
 *								--> tblST.IDST, ID to Source Type		*
 *		SrcName					VARCHAR(255)							*
 *		SrcTitle				LONGTEXT								*
 *		SrcAuthor				VARCHAR(255)							*
 *		SrcPubl					LONGTEXT								*
 *		SrcText					LONGTEXT								*
 *								Text of Source							*
 *		pSrcText				TINYINT(1)								*
 *								1=include on reports, 0=no				*
 *								Not implemented							*
 *		fSrcText				TINYINT(1)								*
 *								1=print first time only, 0=each time	*
 *								Not implemented							*
 *		tSrcText				TINYINT(1)								*
 *								temporary use, set to 0 at beginning of	*
 *								report, then set to 1 first time source	*
 *								is printed.								*
 *								Not used by this implementation			*
 *		SrcNote					LONGTEXT								*
 *								Source Comments							*
 *		pSrcNote				TINYINT(1)								*
 *								1=include on reports, 0=no				*
 *								Not implemented							*
 *		fSrcNote				TINYINT(1)								*
 *								1=print first time only, 0=each time	*
 *								Not implemented							*
 *		tSrcNote				TINYINT(1)								*
 *								temporary use, set to 0 at beginning	*
 *								of report, then set to 1 first time		*
 *								source is printed.						*
 *								Not used by this implementation			*
 *		SrcCallNum				VARCHAR(255)							*
 *		SrcTag					TINYINT(1)								*
 *								1=tagged, 0=not							*
 *		qsTag					TINYINT(1)								*
 *								temporary use, 0=no, 1=yes				*
 *								Not used by this implementation			*
 *		SrcExclude				TINYINT(1)								*
 *								1=Exclude this Source from Reports		*
 *								0=Include								*
 *								Not implemented							*
 *		IDAR					INTEGER(10)								*
 *								--> tblAR.IDAR, ID to Repository		*
 *									Address Record						*
 *		IDAR2					INTEGER(10)								*
 *								--> tblAR.IDAR, ID to Repository		*
 *									Address Record						*
 *		EnteredSD				INTEGER(10)								*
 *								Sort Date internal encoding				*
 *		EnteredD				VARCHAR(100)							*
 *								Date internal encoding					*
 *		FilingRef				VARCHAR(50)								*
 *								user filing number						*
 *		Used					TINYINT(1)								*
 *								temporary use, 0=no, 1=yes				*
 *								Not used by this implementation			*
 *		Published				TINYINT(1)								*
 *								0=No, 1=Yes								*
 *								Not implemented							*
 *		Verified				TINYINT(1)								*
 *								0=No, 1=Yes								*
 *								Not implemented							*
 *		SrcMPub					VARCHAR(255)							*
 *		SrcRollNum				VARCHAR(255)							*
 *		TemplateID				INTEGER(10)								*
 *								ID to source template record			*
 *		Contents				LONGTEXT								*
 *		UseStandard				TINYINT(1)								*
 *								0=use professional template, 			*
 *								1=use standard format					*
 *								Not implemented							*
 *		Bibliography			TINYINT(1)								*
 *								0=no, 1=yes								*
 *								A flag used when printing a report to	*
 *								indicate that the source will be		*
 *								included in the bibliography at the 	*
 *								end of the report.						*
 *								Not used by this implementation			*
 *		Override				LONGTEXT								*
 *								Combine all three user override formats	*
 *								separated by \/\/  						*
 *								For example:							*
 *		Footnote override\/\/Subsequent Override\/\/Bibliograph Override*
 *								Not implemented							*
 *		OverrideFootnote		TINYINT(1)								*
 *								0=don't use the override paragraph, 	*
 *								1=use the override paragraph			*
 *								Not implemented							*
 *		OverrideSubsequent		TINYINT(1)								*
 *								0=don't use the override paragraph, 	*
 *								1=use the override paragraph			*
 *								Not implemented							*
 *		OverrideBibliograph		TINYINT(1)								*
 *								0=don't use the override paragraph, 	*
 *								1=use the override paragraph			*
 *								Not implemented							*
 *																		*
 *  History:															*
 *		2010/08/13		Add translation of source types from tblST		*
 *		2010/10/11		correct reference to connection					*
 *		2010/10/20		changed parent constructor						*
 *		2010/10/23		connection made global							*
 *		2011/01/31		add set methods for major fields				*
 *						providing validation of new values				*
 *		2011/05/23		handle exception from Address constructor		*
 *		2012/01/13		rename classes to have upper case character		*
 *		2012/08/15		support construction with id 'new'				*
 *		2012/10/06		use LegacyRecord::setField to manipulate record	*
 *						fields so save method will correctly construct	*
 *						appropriate INSERT or UPDATE to synchronize the	*
 *						database record.								*
 *						All set methods return former value				*
 *		2012/10/20		standardize initialization of new record		*
 *		2013/06/12		add method toXml to interpret internal values	*
 *		2013/06/18		add missing include of LegacyDate				*
 *		2013/08/09		base class renamed to Record					*
 *		2014/01/30		add ability to obtain instance by name			*
 *						method getPubl renamed to getPublisher			*
 *						method setPubl renamed to setPublisher			*
 *		2014/08/23		add documentation of SQL record layout			*
 *						add static method getSources					*
 *						add static method deleteSources					*
 *						add static method updateSources					*
 *						add documentation of return values of methods	*
 *		2014/09/05		static methods getSources, deleteSources,		*
 *						updateSources use shared static method			*
 *						getWhere to consistently construct WHERE clause	*
 *						support array of IDSR values, and date range	*
 *						in getSources, deleteSources, and updateSources	*
 *		2014/10/01		add method addOwner								*
 *		2014/11/30		validate parameters to constructor more			*
 *						constructor associative array constructor		*
 *		2014/12/08		diagnostic output redirected to $warn			*
 *		2014/12/25		add method getCitations							*
 *		2015/01/10		create new record failed						*
 *		2015/03/04		new value was put into field name 'fldname'		*
 *		2015/04/27		escape < and > in SQL commands displayed in		*
 *						XML or HTML										*
 *		2015/05/27		support an array of values for static methods	*
 *						getSource, deleteSources, and updateSources		*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *						table to interpret ST entries missing 16		*
 *		2015/12/08		base class changed to RecordPic					*
 *		2016/04/28		change toXML to always return the XML and		*
 *						only print it by default						*
 *		2017/07/20		parameter list to logSqlUpdate changed			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/04		class LegacyAddress renamed to class Address	*
 *		2017/09/13		add constants for source type field 'idst'		*
 *						do not throw exception for bad field names		*
 *						in constructor									*
 *						parameter values null or array() to constructor	*
 *						create new record								*
 *		2017/10/04		add methods get( and set(						*
 *						add standard field name translation table		*
 *						extend functionality of search in getWhere		*
 *						use PDO											*
 *		2017/10/11		in method toXml only expand IDAR and IDAR2 if	*
 *						requested										*
 *		2017/11/18		use CensusSet									*
 *		2017/12/18		add support for temporary fields				*
 *		2018/01/19		create new Source was broken					*
 *		2018/04/03		correct behavior of Source::OffsetExists for	*
 *						the pseudo-fields 'typetext' and 'callnumurl'	*
 *		2018/12/02      support field synonyms in constructor           *
 *		                report constructor errors through $this->msg    *
 *		2019/04/15      replace is_a with instanceof                    *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';

/************************************************************************
 *  class Source														*
 *																		*
 *  Definition of a class implementing behavior for source records		*
 *  within a genealogy database.										*
 *																		*
 ************************************************************************/

class Source extends RecordPic
{

    /********************************************************************
     *	Source::$initRow												*
     *																	*
     *	Default initial row for a record in table Source				*
     ********************************************************************/
    protected static	$initRow	= array(
						'idsr'					=> 0,
						'srcname'				=> '',
						'srctitle'				=> '',
						'idst'					=> 1,
						'srcauthor'				=> '',
						'srcpubl'				=> '',
						'srctext'				=> '',
						'psrctext'				=> 0,
						'fsrctext'				=> 0,
						'tsrctext'				=> 0,
						'srcnote'				=> '',
						'psrcnote'				=> 0,
						'fsrcnote'				=> 0,
						'tsrcnote'				=> 0,
						'srccallnum'			=> '',
						'srctag'				=> 0,
						'qstag'					=> 0,
						'srcexclude'			=> 0,
						'idar'					=> 0,
						'idar2'					=> 0,
						'enteredsd'				=> -99999999,
						'enteredd'				=> '',
						'filingref'				=> '',
						'used'					=> 0,
						'published'				=> 0,
						'verified'				=> 0,
						'srcmpub'				=> '',
						'srcrollnum'			=> '',
						'templateid'			=> 0,
						'contents'				=> '',
						'usestandard'			=> 0,
						'bibliography'			=> 0,
						'override'				=> '',
						'overridefootnote'		=> 0,
						'overridesubsequent'	=> 0,
						'overridebibliography'	=> 0);

    /********************************************************************
     *	Source::$translate												*
     *																	*
     *	standard alternative field name identifiers						*
     ********************************************************************/
    protected static	$translate	= array(
						'name'					=> 'srcname',
						'title'					=> 'srctitle',
						'author'				=> 'srcauthor',
						'type'					=> 'idst',
						'publ'					=> 'srcpubl',
						'publisher'				=> 'srcpubl',
						'text'					=> 'srctext',
						'note'					=> 'srcnote',
						'notes'					=> 'srcnote',
						'comments'				=> 'srcnote',
						'callnum'				=> 'srccallnum',
						'tag'					=> 'srctag',
						'exclude'				=> 'srcexclude',
						'mpub'					=> 'srcmpub',
						'rollnum'				=> 'srcrollnum',
						'printsourcetext'		=> 'psrctext',
						'printtext'				=> 'psrctext',
						'printsourcenote'		=> 'psrcnote',
						'printnote'				=> 'psrcnote');

    /********************************************************************
     *	Source::$defaultOrder											*
     *																	*
     *	The default contents of the ORDER BY clause in SELECTs			*
     ********************************************************************/
    protected static	$defaultOrder	= 'SrcName';

    /********************************************************************
     *	Source::$intType												*
     *	Class specific constants and textual interpretation of the 		*
     *	type field `IDST`												*
     ********************************************************************/
    const ST_UNSPECIFIED		= 1;
    const ST_BIBLE				= 2;
    const ST_BOOK				= 3;
    const ST_CENSUS_TAX			= 4;
    const ST_CHURCH				= 5;
    const ST_COURT				= 6;
    const ST_INTERVIEW			= 7;
    const ST_JOURNAL			= 8;
    const ST_LAND				= 9;
    const ST_LETTER				= 10;
    const ST_MILITARY			= 11;
    const ST_NEWSPAPER			= 12;
    const ST_PERIODICAL			= 13;
    const ST_PERSONAL			= 14;
    const ST_VITAL				= 15;
    const ST_WEB_SITE			= 16;
    const ST_MAX				= 16;	// highest assigned code

    /********************************************************************
     *	Source::$intType												*
     *	For internationalization use $translate['srcTypes']             *
     ********************************************************************/
    static $intType	= array(
				 1 		=>	'',
				 2 		=>	'Bible',
				 3 		=>	'Book',
				 4 		=>	'Census/Tax',
				 5 		=>	'Church',
				 6 		=>	'Court',
				 7 		=>	'Interview',
				 8 		=>	'Journal',
				 9 		=>	'Land',
				10 		=>	'Letter',
				11 		=>	'Military',
				12 		=>	'Newspaper',
				13 		=>	'Periodical',
				14 		=>	'Personal',
				15 		=>	'Vital',
				16 		=>	'Web-Site'
    );

    /********************************************************************
     *  function Source::__construct									*
     *																	*
     *  Given the identifier extract information about a source.		*
     *																	*
     *  Input:															*
     *	    $parms		an associative array containing all of the		*
     *					fields in a record with field names in lower	*
     *					case, for example as read from the database		*
     *																	*
     *					or an associative array of search parameters	*
     *					containing either 'idsr' or 'srcname' to		*
     *					select an existing record.  If it does not		*
     *					contain either 'idsr' or 'srcname' a new		*
     *					record is created.								*
     *																	*
     *					The following options are deprecated:			*
     *					numeric identifier of a source (key of tblSR)	*
     *							If is 0, create a new empty record		*
     *							for later insertion into the database.	*
     *					or the string name of a source					*
     *							If is 'new', create a new empty record	*
     *							for later insertion into the database.	*
     *																	*
     *	For example:													*
     *																	*
     *	    new Source();				create new empty entry			*
     *	    new Source(array('idsr' => 127));							*
     *	    $familyTree->getSource(127);    factory method              *
     *	    new Source(array('srcname' => 'London, England'));			*
     *	    new Source(array('srcname' => 'Principia Mathematica',		*
     *					     'srcauthor' => 'Isaac Newton',				*
     *					     'idst' => Source::ST_BOOK));				*
     *																	*
     *  Returns object containing source record.						*
     *																	*
     *	Errors are accumulated in $this->msg                            *
     ********************************************************************/
    function __construct($parms = null)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

		$dbrow	= null;

		// action depends upon type of first parameter
		$idsr		        = null;
		$name		        = null;
		if (is_null($parms))
		{                   // no parameters specified
		    $parms		    = array();
		    $idsr		    = 0;
		}                   // no parameters specified
		else
		if ($parms == 'new')
		{                   // obsolete option to create new default record
		    $parms		    = array();
		    $idsr		    = 0;
		}                   // obsolete option to create new default record
		else
		if (is_string($parms))
		{
		    if (ctype_digit($parms))
		    {
				$idsr		= intval($parms);
				$parms		= array('idsr' => $idsr);
		    }
		    else
		    {
				$name		= $parms;
				$parms		= array('srcname' => $name);
		    }
		}
		else
		if (is_int($parms))
		{
		    $idsr		    = $parms;
		    $parms		    = array('idsr' => $idsr);
		}
		else
		if (is_array($parms))
		{		            // array
		}		            // array
		else
		{		            // unrecognized
		    $this->msg      .= "Source::__construct: " .
								"Invalid parameter: " .
                                gettype($parms) . ", value=" . 
                                print_r($parms, true) .
                                ".\n";
		    $parms		    = array();
		    $idsr		    = 0;
		}		            // unrecognized

		// $parms is the parameters in an associative array
		$where		        = '';
		$and		        = 'WHERE ';
		$sqlParms	        = array();
		
		foreach($parms as $field => $value)
		{			        // loop through all field names
	        $fieldLc		= strtolower($field);
	    	if (array_key_exists($fieldLc, self::$translate))
	    	    $fieldLc	= self::$translate[$fieldLc];
	    	switch($fieldLc)
		    {			    // check specific field names
				case 'idsr':
				{
				    if (is_int($value) || ctype_digit($value))
				    {		// have a number
						$idsr			= intval($value);
						if ($idsr > 0)
						{
						    $where		.= $and . "`IDSR`=:idsr";
						    $and		= ' AND ';
						    $sqlParms['idsr']	= $idsr;
						}
				    }		// have a number
				    else
						$this->msg	.= "Source::__construct: invalid IDSR='$value'. ";
				    break 2;
				}

				case 'srcname':
				{
				    $name			    = $value;
				    $where			    .= $and . "`SrcName`=:srcname";
				    $and			    = ' AND ';
				    $sqlParms['srcname']= $name;
				    break 2;
				}

		    }			    // check specific field names
		}			        // loop through all field names

		if (count($parms) < count(self::$initRow))
		{			        // search parameters
		    // current date in internal forms
		    $currdate	    = getdate();
		    $ldate		    = 1000000*$currdate['mday'] +
						    		  10000*$currdate['mon'] +
						    		  $currdate['year'];
		    if ($ldate < 10000000)
				$ldate		= '000' . $ldate . '00000000';
		    else
				$ldate		= '00' . $ldate . '00000000';
		    $sdate	    	= 10000*$currdate['year'] +
					    			  100*$currdate['mon'] +
					    			  $currdate['mday'];

		    // construct the query of the source table
		    if (strlen($where) > 0)
		    {			    // search for existing record
				$query		= 'SELECT * FROM tblSR ' . $where;
                $stmt		= $connection->prepare($query);
                $queryText  = debugPrepQuery($query, $sqlParms);

				// query the database
				if ($stmt->execute($sqlParms))
				{		    // successful
					$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
					if ($debug)
					    $warn	.= "<p>Source.inc: " . __LINE__ .
									    "query='$queryText'</p>\n";

				    if (count($result) > 0)
				    {		// found source record
						$dbrow		        = $result[0];
						$needInsert	        = false;
                 		// invoke constructor of base class
    		            parent::__construct($dbrow,
    						                'tblSR');
						foreach($parms as $fldname => $value)
						{		// initialize any other fields
						    $fldnameLc		= strtolower($fldname);
            		    	if (array_key_exists($fieldLc, self::$translate))
	            	    	    $fieldLc	= self::$translate[$fieldLc];
            		    	switch($fieldLc)
            			    {		    // check specific field names
            					case 'idsr':
            					{
            					    break;
                                }

            					case 'template':
                                {
                                    if ($value instanceof Template)
                                        $this->template     = $value;
            					    break;
                                }

                                default:
                                {
						            if (array_key_exists($fldnameLc, $dbrow))
								        $this->set($fldnameLc, $value);
						            else
								        $this->msg	.= "Source::__construct: " .
									"Invalid field name='$fldname'. ";
            					    break;
                                }
                            }           // check specific field names
						}		        // initialize any other fields
				    }		// found source record
				    else
				    {		// create a new record
						// set defaults
						$dbrow			    = self::$initRow;
						$dbrow['enteredsd']	= $sdate;
						$dbrow['enteredd']	= $ldate;

						foreach($parms as $fldname => $value)
						{		// initialize any other fields
						    $fldnameLc		= strtolower($fldname);
            		    	if (array_key_exists($fieldLc, self::$translate))
	            	    	    $fieldLc	= self::$translate[$fieldLc];
            		    	switch($fieldLc)
            			    {		    // check specific field names
            					case 'idsr':
            					{
            					    break;
                                }

                                default:
                                {
						    if (array_key_exists($fldnameLc, $dbrow))
								$dbrow[$fldnameLc]	= $value;
						    else
								$this->msg	.= "Source::__construct: " .
									"Invalid field name='$fldname'. ";
            					    break;
                                }
                            }           // check specific field names
						}		// initialize any other fields
						$needInsert	= true;
				    }		    // create new record
				}		        // successful
				else
				{		        // error performing query
				    $this->msg  .= "Source::__construct: '$queryText', " .
								print_r($stmt->errorInfo(),true);
					$dbrow			    = self::$initRow;
				}		        // error performing query
		    }			        // search for existing record
		    else
		    {			        // no search parameters
				// set defaults
				$dbrow			        = self::$initRow;
				$dbrow['enteredsd']	    = $sdate;
				$dbrow['enteredd']	    = $ldate;
				$needInsert		        = true;
		    }			        // no search parameters
		}			            // search parameters
		else
		{			            // full row returned from database query
		    $dbrow		                = $parms;
		    $needInsert	            	= false;
		}			            // full row returned from database query

        // invoke constructor of base class
        if (is_null($this->row))
    		parent::__construct($dbrow,
    						    'tblSR');
		$this->needInsert	            = $needInsert;

		// initialize pseudo-fields
		if (substr($this->row['srccallnum'], 0, 4) == 'http')
		    $this->extras['callnumurl']	= $this->row['srccallnum'];
		else
            $this->extras['callnumurl']	= null;
        if ($this->template)
        {                       // internationalization support
            $this->setTemplate($this->template);
        }                       // internationalization support
        else
		    $this->extras['typetext']		= $this->getTypeText();

		// diagnostic output if debug is set
		$this->dump('Source Record constructed:');
    }		//	function Source::__construct

    /********************************************************************
     *	function Source::setTemplate									*
     *																	*
     *  If an instance of Record is constructed from a database row     *
     *  if is not possible to pass a template as a parameter to the     *
     *  constructor. This method explicitly associates an               *
     *  internationalization template with this instance.               *
     *																	*
     *	Parameters:														*
     *	    $newvalue   instance of class Template                      *
     *																	*
     *	Returns:														*
     *	    Previous setting                                            *
     ********************************************************************/
    function setTemplate($template)
    {
        global $warn;

        $stSet                          = array();
        $srcTypesElt                    = $template['srcTypes'];
        if ($srcTypesElt)
        {
        	foreach($srcTypesElt->childNodes() as $span)
        	{
        	    $key		            = $span->attributes['data-key'];
                $stSet[$key]	        = trim($span->innerHTML()); 
            }
            $idst                       = $this->row['idst'];
    		$this->extras['typetext']	= $stSet[$idst];
        }
        else
            $this->extras['typetext']	= $this->getTypeText();
        $typetext   = $this->extras['typetext'];
   
        $oldvalue                       = $this->template;
        if ($template instanceof Template)
            $this->template             = $template;
        return $oldvalue;
    }           // function Source::setTemplate

    /********************************************************************
     *	function Source:set												*
     *																	*
     *	Change the value of a field in the object.						*
     *	The method validates that the supplied value is compatible with *
     *	the target field.												*
     *																	*
     *	Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
     *																	*
     *	Returns:														*
     *			previous value of the field								*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
		$fieldLc		= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{		// act on specific field
		    case 'idsr':
		    case 'srcname':
		    case 'srctitle':
		    case 'srcauthor':
		    case 'srcpubl':
		    case 'srctext':
		    case 'srcnote':
		    case 'srccallnum':
		    case 'idar':
		    case 'idar2':
		    case 'enteredsd':
		    case 'enteredd':
		    case 'filingref':
		    case 'srcmpub':
		    case 'srcrollnum':
		    case 'templateid':
		    case 'contents':
		    case 'bibliography':
		    case 'override':
				return parent::set($fieldLc, $value);

		    case 'idst':
				return $this->setType($value);

		    case 'psrctext':
		    case 'fsrctext':
		    case 'tsrctext':
		    case 'psrcnote':
		    case 'fsrcnote':
		    case 'tsrcnote':
		    case 'srctag':
		    case 'qstag':
		    case 'srcexclude':
		    case 'used':
		    case 'published':
		    case 'verified':
		    case 'usestandard':
		    case 'overridefootnote':
		    case 'overridesubsequent':
		    case 'overridebibliography':
		    {			// boolean fields
				if ($value)
				    return parent::set($fieldLc, 1);
				else
				    return parent::set($fieldLc, 0);
		    }		// boolean fields

		    default:
		    {
				return parent::set($fieldLc, $value);
		    }
		}		// act on specific field
    }		//	function Source::set

    /********************************************************************
     *	function Source::getIdsr										*
     *																	*
     *	Retrieve the unique numeric key of this instance of				*
     *  Source.															*
     *																	*
     *	Note that this value is not available for a new Source			*
     *  record, one created with index value 0 or a string name that	*
     *	does not match any existing source, until after the method		*
     *	save() has been called, because only then does the database		*
     *	server assign a value for the key.  Until then this method		*
     *  returns 0.														*
     *																	*
     *	Returns:														*
     *			numeric index of instance								*
     ********************************************************************/
    function getIdsr() { return $this->row['idsr']; }

    /********************************************************************
     *  function Source::getType										*
     *																	*
     *	Get the numeric type code of the master source.					*
     *																	*
     *	Returns:														*
     *		value of field 'idst'.  See Source::$intType				*
     ********************************************************************/
    function getType()
    {
		return $this->row['idst'];
    }		//	function Source::getType

    /********************************************************************
     *  function Source::setType										*
     *																	*
     *	Set the numeric type code of the master source.					*
     *																	*
     *	Parameters:														*
     *	    $newType		new value for IDST							*
     *																	*
     *	Returns:														*
     *	    previous type												*
     *																	*
     *	Throws Exception on bad parameter.								*
     ********************************************************************/
    function setType($newType)
    {
		global	$connection;
		global	$warn;

		$oldType	= $this->row['idst'];
		if ($newType < 1)
		{
		    error_log("Source::setType: " . __LINE__ . 
						"Source::setType: invalid new type=$newType\n");
		    $warn	.= "<p>Source::setType: " . __LINE__ . 
						"Source::setType: invalid new type=$newType</p>\n";
		}
		else
		if ($newType > self::ST_MAX)
		{		// out of range of predefined values
		    error_log("Source::setType: " . __LINE__ . 
	    				"Source::setType: invalid new type=$newType\n");
		    $warn	.= "<p>Source::setType: " . __LINE__ . 
						"Source::setType: invalid new type=$newType</p>\n";
        }		// out of range of predefined values

        $template       = $this->template;
        if ($template)
            $this->setTemplate($template);  // update "typetext"
		return parent::set('idst', $newType);
    }		//	function Source::setType

    /********************************************************************
     *  function Source::getTypeText									*
     *																	*
     *	Get the textual interpretation of the type code of the			*
     *	master source.													*
     *	Note this does not support internationalization.                *
     *																	*
     *	Returns:														*
     *			textual interpretation of field 'idst' in English		*
     ********************************************************************/
    function getTypeText()
    {
		global	$debug;
		global	$warn;
		global	$connection;

		if (array_key_exists('typetext', $this->extras))
            return $this->extras['typetext'];

        if ($this->template)
        {                       // internationalization support
            $stSet                      = array();
            $srcTypesElt                = $this->template['srcTypes'];
            if ($srcTypesElt)
            {
            	foreach($srcTypesElt->childNodes() as $span)
            	{
            	    $key		            = $span->attributes['data-key'];
                    $stSet[$key]	        = trim($span->innerHTML()); 
                }
		        $this->extras['typetext']	= $stSet[$idst];
                return $this->extras['typetext'];
            }
        }                       // internationalization support

		$type		= $this->row['idst'];
		if (array_key_exists($type, self::$intType))
		    return self::$intType[$type];
        else
		    return 'Unknown Source Type: ' . $type;
    }		//	function Source::getTypeText

    /********************************************************************
     *  function Source::getName										*
     *																	*
     *	Get the name of the master source.								*
     *																	*
     *	Returns:														*
     *			name used to reference this source in bibliography		*
     ********************************************************************/
    function getName($options	= null)
    {
		return $this->row['srcname'];
    }		//	function Source::getName

    /********************************************************************
     *  function Source::setName										*
     *																	*
     *	Set the name of the master source.								*
     *																	*
     *	Parameters:														*
     *	    $newName	new value for source name						*
     *																	*
     *	Returns:														*
     *	    previous name												*
     ********************************************************************/
    function setName($newName)
    {
		return parent::set('srcname', $newName);
    }		//	function Source::setName

    /********************************************************************
     *  function Source::getTitle										*
     *																	*
     *	Get the title of the master source.								*
     *																	*
     *	Returns:														*
     *		formal title of the document associated with the source		*
     ********************************************************************/
    function getTitle()
    {
		if (array_key_exists('srctitle', $this->row))
		    return $this->row['srctitle'];
		else
		{               // missing expected field name
		    foreach($this->row as $key => $value)
				$warn	.= "<p>$key = $value</p>\n";
		    return 'Error';
		}               // missing expected field name
    }		//	function Source::getTitle

    /********************************************************************
     *  function Source::setTitle										*
     *																	*
     *	Set the title of the master source.								*
     *																	*
     *	Parameters:														*
     *	    $newTitle	new value for source title						*
     *																	*
     *	Returns:														*
     *	    previous title												*
     ********************************************************************/
    function setTitle($newTitle)
    {
		return parent::set('srctitle', $newTitle);
    }		//	function Source::setTitle

    /********************************************************************
     *  function Source::getAuthor										*
     *																	*
     *	Get the author of the master source.							*
     *																	*
     *	Returns:														*
     *		name of the author of the document							*
     ********************************************************************/
    function getAuthor()
    {
		return $this->row['srcauthor'];
    }		//	function Source::getAuthor

    /********************************************************************
     *  function Source::setAuthor										*
     *																	*
     *	Set the author of the master source.							*
     *																	*
     *	Parameters:														*
     *	    $newAuthor		new value for source author					*
     *																	*
     *	Returns:														*
     *	    previous author												*
     ********************************************************************/
    function setAuthor($newAuthor)
    {
		return parent::set('srcauthor', $newAuthor);
    }		//	function Source::setAuthor

    /********************************************************************
     *  function Source::getPublisher									*
     *																	*
     *	Get the publisher of the master source.							*
     *																	*
     *	Returns:														*
     *		name of the publisher of the document						*
     ********************************************************************/
    function getPublisher()
    {
		return $this->row['srcpubl'];
    }		//	function Source::getPubl

    /********************************************************************
     *  function Source::setPublisher									*
     *																	*
     *	Set the publisher of the master source.							*
     *																	*
     *	Parameters:														*
     *	    $newPubl	new value for publisher							*
     *																	*
     *	Returns:														*
     *	    previous publisher											*
     ********************************************************************/
    function setPublisher($newPubl)
    {
		return set('srcpubl', $newPubl);
    }		//	function Source::setPubl

    /********************************************************************
     *  function Source::getSourceText									*
     *																	*
     *	Get the text of the master source.								*
     *																	*
     *	Returns:														*
     *			A section of text from the source document.				*
     ********************************************************************/
    function getSourceText()
    {
		return $this->row['srctext'];
    }		//	function Source::getSourceText

    /********************************************************************
     *  function Source::setSourceText									*
     *																	*
     *	Set the source text of the master source.						*
     *																	*
     *	Parameters:														*
     *	    $newSourceText		new value for source text				*
     *																	*
     *	Returns:														*
     *	    previous source text										*
     ********************************************************************/
    function setSourceText($newSourceText)
    {
		return parent::set('srctext', $newSourceText);
    }		//	function Source::setSourceText

    /********************************************************************
     *  function Source::setPrintSourceText								*
     *																	*
     *	Set the flag to include the source text in reports.				*
     *																	*
     *	Parameters:														*
     *	    $include	true to include, false to exclude				*
     *																	*
     *	Returns:														*
     *	    previous flag setting										*
     ********************************************************************/
    function setPrintSourceText($include)
    {
		if ($include)
		    return parent::set('psrctext', 1);
		else
		    return parent::set('psrctext', 0);
    }		//	function Source::setPrintSourceText

    /********************************************************************
     *  function Source::getNote										*
     *																	*
     *	Get the note of the master source.								*
     *																	*
     *	Returns:														*
     *	    Descriptive note about the source document.					*
     ********************************************************************/
    function getNote()
    {
		return $this->row['srcnote'];
    }		//	function Source::getNote

    /********************************************************************
     *  function Source::setNote										*
     *																	*
     *	Set the note of the master source.								*
     *																	*
     *	Parameters:														*
     *	    $newNote	new value for source note						*
     *																	*
     *	Returns:														*
     *	    previous note												*
     ********************************************************************/
    function setNote($newNote)
    {
		return parent::set('srcnote', $newNote);
    }		//	function Source::setNote

    /********************************************************************
     *  function Source::setPrintSourceNote								*
     *																	*
     *	Set the flag to include the source note in reports.				*
     *																	*
     *	Parameters:														*
     *	    $include	true to include, false to exclude				*
     *																	*
     *	Returns:														*
     *	    previous setting of flag									*
     ********************************************************************/
    function setPrintSourceNote($include)
    {
		if ($include)
		    return parent::set('psrcnote', 1);
		else
		    return parent::set('psrcnote', 0);
    }		//	function Source::setPrintSourceNote

    /********************************************************************
     *  function Source::getCallNum										*
     *																	*
     *	Get the call number of the master source.						*
     *																	*
     *	Returns:														*
     *	    the call number assigned by the primary repository, for		*
     *	    example an LC Number or Dewey Decimal Number				*
     ********************************************************************/
    function getCallNum()
    {
		return $this->row['srccallnum'];
    }		//	function Source::getCallNum

    /********************************************************************
     *  function Source::setCallNum										*
     *																	*
     *	Set the call number of the master source.						*
     *																	*
     *	Parameters:														*
     *	    $newCallNum		new value for source call number			*
     *																	*
     *	Returns:														*
     *	    previous call number										*
     ********************************************************************/
    function setCallNum($newCallNum)
    {
		return parent::set('srccallnum', $newCallNum);
    }		//	function Source::setCallNum

    /********************************************************************
     *  function Source::getRepository									*
     *																	*
     *	Get the repository associated with the master source.			*
     *																	*
     *	Returns:														*
     *	    instance of Address or null if not set						*
     ********************************************************************/
    function getRepository()
    {
		$idar	= $this->row['idar'];
		if ($idar > 0)
		{
		    try {
				return new Address(array('idar' => $idar));
		    }
		    catch (Exception $e)
		    {
				print "<p>" . $e->getMessage() . ' for IDSR=' . $this->getIdsr() .
						': ' . $this->getTitle() . "</p>\n";
				return null;
		    }
		}
		else
		    return null;
    }		//	function Source::getRepository

    /********************************************************************
     *  function Source::setRepository									*
     *																	*
     *	Set the repository of the master source.						*
     *																	*
     *	Parameters:														*
     *	    $newName	name of new repository, or						*
     *					IDAR value, or									*
     *					instance of Address								*
     *																	*
     *	Returns:														*
     *		instance of Address for former value or						*
     *		null if not previously set									*
     ********************************************************************/
    function setRepository($newName)
    {
        global  $warn;

		$oldRepository	    = $this->getRepository();
		if (is_int($newName) || ctype_digit($newName))
		    $newRepository	= new Address(array('idar' => $newName));
		else
		if (is_string($newName))
		    $newRepository	= new Address(array('addrname' => $newName,
									    'kind' => ADDRESS::REPOSITORY));
		else
		if ($newName instanceof Address)
		    $newRepository	= $newName;
        else
        {                       // ignore invalid value
		    $warn    .=
				"Source::setRepository: invalid parameter type=" .
                gettype($newName) . ", value=" . $newName;
		    return	$oldRepository;
        }                       // ignore invalid value

        // note that the value of $newRepository->getIdar() is always
        // the non-zero identifier of a database record
		parent::set('idar', $newRepository->getIdar());
		return	$oldRepository;
    }		//	function Source::setRepository

    /********************************************************************
     *  function Source::toXml											*
     *																	*
     *  Emit an XML representation of the record.						*
     *																	*
     *  Most fields in the record are represented by a child element	*
     *	with the name of the field containing the internal value as text*
     *																	*
     *	Each field whose value is an instance of LegacyDate appears as	*
     *	two elements.  The internal encoded string value appears in an	*
     *	element with 'c' appended to the field name, while the element	*
     *	with the original field name contains an external textual		*
     *	(human-readable) representation of the date.  Note that the		*
     *	"sorted date" field appears with its internal value.			*
     *																	*
     *	Parameters:														*
     *	    $top		tag name for top node, default 'source'			*
     *	    $print		if true print the output, else just return it	*
     *	    $options		1		interpret IDAR and IDAR2			*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/

    /********************************************************************
     *		Constant masks for options in 3rd parameter					*
     ********************************************************************/
    const	TOXML_INCLUDE_REPOSITOR		= 1;

    function toXml($top = 'source',
				   $print = true,
				   $options = 0)
    {
		global	$userid;
		global	$authorized;

		$idsr		= $this->row['idsr'];
		$retval		= "<$top idsr='$idsr'>\n";

		foreach($this->row as $fld => $value)
		{		// loop through all fields
		    switch($fld)
		    {
				case 'idar':
				{	// IDAR of repository
				    $retval	.= "    <$fld>" . $value . "</$fld>\n";
				    if (($options & self::TOXML_INCLUDE_REPOSITOR) &&
						$value > 0)
				    {
						try {
						    $repo	= new Address(array('idar' => $value));
						    $retval	.= $repo->toXml('repository', false);
						} catch (Exception $e) {
						    $retval	.= "<msg>" . $e->getMessage() .
									   "</msg>\n";
						}
				    }
				    break;
				}

				case 'idar2':
				{	// IDAR of repository
				    $retval	.= "    <$fld>" . $value . "</$fld>\n";
				    if (($options & self::TOXML_INCLUDE_REPOSITOR) &&
						$value > 0)
				    {
						try {
						    $repo	= new Address(array('idar' => $value));
						    $retval	.= $repo->toXml('repository', false);
						} catch (Exception $e) {
						    $retval	.= "<msg>" . $e->getMessage() .
									   "</msg>\n";
						}
				    }
				    break;
				}

				case 'enteredd':
				{		// legacy internal date format
				    $date	= new LegacyDate($value);
				    $retval	.= "    <{$fld}c>" . xmlentities($value) .
								       "</{$fld}c>\n";
				    $value	= $date->toString();
				    $retval	.= "    <$fld>" . xmlentities($value) .
								       "</$fld>\n";
				    break;
				}		// legacy internal date format

				case 'idst':
				{	// document type
				    $retval	.= "    <$fld>" . $value . "</$fld>\n";
				    $value	= $this->getTypeText();
				    $retval	.= "    <type>" . xmlentities($value) .
								       "</type>\n";
				    break;
				}

				default:
				{		// fields passed back with no interpretation
				    $retval	.= "    <$fld>" . xmlentities($value) .
								       "</$fld>\n";
				    break;
				}		// fields passed back with no interpretation
		    }		// switch on field name
		}		// loop through all fields

		$retval	.= "</$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }		//	function Source::toXml

    /********************************************************************
     *  function Source::getCitations									*
     *																	*
     *  Get a RecordSet of instances of Citation that cite the			*
     *  current source.													*
     *																	*
     *  Parameters:														*
     *	    $limit		maximum number of instances, default none		*
     *	    $offset		offset to continue from, default 0				*
     *																	*
     *	Returns:														*
     *	    If $limit is not specified or is an integer > 0 then an		*
     *	    RecordSet of instances of Citation indexed by IDSX.  Use:	*
     *			foreach(getCitations(parms) as $idsx => $citation)		*
     *	    If $limit is 0 then returns the number of citations that	*
     *	    would have been returned if $limit had not been specified.	*
     ********************************************************************/
    function getCitations($limit	= null,
						  $offset	= 0)
    {
		if (is_null($limit))
		    $citParms	= array('idsr'	=> $this->row['idsr']);
		else
		    $citParms	= array('idsr'	=> $this->row['idsr'],
								'limit'	=> $limit,
								'offset' => $offset);

		$citations	= new CitationSet($citParms);

		if ($limit === 0)
		{
		    $info	= $citations->getInformation();
		    return $info['count'];
		}
		else
		    return $citations;
    }		//	function Source::getCitations

}		// class Source 

