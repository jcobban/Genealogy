<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  CountyMarriageReportSet.inc                                         *
 *                                                                      *
 *  Definition of a class representing a set of records of reports      *
 *  of marriages registered in pre-confederation Ontario.               *
 *                                                                      *
 *  History:                                                            *
 *      2018/03/10      created to include statistics on transcriptions *
 *                      and links to the family tree in the result      *
 *      2019/01/06      move to namespace Genealogy                     *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/CountyMarriage.inc';
require_once __NAMESPACE__ . '/CountyMarriageReport.inc';

/************************************************************************
 *  class CountyMarriageReportSet                                       *
 *                                                                      *
 *  Definition of a class implementing a set of pre-confederation       *
 *  reports of registered Marriages within a genealogy database.        *
 ************************************************************************/

class CountyMarriageReportSet extends RecordSet
{
    /********************************************************************
     *  function CountyMarriageReportSet::__construct                   *
     *                                                                  *
     *  Create an instance of a class that describes a set of           *
     *  county or district marriage registrations.                      *
     *                                                                  *
     *  Input:                                                          *
     *      $parms      an associative array for selecting records      *
     *                  to be part of the set.                          *
     *      $order      desired order of the records in a report        *
     *                                                                  *
     *  Returns object containing  set of county marriage records.      *
     *                                                                  *
     *  Throws Exception if unable to complete function                 *
     ********************************************************************/
    function __construct($parms = array(),
                         $order = "M_RegDomain, M_Volume, M_ReportNo")
    {
        $parms['order']     = $order;
        parent::__construct('CountyMarriageReports',
                $parms,
                "CountyMarriageReports.*," .
                "(SELECT COUNT(*) FROM CountyMarriages  where CountyMarriages.m_regdomain=CountyMarriageReports.m_regdomain and CountyMarriages.m_volume=CountyMarriageReports.m_volume and CountyMarriages.m_reportno=CountyMarriageReports.m_reportno) as transcribed ," .
                "(SELECT COUNT(*) FROM CountyMarriages  where CountyMarriages.m_regdomain=CountyMarriageReports.m_regdomain and CountyMarriages.m_volume=CountyMarriageReports.m_volume and CountyMarriages.m_reportno=CountyMarriageReports.m_reportno and CountyMarriages.m_idir>0) as linked ");
    }       // CountyMarriageReportSet::__construct

    /********************************************************************
     *  function CountyMarriageReportSet::getWhere                      *
     *                                                                  *
     *  Given an associative array of parameters for searching the      *
     *  table, return the appropriate SQL WHERE clause.                 *
     *                                                                  *
     *  Returns:                                                        *
     *      string containing WHERE clause                              *
     ********************************************************************/
    protected function getWhere()
    {
        global $warn;

        // construct the WHERE clause
        $where      = '';
        $and        = 'WHERE ';
        foreach($this->parms as $fldname => $value)
        {       // loop through all parameters
            $fieldLc    = CountyMarriage::getRealFieldName($fldname);
            switch($fieldLc)
            {
                case 'm_regdomain':
                {       // text fields full match
                    $where          .= "$and `$fieldLc`=?";
                    $this->sqlParms[]       = $value;
                    $and            = ' AND ';
                    break;
                }       // text fields

                case 'm_volume':
                {       // numeric fields
                    if ((is_int($value) || ctype_digit($value))
                        && $value >= 0)
                    {
                        $where          .= "$and `$fieldLc`=?";
                        $this->sqlParms[]   = $value;
                        $and            = ' AND ';
                    }
                    else
                        $warn   .= "<p>CountyMarriageReportSet::getWhere: " .
                               "Invalid `$fldname` value '$value'.</p>\n";
                    break;
                }       // numeric fields

                default:
                {
                    $warn   .= "<p>CountyMarriageReportSet::getWhere: " .
                               "Unrecognized parameter `$fldname`.</p>\n";
                    break;
                }
            }
        }       // loop through all parameters

        return $where;
    }       // CountyMarriageReportSet::getWhere

}       // class CountyMarriageReportSet
