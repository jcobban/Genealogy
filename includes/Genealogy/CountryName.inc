<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CountryName.inc														*
 *																		*
 *  Definition of a class representing a name of a country.				*
 *  This class provides access to the information in a row				*
 *  of the table CountryNames.											*
 *																		*
 *  CREATE TABLE CountryNames (											*
 *		`Code3166_1`	CHAR(2) NOT NULL, -- country code			    *
 *      `Code639_1`		CHAR(2) NOT NULL, -- language code				*
 *		`Name`			VARCHAR(255) NOT NULL DEFAULT '',			    *
 *		`Article`		VARCHAR(32) NOT NULL DEFAULT '',				*
 *		`Possessive`	VARCHAR(32) NOT NULL DEFAULT '',			    *
 *		PRIMARY KEY (`Code3166_1`,`Code639_1`) )						*
 *		ENGINE=InnoDb DEFAULT CHARSET=utf8;								*
 *																		*
 *		`Code3166_1`	The ISO 3166-1 two character country code	    *
 *						by convention in upper case						*
 *      `Code639_1`		The ISO 639-1 two character language code		*
 *						by convention in lower case						*
 *		`Name`			The name of the specified country in the		*
 *						specified language								*
 *		`Article`		The definite article with which to prefix the	*
 *						name of the country.  For example:				*
 *						    'France' takes 'la ' in language 'fr'		*
 *						    'Canada' takes 'le ' in language 'fr'		*
 *						    'États Unies de l'Amerique' takes 'les '	*
 *								in language 'fr'						*
 *						    Countries that start with a vowel take		*
 *								"l'" in language 'fr'.  This			*
 *								requires that the value of this field	*
 *								include the space separator if needed	*
 *		`Possessive`	The possessive with which to prefix the name	*
 *						of the country.  For example:					*
 *						    'France' takes 'de la ' in language 'fr'	*
 *						    'Canada' takes 'du ' in language 'fr'		*
 *						    'États Unies de l'Amerique' takes 'des '	*
 *								in language 'fr'						*
 *						    Countries that start with a vowel take		*
 *								"de l'" in language 'fr'.  This			*
 *								requires that the value of this field	*
 *								include the space separator if needed	*
 *																		*
 *  History:															*
 *		2017/10/27		created											*
 *		2017/12/18		add support for temporary fields				*
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/02/19      do not throw exceptions in constructor          *
 *		                do not invoke other constructors from           *
 *		                the constructor                                 *
 *		                permit passing instances of Country and         *
 *		                Language to the constructor                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Country.inc';
require_once __NAMESPACE__ . '/Language.inc';

/************************************************************************
 *  class CountryName													*
 *																		*
 *  Definition of a class recording information about a name of a		*
 *  country.															*
 ************************************************************************/

class CountryName extends Record
{
    /********************************************************************
     *	CountryName::$country   										*
     *																	*
     *	Associated instance of Country.                                 *
     ********************************************************************/
    private $country	    = null;

    /********************************************************************
     *	CountryName::$language   										*
     *																	*
     *	Associated instance of Language.                                *
     ********************************************************************/
    private $language	    = null;

    /********************************************************************
     *	CountryName::$defaultOrder										*
     *																	*
     *	Default sort order for query response by class RecordSet		*
     ********************************************************************/
    static $defaultOrder	= "`Name`";

    /********************************************************************
     *	CountryName::$initRow											*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'code3166_1'		=> '',
				'code639_1'		    => '',
				'name'			    => '',
				'article'		    => '',
				'possessive'		=> '');

    /********************************************************************
     *	CountryName::$translate											*
     *																	*
     *	Define synonyms for field names									*
     ********************************************************************/
    protected static $translate	= array(
				'cc'			=> 'code3166_1',
				'lang'			=> 'code639_1',
				'the'			=> 'article',
				'of'			=> 'possessive');

    /********************************************************************
     *	function CountryName::__construct								*
     *																	*
     *	Construct an instance of CountryName based upon the				*
     *	parameters.                                                     *
     *																	*
     *	Input:															*
     *	    $parms		an associative array of parameters		        *
     *					to select an existing entry where:		        *
	 *					'code3166_1' may be a string or an              *
     *                  instance of Country                             *
	 *					case 'code639_1' may be a string or an          *
     *                  instance of Language                            *
     *					or a complete row from the table as an		    *
     *					associative array						        *
     *																	*
     *	Returns object containing a description of a name of a country	*
     ********************************************************************/
    function __construct($parms)
    {
		global $debug;
		global $warn;
		global $connection;

		if ($debug)
		    $warn	.= "<p>CountryName::__construct__(" .
						   print_r($parms, true) . "</p>\n";
		if (is_null($connection))
		{
		    throw new Exception('CountryName::constructor: " .
							"No connection to database server');
		}
		$needInsert	        = false;
		$this->table	    = 'CountryNames';

		// validate parameters
		if (is_array($parms))
		{			// valid parm type
		    if (count($parms) < count(self::$initRow))
		    {			// search parameters
				$code		= 'not supplied';
				$where		= '';
				$and		= 'WHERE ';
				$cc		    = 'CA';
				$lang		= 'en';
				$sqlParms	= array();
				foreach($parms as $key => $value)
				{			// loop through parameters
				    $fieldLc		= strtolower($key);
				    if (array_key_exists($fieldLc, self::$translate))
						$fieldLc	= self::$translate[$fieldLc];
				    switch($fieldLc)
				    {			// act on key fields
						case 'code3166_1':
                        {		// country code
                            if ($value instanceof Country)
                            {
                                $this->country      = $value;
                                $cc                 = $value['cc'];
                            }
                            else
						        $cc			        = $value;
						    $where		            .= "$and$fieldLc=:$fieldLc";
						    $sqlParms[$fieldLc]	    = $value;
						    $and		            = ' AND ';
						    break;
						}		// country code

						case 'code639_1':
						{		// language identifier
                            if ($value instanceof Language)
                            {
                                $this->language     = $value;
                                $land               = $value['code'];
                            }
                            else
						        $lang		        = $value;
						    $where		            .= "$and$fieldLc=:$fieldLc";
						    $sqlParms[$fieldLc]	    = $value;
						    $and		            = ' AND ';
						    break;
						}		// language identifier

				    }			// act on key fields
				}			// loop through parameters

				// query the database
				$query		= "SELECT * FROM CountryNames $where";
				$queryText	= debugPrepQuery($query, $sqlParms);
				$stmt		= $connection->prepare($query);

				if ($stmt->execute($sqlParms))
				{		// success
				    if ($debug)
						$warn	.= "<p>CountryName::__construct: " . __LINE__ . 
							   " query='$queryText'</p>\n";

				    $result	= $stmt->fetchAll(PDO::FETCH_ASSOC);
				    if (count($result) == 1)
				    {		// existing record
						$dbrow		            = $result[0];
						$needInsert	            = false;
				    }		// existing record
				    else
				    if (count($result) == 0)
				    {		// create a new record
						// set defaults
						$dbrow			        = self::$initRow;
						$dbrow['code3166_1']	= $cc;
                        $dbrow['code639_1']	    = $lang;
                        if ($this->country)
                            $dbrow['name']		= $this->country->get('name');
                        if ($this->language)
                        {
						    $dbrow['article']	= $this->language->get('article');
                            $dbrow['possessive']= $this->language->get('possessive');
                        }
						$needInsert		= true;
				    }		// create new record
				    else
				    {		// error performing query
						$this->msg  .= "CountryName::__construct: '$queryText' " .
							"returns " . count($result) . " records";
						$dbrow	    = self::$initRow;
				    }		// error performing query
				}		// success
				else
				{		// error performing query
				    $this->msg      .= "CountryName::__construct: '$queryText' " .
								print_r($stmt->errorInfo(),true);
					$dbrow		    = self::$initRow;
				}		// error performing query
		    }			// search parameters
		    else
		    {			// build from existing record
				$dbrow		        = $parms;
				$needInsert	        = false;
		    }			// build from existing record
		}			// array parameter
		else
		{			// unsupported parameter type
		    $this->msg              .= "CountryName::__construct: " .
						"parameter must be an array: is " .
						gettype($parms);
			$dbrow			        = self::$initRow;
		}			// unsupported parameter type


		// invoke constructor of base class
		parent::__construct($dbrow,
						    'CountryNames');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('CountryName Record constructed:');
    }		// CountryName::__construct

    /********************************************************************
     *	function CountryName::get										*
     *																	*
     *	Get the value of a field in the object.							*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to get value of					*
     *																	*
     *	Returns:														*
     *	    the value of the field										*
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
		switch($fieldLc)
		{
            case 'name':
            {
                return $this->getName();
            }

			case 'article':
            case 'possessive':
            {
                if ($this->row[$fieldLc] == '')
                {
                    if (is_null($this->language))
                    {
                        $ll             = $this->row['code3166_1'];
                        $this->language = new Language(array('code' => $ll));
                    }
					$this->row['article']	= $this->language->get('article');
                    $this->row['possessive']= $this->language->get('possessive');
                }
                return $this->row[$fieldLc];
            }

			case 'code3166_1':
			case 'code639_1':
		    default:
		    {		// other fields
				return parent::get($fieldLc);
		    }		// other fields
		}		// switch on field name
    }		// CountryName::get

    /********************************************************************
     *	function CountryName::save										*
     *																	*
     *	Save changes made to the CountryName record into the database.	*
     *																	*
     *	Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format within <cmd> tags.						*
     *					if a string use it as tag name					*
     ********************************************************************/
    function save($xml)
    {
		global	$debug;
		global	$warn;
		global	$userid;
		global	$connection;

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

		if ($this->needInsert)
		{		// create new record
		    $fieldNames		= '';
		    $comma		= '';
		    $values		= '';
		    $parms		= array();
		    foreach($this->row as $fieldName => $value)
		    {
				$fieldNames	.= $comma . "`$fieldName`";
				$values		.= $comma . '?';
				array_push($parms, $value);
				$comma		= ", ";
		    }		// loop through all fields in record

		    // insert the new record into the database
		    $insert		= 'INSERT INTO CountryNames' .
								"($fieldNames) VALUES($values)";
		    $insertText		= debugPrepQuery($insert, $parms);

		    $stmt		= $connection->prepare($insert);
		    if ($stmt->execute($parms))
		    {		// success
				$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
				$count		= count($result);
				if ($xml)
				    print "<$tagname result='$count'>" .
							xmlentities($insertText) . 
						  "</$tagname>\n";
				if ($debug)
				{
				    $warn	.= "<p>insert='$insertText'</p>\n";
				    $warn	.= "<p>count=$count</p>\n";
				}

				$this->changed		= array();	// reset
				$this->needInsert	= false;

				// log the update
				logSqlUpdate($insert,
						     $parms,
						     $count,		// compound key
						     '',
						     $this->toJson(false));
		    }		// success
		    else
		    {		// error inserting record
				throw new Exception("CountryName::save:
							    '$insertText', " .
							    print_r($stmt->errorInfo(),true));
		    }		// error inserting record
		}		// create new record
		else
		{		// update existing record
		    if ($debug)
				$warn	.= "<p>CountryName::save: " . __LINE__ .
						   " changed= " . print_r($this->changed, true) .
						   "</p>\n";
		    $update		= "UPDATE CountryNames SET ";
		    $set		= '';
		    $comma		= '';
		    $count		= 0;
		    $parms		= array();
		    foreach($this->changed as $fld => $value)
		    {
				$set	.= $comma . "`$fld`=?";
				array_push($parms, $value);
				$comma	= ', ';
		    }		// loop through all fields in record
		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
		    {		// changes made
				// assemble the UPDATE command
				$update		.= $set .' WHERE `Code3166_1`=?' .
								 ' AND `Code639_1`=?';
				if ($this->former)
				{
				    array_push($parms, $this->former['code3166_1']);
				    array_push($parms, $this->former['code639_1']);
				}
				else
				{
				    array_push($parms, $this->row['code3166_1']);
				    array_push($parms, $this->row['code639_1']);
				}

				// perform the update
				$updateText	= debugPrepQuery($update, $parms);
				$stmt		= $connection->prepare($update);
				if ($stmt->execute($parms) === false)
				{		// error updating record
				    throw new Exception("CountryName::save:
								'$updateText', " .
								print_r($stmt->errorInfo(),true));
				}		// error updating record
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$tagname count='$count'>" .
							xmlentities($update) . "</$tagname>\n";
				if ($debug)
				    $warn	.= "<p>'$update' count=$count</p>\n";

				// log the update
				logSqlUpdate($update,
							 $parms,
							 0,	// compound key
							 json_encode($this->former),
							 $this->toJson(false));
		    }		// changes made
		}		// update existing record
		return $count;
    }		// CountryName::save

    /********************************************************************
     *	function CountryName::delete									*
     *																	*
     *	Delete the database record associated with this instance.		*
     *																	*
     *	Input:															*
     *	    $xml		if true emit XML								*
     *					if a string emit XML using as tag name			*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method deleted the database record		*
     *	    0			if the record did not need to be deleted		*
     ********************************************************************/
    function delete($xml=false)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		// handle XML output option
		if (is_string($xml))
		    $xmltag	= $xml;
		else
		if ($xml)
		{
		    $xml	= true;
		    $xmltag	= 'cmd';
		}

		// to simplify expressions
		if (!$this->needInsert)
		{		// have existing record
		    $delete		= 'DELETE FROM CountryNames' .
								' WHERE `Code3166_1`=?' .
								' AND `Code639_1`=?';
		    if ($this->former)
		    {
				$sqlParms		= array($this->former['code3166_1'],
								$this->former['code639_1']);
		    }
		    else
		    {
				$sqlParms		= array($this->row['code3166_1'],
								$this->row['code639_1']);
		    }

		    // perform the update
		    $deleteText		= debugPrepQuery($delete, $sqlParms);
		    $stmt		= $connection->prepare($delete);
		    if ($stmt->execute($sqlParms))
		    {		// successful delete
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
				else
				//if ($debug)
				    $warn .= "<p>$deleteText</p>\n<p>count='$count'</p>\n";

				// log the update
				logSqlUpdate($delete,
						     $sqlParms,
						     $count,
						     $this->toJson(false),
						     '');

				// update the memory copy of the record to reflect that it
				// no longer represents a database record
				$this->needInsert	= true;

				return $count;	// number of records deleted
		    }		// successful delete
		    else
		    {		// error updating record
				print "<p>query='$deleteText'</p>\n";
				print "<p class='message'>" .
						print_r($stmt->errorInfo(),true) . 
						"</p>\n";
				return 0;
		    }		// error updating record
		}		// have key value

		// record never written to database
		return 0;	// nothing to delete
    }		// CountryName::delete

    /********************************************************************
     *	function CountryName::getName									*
     *																	*
     *	Convenience function for accessing the 'name' attribute.		*
     *																	*
     *	Returns:														*
     *	    Name of the county as a string.								*
     ********************************************************************/
    function getName($options	= null)
    {
        if ($this->row['name'] == '')
        {
            if (is_null($this->country))
            {
                $cc                 = $this->row['code3166_1'];
                $this->country      = new Country(array('cc'    => $cc));
            }
            $this->row['name'] = $this->country->getName();
        }
		return $this->row['name'];
    }		// CountryName::getName

    /********************************************************************
     *	function CountryName::__toString								*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return  $this->getName();
    }		// CountryName::__toString

}		// class CountryName
