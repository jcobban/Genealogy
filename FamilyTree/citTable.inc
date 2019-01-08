<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  citTable.inc							*
 *									*
 *  This is an PHP file that can be included in any web page		*
 *  to manage the citations of the event described by the web page.	*
 *  In particular it is included in the following pages:		*
 *									*
 *	editEvent.php							*
 *	editMarriage.php						*
 *									*
 *  Prior to including this file the enclosing web page must have	*
 *  initialized the following variables:				*
 *	$idime		unique numeric key of the record containing the	*
 *			event						*
 *	$type		the numeric identifier of the type of event	*
 *			as defined in Citation				*
 *									*
 *	    $idime points to Person record				*
 *		STYPE_NAME		= 1				*
 *		STYPE_BIRTH		= 2				*
 *		STYPE_CHRISTEN		= 3				*
 *		STYPE_DEATH		= 4				*
 *		STYPE_BURIED		= 5				*
 *		STYPE_NOTESGENERAL	= 6				*
 *		STYPE_NOTESRESEARCH	= 7				*
 *		STYPE_NOTESMEDICAL	= 8				*
 *		STYPE_DEATHCAUSE	= 9				*
 *		STYPE_LDSB		= 15  LDS Baptism		*
 *		STYPE_LDSE		= 16  LDS Endowment		*
 *		STYPE_LDSC		= 26  LDS Confirmation		*
 *		STYPE_LDSI		= 27  LDS Initiatory		*
 *									*
 *	    $idime points to Alternate Name Record tblNX		*
 *		STYPE_ALTNAME		= 10				*
 *									*
 *	    $idime points to Child Record tblCR				*
 *		STYPE_CHILDSTATUS	= 11 Child Status	   	*
 *		STYPE_CPRELDAD		= 12 Relationship to Father  	*
 *		STYPE_CPRELMOM		= 13 Relationship to Mother  	*
 *		STYPE_LDSP		= 17 Sealed to Parents		*
 *									*
 *	    $idime points to Family Record				*
 *		STYPE_LDSS		= 18 Sealed to Spouse		*
 *		STYPE_NEVERMARRIED	= 19 This individual never married*
 *		STYPE_MAR		= 20 Marriage			*
 *		STYPE_MARNOTE		= 21 Marriage Note		*
 *		STYPE_MARNEVER		= 22 Never Married	       	*
 *		STYPE_MARNOKIDS		= 23 This couple had no children*
 *									*
 *	    $idime points to Event Record				*
 *		STYPE_EVENT		= 30 Individual Event, idir	*
 *		STYPE_MAREVENT		= 31 Marriage Event, idmr	*
 *									*
 *	    $idime points to To-Do records tblTD.IDTD			*
 *		STYPE_TODO		= 40 To-Do Item	       		*
 *									*
 *	    a temp source type, also any negative numbers are temporary	*
 *		STYPE_TEMP		= 100 used to swap sources. 	*
 *									*
 *	$connection	a connection to the database server		*
 *									*
 *  History: 								*
 *	2010/10/17	Created						*
 *	2011/01/23	Do not emit citations if type of event has not	*
 *			yet been identified, and therefore the type of	*
 *			record referred to by $idime is unknown		*
 *	2011/02/06	escape special chars in source name and page	*
 *			text						*
 *	2011/03/03	underline 'A' in "Add Citation" button text	*
 *	2011/10/02	change name of class LegacyCitationList		*
 *	2012/01/13	change class names				*
 *	2012/03/07	define thead, tbody, and tfoot of citation table*
 *			add template for new citation row		*
 *	2012/05/30	specify class for input fields			*
 *	2013/03/08	suppress pointless error messages on expansion	*
 *	2014/02/19	use IDSX value directly in element identifiers	*
 *	2014/03/21	use Citation::getCitations instead of		*
 *			deprecated LegacyCitationList class		*
 *	2014/04/15	Display default citation while waiting for	*
 *			database server to respond to request for list	*
 *			of sources					*
 *			enable update of citation page number		*
 *	2014/05/30	use explicit style class actleftcit in		*
 *			template for new source citation to limit	*
 *			the width of the selection list to match the	*
 *			width of the display after the citation added	*
 *	2017/07/27	class LegacyCitation renamed to class Citation	*
 *	2017/11/19	use CitationSet in place of getCitations	*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';

    if (isset($type) && $type >= 1 && $type <= 40)
	$disabled	= '';
    else
	$disabled	= "disabled='disabled'";

    if (!isset($type))
	$type	= 1;
    if (!isset($idime))
	$idime	= 0;
?>
    <table id='citTable'>
<!-- edition 2012/03/07 -->
      <thead>
	<tr>
	    <th class='left'>
		<input type='hidden' name='idime'
			value='<?php print $idime; ?>'>
		<input type='hidden' name='citType'
			value='<?php print $type; ?>'>
		Citations:
	    </th>
	    <th class='center'>
		Source Name
	    </th>
	    <th class='center'>
		Details (Page)
	    </th>
	</tr>
      </thead>
      <tbody>
<?php
	// get the list of citations for this event
	if (isset($idime) && isset($type))
	{
	    $citParms	= array('idime'	=> $idime,
				'type'	=> $type);
	    $citations	= new CitationSet($citParms);
	    foreach($citations as $idsx => $cit)
	    {		// loop through citations
		$title		= $cit->getTitle();
		$idsx		= $cit->getIdsx();
		$detail		= htmlspecialchars($cit->getDetail(),
						   ENT_QUOTES); 
		$srcTitle	= htmlspecialchars($title, ENT_QUOTES);
		$srcDetail	= htmlspecialchars($detail,ENT_QUOTES);
?>
	<tr id='sourceRow<?php print $idsx; ?>' >
	    <td id='firstButton<?php print $idsx; ?>'>
		<button type='button'
			id='editCitation<?php print $idsx; ?>'>
		    Edit&nbsp;Citation
		</button>
	    </td>
	    <td id='sourceCell<?php print $idsx; ?>' >
		<input type='text' name='Source<?php print $idsx; ?>'
			id='Source<?php print $idsx; ?>' class='ina leftnc'
			value='<?php print $srctitle; ?>'
			readonly='readonly' size='50'>
		<input type='hidden' name='IDSR<?print $idsx; ?>'
			id='IDSR<?print $idsx; ?>'
			value='<?php print $idsr; ?>'>
	    </td>
	    <td>
		<input type='text' name='Page<?php print $idsx; ?>'
			id='Page<?php print $idsx; ?>'
			class='white leftnc'
			value='<?php print $srcDetail; ?>'
			size='32'>
	    </td>
	    <td id='secondButton<?php print $idsx; ?>'>
		<button type='button'
			id='delCitation<?php print $idsx; ?>'>
		    Delete&nbsp;Citation
		</button>
	    </td>
	</tr>
<?php
	    }		// loop through citations
	}		// idime and type are defined

	// put out row of buttons for citation actions
?>
      </tbody>
      <tfoot>
	<td>
	  <button type='button'
		name='AddCitation'
		id='AddCitation' <?php print $disabled; ?>>
	    <u>A</u>dd Citation
	  </button>
	</td>
      </tfoot>
    </table> <!-- end of table id='citTable' -->
<div id='cittemplates' class='hidden'>
    <table>
	<!-- The following is the template for what a new citation looks
	*    like before the user enters the citation description
	-->
	<tr id='sourceRow$rownum' >
	    <td id='firstButton$rownum'>
	    </td>
	    <td id='sourceCell$rownum' >
	      <select name='Source$rownum' class='white leftcit'>
		<option value='$idsr' selected='selected'>
		    $sourceName
		</option>
	      </select>
	    </td>
	    <td>
		<input type='text' name='Page$rownum'
			class='white leftnc'
			value='$detail'
			size='32'>
	    </td>
	    <td id='secondButton$rownum'>
	    </td>
	</tr>
<!--*********************************************************************
 *  The following is the template for what a citation looks		*
 *  like after the user enters the citation description			*
 *  This should match exactly the layout for existing citations		*
 *  as formatted by PHP.						*
 *									*
 *  Programming note:							*
 *	Although in this completed row it would be more efficient	*
 *	to encode the IDSX value in the names of the form elements	*
 *	this is not possible in the temporary row format above because	*
 *	the IDSX value has not yet been assigned.			*
 **********************************************************************-->
	<tr id='rowTemplate' >
	    <td id='firstButtonTemplate'>
		<button type='button'
			name='editCitation$rownum'
			id='editCitation$rownum'>
		    Edit Citation
		</button>
		<input type='hidden' name='idsx$rownum'
			value='$idsx'>
	    </td>
	    <td id='sourceTextTemplate'>
		<input type='text' name='Source$rownum'
			class='white leftnc'
			value='$title'
			readonly='readonly'
			size='50'>
	    </td>
	    <td>
		<input type='text' name='Page$rownum'
			class='white leftnc'
			value='$detail'
			readonly='readonly'
			size='32'>
	    </td>
	    <td id='secondButtonTemplate'>
		<button type='button'
			name='delCitation$rownum'
			id='delCitation$rownum'>
		    Delete Citation
		</button>
	    </td>
	</tr>
    </table>
</div> <!-- end of <div id='cittemplates'> -->
