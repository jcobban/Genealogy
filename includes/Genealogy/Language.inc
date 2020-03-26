<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Language.inc														*
 *																		*
 *  Definition of a class representing an ISO 639 language.				*
 *  This class provides access to the information in a row				*
 *  of the table Languages.												*
 *																		*
 *		CREATE TABLE `Languages` (										*
 *		    `Code639_1`     CHAR(2) NOT NULL,							*
 *		    `Name`          VARCHAR(255) NOT NULL DEFAULT '',			*
 *		    `NativeName`    VARCHAR(255) NOT NULL DEFAULT '',			*
 *		    `Article`       VARCHAR(32) NOT NULL DEFAULT '',			*
 *		    `Possessive`    VARCHAR(32) NOT NULL DEFAULT 'of',			*
 *		    `Conjunction`   VARCHAR(32) NOT NULL DEFAULT 'and',			*
 *		    `DatePrefix`    VARCHAR(32) NOT NULL DEFAULT 'on',			*
 *		    `Sorry`         VARCHAR(256) NOT NULL DEFAULT '...',	    *
 *		    PRIMARY KEY (`Code639_1`) )									*
 *		ENGINE=InnoDb DEFAULT CHARSET=utf8;								*
 *																		*
 *  History:															*
 *		2017/10/24		created											*
 *		2017/12/18		add support for temporary fields				*
 *		2018/10/13      add support for language specific `Sorry`       *
 *		2019/07/17      add support for country-qualified languages     *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Country.inc';

/************************************************************************
 *  class Language														*
 *																		*
 *  Definition of a class recording information about a language.		*
 *																		*
 ************************************************************************/

class Language extends Record
{
    /********************************************************************
     *	Language::$initRow												*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
						'code639_1'	    	=> '',
						'name'		    	=> '',
						'nativename'		=> '',
						'article'	    	=> '',
						'possessive'		=> 'of',
						'conjunction'		=> 'and',
						'dateprefix'		=> 'on',
						'sorry'	        	=> 'We are sorry that a $langName/$nativeName version of this page is not available.');

    /********************************************************************
     *	Language::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                		'table'     		=> 'Languages',
                		'name'      		=> 'Languages',
                		'prime'	    		=> 'code639_1',
	            		'srvmg'     		=> false,
	            		'fldcount'			=> 8,
                		'order'     		=> 'Name',
                		'classname' 		=> 'Language');

    /********************************************************************
     *	Language::$translate											*
     *																	*
     *	Define synonyms for field names									*
     ********************************************************************/
    protected static $translate	= array(
						'code'			    => 'code639_1',
						'the'			    => 'article',
						'of'			    => 'possessive');

    /********************************************************************
     *	Language::$defaultOrder											*
     *																	*
     *	Default sort order for query response by class RecordSet		*
     ********************************************************************/
    static $defaultOrder	= "`Name`";

    /********************************************************************
     *	$language->country  											*
     *																	*
     *	If the language code includes a country code, this is a         *
     *	reference to the instance of Country.                           *
     ********************************************************************/
    protected $country          = null;

    /********************************************************************
     *	function Language::__construct									*
     *																	*
     *	Construct an instance of Language based upon the				*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *	Input:															*
     *	    $parms		 	an associative array of parameters			*
     *						to select an existing entry or				*
     *						a complete row from the table as an			*
     *						associative array							*
     *						null to create the entry for the			*
     *						current display language as specified		*
     *						in the URL									*
     *																	*
     *	Returns object containing a description of a language			*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms	= null)
    {
		global $debug;
		global $warn;
		global $connection;

		if ($debug)
		{
		    $warn	        .= '<p>Language::__construct(' .
                print_r($parms, true) . ")</p>\n";
		}

		if (is_null($connection))
		{
		    throw new Exception('Language::constructor: " .
							"No connection to database server');
        }

		$needInsert	        = false;
		$this->table	    = 'Languages';
        $code	            = 'en';
        $cc                 = null;

		// validate parameters
		if (is_null($parms) ||
		    (is_array($parms) && count($parms) == 0))
		{			// create default entry from Cookie
		    if (array_key_exists('lang', $_REQUEST))
				$code	    = strtolower($_REQUEST['lang']);
		    $parms	        = array('code'	=> $code);
		}			// create default entry

        // process parameters
		if (is_array($parms))
		{			            // an array with at least one entry
		    if (count($parms) < count(self::$initRow))
		    {			        // search parameters
				$where      		= '';
				$and        		= 'WHERE ';
				$sqlParms          	= array();
				foreach($parms as $key => $value)
				{		        // loop through parameters
				    $fieldLc	    = strtolower($key);
				    if (array_key_exists($fieldLc, self::$translate))
						$fieldLc	= self::$translate[$fieldLc];
				    switch($fieldLc)
				    {		    // act on key fields
						case 'code639_1':
                        {	    // language code
                            if (strlen($value) > 2)
                            {
                                $code	        = substr($value, 0, 2);
                                if (strlen($value) >= 5 && 
                                    substr($value, 2, 1) == '-')
                                    $cc     = strtoupper(substr($value, 3));
                            }
                            else
                                $code           = $value;
						    $where	    	    .= $and . "Code639_1=:code";
						    $sqlParms['code']	= $code;
						    $and	    	    = ' AND ';
						    break;
						}	    // language code

				    }		    // act on key fields
                }		        // loop through parameters

                if (strlen($where) == 0)
                {
				    $where	    	    .= "WHERE Code639_1=:code";
                    $sqlParms['code']	= $code;
                }

				$query	                = "SELECT * FROM Languages $where";

				// query the database
				$stmt	                = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);

				if ($stmt->execute($sqlParms))
				{		// success
				    if ($debug)
						$warn	.= "<p>Language::__construct: " . __LINE__ . 
							       " query='$queryText'</p>\n";

				    $result	            = $stmt->fetchAll(PDO::FETCH_ASSOC);
				    $count              = count($result);
				    if ($count == 1)
				    {		// existing record
						$dbrow		    = $result[0];
						$needInsert	    = false;
				    }		// existing record
				    else
				    if ($count == 0)
                    {		// create a new record
						// set defaults
						$dbrow		    	= self::$initRow;
						$dbrow['code639_1']	= $code;
						$dbrow['name']		= "Language: '$code'";
						$needInsert		    = true;
				    }		// create new record
				    else
				    {		// error performing query
						$this->msg  .= "Language::__construct: '$queryText' " .
							            "returns $count records. ";
				    }		// error performing query
				}		    // success
				else
				{		    // error performing query
				    $this->msg      .="Language::__construct: '$queryText' " .
								print_r($stmt->errorInfo(),true);
				}		    // error performing query
		    }			    // search parameters
		    else
		    {			    // build from existing record
				$dbrow		            = $parms;
				$needInsert	            = false;
		    }			    // build from existing record
		}			        // an array with at least one entry
		else
		{			        // unsupported parameter type
		    throw new Exception("Language::__construct: " .
						"parameter must be an array: is " .
						gettype($parms));
		}			        // unsupported parameter type


		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Languages');
		$this->needInsert	            = $needInsert;

        if (!is_null($cc))
        {
            $this->extras['cc']         = $cc;
        }

		// diagnostic output if debug is set
		$this->dump('Language Record constructed:');
    }		// Language::__construct

    /********************************************************************
     *	function Language::save											*
     *																	*
     *	Save changes made to the Language record into the database.		*
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
		    $xml    	= true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		}		// true

		if ($this->needInsert)
		{		// create new record
		    $fldnames	    = '';
		    $comma	        = '';
		    $values	        = '';
		    $parms	        = array();
		    foreach($this->row as $fld => $value)
		    {
				$fldnames	.= $comma . "`$fld`";
				$values		.= $comma . '?';
				array_push($parms, $value);
				$comma	    = ", ";
		    }		// loop through all fields in record

		    // construct the SQL INSERT command	
		    $insert	= "INSERT INTO Languages ($fldnames) VALUES($values)";
		    $insertText	= debugPrepQuery($insert, $parms);

		    // insert the new record into the database
		    $stmt	= $connection->prepare($insert);
		    if ($stmt->execute($parms))
		    {			// success
				$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
				$count		= count($result);
				if ($xml)
				    print "<$tagname result='$count'>" .
							htmlentities($insertText,ENT_XML1) . 
						  "</$tagname>\n";
				if ($debug)
				{
				    $warn	.= "<p>$insertText</p>\n";
				    $warn	.= "<p>count=$count</p>\n";
				}

				$this->changed		= array();	// reset
				$this->needInsert	= false;

				// log the update
				logSqlUpdate($insert,
						     $parms,
						     0,		// compound key
						     '',
						     $this->toJson(false));
		    }			// success
		    else
		    {			// error inserting record
				throw new Exception("Language::save:
							    '$insertText', " .
							    print_r($stmt->errorInfo(),true));
		    }			// error inserting record
		}		// create new record
		else
		{		// update existing record
		    $update		= "UPDATE Languages SET ";
		    $set		= '';
		    $comma		= '';
		    $count		= 0;
		    $parms		= array();
		    foreach($this->changed as $fld => $value)
		    {
				$set		.= $comma . "`$fld`=?";
				array_push($parms, $value);
				$comma		= ', ';
		    }		// loop through all fields in record
		    $this->changed	= array();	// reset

		    if (strlen($set) > 0)
		    {		// changes made
				// assemble the UPDATE command
				$update		.= $set ." WHERE `Code639_1`=?";
				if ($this->former)
				    array_push($parms, $this->former['code639_1']);
				else
				    array_push($parms, $this->row['code639_1']);

				// perform the update
				$updateText	= debugPrepQuery($update, $parms);
				$stmt		= $connection->prepare($update);
				if ($stmt->execute($parms))
				{
				    $count		= $stmt->rowCount();
				    if ($xml)
						print "<$tagname count='$count'>" .
							    htmlentities($updateText,ENT_XML1) . "</$tagname>\n";
				    if ($debug)
						$warn	.= "<p>'$updateText' count=$count</p>\n";

				    // log the update
				    logSqlUpdate($update,
							 $parms,
							 0,	// compound key
							 json_encode($this->former),
							 $this->toJson(false));
				}
				else
				{		// error updating record
				    throw new Exception("Language::save:
								'$updateText', " .
								print_r($stmt->errorInfo(),true));
				}		// error updating record
		    }		// changes made
		}		// update existing record
		return $count;
    }		// Language::save

    /********************************************************************
     *	function Language::delete										*
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
		    $code	    	= $this->row['code639_1'];
		    $delete		    = "DELETE FROM Languages " .
									"WHERE `Code639_1`=:code";
		    $sqlParms		= array('code'	=> $code);
		    $sqlParmsText	= print_r($sqlParms, true);

		    $stmt		    = $connection->prepare($delete);
		    if ($stmt->execute($sqlParms))
		    {		// successful delete
				$count		= $stmt->rowCount();
				if ($xml)
				    print "<$xmltag count='$count'>$delete</$xmltag>\n";
				else
				if ($debug)
				    print "<p>$delete</p>\n<p>count='$count'</p>\n";

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
				print "<p>query='$delete', parms=$sqlParmsText</p>\n";
				print "<p class='message'>" .
						print_r($stmt->errorInfo(),true) . 
						"</p>\n";
				return 0;
		    }		// error updating record
		}		// have key value

		// record never written to database
		return 0;	// nothing to delete
    }		// Language::delete

    /********************************************************************
     *	function Language:get											*
     *																	*
     *	Obtain the current value of a field in the object.				*
     *																	*
     *  Parameters:														*
     *	    $field		name of field to retrieve						*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
        $fieldLc		= strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc	= self::$translate[$fieldLc];

        switch($fieldLc)
        {			// act on field name
            case 'name':
            {
                return $this->getName();
            }

            case 'nativename':
            {
                return $this->getNativeName();
            }

            case 'sorry':
            {
                return $this->getSorry();
            }

            case 'cc':
            {
                if (array_key_exists('cc', $this->extras))
                    return $this->extras['cc'];
                else
                    return null;
            }


            default:
            {		// other fields
                return parent::get($fieldLc);
            }		// other fields
        }			// act on field name
    }			// function Language::get

    /********************************************************************
     *	function Language::getSorry										*
     *																	*
     *	Get the value of the apology text field as HTML.				*
     *																	*
     *	Returns:														*
     *	    the apology text wrapped in an HTML paragraph               *
     ********************************************************************/
    function getSorry()
    {
        $sorry          = $this->row['sorry'];
        if (substr($sorry,0,3) != '<p>')
            $sorry      = '<p>' . $sorry . "</p>\n";
		return $sorry;
    }		// Languages::getSorry

    /********************************************************************
     *	function Language::getCountry									*
     *																	*
     *	Function for getting the country associated with a              *
     *	country-specific dialect of a language.                         *
     *																	*
     *	Returns:														*
     *	    Instance of Country or null.                                *
     ********************************************************************/
    function getCountry()
    {
        global  $warn;

        if ($this->country)
            return $this->country;
        else
        if (array_key_exists('cc', $this->extras))
        {
            $cc                     = $this->extras['cc'];
            $country                = new Country(array('cc' => $cc));
            if ($country->isExisting())
            {
                $this->country      = $country; // save
                return $this->country;
            }
            else
            {
                unset($this->extras['cc']);
            }
        }
		return null;
    }		// Language::getCountry

    /********************************************************************
     *	function Language::getName										*
     *																	*
     *	Function for accessing the 'name' attribute.		            *
     *																	*
     *	Returns:														*
     *	    Name of the language as a string.							*
     ********************************************************************/
    function getName($options	= null)
    {
		$lang                   = $this->row['code639_1'];
        if ($this->getCountry())
            return $this->row['name'] . 
                    '(' . $this->country->getName() . ')';
		return $this->row['name'];
    }		// Language::getName

    /********************************************************************
     *	function Language::getNativeName								*
     *																	*
     *	Function for accessing the 'nativename' attribute.		        *
     *																	*
     *	Returns:														*
     *	    Name of the language as a string.							*
     ********************************************************************/
    function getNativeName($options	= null)
    {
        global      $warn;

		$lang                   = $this->row['code639_1'];
        if ($this->getCountry())
            return $this->row['nativename'] . 
            '(' . $this->country->getName($lang) . ')';
		return $this->row['nativename'];
    }		// Language::getNativeName

    /********************************************************************
     *	function Language::__toString									*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return  $this->row['name'];
    }		// Language::__toString

}		// class Language
