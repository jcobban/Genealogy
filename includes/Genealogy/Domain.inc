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
 *		PartOf          VARCHAR(16),                                    *
 *		Category        VARCHAR(32) DEFAULT 'State',                    *
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
 *		2019/07/22      when creating new domain use supplied code      *
 *		                show and accept domain code in ISO format       *
 *		                with a hyphen between the country and state     *
 *		2019/07/25      do not permit set for 'iso'                     *
 *		                'iso' is changed when 'domain' is changed       *
 *		2019/12/05      add column Category                             *
 *		                remove deprecated support for array sort        *
 *		                add method getLanguage                          *
 *		                support passing instance of Language to new     *
 *		                correct bugs in constructor                     *
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
                				'domain'		    => 'CAON',
                				'language'		    => 'en',
                				'name'			    => '',
                                'resourcesurl'		=> null,
                                'partof'            => null,
                                'category'          => 'State');

    /********************************************************************
     *	Domain::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     		=> 'Domains',
                'name'      		=> 'Domains',
                'prime'	    		=> array('domain','language'),
	            'srvmg'     		=> false,
	            'fldcount'			=> 4,
                'order'     		=> '`Name`,`Language`',
                'classname' 		=> 'Domain');

    /********************************************************************
     *	Domain::$translate												*
     *																	*
     *	Define synonyms for field names.								*
     ********************************************************************/
    protected static $translate	= array(
                				'code'				=> 'domain',
                				'state'				=> 'state',
                				'statecode'			=> 'state',
                				'prov'				=> 'state',
                				'province'			=> 'state',
                				'statename'			=> 'name',
                				'cc'				=> 'countrycode',
                				'lang'				=> 'language');

    /********************************************************************
     *	Domain::$defaultOrder											*
     *																	*
     *	Define default sort order for use by class RecordSet			*
     ********************************************************************/
    protected static $defaultOrder	= "`Name`,`Language`";

    /********************************************************************
     *	Domain::country													*
     *																	*
     *	Associated instance of Country									*
     ********************************************************************/
    protected $country			= null;

    /********************************************************************
     *	Domain::language												*
     *																	*
     *	Associated instance of Language 								*
     ********************************************************************/
    protected $language			= null;


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
     *																	*
     *	Returns object containing a description of a domain				*
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
                $domain		    = 'CAON';   // default
                $cc	            = 'CA';     // default
                $language	    = 'en';     // default
                foreach($parms as $key => $value)
                {			    // loop through parameters
                    $fieldLc	= strtolower($key);
                    if (array_key_exists($fieldLc, self::$translate))
                        $fieldLc	= self::$translate[$fieldLc];
                    switch($fieldLc)
                    {			// act on key fields
                        case 'countrycode':
                        {		// countrycode
                            if ($value instanceof Country)
                            {
                                $this->country  = $value;
                                $cc             = $value['code'];
                            }
                            else
                            if (is_string($value) && strlen($value) == 2)
                                $cc 	        = strtoupper($value);
                            else
                                $this->msg      .= "Invalid CountryCode='$value'. ";
                            break;
                        }		// countrycode

                        case 'domain':
                        {		// domain
                            $domain             = str_replace('-', '', $value);
                            if (is_string($domain) && strlen($domain) >= 4)
                            {
                                $domain		    = strtoupper($domain);
                                $cc 	        = substr($domain, 0, 2);
                            }
                            else
                                $this->msg      .= "Invalid domain='$value'. ";
                            break;
                        }		// domain

                        case 'language':
                        {		// language code
                            if ($value instanceof Language)
                            {
                                $this->language = $value;
                                $language       = $value['code'];
                            }
                            if (is_string($value) && strlen($value) >= 2)
                            {   // ensure is ll-CC
                                $language	    = strtolower(substr($value, 0, 2)); 
                                if (strlen($language) > 2)
                                {
                                    if (substr($language, 2, 1) == '-')
                                        $language   .= strtoupper(substr($value, 2));
                                    else
                                    $language   .= '-' .
                                    strtoupper(substr($value, 2));
                                }
                            }
                            else
                                $this->msg      .= "Invalid language='$value'. ";
                            break;
                        }		// language

                        case 'state':
                        {
                            if (is_string($value) && strlen($value) >= 2)
                                $domain	        = $cc .
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
                    $domain         = 'GB' . substr($domain, 2);

                if (strlen($language) > 2)
                    throw new Exception("language '$language' too long");
                $query		        = 'SELECT * FROM Domains ' .
                                'WHERE Domain=:domain AND Language=:language';
                $sqlParms	        = array('domain'	=> $domain,
                                            'language'	=> $language);

                // query the database
                $stmt		        = $connection->prepare($query);
                $queryText	        = debugPrepQuery($query, $sqlParms); 

                $this->lastSqlCmd   = $queryText;
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
                            $sqlParms	    = array('domain'	=> $domain,
                                                    'language'	=> 'en');
                            $queryText	    = debugPrepQuery($query, $sqlParms);

                            $this->lastSqlCmd   = $queryText;
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
                            $dbrow['domain']            = $domain;
                            $dbrow['name']	            = 'Domain : '. $domain;
                        }

                        foreach($parms as $key => $value)
                        {		            // override defaults
                            $fieldLc	        = strtolower($key);
                            if (array_key_exists($fieldLc, self::$translate))
                                $fieldLc	    = self::$translate[$fieldLc];
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
                        $needInsert	            = true;
                    }			            // create new record
                }			                // successful execute
                else
                {			                // error performing prepare
                    error_log("Domain::__construct: query='$queryText', " .
                            print_r($stmt->errorInfo(),true) . "\n");
                    if (strlen($queryText) > 40)
                        $queryText	= substr($queryText, 0, 37) . '...';
                    $this->msg  .= "Domain::__construct: " . __LINE__ .
                                " '$queryText', " .
                                print_r($stmt->errorInfo(),true) . '. ';
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

        // initialize special access fields
        $domain         = $this->row['domain'];
        $cc             = substr($domain, 0, 2);
        $state          = substr($domain, 2);
        $this->extras['iso']    = "$cc-$state";

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
        global  $warn;

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
            {
                return $this->row['domain'];
            }

            case 'iso':
            {
                return $this->extras['iso'];
            }

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

            case 'partof':
            {
                $partof     = $this->row['partof'];
                if (is_null($partof))
                {
                    $cc     = substr($this->row['domain'], 0, 2);
                    return $cc;
                }
                else
                {
                    return $partof;
                }
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
        return $this->country;
    }           // function Domain::getCountry

    /********************************************************************
     *	function Domain:getLanguage										*
     *																	*
     *	Get the associated instance of class Language.                  *
     *																	*
     *	Returns:	                                            		*
     *	    instance of class Language                                  *
     ********************************************************************/
    function getLanguage()
    {
        global  $debug;
        global  $warn;

        if (is_null($this->language))
        {
            $lang 	        = $this['language'];
            $this->language	= new Language(array('code'	=> $lang));
        }
        return $this->language;
    }           // function Domain::getLanguage

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
                {
                    $cc             = substr($this->row['domain'], 0, 2);
                    $state          = strtoupper($value);
                    $this->extras['iso']    = "$cc-$state";
                    $retval         = substr($this->row['domain'], 2);
                    parent::set('domain', $cc . $state);
                    return $retval;
                }
                else
                    $warn	.= "<p>Domain::set: " . __LINE__ .
                                " invalid value '$value' for `$field`. </p>\n";
                return $this->get('state');
                break;
            }

            case 'countrycode':
            {
                if (is_string($value) && strlen($value) == 2)
                {
                    $cc             = strtoupper($value);
                    $state          = substr($this->row['domain'], 2);
                    $this->extras['iso']    = "$cc-$state";
                    $retval         = substr($this->row['domain'], 0, 2);
                    parent::set('domain', $cc . $state);
                    return $retval;
                }
                else
                    $warn	.= "<p>Domain::set: " . __LINE__ .
                                " invalid value '$value' for `$field`. </p>\n";
                return $this->get('countrycode');
                break;
            }

            case 'domain':
            {
                $domain             = $this->row['domain'];
                $cc                 = substr($domain, 0, 2);
                $state              = substr($domain, 2);
                $retval             = "$cc-$state";
                $nvalue             = strtoupper(str_replace('-', '', $value));
                if (is_string($nvalue) && strlen($nvalue) >= 4)
                {
                    parent::set('domain',
                                $value); 
                    $cc             = substr($nvalue, 0, 2);
                    $state          = substr($nvalue, 2);
                    $this->extras['iso']    = "$cc-$state";
                }
                else
                    $warn	.= "<p>Domain::set: " . __LINE__ .
                                " invalid value '$value' for `$field`.</p>\n";
                return $retval;
            }

            case 'iso':
            {                       // ignore
                return $this->row['iso'];
            }                       // ignore

            case 'partof':
            {
                $cc             = substr($this->row['domain'], 0, 2);
                if ($value == $cc)
                    return parent::set('partof', null);
                else
                if (substr($value, 0, 2) == $cc)
                {
                    $state      = substr($value, 2);
                    if (substr($state, 0, 2) == '-')
                        return parent::set('partof', $value);
                    else
                        return parent::set('partof', "$cc-$state");
                }
                $warn       .= "<p>Domain::set: new value of Partof '$value' must match country code '$cc'</p>\n";
                return $this->row['partof'];
            }               // partof

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
        global	$msg;
        global	$userid;
        global	$connection;

        $count              = false;

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
            $tagname		= 'cmd';
        }			    // boolean true

        if ($this->needInsert)
        {		        // create new record
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
            }		    // loop through all fields in record

            // construct the SQL INSERT command	
            $insert	= "INSERT INTO Domains ($fldnames) VALUES($values)";

            // insert the new record into the database
            $stmt	            = $connection->prepare($insert);
            $insertText	= debugPrepQuery($insert, $parms);
            $this->lastSqlCmd   = $insertText;
            if ($stmt->execute($parms))
            {		    // execute successful
                $result		    = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count		    = count($result);
                if ($xml)
                    print "$indent<$tagname result='$count'>" . $insertText . 
                            "$indent</$tagname>\n";
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
            }		    // execute successful
            else
            {		    // error inserting record
                $msg    .= "Domain::save:
                                '$insertText', " .
                                print_r($stmt->errorInfo(),true);
            }		    // error inserting record
        }		        // create new record
        else
        {		        // update existing record
            $set		        = '';
            $comma		        = '';
            $count		        = 0;
            $parms		        = array();
            foreach($this->changed as $fld => $value)
            {
                $set		    .= $comma . "`$fld`=:$fld";
                $parms[$fld]	= $value;
                $comma	        = ', ';
            }		    // loop through all fields in record
            $this->changed	    = array();	// reset

            if (strlen($set) > 0)
            {		    // changes made
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
                $this->lastSqlCmd       = $updateText;
                if ($stmt->execute($parms))
                {		// successful update 
                    $count		        = $stmt->rowCount();
                    if ($xml)
                        print "$indent<$tagname count='$count'>\n" .
                                $updateText .
                                "\n$indent</$tagname>\n";
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
                    $msg   .= "Domain::save: " . __LINE__ .
                                " '$updateText' failed: " .
                                print_r($stmt->errorInfo(),true);
                }		// error updating record
            }		    // changes made
        }		        // update existing record
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
            $delete 	            = "DELETE FROM Domains " .
                            "WHERE `Domain`=:domain AND `Language`=:language";
            $deleteText	            = debugPrepQuery($delete, $sqlParms);

            $stmt   	            = $connection->prepare($delete);
            $this->lastSqlCmd       = $deleteText;
            if ($stmt->execute($sqlParms))
            {		// success

                $count	            = $stmt->rowCount();
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
     *	function Domain::toString										*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function toString()
    {
        return  $this->row['domain'];
    }		// Domain::__toString

}		// class Domain
