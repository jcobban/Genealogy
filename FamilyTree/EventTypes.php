<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  EventTypes.php														*
 *																		*
 *  Display a web page containing the records from the Legacy			*
 *  Event Types Table tblET.											*
 *																		*
 *  History:															*
 *		2010/11/30		created											*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/10/13		use LegacyRecord to update database				*
 *		2012/10/22		use LegacyEventType to update database			*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/03		add warning that table is not used				*
 *						add id= attribute to all form fields			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/06/16		use IDET in button and field names				*
 *						to simplify implementation						*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/08/15		class LegacyEventType renamed to EventType		*
 *		2017/09/12		use get( and set(								*
 *		2017/11/28		use class RecordSet								*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/23      use class FtTemplate                            *
 *		2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/EventType.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values of parameters
$lang		        = 'en';
$oldidet            = 0;
$idet	            = 0;
$record	            = null;
$flagsOff   	    = array();

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{	                // invoked by URL to display current status of account
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	                // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
	    switch(strtolower($key))
	    {		        // act on specific parameter
			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }

			case 'debug':
            {
                break;
            }           // handled by common code
        }
    }
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}	                    // invoked by URL to display current status of account
else
if (count($_POST) > 0)
{	                        // invoked by POST to update table
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                                "<table class='summary'>\n" .
                                "<tr><th class='colhead'>key</th>" .
                                  "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{		                // check each field value
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>$value</td></tr>\n";

        if (preg_match('/^([a-zA-Z]+)(\*)$/', $key, $matches))
        {
            $column	        = strtolower($matches[1]);
            $idet           = $matches[2];
            if ($column == 'tag' && 
                strlen($idet) > 1 && substr($idet,0,1) == '1')
            {
                $column     = 'tag1';
                $idet       = substr($idet, 1);
            }
        }
        else
        {
            $column         = strtolower($key);
            $idet           = '';
        }
	    if ($oldidet == 0)
			$oldidet	    = $idet;

	    // on the first input field of a new table row apply
	    // all changes to the preceding row.
	    // Note that to cause the last row of the table to be
	    // processed there must be a parameter after the last row
	    if ($idet != $oldidet && is_object($record))
	    {		                // check for update 
			$eventType	        = $record->get('eventtype');
			if ($idet > 1 && strlen($eventType) == 0)
			{	                // delete record
			    $record->delete();
			    if ($debug)
                    $warn       .= "<p>EventTypes.php: " . __LINE__ . 
                                    ' ' . $record->getLastSqlCmd() . "</p>\n";
			    $record	= null;
			}	                // delete record
			else
			{	                // update record
			    foreach($flagsOff as $flag => $notset)
			    {
					if ($notset)
					{
					    $wasset	= $record->set($flag, 0);
					}
			    }
			    $rc		        = $record->save(false);
			    if ($debug)
                    $warn       .= "<p>EventTypes.php: " . __LINE__ . 
                                    " count=$rc, " .
                                    $record->getLastSqlCmd() . "</p>\n";
			    $record		    = null;
			}	                // update record
			$oldidet	        = $idet;
	    }		                // check for update

	    switch($column)
	    {		                // act on field name prefix
			case 'idet':
			{
                $idet	        = intval($value);
                if (canUser('all'))
                    $record	    = EventType::getEventType($idet);
                else
                    $record     = null;

			    $flagsOff['used']				= true;
			    $flagsOff['tag1']				= true;
			    $flagsOff['qstag']				= true;
			    $flagsOff['private']			= true;
			    $flagsOff['ppexclude']			= true;
			    $flagsOff['rgexclude']			= true;
			    $flagsOff['showdate']			= true;
			    $flagsOff['showdescription']	= true;
			    $flagsOff['showplace']			= true;
			    break;
			}

			case 'used':
			case 'tag1':
			case 'qstag':
			case 'private':
			case 'ppexclude':
			case 'rgexclude':
			case 'showdate':
			case 'showdescription':
			case 'showplace':
			{	                // boolean flags
			    $flagsOff[$fldname]		= false;
			    if (is_object($record))
					$record->set($fldname, 1);
			    break;
			}	                // boolean flags

			case 'updated':
			case 'delete':
			{
			    // obsolete
			    break;
			}

			case 'eventtype':
			{	                // Event type text
			    if (is_object($record))
			    {	            // update record
					$record->set($fldname, $value);
			    }	            // update record
			    break;
			}	                // Event type

			case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
                break;
            }                   // requested language

			case 'debug':
            {
                break;
            }                   // handled by common code

			default:
			{	                // text fields
			    if (intval($idet) > 0 && is_object($record))
					$record->set($fldname, $value);
			    break;
			}	                // text fields
	    }		                // act on field name prefix
	}		                    // check for table updates
}	                        // invoked by POST to update table

// an administrator account can update the table
// apply changes supplied through method=post
if (canUser('all'))
    $action                 = 'Edit';
else
    $action                 = 'Display';

$template                   = new FtTemplate("EventTypes$action$lang.html");

// get all of the event types as a set
$typeSet		            = new RecordSet('tblET');

// display the results
foreach($typeSet as $idet => $record)
{
    $used				                = $record['used'];
	if ($used)
		$record['usedchecked']			= 'checked="checked"';
    else
		$record['usedchecked']			= '';
    $tag1				                = $record['tag1'];
	if ($tag1)
		$record['tag1checked']			= 'checked="checked"';
    else
		$record['tag1checked']			= '';
    $showdate				            = $record['showdate'];
	if ($showdate)
		$record['showdatechecked']		= 'checked="checked"';
    else
		$record['showdatechecked']		= '';
    $showplace				            = $record['showplace'];
	if ($showplace)
		$record['showplacechecked']		= 'checked="checked"';
    else
		$record['showplacechecked']		= '';
    $showdesc				            = $record['showdescription'];
	if ($showdesc)
		$record['showdescriptionchecked']= 'checked="checked"';
    else
		$record['showdescriptionchecked']= '';
    $private				            = $record['private'];
	if ($private)
		$record['privatechecked']		= 'checked="checked"';
    else
		$record['privatechecked']		= '';
    $ppexclude				            = $record['ppexclude'];
	if ($ppexclude)
		$record['ppexcludechecked']		= 'checked="checked"';
    else
		$record['ppexcludechecked']		= '';
    $rgexclude				            = $record['rgexclude'];
	if ($rgexclude)
		$record['rgexcludechecked']		= 'checked="checked"';
    else
		$record['rgexcludechecked']		= '';
    $qstag				                = $record['qstag'];
	if ($qstag)
        $record['qstagchecked']			= 'checked="checked"';
    else
        $record['qstagchecked']			= '';
}

$template['Row$IDET']->update($typeSet);
$template->set('LANG',              $lang);
if ($debug)
    $template->set('DEBUG',         'Y');
else
    $template->set('DEBUG',         'N');

$template->display();
