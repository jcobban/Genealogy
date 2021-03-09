<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  PageSet.inc                                                         *
 *                                                                      *
 *  Definition of a class representing a set of pages of a census.      *
 *  This extension exists such that asking for all of the pages in      *
 *  an enumeration division will always return an instance of Page      *
 *  for every page in the                                               *
 *  division even if only some of the records have actually been        *
 *  created by the transription process.                                *
 *                                                                      *
 *  History:                                                            *
 *      2018/02/13      created                                         *
 *      2020/10/10      remove prefix from field names                  *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/SubDistrictSet.inc';

/************************************************************************
 *  class PageSet                                                       *
 *                                                                      *
 *  Definition of a class representing a set of pages of a census.      *
 ************************************************************************/

class PageSet extends RecordSet
{
    /********************************************************************
     *  $parmsSet                                                       *
     *                                                                  *
     *  This is a bit mask indicating which parameters have been        *
     *  passed to the constructor.                                      *
     *                                                                  *
     *           1      Census Identifier                               *
     *           2      District Identifier                             *
     *           4      SubDistrict Identifier                          *
     *           8      Division Identifier                             *
     *          16      Schedule Identifier                             *
     *          32      any other field                                 *
     ********************************************************************/
    private $parmsSet       = 0;

    /********************************************************************
     *  $subDistrict                                                    *
     *                                                                  *
     *  Instance of SubDistrict                                         *
     ********************************************************************/
    private $subDistrict    = null;

    /********************************************************************
     *  function PageSet::__construct                                   *
     *                                                                  *
     *  Create an instance of the class PageSet                         *
     *                                                                  *
     *  Parameters:                                                     *
     *      $parameters         associative array of fieldname, value   *
     *                          pairs to define the subset of the       *
     *                          records in the SQL table that are       *
     *                          represented by this instance.  The      *
     *                          parameters 'offset' and 'limit'         *
     *                          are reserved to set the OFFSET and      *
     *                          LIMIT clauses.  Use '`offset`', and     *
     *                          '`limit`' to reference fields with      *
     *                          those names. This may be null.          *
     *                                                                  *
     *  Examples:                                                       *
     *  $set        = new PageSet(array('census'    => 'CA1891',        *
     *                                  'distid'    => 89,              *
     *                                  'sdid'      => 'A',             *
     *                                  'division'  => 1,               *
     *                                  'sched'     => 1));             *
     *                                                                  *
     *  $subdist = new SubDistrict(array('census'   => 'CA1891',        *
     *                                   'distid'   => 89,              *
     *                                   'sdid'`    => 'A',             *
     *                                   'division' => 1,               *
     *                                   'sched'    => 1));             *
     *  $set        = new PageSet(array('sd'    => $subdist));          *
     *                                                                  *
     *  Throws:                                                         *
     *      An exception for invalid parameters.                        *
     ********************************************************************/
    function __construct($parms = array())
    {
        $limit                  = PHP_INT_MAX;
        foreach($parms as $field => $value)
        {
            if (strtolower($field) == 'limit')
                $limit          = $value;
        }
        if ($limit > 100)
            $parms['limit']     = 50;
        parent::__construct('Pages', $parms);
    }       // PageSet::__construct

    /********************************************************************
     *  function PageSet::getWhere                                      *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
     *                                                                  *
     *  $this->parms    associative array of field/value combinations   *
     *                  the field names are interpreted according to    *
     *                  the standard translate table.  Note that        *
     *                  because the parameter is an associative array   *
     *                  that only one value can be specified for each   *
     *                  field name.                                     *
     *  $this->sqlParms     construct array of parms for PDO execute    *
     *  $this->limit        return value of limit parameter             *
     *  $this->offset       return value of offset parameter            *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing WHERE clause                              *
     *      A zero length string is returned if the input array is      *
     *      empty, otherwise the string starts with the word WHERE      *
     *                                                                  *
     *  Side-Effects:                                                   *
     *      Issues with the supplied parms are reported by adding       *
     *      warning messages onto the global variable $warn.            *
     ********************************************************************/
    protected function getWhere()
    {
        global  $debug;
        global  $warn;
    
        if ($debug)
            $warn               .= '<p>PageSet::getWhere: ' . 
                                   print_r($this->parms, true) . "</p>\n";
        $where                  = '';
        $and                    = 'WHERE ';
        $this->parmsSet         = 0;
        $censusObj              = null;
        $initRow                = array();
        $districtObj            = null;
        $subDistrictObj         = null;
        if (!is_array($this->sqlParms))
            $this->sqlParms     = array();
    
        // create WHERE clause
        foreach($this->parms as $fldname => $value)
        {
            $fieldLc            = Page::getRealFieldName($fldname);
            switch($fieldLc)
            {           // act on specific fields
                case 'census':
                {       // census identifier field
                    if (is_string($value))
                    {
                        $censusId   = $value;
                        $censusObj  = new Census(array('censusid'=> $value));
                    }
                    else
                    if ($value instanceof Census)
                    {
                        $censusObj      = $value;
                        $censusId       = $censusObj->get('censusid');
                    }
                    else
                    {
                        $warn       .= "<p>PageSet::getWhere: " . __LINE__ .
                                        " Unsupported Census=" .
                                        print_r($value,true) . "</p>\n";
                        break;
                    }
                    $info               = Record::getInformation('Pages');
                    if ($info)
                        $initRow        = $info['initrow'];
                    $this->parmsSet     |= 1;
                    $where              .= "$and`Census`=:censusid";
                    $this->sqlParms['censusid'] = $censusId;
                    $and                = ' AND ';
                    if (!$censusObj->isExisting())
                        $warn       .= "<p>PageSet::getWhere: " . __LINE__ .
                                " Unsupported Census='$value'</p>\n";
                    break;
                }       // census field
        
                case 'distid':
                {       // district id field
                    if (is_string($value))
                    {
                        $distId         = $value;
                        $parms          = array('census'=>$censusObj,
                                                'id'    => $value);
                        $districtObj    = new District($parms);
                    }
                    else
                    if ($value instanceof District)
                    {
                        $districtObj    = $value;
                        $distId         = $districtObj->get('id');
                    }
                    else
                    {
                        $warn   .= "<p>PageSet::getWhere: " . __LINE__ .
                                    " Unsupported DistId=" . 
                                    print_r($value, true) . "</p>\n";
                        break;
                    }
                    $this->parmsSet     |= 2;
                    $where              .= "$and`DistId`=:distid";
                    $this->sqlParms['distid']   = $distId;
                    $and                = ' AND ';
                    if (!$districtObj->isExisting())
                        $warn   .= "<p>PageSet::getWhere: " . __LINE__ .
                                    " Unsupported DistId='$distId'</p>\n";
                    break;
                }       // district id field
        
                case 'sdid':
                {       // sub-district id field
                    if (is_string($value))
                    {
                        $sdId               = $value;
                    }
                    else
                    if ($value instanceof SubDistrict)
                    {
                        $subDistrictObj     = $value;
                        $sdId               = $subDistrictObj->get('id');
                    }
                    else
                    {
                        $warn   .= "<p>PageSet::getWhere: " . __LINE__ .
                                " Unsupported SdId=" . 
                                print_r($value, true) . "</p>\n";
                        break;
                    }
                    $this->parmsSet     |= 4;
                    $where              .= "$and`SdId`=:sdid";
                    $this->sqlParms['sdid'] = $sdId;
                    $and                = ' AND ';
                    break;
                }       // sub-district id field
        
                case 'div':
                {       // division id field
                    if (is_string($value) || is_int($value))
                    {
                        $div            = $value;
                    }
                    else
                    {
                        $warn   .= "<p>PageSet::getWhere: " . __LINE__ .
                                " Unsupported `Div`=" . 
                                print_r($value, true) . "</p>\n";
                    break;
                    }
                    $this->parmsSet     |= 8;
                    $where          .= "$and`Div`=:div";
                    $this->sqlParms['div']  = $div;
                    $and            = ' AND ';
                    break;
                }       // division id field
        
                case 'sched':
                {       // schedule id field
                    if (is_string($value) || is_int($value))
                    {
                        $sched          = $value;
                    }
                    else
                    {
                        $warn   .= "<p>PageSet::getWhere: " . __LINE__ .
                                " Unsupported Sched=" . 
                                print_r($value, true) . "</p>\n";
                    }
                    $this->parmsSet     |= 16;
                    $where              .= "$and`Sched`=:sched";
                    $this->sqlParms['sched']    = $sched;
                    $and                = ' AND ';
                    break;
                }       // schedule if field
        
                case 'pagenum':
                {
                    $this->parmsSet             |= 32;
                    $this->sqlParms[$fieldLc]   = $value;
                    $where                  .= "$and`$fieldLc`>=:$fieldLc";
                    $and                    = ' AND ';
                    break;
                }

                case 'population':
                case 'image':
                case 'transcriber':
                case 'proofreader':
                {       // compare for equal
                    $this->parmsSet |= 32;
                    if (is_string($initRow[$fieldLc]))
                        $expression = $this->getWherePattern($fieldLc,
                                                             $value);
                    else
                    if (is_int($initRow[$fieldLc]))
                    {
                        $expression             = "`$fieldLc`=:$fieldLc";
                        $this->sqlParms[$fieldLc]   = $value;
                    }
                    else
                        $expression = $this->getWhereCommon($fieldLc,
                                                            $value);
                    if (strlen($expression) > 0)
                    {
                        $where      .= $and . $expression;
                        $and        = ' AND ';
                    }
                    break;
                }       // valid field namess
        
                default:
                {
                    $warn   .= "<p>PageSet::getWhere: " . __LINE__ .
                        " Unrecognized search parameter `$fldname`.</p>";
                    break;
                }
        
            }           // act on specific fields
        }               // loop through all parameters
    
        if (!is_null($subDistrictObj))
        {               // SubDistrict instance passed
            if (($this->parmsSet & 1) == 0)
            {
                $censusId           = $subDistrictObj->get('censusid');
                $where              .= "$and`Census`=:censusid";
                $this->sqlParms['censusid'] = $censusId;
                $and                = ' AND ';
                $this->parmsSet     |= 1;
            }
    
            if (($this->parmsSet & 2) == 0)
            {
                $distId             = $subDistrictObj->get('distid');
                $where              .= "$and`DistId`=:distid";
                $this->sqlParms['distid']   = $distId;
                $and                = ' AND ';
                $this->parmsSet     |= 2;
            }
    
            // SubDistrictId already set
            if (($this->parmsSet & 8) == 0)
            {
                $div                = $subDistrictObj->get('div');
                $where              .= "$and`Div`=:div";
                $this->sqlParms['div']  = $div;
                $and                = ' AND ';
                $this->parmsSet     |= 8;
            }
    
            if (($this->parmsSet & 16) == 0)
            {
                $sched              = $subDistrictObj->get('sched');
                $where              .= "$and`Sched`=:sched";
                $this->sqlParms['sched']= $sched;
                $and                = ' AND ';
                $this->parmsSet     |= 16;
            }
        }               // SubDistrict instance passed
    
        if ($this->parmsSet == 15)
        {               // everything but Sched passed
            $sched                  = 1;    // default
            $where                  .= "$and`Sched`=:sched";
            $this->sqlParms['sched']    = $sched;
            $and                    = ' AND ';
            $this->parmsSet         |= 16;
        }               // everything but Sched passed
    
        $subDists           = null;
        if ($this->parmsSet == 31)
        {           // single sub-district
            if(is_null($subDistrictObj))
                $subDistrictObj = new SubDistrict(
                                          array('census'    => $censusId,
                                                'distid'    => $distId,
                                                'id'        => $sdId,
                                                'div'       => $div,
                                                'sched'     => $sched));
    
            if ($subDistrictObj)
            {
                $this->information['count'] = $subDistrictObj->get('pages');
                $this->subDistrict      = $subDistrictObj;
            }
        }           // single sub-district
        else
        if ($this->parmsSet == 7 || $this->parmsSet == 23)
        {           // single township
            $subDists   = new SubDistrictSet(array('census' => $censusId,
                                                   'distid' => $distId,
                                                   'id'     => $sdId));
        }           // single township
        else
        if ($this->parmsSet == 3 || $this->parmsSet == 19)
        {           // single township
            $subDists   = new SubDistrictSet(array('census' => $censusId,
                                                   'distid' => $distId));
        }           // single township

        if ($subDists)
        {
            $count              = 0;
            foreach($subDists as $subDist)
                $count          += $subDist->get('pages');
            $this->information['count'] = $count;
            $this->subDistrict  = $subDists;
        }
        return $where;
    }       // PageSet::getWhere

    /********************************************************************
     *  function PageSet::rewind                                        *
     *                                                                  *
     *  Part of the implementation of the interface Iterator.           *
     *  Reposition to the first element of the "array".                 *
     *                                                                  *
     *  Returns:                                                        *
     *      first instance of Record in the set                         *
     ********************************************************************/
    public function rewind()
    {
        global  $debug;
        global  $warn;
        global  $msg;
        global  $connection;
    
        // the following executes the query to obtain all of the
        // existing records matching the request
        $firstPage      = parent::rewind();
    
        if ($this->parmsSet == 31)
        {                   // ensure complete set of pages
            $subD                   = $this->subDistrict;
            $imageBase              = $subD['sd_imagebase'];
            $relFrame               = $subD['sd_relframe'];
            $pageTot                = $subD['sd_pages'];
            $page1                  = $subD['sd_page1'];
            $bypage                 = $subD['sd_bypage'];
            $lastpage               = $page1 + $bypage *($pageTot - 1);
            if (count($this->list) < $pageTot)
            {               // not all records yet defined in Pages
                $newPages           = array();
                $currPage           = reset($this->list);
                if ($currPage)
                    $currPageNum    = $currPage->get('page');
                else
                    $currPageNum    = PHP_INT_MAX;
                for ($pageNum = $page1;
                     $pageNum <= $lastpage; 
                     $pageNum += $bypage)
                {           // loop through all pages in sub-district
                    if ($pageNum < $currPageNum)
                    {       // not already defined
                        $image      = $subD->getDefaultImage($pageNum);
                        $pageParms  = array('census'    => $subD['sd_census'],
                                            'distid'    => $subD['sd_distid'],
                                            'sdid'      => $subD['sd_id'],
                                            'division'  => $subD['sd_div'],
                                            'sched'     => '1',
                                            'pagenum'   => $pageNum);
                        $page       = new Page($pageParms);
                        $page->set('image', $image);
                        $newPages[$pageNum] = $page;
                    }       // not already defined
                    else
                    {       // existing Page instance
                        $newPages[$currPageNum] = $currPage;
                        $currPage           = next($this->list);
                        if ($currPage)
                            $currPageNum    = $currPage->get('pagenum');
                        else
                            $currPageNum    = PHP_INT_MAX;
                    }       // existing Page instance
                }           // loop through all pages in sub-district
                $this->list         = $newPages;
                $firstPage          = reset($this->list);
            }               // not all records yet defined in Pages
        }                   // request for all pages in SubDistrict
        return $firstPage;
    }       // function PageSet::rewind

}       // class PageSet 
