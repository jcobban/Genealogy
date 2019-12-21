<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  County.inc															*
 *																		*
 *  Definition of a class representing a county, specifically as a		*
 *  registration authority for vital statistics.						*
 *  This class provides access to the information in a row				*
 *  of the table Counties.												*
 *																		*
 *  TABLE Counties (													*
 *		Domain			CHAR(4) NOT NULL DEFAULT 'CAON',				*
 *		Code			CHAR(3) NOT NULL DEFAULT '',					*
 *		Name			VARCHAR(255) NOT NULL DEFAULT '',				*
 *		StartYear		INTEGER(4) NOT NULL DEFAULT '1852',				*
 *		EndYear			INTEGER(4) NOT NULL DEFAULT '9999')				*
 *		UNIQUE KEY `Domain` (`Domain`, `Code`),							*
 *		KEY `Name` (`Name`) )											*
 *																		*
 *  History:															*
 *		2013/11/25		created											*
 *		2014/09/29		change name of static get routine to getCounties*
 *						redefine its parameter to support an array		*
 *		2014/10/19		prov field deprecated in favor of domain		*
 *		2014/11/03		code field is always stored as text				*
 *		2014/12/25		change signature of static function getCounties	*
 *						return count value from getCounties				*
 *						remove table column `Prov`						*
 *						add method getField to simulate field 'Prov'	*
 *		2015/07/10		diagnostic output redirected to $warn			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2016/06/19		support being called with just county code or	*
 *						with Canadian province or US state plus county	*
 *						to avoid confusing $dbrow not defined error		*
 *						support passing Domain object to constructor	*
 *		2017/02/07		add method getDomainObj for efficiency			*
 *		2017/06/11		do not throw exception from getCounties			*
 *		2017/10/09		add static methods getWhere and deleteCounties	*
 *						add method toXml to support getRecordXml.php	*
 *		2017/10/21		permit updating county code						*
 *						replace getCounties, update..., delete...		*
 *						with class CountySet							*
 *		2017/12/18		add support for temporary fields				*
 *		2018/02/18		do not dump out Domain object in debug line		*
 *						for constructor call							*
 *		2018/11/11      do not throw exception from constructor for     *
 *		                bad parameters                                  *
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/05/18      add $info                                       *
 *		2919/12/95      support ISO format Domain                       *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Domain.inc';

/************************************************************************
 *  class County														*
 *																		*
 *  Definition of a class recording information about a single county	*
 *  for vital statistics registrations.									*
 *																		*
 ************************************************************************/

class County extends Record
{
    /********************************************************************
     *	County::$initRow												*
     *																	*
     *	Default initial row												*
     ********************************************************************/
    protected static $initRow	= array(
				'domain'		=> 'CAON',
				'code'			=> null,
				'name'			=> '',
				'startyear'		=> 1852,
				'endyear'		=> 9999);

    /********************************************************************
     *	County::$info											        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                'table'     		=> 'Counties',
                'name'      		=> 'Counties',
                'prime'	    		=> array('domain', 'code'),
	            'srvmg'     		=> false,
	            'fldcount'			=> 6,
                'order'     		=> 'Name',
                'classname' 		=> 'County');

    /********************************************************************
     *	County::$translate												*
     *																	*
     *	Define alternate field names									*
     ********************************************************************/
    protected static $translate	= array(
				'province'		=> 'prov',
				'cc'			=> 'countrycode',
				'county'		=> 'code');

    /********************************************************************
     *	County::$defaultOrder											*
     *																	*
     *	Default sort order												*
     ********************************************************************/
    protected static $defaultOrder	= "`Name`";

    /********************************************************************
     *	County->domain												*
     *																	*
     *	save the associated instance of Domain to avoid recreating it	*
     ********************************************************************/
    private $domain 		= null;

    /********************************************************************
     *	function County::__construct									*
     *																	*
     *	Construct an instance of County based upon the					*
     *	parameters.  This instance may or may not be synchronized with	*
     *  the database.	            									*
     *																	*
     *	Input:															*
     *	    $domain				an associative array obtained from		*
     *							an SQL query by a static method			*
     *							or containing search parameters			*
     *							or instance of Domain                   *
     *	    				 	or administrative domain id (depr.)	    *
     *							(2 char country code + state/prov code)	*
     *	    $code				3 char county abbreviation for use		*
     *							with administrative domain				*
     *																	*
     *	Returns object containing a description of a county				*
     *																	*
     *	Throws Exception if unable to complete function					*
     ********************************************************************/
    function __construct($parms,
						 $code	= null)
    {
		global $debug;
		global $warn;
		global $msg;
		global $connection;

		$needInsert 	    = false;
		$this->table	    = 'Counties';

        // validate parameters
        if (is_string($parms))
        {
            if (is_string($code))
            {
                if ($debug)
                    $warn	.= "<p>County::__construct('$parms', '$code')</p>\n";
                // reformat parameters as array
                $parms      = array('domain'    => $parms,
                                    'code'      => $code);
            }
            else
            {
                if ($debug)
				    $warn	.= "<p>County::__construct('$parms', null)</p>\n";
                // reformat parameters as array
                $parms      = array('domain'    => $parms,
                                    'code'      => null);
				$this->msg 	= 'Missing county code specification. ';
            }
        }

		if (is_array($parms))
		{
            if ($debug)
            {
				$warn	    .= "<p>County::__construct(array(";
			    $comma	        = '';
			    foreach($parms as $field => $value)
                {
				    if ($value instanceof Record)
						$warn	.= "$comma'$field'=>" . get_class($value) .
							            " Object";
				    else
				    if (is_string($value) || is_numeric($value))
						$warn	.= "$comma'$field'=>$value";
				    else
						$warn	.= "$comma'$field'=>" . print_r($value, true);
                    $comma	= ',';
                }
			}
		}
        else
        if ($debug)
			$warn	.= "<p>County::__construct(" .
						print_r($domain,true) . "," . 
						print_r($code,true) . ")</p>\n";

		// validate parameters
		if (is_array($parms))
		{			// array
		    if (count($parms) < count(self::$initRow))
		    {			// associative array of parameters
				$domain	    	= 'CAON';	// default
				foreach($parms as $key => $value)
				{			// loop through parameters
				    switch(strtolower($key))
				    {		// act on key fields
						case 'domain':
						{		// domain
						    if ($value instanceof Domain)
						    {
						    	$domainObj	    = $value;
                                $domain	        = $domainObj->get('domain');
						    }
						    else
						    {
							    $domainObj	    = $this->getDomain($value);
							    $domain	        = $value;
						    }
						    $this->domain	    = $domainObj;

                            $cc		            = substr($domain, 0, 2);
                            if (substr($domain, 2, 1) == '-')
						        $province	    = substr($domain, 3);
                            else
						        $province	    = substr($domain, 2);
						    if (!$domainObj->isExisting())
							    $this->msg	    .= "Domain '$domain' must be a supported two character country code followed by a 2 or 3 character character state or province code. ";
						    break;
						}		// domain

						case 'prov':
						{		// province code
						    if (is_null($this->domain))
						    {
						    	$domain		    = 'CA' . strtoupper($value);
							    $cc		        = 'CA';
							    $province	    = strtoupper($value);
							    $domainObj	    = $this->getDomain($domain);
						    	if (!$domainObj->isExisting())
                                    $this->msg	.= "County::new: " . __LINE__ .
   " Province '$value' must be a valid 2 character character code for Canada. ";
						    }
						    break;
						}		// province code

						case 'state':
						{		// state code
						    if (is_null($this->domain))
						    {
							    $domain		    = 'US' . strtoupper($value);
							    $cc	    	    = 'US';
							    $province	    = strtoupper($value);
						    	$domainObj	    = $this->getDomain($domain);
							    if (!$domainObj->isExisting())
                                    $this->msg	.= "County::new: " . __LINE__ .
    " State '$value' must be a valid 2 character character code for the United States of America. ";
						    }
						    break;
						}		// state code

						case 'code':
						case 'county':
						{		// county code
						    $code			= $value;
						    if (!is_string($code))
							    $this->msg	.= __LINE__ . " County code '$code' must be a string. ";
						    break;
						}		// county code
				    }		// act on key fields
				}			// loop through parameters

				// read the record from the database
				$query	            = "SELECT * FROM Counties " .
						    	"WHERE `Domain`=:domain AND `Code`=:code";
				$sqlParms	        = array('domain'	=> $domain,
							                'code'		=> $code);

				// query the database
				$stmt	            = $connection->prepare($query);
			    $queryText	        = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
				if ($stmt->execute($sqlParms))
				{			// successful query
				    if ($debug)
						$warn	    .= "<p>$queryText</p>\n";

				    $result	        = $stmt->fetchAll(PDO::FETCH_ASSOC);
				    if (count($result) > 0)
				    {		// existing record
						$dbrow	    	= $result[0];
						$needInsert		= false;
				    }		// existing record
				    else
				    {		// create a new record
						// set defaults
						$dbrow		    = self::$initRow;
						$dbrow['domain']= $this->domain->get('domain');
						$dbrow['code']	= $code;
						$dbrow['name']	= $code;
						$needInsert	    = true;
				    }		// create new record
				}			// successful query
				else
				{			// error performing query
				    error_log("County.inc " . __LINE__ ." query='$queryText'</p>\n");
				    $this->msg     .= __LINE__ . " query='$queryText' " .
								    print_r($stmt->errorInfo(),true);
				}			// error performing query
		    }			    // associative array of parameters
		    else
		    {			    // build from existing record
				$dbrow		            = $parms;
				if ($dbrow['domain'] instanceof Domain)
				{
				    $this->domain	    = $dbrow['domain'];
				    $domain         	= $this->domain->get('domain');
				    $dbrow['domain']	= $domain;
				}
                else
                {
				    $domain         	= $dbrow['domain'];
				    $domainObj	        = $this->getDomain($dbrow['domain']);
                }
		    }			// build from existing record
		}			// array
		else
		{		// unsupported data type
		    $this->msg	.= "first parameter must be a string or array: is " .
						    print_r($domain, true) . '.';
		}		// unsupported data type

		// invoke constructor of base class
		if (!isset($dbrow))
		{
		    $dbrow		        = self::$initRow;
		    $dbrow['domain']	= $domain;
		    $dbrow['code']	    = $code;
		    $dbrow['name']	    = $code;
		    $needInsert		    = true;
		}
		parent::__construct($dbrow,
						    'Counties');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
        $this->dump('County Record constructed:');

        $msg                .= $this->msg;
    }		// function County::__construct

    /********************************************************************
     *	function County:get												*
     *																	*
     *	Obtain the current value of a field in the object.				*
     *																	*
     *  Parameters:														*
     *	    $name		name of field to retrieve						*
     *																	*
     *	Side Effects:													*
     *	    Adds warning if $field is not already defined as a field	*
     *	    or pseudo-field and returns null.							*
     ********************************************************************/
    function get($field)
    {
		$fieldLc		= strtolower($field);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc		= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'prov':
		    {
				return substr($this->row['domain'], 2, 2);
		    }		// Province or State code

		    case 'cc':
		    {
				return substr($this->row['domain'], 0, 2);
		    }		// Province or State code

		    default:
		    {
				return parent::get($fieldLc);
		    }		// other fields
		}		    // check field name
    }			// function County::get

    /********************************************************************
     *	County::getRealFieldName										*
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
		if ($fieldLc == 'prov')
		    return "SUBSTR(`Domain`,3) AS Prov"; // pseudo-field
		else
		    return null;
    }		// static function getRealFieldName

    /********************************************************************
     *	function County::getName										*
     *																	*
     *	Convenience function for accessing the 'name' attribute.		*
     *																	*
     *	Returns:														*
     *	    Name of the county as a string.								*
     ********************************************************************/
    function getName($options	= null)
    {
		return $this->row['name'];
    }		// function County::getName

    /********************************************************************
     *	function County::getDomain  									*
     *																	*
     *	Convenience function for accessing the associated Domain object	*
     *																	*
     *	Input:															*
     *		if this is invoked before the object is initialized 		*
     *		pass the domain identifier                                  *
     *		                                                            *
     *	Returns:														*
     *	    instance of Domain											*
     ********************************************************************/
    function getDomain($domain = null)
    {
        if (is_null($this->domain))
        {
            if (is_null($domain))
                $domain         = $this->get('domain');
			$this->domain	    = new Domain(array('domain'	    => $domain,
                                                   'language'	=> 'en'));
        }
		return $this->domain;
    }		// function County::getDomain

    /********************************************************************
     *	fuunction County::toXml											*
     *																	*
     *  Emit the contents of the object as XML.							*
     *																	*
     *	Parameters:														*
     *	    $top		tag name as a string for the tag enclosing the	*
     *					values.											*
     *					If null, do not enclose the individual values	*
     *					inside a root node.								*
     *	    $print		if true print the output, else just return it	*
     *	    $options	match signature									*
     *																	*
     *	Returns:														*
     *	    XML representation of the object							*
     ********************************************************************/

    /********************************************************************
     *	Constant masks for options in 3rd parameter						*
     ********************************************************************/
    const	TOXML_INCLUDE_TOWNSHIPS	= 1;

    function toXml($top,
				   $print	= true,
				   $options	= 0)
    {
		// ensure that there is a label to use for the top element
		if (!is_string($top) || strlen($top) == 0)
		    $top	= 'County';
		if (is_null($top))
		    $retval	= '';
		else
		    $retval	= "    <$top>\n";

		foreach($this->row as $key => $value)
		{
		    $retval	.= "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
		}

		// get list of townships
		if ($options & self::TOXML_INCLUDE_TOWNSHIPS)
		{
		    $getparms	= array('domain'	=> $this->row['domain'],
							'county'	=> $this->row['code']);
		    $townships	= Township::getTownships($getparms);
		    $retval	.= "<townships count='" . $getparms['count'] . "'>\n";
		    foreach($townships as $township)
		    {			// loop through townships
				$retval	.= $township->toXml('township', false);
		    }			// loop through townships
		    $retval	.= "</townships>\n";
		}			// include sub-districts
		if ($top !== null)
		    $retval	.= "    </$top>\n";
		if ($print)
		    print $retval;
		return $retval;
    }			// function County::toXml

    /********************************************************************
     *	function County::__toString										*
     *																	*
     *	Implement conversion to string.									*
     ********************************************************************/
    function __toString()
    {
		return  $this->row['code'];
    }		// function County::__toString

}		// class County
