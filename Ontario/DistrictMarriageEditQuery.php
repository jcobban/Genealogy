<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DistrictMarriageEditQuery.php										*
 *																		*
 *  Prompt the user to enter parameters for a search of the 			*
 *  District Marriage Registration database.							*
 *																		*
 *  History:															*
 *		2017/07/18		split off from CountyMarriageEditQuery.php		*
 *		2018/12/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/DomainSet.inc";
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$domainCode		= 'CAUC';	// default domain
$domainName	    = 'Upper Canada (Ontario)';
$lang		    = 'en';

$parmsText      = "<p class='label'>\$_GET</p>\n" .
                        "<table class='summary'>\n" .
                        "<tr><th class='colhead'>key</th>" .
                        "<th class='colhead'>value</th></tr>\n";
foreach($_GET as $key => $value)
{			// loop through all input parameters
    $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                         "<td class='white left'>$value</td></tr>\n"; 
    switch(strtolower($key))
    {		// process specific named parameters
    	case 'domain':
    	case 'regdomain':
        {
            if (strlen($value) >= 4)
    	        $domainCode	        = strtoupper($value);
    	    break;
    	}		// RegDomain

    	case 'regyear':
    	case 'volume':
    	case 'reportno':
    	case 'itemno':
    	case 'givennames':
    	case 'surname':
    	case 'residence':
    	case 'soundex':
    	{		// to do
    	    break;
    	}		// to do

    	case 'lang':
        {
            if (strlen($value) == 2)
                $lang       = strtolower($value);
    	    break;
    	}		// handled by common code

    	case 'debug':
    	{
    	    break;
    	}		// handled by common code

    	default:
    	{
    	    $warn	.= "Unexpected parameter $key='$value'. ";
    	    break;
    	}		// any other paramters
    }		// process specific named parameters
}			// loop through all input parameters
if ($debug)
    $warn       .= $parmsText . "</table>\n";

$domain	    = new Domain(array('domain'	    => $domainCode,
					           'language'	=> 'en'));
$domainName	= $domain->get('name');
if (!$domain->isExisting())
{               // domain code not defined
	$msg	.= "Domain '$domainCode' must be a supported two character country code followed by a two or three character state or province code. ";
}               // domain code not defined

// get a list of domains for the selection list
$getParms	= array('language'	=> 'en');
$domains	= new DomainSet($getParms);

htmlHeader("District Marriage Registration Query",
    	   array('/jscripts/CommonForm.js',
    			 '/jscripts/js20/http.js',
    			 '/jscripts/Ontario.js',
    			 '/jscripts/util.js',
    			 'CountyMarriageEditQuery.js'),
    	   false);
?>
<body>
<?php
pageTop(array(	'/genealogy.php'	=> 'Genealogy',
    			'/genCountry.php?cc=CA'	=> 'Canada',
    			"/Canada/genProvince.php?Domain=$domainCode"
    							    => $domainName,
    			"/Ontario/CountyMarriageVolumeSummary.php?Domain=$domainCode"	
    							    => 'Volume Summary'));
?>
<div class='body'>
  <h1>District Marriage Registration Query
    <span class='right'>
    	<a href='CountyMarriageEditQueryHelpen.html' target='_blank'>Help?</a>
    </span>
    <div style='clear: both;'></div>
  </h1>
<?php
showTrace();

if (strlen($msg) > 0)
{		// error messages
?>
  <p class='message' id='msgCell'><?php print $msg . "\n"; ?>
  </p>
<?php
}		// error messages
else
{
?>
  <form action='CountyMarriageReportEdit.php' method='GET'
    	name='distForm' id='distForm'>
    <input name='Offset' id='Offset' type='hidden' value='0'>
<?php
    if ($debug)
    {
?>
    <input name='Debug' id='Debug' type='hidden' value='Y'>
<?php
    }
?>
    <div id='formTable'>
      <fieldset class='other'>
    	<legend class='labelSmall'>Identification:</legend>
        <!-- Specify to query marriage registrations from Ontario, Canada -->
        <div class='row'>
    	  <div class='column1'>
    	    <label class='labelSmall' for='RegDomain'>Domain:</label>
    	    <select name='RegDomain' id='RegDomain'
    			    class='white left' size='1'>
<?php
    foreach($domains as $code => $dom)
    {
    	if ($code == $domainCode)
    	    $selected	= "selected='selected'";
    	else
    	    $selected	= '';
?>
    	    <option value='<?php print $code; ?>' <?php print $selected; ?>>
    			<?php print $dom->get('name') . "\n"; ?>
    	    </option>
<?php
    }			// loop through defined domains
?>
    	  </select>
    	</div>
    	<div class='column1'>
    	  <label class='labelSmall' for='Volume'>Volume:</label>
    	  <input name='Volume' id='Volume' type='text' style='float: left;'
    	  	class='white rightnc' size='4' maxlength='4'/>
    	</div>
    	<div style='clear:both;'></div>
      </div>
      <div class='row'>
    	<div class='column1'>
    	  <label class='labelSmall' for='ReportNo'>ReportNo:</label>
    	  <input name='ReportNo' id='ReportNo' type='text' style='float: left;'
    	  	class='white rightnc' size='4' maxlength='4'/>
    	</div>
    	<div class='column1'>
    	  <label class='labelSmall' for='ItemNo'>ItemNo:</label>
    	  <input name='ItemNo' id='ItemNo' type='text' style='float: left;'
    	  	class='white rightnc' size='4' maxlength='4'/>
    	</div>
    	<div style='clear:both;'></div>
      </div>
      </fieldset>
      <fieldset class='other'>
    	<legend class='labelSmall'>Individual:</legend>
      <div class='row'>
    	<div class='column1'>
    	  <label class='labelSmall' for='GivenNames'>Given Names:</label>
    	  <input name='GivenNames' id='GivenNames'
    			type='text' style='float: left;'
    	  	class='white left' size='16' maxlength='64'/>
    	</div>
    	<div class='column1'>
    	  <label class='labelSmall' for='Surname'>Surname:</label>
    	  <input name='Surname' id='Surname' type='text' style='float: left;'
    	  	class='white left' size='16' maxlength='64'/>
    	</div>
    	<div class='column1'>
    	  <label class='labelSmall' for='Soundex'>Soundex:</label>
    	  <input name='Soundex' id='Soundex' type='checkbox'
    			style='float: left;'
    	  	class='white left' value='Y'/>
    	  <div style='clear: both;'></div>
    	</div>
    	<div style='clear:both;'></div>
      </fieldset>
    </div> <!-- id='formTable' -->
    <p id='buttonsRow'>
      <button type='submit' id='Query'>Query</button>
      &nbsp;
      <button type='reset' id='Reset'>Clear Form</button>
      &nbsp;
      <button type='button' id='Stats'>Status</button>
    </p>
  </form>
<?php
}
?>
 </div> <!-- end of <div id='body'> -->
<?php
    pageBot();
?>
    <!--  The remainder of the web page consists of divisions containing
    context specific help.  Including this information here ensures
    that the language of the help balloons matches the language of the
    input form.
    -->
    <div class='balloon' id='HelpRegDomain'>
      This is a selection list to select the registration domain, for example
      a province or state, to be searched.
    </div>
    <div class='balloon' id='HelpVolume'>
      This is the number of a volume of records kept by the archives containing
      pre-confederation marriage registrations.
    </div>
    <div class='balloon' id='HelpReportNo'>
      This identifies a specific report within the volume of marriage
      registrations.
      Each report contains information about the marriages performed by a
      minister of religion or a marriage commissioner (Justice of the Peace)
      in a year.
    </div>
    <div class='balloon' id='HelpItemNo'>
      This specifies a specific marriage within a report by its ordinal 
      position.
    </div>
    <div class='balloon' id='HelpGivenNames'>
      This specifies to search for marriages that match a particular given name.
    </div>
    <div class='balloon' id='HelpSurname'>
      This specifies to search for surnames that match a particular surname or
      part of a surname.  This is a
      <a href="https://en.wikipedia.org/wiki/Regular_expression">
        regular expression
      </a>.
    </div>
    <div class='balloon' id='HelpSurnameSoundex'> 
    <p>The Soundex code has been used for decades to attempt to match names that
      sound similar.  For example it is used by police forces to perform a rough
      match for the names of drivers.  It is a problematic tool, as it
      is based upon the phonetics of British surnames.
      If you select this option along with a
      complete surname, not a pattern match, in the Surname field,
      then the search
      is made for surnames that "sound like" the given surname.  For example
      specifying Soundex together with "McLean" will match all of 
      the surnames in
      the pattern match example under "Surname", but many other names as well,
      such "McCallum", "McAllan", "McClain", and "McWilliams". 
    </div>
    <div class='balloon' id='HelpQuery'>
      Clicking on this button performs the query.
    </div>
    <div class='balloon' id='HelpReset'>
      Clicking on this button clears all of the input fields back to their
      default values.
    </div>
    <div class='balloon' id='HelpStats'>
      Clicking on this button displays the top level statistics page for
      marriage registrations.
    </div>
    <div class='balloon' id='HelprightTop'>
      Click on this button to signon to access extended features of the web-site
      or to manage your account with the web-site.
    </div>
    <div class='hidden' id='templates'>
      <div class='left' id='noCountyMsg'>
          Counties summary file "CountiesListXml.php?Prov=$province"
          not available from server.
      </div>
    </div>
  </body>
</html>
