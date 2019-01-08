<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testCitation.php							*
 *									*
 *  test the Citation class						*
 *									*
 *  History:								*
 *	2014/08/08	created						*
 *	2017/07/27	class LegacyCitation renamed to class Citation	*
 *	2017/09/12	use get( and set(				*
 *	2017/11/19	use CitationSet in place of getCitations	*
 *									*
 *  Copyright 2017 James A. Cobban					*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    htmlHeader("Test Citation Class",
	       array("/jscripts/util.js",
	             "/jscripts/default.js"),
	       false);
?>
<body>
<?php
    pageTop(array('/genealogy.php'	=> 'Genealogy'));
?>
<div class='body'>
    <h1>
      <span class='right'>
	<a href='editEventHelp.html' target='help'>? Help</a>
      </span>
	Test Citation Class
    </h1>
<?php
    $citparms	= array();
    foreach($_POST as $field => $value)
    {
	if (strlen($value) > 0)
	    $citparms[$field]	= $value;
    }

    $citations		= null;
    if (count($citparms) > 0)
    {
	$citparms['limit']	= 200;
	$citations	= new CitationSet($citparms);
	$citations->delete(false, true);
	$citations->update(array('idime' => 10),
			   false,
			   true);
	showTrace();
    }
?>
    <p>
      <form name='testform' id='testform'
		action='testCitation.php' method='post'>
	<div class='row'>
	  <label class='column1' for='IDSX'>
	    IDSX:
	    </label>
	  <input name='IDSX' id='IDSX' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='IDSR'>
	    IDSR:
	    </label>
	  <input name='IDSR' id='IDSR' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='IDIME'>
	    IDIME:
	    </label>
	  <input name='IDIME' id='IDIME' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='Type'>
	    Type:
	    </label>
	  <input name='Type' id='Type' Type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcDetail'>
	    Source Detail:
	  </label>
	  <input name='SrcDetail' id='SrcDetail' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcPrintDetail'>
	    Source Print Detail:
	  </label>
	  <input name='SrcPrintDetail' id='SrcPrintDetail' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcDetText'>
	    Source Detail Text:
	  </label>
	  <input name='SrcDetText' id='SrcDetText' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcPrintText'>
	    Source Print Text:
	  </label>
	  <input name='SrcPrintText' id='SrcPrintText' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcDetNote'>
	    SrcDetNote:
	  </label>
	  <input name='SrcDetNote' id='SrcDetNote' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcPrintNote'>
	    Source Print Note:
	  </label>
	  <input name='SrcPrintNote' id='SrcPrintNote' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcPrint'>
	    Source Print:
	  </label>
	  <input name='SrcPrint' id='SrcPrint' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='SrcSurety'>
	    Source Surety:
	  </label>
	  <input name='SrcSurety' id='SrcSurety' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='EnteredSD'>
	    Entered Sort Date:
	  </label>
	  <input name='EnteredSD' id='EnteredSD' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='EnteredD'>
	    Entered Date:
	  </label>
	  <input name='EnteredD' id='EnteredD' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='FilingRef'>
	    Filing Reference:
	  </label>
	  <input name='FilingRef' id='FilingRef' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='Order'>
	    Order:
	  </label>
	  <input name='Order' id='Order' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='Used'>
	    Used:
	  </label>
	  <input name='Used' id='Used' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='Verified'>
	    Verified:
	  </label>
	  <input name='Verified' id='Verified' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='Content'>
	    Content:
	  </label>
	  <input name='Content' id='content' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='override'>
	    Override:
	  </label>
	  <input name='Override' id='Override' type='text'
		class='white leftnc' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='OverrideFootnote'>
	    Override Footnote:
	  </label>
	  <input name='OverrideFootnote' id='OverrideFootnote' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='OverrideSubsequent'>
	    Override Subsequent:
	  </label>
	  <input name='OverrideSubsequent' id='OverrideSubsequent' type='text'
		class='white right' value=''>
	</div>
	<div class='row'>
	  <label class='column1' for='OverrideBibliography'>
	    Override Bibliography:
	  </label>
	  <input name='OverrideBibliography' id='OverrideBibliography'
		type='text' class='white right' value=''>
	</div>
	<p>
	    <button type='submit'>Test</button>
	</p>
      </form>
<?php
    if (is_array($citations) && count($citations) > 0)
    {		// ran getCitations
?>
      <table border='1'>
	<thead>
	  <th>IDSX</th>
	  <th>IDSR</th>
	  <th>IDIME</th>
	  <th>Type</th>
	  <th>IDIR</th>
	</thead>
	<tbody>
<?php
	foreach($citations as $idsx => $citation)
	{
	    $idsr	= $citation->get('idsr');
	    $indiv	= $citation->getPerson();
	    if ($indiv)
		$idir	= $indiv->getIdir();
	    else
		$idir	= null;
?>
	<tr>
	  <td>
	    <a href='/FamilyTree/editCitation.php?idsx=<?php print $idsx; ?>'
		target='citation'>
		<?php print $idsx; ?> 
	    </a>
	  </td>
	  <td>
	    <a href='/FamilyTree/editSource.php?idsr=<?php print $idsr; ?>'
		target='source'>
		<?php print $idsr; ?> 
	    </a>
	  </td>
	  <td>
		<?php print $citation->get('idime'); ?> 
	  </td>
	  <td>
		<?php print $citation->getCitTypeText(); ?> 
	  </td>
<?php
	    if ($idir)
	    {
?>
	  <td>
	    <a href='/FamilyTree/Person.php?idir=<?php print $idir; ?>'
		target='indiv'>
		<?php print $idir; ?> 
	    </a>
	  </td>
<?php
	    }
?>
	</tr>
<?php
	}
?>
	</tbody>
      </table>
<?php
    }		// ran getCitations
?>
</div>	<!-- id='body' -->
<?php
    pageBot();
?>
<div class='balloon' id='HelpSubmit'>
Click on this button to run the test.
</div>
<div class='balloon' id='HelpIDSX'>
Enter record identification number.
</div>
<div class='balloon' id='HelpIDSR'>
Enter Source record identification number.
</div>
<div class='balloon' id='HelpIDIME'>
Enter record identification number.
</div>
<div class='balloon' id='HelpType'>
Enter citation type.
</div>
<div class='balloon' id='HelpSrcDetail'>
Enter page identification with source.
</div>
<div class='balloon' id='HelpSrcPrintDetail'>
Enter 1 to display the page identification information or 0 to suppress it.
</div>
<div class='balloon' id='HelpSrcDetText'>
Enter text from original document.
</div>
<div class='balloon' id='HelpSrcPrintText'>
Enter 1 to display the original text or 0 to suppress it.
</div>
<div class='balloon' id='HelpSrcDetNote'>
Enter comments for source citation.
</div>
<div class='balloon' id='HelpSrcPrintNote'>
Enter 1 to display the comments or 0 to suppress them.
</div>
<div class='balloon' id='HelpSrcPrint'>
Enter 1 to include this citation in reports or 0 to suppress it.
</div>
<div class='balloon' id='HelpSrcSurety'>
Enter 0 to 4 for degree of confidence in the evidence. 
</div>
<div class='balloon' id='HelpEnteredSD'>
Enter sorted date yyyymmdd/
</div>
<div class='balloon' id='HelpEnteredD'>
Enter internally encoded date, eg 0ddmmyyyy
</div>
<div class='balloon' id='HelpFilingRef'>
User filing number
</div>
<div class='balloon' id='HelpOrder'>
Sort order for citations to a fact.
</div>
<div class='balloon' id='HelpUsed'>
Enter 1 to include referenced citations or 0 for unreferenced citations.
</div>
<div class='balloon' id='HelpVerified'>
Enter 1 to include verified citations or 0 for unverified citations.
</div>
<div class='balloon' id='HelpContent'>
Extended text.
</div>
<div class='balloon' id='HelpOverride'>
Override paragraph text.
</div>
<div class='balloon' id='HelpOverrideFootnote'>
Enter 1 to use the override paragraph or 0 to skip.
</div>
<div class='balloon' id='HelpOverrideSubsequent'>
Enter 1 to use the subsequent override paragraph or 0 to skip.
</div>
<div class='balloon' id='HelpOverrideBibliography'>
Enter 1 to use the bibliography override paragraph or 0 to skip.
</div>
</body>
</html>
