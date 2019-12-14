<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Blog.inc															*
 *																		*
 *  Definition of a class representing a record in the Blogs table		*
 *  that represents a single blog entry.								*
 *																		*
 *	CREATE TABLE `Blogs` (                                              *
 *		BL_Index		SERIAL,			# auto_increment				*
 *		BL_DateTime		DATETIME,		# date and time of post			*
 *		BL_UserName		VARCHAR(64),	# user name of poster			*
 *		BL_Table		VARCHAR(16),	# table name if referenced		*
 *		BL_KeyName		VARCHAR(16),	# primary key name of table		*
 *		BL_KeyValue		INT(10),		# unique identifier of record	*
 *		BL_Text			TEXT,			# blog text						*
 *		BlogName		VARCHAR(255)	# subject or title line			*
 *		PRIMARY KEY (`BL_Index`),						                *
 *      UNIQUE KEY `BL_Index` (`BL_Index`),						        *
 *      KEY `BL_UserName` (`BL_UserName`),						        *
 *      KEY `BlogName` (`BlogName`) )						            *
 *      ENGINE=InnoDB DEFAULT CHARSET=utf8						        *
 *																		*
 *  History:															*
 *		2010/10/21		created											*
 *		2010/12/13		change table name to Blogs						*
 *		2011/08/12		add method getIndex								*
 *						throw exceptions for constructor errors			*
 *		2012/01/13		rename classes to have upper case character		*
 *		2013/08/09		base class renamed to Record					*
 *		2014/03/25		functionality extended to entirely encapsulate	*
 *						access to the table								*
 *		2014/09/23		do not enclose order parameter in back-quotes	*
 *						in static method getBlogs to permit				*
 *						more than one field								*
 *						Add support for limit and offset in parms to	*
 *						static method getBlogs							*
 *						return count and query in parms to getBlogs		*
 *						share creation of WHERE clause between getBlogs	*
 *						and deleteBlogs									*
 *		2014/11/29		put trace information into $warn				*
 *		2015/07/03		accept numeric string as parameter				*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2017/10/15		fix failure to construct for new record			*
 *		2017/11/15		move support for displaying blogs that are not	*
 *						rich-text into this method and return message	*
 *						from method get for invalid field name			*
 *		2017/12/18		add support for temporary fields				*
 *		2018/09/12		add BlogName field								*
 *		2019/01/06      move to namespace Genealogy                     *
 *		2019/04/18      support method getLastSqlCmd                    *
 *		2019/05/20      remove throw from constructor                   *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Blog															*
 *																		*
 *  Definition of a class implementing the interface to a record		*
 *  in the Blogs table.													*
 ************************************************************************/
class Blog extends Record
{

    /********************************************************************
     *	Blog::$initRow		static array containing default values for	*
     *						each field in a new record					*
     ********************************************************************/
    protected static	$initRow	= array(
						'bl_index'		    	=> 0,
						'bl_datetime'			=> '',
						'bl_username'			=> '',
						'bl_table'			    => 'tblIR',
						'bl_keyname'			=> 'idir',
						'bl_keyvalue'			=> 0,
						'bl_text'			    => '',
						'blogname'			    => null);

    /********************************************************************
     *	Blog::$info				    							        *
     *																	*
     *	Description of record class                                     *
     ********************************************************************/
    protected static	$info	= array(
                		'table'     		=> 'Blogs',
                		'name'      		=> 'Blogs',
                		'prime'	    		=> 'bl_index',
	            		'srvmg'     		=> true,
	            		'fldcount'			=> 4,
                		'order'     		=> 'BL_Index',
                		'classname' 		=> 'Blog');

    /********************************************************************
     *	Blog::$translate                                                *
     *																	*	
     *	static array containing alternate field names		            *
     ********************************************************************/
    protected static	$translate	= array(
						'id'				=> 'bl_index',
						'blid'				=> 'bl_index',
						'index'				=> 'bl_index',
						'datetime'			=> 'bl_datetime',
						'username'			=> 'bl_username',
						'table'				=> 'bl_table',
						'keyname'			=> 'bl_keyname',
						'keyvalue'			=> 'bl_keyvalue',
						'text'		        => 'bl_text',
						'title'		        => 'blogname',
						'subject'	        => 'blogname');

    /********************************************************************
     *	function Blog::__construct										*
     *																	*
     *  Given defining parameters construct an instance.				*
     *																	*
     *  Input:															*
     *		$parms		numeric key of existing record					*
     *					associative array of parameters					*
     *					for example:									*
     *					    array('table'		=> 'tblIR',				*
     *							  'keyvalue'		=> $idir,			*
     *							  'text'		=> $message)			*
     *					or an array representing an already fetched		*
     *					record											*
     *																	*
     *  Returns object containing Blog record.							*
     ********************************************************************/
    function __construct($parms)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;
		global	$userid;

		$needInsert	= true;

		// support old-style index parameter
		if (is_string($parms) && $parms == 'new')
		    $parms	= array('bl_index'	=> 0);
		else
		if ((is_int($parms) || ctype_digit($parms)) && $parms >= 0)
		    $parms	= array('bl_index'	=> $parms);

		if (is_array($parms))
		{		// existing record or search parms
		    $bl_index			= null;

		    foreach($parms as $name => $value)
		    {		// loop through all parameters
				$fieldLc	    = strtolower($name);
				if (array_key_exists($fieldLc, self::$translate))
				    $fieldLc	= self::$translate[$fieldLc];
				switch($fieldLc)
				{	// act on specific parameter
				    case 'bl_index':
				    {
						if (is_int($value) ||
						    (is_string($value) && ctype_digit($value)))
						    $bl_index	= $value;
				        else
						    $this->msg  .= "Blog::__construct: " .
				"invalid value " . print_r($value, true) . "for BL_Index";
						break;
				    }		// record index
				}		// loop through parameters
		    }		// loop through all parameters

		    // query the blogs table
		    if (!is_null($bl_index))
		    {			// bl_index specified
		        $query		        = "SELECT * FROM Blogs WHERE BL_Index=?";
		        $sqlParms		    = array($bl_index);
		        
		        // query the database
                $stmt		        = $connection->prepare($query);
                $queryText          = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
		        if ($bl_index == 0 || $stmt->execute($sqlParms))
		        {		// success
				    if ($debug)
				        $warn	.= "<p>Blog.inc: " . __LINE__ . 
							    " query='$queryText</p>\n";
				    if ($bl_index > 0)
				        $row	= $stmt->fetch(PDO::FETCH_ASSOC);
				    else
				        $row	= false;
				    if (is_array($row))
				    {		// found matching record
				        $dbrow		            = $row;
				        $needInsert		        = false;
				    }		// found matching record
				    else
				    {
				        $dbrow			        = self::$initRow;
				        $dbrow['bl_username']	= $userid;
				        $needInsert			    = true;
				    }
				    // invoke constructor of base class
				    parent::__construct($dbrow,
							        'Blogs');
		        }		// success
		        else
		        {		// error performing query
				    $this->msg  .= "Blog::__construct: " .
    						        "SQL error: query='$queryText', " .
    						        print_r($stmt->errorInfo(),true) . ". ";
		        }		// error performing query
		    }			// bl_index specified
		    else
		    {			// bl_index not specified
				$dbrow			        = self::$initRow;
				$dbrow['bl_username']	= $userid;
				$needInsert		        = true;
				// invoke constructor of base class
				parent::__construct($dbrow,
							        'Blogs');
		    }			//bl_index not specified

		    foreach($parms as $name => $value)
		    {		// loop through all parameters
				$fieldLc	    = strtolower($name);
				if (array_key_exists($fieldLc, self::$translate))
				    $fieldLc	= self::$translate[$fieldLc];
				switch($fieldLc)
				{
				    case 'bl_table':
                    {
                        $table              = $value;
                        if (array_key_exists($table, Record::$externalTableNames))
                        {                   // support external table names
                            $table          = Record::$externalTableName[$table];
                        }                   // support external table names
                        $info		        = Record::getInformation($table);
                        if ($info)
                        {
						    parent::set('bl_table', $table);
                            parent::set('bl_keyname', $info['prime']);
                        }
                        else
        				    $this->msg  .= "Blog::__construct: " .
            						       "unsupported BL_Table='$table'.";
						break;
				    }		// Table Name
				    
				    case 'bl_username':
				    case 'bl_keyname':
				    case 'bl_keyvalue':
				    case 'bl_text':
				    case 'blogname':
				    {
						parent::set($fieldLc, $value);
						break;
				    }		// Key Name
				    
				}	// act on specific parameter
		    }		// loop through all parameters
		}		// associative array
		else
		{		// unexpected parameter type
		    $this->msg  .= 'Blog::__construct: called with ' .
							    print_r($parms, true);
		}		// unexpected parameter type

		$this->needInsert	= $needInsert;

		// diagnostic output if debug is set
        $this->dump('Blog Record constructed:');

        $msg                .= $this->msg;
    }		// Blog::__construct

    /********************************************************************
     *	function Blog::get												*
     *																	*
     *  Get the value of a field in the record.							*
     ********************************************************************/
    function get($name)
    {
		$fieldLc	= strtolower($name);
		if (array_key_exists($fieldLc, self::$translate))
		    $fieldLc	= self::$translate[$fieldLc];
		switch($fieldLc)
		{
		    case 'bl_index':
		    case 'bl_datetime':
		    case 'bl_username':
		    case 'bl_keyname':
		    case 'bl_keyvalue':
		    {
				$retval		= $this->row[$fieldLc];
				if ($retval === null)
				    return '';
				else
				    return $retval;
		    }

		    case 'bl_table':
		    {
				$retval		= $this->row[$fieldLc];
				if ($retval === null)
				    return '';
                else
                if (array_key_exists($retval, Record::$internalTableNames))
                {               // report external table name
                    return Record::$internalTableNames[$retval];
                }               // report external table name
                else
				    return $retval;
		    }

		    case 'blogname':
		    {
				$retval			        = $this->row['blogname'];
				if ($retval === null || strlen($retval) == 0)
				{
				    $message		    = $this->getText();
				    if (strlen($message) > 0)
				    {
						$nlpos		    = strpos('\n', $message);
						if (is_int($nlpos))
						    $subject	= substr($message, 0, $nlpos);
						else
						    $subject	= $message;
						$subject	    = preg_replace('/<[^>]+>/', '',
										               $subject);
						return $subject;
				    }
				    else
						return '';
				}
				else
				{
				    $subject	= preg_replace('/<[^>]+>/', '',
									$retval);
				    return $subject;
				}
		    }

		    case 'bl_text':
				return $this->getText();

		    default:
		    {		// other fields
				return parent::get($fieldLc);
		    }		// other fields
		}		// act on specific fields
    }		// Blog::get

    /********************************************************************
     *	function Blog::getIndex											*
     *																	*
     *  Get the row number of the message								*
     ********************************************************************/
    function getIndex()
    {
		$retval	= $this->row['bl_index'];
		if ($retval === null)
		    return '';
		else
		    return $retval;
    }		// Blog::getIndex

    /********************************************************************
     *	function Blog::getTime											*
     *																	*
     *  Get the date and time the message was posted.					*
     ********************************************************************/
    function getTime()
    {
		$retval	= $this->row['bl_datetime'];
		if ($retval === null)
		    return '';
		else
		    return $retval;
    }		// Blog::getTime

    /********************************************************************
     *	function Blog::getUser											*
     *																	*
     *  Get the userid of the creator of the message.					*
     ********************************************************************/
    function getUser()
    {
		$retval	= $this->row['bl_username'];
		if ($retval === null)
		    return '';
		else
		    return $retval;
    }		// Blog::getUser

    /********************************************************************
     *	function Blog::getText											*
     *																	*
     *  Get the text of the message.									*
     ********************************************************************/
    function getText()
    {
		$retval	= $this->row['bl_text'];
		if ($retval === null || $retval == '')
		    return '';
		else
		{
		    if (strpos($retval, '<') === false)
                $retval		= "<p>" .
                              str_replace("\n","</p>\n<p>", $retval) . 
                              "</p>\n";
		    return $retval;
		}
    }		// Blog::getText

    /********************************************************************
     *	function Blog::save												*
     *																	*
     *  Save changes made to the Blog record into the					*
     *  database.														*
     *																	*
     *  Parameters:														*
     *	    $xml		if true diagnostic output is emitted in XML		*
     *					format.											*
     *					if a string use it as tag name of top tag		*
     *					of XML output									*
     ********************************************************************/
    function save($xml)
    {
		global	$debug;
		global	$warn;
		global	$userid;
		global	$connection;
		if ($debug)
		    $warn	.= "<p>Blog::save($xml)</p>\n";

		// get current date and time for date time field in record
		$now		= getdate();
		$currtime	= $now['year'] . '-' . 
						  str_pad($now['mon'], 2, '0', STR_PAD_LEFT) . '-' . 
						  str_pad($now['mday'], 2, '0', STR_PAD_LEFT) . ' ' . 
						  str_pad($now['hours'], 2, '0', STR_PAD_LEFT) . ':' . 
						  str_pad($now['minutes'], 2, '0', STR_PAD_LEFT) . ':' .
						  str_pad($now['seconds'], 2, '0', STR_PAD_LEFT);
		parent::set('bl_datetime', $currtime);
		parent::save($xml);
    }		// Blog::save

}		// class Blog 
