<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ChildRelations.php													*
 *																		*
 *  Display a web page containing the records from the Legacy			*
 *  Child/Parent Relationship Table tblCP/ChildRelations.				*
 *  This table is not actually used by this implementation because		*
 *  using an SQL table for this purpose does not support I18N.			*
 *  That is why the management of the table has not been moved to a		*
 *  class ChildRelation.												*
 *																		*
 *		CREATE TABLE `tblCP` (											*
 *		    `IDCP`				INT(10) UNSIGNED NOT NULL,				*
 *		    `CPRelation`		VARCHAR(50) NOT NULL DEFAULT '',		*
 *		    `Tag1`				TINYINT(3) NOT NULL DEFAULT '0',		*
 *		    `Used`				TINYINT(3) NOT NULL DEFAULT '0',		*
 *		    `qsTag`				TINYINT(3) NOT NULL DEFAULT '0',		*
 *		    PRIMARY KEY (`IDCP`),										*
 *		    KEY `CPRelation` (`CPRelation`) )							*
 *		    ENGINE=InnoDB DEFAULT CHARSET=utf8 							*
 *																		*
 *  History:															*
 *		2010/11/30		created											*
 *		2012/01/13		change class names								*
 *		2012/05/07		support createFromTemplate						*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/17		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/08/09		base class LegacyRecord renamed to Record		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS for layout instead of tables			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/02/18		move all error messages to $msg					*
 *						move all warning messages to $warn				*
 *						cleanup update code								*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *						include http.js									*
 *		2017/09/12		use set(										*
 *		2017/11/28		$rownum replaced by $idcp in added row			*
 *						use new Record to get record to update			*
 *						use RecordSet to get list of relation records	*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/05/05      use FtTemplate                                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Record.inc";
    require_once __NAMESPACE__ . "/RecordSet.inc";
    require_once __NAMESPACE__ . "/FtTemplate.inc";
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function chkUpdate													*
 *																		*
 *  Check the parameters passed by method="post" to see whether the		*
 *  table needs to be updated.											*
 *																		*
 *  Input:																*
 *		$post			associative array of fieldname value pairs		*
 ************************************************************************/
function chkUpdate($post)
{
    global	$debug;
    global	$warn;
    global	$connection;

    if ($debug)
    {
        $warn	.= "<p>chkUpdate(\$post=";
        $comma	= '(';
    }
    $idcp		= 0;
    $updated		= 0;
    $used		= 0;
    $tag1		= 0;
    $qstag		= 0;
    $cprelation		= '';
    foreach($post as $fldname => $value)
    {			// loop through all fieldnames
        if ($debug)
        {
            $warn	.= "$comma$fldname=$value";
            $comma	= ',';
        }
        switch(strtolower($fldname))
        {		// act on specific field names
            case 'updated':
            {
                if (ctype_digit($value))
                    $updated	= $value;
                break;
            }

            case 'idcp':
            {
                if (ctype_digit($value))
                    $idcp	= intval($value);
                break;
            }

            case 'used':
            {
                if ($value)
                    $used	= 1;
                break;
            }

            case 'tag1':
            {
                if ($value)
                    $tag1	= 1;
                break;
            }

            case 'qstag':
            {
                if ($value)
                    $qstag	= 1;
                break;
            }

            case 'cprelation':
            {
                $cprelation	= $value;
                break;
            }

        }		// act on specific field names
    }			// loop through all fieldnames
    if ($debug)
        $warn	.= ")</p>\n";

    if ($updated != 0) 
    {
        // get record
        $status		= new Record(array('idcp'	=> $idcp),
                                 'tblCP');
                                 
        if ($cprelation == '' && $idcp != 1)
        {
            $status->delete();
        }			// delete row
        else
        {		// update
            $status->set('cprelation',	$cprelation);
            $status->set('used',		$used);
            $status->set('tag1',		$tag1); 
            $status->set('qstag',		$qstag);
            $status->save(false);
        }		// update
    }			// row updated
}		// function chkUpdate

/************************************************************************
 *		Open Code														*
 ************************************************************************/
$lang                   = 'en';
if (canUser('all'))
    $action             = 'Update';
else
    $action             = 'Display';

if (count($_GET) > 0)
{                   // display table
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
}                   // display table
else
if (count($_POST))
{                   // update table
    if (canUser('all'))
    {			    // user is master
        // check parameters to see if the table should be updated
        // before being displayed
        $post			    = array();
        $post['used']		= 0;
        $post['tag1']		= 0;
        $post['qstag']		= 0;
        $oldrownum		    = 1;
        $parmsText  = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
        foreach($_POST as $key => $value)
        {		    // check for table updates
            $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
            $matches	= array();
            $fldnameLc  = strtolower($key);
            if (preg_match('/^([a-zA-Z]+)(\d*)$/', $fldnameLc, $matches))
            {
                $column		= strtolower($matches[1]);
                $rownum		= $matches[2];
	            if ($column == 'lang')
	            {
	                if (strlen($value) >= 2)
                        $lang       = strtolower(substr($value,0,2));
                    continue;
                }
                else
	            if ($rownum == '')
	            {
                    continue;
                }
                else
                if ($column == 'tag')
                {
                    $column	= 'tag1';
                    $rownum	= intval(substr($rownum, 1));
                }
                else
                    $rownum	= intval($rownum);
            }

            if ($rownum != $oldrownum)
            {		// check for update on new row
                chkUpdate($post);
                $post['used']	= 0;
                $post['tag1']	= 0;
                $post['qstag']	= 0;
                $oldrownum	    = $rownum;
            }		// check for update
            $post[$column]	    = $value;
        }		    // check for table updates
        if ($debug)
            $warn   .= $parmsText . "</table>\n";
    }			    // user is master
}                   // parameters passed by method post

$template           = new FtTemplate("ChildRelations$action$lang.html");

$template->set('LANG',          $lang);
if ($debug)
    $template->set('DEBUG',     'Y');
else
    $template->set('DEBUG',     'N');

// query the database for details
$relationSet	= new RecordSet('tblCP');
foreach($relationSet as $idcp => $relation)
{
    if ($relation['used'])
        $relation['checkedused']    = 'checked="checked"';
    if ($relation['tag1'])
        $relation['checkedtag1']    = 'checked="checked"';
    if ($relation['qstag'])
        $relation['checkedqstag']   = 'checked="checked"';
}

$template['dataRow']->update($relationSet);

$template->display();
