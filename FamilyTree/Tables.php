<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Tables.php															*
 *																		*
 *  Display a web page to provide access to the database tables that	*
 *  interpret type and status fields.									*
 *																		*
 *  History:															*
 *		2012/10/22		split off from Services.php						*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		replace tables with CSS for layout				*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/08/15		renamed to Tables.php							*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Tables for Interpreting Status and Type Fields',
		       array(	'/jscripts/default.js',
						'/jscripts/js20/http.js',
						'/jscripts/util.js'));
?>
<body>
<?php
   pageTop(array('/genealogy.php'		=> 'Genealogy',
				 '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
    <h1>Tables for Interpreting Status and Type Fields
      <span class="right">
		<a href="TablesHelpen.html" target="help">? Help</a>
      </span>
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
  <p class="label">
		<a href="EventTypes.php">
				Event Types List
		</a>:
  </p>
  <p class="label">
		<a href="ChildStatus.php">
				Child Status List
		</a>:
  </p>
  <p class="label">
		<a href="ChildRelations.php">
				Child/Parent Relationship List
		</a>:
  </p>
  </div>
<?php
    pageBot();
?>
</body>
</html>
