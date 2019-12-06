<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
use \ReflectionClass;
use \Templating\Template;
/************************************************************************
 *  RecordSet.inc														*
 *																		*
 *  Definition of a base class representing a set of records in a		*
 *  genealogical database.												*
 *																		*
 *  Examples:															*
 *		$list			= new RecordSet('Sources',						*
 *									    array('idst'		=> 14));	*
 *		$information	= $list->getInformation();						*
 *		print_r($information,true);										*
 *		print "Query='" . $information['query'] . "'";					*
 *		print "Size of list " . $list->count(); 						*
 *		foreach($list as $key => $record)								*
 *		{				// iterate over set								*
 *		    ...		parms												*
 *		}				// iterate over set								*
 *		$count		= $list->update(array('idst'	=> 15));			*
 *		$count		= $list->delete();									*
 *		$empty		= new RecordSet('Sources', array());// empty set	*
 *		$whole		= new RecordSet('Sources');		// whole table		*
 *		$conv		= new RecordSet('Sources',		// from array		*
 *						array(17 => new Source(array('idsr' => 17)),	*
 *						      18 => new Source(array('idsr' => 18)),	*
 *						      19 => new Source(array('idsr' => 19)));	*
 *		$conv[20]		= new Source(array('idsr' => 20));				*
 *		unset($conv[18]);												*
 *		$children		= new RecordSet('Children' .					*
 *				' INNER JOIN Persons ON Children.IDIR=Persons.IDIR',	*
 *						$parms		= array('idmr' => $idmr,			*
 *								'order'		=> 'Persons.BirthSD'));		*
 *																		*
 *  History:															*
 *		2017/10/13		created											*
 *		2017/10/30		support tables combined by JOIN					*
 *						support union of tables							*
 *						Note that although new RecordSet('tblHR')		*
 *						does not fail it should always be called with	*
 *						second parameter array('limit' => 1) because	*
 *						otherwise it just returns multiple copies of	*
 *						the single instance of LegacyHeader.			*
 *						Therefore using RecordSet to read 'tblHR' is	*
 *						pointless.  Use new LegacyHeader()				*
 *		2017/11/03		support conversion of array of records,			*
 *						including empty	array, to a RecordSet   		*
 *						support OR expression							*
 *						support exact match to empty string				*
 *		2017/11/18		add support for GROUP BY clause					*
 *						throw an exception if an attempt is made to		*
 *						load or delete an exceptionally large number	*
 *						of records as this indicates an error in the	*
 *						the parameters resulting in the request			*
 *						referring to the whole table when that was not	*
 *						the intention.									*
 *		2017/11/21		add support for ! not pattern					*
 *		2017/11/26		array of patterns combined by OR not AND		*
 *						error in construction of SELECT for JOINed		*
 *						tables											*
 *						support using REPLACE on new string values		*
 *		2017/12/05		support multiple fields in getDistinct each		*
 *						of which can be identified by a synonym			*
 *						support joined tables in getDistinct			*
 *		2017/12/10		enhance warning messages for invalid offset		*
 *						and limit values								*
 *						do not put `s around table-qualified fields		*
 *						replace exceptions for table size too large		*
 *						with setting error message and returning		*
 *						empty set										*
 *		2018/01/21		create only one instance of Census for a set	*
 *						of CensusLines									*
 *		2018/02/17		do not log error for empty RecordSet			*
 *		2018/11/11      emit detailed debug for constructor             *
 *		2019/03/07      permit limit parm to override maximum count     *
 *		2019/04/11      add test with bit-mask to getWhereInt and       *
 *		                getWhereCommon                                  *
 *		2019/04/15      add namespace to is_a                           *
 *		2019/04/28      to do: complete support for named substitutions *
 *		                in getWhereInt, getWhereCommon, and surname     *
 *		2019/05/17      only translate table names in GROUP BY and      *
 *		                ORDER BY                                        *
 *		2019/07/17      clean up debugging output from constructor      *
 *		2019/09/21      qualify class Template by namespace             *
 *		2019/11/21      information['query'] is no longer escaped       *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class RecordSet														*
 *																		*
 *  Definition of a base class implementing common behavior for a		*
 *  set of records within a genealogy database.							*
 ************************************************************************/

class RecordSet implements Iterator, Countable, ArrayAccess
{
    /********************************************************************
     *	$table															*
     *																	*
     *	Table name for Query from constructor							*
     ********************************************************************/
    protected	$table		    = null;

    /********************************************************************
     *	$template														*
     *																	*
     *	Instance of class Template for internationalization             *
     ********************************************************************/
    protected	$template		= null;

    /********************************************************************
     *	$externalName													*
     *																	*
     *	External Table name to present to casual users.					*
     ********************************************************************/
    protected	$externalName	= null;

    /********************************************************************
     *	$fields															*
     *																	*
     *	The expression of the set of fields to be selected				*
     ********************************************************************/
    protected	$fields		= '*';

    /********************************************************************
     *	$information													*
     *																	*
     *	An array of attributes:											*
     *	  'table'		SQL table name									*
     *	  'name'		external name of the table shown to users		*
     *    'prime'		The primary key name for the table.				*
     *					The key names are in lower case so they will	*
     *					match the field name in the query response.		*
     *	  'srvmg'		true if the setting of the primary key is		*
     *					managed by the database server for new records,	*
     *					and false if it is managed by the application.	*
     *	  'fldcount'	not currently used								*
     *	  'classname'	name of the class that implements the interface	*
     *					to this table									*
     *	  'order'		default order for operations on this table		*
     *	  'initrow'		default initial row.  This provides information	*
     *					on the fields in the table						*
     *	  'where'		WHERE clause defining this instance with the	*
     *					parameter values expanded						*
     *	  'query'		SQL SELECT to populate this instance with the	*
     *					parameter values expanded						*
     *	  'count'       number of records in the entire set             *
     ********************************************************************/
    protected	$information	= null;

    /********************************************************************
     *	$parms															*
     *																	*
     *	Selection parameters passed to the constructor					*
     ********************************************************************/
    protected	$parms		= null;

    /********************************************************************
     *	$offset															*
     *																	*
     *	starting offset within result set from parameters				*
     ********************************************************************/
    protected	$offset		= 0;

    /********************************************************************
     *	$limit															*
     *																	*
     *	record count limit from parameters								*
     ********************************************************************/
    protected	$limit		= PHP_INT_MAX;

    /********************************************************************
     *	$this->msg       												*
     *																	*
     *	Any error messages accumulated by constructor.                  *
     *	See Record::getErrors                                           *
     ********************************************************************/
    protected 	$msg	        = '';

    /********************************************************************
     *	$list															*
     *																	*
     *	Array of instances of class derived from Record					*
     *	This is only filled in when you start iterating over the set.	*
     ********************************************************************/
    protected	$list		= null;

    /********************************************************************
     *	$where															*
     *																	*
     *	SQL WHERE clause with prepared statement insertion points		*
     ********************************************************************/
    protected	$where		= '';

    /********************************************************************
     *	$orderby														*
     *																	*
     *	SQL ORDER BY clause												*
     ********************************************************************/
    protected	$orderby	= '';

    /********************************************************************
     *	$groupby														*
     *																	*
     *	SQL GROUP BY clause												*
     ********************************************************************/
    protected	$groupby	= '';

    /********************************************************************
     *	$query															*
     *																	*
     *	SQL SELECT with prepared statement insertion points				*
     ********************************************************************/
    protected	$query		= '';

    /********************************************************************
     *	$sqlParms														*
     *																	*
     *	Parameter array of field values to insert into a prepared		*
     *	statement.														*
     ********************************************************************/
    protected	$sqlParms	= array();

    /********************************************************************
     *	$pat															*
     *																	*
     *	pattern of supported comparison operators.  Each elementary		*
     *	value can be compared to the field using:						*
     *			<=														*
     *			<														*
     *			=														*
     *			!=		not equal										*
     *			<=>		null-safe equal									*
     *			<>		not equal										*
     *			>														*
     *			>=														*
     *			&       bit mask test                                   *
     *			:		range, only in 2nd element of array				*
     *			IS 		testing against boolean or null					*
     *			IS NOT	testing against boolean or null					*
     ********************************************************************/
    protected static $pat	=
				'/^(<=|<|=|!=|<=>|<>|>|>=|&|:|IS|IS NOT)?(.*)$/';

    /********************************************************************
     *	function __construct											*
     *																	*
     *	Create an instance of the class RecordSet		 				*
     *																	*
     *	Parameters:														*
     *	    $tableName			the name of the SQL table to which this	*
     *							object applies.  This may be a synonym.	*
     *							It may also be a comma-separated list	*
     *							of SQL table names, or a table name		*
     *							with a JOIN clause in which the table	*
     *							names may be either real or synonyms	*
     *	    $parameters			associative array of fieldname, value	*
     *							pairs to define the subset of the		*
     *							records in the SQL table that are		*
     *							represented by this instance.			*
     *							The parameters 'offset', 'limit', 		*
     *							'groupby', and 'order' are reserved		*
     *							to set the OFFSET, LIMIT, GROUP BY, and	*
     *							and ORDER BY clauses.					*
     *							Use '`offset`',	'`limit`', and			*
     *							'`order`' to reference fields with		*
     *							those names. The default value of this	*
     *							parameter is null in which case the		*
     *							instance references the entire contents	*
     *							of the table.							*
     *							If the fieldname is an integer, usually	*
     *							as a result of being omitted, and the	*
     *							value is an associative array as		*
     *							described above, then the comparisons	*
     *			                specified are combined by OR.           *
     *							or										*
     *							an array containing records from the	*
     *							table identified by the first parameter	*
     *							if this array is empty, then the result	*
     *							is a set which contains no records		*
     *	    $fields				This parameter is only referenced if	*
     *							$tableName is a JOIN expession or a		*
     *							comma-separated list of table names.	*
     *							For a JOIN this restricts the response	*
     *							in place of the default which is to		*
     *							return all of the fields in all the		*
     *							tables.									*
     *							If all of the tables in a list of		*
     *							tables do not have exactly the			*
     *							same set of fields, by name and type,	*
     *							then this parameter is a comma-separated*
     *							list of field names that are valid for	*
     *							all of the tables.						*
     *																	*
     *	Throws:															*
     *	    An exception for invalid parameters.						*
     ********************************************************************/
    function __construct($tableName,
						 $parameters	= null,
						 $fields	    = '*')
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

        if ($debug)
        {
            $parmsText                           = "<p>RecordSet:__construct('$tableName', ";
            if (is_null($parameters))
                $parmsText                       .= "null";
            else
            if (is_array($parameters))
            {
                $parmsText                       .= "array(";
                $comma                      = '';
                foreach($parameters as $field => $value)
                {
                    $parmsText                   .= $comma . "`$field`=";
                    if (is_numeric($value))
                        $parmsText               .= $value;
                    else
                    if (is_null($value))
                        $parmsText               .= "null";
                    else
                    if (is_bool($value))
                    {
                        if ($value)
                            $parmsText           .= "true";
                        else
                            $parmsText           .= "false";
                    }
                    else
                    if (is_string($value))
                        $parmsText               .= htmlspecialchars("'$value'");
                    else
                    if ($value instanceof Record)
                        $parmsText               .= "class " . get_class($value) . 
                                                ' ' .  $value->getName();
                    else
                    if ($value instanceof Template)
                        $parmsText               .= "class Template";
                    else
                    if (is_string($value))
                    {
                        $parmsText               .= "'$value'";
                    }
                    else
                    {
                        $parmsText               .= print_r($value, true);
                    }
                    $comma                  = ',';
                }                   // loop through elements of parm 2

                $parmsText                       .= ")";
            }
            if ($fields != '*')
                $parmsText                       .= ",'$fields'";
            $parmsText                           .= ")</p>\n";

            $trace                          = debug_backtrace();
            foreach($trace as $call)
            {
                if (array_key_exists('class', $call))
                    $classRef               = $call['class'] . '::';
                else
                    $classRef               = '';
                $parmsText   .= "<p>RecordSet::__construct: " . __LINE__ .
                            
                            " function=" . $classRef . $call['function'] .
                            " called by line=" . $call['line'] . 
                            " in " . $call['file'] . "</p>\n";
            }
            $warn           .= $parmsText;
        }

		// validate parameters
		$orderby		    		        = '';
		$groupby		    		        = '';
		$tables			    		        = array();
        $joined			    		        = false;
        $this->limit        		        = PHP_INT_MAX;

		// interpret table name
		if (is_string($tableName))
		{
		    $tableName		                = trim($tableName);
		    if (preg_match('/^(\w+)\s*(JOIN|INNER JOIN|LEFT JOIN|FULL JOIN|RIGHT JOIN|OUTER JOIN)(.*)$/', $tableName))
		    {			// support joined tables
				// translate table names in the expression
				$words		                = preg_split('/\b/', $tableName);
				$tableName	                = $words[1];
				$trans		                = '';
                $aftjoin                    = false;
				foreach($words as $word)
                {
                    if (strlen($word) > 0)
                    {
                        if (strtoupper($word) == 'JOIN')
                            $aftjoin        = true;
                        if ($aftjoin)
                        {
					        $info	            = Record::getInformation($word);
					        if ($info)
							    $trans	        .= $info['table'];
					        else
                                $trans	        .= $word;
                            $aftjoin            = false;
                        }
                        else
                            $trans	            .= $word;
				    }
				}
				$this->table	            = $trans;	// translated

				// construct the SELECT expression
				if ($fields == '*')
				{
                    $joinedtables	        = preg_split('/JOIN/i', $trans);
				    $fields		            = '';
                    $comma		            = '';
				    foreach($joinedtables as $join)
				    {
						$join		        = trim($join);
						$result		        = preg_match('/^(\w+)(\s*AS\s+([^\s]+))?/i', $join, $matches);
						if ($result)
						{
						    if (count($matches) >= 4)
								$fields	    .= $comma . $matches[3] . '.*';
						    else
								$fields	    .= $comma . $matches[1] . '.*';
						    $comma	= ',';
						}
						else
						    $warn	.= "<p>RecordSet::__construct: " .
									   __LINE__ . " \$join='$join'</p>\n";
				    }
				}
				$joined		                = true;
				$tables		                = array();		// not a union
		    }			// support joined tables
		    else
		    {			// check for UNION of tables list
				$tables		                = explode(',',$tableName);
				if (count($tables) > 1)
				    $tableName	            = trim($tables[0]);
		    }			// check for UNION of tables list

		    // get information about the table being searched
            $information	    	        = Record::getInformation($tableName);
            $this->information		        = $information;
		    if ($information && array_key_exists('table', $information))
		    {			// supported
				if (!$joined)
				    $this->table	        = $information['table'];// SQL
				$this->externalName	        = $information['name'];	// external
				$orderby		            = $information['order'];
				$className		            = $information['classname'];
                $nsclass                    = __NAMESPACE__ . "\\" . $className;
				// load the class if necessary
				if (!class_exists(__NAMESPACE__ . "\\" . $className))
				    include __NAMESPACE__ . "/" . $className . ".inc";
		    }
		    else
            {
                $reflection                 = new ReflectionClass($tableName);
                if ($reflection->isSubclassOf('Record'))
                    $this->msg		        .= "Unsupported table '$tableName'. ";
                else
                    $this->msg		        .= "class '$tableName' is not an extension of 'Record'. ";
		    }
		}			// first parameter is string
		else
		    $this->msg			            .= "Table name must be a string. ";

		// if the second parameter is NULL this object represents
		// all of the records in the table
		if (!is_null($parameters) && !is_array($parameters))
		    $this->msg	.= "Second parameter must be null or an array, " .
						   print_r($parameters, true) . '. ';

		// check for an array of records from the specified table
		if (is_null($parameters))
		    $parameters		= array();
		else
		{			    // not null, check for array of records
		    $recordArray		            = true;
		    // note that $recordArray remains true if $parms is an empty array
		    // this is deliberate to be able to create an empty set of records
		    foreach($parameters as $index => $row)
		    {			// loop through parameters
				if (!is_a($row, __NAMESPACE__ . "\\" . $information['classname']))
				{
				    $recordArray		    = false;
				    break;
				}
		    }			// loop through parameters

		    if ($recordArray)
		    {			// have array of records
				$this->parms			    = $parameters;
				$this->sqlParms			    = array();
				$this->where			    = '';
				$this->information['query']	= '';
				$this->information['count']	= count($parameters);
				$this->list			        = $parameters;
				return;
		    }			// have array of records
		}			    // not null, check for array of records

		// 3rd parameter is explicit list of fields
		if ($fields != '*')
		    $this->fields	= $fields;

		// initialize object from parameters
		$or			        = '';		// operator
		$deleteNames		= array();	// list of special keys
		foreach($parameters as $field => $value)
		{
		    switch(strtolower($field))
		    {			// act on specific parameters
				case 'limit':
				{
				    $deleteNames[]	= $field;
				    if (is_int($value) || ctype_digit($value))
						$this->limit		= intval($value);
				    else
						$warn	.= "<p>RecordSet::__construct: " .
						"unexpected limit value '$value' ignored</p>\n";
				    break;
				}

				case 'offset':
				{
				    $deleteNames[]	= $field;
				    if (is_int($value) || ctype_digit($value))
						$this->offset		= intval($value);
				    else
						$warn	.= "<p>RecordSet::__construct: " .
						"unexpected offset value '$value' ignored</p>\n";
				    break;
				}

				case 'order':
				case 'orderby':
				{
				    $deleteNames[]	= $field;
				    if (is_string($value))
				    {
						if (strlen($value) > 0)
						    $orderby	= $value;	// override default
				    }
				    else
				    {
						$warn	.= "<p>RecordSet::__construct: " .
						   "unexpected order value " . print_r($value,true) .
								   " ignored</p>\n";
						ob_start();
						debug_print_backtrace();
						$warn	.= ob_get_clean();
				    }
				    break;
				}

				case 'groupby':
				{
				    $deleteNames[]	= $field;
				    if (is_string($value))
				    {
						if (strlen($value) > 0)
						    $groupby		= $value;
				    }
				    break;
				}

                case 'template':
                {
				    $deleteNames[]	= $field;
                    if ($value instanceof Template)
                        $this->template         = $value;
                    break;
                }

				case 'debug':
				case 'join':
				{
				    $deleteNames[]	= $field;
				    break;
				}
		    }			// act on specific parameters
		}

		// only process these special parameters here, not in
		// RecordSet::getWhere or the getWhere methods of derived classes
		foreach($deleteNames as $name)
		    unset($parameters[$name]);

		$this->parms			= $parameters;
		$this->orderby			= $orderby;
		$this->sqlParms			= array();
		$where			   		= $this->getWhere();	// class specific
		$this->where			= $where;
		$orderby		   		= $this->orderby;	    // getWhere updates

		// construct a query for the specific matches
		$table			   		= $this->table;
		if (count($tables) > 1)
		{			// multiple merged tables
		    $union		   		= '';
		    $sqlParms			= array();
		    $countQuery			= 'SELECT ';
		    $plus		   		= '';
		    $query		   		= '';
		    foreach($tables as $table)
		    {
				if (substr($table, 0, 6) == 'Census')
				{
				    $censusYear	= substr($table,6);
				    $tblid	    = "'CA$censusYear' as CensusId,";
				    if ($censusYear < 1867)
				    {
						$join	= "JOIN Districts ON (D_Census=CONCAT(`Province`,'$censusYear') AND D_Id=District) JOIN SubDistricts ON (SD_Census=CONCAT(`Province`,'$censusYear') AND SD_DistId=District AND SD_Id=SubDistrict AND SD_Div=Division) ";
						$fields	= $this->fields;
				    }
				    else
				    {
						$join	= "JOIN Districts ON (D_Census='CA$censusYear' AND D_Id=District) JOIN SubDistricts ON (SD_Census='CA$censusYear' AND SD_DistId=District AND SD_Id=SubDistrict AND SD_Div=Division) ";
						$fields	= str_replace('Province', 
									      'D_Province as Province',
									      $this->fields);
				    }
				}
				$query		    .= $union . 'SELECT ' .
								  $tblid . $fields .
								  " FROM $table " . $join . $where;
				$countQuery	    .= $plus .
						"COALESCE((SELECT COUNT(*) FROM $table $where),0)";
				$union		    = " UNION ALL ";
				$plus		    = " + ";
				$sqlParms	    = array_merge($sqlParms, $this->sqlParms);
		    }
		    $this->query	    = $query;
		    $this->sqlParms	    = $sqlParms;
		}			// multiple merged tables
		else
		{
		    $this->query	    = 'SELECT ' . $this->fields .
									' FROM ' . $this->table . ' ' .
									$where;
		    $countQuery		    = "SELECT COUNT(*) FROM " . $this->table . 
								   ' ' . $where;
		}

		// construct the GROUP BY clause
		if (is_string($groupby) && strlen($groupby) > 0)
		{
            $fields		    = explode(',', $groupby);
		    $groupby		= '';
            $and            = '';
            foreach($fields as $i => $field)
            {
                $rresult    = preg_match('/^`?(\w+)`?\.(.*)/',
                                         $field,
                                         $matches);
                if ($rresult)
                {
                    $table  = $matches[1];
                    $column = $matches[2];
	                if (strlen($word) > 0)
	                {
						$info		    = Record::getInformation($table);
						if ($info)
						    $table      = $info['table'];
	                }
                    $groupby	        .= "$and`$table`.$column";
                }
                else
                    $groupby	        .= "$and$field";
                $and                    = ',';
		    }			// translate external table names
		    $this->query	.= " GROUP BY " . $groupby; 
		}

		// construct the ORDER BY clause
		if (is_string($orderby) && strlen($orderby) > 0)
        {
            $fields		    = explode(',', $orderby);
            $orderby		= '';
            $and            = '';
            foreach($fields as $i => $field)
            {
                $rresult    = preg_match('/^`?(\w+)`?\.(.*)/',
                                         $field,
                                         $matches);
                if ($rresult)
                {
                    $table  = $matches[1];
                    $column = $matches[2];
	                if (strlen($word) > 0)
	                {
						$info		    = Record::getInformation($table);
						if ($info)
						    $table      = $info['table'];
	                }
                    $orderby	        .= "$and`$table`.$column";
                }
                else
                    $orderby	        .= "$and$field";
                $and                    = ',';
		    }			// translate external table names
		    $this->query	.= " ORDER BY " . $orderby; 
		}

		// add the LIMIT clause
		if ($this->limit < PHP_INT_MAX)
		   $this->query		.= " LIMIT " . $this->limit;

		// add the OFFSET clause
		if ($this->offset > 0)
		   $this->query		.= " OFFSET " . $this->offset;

		// external representation of query for messages
		$this->information['query']	= debugPrepQuery($this->query,
                                                     $this->sqlParms,
                                                     false);
		// query the database for count
		if (!isset($this->information['count']))
		{			// set count to actual number of records
		    $stmt		= $connection->prepare($countQuery);
		    $queryText	= debugPrepQuery($countQuery, $this->sqlParms);

		    if ($stmt->execute($this->sqlParms))
		    {			// success
				if ($debug)
				{
				    $warn	.= "<p>RecordSet.inc: " . __LINE__ .
								   " query=\"$queryText\"</p>\n";
				}

				// return count in parameter object
				$row 			= $stmt->fetch(PDO::FETCH_NUM);
                $this->information['count']	= $row[0];
		    }			// success
		    else
		    {			// error performing query
				$this->msg  .= "RecordSet::__construct: \"$queryText\", " .
								    print_r($stmt->errorInfo(),true);
		    }			// error performing query
        }			// set count to actual number of records
        if (strlen($this->msg) > 0)
            $msg            .= $this->msg;
    }		// RecordSet::__construct

    /********************************************************************
     *	function RecordSet::setTemplate									*
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
    public function setTemplate($newvalue)
    {
        $oldvalue                   = $this->template;
        if ($newvalue instanceof Template)
            $this->template         = $newvalue;

        // normally $recordSet->setTemplate is called before
        // $recordSet->rewind, so $recordSet->list should be null 
        if (is_array($this->list))
            foreach($list as $record)
                $record->setTemplate($newvalue);

        return $oldvalue;
    }           // function RecordSet::seTemplate

    /********************************************************************
     *  function RecordSet::getInformation								*
     *																	*
     *  Get information about the table associated with this instance.	*
     *																	*
     *	Returns:														*
     *	    An array of attributes:										*
     *	'table'			SQL table name									*
     *	'name'			external name of the table shown to users		*
     *  'prime'			The primary key name for the table.				*
     *					The key names are in lower case so they will	*
     *					match the field name in the query response.		*
     *	'srvmg'			true if the setting of the primary key is		*
     *					managed by the database server for new records,	*
     *					and false if it is managed by the application.	*
     *	'fldcount'		not currently used								*
     *	'classname'		name of the class that implements the interface	*
     *					to this table									*
     *	'order'			default order for operations on this table		*
     *	'initrow'		default initial row.  This provides information	*
     *					on the fields in the table						*
     *	'query'			SQL SELECT to populate this instance with the	*
     *					parameter values expanded						*
     *					This string is XML and HTML safe				*
     *	'count'			number of records in the selection				*
     ********************************************************************/
    public function getInformation()
    {
		return $this->information;
    }		//  function RecordSet::getInformation

    /********************************************************************
     *  function RecordSet::getWhereCommon								*
     *																	*
     *  Construct the SQL expression to compare a field. This is		*
     *	common functionality used by the constructor in derived			*
     *	classes.														*
     *	By default this is called when the constructor is unable to		*
     *	determine whether the field is either an integer or a string	*
     *																	*
     *	Input:															*
     *	    $fldname	the SQL field name.  Note that this must be		*
     *					a valid field name, not a synonym.				*
     *	    $value		the value, which may be an elementary value,	*
     *					a string consisting of a comparison operator	*
     *					followed by a positive integer,					*
     *					or an instance of Record, or					*
     *					a simple array of choices each member of which	*
     *			        may be an					                    * 
     *					elementary value or a string consisting of		*
     *					a comparison operator followed by a positive	*
     *					integer.										*
     *		$refname    if omitted using positional insertions in       *
     *		            sqlParms                                        *
     *		            if a string use it for named insertions         *
     *		            if boolean true use the field name as the       *
     *		            name for named insertions                       *
     *																	*
     *	Examples:														*
     *			getWhereCommon('idir',1234)								*
     *			getWhereCommon('name',array('1stchoice','2ndchoice')	*
     *			getWhereCommon('age',array('>=21','<65')				*
     *																	*
     *	Returns:														*
     *	    String containing an SQL expression							*
     *																	*
     *	Side-Effects:													*
     *	    Issues with the supplied value are reported by adding		*
     *	    warning messages onto the global variable $warn.			*
     *	    The array $sqlParms is updated to include new substitution	*
     *	    values corresponding to insertion points in the returned	*
     *	    expression string.											*
     ********************************************************************/
    protected function getWhereCommon($fldname,
								      $value,
                                      $refname = null)
    {
		global	$debug;
		global	$warn;

		if (substr($fldname, 0, 1) != '`' &&
            strpos($fldname, '.') === false)
        {
            if ($refname === true)
                $refname    = strtolower($fldname);
            $fldname		= "`$fldname`";
        }
        else
        {
            if ($refname === true)
                $refname    = strtolower(substr($fldname,1,strlen($fldname)-1));
        }

		$matches		= array();
		$where			= '';
		if (is_array($value))
		{		// list of values
		    $values		= $value;
		    if (count($values) == 0)
				return '';
		    $oldwhere		= $where;	// prepare for ':' range
		    $or			    = '';
		    for($i = 0; $i < count($values); $i++)
		    {
				$value			= trim($values[$i]);
				if (is_int($value))
				{			// integer
                    if (is_string($refname))
                    {
                        $where			    .= $or . "$fldname=:$refname$i";
                        $this->sqlParms["$refname$i"]   = $val;
                        $i++;
                    }
                    else
                    {
                        $where			    .= $or . "$fldname=?";
                        $this->sqlParms[]   = $val;
                    }
				    $or			= ' OR ';
				}			// integer
				else
				if (is_string($value))
				{			// elementary value
				    $matches		= array();
				    if (preg_match(self::$pat,
								   $value,
								   $matches))
				    {			// optional operator and constant
						$operator	= $matches[1];
						$value		= trim($matches[2]);
						if (strlen($value) >= 2)
						{		// check for quoted string
						    if (substr($value,0,1) == "'" &&
								substr($value,-1) == "'")
								$value	= substr($value, 1, strlen($value)-2);
						    else
						    if (substr($value,0,1) == '"' &&
								substr($value,-1) == '"')
								$value	= substr($value, 1, strlen($value)-2);
						    $value		= stripcslashes($value);
						}		// check for quoted string

						if ($operator == ':')
						{		// range operator
						    if ($i == 1 && count($values) == 2)
						    {		// only permitted on second element
								// change original comparison for first element
                                // and add comparison for second element
                                if (is_string($refname))
                                {
                                    $where			    .= $oldwhere . "$fldname=:$refname$i";
                                    $this->sqlParms["$refname$i"]   = $val;
                                }
                                else
                                {
								    $where		= $oldwhere .
								    		      "$fldname>=? AND " .
								    		      "$fldname<=?";
								    $this->sqlParms[]	= $value;
                                }
						    }		// only permitted on second element
						    else
						    {		// ignore with warning
								$warn	.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value " .
										"'$value', count(\$values)=" . 
										count($values) . "</p>\n";
						    }		// ignore with warning
						}		// range operator
						else
						if ($operator == '&')
                        {		// bit-mask operator
						    $where		.= $or . "($fldname&?)!= 0";
                        }
						else
						{		// comparison operator
						    if ($operator == '')
								$operator	= '=';

						    $where		.= $or . "$fldname$operator?";
						    $this->sqlParms[]	= $value;
						    if ($operator == '=')
						    {
								$or		= ' OR ';
						    }
						    else
								$or		= ' AND ';
						}		// comparison operator
				    }			// optional operator and integer
				    else
				    {			// invalid value
						$warn		.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value " .
										"'$value'</p>\n";
				    }			// invalid value
				}			// elementary value
				else
				{			// invalid non-elementary
				    $where		.= "$fldname=?";
				    $this->sqlParms[]	= $value;
				    $warn		.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value " .
										"'$value'</p>\n";
				}			// invalid non-elementary value
		    }				// loop through values
		    if (count($values) > 1)
				$where		= "($where)";
		}			// list of values
		else
		if ($value instanceof Record)
		{			// record
		    $value		= $value->getId();
		    $where		.= "$fldname=?";
		    $this->sqlParms[]	= $value;
		}			// record
		else
		if (preg_match(self::$pat,
				       $value,
				       $matches))
		{			// optional operator and value
		    $operator		= $matches[1];
		    $value		    = $matches[2];
		    if (strlen($value) >= 2)
		    {		// check for quoted string
				if (substr($value,0,1) == "'" &&
				    substr($value,-1) == "'")
				    $value	= substr($value, 1, strlen($value)-2);
				else
				if (substr($value,0,1) == '"' &&
				    substr($value,-1) == '"')
				    $value	= substr($value, 1, strlen($value)-2);
				$value		= stripcslashes($value);
		    }		// check for quoted string
		    if ($operator == '')
				$where		.= "$fldname=?";
			else
			if ($operator == '&')
            {		// bit-mask operator
                $where		.= "($fldname&?)!= 0";
            }
		    else
		    if ($operator == ':')
		    {
				$where		.= "$fldname=?";
				$warn	.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname value ':$value'," .
								"count(\$values)=1</p>\n";
		    }
		    else
				$where		.= "$fldname$operator?";
		    $this->sqlParms[]	= $value;
		}			// optional operator and integer
		else
		{
		    $where		.= "$fldname=?";
		    $this->sqlParms[]	= $value;
		}
		return $where;
    }		// function RecordSet::getWhereCommon,

    /********************************************************************
     *  function RecordSet::getWhereInt									*
     *																	*
     *  Construct the SQL expression to compare a field whose value		*
     *	is a positive integer for a particular parameter.  This is		*
     *	common functionality used by the constructor in derived			*
     *	classes.  This differs from method getWhereCommon only in		*
     *	that it issues warnings for non-integral values					*
     *	By default this is called when the constructor is able to		*
     *	determine that the field is an integer.							*
     *																	*
     *	Input:															*
     *	    $fldname	the SQL field name.  Note that this must be		*
     *					a valid field name, not a synonym.				*
     *	    $value		the value, which may be an integer, or			*
     *					a string optionally starting with an operator,	*
     *					or an instance of Record, or					*
     *					an array each member of	which may be an integer	*
     *					or a string optionally starting with an operator*
     *		$refname    if omitted using positional insertions in       *
     *		            sqlParms                                        *
     *		            if a string use it for named insertions         *
     *		            if boolean true use the field name as the       *
     *		            name for named insertions                       *
     *																	*
     *	Examples:														*
     *			getWhereInt('idir',1234)								*
     *			getWhereInt('status',array(1,4,5,7,9))					*
     *			getWhereInt('age',array('>=21','<=65'))					*
     *			getWhereInt('age',array(21,':65'))						*
     *																	*
     *	Returns:														*
     *	    String containing an SQL expression							*
     *																	*
     *	Side-Effects:													*
     *	    Issues with the supplied value are reported by adding		*
     *	    warning messages onto the global variable $warn.			*
     *	    The array $sqlParms is updated to include new substitution	*
     *	    values corresponding to insertion points in the returned	*
     *	    expression string.											*
     ********************************************************************/
    protected function getWhereInt($fldname,
								   $value,
                                   $refname = null)
    {
		global	$debug;
		global	$warn;

		if (substr($fldname, 0, 1) != '`' &&
            strpos($fldname, '.') === false)
        {
            if ($refname === true)
                $refname    = strtolower($fldname);
            $fldname		= "`$fldname`";
        }
        else
        {
            if ($refname === true)
                $refname    = strtolower(substr($fldname,1,strlen($fldname)-1));
        }

		$matches		= array();
		$where			= '';
		if (is_array($value))
		{		// list of values
		    $values		= $value;
		    if (count($values) == 0)
				return '';
		    $oldwhere		= $where;	// prepare for ':' range
		    $or			    = '';
		    for($i = 0; $i < count($values); $i++)
		    {
				$value			= trim($values[$i]);
				if (is_int($value))
				{			// integer
                    if (is_string($refname))
                    {
                        $where			    .= $or . "$fldname=:$refname$i";
                        $this->sqlParms["$refname$i"]   = $val;
                    }
                    else
                    {
                        $where			    .= $or . "$fldname=?";
                        $this->sqlParms[]   = $val;
                    }
				    $or			= ' OR ';
				}			// integer
				else
				if (is_string($value))
				{			// elementary value
				    $matches		= array();
				    if (preg_match(self::$pat,
								   $value,
								   $matches))
				    {			// optional operator and integer
						$operator	= $matches[1];
						$value		= $matches[2];
						if (preg_match('/-?\d+/', $value) == 0)
						    $warn	.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value " .
										"'$value'</p>\n";

						if ($operator == ':')
						{		// range operator
						    if ($i == 1 && count($values) == 2)
						    {		// only permitted on second element
								// change original comparison for first element
                                // and add comparison for second element
                                if (is_string($refname))
                                {
								    $where		= $oldwhere .
									    	     "$fldname>=:{$refname}0 AND " .
									    	     "$fldname<=:{$refname}1";
                                    $this->sqlParms[$refname . '1']	= $value;
                                }
                                else
                                {
								    $where		= $oldwhere .
									    	      "$fldname>=? AND " .
									    	      "$fldname<=?";
                                    $this->sqlParms[]	= $value;
                                }
						    }		// only permitted on second element
						    else
						    {		// ignore with warning
								$warn	.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value " .
										"'$value', count(\$values)=" . 
										count($values) . "</p>\n";
						    }		// ignore with warning
						}		// range operator
            			else
			            if ($operator == '&')
                        {		// bit-mask operator
                            if (is_string($refname))
                            {
                                $where		.= $or . "($fldname&:$refname)!=0";
                                $this->sqlParms[$refname]   = $val;
                            }
                            else
                            {
                                $where		.= "$or($fldname&?)!= 0";
                                $this->sqlParms[]   = $val;
                            }
                        }
						else
						{		// comparison operator
						    if ($operator == '')
								$operator	= '=';

                            if (is_string($refname))
                            {
						        $where		.= $or . "$fldname$operator:$refname$i";
						        $this->sqlParms[$refname . $i]	= $value;
                            }
                            else
                            {
						        $where		.= $or . "$fldname$operator?";
						        $this->sqlParms[]	= $value;
                            }
						    if ($operator == '=')
								$or		= ' OR ';
						    else
								$or		= ' AND ';
						}		// comparison operator
				    }			// optional operator and integer
				    else
				    {			// invalid value
						$warn		.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value " .
										"'$value'</p>\n";
				    }			// invalid value
				}			// elementary value
				else
				{			// invalid non-integer value
				    $warn		.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname"."[$i] value ". 
									     print_r($value, true) . "</p>\n";
				    $where		.= "$fldname=?";
				    $this->sqlParms[]		= $value;
				}			// invalid non-integer value
		    }				// loop through values
		    if (count($values) > 1)
				$where		= "($where)";
		}			// list of values
		else
		if ($value instanceof Record)
		{			// record
		    $value		= $value->getId();
		    $where		.= "$fldname=?";
		    $this->sqlParms[]	= $value;
		}			// record
		else
		if (preg_match(self::$pat,
				       $value,
				       $matches))
		{			// optional operator and integer
		    $operator		= $matches[1];
		    $value		    = $matches[2];
            if ($operator == '')
            {               // equality
                if (is_string($refname))
                {
                    $where			    .= "$fldname=:$refname";
                    $this->sqlParms[$refname]   = $value;
                }
                else
                {
                    $where			    .= "$fldname=?";
                    $this->sqlParms[]   = $value;
                }
            }               // equality
   			else
            if ($operator == '&')
            {		// bit-mask operator
                if (is_string($refname))
                {
                    $where			    .= "($fldname&:$refname)!=0";
                    $this->sqlParms[$refname]   = $value;
                }
                else
                {
                    $where			    .= "($fldname&?)!= 0";
                    $this->sqlParms[]   = $value;
                }
            }
		    else
		    if ($operator == ':')
		    {               // range
                if (is_string($refname))
                {
                    $where			    .= "$fldname=:$refname";
                    $this->sqlParms[$refname]   = $value;
                }
                else
                {
                    $where			    .= "$fldname=?";
                    $this->sqlParms[]   = $value;
                }
				$warn	.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname value ':$value'," .
								"count(\$values)=1</p>\n";
		    }               // range
            else
            {               // comparison operator
                if (is_string($refname))
                {
                    $where			    .= "$fldname$operator:$refname";
                    $this->sqlParms[$refname]   = $value;
                }
                else
                {
                    $where			    .= "$fldname$operator?";
                    $this->sqlParms[]   = $value;
                }
            }               // comparison operator
		}			// optional operator and integer
		else
		{
		    $warn		.= "<p>RecordSet: " . __LINE__ . " Invalid $fldname value " .
								       print_r($value,true) . "</p>\n";
		    $where		.= "$fldname=?";
		    $this->sqlParms[]		= $value;
		}
		return $where;
    }		// function RecordSet::getWhereInt,

    /********************************************************************
     *  function RecordSet::getWherePattern								*
     *																	*
     *  Construct the SQL expression to compare a field whose value		*
     *	is a string for a regular expression pattern match.  This is	*
     *	common functionality used by the constructor in derived 		*
     *	classes.														*
     *	For efficiency and increased portability (since not all SQL		*
     *	servers support regular expressions) the most common regular	*
     *	expression syntaces are simulated using standard functions.		*
     *	By default this is called when the constructor is able to		*
     *	determine that the field is a string.							*
     *																	*
     *	Input:															*
     *	    $fldname	the SQL field name.  Note that this must be		*
     *					a valid field name, not a synonym.				*
     *	    $pattern	the value, which is a string containing a		*
     *					regular expression or an array of strings		*
     *					containing regular expressions					*
     *		$refname    if omitted using positional insertions in       *
     *		            sqlParms                                        *
     *		            if a string use it for named insertions         *
     *		            if boolean true use the field name as the       *
     *		            name for named insertions                       *
     *																	*
     *	Examples:														*
     *			getWherePattern('address',"^full text match$")			*
     *			getWherePattern('address',"^match front of value")		*
     *			getWherePattern('address',"match back of value$")		*
     *			getWherePattern('address',"\d+")		match integer	*
     *					for more portable pattern matching use			*
     *			getWherePattern('address',"LIKE '%sex%'")				*
     *																	*
     *	Returns:														*
     *	    String containing an SQL expression							*
     *																	*
     *	Side-Effects:													*
     *	    Issues with the supplied value are reported by adding		*
     *	    warning messages onto the global variable $warn.			*
     *	    The array $this->sqlParms is updated to include new	        *
     *	    substitution values corresponding to insertion points       *
     *	    in the returned expression string.							*
     ********************************************************************/
    protected function getWherePattern($fldname,
                                       $pattern,
                                       $refname = null)
    {
		global	$debug;
		global	$warn;

		if (substr($fldname, 0, 1) != '`' &&
            strpos($fldname, '.') === false)
        {
            if ($refname === true)
                $refname    = strtolower($fldname);
            $fldname		= "`$fldname`";
        }
        else
        {
            if ($refname === true)
                $refname    = strtolower(substr($fldname,1,strlen($fldname)-1));
        }

		$matches		    = array();
		$where		    	= '';
		if (is_array($pattern))
		    $parms	    	= $pattern;
        else
        if ($pattern instanceof County ||
            $pattern instanceof Township)
            $parms          = array('^' . $pattern->get('code') . '$');
        else
        if ($pattern instanceof Record)
            $parms          = array('^' . $pattern->get('name') . '$');
        else
		if (is_string($pattern))
		    $parms		    = array($pattern);
		else
		{
		    $warn	        .= "<p>RecordSet::getWherePattern: " .
						        "Invalid value for 2nd parameter " .
						        print_r($pattern, true) . "</p>\n";
		    return '';
		}

		// process parameters
        $or				= '';
        $i              = 1;

		foreach($parms as $pattern)
        {		// loop through patterns
            if (is_array($pattern))
            {               // array of simple string comparisons
                foreach($pattern as $val)
                {
                    if (is_string($refname))
                    {
                        $where			    .= $or . "$fldname=:$refname$i";
                        $this->sqlParms["$refname$i"]   = $val;
                        $i++;
                    }
                    else
                    {
                        $where			    .= $or . "$fldname=?";
                        $this->sqlParms[]   = $val;
                    }
                    $or             = ' OR ';
                }
            }               // array of simple string comparisons
            else
		    if ($pattern == '')
		    {			    // empty string
				$where			.= $or . "$fldname=''";
                $or             = ' OR ';
		    }			    // empty string
		    else
		    if (strlen($pattern) > 5 &&
				strtoupper(substr($pattern, 0, 5) == 'LIKE '))
		    {		        // portable pattern matching
				$value			= trim(substr($pattern, 5));
				if (substr($value,0,1) == "'" &&
				    substr($value,-1) == "'")
				    $value		= substr($value, 1, strlen($value)-2);
				else
				if (substr($value,0,1) == '"' &&
				    substr($value,-1) == '"')
				    $value		= substr($value, 1, strlen($value)-2);
				$value			= stripcslashes($value);
                if (is_string($refname))
                {
				    $where			.= $or . "$fldname LIKE :$refname$i";
                    $this->sqlParms["$refname$i"]   = $value;
                    $i++;
                }
                else
                {
                    $where			.= $or . "$fldname LIKE ?";
                    $this->sqlParms[]   = $val;
                }
                $or                 = ' OR ';
				$this->sqlParms[]	= $value;
		    }		        // portable pattern matching
		    else
		    if (strlen($pattern) > 1 &&
				substr($pattern, 0 ,1) == '^' &&
				preg_match("/[.*+?()[\]]/",
						   substr($pattern,1,strlen($pattern)-2)) == 0)
		    {		        // match at beginning
				if (substr($pattern, -1) == '$')
				{		    // match whole value
				    $vlen		= strlen($pattern) - 2;
				    $value	    = substr($pattern, 1, $vlen);
                    if (is_string($refname))
                    {
				        $where		.= $or . "$fldname=:$refname$i";
                        $this->sqlParms["$refname$i"]   = $value;
                        $i++;
                    }
                    else
                    {
				        $where		.= $or . "$fldname=?";
                        $this->sqlParms[]   = $value;
                    }
				}		    // match whole value
				else
				{		    // match at beginning of string
				    $vlen		= strlen($pattern) - 1;
				    $value  	= substr($pattern, 1);
                    if (is_string($refname))
                    {
				        $where		.= $or . "LEFT($fldname, $vlen)=:$refname$i";
                        $this->sqlParms["$refname$i"]   = $value;
                        $i++;
                    }
                    else
                    {
				        $where		.= $or . "LEFT($fldname, $vlen)=?";
                        $this->sqlParms[]   = $value;
                    }
				}		    // match at beginning of string
		    }		        // match at beginning
		    else
		    if (strlen($pattern) > 1 &&
				substr($pattern, -1) == '$' &&
				preg_match("/[.*+?()[\]]/",
						   substr($pattern,0,strlen($pattern)-1)) == 0)
		    {		        // match at end of string
				$vlen		 	= strlen($pattern) - 1;
			    $value  	    = substr($pattern, 0, $vlen);
                if (is_string($refname))
                {
			        $where		.= $or . "RIGHT($fldname, $vlen)=:$refname$i";
                    $this->sqlParms["$refname$i"]   = $value;
                    $i++;
                }
                else
                {
			        $where		.= $or . "RIGHT($fldname, $vlen)=?";
                    $this->sqlParms[]   = $value;
                }
		    }		        // match at end of string
		    else
		    if (preg_match("/[.*+?()[\]]/", $pattern) == 1)
		    {		        // value is a REGEXP pattern
                if (is_string($refname))
                {
			        $where		.= $or . "$fldname REGEXP :$refname$i";
                    $this->sqlParms["$refname$i"]   = $pattern;
                    $i++;
                }
                else
                {
			        $where		.= $or . "$fldname REGEXP ?";
                    $this->sqlParms[]   = $pattern;
                }
		    }		        // value is a REGEXP pattern
		    else
		    if (substr($pattern,0,1) == '!')
		    {		        // value is a string to exclude from results
				$value      	= substr($pattern,1);
                if (is_string($refname))
                {
			        $where		.= $or . "$fldname!=:$refname$i";
                    $this->sqlParms["$refname$i"]   = $value;
                    $i++;
                }
                else
                {
			        $where		.= $or . "$fldname!=?";
                    $this->sqlParms[]   = $value;
                }
		    }		        // value is a string to exclude from results
		    else
		    {		        // value is a string to find
                if (is_string($refname))
                {
			        $where		.= $or . "LOCATE(:$refname$i, $fldname) > 0";
                    $this->sqlParms["$refname$i"]   = $pattern;
                    $i++;
                }
                else
                {
			        $where		.= $or . "LOCATE(?, $fldname) > 0";
                    $this->sqlParms[]   = $pattern;
                }
		    }		        // value is a string to find
		    $or		= ' OR ';
		}		            // loop through patterns
		if (strlen($where) > 0 && count($parms) > 1)
		{
		    $where		= "($where)";
		}
		return $where;
    }		// function RecordSet::getWherePattern

    /********************************************************************
     *  function RecordSet::getSurnameChk								*
     *																	*
     *  Get a MySQL expression that performs a loose comparison for a	*
     *  a supplied surname.												*
     *	This can only be called for an object which contains fields		*
     *	named `Surname` and `SoundsLike`, that is for tblIR, tblNR,		*
     *	and tblNX.														*
     *																	*
     *  Input:															*
     *	    $table		the table to search								*
     *	    $surname	the surname to check for						*
     *																	*
     *  Returns:														*
     *	    a string that can be substituted into a MySQL WHERE clause	*
     ********************************************************************/
    protected function getSurnameChk($table, 
								     $surname)
    {
		$information	= Record::getInformation($table);
		if ($information)
		{			// valid table name
		    $initRow	= $information['initrow'];
		    $valid	= array_key_exists('surname',		$initRow) &&
						  array_key_exists('soundslike',	$initRow);
		    $table	= $information['table'];	// actual table name
		}			// valid table name
		else
		    $valid	= false;// invalid table name

		if ($valid)
		{			// valid table
		    $surnameRec	= new Surname(array('surname' => $surname));
		    $pattern	= $surnameRec->get('pattern');
		    if ($pattern != '')
		    {		// use pattern match rather than soundex
				$this->sqlParms[]	= $pattern;
				return $table . ".Surname REGEXP ?";
		    }		// use pattern match rather than soundex
		    else
		    {		// use soundex match
				// remove question marks
				$surname	        = str_replace('?', '', $surname);
                $this->sqlParms[]	= $surname;
                $sxtable            = $table;
                if ($table == 'tblNX')
                    $sxtable        = 'tblNR';
                $surnameCmp	        = $sxtable .
                                        ".SoundsLike=LEFT(SOUNDEX(?),4)";
				if (substr($surname, strlen($surname) - 1) == 's')
				{		// also try soundex without final s
				    $this->sqlParms[] = substr($surname, 0, strlen($surname)-1);
				}		// also try soundex without final s
				else
				{		// also try soundex with final s
				    $this->sqlParms[] = $surname . 's';
				}		// also try soundex with final s
				$surnameCmp	        .= " OR " . $sxtable .
									    ".SoundsLike=LEFT(SOUNDEX(?),4)";

				if (strlen($surname) > 4)
				{		// add other loose surname comparisons
				    // match a surname that starts with the same 2 characters
				    // and ends with the same character or
				    // starts with the same character and
				    // ends with the same 3 characters
				    $this->sqlParms[]	= substr($surname, 0, 2);
				    $this->sqlParms[]	= substr($surname, -1);
				    $this->sqlParms[]	= substr($surname, 0, 1);
				    $this->sqlParms[]	= substr($surname, -3);
				    $surnameCmp	.= " OR (LEFT($table.Surname,2)=?" .
									" AND RIGHT($table.Surname,1)=?) OR " .
									"(LEFT($table.Surname,1)=?" .
									" AND RIGHT($table.Surname,3)=?)";
				}		// add other loose surname comparisons
				return "($surnameCmp)";
		    }			// use soundex match
		}			// valid table name
		else
		{
		    $warn	.= "<p>record::getSurnameChk: " .
								"Unsupported table '$table'</p>\n";
		}
		return '';		// no comparison created
    }		// function RecordSet::getSurnameChk

    /********************************************************************
     *	fuunction RecordSet::getWhere									*
     *																	*
     *  Given an associative array of parameters for searching the		*
     *  table, return the appropriate SQL WHERE clause.					*
     *																	*
     *	Returns:														*
     *	    string containing WHERE clause								*
     *	    A zero length string is returned if the input array is		*
     *      empty, otherwise the string starts with the word WHERE		*
     *																	*
     *	Side Effects:													*
     *	    If any unusual data is encountered it is reported by		*
     *	    adding a message to $warn. 									*
     ********************************************************************/
    protected function getWhere()
    {
		global	$debug;
		global	$warn;

		$where		= '';		// return value
		$and		= 'WHERE ';	// combining operator

		// belt and suspenders
		if (!is_array($this->sqlParms))
		    $this->sqlParms		= array();

		// process parameters
		$translate		= null;
		$initRow		= $this->information['initrow'];
		$className		= $this->information['classname'];
        $nsclass        = __NAMESPACE__ . "\\" . $className;

		foreach($this->parms as $fldname => $value)
		{			// loop through all parameters
		    $expression		= '';
		    if (is_int($fldname) && is_array($value))
		    {			// OR expression
				$or		= '';
				$orExpression	= '';
				foreach($value as $fld2 => $val2)
				{
				    $fieldLc	= $nsclass::getRealFieldName($fld2);
				    if (is_null($fieldLc))
				    {
						$warn	.= "<p>RecordSet::getWhere: " . __LINE__ .
		    " Unrecognized field name '$fldname' in selection criteria</p>\n";
				    }
				    else
				    {		// valid field names
						if (is_string($initRow[$fieldLc]))
						    $expression	= $this->getWherePattern($fieldLc,
												 $val2);
						else
						if (is_int($initRow[$fieldLc]))
						    $expression	= $this->getWhereInt($fieldLc,
											     $val2);
						else
						    $expression	= $this->getWhereCommon($fieldLc,
												$val2);
						if (strlen($expression) > 0)
						{
						    $orExpression	.= $or . $expression;
						    $or			= ' OR ';
						}
				    }		// valid field namess
				}		// loop through comparisons
				if (strlen($orExpression) > 0)
				{
				    $where	.= $and . '(' . $orExpression . ')';
				    $and	= ' AND ';
				}
		    }			// OR expression
		    else
		    if (is_string($fldname))
            {			// field name
                $fieldLc	= $nsclass::getRealFieldName($fldname);
				if (is_null($fieldLc))
				{
				    $warn	.= "<p>RecordSet::getWhere: " . __LINE__ .
		    "  Unrecognized field name '$fldname' in selection criteria</p>\n";
				}
				else
                {		        // valid field names
                    if (array_key_exists($fieldLc, $initRow))
                    {           // field defined in initrow
					    if (is_string($initRow[$fieldLc]))
							$expression	= $this->getWherePattern($fieldLc,
													 $value);
					    else
					    if (is_int($initRow[$fieldLc]))
							$expression	= $this->getWhereInt($fieldLc,
												     $value);
					    else
	                    {
	                        if ($debug)
	                        {
	$warn	.= "<p>RecordSet::getWhere: " . __LINE__ .
					" \$initRow['$fieldLc']=" . gettype($initRow[$fieldLc]) .
					"</p>\n";
	foreach($initRow as $f => $v)
	$warn	.= "<p>RecordSet::getWhere: " . __LINE__ . ' ' .
					$f . '=' . gettype($v) . "='$v'</p>\n";
	                        }
							$expression	= $this->getWhereCommon($fieldLc,
	                                                            $value);
					    }
					    if (strlen($expression) > 0)
					    {
							$where	.= $and . $expression;
							$and	= ' AND ';
					    }
                    }           // field defined in initrow
				}		        // valid field namess
		    }			        // field name
		    else
				$warn	.= "<p>RecordSet::getWhere: " . __LINE__ .
						    " Unrecognized field name " . print_r($fldname,true) .
						    " in selection criteria</p>\n";
		}			// loop through all parameters

		return $where;
    }		// function RecordSet::getWhere

    /********************************************************************
     *	function RecordSet::delete										*
     *																	*
     *  Delete all records in a RecordSet.								*
     *																	*
     *  Parameters:														*
     *	    $xml		if true or a string, emit output to XML			*
     *	    $simulate	if true just display SQL command				*
     *																	*
     *	Returns:														*
     *			number of records deleted								*
     *																	*
     *	Side Effects:													*
     *	    An exception is thrown if the SQL server rejects the		*
     *	    DELETE command												*
     *	    If any unusual data is encountered it is reported by		*
     *	    adding a message to $warn.  A specific case is if the		*
     *	    an attempt is made to delete all the records in the table.	*
     ********************************************************************/
    public function delete($xml		    = false,
						   $simulate	= false)
    {
		global	$debug;
		global	$warn;
		global	$msg;
		global	$connection;

        if ($this->information['count'] > 1000)
        {
		    $msg        .= "RecordSet::delete: " .
								number_format($this->information['count']) .
                                " is too many records to delete.";
            return 0;
        }

		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $msgtag	    = "p class='message'";
		    $emsgtag	= "p";
		    $xml	    = true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		    $msgtag	    = 'msg';
		    $emsgtag	= 'msg';
		}		// true

		if (strlen($this->where) == 0)
		{
		    $warn	.= "<p>RecordSet: delete: " .
								"not permitted to delete all records in ".
								$this->externalName . "</p>\n";
		    return 0;
		}

		// update the database
		$delete		= "DELETE FROM " . $this->table . ' ' . $this->where;
		$deleteText	= debugPrepQuery($delete, $this->sqlParms);
		if ($simulate)
		{		// no not update database
		    print "<p>Simulate: '$deleteText'</p>\n";
		    $result	= 0;
		}		// do not update database
		else
		{		// update database
		    $stmt	= $connection->prepare($delete);
		    if ($stmt->execute($this->sqlParms))
		    {		// success
				$result	= $stmt->rowCount();
		    }		// success
		    else
		    {		// error performing query
				$msg        .= "RecordSet::delete: " .
									"'$deleteText', " .
                                    print_r($stmt->errorInfo(),true);
                return 0;
		    }		// error performing query
		}		// update database

		if ($result > 0)
		{		// deleted some records
		    if ($xml)
				print "    <$tagname count='$result'>\n" .
								"\t" . $deleteText . "\n" . 
				      "    </$tagname>\n";
		    else
		    if ($debug)
				$warn	.= "<p>RecordSet::delete: " . __LINE__ .
								" '$deleteText'</p>\n" .
						"<p>Deleted $result records</p>\n";
		    logSqlUpdate($delete,
						 $this->sqlParms,
						 0,
						 '',
						 '');
		}		// deleted some records

		return $result;
    }		// RecordSet::delete

    /********************************************************************
     *	function RecordSet::update										*
     *																	*
     *  Update all records in a RecordSet based upon a set				*
     *  of parameters.													*
     *																	*
     *  Parameters:														*
     *	    $setparms	associative array of field/value combinations	*
     *					to specify update to be applied to all records	*
     *					belonging to this RecordSet						*
     *	    $xml		if true or a string, emit output to XML			*
     *	    $simulate	if true just display SQL command				*
     *	    $orderby	value for ORDER clause							*
     *					this can also be set by specifying				*
     *					'order' in the first parameter.					*
     *																	*
     *	Returns:														*
     *		number of records updated								    *
     *																	*
     *	Side Effects:													*
     *	    An exception is thrown if the SQL server rejects the		*
     *	    UPDATE command												*
     *	    If any unusual data is encountered it is reported by		*
     *	    adding a message to $warn.  A specific case is if the		*
     *	    SET clause is empty because no valid parameters were		*
     *	    passed in the first parameter.								*
     ********************************************************************/
    public function update($setparms,
						   $xml		= false,
						   $simulate	= false,
						   $orderby	= '')
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$quit		    = false;
		if (is_string($xml))
		{		// string tag name
		    $tagname	= $xml;
		    $msgtag	    = "p class='message'";
		    $emsgtag	= "p";
		    $xml	    = true;
		}		// string tag name
		else
		if ($xml)
		{		// true
		    $tagname	= 'cmd';
		    $msgtag	    = 'msg';
		    $emsgtag	= 'msg';
		}		// true

		// construct the SET clause for the update
		$set			= '';
		$comma			= '';
		$setParms		= array();
		$className		= $this->information['classname'];
        $nsclass        = __NAMESPACE__ . "\\" . $className;
		foreach($setparms as $fldname => $value)
		{		// loop through all parameters
		    if (strtolower($fldname) == 'order')
				$orderby		= $value;
		    else
		    { 
				if ($className)
				    $fieldLc	= $nsclass::getRealFieldName($fldname);
				else
				    $fieldLc	= strtolower($fldname);
				if ($fieldLc)
				{
				    if (is_array($value))
				    {		// array
						if (count($value) == 2 &&
						    is_string($value[0]) && is_string($value[1]))
						{	// use REPLACE
						    $set	.= $comma . 
									" `$fieldLc`=REPLACE(`$fieldLc`,?,?)";
						    $setParms[]	= $value[0];
						    $setParms[]	= $value[1];
						}	// use REPLACE
						else
						if (count($value) >= 1)
						{	// ignore all but first
						    $set	.= "$comma `$fieldLc`=?";
						    $setParms[]	= $value[0];
						    $warn	.= "<p>RecordSet::update: " . __LINE__ .
									   " unexpected $fldname=" .
									   print_r($value, true) . "</p>\n";
						}	// ignore all but first
						else
						{	// empty array
						    $set	.= "$comma `$fieldLc`=''";
						    $warn	.= "<p>RecordSet::update: " . __LINE__ .
									   " unexpected $fldname=" .
									   "empty array</p>\n";
						}	// empty array
				    }		// array
				    else
				    if (is_string($value))
				    {		// string
						if (substr($value, 0, 2) == '+=' &&
						    ctype_digit(substr($value, 2)))
						{		// simulate increment
						    $set	.= "$comma `$fieldLc`=`$fieldLc`+" .
										   substr($value, 2);
						}		// simulate increment
						else
						if (substr($value, 0, 2) == '-=' &&
						    ctype_digit(substr($value, 2)))
						{		// simulate decrement
						    $set	.= "$comma `$fieldLc`=`$fieldLc`-" .
										   substr($value, 2);
						}		// simulate decrement
						else
						{
						    $set	.= "$comma `$fieldLc`=?";
						    $setParms[]	= $value;
						}
				    }		// string
				    else
				    {		// neither array nor string
						$set		.= "$comma `$fieldLc`=?";
						$setParms[]	= $value;
				    }		// neither array nor string
				 
				    $comma		= ', ';
				}
				else
				{
				    $warn		.= "<p>RecordSet::update " . __LINE__ .
		" Unrecognized field name '$fldname' in update parameters</p>\n";
				    $quit		= true;
				}
		    }
		}		// loop through all parameters

		if (strlen($orderby) > 0)
		{		// ORDER specified
		    $fields	= explode(',',str_replace('`','',$orderby));
		    $initrow	= $this->information['initrow'];
		    foreach($fields as $orderfield)
		    {
				$space	= strpos($orderfield, ' '); // exclude ASC/DESC
				if ($space > 0)
				    $orderfield		= substr($orderfield, 0, $space);
				if (!array_key_exists(strtolower($orderfield), $initrow))
				{
				    $warn	.= "<p>RecordSet::update: " .
				   "Invalid field name '$orderfield' in ORDER clause</p>\n";
				    $quit		= true;
				}
		    }
		}		// ORDER specified

		// do not permit updating all records in table
		if (strlen($this->where) == 0)
		{
		    $warn	.= "<p>RecordSet: update: ".
								"cannot update all records in table " .
								$this->externalName . "</p>\n";
		    $quit		= true;
		}

		if ($quit)
		    return 0;

		if (strlen($set) > 0)
		{
		    // update the database
		    $update		= 'UPDATE ' . $this->table . 
								    " SET $set " .$this->where;
		    if (strlen($orderby) > 0)
				$update	.= ' ORDER BY ' . $orderby;
		    $sqlParms	= array_merge($setParms, $this->sqlParms);
		    $updateText	= debugPrepQuery($update, $sqlParms);

		    if ($simulate)
		    {		// no not update database
				print "<p>Simulate: '$updateText'</p>\n";
				$count		= 0;
		    }		// do not update database
		    else
		    {		// update database
				$stmt		= $connection->prepare($update);
				if ($stmt->execute($sqlParms))
				{		// success
				    $count		= $stmt->rowCount();
				}		// success
				else
				{		// error performing query
				    throw new Exception("RecordSet::update: " .
									    "'$updateText', " .
									    print_r($stmt->errorInfo(),true));
				}		// error performing query
		    }		// update database

		    if ($count > 0)
		    {		// updated some records
                if ($xml)
                {
				    print "    <$tagname count='$count'>\n" .
								    "\t" . $updateText . "\n" .
                                    "    </$tagname>\n";
                    $callback   = debug_backtrace();
                    foreach($callback as $i => $entry)
                    {
                        print "<call level=\"$i\">" . $entry["file"] . ':' . $entry["line"] . ' ' . $entry["function"] . '(' . print_r($entry["args"], true) . ")</call>\n";
                    }
                }
				if ($debug)
				    $warn	.= "<p>RecordSet.inc: " . __LINE__ . ' ' . 
								    $updateText . "</p>\n" .
						    "<p>Updated $count records</p>\n";
				logSqlUpdate($update,
						     $sqlParms,
						     0,
						     '',
						     '');
		    }		// updated some records
		}
		else
		{
		    $warn	.= "<p>RecordSet::update: no update requested</p>\n";
		    return 0;
		}

		return $count;
    }		// function RecordSet::update

    /********************************************************************
     *	function RecordSet::count										*
     *																	*
     *	Get the number of records in the set that can be iterated over	*
     *	or that would be deleted or updated.							*
     *  This can either by accessed by $set->count() or by count($set).	*
     *																	*
     *  Returns:														*
     *	    count of records											*
     ********************************************************************/
    public function count()
    {
        if (array_key_exists('count', $this->information))
            $totalCount	= $this->information['count'];
        else
            $totalCount = 0;
		if ($this->limit < $totalCount)
		    return $this->limit;
		else 
		    return $totalCount;
    }		// function RecordSet::count

    /********************************************************************
     *	function RecordSet::getDistinct									*
     *																	*
     *	Get an array of the distinct values of a field in the table.	*
     *																	*
     *	Parameters:														*
     *	    $field		name of field or commalist of fields to get		*
     *					values for.  If the request uses a JOIN each	*
     *					entry may be a table-qualified name. Field		*
     *					names can be either real field names or			*
     *					synonyms and table names may be either real		*
     *					table names or synonyms.						*
     *					e.g. IDIME, Names.Surname, Persons.Gender		*
     *	    $join		a JOIN expression, which may use either real	*
     *					table names or external synonyms, and either	*
     *		            real field names or synonyms.  e.g.		        * 
     *					JOIN Names on Names.IDIR=Citations.IDIME		*
     *																	*
     *  Returns:														*
     *	    array of values or array of associative arrays				*
     ********************************************************************/
    public function getDistinct($field,
								$join	= '')			
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$retval		        = array();
		// construct a query for the specific matches
		$className			= $this->information['classname'];
        $nsclass        	= __NAMESPACE__ . "\\" . $className;
		$fields				= explode(',',$field);
		$distinct			= '';
		$orderFld			= '';
		$comma				= '';
		foreach($fields as $dfield)
		{		// support multiple fields in DISTINCT clause
		    $matches		= array();
		    if (preg_match('/(\w+)\s*\.s*(\w+)/', $dfield, $matches))
		    {		// support table.fieldname
				$tblName	= $matches[1];
				$tfield		= $matches[2];
				$info		= Record::getInformation($tblName);
				$tclassName	= $info['classname'];
                $tnsclass   = __NAMESPACE__ . "\\" . $tclassName;
				// support field name and table name synonyms
				$tfield		= $tnsclass::getRealFieldName($tfield);
				if (is_null($tfield))
				    $fieldLc	= null;
				else
				    $fieldLc	= $info['table'] . '.' . $tfield;
		    }		// support table.fieldname
		    else
				$fieldLc	= $nsclass::getRealFieldName(trim($dfield));
		    if (is_null($fieldLc))
				$warn	.= "<p>RecordSet::getDistinct: " .
						   "Invalid field name parameter '$dfield'</p>\n";
		    else
				$distinct	.= $comma . $fieldLc;

		    // the value returned by getRealFieldName may be an expression
		    // defined as a pseudo fieldname
		    if (preg_match('/^(.*) AS (.*)$/i', $fieldLc, $matches))
				$orderFld	.= $comma . $matches[2];
		    else
				$orderFld	= $comma . $fieldLc;
		    $comma		= ',';
		}		// support multiple fields in DISTINCT clause

		// perform the query
		$query			= "SELECT DISTINCT $distinct " .
								  "FROM `" . $this->table . '` ' .
								  $this->where . " ORDER BY $orderFld";
		$queryText		= debugPrepQuery($query, $this->sqlParms);
		if ($debug)
		    $warn	.= "<p>RecordSet::getDistinct: " . __LINE__ . " query='$queryText'</p>\n";

		// query the database for matches
		$stmt		= $connection->prepare($query);
		if ($stmt->execute($this->sqlParms))
		{		// success
		    if ($debug)
				$warn	.= "<p>RecordSet.inc: " . __LINE__ .
								   " \"$queryText\"</p>\n";
		    $result	= $stmt->fetchAll(PDO::FETCH_NUM);
		    foreach($result as $row)
				$retval[]	= $row[0];
		    return $retval;
		}		// success
		else
		{		// error performing query
		    throw new Exception("RecordSet::getDistinct: \"$queryText\", " .
								    print_r($stmt->errorInfo(),true));
		}		// error performing query

    }		// public function getDistinct()

    /********************************************************************
     *	function RecordSet::getErrors									*
     *																	*
     *  Get any errors detected by the constructor.  These errors       *
     *  prevent updates being applied to the database but are           *
     *  accumulated as an alternative to throwing an exception.         *
     *																	*
     *	Returns:														*
     *	    string containing error messages                            *
     ********************************************************************/
    function getErrors($xml)
    {
        return $this->$msg;
    }		// function Record::getErrors

    /********************************************************************
     *	function RecordSet::push										*
     *																	*
     *	Add a new index and record to the front of the RecordSet.		*
     *	Note that $recordSet[$index] = $record will add $record to		*
     *	the back of the RecordSet if $index does not already exist		*
     *																	*
     *	Input:															*
     *	    $index		index, unique key, of the record being added	*
     *	    $record		record to add.  Note that this must be the		*
     *					same class as the existing record(s)			*
     *																	*
     ********************************************************************/
    public function push($index, $record)
    {
		global	$debug;
		global	$warn;
		global	$connection;

		$newEntry		= array($index => $record);
		if ($this->list === null)
		    $this->list		= $newEntry;
		else
		    $this->list		= array_merge($newEntry, $this->list);
    }		// function RecordSet::push

    /********************************************************************
     *	function RecordSet::rewind										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Reposition to the first element of the "array".					*
     *	The actual loading of the table is deferred until now so that	*
     *	an instance of RecordSet can be used to determine how many		*
     *	records are in the set even if the records themselves are not	*
     *	to be examined.													*
     *																	*
     *  Returns:														*
     *	    first instance of Record in the set							*
     ********************************************************************/
    public function rewind()
    {
		global	$debug;
		global	$warn;
		global	$msg;
        global	$connection;

        $maxsetsize         = 1000;
        if (array_key_exists('maxsetsize', $this->information))
            $maxsetsize     = $this->information['maxsetsize'];
        if ($this->limit == PHP_INT_MAX &&
            $this->information['count'] > $maxsetsize)
        {
            $warn   .= "<p>RecordSet::rewind: " . __LINE__ . ' ' .
                        number_format($this->information['count']) .
                        " is too many records to read into memory for query '" .
                        htmlspecialchars($this->information['query']) . 
                        "' from parms " . print_r($this->parms, true) . ".</p>\n";
            $this->limit    = 0;
        }

		if ($this->list === null)
		{
		    if ($this->limit == 0)
		    {
				$this->list	= array();
				return false;
		    }

		    // construct a query for the specific matches
		    $query		    = $this->query;
		    $queryText		= debugPrepQuery($query, $this->sqlParms);

		    // query the database for matches
		    $stmt		    = $connection->prepare($query);
		    if ($stmt->execute($this->sqlParms))
		    {		// success
				$result		= $stmt->fetchAll(PDO::FETCH_ASSOC);
				if ($debug)
				{
				    $warn	.= "<p>RecordSet.inc: " . __LINE__ .
								   " \"$queryText\"</p>\n";
				}

				// have a result set, fetch rows
				$retval			= array();
				$prime			= $this->information['prime'];
				$className		= $this->information['classname'];
				$nsclass    	= __NAMESPACE__ . "\\" . $className;
				if (!class_exists($nsclass))
				    include __NAMESPACE__ . "/" . $className . ".inc";
				$censusObj		= null;
                $oldCensusId	= '';
                $censusTable    = ($className == 'CensusLine') ||
                                  preg_match('/^Census\d\d\d\d$/', $className);
				foreach($result as $row)
				{		// loop through records from table
				    if (is_array($row))
				    {
						if ($className)
						{
						    if ($className == 'Record')
								$object		= new Record($row,$this->table);
						    else
						    if ($censusTable)
						    {           // census
								if (array_key_exists('censusid', $row))
								    $censusId	= $row['censusid'];
								else
								{
								    $censusYear	= substr($this->table, 6, 4);
								    if ($censusYear > 1867)
									    $censusId   = 'CA' . $censusYear;
								    else
                                        $censusId   = $row['province'] . 
                                                      $censusYear;
								}
								if (is_null($censusObj) ||
								    $censusId != $oldCensusId)
								{
								    $censusObj		= 
								new Census(array('censusid' => $censusId));
								    $oldCensusId	= $censusId;
								}
								$object		= new $nsclass(array('Census' => $censusObj),
												 $row);
						    }           // census
                            else
                            {
                                $object		= new $nsclass($row);
                            }
						}
						else
						    $object		    = new Record($row,$this->table);
						if (is_string($prime) && strlen($prime) > 0)
						    $retval[$row[$prime]]= $object;
						else
						    $retval[]		= $object;
				    }
				}		// loop through records from table

				$this->list	= $retval;
		    }		// success
		    else
		    {		// error performing query
                print "<p>RecordSet::rewind: " . __LINE__ .
                        " query='$query', " .
						" '$queryText'</p>\n";
				if (strlen($queryText) > 40)
				    $queryText	= substr($queryText, 0, 30) . '...' .
								  substr($queryText, -7);
				throw new Exception("RecordSet::rewind: \"$queryText\", " .
								    print_r($stmt->errorInfo(),true));
		    }		// error performing query
		}

		return reset($this->list);
    }		// public function rewind()

    /********************************************************************
     *	function RecordSet::current										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Return the value of the current element of the "array"			*
     *																	*
     *  Returns:														*
     *	    instance of Record											*
     ********************************************************************/
    public function current()
    {
		if ($this->list === null)
		    $this->rewind();
		$row		= current($this->list);
		return $row;
    }		// public function current()

    /********************************************************************
     *	function RecordSet::key											*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Return the key of the current element of the "array".			*
     *																	*
     *  Returns:														*
     *	    instance of Record											*
     ********************************************************************/
    public function key()
    {
		if ($this->list === null)
		    $this->rewind();
		$row		= key($this->list);
		return $row;
    }		// public function key()

    /********************************************************************
     *	function RecordSet::next										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Advance to the next element of the "array" and return it.		*
     *																	*
     *  Returns:														*
     *	    instance of Record or false if at end						*
     ********************************************************************/
    public function next()
    {
		if ($this->list === null)
		    $row	= $this->rewind();
		else
		    $row	= next($this->list);
		return $row;
    }		// public function next()

    /********************************************************************
     *	function RecordSet::last										*
     *																	*
     *	Set the current pointer to the last entry in the set and		*
     *	return the last entry in the set.								*
     *																	*
     *  Returns:														*
     *	    instance of Record											*
     ********************************************************************/
    public function last()
    {
		if ($this->list === null)
		    $this->rewind();
		$row		= end($this->list);
		return $row;
    }		// public function last()

    /********************************************************************
     *	function RecordSet::valid										*
     *																	*
     *	Part of the implementation of the interface Iterator.			*
     *	Returns true if the iterator is valid.							*
     *																	*
     *  Returns:														*
     *	    boolean														*
     ********************************************************************/
    public function valid()
    {
		if ($this->list === null)
		    $this->rewind();
        if ($this->list === null)
            return false;
		$key		= key($this->list);
		$valid 		= ($key !== NULL && $key !== FALSE);
		return $valid;
    }		// public function valid()

    /********************************************************************
     *	function RecordSet::offsetSet									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Updates the set to include a new element.						*
     ********************************************************************/
    public function offsetSet($offset, $value)
    {
		global	$debug;
		global	$warn;

		if ($this->list === null)
		    $this->rewind();
		if (is_null($offset))
		{
		    $this->list[]	= $value;
		}
		else 
		{
		    $this->list[$offset] = $value;
		}
		$this->information['count']++;
    }		// public function offsetSet

    /********************************************************************
     *	function RecordSet::offsetExists								*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Returns true if the index is in use.							*
     ********************************************************************/
    public function offsetExists($offset)
    {
		if ($this->list === null)
		    $this->rewind();
		return isset($this->list[$offset]);
    }		// public function offsetExists($offset)

    /********************************************************************
     *	function RecordSet::offsetUnset									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Deletes an element out of the RecordSet							*
     ********************************************************************/
    public function offsetUnset($offset)
    {
		if ($this->list === null)
		    $this->rewind();
		unset($this->list[$offset]);
    }		// public function offsetUnset($offset)

    /********************************************************************
     *	function RecordSet::offsetGet									*
     *																	*
     *	Part of the implementation of the interface ArrayAccess.		*
     *	Returns the instance associated with an index.					*
     ********************************************************************/
    public function offsetGet($offset)
    {
		if ($this->list === null)
		    $this->rewind();
		return isset($this->list[$offset]) ? $this->list[$offset] : null;
    }		// public function offsetGet($offset)

}		// class RecordSet

