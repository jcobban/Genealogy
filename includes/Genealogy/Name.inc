<?php
namespace Genealogy;
use \PDO;
use \Exception;
use Templating\Template;
use Templating\TemplateTag;

/************************************************************************
 *  Name.inc															*
 *																		*
 *  Definition of a class representing a name index record in a			*
 *  genealogical database.										 		*
 *  This class provides access to the information in a row of the		*
 *  table tblNX.														*
 *																		*
 *  Although these records are described as "Alternate Name" records,	*
 *  the vast majority of them document the primary name(s) by which an	*
 *  individual is known.  For most men there is only one record,		*
 *  tracking the birth name of the individual.  For married women who	*
 *  took their husband's surname at marriage there is one record for	*
 *  the birth name, and one for each married name.  This table is used	*
 *  to populate the nominal index of individuals in the database either	*
 *  including or excluding married names.								*
 *																		*
 *  The following is adapted from the internal documentation of Legacy	*
 *  Family Tree, and is copyright Millenium Corporation.				*
 *																		*
 *     Table: tblNX - Alternate Name List.								*
 *																		*
 *     Columns															*
 *		  Name					  Type									*
 *		  IDNX					  INTEGER(10)							*
 *		  IDIR					  INTEGER(10)							*
 *				--> tblIR.IDIR, ID to Person Record						*
 *		  Surname				  VARCHAR(120)							*
 *		  SoundsLike			  VARCHAR(4)							*
 *		  GivenName				  VARCHAR(120)							*
 *		  Prefix				  VARCHAR(120)							*
 *		  Title					  VARCHAR(120)							*
 *		  UserRef				  VARCHAR(50)							*
 *		  Order					  SHORTINT(5)							*
 *				0  = main name                                          *
 *				-1 = married names                              		*
 *				1  = 1st Alt Name, 										*
 *				2  = 2nd Alt Name, etc...								*
 *		  MarriedNameCreatedBy	  TINYINT(3)							*
 *				0  = not a married name,								*
 *				1  = rule generated married name, 						*
 *				2  = user created married name							*
 *		  BirthSD				  INTEGER(10)							*
 *				(see Sort Date)											*
 *				Unused by this implementation                           *
 *		  PreferredAKA			  TINYINT(3)							*
 *				0=no, 1=yes												*
 *		  AKANote				  LONGTEXT								*
 *		  IDMR	                  INTEGER(10)							*
 *				--> tblMR.ID,  ID to Marriage Record					*
 *				Set to NULL for all names except Married names			*
 *		  SrchTag				  TINYINT(3)							*
 *				0=no, 1=yes												*
 *				Unused by this implementation                           *
 *		  qsTag					  TINYINT(3)							*
 *				temporary use, 0=no, 1=yes								*
 *				Unused by this implementation                           *
 *		  `TreeName` VARCHAR(63) NOT NULL DEFAULT '',                   *
 *																		*
 *  The design of this table in the original implementation violated    *
 *  good design for SQL databases in that the information was           *
 *  replicated in the Persons (tblIR), and Families (tblMR) tables      *
 *  and the values needed to be synchronized across changes.            *
 *  The only values of GivenName and Surname for an individual that     *
 *  are now guaranteed to be maintained are those in this table.        * 
 *  Furthermore this table contained two unique keys for identifying    *
 *  the associated record in the Surnames (tblNR) table.  The redundant *
 *  field IDNR is deprecated, is no longer guaranteed to be maintained, *
 *  and is hidden from external view.                                   * 
 *  The field that provided a link to the associated record in the      *
 *  Families (tblMR) table is renamed to `IDMR` to clarify its meaning. *
 *																		*
 *  History:															*
 *		2011/01/03		created											*
 *		2012/01/14		change class name to LegacyAltName				*
 *		2012/09/24		set needInsert to true if constructor called	*
 *						with an array with idnx == 0					*
 *		2012/10/02		use LegacyRecord::setField to manage contents of*
 *						record so that save will correctly construct the*
 *						required INSERT or UPDATE SQL command to		*
 *						apply changes									*
 *		2012/10/20		standardize initialization of new record		*
 *		2012/10/31		on initialization from instance of Person		*
 *						the Name record was not updated					*
 *		2013/02/14		support creating married name entries for both	*
 *						husband and wife								*
 *		2013/02/26		allow initialization with incomplete row		*
 *		2013/03/04		allow initialization from an incomplete instance*
 *						of Person										*
 *		2013/04/03		properly escape SQL commands when output to XML	*
 *		2013/04/21		add method getName								*
 *		2013/08/09		base class renamed to Record					*
 *		2014/03/20		support passing parameters to constructor in	*
 *						an associative array							*
 *						Support creating new ordinary alternate name	*
 *						add static Legacy Name::getNames				*
 *						add static LegacyName::deleteNames				*
 *		2014/03/28		do cleanup if too many alternate names match	*
 *						request to get what should be a unique record	*
 *		2014/04/08		rename to LegacyName							*
 *						initialize field 'akanote' in the record with	*
 *						`order`=0 to match 'namenote' in individual		*
 *						array returned by static getNames indexed by	*
 *						IDNX											*
 *		2014/04/30		fix undefined variable on record not found		*
 *		2014/11/13		change static method getNames so it can be		*
 *						used by script addCitXml.php by adding gender	*
 *						throw exception if no parms to getNames or		*
 *						deleteNames										*
 *						permit presence of gender field in record		*
 *		2014/12/08		diagnostic output to $warn						*
 *		2015/01/02		add static method updateNames and use common	*
 *						static method getWhere to construct SQL			*
 *						WHERE expression								*
 *		2015/01/07		extend constructor support for array input		*
 *						to provide alternative to passing instance		*
 *						of LegacyFamily and $husb parameter to get		*
 *						alternate married name record					*
 *		2015/01/08		permit specifying an array of values of 'Order'	*
 *						in static methods getNames, deleteNames, and	*
 *						updateNames										*
 *						If the supplied given name and surname match	*
 *						an existing name record for the individual		*
 *						then return that record instead of creating a	*
 *						new duplicate									*
 *		2015/01/27		constructor accepts 'idmr' as synonym for the	*
 *						easily misspelled 'marriednamemaridid' parm		*
 *						static methods getNames, updateNames, and		*
 *						deleteNames accept 'idmr' as synonym for the	*
 *						easily misspelled 'marriednamemaridid' parm		*
 *		2015/03/08		do not reference deprecated name fields in		*
 *						LegacyFamily									*
 *						ensure implied `Order`=-1 for construction of	*
 *						married name record								*
 *		2015/04/27		escape < and > in SQL commands displayed in		*
 *						XML or HTML										*
 *		2015/05/15		use new parameter definition for Surname		*
 *						add support for array of given names and for	*
 *						given name prefix search						*
 *		2015/08/11		add support for treename						*
 *		2015/08/23		correct support for treename					*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2015/12/08		truncate SOUNDEX to 4 characters				*
 *		2017/03/19		use preferred parameters to new Person			*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/07/20		parameter list to logSqlUpdate changed			*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/10/13		change class LegacyIndiv to class Person		*
 *		2017/12/18		add support for temporary fields				*
 *		2018/03/21		temporarily add logging to /logs/Name.log		*
 *						to track down cause of surname reversals		*
 *		2018/11/09      add constants for `order`                       *
 *		2018/11/05      remove method updateFromIndiv                   *
 *		                report errors in constructor through $this->msg *
 *		                save references to Person and Family            *
 *		                move functionality of Person::getName here      *
 *		                add pseudo fields for birth and death dates     *
 *		2018/12/25      change MarriedNameMarIDID to IDMR               *
 *		                add methods toXML and toJSON to hide IDNR       *
 *		2019/01/03      name fields in Person obsolete                  *
 *		2019/02/16      do not act on unused fields                     *
 *		2019/04/09      support passing TemplateTag to getName          *
 *		2019/04/15      replace is_a with instanceof                    *
 *		2019/05/20      do not display marriagenamecreatedby for        *
 *		                non marriage records                            *
 *		2918/06/01      improve JSON output                             *
 *		2019/07/18      use Person::getPerson                           *
 *		2019/11/22      add Name::$info                                 *
 *		                defer assignment of aka order until save        *
 *		2019/11/26      correct constructor which was returning the     *
 *		                an existing entry if no valid IDIR was passed   *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Surname.inc';

/************************************************************************
 *  class Name															*
 *																		*
 *  Definition of a class implementing behavior for name	            *
 *  records within a genealogy database.								*
 ************************************************************************/

class Name extends Record
{
    /********************************************************************
     *	$this->person                                                    *
     *	This is a saved reference to the associated instance of Person	*
     ********************************************************************/
    protected   $person         = null;

    /********************************************************************
     *	$this->family                                                   *
     *	This is a saved reference to the associated instance of         *
     *	Family if order=-1                                              *
     ********************************************************************/
    protected   $family         = null;

    /********************************************************************
     *	$this->surnameRec                                               *
     *	This is a saved reference to the associated instance of Surname	*
     ********************************************************************/
    protected   $surnameRec     = null;

    /********************************************************************
     *	Name::$initRow		                                       		*
     *																	*
     *	static array containing default values for each field in        *
     *	a new record.           						                *
     ********************************************************************/
    protected static	$initRow	= array(
    					'idnx'					=> 0,	        // autoincrement
    					'idnr'					=> 0,           // ignored
    					'idir'					=> 0,
    					'surname'				=> '',
    					'soundslike'			=> '',
    					'givenname'				=> '',
    					'prefix'				=> '',
    					'title'					=> '',
    					'userref'				=> '',
    					'order'					=> 0,
    					'marriednamecreatedby'	=> 0,
    					'birthsd'				=> -99999999,   // ignored
    					'preferredaka'			=> 0,	        // not AKA
    					'akanote'				=> '',
    					'idmr'	                => null,
    					'srchtag'				=> 0,	        // ignored
    					'qstag'					=> 0,	        // ignored
    					'treename'				=> ''
    					);

    /********************************************************************
     *	Name::$info											            *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                		'table'     			=> 'tblNX',
                		'name'      			=> 'Names',
                		'prime'	    			=> 'idnx',
	            		'srvmg'     			=> true,
	            		'fldcount'				=> 18,
                		'order'     			=> '`order`',
                		'classname' 			=> 'Name');

    /********************************************************************
     *	Name::$translate												*
     *																	*
     *	static array containing alternate names for each field			*
     ********************************************************************/
    protected static	$translate	= array(
    					'id'		            => 'idnx',
    					'given'		            => 'givenname',
    					'`order`'		        => 'order',
    					'type'		            => 'order',
    					'suffix'		        => 'title',
    					'createdby'		        => 'marriednamecreatedby',
                        'marriednamemaridid'	=> 'idmr',
                        'namenote'              => 'akanote',
                        'notes'                 => 'akanote'
    					);

    /********************************************************************
     *	Constant values of `order`										*
     ********************************************************************/
    const	PRIMARY			    = 0;
    const	MARRIED			    = -1;

    /********************************************************************
     *	Options for method getName										*
     *																	*
     *	These are combined using bitwise OR so each must be a			*
     *	power of 2.														*
     ********************************************************************/
    const	NAME_INCLUDE_DATES	= 1;
    const	NAME_SURNAME_FIRST	= 2;
    const	NAME_INCLUDE_LOCS	= 4;

    /********************************************************************
     *	function Name::constructWhere									*
     *																	*
     *  Create the WHERE clause and parms for getting a Name record		*
     *	using a PDO prepared statement.									*
     *	This is just to reduce the nesting level of this code.          *
     *																	*
     *  Input:															*
     *	    $parms		parameters										*
     *		$sqlParms	array to pass to PDO::execute					*
     *																	*
     *  Returns															*
     *		SQL WHERE clause as String									*
     *																	*
     *	Side-Effects:													*
     *		$this->person is initialized to point at instance of Person  *
     *		if it was passed as value of idir parameter                 *
     *		$this->family is initialized to point at instance of Family *
     *		if `IDMR' is instance of Family.                            *
     ********************************************************************/
    private function constructWhere($parms,
    				    		    &$sqlParms)
    {
    	global	$debug;
    	global	$warn;

        $idnx					= null;
        $idir					= null;
    	$idmr					= null;
        $order					= null;
        $givenname              = null;
        $surname                = null;
    	$where					= '';
    	$and					= 'WHERE ';
    	$sqlParms				= array();
    	$numParms				= count($parms);

    	foreach($parms as $field => $value)
        {		                            // loop through all parameters
    	    $fieldLc		                = strtolower($field);
    	    if (array_key_exists($fieldLc, self::$translate))
    			$fieldLc	                = self::$translate[$fieldLc];
    	    switch($fieldLc)
    	    {		                        // act on search fields
    			case 'idnx':
    			{		                    // IDNX
    			    if (is_int($value) || ctype_digit($value))
    			    {                       // valid numeric
                        $where		        .= $and . "IDNX=:idnx";
                        $idnx               = $value;
    					$sqlParms['idnx']	= $value;
                        $and			    = ' AND ';
                        break 2;            // no other parameters for search
    			    }                       // valid numeric
    			    else                    // invalid value
    					$this->msg	        .= "IDNX='$value' value invalid";
    			    break;
    			}		                    // IDNX

    			case 'idir':
                {		                    // IDIR
                    $idir                   = 0;
    			    if ($value instanceof Person)
    			    {		                // Person record
    					$this->person	    = $value;
                        if ($this->person->isExisting())
                            $idir		    = $this->person->getIdir();
    			    }		                // Person record
    			    else
    				if (is_int($value))
    			    {		                // key
                        $idir		        = $value;
                    }
    				else
    			    {		                // key
                        $idir		        = intval($value);
                    }

                    if ($idir > 0)
                    {
    			        $where		        .= $and . "IDIR=:idir";
    			        $sqlParms['idir']	= $idir;
                        $and		        = ' AND ';
                    }
    			    break;
    			}		                    // IDIR

    			case 'order':
                {		                    // Order
                    $order                  = intval($value);
    			    break;
    			}	                        // Order

    			case 'idmr':
    			{	                        // idmr of family
    			    if ($value instanceof Family)
                    {		                // Family record
    					$this->family		= $value;
    					$idmr		        = $this->family->getIdmr();
    			    }		                // Family record
    			    else
    			    if (is_int($value) || ctype_digit($value))
    			    {		                // IDMR
                        $idmr		        = intval($value);
    			    }		                // IDMR
                    else
                    {                       // invalid value
                        $this->msg	        .=
                            "IDMR='$idmr' value invalid. ";
                        break;
                    }                       // invalid value

                    if ($idmr > 0)
                    {
                        $where		            .= $and . "IDMR=:idmr";
    			        $sqlParms['idmr']	    = $idmr;
    			        $and		            = ' AND ';
                    }                       // valid value
    			    break;
    			}	                        // idmr of family

    			case 'givenname':
                {		                    // GivenName
                    $givenname              = $value;
    			    break;
    			}	                        // GivenName

    			case 'surname':
                {		                    // Surname
                    $surname                = $value;
    			    break;
    			}	                        // Surname

    	    }		                        // act on specific fields
    	}		                            // loop through all parameters

        if (is_null($idnx))
        {                                   // IDNX not specified
            if (is_null($order))
	        {                               // order not specified
		    	if ($idmr > 0 && $idir > 0)
		    	{			                // Order must be -1
		    	    // if IDMR is specified this
		    	    // implies Order=-1
		    	    $where		                .= $and . "`Order`=-1";
	            }			                // Order must be -1
		        else
		        if ($idir > 0)
		        {                           // IDIR specified
	                if (!is_null($givenname) || !is_null($surname))
	                {                       // alternate name
	                    if (!is_null($givenname))
	                    {
	                        $where		        .= $and . "GivenName=:givenname";
	    			        $sqlParms['givenname']	= $givenname;
	    			        $and		        = ' AND ';
	                    }
	                    if (!is_null($surname))
	                    {
	                        $where		        .= $and . "Surame=:surname";
	    			        $sqlParms['surname']= $surname;
	    			        $and		        = ' AND ';
	                    }
		    	        $where		            .= $and . "`Order`>0";
	                }                       // alternate name
	                else
	                {                       // get primary name
	                    $where		            .= $and . "`Order`=0";
	                }                       // get primary name
		        }                           // IDIR specified
	        }                               // order not specified
	        else
            {                               // order specified
                if ($idir > 0)
                {
	                $where		                .= $and . "`Order`=:order";
	                $sqlParms['order']	        = $order;
                    $and		                = ' AND ';
                }
	        }                               // order specified
        }                                   // IDNX not specified

    	return $where;
    }		// private function Name::constructWhere

    /********************************************************************
     *	function Name::__construct									    *
     *																	*
     *  Given the identifier extract information about a alternate name	*
     *	from the database. 												*
     *																	*
     *  Input:															*
     *		$parms		an associative array of parameter values to		*
     *					use to locate and initialize the instance.		*
     *					For example:									*
     *					    array() constructs a new default instance  	*
     *																	*
     *					    array('idnx' => $idnx) constructs from an	*
     *							existing table row.						*
     *																	*
     *					    array('idir' 		=> $idir) or			*
     *					    array('idir' 		=> $idir,				*
     *					          'order'       => Name::PRIMARY)       *
     *					        creates an entry for the primary        *
     *					        name of the individual                  *
     *																	*
     *					    array('idir' 		=> $idir,				*
     *							  'givenname'	=> $givenname,		    *
     *							  'surname'		=> $surname)			*
     *						    adds an alternate name for a Person	    *
     *																	*
     *					    array('idir'		=> $idir,				*
     *							  'order'		=> positiveInt)			*
     *							returns the specific existing			*
     *							alternate name of a Person				*
     *																	*
     *					    array('idir'		=> $idir,				*
     *							  'idmr'		=> $idmr) or    		*
     *					    array('idir'		=> $idir,				*
     *							  'idmr'		=> $idmr,				*
     *					          'order'       => Name::MARRIED)       *
     *							returns the married name entry for the	*
     *							particular Person as a spouse in		*
     *							the specified family.					*
     *																	*
     *							$idir may be either an IDIR value or	*
     *							an instance of Person					*
     *							$idmr may be either an IDMR value or	*
     *							an instance of Family					*
     *																	*
     *					or a database record presented as an array		*
     *																	*
     *  Creates object containing a name record.					    *
     ********************************************************************/
    function __construct($parms = null)
    {
    	global	$debug;
    	global	$warn;
    	global	$msg;
        global	$connection;
        global  $document_root;

    	$dbrow					= null;
    	$needInsert				= false;
    	$idnx					= null;
    	$idir					= null;
    	$idmr					= null;
        $order					= null;
        $surname            	= '';

        $this->constDebug('Name', $parms);

    	// action depends upon type of first parameter
    	if (is_null($parms))
        {
            $parms		        = array();
        }
    	else
    	if (is_int($parms) ||
            (is_string($parms) && ctype_digit($parms)))
        {
            $parms		        = array('idnx'	=> $parms);
        }
    	else
    	if ($parms instanceof Person)
        {			                // old way to init from individual
            if ($parms->isExisting())
                $idir           = $parms->get('idir');
            else
                $idir           = 0;
    	    $this->person		= $parms;
    	    $parms		        = array('idir'	=> $idir,
    								    'order'	=> Name::PRIMARY);
    	}			                // old way to init from individual
    	else
    	if ($parms instanceof Family)
        {			                // old way to reset married surname of wife
            if ($parms->isExisting())
                $idmr           = $parms->get('idmr');
            else
                $idmr           = 0;
    	    $this->family		= $parms;
    	    $parms		        = array('idir'	=> $family->get('idirwife'),
    								    'idmr'	=> $family);
        }			                // old way to reset married surname of wife

        //$this->constDebug('Name', $parms);

    	// all of the supported (if deprecated) parameter formats have been
    	// reformatted into an array
    	if (is_array($parms))
        {		                // associative array of field names and values
    	    // validate parameter
    	    $numParms		            = count($parms);

    	    // if every field supplied, construct from the array
    	    if ($numParms >= count(self::$initRow))
    	    {			            // complete row from database
    			$dbrow		            = $parms;
    			foreach($parms as $field => $value)
                {
                    $fieldLc            = strtolower($field);
                	if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc	    = self::$translate[$fieldLc];
                    switch($fieldLc)
                    {
                        case 'idnx':
                        {
                            $idnx	                = $value;
                            break;
                        }

                        case 'idmr':
                        {
                            if ($value instanceof Family)
                            {
                                $this->family       = $value;
                                $idmr               = $value->getId();
                            }
                            else
                                $idmr	            = $value;
                            $parms['idmr']          = $idmr;
                            break;
                        }

                        case 'gender':
    			        {		        // pseudo field copied from Person
    					    break;      // ignore
    			        }		        // pseudo field copied from Person

                        default:
                        { 
    			            if (!array_key_exists($fieldLc, self::$initRow))
    			            {
    					        $warn	.= "<p>Name::__construct: " . __LINE__ .
    							" Invalid field name '$fieldLc' in parameters.</p>\n".
    							print_r($parms, true) . ".\n";
    					        ob_start();
    					        debug_print_backtrace();
    					        $this->msg	.= ob_get_clean();
    			            }
                            break;
                        }
                    }               // act on specific fields
    			}		            // loop through fields

    			if (is_null($idnx))
    			    $this->msg	.= "Missing key 'idnx' in row.\n";
    			$needInsert	= false;
    	    }			            // complete row from database
    	    else
            {				        // partial row, use as search parameters
    	        $sqlParms		    = array();
    	        $where		        = $this->constructWhere($parms,
    							            			    $sqlParms);

                // if selection parameters passed, get or create the record
    			if (strlen($where) > 0)
    			{			        // requested search for matching record
    			    // construct the query of the alternate name table
                    $query	            = "SELECT * FROM tblNX $where LIMIT 2";

    			    // query the database
    			    $stmt	            = $connection->prepare($query);
                    $queryText          = debugPrepQuery($query, $sqlParms);
    			    if ($stmt->execute($sqlParms))
    			    {		        // successful query
    					if ($debug)
    					    $warn       .= "<p>Name.inc: " . __LINE__ .
    							                " query='$queryText'</p>\n";
    					$row		        = $stmt->fetchAll(PDO::FETCH_ASSOC);
    					if (count($row) >= 1)
                        {		    // found existing name record
                            if (count($row) > 1 && canUser('all'))
                            {
    					        $warn       .= "<p>Name.inc: " . __LINE__ .
                                    " query='$queryText' returned more than 1 row</p>\n";
                                foreach($row as $namerec)
                                    $warn       .= "<p>Name.inc: " . __LINE__ .
                                    " IDNX=" . $namerec['idnx'] .
                                    " Order=" . $namerec['order'] . "</p>\n";
                            }
    					    $count	        = 1;
    					    $dbrow	        = $row[0];
    					    $needInsert	    = false;
    					}		    // found existing name record
    					else
    					{		    // no existing record
    					    $count	        = 0;
    					    $dbrow	        = self::$initRow;
    					    $gender	        = Person::UNKNOWN;
    					    if ($this->person)
                            {		// person passed as parameter
                                $person             = $this->person;
                                $idir               = $person['idir'];
                                $dbrow['idir']      = $idir;
                                if (array_key_exists('order', $sqlParms))
                                {
                                    $order          = $sqlParms['order'];
                                    $dbrow['order'] = $order;
                                }
    					    }		// person passed as parameter

    					    if ($this->family)
    					    {		// family passed as parameter
                                $dbrow['idmr']      = $this->family['idmr'];
    							$dbrow['order']		= Name::MARRIED;
                            }		// family passed as parameter

                            if ($debug)
                            {
    							$warn	.= "<p>Name.inc: " . __LINE__ .
                                " new row " . print_r($dbrow, true) . "</p>\n";
    					        ob_start();
    					        debug_print_backtrace();
                                $warn	.= "<p>" . htmlentities(ob_get_clean(),ENT_XML1) . "</p>\n";
                            }

                            foreach($parms as $field => $value)
                            {
    							$fieldLc		= strtolower($field);
    							if (array_key_exists($fieldLc, self::$translate))
    							    $fieldLc	= self::$translate[$fieldLc];
    							switch($fieldLc)
                                {	            	// act on specific field
    			                    case 'surname':
    			                    {
                                        $dbrow['surname']	= $value;
                                        break;
                                    }

								    case 'givenname':
								    {
										$dbrow['givenname']	= $value;
										break;
								    }
					
								    case 'prefix':
								    {
										$dbrow['prefix']	= $value;
										break;
								    }
					
								    case 'title':
								    {
										$dbrow['title']		= $value;
										break;
								    }
					
								    case 'akanote':
								    {
										$dbrow['akanote']	= $value;
										break;
								    }
					
								    case 'userref':
								    {
										$dbrow['userref']	= $value;
										break;
								    }
					
								    case 'order':
								    {
										$dbrow['order']		= $value;
										break;
			                        }
                                }
                            }
    					    $needInsert	= true;
    					}		    // no existing record
    			    }			    // successful query
    			    else
    			    {			    // error performing query
    	                error_log("Name::__construct: " . __LINE__ .
    			                	" query='$queryText', " .
                                    print_r($stmt->errorInfo(),true) . ".\n",
                                  3, 
                                  $document_root . "/logs/Name.log");
    					$this->msg      .= "Query='$queryText', " .
                                    print_r($stmt->errorInfo(),true) . ".\n";
    	                $dbrow	        = self::$initRow;
    			    }			    // error performing query
                }			        // requested search for matching record
                else
                {                   // called with unsaved Person
                    $dbrow	                    = self::$initRow;
                    if ($this->person)
                    {
                        $dbrow['surname']   = $this->person->row['surname'];
                        $dbrow['givenname'] = $this->person->row['givenname'];
		    			$dbrow['prefix']	= $this->person->row['prefix'];
		    			$dbrow['title']		= $this->person->row['title'];
		    			$dbrow['akanote']	= $this->person->row['namenote'];
	        			$dbrow['userref']	= $this->person->row['userref'];
                    }
                }                   // called with incomplete Person
    	    }				        // partial row, use as search parameters
        }		                    // associative array
        else
        {                           // invalid parameters
            $dbrow	                = self::$initRow;
        }                           // invalid parameters

        if (is_null($dbrow))
            throw new Exception("Name.inc: " . __LINE__ . " how can row not be initialized? parms=($parmText)");

        // invoke constructor of base class
    	parent::__construct($dbrow, 'tblNX');
    	$this->needInsert	    = $needInsert;

    	$idir	                = $dbrow['idir'];

        // ensure that all married names match the prime givenname,
        // prefix, title, and userref
        if ($this['order'] == 0 && $idir > 0 && !$needInsert)
        {                   // primary name record
		    $updParms	= array('idir'		=> $idir,
								'type'	    => -1,
								'orderby'	=> 'idnx');
		    $setParms	= array('givenname'	=> $this['givenname'],
								'prefix'	=> $this['prefix'],
								'title'		=> $this['title'],
								'userref'	=> $this['userref']);
		    $names	    = new RecordSet('Names', $updParms);
	        $count	    = $names->update($setParms);
	    }
	
	    if ($this['order'] == -1 && is_null($this->row['idmr']))
	        $this->msg      = "Name::__construct: attempt to create married name record without IDMR"; 
	
	    // diagnostic output if debug is set
    	if ($needInsert)
    	    $this->dump('New Name Record constructed:');
    	else
            $this->dump('Name Record constructed:');

        // notify user of any errors detected by the constructor
        if (strlen($this->msg) > 0)
            $msg        .= "Name::__construct: " . $this->msg;
    }		//		function Name::__construct

    /********************************************************************
     *	function Name::get											    *
     *																	*
     *  Get the value of a field by name								*
     *																	*
     *	Parameters:														*
     *	    $field		field name										*
     *					'gender', 'birthd', 'birthsd', 'deathd', and    *
     *					'deathsd' return value for the corresponding	*
     *					Person record									*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
        if (is_null($this->row))
            throw new Exception('Name.get called before initialized');
    	$fieldLc	    = strtolower($field);
    	if (array_key_exists($fieldLc, self::$translate))
    	    $fieldLc	= self::$translate[$fieldLc];

        switch($fieldLc)
        {                   // act on specific fields
            case 'idnr':
            {               // obsolete field
                // note that there is a field 'idnr' in the old record
                // but it is no longer supported
        	    return $this->getSurnameRec()->get('idnr');
            }               // obsolete field

            case 'soundslike':
            {               // obsolete field
                // note that there is a field 'soundslike' in the old record
                // but it is no longer supported
        	    return $this->getSurnameRec()->get('soundslike');
            }               // obsolete field

            case 'gender':
            {               // pseudo-field
                if (!array_key_exists('gender', $this->extras))
                {
                    if (is_null($this->getPerson()))
                        $this->extras['gender'] = Person::UNKNOWN;
                    else
                        $this->extras['gender']	= $this->person['gender'];
                }
    	        return $this->extras['gender'];
    	    }               // pseudo-field

            case 'birthsd':
            {               // pseudo-field
                // note that there is a field 'birthsd' in the old record
                // but it is no longer supported
                if (!array_key_exists('birthsd', $this->extras))
                {
                    if (is_null($this->getPerson()))
                        $this->extras['birthsd']    = -99999999;
                    else
                    {
                        $birthEvent     = $this->person->getBirthEvent(true);
                        $this->extras['birthsd']	= $birthEvent['eventsd'];
                        $this->extras['birthd']	    = $birthEvent['eventd'];
                    }
                }
    	        return $this->extras['birthsd'];
    	    }               // birthsd

            case 'birthd':
            {               // pseudo-field
                if (!array_key_exists('birthd', $this->extras))
                {
                    if (is_null($this->getPerson()))
                        $this->extras['birthd']     = '';
                    else
                    {
                        $birthEvent     = $this->person->getBirthEvent(true);
                        $this->extras['birthsd']	= $birthEvent['eventsd'];
                        $this->extras['birthd']	    = $birthEvent['eventd'];
                    }
                }
    	        return $this->extras['birthd'];
    	    }               // birthd

            case 'deathsd':
            {               // pseudo-field
                if (!array_key_exists('deathsd', $this->extras))
                {
                    if (is_null($this->getPerson()))
                        $this->extras['deathsd']    = -99999999;
                    else
                    {
                        $deathEvent     = $this->person->getDeathEvent(true);
                        $this->extras['deathsd']	= $deathEvent['eventsd'];
                        $this->extras['deathd']	    = $deathEvent['eventd'];
                    }
                }
    	        return $this->extras['deathsd'];
    	    }               // deathsd

            case 'deathd':
            {               // pseudo-field
                if (!array_key_exists('deathd', $this->extras))
                {
                    if (is_null($this->getPerson()))
                        $this->extras['deathd']     = '';
                    else
                    {
                        $deathEvent     = $this->person->getDeathEvent(true);
                        $this->extras['deathsd']	= $deathEvent['eventsd'];
                        $this->extras['deathd']	    = $deathEvent['eventd'];
                    }
                }
    	        return $this->extras['deathd'];
    	    }               // deathd

            default:
    	    {
    	        return parent::get($fieldLc);
    	    }
        }                   // act on specific fields
    }		//		function Name::get

    /********************************************************************
     *	function Name:set												*
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
        global  $document_root;
        global  $debug;
        global  $warn;

		error_log("Name::set: " . __LINE__ . " '$field','$value'\n",
		          3,
		          $document_root . "/logs/Name.log");
        $backtrace          = debug_backtrace();
        foreach($backtrace as $call)
        {                       // interpret backtrace
            if (array_key_exists('class', $call) &&
                strlen($call['class']) > 0)
                $classPref      = $call['class'] . '::';
            else
                $classPref      = '';
	        error_log("Name::set: " . __LINE__ . 
		                    " function=$classPref" . $call['function'] .
		                    " called from line=" . $call['line'] . 
		                    " of " . $call['file'] . "\n",
			          3,
                      $document_root . "/logs/Name.log");
        }                       // interpret backtrace
        if ($debug)
            showTrace();
    	$value			= trim($value);
    	$fieldLc		= strtolower($field);
    	if (array_key_exists($fieldLc, self::$translate))
    	    $fieldLc	= self::$translate[$fieldLc];
    	switch($fieldLc)
    	{	            	// act on specific field
    	    case 'surname':
            {               // surname
    			return $this->setSurname($value);
    			break;
            }               // surname

            case 'idnr':
            {               // obsolete field
        	    return $this->getSurnameRec->get('idnr');
            }               // obsolete field

            case 'givenname':
    	    case 'prefix':
    	    case 'title':
    	    case 'userref':
            case 'akanote':
            {
                if ($this->person)
                    $this->person->setLocal($fieldLc, $value);    
    			return parent::set($fieldLc, $value);
            }

            case 'akanote':
            {
                if ($this->person)
                    $this->person->setLocal('namenote', $value);    
    			return parent::set($fieldLc, $value);
            }

    	    case 'idnx':
    	    case 'idir':
    	    case 'soundslike':
    	    case 'order':
    	    case 'marriednamecreatedby':
    	    case 'preferredaka':
    	    case 'srchtag':
    	    case 'qstag':
    	    case 'treename':
    	    {
    			return parent::set($fieldLc, $value);
    	    }

		    case 'gender':
		    case 'birthsd':
		    case 'birthd':
		    case 'deathsd':
            case 'deathd':
    	    {                   // do not alter fields only in Person
    			return parent::get($fieldLc);
    	    }                   // do not alter fields only in Person

    	    default:
    	    {	    	// other field names
    			return parent::set($fieldLc, $value);
    	    }		    // other fields
    	}		        // act on specific field
    }		//		function Name::set

    /********************************************************************
     *	function Name::getIdnx											*
     *																	*
     *	Retrieve the unique numeric key of this instance of Name		*
     *	Note that this value is not available for a new Name			*
     *	record until after the method save() has been called, because	*
     *	only then does the database server assign a value for the key. 	*
     *																	*
     *	Returns:														*
     *	    unique numeric key of this row in the table					*
     ********************************************************************/
    function getIdnx() { return $this['idnx']; }

    /********************************************************************
     *	function Name::getIdir											*
     *																	*
     *	Retrieve the unique numeric key of the instance of Person		*
     *	representing the individual documented by this instance of		*
     *	Name.															*
     *																	*
     *	Returns:														*
     *	    unique numeric key of the associated individual.			*
     ********************************************************************/
    function getIdir() { return $this['idir']; }

    /********************************************************************
     *	function Name::getSurname										*
     *																	*
     *	Return the value of surname for this alternate name record.		*
     *																	*
     *	Returns:														*
     *	    alternate surname as a string								*
     ********************************************************************/
    function getSurname()
    {
    	$retval		= $this['surname'];
    	if ($retval === null)
    	    return '';
    	else
    	    return $retval;
    }		//		function Name:: getSurname

    /********************************************************************
     *	function Name::setSurname										*
     *																	*
     *	Change the value of surname for this alternate name record.		*
     *  This also ensures that the reference to the associated          *
     *	Surname instance is updated.  If this is the primary Name		*
     *	record for an individual all alternate name records for this    *
     *	individual are also updated.  For example this deals with the   *
     *	situation where the spelling of the surname is corrected.       *
     *																	*
     *	Parameters:														*
     *	    $newname	new surname as a string							*
     *																	*
     *	Returns:														*
     *	    old surname as a string										*
     ********************************************************************/
    function setSurname($newname)
    {
        $oldsurname		            = parent::set('surname', $newname);
        if ($this->person)
            $this->person->setLocal('surname', $newname);

        // get value of IDNR for the surname of the individual
        if ($newname != $oldsurname)
        {
            if ($newname == '')
                throw new Exception('Name::setSurname: new name is blank');
            $this->surnameRec	    = new Surname(array('surname' => $newname));
            $this->surnameRec->save(false);
	        if ($this['order'] == Name::PRIMARY)
	        {                       // primary name
	    		$idir	            = $this['idir'];
	    	    $otherNames         = new RecordSet('Names',
	    							            array('idir'	    => $idir,
	    									          '`order`'	    => '>0')); 
	    	    foreach($otherNames as $idnx => $nameRec)
	    	    {		            // loop through other name records
	    	        if ($nameRec->get('surname') == $oldsurname)
	    	        {		        // matches old surname
	    			    $nameRec->setSurname($newname);
	    			    $nameRec->save(false);
	                }		        // matches old surname
	            }                   // loop through other names
	        }                       // primary name
        }                           // surname has changed
    	return $oldsurname;
    }		//		function Name:: setSurname

    /********************************************************************
     *	function Name::getGivenName									    *
     *																	*
     *	Return the value of given name for this alternate name record.	*
     *																	*
     *	Returns:														*
     *	    given name as a string										*
     ********************************************************************/
    function getGivenName()
    {
    	$retval	            = $this['givenname'];
    	if ($retval === null)
    	    return '';
    	else
    	    return $retval;
    }		//		function Name:: getGivenName

    /********************************************************************
     *	function Name::getName											*
     *																	*
     *	Return the name for this individual including all parts.		*
     *	This method is implemented for consistency with other classes	*
     *	which have a getName method to return a summary of the			*
     *	instance.														*
     *																	*
     *	Input:															*
     *	    $options		combination of option flags					*
     *			NAME_INCLUDE_DATES		include birth and death dates	*
     *			NAME_INCLUDE_LOCS		include birth and death locs	*
     *			NAME_SURNAME_FIRST		put surname first				*
     *			            or internationalization translate table     *
     *	    $options2		combination of option flags if $options 	*
     *	                    is a translate table                        *
     *			NAME_INCLUDE_LOCS		include birth and death locs	*
     *			NAME_SURNAME_FIRST		put surname first				*
     *																	*
     *	Returns:														*
     *	    string containing the name of the individual				*
     ********************************************************************/
    function getName($options	= 0,
                     $options2  = 0)
    {
		global	$debug;
		global	$warn;

        if ($this->row['surname'] == '' && $this->row['idir'] > 0)
        {
            $person             = $this->getPerson();
            if ($person)
                $warn           .= $person->dump('Name::getName');
        }
        $evBirth                = null;
        $evDeath                = null;
		if (is_array($options) || $options instanceof TemplateTag)
		{		// support being passed internationalization table
		    $tranTab			= $options;
		    $options			= self::NAME_INCLUDE_DATES | $options2;
		}		// support being passed internationalization table
		else
		    $tranTab			= null;

		$includeDates			= ($options & self::NAME_INCLUDE_DATES) != 0;
		$includeLocs			= ($options & self::NAME_INCLUDE_LOCS) != 0;
		$surnameFirst			= ($options & self::NAME_SURNAME_FIRST) != 0;
        $idir		    		= $this['idir'];
		$prefix		    		= $this['prefix'];
		$givenname	    		= $this['givenname'];
		$surname	    		= $this['surname'];
		$birth		    		= '';
		$death		    		= '';
		$maidenName	    		= null;
		$suffix		    		= $this['title'];

        if ($this->getPerson())
        {
		    $evBirth			= $this->person->getBirthEvent();
            $bprivlim           = $this->person->getBPrivLim();
            $dprivlim           = $this->person->getDPrivLim();
        }

		if ($evBirth)
        {
			$tbirth		        = $evBirth->getDate($bprivlim,
                                                    $tranTab);
            if ($tbirth == 'Private')
                return 'Private';
        }

		if ($this['order'] == Name::MARRIED)
        {		// check for maiden name
            $primeName          = new Name(array('idir'     => $idir,
                                                 'order'    => Name::PRIMARY));
			$maidenName	        = $primeName->row['surname'];
        }		// check for maiden name
        else
            $maidenName         = $surname;

		$retval		            = '';
		$space		            = '';

		if ($surnameFirst)
		{			// Surname, Given Names
		    $retval		        .= $surname;
		    if ($maidenName != $surname)
				$retval		    .= " ($maidenName)";
		    $retval		        .= ',';
		    $space		        = ' ';

		    if (strlen($prefix) > 0)
		    {
				$retval		    .= $space . $prefix;
				$space		    = ' ';
		    }

		    if (strlen($givenname) > 0)
		    {
				$retval		    .= $space . $givenname;
				$space		    = ' ';
		    }

		    if (strlen($suffix) > 0)
		    {
				$retval		    .= $space . $suffix;
		    }
		}			// Surname, Given Names
		else
        {			// Given Names Surname
		    if (strlen($prefix) > 0)
		    {
				$retval		    .= $prefix;
				$space		    = ' ';
		    }

		    if (strlen($givenname) > 0)
		    {
				$retval		    .= $space . $givenname;
				$space		    = ' ';
		    }

		    if (strlen($surname) > 0)
		    {
				$retval		    .= $space . $surname;
				$space		    = ' ';
		    }
		    if ($maidenName != $surname)
		    {
				$retval		    .= $space . "($maidenName)";
				$space		    = ' ';
		    }

		    if (strlen($suffix) > 0)
		    {
				$retval		    .= $space . $suffix;
		    }
		}			// Given Names Surname

		// optionally include birth and death dates
		if ($includeDates || $includeLocs)
		{		// add dates
		    if ($evBirth)
		    {
				$birth		= $evBirth->getDate($bprivlim,
                                                $tranTab);
				if ($includeLocs)
				{	// include location name
				    $loc	= $evBirth->getLocation();
				    if ($loc->getIdlr() > 1)
						$birth	.= ' at ' . $loc->getName();
				}	// include location name
		    }
		    else
				$birth	= '';

            if ($this->person)
		        $evDeath		= $this->person->getDeathEvent();
		    if ($evDeath)
		    {
				$death		= $evDeath->getDate($dprivlim,
											    $tranTab);
				if ($includeLocs)
				{	// include location name
				    $loc	= $evDeath->getLocation();
				    if ($loc->getIdlr() > 1)
						$death	.= ' at ' . $loc->getName();
				}	// include location name
		    }
		    else
				$death		= '';

		    $retval	.= $space . "($birth&#8212;$death)";
		}		// add dates
		if ($debug)
		    $warn	.= "<p>Name::getName returns '$retval'</p>\n";
		return	$retval;
    }		// Name::getName

    /********************************************************************
     *	function Name::getNextOrder										*
     *																	*
     *  Get the next order value for assigning a new alternate name/	*
     *	nickname/sobriquet for an individual.							*
     *																	*
     *  Returns															*
     *	    next integer value to assign to `order`                     *
     *	    null if unable to complete function					        *
     ********************************************************************/
    private function getNextOrder()
    {
    	global	$debug;
    	global	$warn;
    	global	$connection;

    	// query the database
        $idir				= $this['idir'];
        if (!is_int($idir) || $idir == 0)
            return null;

    	$query				= 'SELECT COALESCE(MAX(`Order`), 0)+1 ' .
    							        'FROM tblNX WHERE IDIR=:idir';
    	$stmt				= $connection->prepare($query);
    	$sqlParms			= array('idir'	=> $idir);
    	$queryText			= debugPrepQuery($query, $sqlParms);

    	if ($stmt->execute($sqlParms))
    	{		// successful query
    	    if ($debug)
    			$warn	.= "<p>Name::getNextOrder: $queryText</p>";

    	    $row		= $stmt->fetch(PDO::FETCH_NUM);
    	    if (is_array($row))
    	    {		// got result
    			return $row[0];
    	    }		// got result
    	    else
    	    {		// failed
    			return null;
    	    }		// failed
    	}		    // successful query
    	else
    	{		    // error performing query
    	    $msg      .= "Name::getNextOrder:" .
                "'$queryText', " .  print_r($stmt->errorInfo(),true) .
                ".\n";
    		return null;
    	}		    // error performing query
    }			//		function Name::getNextOrder

    /********************************************************************
     *	function Name::save											    *
     *																	*
     *  Save changes made to the Name record into the					*
     *  database.														*
     *																	*
     *  Parameters:														*
     *		$xml		if true diagnostic output is emitted in XML		*
     *					format, if a string use it as tag name			*
     ********************************************************************/
    function save($xml  = false)
    {
    	global	$debug;
    	global	$warn;
    	global	$userid;
    	global	$connection;
    	global	$document_root;

        // determine top level tag name for XML output
        $indent        		= '';
        if (is_string($xml))
        {			    // string tag name
            $matches   		= array();
            if (preg_match('/^(\s*)(\w*)/', $xml, $matches))
            {
                $indent		= $matches[1];
                $xml   		= $matches[2];
            }
            $tagname		= $xml;
            $xml	   		= true;
        }			    // string tag name
        else
        if ($xml)
        {			    // boolean true
            $indent         = "    ";
    	    $tagname	    = 'sql';
        }		// true
        else
            $tagname        = '';
        if ($debug)
            $warn   .= "<p>Name::save('$tagname')</p>\n";

        if (strlen($this->msg)> 0)
        {
            $warn   .= "<p>Name::save: Update suppressed due to errors: " .
                        $this->msg . "</p>\n";
            return 0;
        }

        // records with given name starting with 'Lost Record '
        // are created only to satisfy IDIR values and should
        // not be written into the database
        if (substr($this->row['givenname'], 0, 12) == 'Lost Record ')
            return 0;

        // if the instance of Name was created using an instance of Person
        // or an instance of Family that had not been inserted into the
        // database at the time the Name was created, check to see if
        // the Person or Family has subsequently been created.
        $idir                       = $this->row['idir'];
        if ($idir == 0 && $this->person)
        {
            if (!$this->person->isExisting())
                return false;           // not ready to insert
            $idir                   = $this->person['idir']; 
            $this['idir']           = $idir; 
        }
        $idmr                       = $this->row['idmr'];
        if ($this['idmr'] == 0 && $this->family)
        {
            if (!$this->family->isExisting())
                return false;           // not ready to insert
            $idmr                   = $this->family['idmr'];
            $this['idmr']           = $idmr;
        }

        // ensure the married surname of the wife is set
        if ($idmr)
        {                           // a married name
            $family                 = $this->getFamily();
            if ($this['surname'] == '' &&
                $this['gender'] == Person::FEMALE)
            {
                switch($family['marriednamerule'])
                {                   // depends upon married name rule
                    case 1:
                        $this['surname']    = $family['husbsurname'];
                        break;

                    default:
                        $this['surname']    = 'Wifeof' .
                strtolower($family['husbgivenname'] . $family['husbsurname']);
                        break;
                }                   // depends upon married name rule
            }                       // married surname of wife empty
        }                           // a married name

    	// insert the record into the database if it does not already
    	// exist, otherwise update those fields that need to be updated
    	if ($this->needInsert || $this['idnx'] == 0)
        {		// create new record
    	    $fldnames		            = '';
    	    $values		                = '';
    	    $sqlParms	            	= array();
    	    $comma		                = '';
    	    foreach($this->row as $fieldLc => $value)
    	    {
    			switch($fieldLc)
    			{	// act on field name
    			    case 'idnx':
    			    {	// auto-increment key of record
    					// set by SQL server
    					break;
    			    }	// auto-increment key of record

    			    case 'idmr':
    			    {	// reference to Family
        				$fldnames		        .= "$comma`IDMR`";
        				$values			        .= "$comma:idmr";
        				if ($value === '')
                            $value		        = null;
        				$sqlParms[$fieldLc]	    = $value;
                        $comma			        = ',';
    					break;
    			    }	// reference to Family

    			    case 'surname':
    			    {	// surname also updates soundex
    					$fldnames	.= $comma . "`surname`, `soundslike`";
    					$values		.= $comma . ':surname' .
    								   $comma . "LEFT(SOUNDEX(:surname),4)";
    					$sqlParms['surname']	= $value;
    					$comma		= ',';
    					break;
    			    }	// surname also updates soundex

    			    case 'soundslike':
    			    {	// set under 'surname'
    					break;
    			    }	// set under 'surname'

    			    case 'order':
    			    {	// type of alternate name record
    					$fldnames	            .= $comma . "`Order`";
    					$values		            .= $comma . ':order';
    					if (is_null($value))
    					{	// ask server to calculate value
    					    $value	            = $this->getNextOrder();
    					}	// ask server to calculate value
    					$sqlParms['order']	    = $value;
    					break;
    			    }	// type of alternate name record

    			    default:
                    {	// other fields
                        if (array_key_exists($fieldLc, self::$initRow))
                        {
        					$fldnames		        .= $comma . "`$fieldLc`";
        					$values			        .= $comma . ':' . $fieldLc;
        					if (is_null($value))
        					    $value		        = '';
        					$sqlParms[$fieldLc]	    = $value;
                            $comma			        = ',';
                        }
    					break;
    			    }	// other fields
    			}	// switch on field name
            }		// loop through all fields in record

    	    // ensure 'marriednamecreatedby' set correctly
    	    if ($this['order'] == -1 &&
    			$this['marriednamecreatedby'] == 0)
            {
                $gender             = $this['gender'];
    			if ($gender == Person::FEMALE)
    			    $this['marriednamecreatedby'] == 1;
    			else
    			if ($gender == Person::MALE)
    			    $this['marriednamecreatedby'] == 0;
    	    }

    	    // create the command to insert the new record into the database
            $insert 	        = 
                        "INSERT INTO tblNX ($fldnames) VALUES($values)";
    	    $insertText	        = debugPrepQuery($insert, $sqlParms);
            $this->lastSqlCmd   = $insertText;

    	    // insert the record into the database
    	    $stmt		        = $connection->prepare($insert);
    	    if ($stmt->execute($sqlParms))
            {		        	// successful insert
    			$count		    = $stmt->rowCount();
    			if ($xml)
                    print "$indent<$tagname count='$count'>\n" .
    							$insertText .
    					  "\n</$tagname>\n";
    			if ($debug)
    			{
    			    $warn	    .= "<p>$insertText</p>\n";
    			    $warn	    .= "<p>count=$count</p>\n";
    			}

    			$this->changed		= array();	// reset
    			$this->needInsert	= false;	// no longer need insert

    			// get the record identifier set by the database server for
    			// the new record
    			// update primary key value in record,
    			// as it was previously 0
    			$idnx			    = $connection->lastInsertId();
    			$this['idnx']	    = $idnx;

    			if ($xml)
                    print "$indent<$tagname idnx='$idnx'>lastInsertId</$tagname>\n";

    			if ($debug)
    			{
    			    $warn	        .= "<p>lastInsertId: IDNX=$idnx</p>\n";
    			}

    			// log the update
    			$this->log($insert, $sqlParms);

    			// obtain the updated record from the database
    			// so the memory copy will include values set by the
    			// SQL server or calculated in the INSERT statement
    			$query		        = "SELECT * FROM tblNX WHERE IDNX=:idnx";
    			$sqlParms       	= array('idnx'	=> $idnx);
    			$stmt	        	= $connection->prepare($query);
    			$queryText      	= debugPrepQuery($query, $sqlParms);
    			if ($stmt->execute($sqlParms))
    			{		    // successful query
    			    // record retrieved
    			    if ($xml)
                        print "$indent<$tagname>\n$queryText\n</$tagname>\n";
    			    if ($debug)
    					$warn	    .= "<p>$queryText</p>\n";
    			    $this->row		= $stmt->fetch();
    			    $idnx		    = $this['idnx'];
    			}		    // successful query
    			else
    			{		    // error performing query
    			    throw new Exception("Name::save: " . __LINE__ .
    								" '$queryText', " .
    								print_r($stmt->errorInfo(),true));
    			}		    // error performing query
    	    }	    		// successful insert
    	    else
    	    {	       		// error inserting record
    			throw new Exception("Name::save: " . __LINE__ .
    							    " '$insertText', " .
    							    print_r($stmt->errorInfo(),true));
    	    }	        	// error inserting record
    	}	            	// create new record
    	else
    	{	            	// update existing record
    	    $update		            = "UPDATE tblNX SET ";
    	    $set		            = '';
    	    $comma		            = '';
    	    foreach($this->changed as $fieldLc => $value)
    	    {
    			switch($fieldLc)
    			{	        // act on field name
    			    case 'idnx':
    			    {	    // auto-increment key of record
    					// set by SQL server
    					break;
    			    }	    // auto-increment key of record

    			    case 'surname':
    			    {	    // surname also updates soundex
    					$set		        .= $comma . "`surname`=:surname";
    					$sqlParms['surname']= $value;
                        $set	            .= 
                                ", `soundslike`=LEFT(SOUNDEX(:surname),4)";
    					$comma		        = ', ';
    					break;
    			    }	    // surname also updates soundex

    			    case 'soundslike':
    			    {	    // set under 'surname'
    					break;
    			    }   	// set under 'surname'

    				case 'idnr':
    				case 'idir':
    				case 'order':
    				case 'marriednamecreatedby':
    				case 'preferredaka':
    				case 'idmr':
    				case 'srchtag':
                    case 'qstag':
                    {               // numeric fields
                        if (is_string($value))
                        {
                            $value      = trim($value);
                            if (strlen($value) == 0)
                                $value  = 0;
                            else
                            if (ctype_digit($value) ||
                                (substr($value, 0, 1) == '-' &&
                                 ctype_digit(substr($value, 1))))
                                $value      = (int)$value;
                            else
                            if ($value == 'on')
                                $value      = 1;
                            else
                                $value      = 0;
                        }
                        else
                        if (!is_int($value))
                            $value          = 0;
    					$set		        .= $comma . "`$fieldLc`=:$fieldLc";
    					$sqlParms[$fieldLc]	= $value;
    					$comma		    	= ', ';
    					break;
                    }               // numeric fields

    			    default:
    			    {	    // other fields
    					$set		        .= $comma . "`$fieldLc`=:$fieldLc";
    					$sqlParms[$fieldLc]	= $value;
    					$comma		    	= ', ';
    					break;
    			    }	    // other fields
    			}	        // switch on field name
    	    }	        	// loop through all fields in record
    	    $this->changed	= array();	// reset

    	    // ensure 'marriednamecreatedby' set correctly
    	    if ($this['order'] == -1 &&
    			$this['marriednamecreatedby'] == 0)
            {               // correct setting of MarriedNameCreatedBy
                $gender         = $this['gender'];
    			$set	        .= "$comma`marriednamecreatedby`=:createdby";
    			if ($gender == Person::FEMALE)
    			    $sqlParms['createdby']	= 1;
    			else
    			    $sqlParms['createdby']	= 2;
    	    }               // correct setting of MarriedNameCreatedBy

    	    if (strlen($set) > 0)
    	    {	        	// changes made to contents of record
    			$sqlParms['idnx']	= $this['idnx'];
    			$update		        = "UPDATE tblNX SET $set WHERE IDNX=:idnx";
    			$updateText	        = debugPrepQuery($update, $sqlParms);
                $this->lastSqlCmd   = $updateText;

    			// update the record
    			$stmt		        = $connection->prepare($update);
    			if ($stmt->execute($sqlParms))
    			{		    // update successful
    			    $count		    = $stmt->rowCount();
                    if ($xml)
                    {
                        print "$indent<$tagname count='$count'>\n$updateText\n</$tagname>\n";
                    }
    			    if ($debug)
                        $warn	    .= "<p>Name::save: " . __LINE__ .
                                       " SQL='$updateText', count=$count</p>\n";

    			    // log the update
    			    $this->log($update, $sqlParms);
        $backtrace          = debug_backtrace();
        foreach($backtrace as $call)
        {                       // interpret backtrace
            if (array_key_exists('class', $call) &&
                strlen($call['class']) > 0)
                $classPref      = $call['class'] . '::';
            else
                $classPref      = '';
	        error_log("Name::save: " . __LINE__ . 
		                    " function=$classPref" . $call['function'] .
		                    " called from line=" . $call['line'] . 
		                    " of " . $call['file'] . "\n",
			          3,
			          $document_root . "/logs/Name.log");
		}                       // interpret backtrace
    			}		    // update successful
    			else
    			{		    // error updating record
    			    throw new Exception("Name::save: " . __LINE__ .
    								" '$updateText', " .
    								print_r($stmt->errorInfo(),true));
    			}		    // error updating record
    	    }		        // changes made
    	}		            // update existing record
    }		//		function Name::save

    /********************************************************************
     *	Name::getPerson                                                 *
     *																	*
     *	This is a saved reference to the associated instance of Person	*
     *																	*
     *	Returns:														*
     *		instance of Person or null									*
     ********************************************************************/
    function getPerson()
    {
        if (is_null($this->person))
        {
            $idir               = $this['idir'];
            if ($idir > 0)
                $this->person	= Person::getPerson($idir);
        }
    	return $this->person;
    }		//		function Name::getPerson

    /********************************************************************
     *	Name::getFamily                                                 *
     *																	*
     *	This is a saved reference to the associated instance of         *
     *	Family if order=-1                                              *
     *																	*
     *	Returns:														*
     *		instance of Family or null									*
     ********************************************************************/
    function getFamily()
    {
        if ($this['order'] == -1 && is_null($this->family))
        {
            $idmr               = $this['idmr'];
            if ($idmr > 0)
                $this->family	= new Family(array('idmr' => $idmr));
        }
    	return $this->family;
    }		//		function Name::getFamily

    /********************************************************************
     *	Name::getSurnameRec                                             *
     *																	*
     *	This is a saved reference to the associated instance of Surname	*
     ********************************************************************/
    function getSurnameRec()
    {
        if (is_null($this->surnameRec))
        {
            $surname            = $this['surname'];
            $this->surnameRec   = new Surname(array('surname' => $surname));
            $this->surnameRec->save(false);
        }
    	return $this->surnameRec;
    }		//		function Name::getSurnameRec

    /********************************************************************
     *	function Name::toXml											*
     *																	*
     *  Construct and optionally print an XML representation of the		*
     *	record.															*
     *																	*
     *	Parameters:														*
     *	    $top		tag name as a string for the tag enclosing the	*
     *					values.											*
     *					If null, do not enclose the individual values	*
     *					inside a root node.								*
     *	    $print		if true print the output, else just return it	*
     *	    $options	flags to indicate which information to include	*
     *					(available to derived classes)					*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/
    function toXml($top,
				   $print = true,
				   $options = 0)
    {
		global	$debug;

		// ensure that there is a label to use for the top element
		if (!is_string($top) || strlen($top) == 0)
		    $top	= $this->table;

		$prime		= $this->prime;
		if ($top !== null)
		{
		    if (is_string($prime) && strlen($prime) > 0)
		    {
				$keyvalue	= $this[$prime];
				$retval		= "    <$top $prime='$keyvalue'>\n";
		    }
		    else
				$retval		= "    <$top>\n";
		}
		else
		    $retval		= '';

		foreach($this->row as $key => $value)
        {
            if ($key == 'idnr'||        // fields unused by this implementation
                $key == 'soundslike'||
                $key == 'birthsd'||
                $key == 'srchtag'||
                $key == 'qstag')
            {}
            else
            if ($key == 'marriednamecreatedby')
            {
                if ($this['order'] < 0)
                {                       // married name record
	                $retval	.= "\t<$key>";
	                if ($value == 0)
	                    $retval	.= "0=not a married name";
	                else
	                if ($value == 1)
	                    $retval	.= "1=replace wife's surname with husband's";
	                else
	                if ($value == 2)
	                    $retval	.= "2=user created married name";
	                else
	                    $retval	.= htmlentities($value,ENT_XML1);
	                $retval     .= "</$key>\n";
                }                       // married name record
            }
            else
		        $retval	.= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
		}
		if ($top !== null)
		    $retval	.= "    </$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Name::toXml

    /********************************************************************
     *	function Name::toJson											*
     *																	*
     *  Construct and optionally print a JSON representation of the		*
     *	record.															*
     *																	*
     *	Parameters:														*
     *	    $print		if true print the output, else just return it	*
     *	    $options	flags to indicate which information to include	*
     *					(available to derived classes)					*
     *																	*
     *	Returns:														*
     *	    JSON representation of the object as a string				*
     ********************************************************************/
    function toJson($print = true,
				    $options = 0)
    {
		global	$debug;

		$retval		= '';
		$comma		= "{\n";
		foreach($this->row as $key => $value)
        {
            switch($key)
            {                       // act on specific fields
                case 'idnr':        // fields unused by this implementation
                case 'soundslike':
                case 'birthsd':
                case 'srchtag':
                case 'qstag':
                    break;

                case 'order':
                {
                    $retval		        .= "$comma\t\"$key\":\t$value";
                    $retval             .= ",\n\t\"means\":\t";
                    if ($value == -1)
                        $retval         .= "\"Married Name\"";
                    else
                    if ($value == 0)
                        $retval         .= "\"Primary Name\"";
                    else
                    if ($value > 0)
                        $retval         .= "\"AKA Name\"";
                    else
                        $retval         .= "\"Undefined value\"";
                    break;
                }                   // order

                case 'marriednamecreatedby':
                case 'idmr':
                {
                    if ($this['order'] != -1)
                        break;      // ignore
				    $retval		.= "$comma\t\"$key\":\t$value";
                    break;
                }                   // married name created by

                default:
                {
				    if (is_numeric($value))
				    {
                        if ((strlen($value) == 1 ||
                             substr($value, 0, 1) != '0'))
						    $retval		.= "$comma\t\"$key\":\t$value";
						else
						{		// handle values with leading 0
						    $retval		.= "$comma\t\"$key\":\t\"$value\"";
						}		// handle values with leading 0
				    }
				    else
				    {
						$retval		.= "$comma\t\"$key\":\t" .
									   json_encode($value);
		            }
                }                   // other fields
            }                       // act on specific fields
		    $comma		= ",\n";
        }

		if (strlen($retval) > 0)
		    $retval		.= "\n}\n";
		if ($print)
		    print $retval;
		return $retval;
    }		// function Name::toJson

}		// class Name

