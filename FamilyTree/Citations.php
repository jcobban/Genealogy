<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Citations.php														*
 *																		*
 *  Display a web page containing all of the citations matching a		*
 *  pattern.															*
 *																		*
 *  History:															*
 *		2011/07/05		created											*
 *		2011/10/02		cleanup											*
 *		2012/01/13		change class names								*
 *		2012/02/01		name of marriage edit script changed			*
 *		2012/02/25		use switch for parameter names					*
 *						add support for popup help bubbles				*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/08/21		use htmlHeader function to standardize <head>	*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/14		use CSS rather than tables for layout			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/04		use Citation::getCitations to obtain			*
 *						list of citations to display instead of			*
 *						accessing SQL directly							*
 *						Initialize selected option of event type		*
 *						selection list in PHP rather than javascript	*
 *		2014/10/02		incorrect parameter list to getCitations		*
 *		2015/05/04		display the name of the associated record		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js	before util.js					*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/08/15		class LegacyToDo renamed to class ToDo			*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Child.inc';
require_once __NAMESPACE__ . '/Event.inc';
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/ToDo.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function typeValue													*
 *																		*
 *  Emit value and selected parameters for the type selection option	*
 ************************************************************************/
function typeValue($value)
{
    global $type;
    print "value=$value";
    if ($value == $type)
		print ' selected="selected"';
}		// function typeValue

    /********************************************************************
     *  Identify type of record containing event details				*
     ********************************************************************/
    static $urlname = array(
				0	=> "Person.php?idir=",
				1	=> "Person.php?idir=",
				2	=> "Person.php?idir=",
				3	=> "Person.php?idir=",
				4	=> "Person.php?idir=",
				5	=> "Person.php?idir=",
				6	=> "Person.php?idir=",
				7	=> "Person.php?idir=",
				8	=> "Person.php?idir=",
				9	=> "Person.php?idir=",
				10	=> "getRecordXml.php?idnx=",
				11	=> "getRecordXml.php?idcr=",
				12	=> "getRecordXml.php?idcr=",
				13	=> "getRecordXml.php?idcr=",
				15	=> "Person.php?idir=",
				16	=> "Person.php?idir=",
				17	=> "getRecordXml.php?idcr=",
				18	=> "editMarriages.php?idmr=",
				19	=> "editMarriages.php?idmr=",
				20	=> "editMarriages.php?idmr=",
				21	=> "editMarriages.php?idmr=",
				22	=> "editMarriages.php?idmr=",
				23	=> "editMarriages.php?idmr=",
				26	=> "Person.php?idir=",
				27	=> "Person.php?idir=",
				30	=> "editEvent.php?type=30&amp;ider=",
				31	=> "editEvent.php?type=31&amp;ider=",
				40	=> "getRecordXml.php?idtd=");

    // get the parameters
    $pattern	= '';
    $count	= 0;
    $offset	= 0;
    $limit	= 20;
    $type	= null;		// so it will fail to match on default
    $idsr	= null;		// so it will fail to match on default
    $parms	= array();
    foreach($_GET as $key => $value)
    {		// loop through all parameters
		switch($key)
		{	// take action on specific parameter
		    case 'pattern':
		    {
				$pattern		= $value;
				$parms['srcdetail']	= $value;
				break;
		    }

		    case 'type':
		    {
				$type			= (int)$value;
				$parms['type']		= $type;
				break;
		    }

		    case 'idsr':
		    {
				$idsr			= (int)$value;
				$parms['idsr']		= $idsr;
				break;
		    }

		    case 'offset':
		    {
				$offset			= (int)$value;
				break;
		    }

		    case 'limit':
		    {
				$limit			= (int)$value;
				break;
		    }
		}	// take action on specific parameter
    }		// loop through all parameters

    $parms['offset']	= $offset;
    $parms['limit']	= $limit;
    if (strlen($pattern) == 0)
		$msg	.= "Please specify a pattern for citations. ";
    if (is_null($idsr))
		$msg	.= "Please identify source. ";
    if (is_null($type))
		$msg	.= "Please identify citation or event type. ";

    $prevoffset	= $offset - $limit;
    $nextoffset	= $offset + $limit;

    if (strlen($msg) == 0)
    {			// get the matching citations
		$list		= new CitationSet($parms,
								  'SrcDetail');
		$info		= $list->getInformation();
		$count		= $list->count();
		$totalcount	= $info['count'];
    }			// get the matching citations


    htmlHeader('Citations Master List',
		       array('/jscripts/CommonForm.js',
				     '/jscripts/js20/http.js',
				     '/jscripts/util.js',
				     '/jscripts/CommonForm.js',
				     'Citations.js'),
				true);
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
    <h1>
      <span class="right">
		<a href="CitationsHelpen.html" target="help">? Help</a>
      </span>
      Citations Master List
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {			// errors detected
?>
    <p class="message"><?php print $msg; ?></p>
<?php
    }			// errors detected
?>
    <p class="label">
<?php
    if ($count == 0)
    {
		print 'No';
    }
    else
    {		// got some results
		print $count;
    }
?>
 Citations match the specified pattern.
    </p>
    <form name="citForm" id="citForm" action="Citations.php">
      <div class="row">
		<label class="column1" for="type">
		    Event&nbsp;Type:
		</label>
		<select name="type" id="type" size="5" class="white left">
				<option <?php typeValue(-1); ?>>Choose a type</option>
				<option <?php typeValue(0); ?>>Unspecified</option>
				<option <?php typeValue(1); ?>>Name</option>
				<option <?php typeValue(2); ?>>Birth</option>
				<option <?php typeValue(3); ?>>Christening</option>
				<option <?php typeValue(4); ?>>Death</option>
				<option <?php typeValue(5); ?>>Buried</option>
				<option <?php typeValue(6); ?>>General Notes</option>
				<option <?php typeValue(7); ?>>Research Notes</option>
				<option <?php typeValue(8); ?>>Medical Notes</option>
				<option <?php typeValue(9); ?>>Death Cause</option>
				<option <?php typeValue(10); ?>>Alternate Name</option>
				<option <?php typeValue(11); ?>>Child Status</option>
				<option <?php typeValue(12); ?>>Child Relationship to Dad</option>
				<option <?php typeValue(13); ?>>Child Relationship to Mom</option>
				<option <?php typeValue(15); ?>>LDS Baptism</option>
				<option <?php typeValue(16); ?>>LDS Endowment</option>
				<option <?php typeValue(17); ?>>LDS Sealed to Parents</option>
				<option <?php typeValue(18); ?>>LDS Sealed to Spouse</option>
				<option <?php typeValue(19); ?>>Never Married</option>
				<option <?php typeValue(20); ?>>Marriage</option>
				<option <?php typeValue(21); ?>>Marriage Note</option>
				<option <?php typeValue(22); ?>>Marriage Never</option>
				<option <?php typeValue(23); ?>>Marriage No Children</option>
				<option <?php typeValue(26); ?>>LDS Confirmation</option>
				<option <?php typeValue(27); ?>>LDS Initiatory</option>
				<option <?php typeValue(30); ?>>Event</option>
				<option <?php typeValue(31); ?>>Marriage Event</option>
				<option <?php typeValue(40); ?>>To Do</option>
		</select>
		<input type="hidden" name="typeparm" id="typeparm"
				value="<?php print $type; ?>">
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<label class="column1" for="idsr">
		    Master&nbsp;Source:
		</label>
		<select name="idsr" id="idsr" size="5" class="white left">
		    <option value="-1">Choose a type</option>
		</select>
		<input type="hidden" name="idsrparm" id="idsrparm"
				value="<?php print $idsr; ?>">
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<label class="column1" for="pattern">
		    Pattern:
		</label>
		<input name="pattern" id="pattern" type="text"
				class="white leftnc" size="64" value="<?php print $pattern; ?>">
		<div style="clear: both;"></div>
      </div>
    <p>
		<button type="submit" id="Submit">
		    Search
		</button>
    </p>
    </form>
<?php
    if ($count > 0)
    {		// query issued and retrieved some records
?>
    <div class="center">
<?php
		if ($prevoffset >= 0)
		{	// previous page of output to display
?>
      <span class="left">
		<a href="Citations.php?type=<?php print $type; ?>&amp;idsr=<?php print $idsr; ?>&amp;pattern=<?php print $pattern; ?>&amp;limit=<?php print $limit; ?>&amp;offset=<?php print $prevoffset; ?>">&lt;---</a>
      </span>
<?php
		}	// previous page of output to display
?>
<?php
		if ($nextoffset < $totalcount)
		{	// next page of output to display
?>
      <span class="right"> 
		<a href="Citations.php?type=<?php print $type; ?>&amp;idsr=<?php print $idsr; ?>&amp;pattern=<?php print $pattern; ?>&amp;limit=<?php print $limit; ?>&amp;offset=<?php print $nextoffset; ?>">---&gt;</a>
      </span>
<?php
		}	// next page of output to display
		$last	= min($nextoffset - 1, $totalcount);
?>
		rows <?php print $offset; ?> to <?php print $last; ?>
				of <?php print $totalcount; ?> 
      <div style="clear: both;"></div>
    </div>
<!--- Put out the response as a table -->
<table class="details">
<!--- Put out the column headers -->
  <thead>
   <tr>
    <th class="colhead">
		IDIME
    </th>
    <th class="colhead" style="width: 45em;">
		Page
    </th>
   </tr>
  </thead>
  <tbody>
<?php
		// display the results
		foreach($list as $idsx => $citation)
		{
		    $idime	= $citation->get('idime'); 
		    $type	= $citation->get('type');

		    // get the appropriate object instance
		    switch($type)
		    {
				case Citation::STYPE_UNSPECIFIED:
				case Citation::STYPE_NAME:
				case Citation::STYPE_BIRTH:
				case Citation::STYPE_CHRISTEN:
				case Citation::STYPE_DEATH:
				case Citation::STYPE_BURIED:
				case Citation::STYPE_NOTESGENERAL:
				case Citation::STYPE_NOTESRESEARCH:
				case Citation::STYPE_NOTESMEDICAL:
				case Citation::STYPE_DEATHCAUSE:
				case Citation::STYPE_LDSB:
				case Citation::STYPE_LDSE:
				case Citation::STYPE_LDSC:
				case Citation::STYPE_LDSI:
				{
				    $record	= new Person(array('idir' => $idime));
				    $href	= "Person.php?idir=$idime";
				    break;
				}		// instance of Person

    /********************************************************************
     *		IDIME points to Alternate Name Record tblNX						*
     ********************************************************************/
				case Citation::STYPE_ALTNAME:
				{
				    $record	= new Name(array('idnx' => $idime));
				    $href	= "getRecordXml.php?idnx=" . $idime;
				    break;
				}		// instance of Name

    /********************************************************************
     *		IDIME points to Child Record tblCR.IDCR								*
     ********************************************************************/
				case Citation::STYPE_CHILDSTATUS:
				case Citation::STYPE_CPRELDAD:
				case Citation::STYPE_CPRELMOM:
				case Citation::STYPE_LDSP:
				{
				    $record	= new Child(array('idcr' => $idime));
				    $href	= "getRecordXml.php?idcr=$idime";
				    break;
				}		// instance of Child

    /********************************************************************
     *		IDIME points to Marriage Record tblMR.idmr						*
     ********************************************************************/
				case Citation::STYPE_LDSS:
				case Citation::STYPE_NEVERMARRIED:
				case Citation::STYPE_MAR:
				case Citation::STYPE_MARNOTE:
				case Citation::STYPE_MARNEVER:
				case Citation::STYPE_MARNOKIDS:
				case Citation::STYPE_MAREND:
				{
				    $record	= new Family(array('idmr' => $idime));
				    $href	= "editMarriages.php?idmr=$idime";
				    break;
				}		// instance of Family

    /********************************************************************
     *		IDIME points to Event Record tblER.ider								*
     ********************************************************************/
				case Citation::STYPE_EVENT:
				case Citation::STYPE_MAREVENT:
				{
				    $record	= new Event(array('ider' => $idime));
				    $href	= "editEvent.php?type=$type&amp;ider=$idime";
				    break;
				}		// instance of Event

    /********************************************************************
     *  IDIME points to To-Do records tblTD.IDTD						*
     ********************************************************************/
				case Citation::STYPE_TODO:
				{
				    $record	= new ToDo(array('idtd' => $idime));
				    $href	= "getRecordXml.php?idtd=$idime";
				    break;
				}		// instance of To Do
		    }			// act on specific types
		    $page	= $citation->get('srcdetail');
?>
    <tr>
		<td class="odd right">
		    <a href="<?php print $href; ?>" target="_blank">
				<?php print $idime; ?>=<?php print $record->getName(); ?>
		    </a>
		</td>
		<td class="odd left">
		    <?php print $page; ?> 
    </tr>
<?php
		}	// loop through results
?>
  </tbody>
</table>
<?php
    }	// query issued
?>
</div>
<?php
    pageBot();
?>
<div class="balloon" id="Helppattern">
<p>
This is an extended regular expression, as supported by MySQL, 
which is used to limit
the locations to be displayed. See <a href="http://www.tin.org/bin/man.cgi?section=7&topic=regex">Henry Spencer"s regex page</a>.
<ul>
    <li>If the pattern contains no special 
characters then only locations containing that string will be included.
For example the pattern "London" will match locations containing the string
"London".  Note that the search ignores case, so that pattern will also match
"LONDON" and "london".
    <li>If the pattern begins with a caret '^' then only locations that
<b>begin</b> with the remainder of the pattern are included.  
For example the pattern
"^Ba" displays locations starting with "Ba" (or "ba" or "BA").
    <li>If the pattern ends with a dollar sign '$', then only locations that
<b>end</b> with the remainder of the pattern are included.  
For example the pattern
"CA$" matches locations that end with "CA" (or "ca" or "Ca").
    <li>In a pattern a period '.' matches any single character.  For example
the pattern "B.b" matches any location that contains two letter Bs separated
by one character, for example "Bab", "Beb", "Bib", "Bob", or "Bub" 
anywhere in the location name.
    <li>In a pattern an asterisk '*' matches zero or more of the preceding
character; "bo*b" matches "bb", "bob", and "boob"
anywhere in the location name.
</p>
</div>
<div class="balloon" id="Helptype">
<p>This selection list is used to choose the type of Event for which a list of
citations is to be produced.
</p>
</div>
<div class="balloon" id="Helpidsr">
<p>This selection list is used to choose the master source to which the
citations refer.
</p>
</div>
<div class="balloon" id="Helptypeparm">
<p>This selection list is used to choose the type of Event for which a list of
citations is to be produced.
</p>
</div>
<div class="balloon" id="Helpidsrparm">
<p>This selection list is used to choose the master source to which the
citations refer.
</p>
</div>
<div class="balloon" id="HelpSubmit">
<p>Click on this button to apply the search criteria.
</p>
</div>
</body>
</html>
