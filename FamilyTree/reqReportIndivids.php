<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  reqReportIndivids.php												*
 *																		*
 *  Request a report of individuals matching a search.					*
 *																		*
 *  History:															*
 *		2011/02/05		created											*
 *		2012/01/13		change class names								*
 *		2012/02/08		add dates to criteria							*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/02/08		standardize appearance of <select>				*
 *		2014/03/10		use CSS instead of tables for form layout		*
 *		2014/04/21		add support for individual event place and date	*
 *						and christening and buried date					*
 *		2015/06/30		add support for searching by event description	*
 *						and by cause of death							*
 *						add support for joining tblIR and tblER			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2016 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

    htmlHeader('Request Report on Persons',
				array(	'/jscripts/CommonForm.js',
					'/jscripts/js20/http.js',
					'/jscripts/util.js',
					'/jscripts/CommonForm.js',
					'reqReportIndivids.js'),
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
		<a href="reqReportIndividsHelpen.html" target="help">? Help</a>
      </span>
		Request Report on Persons
    </h1>
<?php
    showTrace();
?>
  <form action="reportIndivids.php" method="get" id="reqForm">
<?php
    if ($debug)
    {
?>
		<input type="hidden" name="debug" id="debug" value="Y">
<?php
    }
?>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="chooseFields">
		    Fields:
		  </label>
		  <select id="chooseFields" size="10" class="white left">
		    <option value="0">Choose fields to include
		    <option value="tblIR.IDIR">Link to Details
		    <option value="tblIR.FSID">
				Person ID from FamilySearch
		    <option value="tblIR.Surname">Surname
		    <option value="tblIR.SoundsLike">Surname Soundex Code
		    <option value="tblIR.GivenName">GivenName
		    <option value="tblIR.Prefix">Name Prefix
		    <option value="tblIR.Title">Name Title
		    <option value="tblIR.NameNote">Name Note
		    <option value="tblIR.Gender">Gender
		    <option value="tblIR.BirthD">Birth Date
		    <option value="tblIR.IDLRBirth">Birth Location
		    <option value="tblIR.BirthNote">Birth Note
		    <option value="tblIR.ChrisD">Christening Date
		    <option value="tblIR.IDLRChris">Christening Location
		    <option value="tblIR.ChrisNote">Christening Note
		    <option value="tblIR.ChrTerm">Christening Term
		    <option value="tblER.EventD">Event Date
		    <option value="tblER.Description">Event Description
		    <option value="tblER.Desc">Event Note
		    <option value="tblER.IDLREvent">Event Location
		    <option value="tblIR.DeathD">Death Date
		    <option value="tblIR.IDLRDeath">Death Location
		    <option value="tblIR.DeathCause">Cause of Death
		    <option value="tblIR.DeathNote">Death Note
		    <option value="tblIR.BuriedD">Buried Date
		    <option value="tblIR.BuriedNote">Buried Note
		    <option value="tblIR.IDLRBuried">Buried Location
		    <option value="tblIR.Cremated">Cremated?
		    <option value="tblIR.Living">Living
		    <option value="tblIR.BaptismD">LDS Baptism Date 
		    <option value="tblIR.BaptismKind">LDS Baptism Kind
		    <option value="tblIR.IDTRBaptism">LDS Baptism Location
		    <option value="tblIR.BaptismNote">LDS Baptism Note
		    <option value="tblIR.LDSB">LDS Baptism Temple Ready
		    <option value="tblIR.EndowD">LDS Endowment Date
		    <option value="tblIR.IDTREndow">LDS Endowment Location
		    <option value="tblIR.LDSE">LDS Endowment Temple Ready
		    <option value="tblIR.EndowNote">LDS Endowment Note
		    <option value="tblIR.ConfirmationD">LDS Confirmation Date
		    <option value="tblIR.ConfirmationKind">LDS Confirmation Date
		    <option value="tblIR.IDTRConfirmation">LDS Confirmation Location
		    <option value="tblIR.ConfirmationNote">LDS Confirmation Note
		    <option value="tblIR.LDSC">LDS Confirmation Temple Ready
		    <option value="tblIR.InitiatoryD">LDS Initiatory Date
		    <option value="tblIR.IDTRInitiatory">LDS Initiatory Location
		    <option value="tblIR.InitiatoryNote">LDS Initiatory Note
		    <option value="tblIR.LDSI">LDS Initiatory Temple Ready
		    <option value="tblIR.IDMRPref">Preferred Marriage
		    <option value="tblIR.IDMRParents">Preferred Parents
		    <option value="tblIR.IDAR">Mailing Address
		    <option value="tblIR.AncInterest">Ancestor Interest Level
		    <option value="tblIR.DecInterest">Descendant Interest Level
		    <option value="tblIR.Tag1">Tag1
		    <option value="tblIR.Tag2">Tag2
		    <option value="tblIR.Tag3">Tag3
		    <option value="tblIR.Tag4">Tag4
		    <option value="tblIR.Tag5">Tag5
		    <option value="tblIR.Tag6">Tag6
		    <option value="tblIR.Tag7">Tag7
		    <option value="tblIR.Tag8">Tag8
		    <option value="tblIR.Tag9">Tag9
		    <option value="tblIR.TagGroup">TagGroup
		    <option value="tblIR.TagAnc">TagAnc
		    <option value="tblIR.TagDec">TagDec
		    <option value="tblIR.SaveTag">Save Tag
		    <option value="tblIR.qsTag">qs Tag
		    <option value="tblIR.SrchTagIGI">Search Tag IGI
		    <option value="tblIR.SrchTagRG">Search Tag RG
		    <option value="tblIR.SrchTagFS">Search Tag FS
		    <option value="tblIR.RGExclude">RG Exclude
		    <option value="tblIR.ReminderTag">Reminder Tag
		    <option value="tblIR.ReminderTagDeath">Reminder Tag Death
		    <option value="tblIR.TreeNum">Tree Number
		    <option value="tblIR.UserRef">User ID/Number
		    <option value="tblIR.AncestralRef">Ancestral File Number
		    <option value="tblIR.Notes">General Notes
		    <option value="tblIR.References">Research Notes
		    <option value="tblIR.Medical">Medical Notes
		    <option value="tblIR.PPCheck">Potential Problems Exist,
		    <option value="tblIR.Imported">was imported
		    <option value="tblIR.Added">date record was added
		    <option value="tblIR.AddedTime">time record was added
		    <option value="tblIR.Updated">date record was modified
		    <option value="tblIR.UpdatedTime">time record was modified
		    <option value="tblIR.Relations">encoded relationship string.
		    <option value="tblIR.IntelliShare">IntelliShare
		    <option value="tblIR.NeverMarried">Never Married
		    <option value="tblIR.DirectLine">Direct Line
		    <option value="tblIR.ColorTag">Mary Hill ancestor colors
		    <option value="tblIR.Private">Private
		    <option value="tblIR.PPExclude">Exclude from Potential Problems Report
		    <option value="tblIR.DNA">DNA
		  </select>
		</div>
		<div class="column2">
		  <label class="column2" for="chooseSort" class="label">
		    Order By:
		  </label>
		  <select id="chooseSort" size="10" class="white left">
		    <option value="tblIR.0">Choose fields to sort on
		    <option value="tblIR.IDIR">Numeric Key
		    <option value="tblIR.FSID">
				Person ID from FamilySearch
		    <option value="tblIR.Surname">Surname
		    <option value="tblIR.SoundsLike">Surname Soundex Code
		    <option value="tblIR.GivenName">GivenName
		    <option value="tblIR.Prefix">Name Prefix
		    <option value="tblIR.Title">Name Title
		    <option value="tblIR.NameNote">Name Note
		    <option value="tblIR.Gender">Gender
		    <option value="tblIR.BirthSD">Birth Date
		    <option value="tblIR.IDLRBirth">Birth Location
		    <option value="tblIR.ChrisSD">Christening Date
		    <option value="tblIR.IDLRChris">Christening Location
		    <option value="tblIR.ChrTerm">Christening Term
		    <option value="tblER.EventSD">Event Date
		    <option value="tblER.IDLREvent">Event Location
		    <option value="tblIR.DeathSD">Death Date
		    <option value="tblIR.IDLRDeath">Death Location
		    <option value="tblIR.BuriedSD">Buried Date
		    <option value="tblIR.IDLRBuried">Buried Location
		    <option value="tblIR.Cremated">Cremated?
		    <option value="tblIR.BirthNote">Birth Note
		    <option value="tblIR.ChrisNote">Christening Note
		    <option value="tblIR.DeathNote">Death Note
		    <option value="tblIR.BuriedNote">Buried Note
		    <option value="tblIR.BaptismNote">LDS Baptism Note
		    <option value="tblIR.EndowNote">LDS Endowment Note
		    <option value="tblIR.Living">Living
		    <option value="tblIR.BaptismSD">LDS Baptism Date 
		    <option value="tblIR.BaptismKind">LDS Baptism Kind
		    <option value="tblIR.IDTRBaptism">LDS Baptism Location
		    <option value="tblIR.LDSB">LDS Baptism Temple Ready
		    <option value="tblIR.EndowSD">LDS Endowment Date
		    <option value="tblIR.IDTREndow">LDS Endowment Location
		    <option value="tblIR.LDSE">LDS Endowment Temple Ready
		    <option value="tblIR.ConfirmationSD">LDS Confirmation Date
		    <option value="tblIR.ConfirmationKind">LDS Confirmation Date
		    <option value="tblIR.IDTRConfirmation">LDS Confirmation Location
		    <option value="tblIR.ConfirmationNote">LDS Confirmation Note
		    <option value="tblIR.LDSC">LDS Confirmation Temple Ready
		    <option value="tblIR.InitiatorySD">LDS Initiatory Date
		    <option value="tblIR.IDTRInitiatory">LDS Initiatory Location
		    <option value="tblIR.InitiatoryNote">LDS Initiatory Note
		    <option value="tblIR.LDSI">LDS Initiatory Temple Ready
		    <option value="tblIR.IDMRPref">Preferred Marriage
		    <option value="tblIR.IDMRParents">Preferred Parents
		    <option value="tblIR.IDAR">Mailing Address
		    <option value="tblIR.AncInterest">Ancestor Interest Level
		    <option value="tblIR.DecInterest">Descendant Interest Level
		    <option value="tblIR.Tag1">Tag1
		    <option value="tblIR.Tag2">Tag2
		    <option value="tblIR.Tag3">Tag3
		    <option value="tblIR.Tag4">Tag4
		    <option value="tblIR.Tag5">Tag5
		    <option value="tblIR.Tag6">Tag6
		    <option value="tblIR.Tag7">Tag7
		    <option value="tblIR.Tag8">Tag8
		    <option value="tblIR.Tag9">Tag9
		    <option value="tblIR.TagGroup">TagGroup
		    <option value="tblIR.TagAnc">TagAnc
		    <option value="tblIR.TagDec">TagDec
		    <option value="tblIR.SaveTag">Save Tag
		    <option value="tblIR.qsTag">qs Tag
		    <option value="tblIR.SrchTagIGI">Search Tag IGI
		    <option value="tblIR.SrchTagRG">Search Tag RG
		    <option value="tblIR.SrchTagFS">Search Tag FS
		    <option value="tblIR.RGExclude">RG Exclude
		    <option value="tblIR.ReminderTag">Reminder Tag
		    <option value="tblIR.ReminderTagDeath">Reminder Tag Death
		    <option value="tblIR.TreeNum">Tree Number
		    <option value="tblIR.UserRef">User ID/Number
		    <option value="tblIR.AncestralRef">Ancestral File Number
		    <option value="tblIR.Notes">General Notes
		    <option value="tblIR.References">Research Notes
		    <option value="tblIR.Medical">Medical Notes
		    <option value="tblIR.DeathCause">Cause of Death
		    <option value="tblIR.PPCheck">Potential Problems Exist,
		    <option value="tblIR.Imported">was imported
		    <option value="tblIR.Added">date record was added
		    <option value="tblIR.AddedTime">time record was added
		    <option value="tblIR.Updated">date record was modified
		    <option value="tblIR.UpdatedTime">time record was modified
		    <option value="tblIR.Relations">encoded relationship string.
		    <option value="tblIR.IntelliShare">IntelliShare
		    <option value="tblIR.NeverMarried">Never Married
		    <option value="tblIR.DirectLine">Direct Line
		    <option value="tblIR.ColorTag">Mary Hill ancestor colors
		    <option value="tblIR.Private">Private
		    <option value="tblIR.PPExclude">Exclude from Potential Problems Report, 
		    <option value="tblIR.DNA">DNA
		  </select>
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="fields">Chosen:</label>
		  <select name="fields[]" id="fields" size="10" multiple="multiple"
				class="white left">
		  </select>
		</div>
		<div class="column2">
		  <label class="column2" for="orderby">Chosen:</label>
		  <select name="orderby[]" id="orderby" size="10" multiple="multiple"
				class="white left">
		  </select>
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="GivenName">
		    Given Name:
		  </label>
		  <input type="text" size="32" maxlength="120" 
				name="GivenName" id="GivenName"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="Surname">
		    Surname:
		  </label>
		  <input type="text" size="32" maxlength="120" 
				name="Surname" id="Surname"
				class="white left">
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="Gender">
		    Gender:
		  </label>
		  <select name="Gender" id="Gender" size="1" class="white left">
		    <option value="">Don"t Care</option>
		    <option value="0">Male</option>
		    <option value="1">Female</option>
		    <option value="2">Unknown</option>
		  </select>
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="IDLRBirth">
		    Birth Place:
		  </label>
		  <input type="text" size="32" maxlength="255" 
				name="IDLRBirth" id="IDLRBirth"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="BirthDate">
		    Date:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="BirthDate" id="BirthDate"
				class="white left">
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="IDLRChris">
		    Christening Place:
		  </label>
		  <input type="text" size="32" maxlength="255" 
				name="IDLRChris" id="IDLRChris"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="ChrisDate">
		    Date:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="ChrisDate" id="ChrisDate"
				class="white left">
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="IDLREvent">
		    Event Place:
		  </label>
		  <input type="text" size="32" maxlength="255" 
				name="IDLREvent" id="IDLREvent"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="EventDate">
		    Date:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="EventDate" id="EventDate"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="Description">
		    Description:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="Description" id="Description"
				class="white left">
		</div>
		<div style="clear: both;"></div>
      </div>
      <div class="row">
		<div class="column1">
		  <label class="column1" for="IDLRDeath">
		    Death Place:
		  </label>
		  <input type="text" size="32" maxlength="255" 
				name="IDLRDeath" id="IDLRDeath"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="DeathDate">
		    Date:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="DeathDate" id="DeathDate"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="DeathCause">
		    Cause:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="DeathCause" id="DeathCause"
				class="white left">
		</div>
		<div style="clear: both;"></div>
      </div>
		<div class="column1">
		  <label class="column1" for="IDLRBuried">
		    Buried Place:
		  </label>
		  <input type="text" size="32" maxlength="255"
				name="IDLRBuried" id="IDLRBuried"
				class="white left">
		</div>
		<div class="column2">
		  <label class="column2" for="BuriedDate">
		    Date:
		  </label>
		  <input type="text" size="12" maxlength="255" 
				name="BuriedDate" id="BuriedDate"
				class="white left">
		</div>
		<div style="clear: both;"></div>
      </div>
      <p>
		<button type="submit" id="Submit">Request</button>
      </p>
  </form>

</div>
<?php
    pageBot();
?>
    <div class="balloon" id="HelpchooseFields">
        Use the mouse to click on those fields that you wish to include in the report. 
    </div>
    <div class="balloon" id="Helpfields">
        List of fields that you have chosen to include in the report.
    </div>
    <div class="balloon" id="HelpchooseSort">
        Use the mouse to click on the fields to be used for sorting the report.
    </div>
    <div class="balloon" id="Helporderby">
        List of fields that you have chosen to use to sort the report.
    </div>
    <div class="balloon" id="HelpSurname">
        If you wish to restrict the report to only include individuals with a
        particular surname, enter it here.
    </div>
    <div class="balloon" id="HelpGivenName">
        To do GivenName.
    </div>
    <div class="balloon" id="HelpGender">
        To do Gender.
    </div>
    <div class="balloon" id="HelpIDLRBirth">
        To do Birth Place.
    </div>
    <div class="balloon" id="HelpBirthDate">
        To do Birth Date.
    </div>
    <div class="balloon" id="HelpIDLRChris">
        To do Christening Place.
    </div>
    <div class="balloon" id="HelpChrisDate">
        To do Christening Date.
    </div>
    <div class="balloon" id="HelpIDLRDeath">
        To do Death Place.
    </div>
    <div class="balloon" id="HelpDeathDate">
        To do Death Date.
    </div>
    <div class="balloon" id="HelpIDLRBuried">
        To do Buried Place.
    </div>
    <div class="balloon" id="HelpEventDate">
        To do Event Date.
    </div>
    <div class="balloon" id="HelpIDLREvent">
        To do Event Place.
    </div>
    <div class="balloon" id="HelpEventDate">
        To do Event Date.
    </div>
    <div class="balloon" id="HelpSubmit">
        Click on this button with the mouse to request the report, or press the
        Enter key while in a normal text input field.
    </div>
  </body>
</html>
