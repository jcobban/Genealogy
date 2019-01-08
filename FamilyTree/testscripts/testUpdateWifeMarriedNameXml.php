<?php
namespace Genealogy;
use \PDO;
use \Exception;
/**
 *  testUpdateWifeMarriedNameXml.php
 *
 *  Display a web page for invoking the script updateWifeMarriedNameXml.php
 *
 *
 *  History:
 *	2013/01/17	created
 *
 * Copyright &copy; 2013 James A. Cobban
 **/
    require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Test Update Wife Married Name',
 		array( '/jscripts/CommonForm.js',
 			'/jscripts/util.js'));
?>
<body>
<?php
    pageTop(array());
?>
  <div class='body'>
      <h1>
	<span class='right'>
	  <a href='testUpdateWifeMarriedNameXmlHelp.html' target='help'>
		? Help
	  </a>
	</span>
	Test Update Wife Married Name
      <div style='clear: both;'></div>
  </h1>
<?php
    if (strlen($msg) > 0)
    {
?>
  <p class='message'>
	<?php print $msg; ?> 
  </p>
<?php
    }	// error message to be displayed

?>
  <form name='famForm' action='/updateWifeMarriedNameXml.php' method='post'>
      <div class='row'>
	<label class='column1'>
	    IDMR:
	</label>
	<input type='text' class='white rightnc' id='idmr' name='idmr'
		size='6' value='0'>
	</div>
      <div class='row'>
	<button type='submit' name='Submit'>
	    <u>U</u>pdate Marriage
	</button>
      </div>
  </form>
  </div>
<?php
    pageBot();
?>
<div class='balloon' id='HelpHusbGivenName'>
<p>This displays the given names of the husband.
This is a read-only field.  The husband is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpHusbSurname'>
<p>This displays the family name of the husband. 
This is a read-only field.  The husband is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpWifeGivenName'>
<p>This displays the given names of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpWifeSurname'>
<p>This displays the family name of the wife. 
This is a read-only field.  The wife is altered by clicking on one
of the buttons at the end of this row. 
</p>
</div>
<div class='balloon' id='HelpchangeHusb'>
<p>Selecting this button pops up a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the husband
in this marriage.
</p>
</div>
<div class='balloon' id='HelpcreateHusb'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to create a
new individual in the family tree to be the husband in this marriage.
</p>
</div>
<div class='balloon' id='HelpdetachHusb'>
<p>Selecting this button detaches the currently assigned husband from this
marriage.  It is not necessary to do this before selecting or creating
a new husband.
</p>
</div>
<div class='balloon' id='HelpchangeWife'>
<p>Selecting this button pops up a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
that permits you to select an
already existing individual from the family tree to assign as the wife
in this marriage.
</p>
</div>
<div class='balloon' id='HelpcreateWife'>
<p>Selecting this button pops up a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
that permits you to create a
new individual in the family tree to be the wife in this marriage.
</p>
</div>
<div class='balloon' id='HelpdetachWife'>
<p>Selecting this button detaches the currently assigned wife from this
marriage.  It is not necessary to do this before selecting or creating
a new wife.
</p>
</div>
<div class='balloon' id='HelpMarD'>
<p>Supply the date of the marriage.  The program understands a wide
variety of date formats which are too extensive to be described here.
It is suggested that you enter the date of marriage in the form "dd mmm yyyy"
where "dd" is the day of the month, "mmm" is a 3 letter abbreviation for the
name of the month, and "yyyy" is the year of the marriage.
</p>
<p>See <a href='datesHelp.html'>supported date formats</a> for details.
</p>
</div>
<div class='balloon' id='HelpMarLoc'>
<p>Supply the location of the marriage.  The text you enter is used to
select an appropriate Location record.  This is done by first doing a
case-insensitive search for a match on the complete text you entered, and if
this fails then a search is done for a match on the short name of the
location.  If no match is found on either search then a new location is
created using exactly the text you entered.  Subsequently the only way that
you can change the appearance of the location is to either select a different
location by typing in its name or short name, or by editing the location
record itself.
</p>
</div>
<div class='balloon' id='HelpIDMS'>
<p>This selection list permits you to specify the ending or current
status of this marriage.
</p>
</div>
<div class='balloon' id='HelpMarriedNameRule'>
<p>This selection list permits you to specify whether or not the wife
took her husband's surname as a result of the marriage.  The default
is the traditional practice.
</p>
</div>
<div class='balloon' id='HelpNotes'>
<p>Supply extended textual notes about the marriage.
</p>
<p>Although you might be tempted to include the text of a newspaper notice
about the marriage in this field, it is recommended that you put that
text into the citation text field instead.
</p>
</div>
<div class='balloon' id='HelpAddCitation'>
<p>Selecting this button permits you to add a citation to document a
source of your knowledge of this family.
</p>
</div>
<div class='balloon' id='HelpeditCitation'>
<p>Selecting this button pops up a dialog that permits you to specify
more information about a citation beyond the name of the source and the
page within that source where the evidence is located.  This dialog also
permits you to modify the name of the source and the page identification.
</p>
</div>
<div class='balloon' id='HelpSource'>
<p>When you add a new source citation this field is a selection list
of all of the defined sources currently used in the family tree, from
which you can select the source that you wish to cite.
</p>
<p>Once the citation is add this becomes a read-only text field
documenting the selection.  If you wish to change the source select the
"Edit Citation" button.
</p>
</div>
<div class='balloon' id='HelpPage'>
<p>When you add a new source citation this field is a text field
for specifying the page within the selected source that contains
the evidence for this family.
</p>
<p>Once the citation is add this becomes a read-only text field
documenting the page.  If you wish to change the page select the
"Edit Citation" button.
</p>
</div>
<div class='balloon' id='HelpdelCitation'>
<p>Selecting this button removes the citation.
</p>
</div>
<div class='balloon' id='HelpaddChild'>
<p>Selecting this button, or using the keyboard short-cut alt-E, opens a
<a href='chooseIndividHelp.html' target='_blank'>dialog</a> 
to choose an existing individual
in the family tree database to add as a child of this family.
</p>
</div>
<div class='balloon' id='HelpaddNewChild'>
<p>Selecting this button, or using the keyboard short-cut alt-N, opens a 
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
to create a new individual in the
family tree database that is added as a child of this family.
</p>
</div>
<div class='balloon' id='Helpupdate'>
<p>Selecting this button, or using the keyboard short-cuts alt-U or ctl-S, 
updates the database to apply all of the pending 
changes to the marriage record.  Note that updates to citations and for
managing the list of children are applied to the database independently.
</p>
</div>
<div class='balloon' id='HelporderChildren'>
<p>Selecting this button, or using the keyboard short-cut alt-O, 
reorders the children of this marriage by their
dates of birth.
</p>
</div>
<div class='balloon' id='HelpeditChild'>
<p>Selecting this button opens a
<a href='editIndividHelp.html' target='_blank'>dialog</a> 
to edit the detailed information
about the child summarized in this line of the form.
</p>
</div>
<div class='balloon' id='HelpdetChild'>
<p>Selecting this button detaches the child summarized in this line of the
form from this family.  You can then go to another family and attach the
child there.
</p>
</div>
</body>
</html>
