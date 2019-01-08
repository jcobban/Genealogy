<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  UserSet.inc								*
 *									*
 *  Definition of a class representing a set of Users 			*
 *  Note that the former function of static function 			*
 *  RecOwner::getOwners has been moved here because that function	*
 *  returned an array of instances of User.				*
 *									*
 *  History:								*
 *	2017/10/17	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';

/************************************************************************
 *  class UserSet							*
 *									*
 *  Definition of a class representing a set of Users.			*
 ************************************************************************/

class UserSet extends RecordSet
{

    /********************************************************************
     *	UserSet::__construct						*
     *									*
     *  Given defining parameters construct an instance.		*
     *									*
     *	Examples:							*
     *	Get all of the administrators:					*
     *		new UserSet(array('auth'	=> 'yes'))		*
     *	Get users whose name contains text:				*
     *		new UserSet(array('username'	=> $text))		*
     *	Get users whose email address contains text:			*
     *		new UserSet(array('email'	=> $text))		*
     *	In addition to the ability to search by the fields within the	*
     *	User record it is also possible to search for all of the Users	*
     *	who share ownership of a record, including all administrators,	*
     *	by creating an instance:					*
     *		new UserSet(array('table'	=> $tablename,		*
     *				  'recordid'	=> $recordid))		*
     *									*
     *  Input:								*
     *	    $parms	associative array of search parameters		*
     *			for example:					*
     *			    array('username'	=> 'JohnSmith',		*
     *				  'email'	=> 'jsmith@google.ca')	*
     *									*
     *  Returns object representing a set of Users.			*
     ********************************************************************/
    function __construct($parms)
    {
	parent::__construct('Users', $parms);
    }		// UserSet::__construct

    /********************************************************************
     *	UserSet::getWhere						*
     *									*
     *  Construct the WHERE clause to select a set of Users based on	*
     *  the parameters.							*
     *									*
     *	Returns:							*
     *	    String containing WHERE clause				*
     *									*
     *	Side Effects:							*
     *		Updates $this->sqlParms					*
     ********************************************************************/
    function getWhere()
    {
	global	$debug;
	global	$warn;

	// construct the query of the table
	$where		= '';
	$and		= 'WHERE ';
	$recordid	= null;
	$table		= null;
	foreach($this->parms as $fldname => $value)
	{
	    $fieldLc	= User::getRealFieldName($fldname);
	    switch($fieldLc)
	    {		// act on specific field name
		case 'username':
		{
		    if (substr($value,0,1) == '^' && substr($value,-1,1) == '$')
		    {
			$where		.= $and . "`UserName`=?";
			$this->sqlParms[]= substr($value, 1, strlen($value)-2);
		    }
		    else
		    if (substr($value,0,1) == '!')
		    {
			$where		.= $and . "`UserName`<>?";
			$this->sqlParms[]= substr($value, 1);
		    }
		    else
		    {
			$where		.= $and . "LOCATE(?,`UserName`) > 0 ";
			$this->sqlParms[]= $value;
		    }
		    $and		= ' AND ';
		    break;
		}

		case 'email':
		{
		    $where		.= $and . "LOCATE(?,`Email`) > 0 ";
		    $this->sqlParms[]	= $value;
		    $and		= ' AND ';
		    break;
		}

		case 'auth':
		{
		    if (strtolower($value) == 'pending')
			$where			.= $and . "`Auth`='pending'";
		    else
		    {
			$pattern		= '(^|,)' . $value . '(,|$)';
			$where			.= $and .
					"(`Auth` REGEXP ? OR `Auth`='yes')";
			$this->sqlParms[]	= $value;
		    }
		    $and		= ' AND ';
		    break;
		}

		case 'options':
		{
		    $where		.= $and . "(`Options` & ?) > 0";
		    $this->sqlParms[]	= $value;
		    $and		= ' AND ';
		    break;
		}

		case 'recordid':
		{
		    if (is_int($value) ||
			(is_string($value) && ctype_digit($value)))
			$recordid	= intval($value);
		    break;
		}

		case 'table':
		{			// can be an alternate table name
		    $info		= Record::getInformation($value);
		    if ($info)
			$table		= $info['table'];
		    else
			$table		= 'Users';
		    break;
		}

		default:
		{
		    $where		.= $and . $fldname . '=?';
		    $this->sqlParms[]	= $value;
		    $and		= ' AND ';
		    break;
		}
	    }		// act on specific field name
	}		// loop through all parameters

	if (strlen($recordid) > 0 && strlen($table) > 0)
	{		// get list of owners of a record
	    $where	.= $and . "(Auth REGEXP 'yes'" .
				" OR Username IN " .
				"(SELECT RO_Username FROM RecOwners WHERE " .
				    " RO_Table='$table' AND " .
				    " RO_KeyValue=?))";
	    $this->sqlParms[]	= $recordid;
	}		// get list of owners of a record

	return $where;
    }		// UserSet::getWhere

}		// class UserSet
