<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  Census1901Sched2.inc                                                *
 *                                                                      *
 *  Definition of a class representing a single line from the index     *
 *  table for images of the Address Schedule 2 of the 1901 Census of    *
 *  Canada,  This class provides access to the information in a row     *
 *  of the table Census1901S2.                                          *
 *                                                                      *
 *  CREATE TABLE `Census1901S2` (                                       *
 *      `ID`            INT(11) NOT NULL AUTO_INCREMENT,                *
 *      `DistrictName`  VARCHAR(100),                                   *
 *      `DistrictNumber` DECIMAL(17,1),                                 *
 *      `SubDistName`   VARCHAR(100),                                   *
 *      `SubDistId`     VARCHAR(10) DEFAULT NULL,                       *
 *      `Division`      VARCHAR(10),                                    *
 *      `PageNum`       VARCHAR(100),                                   *
 *      `ImageURL`      VARCHAR(255),                                   *
 *      `Reel`          VARCHAR(10),                                    *
 *      `Province`      CHAR(2),                                        *
 *      PRIMARY KEY (`ID`),                                             *
 *      KEY `index1` (`DistrictNumber`,                                 *
 *                      `SubDistId`,                                    *
 *                      `Division`,                                     *
 *                      `PageNum`) )                                    *
 *      ENGINE=InnoDB CHARSET=utf8mb4                                   *
 *                                                                      *
 *  History:                                                            *
 *      2021/03/23      created                                         *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';

/************************************************************************
 *  class Census1901Sched2                                              *
 *                                                                      *
 *  Definition of a class recording information about the location      *
 *  of images of schedule 2 of the 1901 census of Canada.               *
 ************************************************************************/

class Census1901Sched2 extends Record
{
    /********************************************************************
     *  Census1901Sched2::$initRow                                      *
     *                                                                  *
     *  Default initial row                                             *
     ********************************************************************/
    protected static $initRow   = array(
                'id'                    => 0,
                'districtname'          => '',
                'districtnumber'        => 0,
                'subdistname'           => '',
                'subdistid'             => '',
                'division'              => '',
                'pagenum'               => '',
                'imageurl'              => '',
                'reel'                  => '',
                'province'              => '');

    /********************************************************************
     *  Census1901Sched2::$info                                         *
     *                                                                  *
     *  Description of record class                                     *
     ********************************************************************/
    protected static    $info   = array(
                'table'     => 'Census1901S2',
                'name'      => 'Census1901Sched2',
                'prime'     => 'id',
                'srvmg'     => true,
                'fldcount'  => 10,
                'order'     => 'districtnumber,subdistid,division,pagenum',
                'classname' => 'Census1901Sched2');

    /********************************************************************
     *  Census1901Sched2::$translate                                    *
     *                                                                  *
     *  standard alternative field name identifiers                     *
     ********************************************************************/
    protected static $translate = array();

    /********************************************************************
     *  Census1901Sched2::distObj                                       *
     *                                                                  *
     ********************************************************************/
    private $distObj    = null;

    /********************************************************************
     *  function Census1901Sched2::__construct                          *
     *                                                                  *
     *  Construct an instance of Census1901Sched2 based upon the        *
     *  parameters.  This instance may or may not be synchronized with  *
     *  the database.                                                   *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array of parameters              *
     *                  identifying the specific row.                   *
     *                   'DistrictNumber'   district identifier         *
     *                   'SubDistId'        sub-district identifier     *
     *                   'Division'         division identifier         *
     *                   'PageNum'          page number                 *
     *                                                                  *
     *  Returns:                                                        *
     *      object containing a information about a page schedule 2     *
     ********************************************************************/
    function __construct($parms)
    {
        global $debug;
        global $warn;
        global $msg;
        global $connection;

        $dbrow              = self::$initRow;
        $needInsert         = false;
        $id                 = null;
        $distID             = null;
        $subDistID          = null;
        $div                = null;
        $page               = null;

        $diagtext           = "<p>Census1901Sched2::__construct(\$parms=";
        $comma              = '[';
        foreach($parms as $key => $value)
        {               // loop through all parms
            $diagtext       .= "$comma$key=";
            if (is_numeric($value)) 
                $diagtext   .= $value;
            else
            if (is_string($value)) 
                $diagtext   .= "'" . htmlspecialchars($value) . "'";
            else
                $diagtext   .= htmlspecialchars(print_r($value, true));
            $comma          = ',';

            switch(strtolower($key))
            {           // act on identification parms
                case 'id':
                    if (ctype_digit($value))
                        $id         = $value;
                    break;

                case 'districtnumber':
                    if (preg_match('/^\d+(.5|)$/', $value))
                        $distID     = $value;
                    break;

                case 'subdistid':
                    $subDistId      = $value;
                    break;

                case 'division':
                    $div            = $value;
                    break;

                case 'pagenum':
                    if (ctype_digit($value))
                        $page       = $value;
                    break;

                case 'districtname':
                case 'subdistname':
                case 'imageurl':
                case 'reel':
                case 'province':
                    break;

                default:
                    $this->msg      .= "Unexpected field name '$key'. ";
                    break;

            }           // act on identification parms
        }               // loop through all parms
        if ($debug)
            $warn                   .= $diagtext;

        if (strlen($this->msg) > 0)
            return;

        if (count($parms) == count(self::$initRow))
        {               // complete record supplied
            $dbrow                  = $parms;
            $this->needInsert       = $id == 0;
        }               // complete record supplied
        else
        if ($id)
        {               // request by record number
            $query          = "SELECT * FROM Census1901S2 WHERE id=:id";
            $sqlParms           = array('id'        => $id);
            $stmt               = $connection->prepare($query);
            $queryText          = debugPrepQuery($query, $sqlParms);
            $this->lastSqlCmd   = $queryText;
            if ($stmt->execute($sqlParms))
            {               // successful query
                if ($debug)
                    $warn .= "<p>$queryText</p>\n";

                $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0)
                {           // existing record
                    $dbrow                  = $result[0];
                    $needInsert             = false;
                }           // existing record
                else
                {           // create a new record
                    $dbrow                  = self::$initRow;
                    $needInsert             = true;
                    $this->msg              +=
                                "ID=$id does not match existing record. ";
                }           // create a new record
            }               // successful query
            else
            {               // error performing query
                $this->msg      .= "Census1901Sched2::__construct: '$queryText' " .
                                    print_r($stmt->errorInfo(),true) . '. ';
            }               // error performing query
        }
        else
        if ($distID && $subDistID && $page)
        {                   // request by enumeration division and page
            if (!$div)
                $div            = '';
            $subparms           = array('sd_census'     => 'CA1901',
                                        'sd_distId'     => $distId,
                                        'sd_id'         => $subDistID,
                                        'sd_div'        => $div,
                                        'sd_sched'      => 1);
            $subdist            = new SubDistrict($subparms);
            if ($subdist->isExisting())
            {               // good subdistrict
                $query          = "SELECT * FROM Census1901S2 WHERE " .
                    "DistrictNumber=:distid AND SubDistId=:subdistid AND Division=:div AND PageNum=:page";
                $sqlParms           = array('distid'        => $distId,
                                            'subdistid'     => $subDistId,
                                            'div'           => $div,
                                            'page'          => $page);
                $stmt               = $connection->prepare($query);
                $queryText          = debugPrepQuery($query, $sqlParms);
                $this->lastSqlCmd   = $queryText;
                if ($stmt->execute($sqlParms))
                {               // successful query
                    if ($debug)
                        $warn .= "<p>$queryText</p>\n";
    
                    $result         = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result) > 0)
                    {           // existing record
                        $dbrow                  = $result[0];
                        $needInsert             = false;
                    }           // existing record
                    else
                    {           // create a new record
                        $dbrow                  = self::$initRow;
                        $dbrow['districtnumber']= $distId;
                        $dbrow['subdistid']     = $subDistId;
                        $dbrow['division']      = $div;
                        $dbrow['page']          = $page;
                        $needInsert             = true;
                    }           // create a new record
                }               // successful query
                else
                {               // error performing query
                    $this->msg      .= "Census1901Sched2::__construct: '$queryText' " .
                                        print_r($stmt->errorInfo(),true) . '. ';
                }               // error performing query
            }               // good subdistrict
            else
                $this->msg      .= "Invalid SubDistrict identifiers. ";
        }                   // request by enumeration division and page   

        // invoke constructor of base class
        parent::__construct($dbrow,
                            'Census1901Sched2');
        $this->needInsert   = $needInsert;

        // diagnostic output if debug is set
        $this->dump('Census1901S2 Record constructed:');

        $msg                .= $this->msg;
    }       // function Census1901Sched2::__construct

}       // class Census1901Sched2
