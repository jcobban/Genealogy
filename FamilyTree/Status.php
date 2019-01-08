<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Status.php															*
 *																		*
 *  Display a web page reporting statistics about the family tree.		*
 *																		*
 *  History:															*
 *		2011/06/05		created											*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/11		standardize page layout							*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2014/07/04		use Citation::getCitations to determine			*
 *						number of citation records in use				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/10/14		use class RecordSet								*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    // query the database for details
    // get total number of individuals in database
    $recset		= new RecordSet('Persons', null);
    $information	= $recset->getInformation();
    $numPersons		= $information['count']; 

    // get total number of families
    $recset		= new RecordSet('Families', null);
    $information	= $recset->getInformation();
    $numFamilies	= $information['count']; 

    // get total number of events
    $recset		= new RecordSet('Events', null);
    $information	= $recset->getInformation();
    $numEvents		= $information['count']; 

    // get number of citations
    $citlist		= new RecordSet('Citations', null);
    $information	= $citlist->getInformation();
    $numCitations	= $information['count'];

    htmlHeader('Database Status',
				array(	'/jscripts/js20/http.js',
						'/jscripts/util.js',
						'/jscripts/default.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
    <div class="fullwidth">
      <span class="h1">
		Database Status
      </span>
      <span class="right">
		<a href="StatusHelpen.html" target="help">? Help</a>
      </span>
      <div style="clear: both;"></div>
    </div>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
    <p class="message"><?php print $msg; ?></p>
<?php
		$msg	= 0;
    }
    else
    {			// no errors
?>
    <p>The database contains:
    <ul>
		<li><?php print number_format($numPersons); ?> individuals.
		<li><?php print number_format($numFamilies); ?> families.
		<li><?php print number_format($numEvents); ?> events.
		<li><?php print number_format($numCitations); ?> citations.
    </ul>
<?php
    }			// no errors
?>
  </div>
<?php
    pageBot();
?>
</body>
</html>
