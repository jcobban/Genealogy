<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ReqUpdatePages.html							*
 *									*
 *  Update a page of the Pages database.				*
 *									*
 *  History (of ReqUpdatePages.html):					*
 *	2010/10/01	Reformat to new page layout.			*
 *	2011/01/20	Make <select size='9'>				*
 *	2011/06/27	add support for 1916				*
 *	2011/11/04	add support for mouseover help			*
 *	2013/07/30	add Facebook like				*
 *			correct context specific Help			*
 *	2013/08/17	add support for 1921				*
 *	2014/06/02	do not use table for layouts			*
 *	2015/05/25	help pages were not displayed in new tab/window	*
 *			misspelled name of PageFormHelp			*
 *									*
 *  History (of ReqUpdatePages.php):					*
 *	2015/06/02	renamed and made conditional on user's auth	*
 *			display warning messages			*
 *	2015/07/02	access PHP includes using include_path		*
 *	2016/03/16	support dynamically change Census passed to	*
 *			PageForm.php on submit				*
 *	2017/02/07	use class Country				*
 *			validate census identifier			*
 *	2017/09/12	use get( and set(				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Census.inc';
    require_once __NAMESPACE__ . '/Country.inc';
    require_once __NAMESPACE__ . '/common.inc';

    $censusid		= '';
    $cc			= 'CA';
    $countryName	= 'Canada';
    $censusYear		= '';
    $provinceCode	= '';
    $distid		= '';
    $subdistid		= '';
    $division		= '';
    $selected	= array(0	=> ' selected="selected"',
			'QC'	=> array(1831	=> ''),
			'CA'	=> array(1851	=> '',
					 1861	=> '',
					 1871	=> '',
					 1881	=> '',
					 1891	=> '',
					 1901	=> '',
					 1906	=> '',
					 1911	=> '',
					 1916	=> '',
					 1921	=> '')
			);

    foreach($_GET as $key => $value)
    {
	switch(strtolower($key))
	{		// act on specific keys
	    case 'census':
	    case 'censusid':
	    {
		if (strlen($value) == 4)
		{		// old format only includes year
		    $censusYear		= intval($value);	// census year
		    $censusId		= 'CA' . $censusYear;
		    $cc			= 'CA';
		}		// old format only includes year
		else
		{		// CCYYYY
		    $censusId		= $value;
		    $censusYear		= intval(substr($censusId, 2));
		    $cc			= substr($censusId, 0, 2);
		}		// CCYYYY

		try
		{		// valid census id
		    $censusRec	= new Census(array('censusid'	=> $censusId));
		    if (is_string($censusRec->get('partof')))
		    {
			$cc		= $censusRec->get('partof');
			$provinceCode	= substr($value,0,2);
		    }
		    $selected[0]	= '';
		    $selected[$cc][$censusYear]	= ' selected="selected"';
		}		// valid census id
		catch(Exception $e)
		{
		    $warn	.= "<p>Census='$censusId' is not supported.</p>\n";
		}

		$countryObj		= new Country(array('code' => $cc));
		$countryName		= $countryObj->getName();
		break;
	    }

	    case 'province':
	    {
		$provinceCode	= $value;
		break;
	    }

	    case 'district':
	    {
		$district	= $value;
		break;
	    }

	    case 'subdistrict':
	    {
		$subdistrict	= $value;
		break;
	    }

	    case 'division':
	    {
		$division	= $value;
		break;
	    }

	}		// act on specific keys
    }

    // determine whether the invoker can update
    $update	= canUser('edit');
    $title	= "Census Administration: $countryName: $censusYear Census: Page Table";
    if ($update)
	$title	.= " Update";
    else
	$title	.= " Display";

    htmlHeader($title,
	       array(	'/jscripts/js20/http.js',
			'/jscripts/util.js',
			'ReqUpdatePages.js'));
?>
<body>
<?php
    pageTop(array(
	'/genealogy.php'		=> 'Genealogy', 
	'/genCanada.html'		=> 'Canada',
	'EditCensuses.php'		=> 'Censuses',
	"ReqUpdateDists.php?Census=$censusid"	=> 'Districts',
	'ReqUpdateSubDists.php'=> 'Sub-Districts'));
?>
 <div class="body">
   <h1>
      <span class="right">
	<a href="ReqUpdatePagesHelpen.html" target="_blank">Help?</a>
      </span>
	<?php print $title; ?>
      <div style="clear: both;"></div>
    </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {			// display error messages
?>
    <p class="message">
	<?php print $msg; ?>
    </p>
<?php
    }			// display error messages
    else
    {			// no errors detected
?>
    <form action="PageForm.php" name="distForm">
      <input type="hidden" name="ProvinceCode"
		value="<?php print $provinceCode; ?>">
      <table id="formTable">
        <tr id="distRow">
          <td class="label">
	      Census:
	  </td>
	  <td>
	    <select size="9" name="CensusSel" id="CensusSel">
	        <option value="" <?php print $selected[0]; ?>>
	      Choose a Census:</option>
	        <option value="QC1831" <?php print $selected['QC'][1831]; ?>>
	      1831 Quebec</option>
	        <option value="CA1851" <?php print $selected['CA'][1851]; ?>>
	      1851/52 Canada</option>
	        <option value="CA1861" <?php print $selected['CA'][1861]; ?>>
	      1861 Canada</option>
	        <option value="CA1871" <?php print $selected['CA'][1871]; ?>>
	      1871 Canada </option>
	        <option value="CA1881" <?php print $selected['CA'][1881]; ?>>
	      1881 Canada </option>
	        <option value="CA1891" <?php print $selected['CA'][1891]; ?>>
	      1891 Canada </option>
	        <option value="CA1901" <?php print $selected['CA'][1901]; ?>>
	      1901 Canada </option>
	        <option value="CA1906" <?php print $selected['CA'][1906]; ?>>
	      1906 Prairie Provs</option>
	        <option value="CA1911" <?php print $selected['CA'][1911]; ?>>
	      1911 Canada </option>
	        <option value="CA1916" <?php print $selected['CA'][1916]; ?>>
	      1916 Prairie Provs</option>
	        <option value="CA1921" <?php print $selected['CA'][1921]; ?>>
	      1921 Canada </option>
	    </select> 
	    <input type="hidden" name="Census" id="Census" value="">
        </td>
          <td class="label">
		Province:
	  </td>
	  <td>
	    <select size="9" name="Province">
	    </select> 
        </td>
        <td id="msgCell" class="label">
		District:
        </td>
        <td>
	  <select size="9" name="District">
	  </select> 
	</td>
	<td class="label">
	  <a href="PageFormHelpen.html" target="_blank">Help?</a>
	</td>
      </tr>
      <tr id="divRow">
          <td class="label">
	      SubDistrict:
	  </td>
	  <td>
	    <select size="9" name="SubDistrict">
	    </select> 
	  </td>
          <td class="label" id="divLabel">
	  </td>
          <td id="divCell">
	  </td>
      </tr>
    </table>
    <button type="submit" id="Submit">Request Form</button>
  </form>
<?php
    }			// no errors detected
?>
  </div> <!-- id="body" -->
<?php
    pageBot();
?>
    <div class="balloon" id="HelpCensus">
	The Census selection list is used to specify the year of the
	census enumeration for which you wish to update a Sub-District
	description.
    </div>
    <div class="balloon" id="HelpProvince">
	The Province selection list is an alphabetical list of the
	provinces in the specified census.  It is used if you wish to limit the 
	displayed list of districts to a specific province.
    </div>
    <div class="balloon" id="HelpDistrict">
	The District selection list is an alphabetical list of census
	districts, roughly equivalent to federal electoral ridings.  This tool
	permits editting the descriptions of the sub-districts within
	a specified census district. 
    </div>
    <div class="balloon" id="HelpSubDistrict">
	The Sub-District selection list is an alphabetical list of
	sub-districts 
	within the selected census district[s].  Each generally represents a
	city ward, town, or township. 
    </div>
    <div class="balloon" id="HelpDivision">
	Sub-Districts that were too populous to be handled by a single
	enumerator were sub-divided into enumeration divisions.
    </div>
    <div class="balloon" id="HelpSubmit">
	Click on this button to open the form for viewing and updating
	the information recorded about individual pages of a sub-district.
    </div>
  </body>
</html>
