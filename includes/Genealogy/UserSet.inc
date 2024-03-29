<?php
namespace Genealogy;
use \PDO;
/************************************************************************
 *  UserSet.inc                                                         *
 *                                                                      *
 *  Definition of a class representing a set of Users                   *
 *  Note that the former function of static function                    *
 *  RecOwner::getOwners has been moved here because that function       *
 *  returned an array of instances of User.                             *
 *                                                                      *
 *  History:                                                            *
 *      2017/10/17      created                                         *
 *      2019/07/19      correct construction of WHERE clause for        *
 *                      username => array                               *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';

/************************************************************************
 *  class UserSet                                                       *
 *                                                                      *
 *  Definition of a class representing a set of Users.                  *
 ************************************************************************/

class UserSet extends RecordSet
{

    /********************************************************************
     *  function UserSet::__construct                                   *
     *                                                                  *
     *  Given defining parameters construct an instance.                *
     *                                                                  *
     *  Examples:                                                       *
     *      Get all of the administrators:                              *
     *          new UserSet(array('auth'        => 'yes'))              *
     *      Get users whose name contains text:                         *
     *          new UserSet(array('username'    => $text))              *
     *      Get users whose email address contains text:                *
     *          new UserSet(array('email'       => $text))              *
     *  In addition to the ability to search by the fields within the   *
     *  User record it is also possible to search for all of the Users  *
     *  who share ownership of a record, including all administrators,  *
     *  by creating an instance:                                        *
     *          new UserSet(array('table'       => $tablename,          *
     *                            'recordid'        => $recordid))      *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      associative array of search parameters          *
     *                  for example:                                    *
     *                      array('username'        => 'JohnSmith',     *
     *                            'email'       => 'jsmith@google.ca')  *
     *                                                                  *
     *  Returns object representing a set of Users.                     *
     ********************************************************************/
    function __construct($parms = null)
    {
        parent::__construct('Users', $parms);
    }       // UserSet::__construct

    /********************************************************************
     *  function UserSet::getWhere                                      *
     *                                                                  *
     *  Construct the WHERE clause to select a set of Users based on    *
     *  the parameters.                                                 *
     *                                                                  *
     *  Returns:                                                        *
     *      String containing WHERE clause                              *
     *                                                                  *
     *  Side Effects:                                                   *
     *      Updates $this->sqlParms                                     *
     ********************************************************************/
    function getWhere()
    {
        global  $debug;
        global  $warn;

        // construct the query of the table
        $where      = '';
        $and        = 'WHERE ';
        $recordid   = null;
        $table      = 'Users';
        foreach($this->parms as $fldname => $value)
        {
            $fieldLc    = User::getRealFieldName($fldname);
            switch($fieldLc)
            {                       // act on specific field name
                case 'username':
                    if (is_string($value))
                        $usernames      = array($value);
                    else
                    if (is_array($value))
                        $usernames      = $value;
                    else
                    {
                        $this->msg      .= "Unsupported value " .
                                        gettype($value) . " for 'username'. ";
                        break;
                    }

                    $iu                 = '';
                    $or                 = $and . '(';
                    $close              = '';
                    foreach($usernames as $value)
                    {                   // loop through supplied values
                        if (substr($value,0,1) == '!')
                        {               // exclusion
                            $where              .= $and .
                                                   "`UserName`<>:username$iu";
                            $this->sqlParms["username$iu"]
                                                = substr($value, 1);
                            $or                 = ' AND (';
                            $and                = ' AND ';
                            $close              = '';
                        }               // exclusion
                        else
                        if (strlen($value) > 2 &&
                            substr($value,0,1) == '^' &&
                            substr($value,-1,1) == '$')
                        {               // inclusion specific username
                            $where              .= $or . 
                                                    "`UserName`=:username$iu";
                            $this->sqlParms["username$iu"]
                                                = substr($value,
                                                         1, 
                                                         strlen($value)-2);
                            $or                 = ' OR ';
                            $and                = ') AND ';
                            $close              = ')';
                        }               // inclusion specific username
                        else
                        if (strlen($value) > 1 &&
                            substr($value,0,1) == '^')
                        {               // inclusion specific username
                            $prefix             = substr($value, 1);
                            $where              .= $or . 
                                "LEFT(`UserName`," .
                                strlen($prefix) . ")=:username$iu";
                            $this->sqlParms["username$iu"] = $prefix;
                            $or                 = ' OR ';
                            $and                = ') AND ';
                            $close              = ')';
                        }               // inclusion specific username
                        else
                        if (strlen($value) > 0)
                        {               // inclusion pattern
                            $where              .= $or . 
                                        "LOCATE(:username$iu,`UserName`) > 0 ";
                            $this->sqlParms["username$iu"]
                                                = $value;
                            $or                 = ' OR ';
                            $and                = ') AND ';
                            $close              = '';
                        }               // inclusion pattern

                        if ($iu == '')
                            $iu                 = 1;
                        else
                            $iu++;
                    }                   // loop through supplied values
                    $where                      .= $close;
                    break;              // username

                case 'email':
                    if (is_string($value))
                    {
                        if (substr($value, 0, 1) == '^')
                        {
                            if (substr($value, -1, 1) == '$')
                            {
                                $len            = strlen($value) - 2;
                                $where          .= "$and`Email`=:email";
                                $value          = substr($value, 1, $len);
                            }
                            else
                            {
                                $len            = strlen($value) - 1;
                                $where          .= "$and LEFT(`Email`,$len)=:email";
                                $value          = substr($value, 1, $len);
                            }
                        }
                        else
                        if (substr($value, -1, 1) == '$')
                        {
                            $len            = strlen($value) - 1;
                            $where          .= "$and RIGHT(`Email`,$len)=:email";
                            $value          = substr($value, 0, $len);
                        }
                        else
                            $where          .= "$and LOCATE(:email,`Email`) > 0";
                        $this->sqlParms['email']    = $value;
                        $and            = ' AND ';
                    }
                    else
                        $this->msg      .= "Unsupported value for 'email'. ";
                    break;                  // e-mail address

                case 'auth':
                    if (is_string($value))
                    {
                        if (strtolower($value) == 'pending')
                            $where      .= $and . "`Auth`='pending'";
                        else
                        {               // match full tokens
                            $pattern    = '(^|,)' . $value . '(,|$)';
                            $where      .= $and .
                                        "(`Auth` REGEXP :auth OR `Auth`='yes')";
                            $this->sqlParms['auth'] = $value;
                        }               // match full tokens
                        $and            = ' AND ';
                    }
                    else
                        $this->msg      .= "Unsupported value for 'auth'. ";
                    break;              // authorizations

                case 'options':
                    if (is_int($value) || ctype_digit($value))
                    {
                        $where      .= $and . "(`Options` & :options) > 0";
                        $this->sqlParms['options']  = $value;
                        $and        = ' AND ';
                    }
                    else
                        $this->msg      .= "Unsupported value for 'options'. ";
                    break;              // user set options

                case 'recordid':        // search RecOwners table
                    if (is_int($value) ||
                        (is_string($value) && ctype_digit($value)))
                        $recordid   = intval($value);
                    break;              // search RecOwners table

                case 'table':           // search RecOwners table
                    // can be an alternate table name
                    $info           = Record::getInformation($value);
                    if ($info)
                        $table      = $info['table'];
                    else
                        $table      = 'Users';
                    break;              // search RecOwners table

                case 'password':
                    $where          .= $and .
                                       $this->getWhereCommon($fieldLc,
                                                             $value,
                                                             'password');
                    break;

                default:
                    $this->msg      .= "Unsupported '$fldname' => $value. ";
                    break;
            }                   // act on specific field name
        }                       // loop through all parameters

        if (strlen($recordid) > 0)
        {       // get list of owners of a record
            $where  .= $and . "(Auth REGEXP 'yes'" .
                                " OR Username IN " .
                                "(SELECT RO_Username FROM RecOwners WHERE " .
                                    " RO_Table='$table' AND " .
                                    " RO_KeyValue=:recordid))";
            $this->sqlParms['recordid'] = $recordid;
        }       // get list of owners of a record

        return $where;
    }       // UserSet::getWhere

    /********************************************************************
     *  function UserSet::getMaillist                                   *
     *                                                                  *
     *  Obtain a string of e-mail addresses suitable for insertion      *
     *  into a mail request.                                            *
     *                                                                  *
     *  Returns:                                                        *
     *      String containing list of mail addresses                    *
     ********************************************************************/
    function getMaillist()
    {
        global  $debug;
        global  $warn;
        global  $connection;

        $list                   = '';
        $comma                  = '';
        $query                  = "SELECT `Email` FROM Users {$this->where}";
                // add the LIMIT clause
        if ($this->limit < PHP_INT_MAX)
           $query               .= " LIMIT " . $this->limit;

        // add the OFFSET clause
        if ($this->offset > 0)
           $query               .= " OFFSET " . $this->offset;

        $stmt                   = $connection->prepare($query);
        $queryText              = debugPrepQuery($query, $this->sqlParms);
        if ($stmt->execute($this->sqlParms))
        {
            $this->lastSqlCmd   = $queryText;
            $emails             = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
	        foreach($emails as $email)
	        {                       // assemble bulk mailing list
	            if (filter_var($email, FILTER_VALIDATE_EMAIL))
	            {                   // valid e-mail
	                $list       .= $comma . $email;
	                $comma      = ',';
	            }                   // valid e-mail
		        else
		        {
                    error_log("'$email' is not a valid e-mail address. Record deleted.");
                    $baduser    = new User(array('email' => $email));
                    $baduser->delete();
                }
            }                       // assemble bulk mailing list
        }                           // query succeeded
        else
        {           // error performing query
            $this->msg  .= "<p class='error'>RecordSet::__construct: " .
                            __LINE__ .
                            " \"$queryText\"" .
                    "<br>parms=" . var_export($this->sqlParms,true) . 
                    "<br>errors=" . var_export($stmt->errorInfo(),true);
        }           // error performing query
        return $list;
    }       // UserSet::getMailList

}       // class UserSet
