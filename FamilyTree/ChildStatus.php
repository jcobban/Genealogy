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
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
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
    $idcs		= 'N/A';
    $updated		= 0;
    $used		= 0;
    $tag1		= 0;
    $qstag		= 0;
    $childstatus	= '';
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
				$updated	= $value;
				break;
		    }

		    case 'idcs':
		    {
				$idcs		= $value;
				break;
		    }

		    case 'used':
		    {
				$used		= $value;
				break;
		    }

		    case 'tag1':
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

		}		// act on specific field names
    }			// loop through all fieldnames
    if ($debug)
		$warn	.= ")</p>\n";

    if ($updated != 0)
    {
		$status	= new Record(array('idcs'	=> $idcs),
							     'tblCS');
		if ($childstatus == '' && $idcs > 1)
		{
		    $status->delete();
		}		// delete row
		else
		{		// update
		    $status->set('idcs',	$idcs);
		    $status->set('childstatus',	$childstatus);
		    $status->set('used',	$used);
		    $status->set('tag1',	$tag1); 
		    $status->set('qstag',	$qstag);
		    $status->save(false);
		}		// update
    }			// row updated
}		// chkUpdate

    if (canUser('all'))
    {			// user is master
		$readonly	= '';
		$txtleftclass	= 'white left';

		// check parameters to see if the table should be updated
		// before being displayed
		$post			= array();
		$post['Used']		= 0;
		$post['Tag1']		= 0;
		$post['qsTag']		= 0;
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
		$readonly	= 'readonly';
		$txtleftclass	= 'ina left';
    }			// user can not edit

    // query the database for details
    $statusSet	= new RecordSet('tblCS');

    $warn	.= "<p>This table is not used by this implementation because it does not support internationalization.</p>\n";

    htmlHeader('Child Status List',
				array(	'/jscripts/CommonForm.js',
						'/jscripts/js20/http.js',
						'/jscripts/util.js',
						'ChildStatus.js'));
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
		<a href="ChildStatusHelpen.html" target="help">? Help</a>
      </span>
		Child Status List
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
  <form name="srcForm" action="ChildStatus.php" method="post">
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
      <tr>
		<th class="colhead">
		  IDCS
		</th>
		<th class="colhead">
		  Child Status
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
      </tr>
    </thead>
    <tbody>
<?php
		// display the results
		$i	= 0;		// row counter
		foreach($statusSet as $row)
		{			// loop through results
		    $idcs	= $row['idcs'];
		    $status	= $row['childstatus'];
		    $used	= $row['used'];
		    $tag1	= $row['tag1'];
		    $qstag	= $row['qstag'];
?>
      <tr>
		<td class="left">
		    <input type="text" class="ina rightnc" size="4"
						name="IDCS<?php print $i; ?>"
						id="IDCS<?php print $i; ?>"
						value="<?php print $idcs; ?>" readonly="readonly">
		    <input type="hidden"
						name="Updated<?php print $i; ?>"
						id="Updated<?php print $i; ?>"
						value="0">
		</td>
		<td class="left">
		    <input type="text" class="<?php print $txtleftclass; ?>" size="40"
						name="ChildStatus<?php print $i; ?>"
						id="ChildStatus<?php print $i; ?>"
						value="<?php print $status; ?>"
						<?php print $readonly; ?>>
		</td>
		<td class="center">
		    <input type="checkbox"
						name="Used<?php print $i; ?>"
						id="Used<?php print $i; ?>"
						<?php print $readonly; ?> 
				<?php if ($used > 0) print ' checked'; ?>>
		</td>
		<td class="center">
		    <input type="checkbox"
						name="tag1<?php print $i; ?>"
						id="tag1<?php print $i; ?>"
						<?php print $readonly; ?> 
				<?php if ($tag1 > 0) print ' checked'; ?>>
		</td>
		<td class="center">
		    <input type="checkbox"
						name="qstag<?php print $i; ?>"
						id="qstag<?php print $i; ?>"
						<?php print $readonly; ?> 
				<?php if ($qstag > 0) print ' checked'; ?>>
		</td>
		<td class="center">
<?php
		    if (canUser('all') && $idcs > 1)
		    {		// user is master
?>
		    <button id="Delete<?php print $i; ?>">
				Delete
		    </button>
<?php
		    }		// user is master
?>
		</td>
      </tr>
<?php
		    $i++;		// row counter
		}		// loop through results
?>
    </tbody>
  </table>
<?php
    if (canUser('all'))
    {		// permit adding a Status
?>
    <p>
		<button type="button" id="Add">
		    Create New Status
		</button>
    </p>
<?php
    }		// permit adding a Status

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
  <div class="hidden" id="templates">
    <table>
      <tr id="newRowTemplate">
		<td class="left">
		    <input type="text" class="white rightnc" size="4"
						name="IDCS$idcs"
						value="$idcs">
		    <input type="hidden" name="Updated$idcs"
						value="1">
		</td>
		<td class="left">
		    <input type="text" class="white left" size="40"
						name="ChildStatus$idcs"
						value="">
		</td>
		<td class="center">
		    <input type="checkbox" name="Used$idcs">
		</td>
		<td class="center">
		    <input type="checkbox" name="tag1$idcs">
		</td>
		<td class="center">
		    <input type="checkbox" name="qstag$idcs">
		</td>
		<td class="center">
		    <button name="Delete$idcs">
				Delete
		    </button>
		</td>
      </tr>
    </table>
  </div>
  <div class="balloon" id="HelpIDCS">
    <p>This field contains the unique numeric code point used in the
		Child Status table (tblCS) to identify this
		status.
    </p>
  </div>
  <div class="balloon" id="HelpChildStatus">
    <p>This field contains the textual description of the Child's
		status at birth, as it will appear in web pages, and on selection
		lists when updating the database.
    </p>
  </div>
  <div class="balloon" id="HelpUsed">
    <p>Internal use by Legacy.
    </p>
  </div>
  <div class="balloon" id="Helptag">
    <p>Internal use by Legacy.
    </p>
  </div>
  <div class="balloon" id="Helpqstag">
    <p>Internal use by Legacy.
    </p>
  </div>
  <div class="balloon" id="HelpDelete">
    <p>Click on this button to delete the status described in this row
		of the table.
    </p>
  </div>
  <div class="balloon" id="HelpAdd">
    <p>Click on this button to add a new status to the table.
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
