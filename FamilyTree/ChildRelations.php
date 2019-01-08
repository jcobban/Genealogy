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
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
    require_once __NAMESPACE__ . "/Record.inc";
    require_once __NAMESPACE__ . "/RecordSet.inc";
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  chkUpdate																*
 *																		*
 *  Check the parameters passed by method="post" to see whether the		*
 *  table needs to be updated.												*
 *																		*
 *  Input:																*
 *		$post				associative array of fieldname value pairs		*
 ************************************************************************/
function chkUpdate($post)
{
    global	$debug;
    global	$warn;
    global	$connection;

    if ($debug)
    {
		$warn	.= "<p>chkUpdate($post=";
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
							     
		if ($cprelation == '')
		{
		    $status->delete();
		}			// delete row
		else
		{		// update
		    $status->set('idcp',		$idcp);
		    $status->set('cprelation',	$cprelation);
		    $status->set('used',		$used);
		    $status->set('tag1',		$tag1); 
		    $status->set('qstag',		$qstag);
		    $status->save(false);
		}		// update
    }			// row updated
}		// chkUpdate

/************************************************************************
 *		Open Code														*
 ************************************************************************/
    if (canUser('all'))
    {			// user is master
		$readonly	= '';

		// check parameters to see if the table should be updated
		// before being displayed
		$post			= array();
		$post['used']		= 0;
		$post['tag1']		= 0;
		$post['qstag']		= 0;
		$oldrownum		= 0;
		foreach($_POST as $key => $value)
		{		// check for table updates
		    $matches	= array();
		    if (preg_match('/^([a-zA-Z]+)(\d*)$/', $key, $matches))
		    {
				$column		= strtolower($matches[1]);
				$rownum		= $matches[2];
				if ($column == 'tag')
				{
				    $column	= 'tag1';
				    $rownum	= intval(substr($rownum, 1));
				}
				else
				    $rownum	= intval($rownum);
		    }
		    else
				$msg		.= "Invalid field name '$key'. ";

		    if ($rownum != $oldrownum)
		    {		// check for update on new row
				chkUpdate($post);
				$post['Used']	= 0;
				$post['Tag1']	= 0;
				$post['qsTag']	= 0;
				$oldrownum	= $rownum;
		    }		// check for update
		    $post[$column]	= $value;
		}		// check for table updates
		if (array_key_exists('updated', $post))
		    chkUpdate($post);	// final row
    }			// user is master
    else
    {			// user can not edit
		$readonly	= ' readonly';
    }			// user can not edit

    // query the database for details
    $relationSet	= new RecordSet('tblCP');

    $warn	.= "<p>This table is not used by this implementation because it does not support internationalization.</p>\n";

    htmlHeader('Child/Parent Relationship List',
				array(	'/jscripts/CommonForm.js',
						'/jscripts/js20/http.js',
						'/jscripts/util.js',
						'ChildRelations.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/genCountry.php?cc=CA'	=> 'Canada',
				  '/Canada/genProvince.php?Domain=CAON'
									=> 'Ontario',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
    <h1>
      <span class="right">
		<a href="ChildRelationsHelpen.html" target="help">? Help</a>
      </span>
      Child/Parent Relationship List
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
  <p class="message">
		<?php print $msg; ?> 
  </p>
<?php
    }		// error message to display

?>
  <form name="srcForm" action="ChildRelations.php" method="post">
<?php
		if ($debug)
		{
?>
    <input id="Debug" name="Debug" type="hidden" value="Y">
<?php
		}		// debugging
?>
  <!--- Put out the response as a table -->
  <table class="details" id="formTable">
    <!--- Put out the column headers -->
    <thead>
      <th class="colhead">
		IDCP
      </th>
      <th class="colhead">
		Relationship
      </th>
      <th class="colhead">
		Used
      </th>
      <th class="colhead">
		Tag1
      </th>
      <th class="colhead">
		qsTag
      </th>
      <th class="colhead">
		Action
      </th>
    </thead>
    <tbody>
<?php
    // display the results
    foreach($relationSet as $row)
    {
		$idcp		= $row['idcp'];
		$relation	= $row['cprelation'];
		$used		= $row['used'];
		$tag1		= $row['tag1'];
		$qstag		= $row['qstag'];
?>
      <tr>
		<td class="left">
		    <input type="text" class="ina rightnc" size="4"
						name="IDCP<?php print $idcp; ?>"
						id="IDCP<?php print $idcp; ?>"
						value="<?php print $idcp; ?>" readonly="readonly">
		    <input type="hidden" name="Updated<?php print $idcp; ?>"
						id="Updated<?php print $idcp; ?>"
						value="0">
		</td>
		<td class="left">
		    <input type="text" class="white left" size="40"
						name="CPRelation<?php print $idcp; ?>"
						id="CPRelation<?php print $idcp; ?>"
						value="<?php print $relation; ?>"
						<?php print $readonly; ?>>
		</td>
		<td class="center">
		    <input type="checkbox"
						name="Used<?php print $idcp; ?>"
						id="Used<?php print $idcp; ?>"
						<?php print $readonly; ?> 
				<?php if ($used > 0) print ' checked'; ?>>
		</td>
		<td class="center">
		    <input type="checkbox"
						name="tag1<?php print $idcp; ?>"
						id="tag1<?php print $idcp; ?>"
						<?php print $readonly; ?> 
				<?php if ($tag1 > 0) print ' checked'; ?>>
		</td>
		<td class="center">
		    <input type="checkbox" 
						name="qstag<?php print $idcp; ?>"
						id="qstag<?php print $idcp; ?>"
						<?php print $readonly; ?> 
				<?php if ($qstag > 0) print ' checked'; ?>>
		</td>
		<td class="center">
<?php
		if (canUser('all') && $idcp > 1)
		{		// user is master
?>
		    <button id="Delete<?php print $idcp; ?>">
				Delete
		    </button>
<?php
		}		// user is master
?>
		</td>
      </tr>
<?php
    }	// loop through results
?>
    </tbody>
  </table>
<?php
    if (canUser('all'))
    {		// permit adding a Relationship
?>
    <p>
		<button type="button" id="Add">
		    Create New Relationship
		</button>
    </p>
<?php
    }		// permit adding a Relationship

    if (canUser('all'))
    {		// user is master
?>
  <button type="submit" id="Submit">Update Table</button>
<?php
    }		// user is master
?>
</form>
</div>
<?php
    pageBot();
?>
  <div id="templates" class="hidden">
   <table>
    <tr id="newRowTemplate">
		<td class="left">
		    <input type="text" class="white rightnc" size="4"
						name="IDCP$idcp"
						value="$idcp">
		    <input type="hidden" name="Updated$idcp"
						value="1">
		</td>
		<td class="left">
		    <input type="text" class="white left" size="40"
						name="CPRelation$idcp"
						value="$relation">
		</td>
		<td class="center">
		    <input type="checkbox" name="Used$idcp">
		</td>
		<td class="center">
		    <input type="checkbox" name="tag1$idcp">
		</td>
		<td class="center">
		    <input type="checkbox" name="qstag$idcp">
		</td>
		<td class="center">
		</td>
    </tr>
   </table>
  </div>
  <div class="balloon" id="HelpIDCP">
    <p>This field contains the unique numeric code point used in the
		Child/Parent relationship table (tblCP) to identify this
		relationship.
    </p>
  </div>
  <div class="balloon" id="HelpCPRelation">
    <p>This field contains the textual description of the Child/Parent
		relationship, as it will appear in web pages, and on selection
		lists when updating the database.
    </p>
  </div>
  <div class="balloon" id="HelpUsed">
    <p>Internal use by Legacy.
    </p>
  </div>
  <div class="balloon" id="Helptag1">
    <p>Internal use by Legacy.
    </p>
  </div>
  <div class="balloon" id="Helpqstag">
    <p>Internal use by Legacy.
    </p>
  </div>
  <div class="balloon" id="HelpDelete">
    <p>Click on this button to delete the relationship described in this row
		of the table.
    </p>
  </div>
  <div class="balloon" id="HelpAdd">
    <p>Click on this button to add a new relationship to the table.
		A new row is added to the table with a blank description.
		Edit the description and click on the "Update Table" button
		to add the new entry.
    </p>
  </div>
  <div class="balloon" id="HelpSubmit">
    <p>Click on this button to update the database to reflect the
		changes you have entered.  
    </p>
  </div>
</body>
</html>
