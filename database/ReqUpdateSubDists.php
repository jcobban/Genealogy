<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdateSubDists.php						*
 *									*
 *  Request to update or view a portion of the SubDistricts database.	*
 *									*
 *  History (of ReqUpdateSubDists.html):				*
 *	2010/10/01	Reformat to new page layout.			*
 *	2010/11/24	link to help page				*
 *	2011/01/20	increase size of selects to 9 so all censuses	*
 *			display						*
 *	2011/03/09	improve separation of HTML and Javascript	*
 *	2011/06/27	add support for 1916 census			*
 *	2012/09/17	support census identifiers			*
 *	2013/07/30	add Facebook like				*
 *			add help for all form elements			*
 *			standardize appearance of submit button		*
 *	2013/08/18	add support for 1921 census			*
 *	2014/06/29	clear up layout of <h1> and botcrumbs		*
 *	2015/03/15	internationalize all text strings including	*
 *			province names					*
 *	2015/12/10	escape province names				*
 *									*
 *  History (of ReqUpdateSubDists.php):					*
 *	2015/06/02	renamed and made conditional on user's auth	*
 *			display warning messages			*
 *	2015/07/02	access PHP includes using include_path		*
 *	2016/01/20	display debug trace				*
 *			display error messages				*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Census.inc';
    require_once __NAMESPACE__ . '/CensusSet.inc';
    require_once __NAMESPACE__ . '/DomainSet.inc';
    require_once __NAMESPACE__ . '/Country.inc';
    require_once __NAMESPACE__ . '/common.inc';

    $cc			= 'CA';
    $countryName	= 'Canada';
    $provinceText	= 'Province';
    $provincesText	= 'Provinces';
    $censusId		= '';
    $censusYear		= '';
    $provinces		= '';
    $province		= 'CW';
    $distId		= null;
    foreach($_GET as $key => $value)
    {				// loop through parameters
	switch($key)
	{			// act on specific parameters
	    case 'Census':
	    {			// census identifier
		// support old parameter value
		if (strlen($value) == 4)
		{
		    $censusYear	= $value;
		    if (intval($censusYear) < 1867)
			$censusId	= 'CW' . $value;
		    else
			$censusId	= 'CA' . $value;
		}
		else
		    $censusId	= $value;

		// validate
		$censusYear	= substr($censusId, 2);
		$censusRec	= new Census(array('censusid'	=> $censusId,
						   'collective'	=> 0,
						   'create' => true));
		if ($censusRec->get('partof'))
		{
		    $cc		= $censusRec->get('partof');
		    $censusId	= $cc . $censusYear;
		}
		else
		    $cc		= substr($censusId, 0, 2);

		if ($cc != 'CA')
		{
		    $provincesText	= 'States';
		    $provinceText	= 'States';
		}
		$countryObj	= new Country(array('code' => $cc));
		$countryName	= $countryObj->get('name');
		$provinces	= $censusRec->get('provinces');
		break;
	    }			// census identifier

	    case 'Province':
	    {			// province code
		$province	= $value;
		if (strlen($value) == 2)
		{		// have a province
		    $pos	= strpos($provinces, $value);
		    if ($pos === false || ($pos & 1) == 1)
		    {		// no match in list of provinces
			$msg	.= "Invalid value '$value' for Province. ";
			$msg	.= "\$pos=" . print_r($pos, true);
			$msg	.= " \$provinces='$provinces' ";
		    }		// no match in list of provinces
		}		// have a province
		else
		if (strlen($value) != 0)
		    $msg	.= "Invalid value '$value' for Province. ";
		break;
	    }			// province code

	    case 'District':
	    {			// district number
		$distId		= $value;
		$result		= array();
		if (preg_match("/^([0-9]+)(\.[05])?$/", $distId, $result) != 1)
		    $msg	.= "District value '$distId' is invalid. ";
		else
		{
		    if (count($result) > 2 && $result[2] == '.0')
			$distId	= $result[1];	// integral portion only
		}
		break;
	    }			// District number
	}			// act on specific parameters
    }				// loop through parameters

    // notify the invoker if they are not authorized
    $update	= canUser('edit');
    $title	= "Census Administration: $countryName: Choose Sub-District Table";

    htmlHeader($title,
	       array(	'/jscripts/js20/http.js',
			'/jscripts/util.js',
			'ReqUpdateSubDists.js'),
	       false);
?>
<body>
<?php
    pageTop(array(
	'/genealogy.php'	=> 'Genealogy', 
	"/gen$cc.html"		=> $countryName,
	'EditCensuses.php'	=> 'Censuses',
	"DistForm.php?Census=$censusId&Province=$province"	=> 'Districts'));
?>
 <div class='body'>
  <h1>
      <span class='right'>
	<a href='ReqUpdateSubDistsHelpen.html' target='help'>? Help</a>
      </span>
	<?php print $title; ?>
      <span style='clear: both;'></span>
  </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {			// display error messages
?>
    <p class='message'><?php print $msg; ?></p>
<?php
    }			// display error messages
    else
    {			// no errors
	$getParms	= array('partof'	=> null,
				'countrycode'	=> $cc);
	$censuses	= new CensusSet($getParms);
	if ($censusId == '')
	    $selected	= "selected='selected'";
	else
	    $selected	= '';
	showTrace();
?> 
    <form action='SubDistForm.php'
          name='distForm'>
      <table id='formTable'>
        <tr id='distRow'>
          <td class='label'>
	    Census:
	  </td>
	  <td>
	    <input type='hidden' name='censusId' value='<?php print $censusId; ?>'>
	    <select size='9' name='CensusYear'>
	        <option value='' <?php print $selected; ?>>
	      Choose a Census:</option>
<?php
	foreach ($censuses as $crec)
	{
	    if ($censusId == $crec->get('censusid'))
		$selected	= "selected='selected'";
	    else
		$selected	= '';
?>
	        <option value='<?php print $crec->get('censusid'); ?>' <?php print $selected; ?>>
	      <?php print $crec->get('name'); ?></option>
<?php
	}
?>
	    </select>
	    <!-- The following field contains the census id passed to scripts-->
	    <input name='Census' type='hidden' value=''> 
        </td>
          <td class='label'>
	    <?php print $provinceText; ?>:
	  </td>
	  <td>
	    <select size='9' name='Province'>
<?php
	if ($censusYear > 1867)
	{			// post confederation census
	    if ($province == 'CW' || $province == '')
		$select		= $selected;
	    else
		$select		= '';
?>
	    <option value='' <?php print $select; ?>>
		All <?php print $provincesText; ?>
	    </option>
<?php
	}			// post confederation census

	$getParms	= array('cc' => $cc);
	$domains	= new DomainSet($getParms);
	for ($ip = 0; $ip < strlen($provinces); $ip = $ip + 2)
	{			// loop through all provinces
	    $pc		= substr($provinces, $ip, 2);
	    if (array_key_exists($cc . $pc, $domains))
		$pname	= htmlspecialchars($domains[$cc . $pc]->getName());
	    else
		$pname	= "state $cc$pc";
	    if ($pc == $province)
		$select		= $selected;
	    else
		$select		= '';
?>
	    <option value='<?php print $pc; ?>' <?php print $select; ?>>
		<?php print $pname; ?>
	    </option>
<?php
	}			// loop through all provinces
?>
	    </select> 
        </td>
        <td id='msgCell'>
            <span class='label'>District:</span>
        </td>
        <td>
	  <select size='9' name='District'>
	  </select> 
	</td>
      </tr>
    </table>
    <button type='submit' class='button' id='Submit'>Request Form</button>
  </form>
<?php
    }			// no errors
?>
  </div>
<?php
    pageBot();
?>
  <div class='balloon' id='msgDiv'>
  </div>
  <div id='hideMsgTemplate' class='hidden'>
    <!-- template for dialog reporting error from getIndivNamesXml.php -->
    <form name='Msg$template' id='Msg$template'>
      <p class='message'>$msg</p>
      <p>
        <button type='button' id='closeDlg$template'>
  	OK
        </button>
      </p>
    </form>
  </div>		<!-- id='hideMsgTemplate'-->
  <!-- popup help balloons -->
  <div class='balloon' id='HelpCensusYear'>
    The Census selection list is used to specify the year of the
    census enumeration for which you wish to update a Sub-District description.
  </div>
  <div class='balloon' id='HelpProvince'>
    The Province selection list is an alphabetical list of the
    provinces in the specified census.  It is used if you wish to limit the 
    displayed list of districts to a specific province.
  </div>
  <div class='balloon' id='HelpDistrict'>
    The District selection list is an alphabetical list of census
    districts, roughly equivalent to federal electoral ridings.  This tool
    permits editting the descriptions of the sub-districts within a specified
    census district.  You may either highlight a district and then click on
    "Request Form" or double-click on a district.
  </div>
  <div class='balloon' id='HelpSubmit'>
    Click on this button to open the form for editing the information about the
    sub-districts of the selected district.
  </div>
  <!-- templates for dynamic HTML -->
  <div class='hidden' id='templates'>
    <div id='noDistFileMsg'>
      Census summary script 'CensusGetDistricts.php?Census=$census' failed
    </div>
    <div id='badDistFileMsg'>
      ReqUpdateSubdists.js:gotDistFile: error response: $msgs
    </div>
    <div id='chooseDistText'>
      Select a District:
    </div>
    <div id='prov'>
      All <?php print $provincesText; ?>
    </div>
    <div id='provAB'>
      Alberta
    </div>
    <div id='provBC'>
      British Columbia
    </div>
    <div id='provCE'>
      Canada East (Quebec)
    </div>
    <div id='provCW'>
      Canada West (Ontario)
    </div>
    <div id='provMB'>
      Manitoba
    </div>
    <div id='provNB'>
      New Brunswick
    </div>
    <div id='provNS'>
      Nova Scotia
    </div>
    <div id='provNT'>
      North-West Territories
    </div>
    <div id='provON'>
      Ontario
    </div>
    <div id='provPI'>
      Prince Edward Island
    </div>
    <div id='provQC'>
      Quebec
    </div>
    <div id='provSK'>
      Saskatchewan
    </div>
    <form id='censusinfo' name='censusinfo'>
<?php
    foreach($censuses as $census)
    {			// loop through all censuses
?>
    <input id='Provinces<?php print $census->get('censusid'); ?>'
		name='Provinces<?php print $census->get('censusid'); ?>'
		value='<?php print $census->get('provinces'); ?>'
		type='hidden'>
<?php
    }			// loop through all censuses
?>
    </form>
  </div> <!-- end of templates -->
</body>
</html>
