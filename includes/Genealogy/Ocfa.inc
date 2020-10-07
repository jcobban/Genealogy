<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Ocfa.inc												            *
 *																		*
 *  Definition of a class representing a record in the					*
 *  Ontario Cemetery Finding Aid (OCFA) table.                          *
 *																		*
 *		CREATE TABLE `Ocfa` (								            *
 *		    `RecordIndex` INT(11) NOT NULL AUTO_INCREMENT,              *
 *	    	`Surname`           VARCHAR(48),							*
 *	    	`GivenName`         VARCHAR(48),							*
 *	    	`Cemetery`          VARCHAR(64),							*
 *	    	`County`            VARCHAR(32),							*
 *	    	`Township`          VARCHAR(48),							*
 *	    	`Reference`         VARCHAR(16),							*
 *	    	`Soundex`           CHAR(4) )								*
 *	    	PRIMARY KEY (`RecordIndex`),								*
 *		    KEY `Surname` (`Surname`),									*
 *		    KEY `GivenName` (`GivenName`),								*
 *		    KEY `Cemetery` (`Cemetery`),								*
 *		    KEY `County` (`County`),									*
 *		    KEY `Township` (`Township`),								*
 *		    KEY `Soundex` (`Soundex`) )									*
 *          ENGINE=InnoDBDEFAULT CHARSET=utf8							*
 *																		*
 *  History:															*
 *		2018/11/21		created											*
 *		2019/04/11      support case independent search for record      *
 *		                key, and support key synonym                    *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Ocfa												            *
 *																		*
 *  Definition of a class implementing the interface to a record		*
 *  in the Ontario Cemetery Finding Aid (OCFA) table.					*
 ************************************************************************/

class Ocfa extends Record
{

    /********************************************************************
     *	Ocfa::$initRow		array containing default values for			*
     *						each field in a new record					*
     ********************************************************************/
    protected static	$initRow	= array(
                    'recordindex'       => 0,
                    'surname'			=> '',
                    'givenname'			=> '',
                    'cemetery'			=> '',
                    'county'			=> '',
                    'township'			=> '',
                    'reference'			=> '',
                    'soundex'           => '');

    /********************************************************************
     *	Ocfa::$translate                                                *
     *																	*	
     *	static array containing alternate field names		            *
     ********************************************************************/
    protected static	$translate	= array(
						'id'				=> 'recordindex');

    /********************************************************************
     *	Ocfa::$defaultOrder		                						*
     *																	*
     *	Definition of default order for records retrieved by RecordSet	*
     ********************************************************************/
    protected static	$defaultOrder	= 
        'Surname,GivenName, County, Township, Cemetery';
						
    /********************************************************************
     *	function Ocfa::__construct							            *
     *																	*
     *  Given defining parameters construct an instance.				*
     *																	*
     *  Input:															*
     *		$parms		associative array of parameters					*
     *					for example:									*
     *					    array('id'		=> $id)				        *
     *					or an array representing an already fetched		*
     *					record											*
     *																	*
     *  Returns object containing Ocfa record.				            *
     ********************************************************************/
    function __construct($parms)
    {
		global	$debug;
		global	$warn;
		global	$connection;
		global	$userid;

		$needInsert	= true;
		$errors		= '';

		if ($debug)
		    $warn	.= "<p>Ocfa::__construct(" .
							print_r($parms, true) . ")</p>\n";

		if (is_array($parms))
		{		                // associative array
		    if (count($parms) >= count(self::$initRow))
		    {			        // complete row from database
				$dbrow		        = $parms;
				$needInsert	        = false;
		    }			        // complete row from datase
		    else
		    {			        // key and optional other parms
                $id		            = 0;
                foreach($parms as $fldname => $value)
                {               // loop through parms
				    $fieldLc        = strtolower($fldname);
            		if (array_key_exists($fieldLc, self::$translate))
            		    $fieldLc	= self::$translate[$fieldLc];
            		switch($fieldLc)
                    {           // act on specific parameter
                        case 'recordindex':
                        {
                            $id     = $value;
                            break;
                        }       // prime key
                    }           // act on specific parameter
                }               // loop through parms

                if ($id > 0)
				{		        // initialize from database
				    // construct the query of the user table
				    $query	        = 'SELECT * FROM Ocfa ' .
							        	'WHERE RecordIndex=:id';
				    $sqlParms	    = array('id'	=> $id);
				    
				    // query the database
				    $stmt	        = $connection->prepare($query);
				    $queryText	    = debugPrepQuery($query, $sqlParms);
				    
				    if ($stmt->execute($sqlParms))
				    {		    // success
						if ($debug)
						    $warn	.= "<p>Ocfa::__construct: ".
							        	   "$queryText</p>";
						$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
						if (count($result) > 0)
						{		// found matching record
						    $dbrow		    = $result[0];
						    $needInsert		= false;
						}		// found matching record
						else
						{		// invalid key
						    $dbrow		    = self::$initRow;
						    $needInsert		= true;
						}		// invalid key
				    }		    // success
				    else
				    {		    // error performing query
						throw new Exception("Ocfa::__construct: " .
								    "query='$queryText', " .
							    print_r($stmt->errorInfo(),true));
				    }		    // error performing query
				}		        // initialize from database
		    }		            // key and optional other parms
		}		                // associative array
		else
		{		                // unexpected parameter type
		    throw new Exception('Ocfa::__construct: called with ' .
							 gettype($parms));
		}		                // unexpected parameter type

		// invoke constructor of base class
		parent::__construct($dbrow,
						    'Ocfa');
		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
		$this->dump('Ocfa Record constructed:');
    }		// Ocfa::__construct
}		// class Ocfa 
