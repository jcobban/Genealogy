<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Book.inc                                                            *
 *                                                                      *
 *  Definition of a class representing a book.                          *
 *  This class provides access to the information in a row              *
 *  of the table Books.                                                 *
 *                                                                      *
 *  CREATE TABLE Books ( ISBN CHAR(13),                                 *
 *                       PRIMARY KEY (ISBN),                            *
 *                       Dewey VARCHAR(16),                             *
 *                       INDEX (Dewey),                                 *
 *                       LC VARCHAR(16),                                *
 *                       INDEX (LC),                                    *
 *                       Title VARCHAR(64),                             *
 *                       INDEX (Title),                                 *
 *                       Author VARCHAR(255),                           *
 *                       Image VARCHAR(255))                            *
 *                                                                      *
 *  History:                                                            *
 *      2021/05/16      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';

/************************************************************************
 *  class Book                                                          *
 *                                                                      *
 *  Definition of a class recording information about a book.           *
 *                                                                      *
 ************************************************************************/

class Book extends Record
{
    /********************************************************************
     *  Book::$initRow                                                  *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'isbn'              => '',
                'dewey'             => '',
                'lc'                => '',
                'title'             => '',
                'author'            => '',
                'image'             => '');

    /********************************************************************
     *  Book::$info                                                     *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'             => 'Books',
                'name'              => 'Books',
                'prime'             => 'isbn',
                'srvmg'             => false,
                'fldcount'          => 6,
                'order'             => 'title',
                'classname'         => 'Book');

    /********************************************************************
     *  Book::$defaultOrder                                             *
     *                                                                  *
     *  Default sort order for query response by class RecordSet        *
     ********************************************************************/
    static $defaultOrder    = "`Title`";

    /********************************************************************
     *  Book::$translate                                                *
     *                                                                  *
     *  Define synonyms for field names                                 *
     ********************************************************************/
    protected static $translate = array(
                'barcode'           => 'isbn');

    /********************************************************************
     *  Book::__construct                                               *
     *                                                                  *
     *  Construct an instance of Book based upon the                    *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $parms              an associative array of parameters      *
     *                          to select an existing entry or          *
     *                          a complete row from the table as an     *
     *                          associative array                       *
     *                                                                  *
     *  Returns object containing a description of a book               *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $connection;

        if (is_null($connection))
        {
            throw new Exception('Book::constructor: " .
                            "No connection to database server');
        }
        $needInsert             = false;
        $this->table            = 'Books';

        // support undocumented shorthand call
        if (is_string($parms) &&
            strlen($parms) >= 10 && 
            ctype_digit($parms))
            $parms              = array('isbn'  => $parms);

        // validate parameters
        if (is_array($parms))
        {           // valid parm type
            if (count($parms) < count(self::$initRow))
            {           // search parameters
                $isbn           = 'not supplied';
                $where          = '';
                $and            = 'WHERE ';
                $sqlParms       = array();
                foreach($parms as $key => $value)
                {               // loop through parameters
                    switch(strtolower($key))
                    {           // act on key fields
                        case 'isbn':
                        {       // book number
                            $isbn               = $value;
                            $where              .= $and . "ISBN=:isbn";
                            $sqlParms['isbn']   = $isbn;
                            $and                = ' AND ';
                            break;
                        }       // book number

                    }           // act on key fields
                }               // loop through parameters

                $query                  = "SELECT * FROM Books $where";

                // query the database
                $stmt                   = $connection->prepare($query);
                $queryText              = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd       = $queryText;
                if ($stmt->execute($sqlParms))
                {       // success
                    if ($debug)
                        $warn       .= "<p>Book::__construct: " . __LINE__ . 
                               " query='$queryText'</p>\n";

                    $result             = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) == 1)
                    {               // existing record
                        $dbrow          = $result[0];
                        $needInsert     = false;
                    }               // existing record
                    else
                    if (count($result) == 0)
                    {               // create a new record
                        // set defaults
                        $dbrow          = self::$initRow;
                        $dbrow['isbn']  = $isbn;
                        $dbrow['title'] = "Book: '$isbn'";
                        $needInsert     = true;
                    }               // create new record
                    else
                    {               // error performing query
                        $this->errors = "Book::__construct: '$queryText' " .
                            "returns " . count($result) . " records";
                    }               // error performing query
                }                   // success
                else
                {       // error performing query
                    $this->errors   = "Book::__construct: '$queryText' " .
                                print_r($stmt->errorInfo(),true);
                }       // error performing query
            }           // search parameters
            else
            {           // build from existing record
                $dbrow                  = $parms;
                $needInsert             = false;
            }           // build from existing record
        }           // array parameter
        else
        {           // unsupported parameter type
            $this->errors       = "Book::__construct: " .
                        "parameter must be an array: is " .
                        gettype($parms);
        }           // unsupported parameter type


        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Books');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Book Record constructed:');
    }       // Book::__construct

    /********************************************************************
     *  Book::getName                                                   *
     *                                                                  *
     *  Get the title of the Book.                                      *
     *                                                                  *
     *  Returns:                                                        *
     *      Title of the book as a string.                              *
     ********************************************************************/
    function getName($options   = '')
    {
        return $this->row['title'];
    }       // Book::getName

    /********************************************************************
     *  Book::__toString                                                *
     *                                                                  *
     *  Implement conversion to string.                                 *
     ********************************************************************/
    function __toString()
    {
        return  $this->row['title'];
    }       // Book::__toString

}       // class Book
