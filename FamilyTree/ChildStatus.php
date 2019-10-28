<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ChildStatus.php														*
 *																		*
 *  Display a web page containing the records from the Legacy			*
 *  Child Status Table tblCS.											*
 *  This table is not actually used by this implementation because		*
 *  using an SQL table for this purpose does not support I18N.			*
 *  That is why the management of the table has not been moved to a		*
 *  class ChildStatus or LegacyChildStatus.								*
 *																		*
 *  History:															*
 *		2010/11/30		created											*
 *		2012/01/13		change class names								*
 *		2012/03/08		support createFromTemplate						*
 *						use id= to identify buttons rather than name=	*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS for layout instead of tables			*
 *						visually indicate that administrator can		*
 *						alter status text of existing rows and all		*
 *						fields of a new row								*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/02/18		move all error messages to $msg					*
 *						move all warning messages to $warn				*
 *						cleanup update code								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/08/15		renamed to ChildStatus.php						*
 *		2017/09/12		use set(										*
 *		2017/11/28		$rownum replaced by $idcs in added row			*
 *						use new Record to get record to update			*
 *						use RecordSet to get list of relation records	*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/07/07      use Template                                    *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// interpret parameters
$lang                   = 'en';

if (count($_GET) > 0)
{                   // initial display
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
    {	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		    // act on specific parameter
			case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value,0,2));
                break;
            }
        }
    }
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // initial display
else
if (count($_POST) > 0)
{                   // update table
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                  "<th class='colhead'>value</th></tr>\n";
    $used               = 0;
    $tag1               = 0;
    $qstag              = 0;
    $childstats         = '';
    $idcs               = null;
	foreach($_POST as $key => $value)
	{	            // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
            "<td class='white left'>$value</td></tr>\n";
        $key                    = strtolower($key);
        $result                 = preg_match('/([a-zA-Z_$]+)(\d*)/', $key, $matches);
        $column                 = $matches[1];
        $row                    = $matches[2];
	    switch($column)
	    {		    // act on specific parameter
			case 'lang':
            {
                if (strlen($value) >= 2)
                    $lang       = strtolower(substr($value,0,2));
                break;
            }

		    case 'idcs':
            {
                if ($idcs > 1 && canUser('all'))
                {           // only administrator can update
                    $record     = new Record(array('idcs' => $idcs), 'tblCS');
                    if ($childstatus == '')
                    {       // delete record
                        $record->delete(false);
                        $lastcmd        = $record->getLastSqlCmd();
                        if (strlen($lastcmd) > 0)
                            $warn       .= "<p>Table updated '$lastcmd'</p>\n";
                    }       // delete record
                    else
                    {       // update record
                        $record['used']         = $used;
                        $record['tag1']         = $tag1;
                        $record['qstag']        = $qstag;
                        $record['childstatus']  = $childstatus;
                        $record->save(false);
                        $lastcmd        = $record->getLastSqlCmd();
                        if (strlen($lastcmd) > 0)
                            $warn       .= "<p>Table updated '$lastcmd'</p>\n";
                    }       // update record
                }           // only administrator can update
			    $used               = 0;
			    $tag1               = 0;
			    $qstag              = 0;
			    $childstats         = '';
				$idcs		        = $value;
				break;
		    }

		    case 'used':
		    {
				$used		= $value;
				break;
		    }

		    case 'tagi':
		    {
				$tag1		= $value;
				break;
		    }

		    case 'qstag':
		    {
				$qstag		= $value;
				break;
		    }

		    case 'childstatus':
		    {
				$childstatus	= $value;
				break;
		    }

		}		    // act on specific field names
    }			    // loop through all fieldnames
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // update table

// create Template
if (canUser('all'))
    $action             = 'Update';
else
    $action             = 'Display';
$template		        = new FtTemplate("ChildStatus$action$lang.html");
$trtemplate             = $template->getTranslate();

// query the database for details
$statusSet	= new RecordSet('tblCS');

$dataRow                = $template['dataRow$IDCS'];
if ($dataRow === null)
    print \Templating\escape($template->getRawTemplate());
$dataRowHtml            = $dataRow->outerHTML();
$data                   = '';

foreach($statusSet as $row)
{		        	// loop through results
    $idcs				= $row['idcs'];
    $status				= $row['childstatus'];
    $used				= $row['used'];
    $tag1				= $row['tag1'];
    $qstag				= $row['qstag'];
    $rtemplate          = new \Templating\Template($dataRowHtml);
    $rtemplate->set('IDCS',     $idcs);
    $rtemplate->set('STATUS',   $status);
    if ($used)
        $rtemplate->set('USEDCHECKED',  'checked="checked"');
    else
        $rtemplate->set('USEDCHECKED',  '');
    if ($tag1)
        $rtemplate->set('TAG1CHECKED',  'checked="checked"');
    else
        $rtemplate->set('TAG1CHECKED',  '');
    if ($qstag)
        $rtemplate->set('QSTAGCHECKED', 'checked="checked"');
    else
        $rtemplate->set('QSTAGCHECKED', '');
    $data               .= $rtemplate->compile();
}		// loop through results
$dataRow->update($data);

$template->set('CONTACTTABLE',	'tblCS');
$template->set('CONTACTSUBJECT','[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('LANG',		    $lang);

$template->display();
showTrace();
