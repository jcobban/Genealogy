<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  mergeIndivid.php													*
 *																		*
 *  Display a web page to support merging two individuals				*
 *  in the family tree table of individuals.							*
 *																		*
 *  URI Parameters:														*
 *		idir			unique numeric key of the first instance of		*
 *						Person											*
 *		idir2			unique numeric key of the first instance of		*
 *						Person											*
 *																		*
 *  History:															*
 *		2010/12/25		created											*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/01/28		clean up parameter validation					*
 *						do not display Gender of individuals, since it	*
 *						must match, instead hide it as a parameter		*
 *						add button to prevent merging in future			*
 *						add help balloons for all fields				*
 *		2013/02/24		standardize presentation						*
 *		2013/05/31		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						use id= instead of name= with buttons			*
 *		2013/06/01		change nominalIndex.html to legacyIndex.php		*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/10		use CSS for layout instead of tables			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/01		print $warn, which may contain debug trace		*
 *						pass debug flag to mergeUpdIndivid.php			*
 *		2015/01/01		use getBirthEvent, getChristeningEvent,			*
 *						getDeathEvent, getBuriedEvent					*
 *						and extended getName from LegacyIndiv			*
 *		2015/03/24		explicitly pass givenname, surname, gender,		*
 *						and birth year range to getIndivNamesXml		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/23		add support for treename						*
 *		2016/01/19		add id to debug trace							*
 *		2017/06/03		use new format of LegacyIndiv constructor		*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/16		legacyIndivid.php renamed to Person.php			*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/common.inc';

    // parameters to nominalIndex.php
    $nameuri			= '';
    $birthmin			= '';
    $birthmax			= '';
    $idir	    		= null;
    $idir2	    		= null;
    $lang               = 'en';

    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{
		    case 'id':
		    case 'idir':
		    {		    // identifier of individual
                $idir		= $value;
                break;
            }		    // identifier of individual

		    case 'idir2':
		    {		    // identifier of individual
                $idir2		= $value;
                break;
            }		    // identifier of individual

		    case 'lang':
            {		    // identifier of individual
                if (strlen($value) >= 2)
                    $lang		= strtolower(substr($value,0,2));
                break;
            }		    // identifier of individual

		}		        // switch on keyword
    }			        // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    if (!is_null($idir) > 0 && ctype_digit($idir))
    {			// get the requested individual
		    $person		= new Person(array('idir' => $idir));
		    $isOwner		= canUser('edit') && 
							  $person->isOwner();
		    if (!$isOwner)
				$msg	.= "You are not authorized to update this individual. ";

		    $name	= $person->getName(Person::NAME_INCLUDE_DATES);
		    $given		= $person->getGivenName();
		    if (strlen($given) > 2)
				$givenPre	= substr($given, 0, 2);
		    else
				$givenPre	= $given;
		    $surname	= $person->getSurname();
		    $nameuri	= rawurlencode($surname . ', ' . $given);
		    if (strlen($surname) == 0)
				$prefix	= '';
		    else
		    if (substr($surname,0,2) == 'Mc')
				$prefix	= 'Mc';
		    else
				$prefix	= substr($surname,0,1);
		    $treename		= $person->getTreeName();
		    // interpret sex of individual
		    $gender		= $person->getGender();
		    if ($gender == Person::MALE)
				$gender		= 'M';
		    else
		    if ($gender == Person::FEMALE)
				$gender		= 'F';
		    else
				$gender		= '';

		    // interpret encoded field values
		    $idar		= $person->get('idar');
		    $eSurname		= htmlspecialchars($surname, ENT_QUOTES);
		    $eGiven		= htmlspecialchars($given, ENT_QUOTES);
		    $eTreename		= htmlspecialchars($treename, ENT_QUOTES);

		    $birth		= $person->getBirthEvent();
		    if ($birth)
		    {
				$birthd		= $birth->getDate(9999);
				$birthyear	= floor($birth->get('eventsd')/10000);
				$birthmin	= $birthyear - 10;
				$birthmax	= $birthyear + 10;
				$idlrbirth	= $birth->get('idlrevent');
		    }
		    else
		    {
				$birthd		= '';
				$idlrbirth	= 1;
		    }

		    $chris		= $person->getChristeningEvent();
		    if ($chris)
		    {
				$chrisd		= $chris->getDate(9999);
				$idlrchris	= $chris->get('idlrevent');
		    }
		    else
		    {
				$chrisd		= '';
				$idlrchris	= 1;
		    }

		    $death		= $person->getDeathEvent();
		    if ($death)
		    {
				$deathd		= $death->getDate(9999);
				$idlrdeath	= $death->get('idlrevent');
		    }
		    else
		    {
				$deathd		= '';
				$idlrdeath	= 1;
		    }

		    $buried		= $person->getBuriedEvent();
		    if ($buried)
		    {
				$buriedd	= $buried->getDate(9999);
				$idlrburied	= $buried->get('idlrevent');
		    }
		    else
		    {
				$buriedd	= '';
				$idlrburied	= 1;
		    }

		    $title		= "Merge $name";

		    // get location names corresponding to IDLR values in record
		    $birthLocationName	= '';
		    if($idlrbirth > 1)
		    {
				$birthLocation	= new Location(array('idlr' => $idlrbirth));
				$birthLocationName	= $birthLocation->getName();
		    }		// specified

		    $chrisLocationName	= '';
		    if ($idlrchris > 1)
		    {
				$chrisLocation	= new Location(array('idlr' => $idlrchris));
				$chrisLocationName	= $chrisLocation->getName();
		    }		// specified

		    $deathLocationName	= '';
		    if($idlrdeath > 1)
		    {
				$deathLocation	= new Location(array('idlr' => $idlrdeath));
				$deathLocationName	= $deathLocation->getName();
		    }		// specified

		    $buriedLocationName	= '';
		    if($idlrburied > 1)
		    {
				$buriedLocation	= new Location(array('idlr' => $idlrburied));
				$buriedLocationName	= $buriedLocation->getName();
		    }		// specified
    }		// get the requested individual
    else
    {
		$msg		.= 'Missing or invalid value of IDIR. ';
    }

    // create the trail of breadcrumbs for the header and footer
    $links	= array('/genealogy.php'		=> 'Genealogy',
						'/genCanada.html'		=> 'Canada',
						'/Canada/genProvince.php?Domain=CAON'	=> 'Ontario',
						'/FamilyTree/Services.php'	=> 'Services',
						"/FamilyTree/nominalIndex.php?name=$nameuri"
										=> 'Nominal Index');
    if (strlen($surname) > 0)
    {
		$links["Surnames.php?initial=$prefix"]	=
								"Surnames Starting with '$prefix'";
		$links["Names.php?Surname=$surname"]	=
								"Surname '$surname'";
		$links["Person.php?idir=$idir"]	=
								"$given $surname";
    }		// surname present

    // start emitting page
    htmlHeader($title,
		       array('/jscripts/CommonForm.js',
				     '/jscripts/js20/http.js',
				     '/jscripts/util.js',
				     'mergeIndivid.js'),
    			true);
?>
<body>
<?php
    pageTop($links);
?>	
  <div class="body">
    <h1>
      <span class="right">
    	<a href="mergeIndividHelpen.html" target="help">? Help</a>
      </span>
    	<?php print $title; ?> 
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
    else 
    {		// no errors detected in validation
?>
    <form name="indForm" action="mergeUpdIndivid.php" method="post">
      <div class="row" id="ButtonRow">
    	<button type="submit" id="Submit" disabled>
    	    Merge Persons
    	</button>
    	&nbsp;
    	<button type="button" id="choose">
    	    Choose Second
    	</button>
    	&nbsp;
    	<button type="button" id="donotmerge" disabled>
    	    Do Not Ever Merge
    	</button>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="HeaderRow">
    	<div class="labelColumn"></div>
    	<div class="checkboxColumn"></div>
    	<div class="headerColumn">
    			First Person
    	</div>
    	<div class="checkboxColumn"></div>
    	<div class="headerColumn">
    			Second Person
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="IdirRow">
    	<div class="labelColumn">
    			IDIR:
    	</div>
    	<div class="checkboxColumn"></div>
    	<div class="dataColumn">
    	  <input type="text" name="idir1" id="idir1"
    			  class="ina rightnc"
    			  readonly="readonly" size="6"
    			  value="<?php print $idir; ?>">
    	  <input type="hidden" name="surname" id="surname"
    			value="<?php print $eSurname; ?>">
    	  <input type="hidden" name="givenpre" id="givenpre"
    			value="<?php print $givenPre; ?>">
    	  <input type="hidden" name="treename" id="treename"
    			value="<?php print $eTreename; ?>">
    	  <input type="hidden" name="gender" id="gender"
    			value="<?php print $gender; ?>">
    	  <input type="hidden" name="birthmin" id="birthmin"
    			value="<?php print $birthmin; ?>">
    	  <input type="hidden" name="birthmax" id="birthmax"
    			value="<?php print $birthmax; ?>">
    	</div>
    	<div class="checkboxColumn"></div>
    	<div class="dataColumn">
    	  <input type="text" name="idir2"
    			  class="ina rightnc"
    			  readonly="readonly" size="6"
    			  value="">
    	  <button type="button" name="view2" disabled="disabled">
    			Details
    	  </button>
    	  <input type="hidden" name="Sex" value="<?php print $gender; ?>">
<?php
    	if ($debug)
    	{
?>
    	  <input type="hidden" name="Debug" value="Y">
<?php
    	}
?>
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="SurnameRow">
    	<div class="labelColumn">
    			Surname:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="SurnameCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="Surname1" size="20" class="ina left"
    			  readonly="readonly"
    			  value="<?php print $eSurname; ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="SurnameCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="Surname2" size="20" class="ina left"
    			  readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="GivenNameRow">
    	<div class="labelColumn">
    			Given Names:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="GivenNameCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="GivenName1" size="32" class="ina left"
    			  readonly="readonly"
    			  value="<?php print $eGiven; ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="GivenNameCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="GivenName2" size="32" class="ina left"
    			  readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="BirthDateRow">
    	<div class="labelColumn">
    			Birth Date:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BthDateCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BirthDate1" size="16"
    			  class="ina left" readonly="readonly"
    			  value="<?php print $birthd; ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BthDateCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BirthDate2" size="16"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="BirthLocationRow">
    	<div class="labelColumn">
    			Location: 
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BthLocCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BirthLocation1" size="32"
    			  class="ina left" readonly="readonly"
    			  value="<?php print htmlspecialchars($birthLocationName,
    								      ENT_QUOTES); ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BthLocCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BirthLocation2" size="32"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="ChrisDateRow">
    	<div class="labelColumn">
    			Christening Date:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="CrsDateCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="ChrisDate1" size="16"
    			  class="ina left" readonly="readonly"
    			  value="<?php print $chrisd; ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="CrsDateCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="ChrisDate2" size="16"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="ChrisLocationRow">
    	<div class="labelColumn">
    			Location:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="CrsLocCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="ChrisLocation1" size="32"
    			  class="ina left" readonly="readonly"
    			  value="<?php print htmlspecialchars($chrisLocationName,
    						     ENT_QUOTES); ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="CrsLocCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="ChrisLocation2" size="32"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="DeathDateRow">
    	<div class="labelColumn">
    			Death Date:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="DthDateCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="DeathDate1" size="16"
    			  class="ina left" readonly="readonly"
    			  value="<?php print $deathd; ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="DthDateCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="DeathDate2" size="16"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="DeathLocationRow">
    	<div class="labelColumn">
    			Location:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="DthLocCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="DeathLocation1" size="32"
    			  class="ina left" readonly="readonly"
    			  value="<?php print htmlspecialchars($deathLocationName,
    								      ENT_QUOTES); ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="DthLocCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="DeathLocation2" size="32"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="BurialDateRow">
    	<div class="labelColumn">
    			Burial Date:
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BurDateCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BuriedDate1" size="16"
    			  class="ina left" readonly="readonly"
    			  value="<?php print $buriedd; ?>">
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BurDateCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BuriedDate2" size="16"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
      <div class="row" id="BurialLocationRow">
    	<div class="labelColumn">
    			Location: 
    	</div>
    	<div class="checkboxColumn">
    	  <input type="checkbox" name="BurLocCb1" checked>
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BuriedLocation1" size="32"
    			  class="ina left" readonly="readonly"
    			  value="<?php print htmlspecialchars($buriedLocationName,
    								      ENT_QUOTES); ?>">
    	</div>
    	<div class="checkboxColumn">
    	<input type="checkbox" name="BurLocCb2">
    	</div>
    	<div class="dataColumn">
    	  <input type="text" name="BuriedLocation2" size="32"
    			  class="ina left" readonly="readonly"
    			  value="">
    	</div>
    	<div style="clear: both;"></div>
      </div>
    </form>
<?php
    }			// no errors detected in validation
?>
    </div>  <!-- end of <div id="body"> -->
<?php
    pageBot($title . ": IDIR=$idir");
?>
<div class="balloon" id="HelpSubmit">
<p>
Clicking on this button merges the second individual into the first
individual.
</p>
</div>
<div class="balloon" id="Helpchoose">
<p>
Clicking on this button opens a dialog to choose the second individual.
The dialog displays a list of individuals in order by surname and given name,
starting with the surname and given name of the first individual,
who match the first individual on gender.
</p>
</div>
<div class="balloon" id="Helpdonotmerge">
<p>
Clicking on this button records in the database that the two individuals
are not the same individual.  In future the list of individuals that you
are given to choose from will exclude the current pair.  The page
resets to permit you to make another choice.
</p>
</div>
<div class="balloon" id="Helpidir1"
<p>
This field displays the internal record number of the first individual.
</p>
</div>
<div class="balloon" id="Helpidir2"
<p>
This field displays the internal record number of the second individual.
</p>
</div>
<div class="balloon" id="Helpview2">
<p>
Clicking on this button opens a window to display the details about the
second individual.
</p>
</div>
<div class="balloon" id="HelpSurnameCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the surname of the first individual.
</p>
</div>
<div class="balloon" id="HelpSurnameCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the surname of the second individual.
</p>
</div>
<div class="balloon" id="HelpSurname">
<p>
This readonly field displays the surname of the individual.
</p>
</div>
<div class="balloon" id="HelpGivenNameCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the given name of the first individual.
</p>
</div>
<div class="balloon" id="HelpGivenNameCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the given name of the second individual.
</p>
</div>
<div class="balloon" id="HelpGivenName">
<p>
This readonly field displays the given name of the individual.
</p>
</div>
<div class="balloon" id="HelpBthDateCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the birth date of the first individual.
</p>
</div>
<div class="balloon" id="HelpBthDateCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the birth date of the second individual.
</p>
</div>
<div class="balloon" id="HelpBirthDate">
<p>
This readonly field displays the birth date of the individual.
</p>
</div>
<div class="balloon" id="HelpBthLocCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the birth location of the first individual.
</p>
</div>
<div class="balloon" id="HelpBthLocCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the birth location of the second individual.
</p>
</div>
<div class="balloon" id="HelpBirthLocation">
<p>
This readonly field displays the birth location of the individual.
</p>
</div>
<div class="balloon" id="HelpCrsDateCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the christening date of the first individual.
</p>
</div>
<div class="balloon" id="HelpCrsDateCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the christening date of the second individual.
</p>
</div>
<div class="balloon" id="HelpChrisDate">
<p>
This readonly field displays the christening date of the individual.
</p>
</div>
<div class="balloon" id="HelpCrsLocCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the christening location of the first individual.
</p>
</div>
<div class="balloon" id="HelpCrsLocCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the christening location of the second individual.
</p>
</div>
<div class="balloon" id="HelpChrisLocation">
<p>
This readonly field displays the christening location of the individual.
</p>
</div>
<div class="balloon" id="HelpDthDateCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the death date of the first individual.
</p>
</div>
<div class="balloon" id="HelpDthDateCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the death date of the second individual.
</p>
</div>
<div class="balloon" id="HelpDeathDate">
<p>
This readonly field displays the death date of the individual.
</p>
</div>
<div class="balloon" id="HelpDthLocCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the death location of the first individual.
</p>
</div>
<div class="balloon" id="HelpDthLocCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the death location of the second individual.
</p>
</div>
<div class="balloon" id="HelpDeathLocation">
<p>
This readonly field displays the death location of the individual.
</p>
</div>
<div class="balloon" id="HelpBurDateCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the buried date of the first individual.
</p>
</div>
<div class="balloon" id="HelpBurDateCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the buried date of the second individual.
</p>
</div>
<div class="balloon" id="HelpBuriedDate">
<p>
This readonly field displays the buried date of the individual.
</p>
</div>
<div class="balloon" id="HelpBurLocCb1">
<p>
Clicking on this checkbox indicates that the merged individual is
to retain the buried location of the first individual.
</p>
</div>
<div class="balloon" id="HelpBurLocCb2">
<p>
Clicking on this checkbox indicates that the merged individual is
to take the buried location of the second individual.
</p>
</div>
<div class="balloon" id="HelpBuriedLocation">
<p>
This readonly field displays the buried location of the individual.
</p>
</div>
</body>
</html>
