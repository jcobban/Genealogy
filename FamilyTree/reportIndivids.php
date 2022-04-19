<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  reportIndivids.php                                                  *
 *                                                                      *
 *  Display a report of individuals matching a search initiated by      *
 *  reqReportIndivids.php.                                              *
 *                                                                      *
 *  Parameters (passed by method="get"):                                *
 *      fields      array, or comma-separated list, of field names to   *
 *                  include                                             *
 *      orderby     array, or comma-separated list, of sort field names *
 *      table       name of database table                              *
 *      limit       number of rows to display                           *
 *      <anything>  field name and value for limiting                   *
 *                                                                      *
 *  History:                                                            *
 *      2011/02/02      created                                         *
 *      2012/01/13      change class names                              *
 *      2012/02/09      add support for searching for birth or          *
 *                      death year                                      *
 *      2012/04/07      add forward and back paging links               *
 *      2012/07/26      change genOntario.html to genOntario.php        *
 *      2012/08/11      add target to link to individual                *
 *      2012/12/22      make date limits subject to owner's             *
 *                      level of authorization                          *
 *      2013/02/28      bug in date privacy limit support               *
 *                      honor record ownership if request includes IDIR *
 *      2013/06/01      use pageTop and pageBot to standardize          *
 *                      appearance                                      *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/03/10      use CSS for form layout instead of tables       *
 *      2014/03/25      unclosed <div>s                                 *
 *      2014/04/21      support christen and buried dates               *
 *                      support event place and date                    *
 *      2014/09/19      use LegacyLocation::getLocations to get list    *
 *                      of IDLRs to match a location against            *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *      2015/06/30      support join on tblER                           *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2015/12/10      tblER join on field IDIR nor IDIME              *
 *      2016/01/19      add id to debug trace                           *
 *                      include http.js before util.js                  *
 *      2017/08/16      legacyIndivid.php renamed to Person.php         *
 *      2017/09/09      change class LegacyLocation to class Location   *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/04      use class RecordSet instead of getLocations     *
 *      2018/11/19      change Helpen.html to Helpen.html               *
 *      2020/12/20      use FtTemplate                                  *
 *                      improve validation of parameters                *
 *                      support external table names such as Persons    *
 *      2021/05/03      correct handling of requests with multiple      *
 *                      tables                                          *
 *      2022/04/18      construct page from template                    *
 *                      fix handling of order                           *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get the parameters
$fields                     = '';
$fieldstext                 = null;
$orderby                    = '';
$ordertext                  = null;
$and                        = '';
$offset                     = 0;
$limit                      = 20;
$search                     = '';
$table                      = 'tblIR';
$badTableName               = null;

// master user has access to all records even if not explicitly
// granted by the creator   
if (canUser('all'))
{
    $bprivlim               = 9999;
    $dprivlim               = 9999;
}
else
{
    $bprivlim               = intval(date('Y')) - 105;
    $dprivlim               = intval(date('Y')) - 72;
}

// process input parameters and build the search parameter list
$lang                       = 'en';
$recParms                   = array();
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by method=get
    $parmsText          = "<p class='label'>\$_GET</p>\n" .
                          "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                   // loop through input parameters
        if (is_string($value))
            $safevalue      = htmlspecialchars($value);
        else
        if (is_int($value))
            $safevalue      = $value;
        else
            $safevalue      = htmlspecialchars(var_export($value,true));
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n";
        if (is_string($value))
            $value          = trim($value); 
        switch(strtolower($key))
        {               // switch on parameter name
            case 'fields':
            {
                // $fields becomes a comma separated list of names
                // $fldarray is an array of names
                if (is_array($value))
                {
                    $fldarray           = $value;
                    $comma              = '';
                    foreach($value as $i => $field)
                    {
                        if (preg_match('/^([a-zA-Z0-9.]*|`[a-zA-Z0-9.`]*`)$/',
                                        $field)) 
                        {
                            $fields     .= $comma . $field;
                            $search     .= "&amp;fields[]=" . $field;
                            $comma      = ',';
                        }
                        else
                        {
                            $fieldstext = $safevalue;
                            break;
                        }
                    }
                }
                else
                if (is_string($value) && 
                    preg_match('/^[a-zA-Z0-9`,]*$/', $value))
                {
                    $fields         = $value;
                    $search         .= "&amp;fields=" . urlencode($fields);
                    $fldarray       = explode(',', $fields);
                }
                else
                    $fieldstext     = $safevalue;
                break;
            }

            case 'orderby':
            {
                // $orderby becomes a comma separated list of names
                if (is_array($value))
                {
                    $comma      = '';
                    foreach($value as $i => $field)
                    {
                        if (preg_match('/^([a-zA-Z0-9.]*|`[a-zA-Z0-9.`]*`)$/', 
                                        $field))
                        {
                            $orderby    .= $comma . $field;
                            $search     .= "&amp;orderby[]=" . $field;
                            $comma      = ',';
                        }
                        else
                        {
                            $ordertext  = $safevalue;
                            break;
                        }
                    }
                }
                else
                if (is_string($value) && 
                    preg_match('/^[a-zA-Z0-9`,]*$/', $value))
                {
                    $orderby        = $value;
                    $search         .= "&amp;orderby=" . urlencode($value);
                }
                else
                    $ordertext      = $safevalue;
                break;
            }

            case 'table':
            {
                $info           = Record::getInformation($value);
                if (!is_null($info))
                {
                    $table          = $info['table'];
                    $search         .= "&amp;table=" . urlencode($value);
                }
                else
                    $badTableName   = $value;
                break;
            }

            case 'limit':
            {
                if (ctype_digit($value))
                    $limit      = $value;
                break;
            }

            case 'offset':
            {
                if (ctype_digit($value))
                    $offset     = $value;
                break;
            }

            case 'submit':
            case 'Submit':
            {       // ignore the value of the submit button
                break;
            }       // ignore the value of the submit button

            case 'gender':
            {
                if (ctype_digit($value) && $value < 3)
                {
                    $recParms[$key] = $value;
                    $search         .= "&amp;$key=$value";
                }
                else
                    $gendertext     = $safevalue;
                break;
            }

            case 'idlrbirth':
            case 'idlrchris':
            case 'idlrdeath':
            case 'idlrburied':
            case 'idlrevent':
            {
                if (strlen($value) > 0)
                {   // value specified
                    if (substr($table,0,5) == 'tblIR')
                        $table  = 'tblIR JOIN tblER ON tblER.IDIR=tblIR.IDIR';
                    else
                        $table  = 'tblER';
                    $getParms       = array('Location'  => $value);
                    $locations      = new RecordSet('Locations', 
                                                    $getParms);
                    if ($locations->count() > 0)
                    {   // at least one IDLR
                        $idlrs          = array();
                        foreach($locations as $idlr => $loc)
                        {
                            $idlrs[]    = $idlr;
                        }   // loop through all matching IDLRs
                        $recParms['tblER.IDLREvent']    = $idlrs;
                    }   // at least one IDLR
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }       // location keys

            case 'birthdate':
            {
                if (strlen($value) > 0)
                {   // value specified
                    // for the moment only support year
                    $pos    = preg_match('/[0-9]{4}/', $value, $matches);
                    if ($pos > 0)
                    {   // year found in date
                        $recParms["BirthSD"]    = array(">=" . $matches[0] . "0000",
                                    '<' . ($matches[0]+1) . '0000');
                    }   // year found in date
                    else
                        $msg    .= "Birth Date '$value' is invalid. ";
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }       // birth date

            case 'chrisdate':
            {
                if (strlen($value) > 0)
                {   // value specified
                    // for the moment only support year
                    $pos    = preg_match('/[0-9]{4}/', $value, $matches);
                    if ($pos > 0)
                    {   // year found in date
                        $recParms["ChrisSD"]    = array(">=" . $matches[0] . "0000",
                                    '<' . ($matches[0]+1) . '0000');
                    }   // year found in date
                    else
                        $msg    .= "Christening Date '$value' is invalid. ";
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }       // chris date

            case 'deathdate':
            {
                if (strlen($value) > 0)
                {   // value specified
                    // for the moment only support year
                    $pos    = preg_match('/[0-9]{4}/', $value, $matches);
                    if ($pos > 0)
                    {   // year found in date
                        $recParms["DeathSD"]    = array(">=" . $matches[0] . "0000",
                                    '<' . ($matches[0]+1) . '0000');
                    }   // year found in date
                    else
                        $msg    .= "Death Date '$value' is invalid. ";
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }       // death date

            case 'burieddate':
            {
                if (strlen($value) > 0)
                {   // value specified
                    // for the moment only support year
                    $pos    = preg_match('/[0-9]{4}/', $value, $matches);
                    if ($pos > 0)
                    {   // year found in date
                        $recParms["BuriedSD"]   = array(">=" . $matches[0] . "0000",
                                    '<' . ($matches[0]+1) . '0000');
                    }   // year found in date
                    else
                        $msg    .= "Buried Date '$value' is invalid. ";
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }       // buried date

            case 'eventdate':
            {
                if (strlen($value) > 0)
                {   // value specified
                    if (substr($table,0,5) == 'tblIR')
                        $table      =
                        'tblIR JOIN tblER ON tblER.IDIME=tblIR.IDIR';
                    else
                        $table      = 'tblER';
                    // for the moment only support year
                    $pos            = preg_match('/[0-9]{4}/', $value, $matches);
                    if ($pos > 0)
                    {   // year found in date
                        $recParms["tblER.EventSD"]= array(">=" . $matches[0] . "0000",
                                    '<' . ($matches[0]+1) . '0000');
                    }   // year found in date
                    else
                        $msg        .= "Event Date '$value' is invalid. ";
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }       // event date

            case 'description':
            {
                if (strlen($value) > 0)
                {   // value specified
                    if (substr($table,0,5) == 'tblIR')
                        $table      =
                        'tblIR JOIN tblER ON tblER.IDIR=tblIR.IDIR';
                    else
                        $table      = 'tblER';
                    $recParms['tblER.Description']  = $value;
                }   // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }           // event date

            case 'debug':
            {           // handled by common code
                break;
            }           // handled by common code

            case 'lang':
            {           // user requested language
                $lang       = FtTemplate::validateLang($value);
                break;
            }           // user requested language
                
            default:
            {
                if (strlen($value) > 0)
                {       // value specified
                    $recParms['tblIR.' . $key]  = $value;
                }       // value specified
                $search     .= "&amp;$key=" . urlencode($value);
                break;
            }           // other fields
        }               // switch on parameter name
    }                   // loop through input parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}                       // invoked by method=get
$template               = new FtTemplate("reportIndivids$lang.html");
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];

if (count($recParms) > 0 && strlen($fields) > 0)
{       // some limits on reply
    if (strlen($orderby) > 0)
        $recParms['order']      = $orderby;
    else
    {
        if (count($fldarray) > 1)
            $recParms['order']  = $fldarray[0] . ',' . $fldarray[1];
        else
            $recParms['order']  = $fldarray[0];
    }


    $recParms['offset']         = $offset;
    $recParms['limit']          = $limit;
    $template->set('offset', $offset);
    $template->set('limit', $limit);
    $template->set('search', $search);

    // to avoid a long wait, first check to see how many responses there are
    $set        = new RecordSet($table,
                                $recParms,
                                $fields);
    $info       = $set->getInformation();
    $count      = $info['count'];
    $query      = $info['query'];
    if ($count == 0)
        $count  = 'No';
    if ($debug)
        $warn   .= "<p>reportIndivids.php: " . __LINE__ . 
                    "'" . $query . "', " . 
                    "Returns $count records</p>\n";
}       // some limits on reply
else
{       // missing mandatory parameters
    if (strlen($fields) == 0)
        $msg    .= "Missing mandatory parameter 'fields'. ";
    $msg    .= "Missing restrictions on which records to return. ";
}       // missing mandatory parameters

if (strlen($msg) == 0)
{       // no errors detected
    if ($count > 0)
    {       // query issued
        $prevoffset = max(0, $offset-$limit);
        $nextoffset = min($count, $offset+$limit);
        $template->set('prevoffset',        $prevoffset);
        $template->set('nextoffset',        $nextoffset);
        $template->set('count',             $count);
        if ($offset <= 0)
            $template['prevLink']->update();
        if ($offset > $count - $limit)
            $template['nextLink']->update();
        $header     = array();
        foreach($fldarray as $i => $field)
        {
            if (substr($field, 0, 6) == 'tblIR.' ||
                substr($field, 0, 6) == 'tblER.')
                $fldname    = substr($field, 6);
            else
                $fldname    = $field;
            if (substr($fldname, 0, 4) == 'IDLR')
                $fldname    = substr($fldname, 4) . " Place";
            $header[]       = array('i'         => $i,
                                    'fldname'   => $fldname);
        }   // loop through all fields
        $template['colHeader$i']->update($header);

        // display the results
        $even           = false;
        $body           = '';
        $ctemplate      = $template['valueCell$i'];
        $ctemplateBody  = $ctemplate->outerHTML;
        $rownum         = 0;
        foreach($set as $row)
        {
            $rownum++;
            if ($even)
                $class  = 'even left';
            else
                $class  = 'odd left';
            $contents   = array();

            foreach($fldarray as $i => $field)
            {               // loop through all fields in row
                if (substr($fldarray[$i], 0, 6) == 'tblIR.' ||
                    substr($fldarray[$i], 0, 6) == 'tblER.')
                    $fldname    = strtolower(substr($fldarray[$i], 6));
                else
                    $fldname    = strtolower($fldarray[$i]);
                $value          = $row[$fldname];
                $isOwner        = false;
                switch($fldname)
                {           // format depending upon field name
                    case 'idir':
                    {
                        $class      = 'button';
                        $value      = "<a href='Person.php?id=$value' target='indiv'>View</a>";
                        if ($bprivlim == 9999)
                            $isOwner    = true;
                        else
                            $isOwner    = RecOwner::chkOwner($value, 'tblIR');
                        break;
                    }       // hyperlink to details
    
                    case 'birthd':
                    case 'chrisd':
                    case 'baptismd':
                    case 'confirmationd':
                    {
                        $date       = new LegacyDate($value);
                        if ($isOwner)
                            $value  = $date->toString(9999);
                        else
                            $value  = $date->toString($bprivlim);
                        break;
                    }       // dates near birth
    
                    case 'deathd':
                    case 'buriedd':
                    case 'initiatoryd':
                    case 'endowd':
                    case 'eventd':
                    {
                        $date       = new LegacyDate($value);
                        if ($isOwner)
                            $value  = $date->toString(9999);
                        else
                            $value  = $date->toString($dprivlim);
                        break;
                    }       // dates during lifetime
    
                    case 'idlrbirth':
                    case 'idlrchris':
                    case 'idlrdeath':
                    case 'idlrburied':
                    case 'idlrevent':
                    {
                        $loc    = new Location(array('idlr' => $value));
                        $value  = $loc->toString();
                        break;
                    }       // location keys
    
                    case 'gender':
                    {
                        switch ($value)
                        {
                            case 0:
                            {
                                $value  = 'Male';
                                break;
                            }
        
                            case 1:
                            {
                                $value  = 'Female';
                                break;
                            }
        
                            default:
                            {
                                $value  = 'Unknown';
                                break;
                            }
                        }   // switch on value
                        break;
                    }       // case 'gender'
    
                    case 'tag1':
                    case 'tag2':
                    case 'tag3':
                    case 'tag4':
                    case 'tag5':
                    case 'tag6':
                    case 'tag7':
                    case 'tag8':
                    case 'tag9':
                    case 'taggroup':
                    case 'taganc':
                    case 'tagdec':
                    case 'savetag':
                    case 'qstag':
                    case 'srchtagigi':
                    case 'srchtagrg':
                    case 'srchtagfs':
                    case 'rgexclude':
                    case 'remindertag':
                    {
                        switch ($value)
                        {
                            case 0:
                            {
                                $value  = 'false';
                                break;
                            }
        
                            case 1:
                            {
                                $value  = 'true';
                                break;
                            }
        
                            default:
                            {
                                $value  = 'Unknown';
                                break;
                            }
                        }   // switch on value
                        break;
                    }       // flags
    
                }           // format depending upon field name

                $contents[] = array('i'         => $i,
                                    'class'     => $class,
                                    'value'     => $value);
            }               // loop through all fields in row
            $ttemplate      = new Template($ctemplateBody);
            $ttemplate['valueCell$i']->update($contents);
            $body           .= "<tr id=\"datarow$rownum\">\n" .
                                $ttemplate->compile() .
                               "</tr>\n";
            $even   = !$even;
        }                   // loop through results
        $template['datarow$rownum']->update($body);
    }                       // query issued
    else
    {
        $template['paging']->update();
        $template['dataTable']->update();
    }
}                           // no errors detected
else
{
    $template['paging']->update();
    $template['dataTable']->update();
}

$template->display();
