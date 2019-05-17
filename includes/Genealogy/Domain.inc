<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Domain.inc															*
 *																		*
 *  Definition of a class representing a state, specifically as a		*
 *  registration authority for vital statistics.						*
 *  This class provides access to the information in a row				*
 *  of the table Domains.												*
 *																		*
 *  CREATE TABLE Domains (												*
 *		Domain			VARCHAR(8) NOT NULL DEFAULT 'CAON',				*
 *		Language		VARCHAR(8) DEFAULT 'en',						*
 *		PRIMARY KEY (Domain, Language),									*
 *		Name			VARCHAR(255) NOT NULL DEFAULT '',				*
 *		ResourcesURL	VARCHAR(255),									*
 *		ENGINE=InnoDb DEFAULT CHARSET=utf8;								*
 *																		*
 *  History:															*
 *		2016/05/20		created											*
 *		2017/12/18		add support for temporary fields				*
 *		2018/01/01		issue warning for unknown country or domain		*
 *						getName optionally returns both country name	*
 *						and state/province name							*
 *		2018/10/21      add method getCountry                           *
 *		2018/11/22      failed if domain code was garbage               *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/04/07      retain full value of language parm              *
 *		                report invalid parameters through msg           *
 *		                accept instance of Country through countrycode  *
 *		                construct Country in getCountry                 *
 *		                use Country->getName($lang)                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/Country.inc';

/************************************************************************
 *  class Domain														*
 *																		*
 *  Definition of a class recording information about a domain, such	*
 *  as a state or province for vital statistics registrations.			*
 *																		*
 ************************************************************************/

class Domain extends Record
{

    /********************************************************************
     *	Domain::$initRow												*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
                'domain'		=> 'CAON',
                'language'		=> 'en',
                'name'			=> '',
                'resourcesurl'		=> null);

    /********************************************************************
     *	Domain::$translate												*
     *																	*
     *	Define synonyms for field names.								*
     ********************************************************************/
    protected static $translate	= array(
                'code'			=> 'domain',
                'state'			=> 'state',
                'prov'			=> 'state',
                'province'		=> 'state',
                'cc'			=> 'countrycode',
                'lang'			=> 'language');

    /********************************************************************
     *	Domain::$defaultOrder											*
     *																	*
     *	Define default sort order for use by class RecordSet			*
     ********************************************************************/
    protected static $defaultOrder	= "`Name`";

    /********************************************************************
     *	Domain::country													*
     *																	*
     *	Associated instance of Country									*
     ********************************************************************/
    protected $country			= null;

    /********************************************************************
     *	function Domain::__construct									*
     *																	*
     *	Construct an instance of Domain based upon the					*
     *	parameters.  This instance may or may not be synchronized with	*
     *	the database.													*
     *																	*
     *	Input:															*
     *	    $parms		 		an associative array of parameters		*
     *							to select an existing entry or			*
     *							a complete row from the table as an		*
     *							associative array						*
     *	Note that by convention ISO language codes are expressed in		*
     *	lower case to distinguish them from the ISO country codes.		*
     *	    'FR' is the ISO country code for France						*
     *	    'fr' is the ISO language code for French					*
     *	The ISO country code and the ISO language code can be combined:	*
     *	    'fr-CA'		Canadian French									*
     *	    'en-US'		American English								*
     *																	*
     *	Returns object containing a description of a domain				*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms	= array())
    {
        global $debug;
        global $warn;
        global $connection;

        if (is_null($connection))
        {
            throw new Exception('Domain::constructor: " .
                            "No connection to database server');
        }

        $needInsert	            = false;
        $this->table	        = 'Domains';

        // validate parameters
        if (is_array($parms))
        {
            if (count($parms) < count(self::$initRow))
            {				// search parameters
                $domain		    = 'CAON';
                $cc	            = 'CA';
                $language	    = 'en';
                foreach($parms as $key => $value)
                {			    // loop through parameters
                    $fieldLc	= strtolower($key);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc	= self::$translate[$fieldLc];
                    switch($fieldLc)
                    {			// act on key fields
                        case 'domain':
                        {		// domain
                            if (is_string($value) && strlen($value) >= 4)
                            {
                                $domain		    = strtoupper($value);
                                $cc 	        = substr($domain, 0, 2);
                            }
                            else
                                $this->msg      .= "Invalid domain=$value. ";
                            break;
                        }		// domain

                        case 'language':
                        {		// language code
                            if (is_string($value) && strlen($value) >= 2)
                            {   // ensure is ll-CC
                                $language	    = strtolower(substr($value, 0, 2)) .
                                                  strtoupper(substr($value, 2));
                                if (strlen($language) > 2 &&
                                    substr($language, 2, 1) != '-')
                                    $language   = substr($language, 0, 2) .
                                                  '-' .
                                                  substr($language, 2);
                            }   // ensure is ll-CC
                            else
                                $this->msg      .= "Invalid language=$value. ";
                            break;
                        }		// language code

                        case 'countrycode':
                        {		// countrycode
                            if (is_string($value) && strlen($value) == 2)
                            {
                                $cc 	        = strtoupper($value);
                            }
                            else
                            if ($value instanceof Country)
                            {
                                $this->country  = $value;
                                $cc  	        = $this->country['cc'];
                            }
                            else
                                $this->msg      .= "Invalid countrycode=$value. ";
                            $domain 	        = $cc .
                                                  substr($domain, 2);
                            break;
                        }		// countrycode

                        case 'state':
                        {
                            if (is_string($value) && strlen($value) == 2)
                                $domain	        = substr($domain, 0, 2) .
                                                  strtoupper($value);
                            else
                                $this->msg      .= "Invalid state=$value. ";
                            break;
                        }		// state

                    }			// act on key fields
                }			    // loop through parameters

                // the United Kingdom traditionally uses country code UK
                // even though the code assigned by ISO is GB
                if (substr($domain, 0, 2) == 'UK')
                    $domain     = 'GB' . substr($domain, 2);

                $sqlParms	    = array('domain'	=> $domain,
                                        'language'	=> $language);
                $query		    = 'SELECT * FROM Domains ' .
                                  'WHERE Domain=:domain AND Language=:language';

                // query the database
                $stmt		= $connection->prepare($query);
                $queryText	= debugPrepQuery($query, $sqlParms);

                if ($stmt->execute($sqlParms))
                {			    // successful execute
                    if ($debug)
                        $warn	    .= "<p>Domain::__construct: " .
                                        __LINE__ . "'$queryText'</p>\n";
                    $row		    = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (is_array($row))
                    {			// existing record
                        $dbrow		= $row;
                        $needInsert	= false;
                    }			// existing record
                    else
                    {			// create a new record
                        if ($language != 'en')
                        {		// check for english entry
                            $sqlParms	= array('domain'	=> $domain,
                                                'language'	=> 'en');
                            $queryText	= debugPrepQuery($query, $sqlParms);

                            if ($stmt->execute($sqlParms))
                            {		// successful execute
                                if ($debug)
                                    $warn	.= "<p>Domain::__construct: " .
                                    __LINE__ . "'$queryText'</p>\n";
                                $row		= $stmt->fetch(PDO::FETCH_ASSOC);
                                if (is_array($row))
                                {			// existing record
                                    $dbrow		        = $row;
                                    $dbrow['language']	= $language;
                                }			// existing record
                                else
                                {
                                    $dbrow		        = self::$initRow;
                                    $dbrow['name']	    = 'Domain : '. $domain;
                                }
                            }	            // successful execute
                        }	                // check for english entry
                        else
                        {
                            $dbrow		                = self::$initRow;
                            $dbrow['name']	            = 'Domain : '. $domain;
                        }

                        foreach($parms as $key => $value)
                        {		            // override defaults
                            $fieldLc	= strtolower($key);
                            if (array_key_exists($fieldLc, self::$translate))
                                $fieldLc	= self::$translate[$fieldLc];
                            switch($fieldLc)
                            {		        // act on fields
	                            case 'name':
	                            case 'resourcesurl':
	                            {	
	                                $dbrow[$fieldLc]	= $value;
	                                break;
	                            }

                            }		        // act on key fields
                        }		            // loop through parameters
                        $needInsert	= true;
                    }			            // create new record
                }			                // successful execute
                else
                {			                // error performing prepare
                    error_log("Domain::__construct: query='$queryText', " .
                            print_r($stmt->errorInfo(),true) . "\n");
                    if (strlen($queryText) > 40)
                        $queryText	= substr($queryText, 0, 37) . '...';
                    throw new Exception("Domain::__construct: " . __LINE__ .
                                " '$queryText', " .
                                print_r($stmt->errorInfo(),true));
                }			                // error performing prepare
            }				                // search parameters
            else
            {				                // build from existing record
                $dbrow		= $parms;
                $needInsert	= false;
            }				                // build from existing record
        }				                    // parameter is an array
        else
        {                   				// unsupported data type
            $this->msg      .= "parameter must be an array: is " .
                                print_r($parms, true) . '. ';
        }				                    // unsupported data type

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Domains');
        $this->needInsert	= $needInsert;

        // diagnostic output if debug is set
        $this->dump('Domain Record constructed:');
    }		// Domain::__construct

    /********************************************************************
     *	function Domain:get												*
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
        $matches	= array();
        if (preg_match('/^(.*) AS (.*)$/i', $fieldLc, $matches))
        {
            $fieldExpr	= $matches[1];
            $fieldLc	= strtolower($matches[2]);
        }

        switch($fieldLc)
        {			// act on field name
            case 'domain':
            case 'language':
            case 'name':
            case 'resourcesurl':
            {
                return $this->row[$fieldLc];
            }

            case 'state':
            {
                return substr($this->row['domain'], 2);
            }

            case 'countrycode':
            {
                return substr($this->row['domain'], 0, 2);
            }

            default:
            {		// other fields
                return parent::get($fieldLc);
            }		// other fields
        }			// act on field name
    }			// function Domain::get

    /********************************************************************
     *	function Domain:getCountry										*
     *																	*
     *	Get the associated instance of class Country.                   *
     *																	*
     *	Returns:	                                            		*
     *	    instance of class Country                                   *
     ********************************************************************/
    function getCountry()
    {
        global  $debug;
        global  $warn;

        if (is_null($this->country))
        {
            $cc 	        = substr($this['domain'], 0, 2);
            $this->country	= new Country(array('code'	=> $cc));
        }
        if (!($this->country->isExisting()))
        {
            if ($debug)
            {
                $warn	    .= "<p>Domain::getCountry: " .
                               __LINE__ .
                               " no match for country code '$cc'</p>\n";
	            $trace          = debug_backtrace();
	            foreach($trace as $call)
	            {
	                if (array_key_exists('class', $call))
	                    $classRef       = $call['class'] . '::';
	                else
	                    $classRef       = '';
	                if ($debug)
	                    $warn   .= "<p>Domain::getCountry: " . __LINE__ .
	                            " function=" . $classRef . $call['function'] .
	                            " called by line=" . $call['line'] . 
	                            " in " . $call['file'] . "</p>\n";
	            }
            }
        }
        return $this->country;
    }           // function Domain::getCountry

    /********************************************************************
     *	function Domain:set												*
     *																	*
     *	Change the value of a field in the object.						*
     *	The method validates that the supplied value is compatible with *
     *	the target field.												*
     *																	*
     *	Parameters:														*
     *	    $field		name of field to change							*
     *	    $value		new value to set it to							*
     *																	*
     *	Side Effects:													*
     *	    Adds trace if $field is not already defined as a field		*
     *	    or pseudo-field and debugging is enabled					*
     ********************************************************************/
    function set($field, $value)
    {
        global $warn; 

        $fieldLc	= strtolower($field);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc	= self::$translate[$fieldLc];

        switch($fieldLc)
        {
            case 'state':
            {
                if (is_string($value) && strlen($value) >= 2)
                    return parent::set('domain',
                                       substr($this->row['domain'], 0, 2) .
                                            strtoupper($value));
                else
                    $warn	.= "<p>Domain::set: " . __LINE__ .
                                " invalid value '$value' for `$field`. </p>\n";
                return $this->get('state');
                break;
            }

            case 'countrycode':
            {
                if (is_string($value) && strlen($value) == 2)
                    return parent::set('domain',
                                       strtoupper($value) . 
                                            substr($this->row['domain'], 2));
                else
                    $warn	.= "<p>Domain::set: " . __LINE__ .
                                " invalid value '$value' for `$field`. </p>\n";
                return $this->get('countrycode');
                break;
            }

            case 'domain':
            {
                if (is_string($value) && strlen($value) >= 4)
                    return parent::set('domain',
                                       strtoupper($value)); 
                else
                    $warn	.= "<p>Domain::set: " . __LINE__ .
                                " invalid value '$value' for `$field`.</p>\n";
                return $this->get('domain');
                break;
            }

            default:
            {
                return parent::set($fieldLc, $value);
            }
        }
    }		// Domain::set

    /********************************************************************
     *	Domain::getRealFieldName										*
     *																	*
     *	Perform standard manipulation for this table to obtain the		*
     *	the name of a field from a suggested parameter name.			*
     *																	*
     *	Returns:														*
     *	    A field name in lower case or null if input is invalid.		*
     ********************************************************************/
    public static function getRealFieldName($fldname)
    {
        $fieldLc	= strtolower($fldname);
        if (array_key_exists($fieldLc, self::$translate))
            $fieldLc	= self::$translate[$fieldLc];
        if ($fieldLc == 'offset' || $fieldLc == 'limit')
            return $fieldLc;
        else
        if (array_key_exists($fieldLc, self::$initRow))
            return $fieldLc;
        else
        if ($fieldLc == 'countrycode')
            return "LEFT(`Domain`,2) AS CountryCode"; // pseudo-field
        else
        if ($fieldLc == 'state')
            return "SUBSTR(`Domain`,3) AS State"; // pseudo-field
        else
            return null;
    }		// static function getRealFieldName

    /********************************************************************
     *	function Domain::save											*
     *																	*
     *	Save changes made to the Domain record into the database.		*
     *																	*
     *	Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format within <cmd> tags.						*
     *					if a string use it as tag name					*
     *																	*
     *	Returns:														*
     *	    false		if the method was unable to update the database	*
     *	    1			if the method updated the database record		*
     *	    0			if the record did not need to be updated		*
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
            $fldnames	        = '';
            $comma	            = '';
            $values	            = '';
            $parms	            = array();
            foreach($this->row as $fld => $value)
            {
                $fldnames	    .= $comma . "`$fld`";
                $values		    .= $comma . ":$fld";
                $parms[$fld]	= $value;
                $comma	        = ", ";
            }		// loop through all fields in record

            // construct the SQL INSERT command	
            $insert	= "INSERT INTO Domains ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt	            = $connection->prepare($insert);
            $insertText	= debugPrepQuery($insert, $parms);
            if ($stmt->execute($parms))
            {		// execute successful
                $result		    = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count		    = count($result);
                if ($xml)
                    print "<$tagname result='$count'>" . $insertText . 
                            "</$tagname>\n";
                if ($debug)
                {
                    $warn	    .= "<p>$insertText</p>\n";
                    $warn	    .= "<p>count=$count</p>\n";
                }

                $this->changed		= array();	// reset
                $this->needInsert	= false;

                // log the update
                logSqlUpdate($insert,
                             $parms,
                             0,		// compound key
                             '',
                             $this->toJson(false));
            }		// execute successful
            else
            {		// error inserting record
                throw new Exception("Domain::save:
                                '$insertText', " .
                                print_r($stmt->errorInfo(),true));
            }		// error inserting record
        }		    // create new record
        else
        {		    // update existing record
            $set		        = '';
            $comma		        = '';
            $count		        = 0;
            $parms		        = array();
            foreach($this->changed as $fld => $value)
            {
                $set		    .= $comma . "`$fld`=:$fld";
                $parms[$fld]	= $value;
                $comma	        = ', ';
            }		// loop through all fields in record
            $this->changed	    = array();	// reset

            if (strlen($set) > 0)
            {		// changes made
                // assemble the UPDATE command
                $update		= "UPDATE Domains SET " . $set .
                           " WHERE `Domain`=:odomain AND `Language`=:olanguage";
                if ($this->former)
                {		// record updated
                    $parms['odomain']	= $this->former['domain'];
                    $parms['olanguage']	= $this->former['language'];
                }		// record updated
                else
                {		// record not updated
                    $parms['odomain']	= $this->row['domain'];
                    $parms['olanguage']	= $this->row['language'];
                }		// record not updated

                // perform the update
                $stmt		            = $connection->prepare($update);
                $updateText	            = debugPrepQuery($update, $parms);
                if ($stmt->execute($parms))
                {		// successful update 
                    $count		        = $stmt->rowCount();
                    if ($xml)
                        print "<$tagname count='$count'>$updateText</$tagname>\n";
                    if ($debug)
                        $warn	    .= "<p>'$updateText' count=$count</p>\n";

                    // log the update
                    $this->log($update,
                               $parms);
                }		// successful update 
                else
                {		// error updating record
                    error_log("Domain::save: query='$updateText'\n");
                    if (strlen($updateText) > 40)
                        $updateText	= substr($updateText, 0, 37) . '...';
                    throw new Exception("Domain::save: " . __LINE__ .
                                " '$updateText' failed: " .
                                print_r($stmt->errorInfo(),true));
                }		// error updating record
            }		// changes made
        }		// update existing record
        return $count;
    }		// Domain::save

    /********************************************************************
     *	function Domain::delete											*
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
            if ($this->former)
            {
                $sqlParms	= array('domain'   => $this->former['domain'],
                                    'language' => $this->former['language']);
            }
            else
            {
                $sqlParms	= array('domain'   => $this->row['domain'],
                                    'language' => $this->row['language']);
            }
            $delete 	    = "DELETE FROM Domains " .
                            "WHERE `Domain`=:domain AND `Language`=:language";
            $deleteText	    = debugPrepQuery($delete, $sqlParms);

            $stmt   	    = $connection->prepare($delete);
            if ($stmt->execute($sqlParms))
            {		// success

                $count	    = $stmt->rowCount();
                if ($xml)
                    print "<$xmltag count='$count'>$deleteText</$xmltag>\n";
                if ($debug)
                    $warn	.= "<p>Domain::delete: '$deleteText' count='$count'</p>\n";

                // log the update
                $this->log($delete,
                           $sqlParms);

                // update the memory copy of the record to reflect that it
                // no longer represents a database record
                $this->needInsert	= true;

                return $count;	// number of records deleted
            }		// success
            else
            {		// error updating record
                error_log("Domain::delete: query='$deleteText', " .
                          print_r($stmt->errorInfo(),true) . 
                          "\n");
                return false;
            }		// error updating record
        }		// have key value

        // record never written to database
        return 0;	// nothing to delete
    }		// Domain::delete

    /********************************************************************
     *	function Domain::getName										*
     *																	*
     *	Get the name of the domain.										*
     *																	*
     *	Input:															*
     *	    $options	if even just return value of field 'name'		*
     *					if odd return "country: name"			        *
     *																	*
     *	Returns:														*
     *	    Name of the domain as a string.								*
     ********************************************************************/
    function getName($options	= 0)
    {
        global	$warn;

        if ($options & 1)
        {
            $country        = $this->getCountry();
            $lang	        = $this->row['language'];
            return $country->getName($lang) . ': ' .
                    $this->row['name'];
        }
        else
            return $this->row['name'];
    }		// Domain::getName

    /********************************************************************
     *	function Domain::cmp											*
     *																	*
     *	Compare an instance of domain to another instance of Domain		*
     *																	*
     *	Returns:														*
     *			-1 if this is less than other							*
     *			1 if this is greater than other							*
     *			0 if this is equal other			                    *
     ********************************************************************/
    function cmp(Domain $other)
    {
        $val1		= strtolower($this->row['name']);
        $val2		= strtolower($other->row['name']);
        if ($val1 < $val2)
            return -1;
        else
        if ($val1 > $val2)
            return 1;
        else
            return 0;
    }		// Domain::cmp

    /********************************************************************
     *	function Domain::sortcallback									*
     *																	*
     *	Satisfy the stupid callback function demanded by PHP uasort.	*
     ********************************************************************/
    static function sortcallback(Domain $a, Domain $b)
    {
        return $a->cmp($b);
    }

    /********************************************************************
     *	function Domain::toString										*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function toString()
    {
        return  $this->row['domain'];
    }		// Domain::__toString

}		// class Domain
