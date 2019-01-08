<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageReportEdit.php										*
 *																		*
 *  Display form for editting information about marriage reports		*
 *  submitted by ministers of religion to report marriages they			*
 *  performed during a year prior to confederation.						*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain	2 letter country code + 2 letter state/province code	*
 *		Volume	volume number											*
 *																		*
 *  History:															*
 *		2016/01/29		created											*
 *		2016/05/20		use class Domain to validate domain code		*
 *		2017/01/12		add links to Ontario Archives					*
 *						add button to display image						*
 *		2017/02/07		use class Country								*
 *		2017/07/18		use Canada West for county registrations		*
 *						update did not refresh stats					*
 *		2017/09/12		use get( and set(								*
 *		2018/02/25		use RecordSet									*
 *		2018/03/10		use CountyMarriageReportSet						*
 *		2018/11/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/CountyMarriageReportSet.inc";
require_once __NAMESPACE__ . "/common.inc";

function compareReports($r1, $r2)
{
    if ($r1->get('m_regdomain') == $r2->get('m_regdomain') &&
		$r1->get('m_volume') == $r2->get('m_volume') &&
		$r1->get('m_reportno') == $r2->get('m_reportno'))
    {
		return 0;
    }
    if ($r1->get('m_regdomain') < $r2->get('m_regdomain'))
    {
		return -1;
    } else
    if ($r1->get('m_regdomain') > $r2->get('m_regdomain'))
    {
		return 1;
    }
    if ($r1->get('m_volume') < $r2->get('m_volume'))
    {
		return -1;
    } else
    if ($r1->get('m_volume') > $r2->get('m_volume'))
    {
		return 1;
    }
    if ($r1->get('m_reportno') < $r2->get('m_reportno'))
    {
		return -1;
    }

    return 1;
}

// defaults
$domainCode			= 'CACW';
$prov				= 'CW';
$province			= 'Canada West (Ontario)';
$cc					= 'CA';
$countryName		= 'Canada';
$by					= 'County';
$volume				= null;
$reportNo			= null;
$report				= null;		// instance of CountyMarriageReport
$offset				= null;
$limit		        = null;

// validate parameters
if (count($_POST) > 0)
{			// perform update
    $parmsText  = "<p class=\"label\">\$_POST</p>\n" .
                  "<table class=\"summary\">\n" .
                  "<tr><th class=\"colhead\">key</th>" .
                      "<th class=\"colhead\">value</th></tr>\n";
	$reports	= array();

	foreach($_POST as $key => $value)
	{
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                        "<td class=\"white left\">$value</td></tr>\n"; 
	    if (preg_match("/^([a-zA-Z_]+)(\d*)$/", $key, $matches))
	    {
			$column	= $matches[1];
			$row	= $matches[2];
	    }
	    else
	    {
			$column	= $key;
			$row	= '';
	    }

	    switch(strtolower($column))
	    {		// act on specific parameters
			case 'domain':
            {	// Domain
                if (strlen($value) >= 4)
			        $domainCode	    = strtoupper($value);
			    break;
			}	// Domain

			case 'volume':
			{	// volume
			    if ($report &&
					$report->get('givennames') != 'New Minister')
			    {
					$report->save();
					$reports[]	= $report;
			    }
			    $report	= null;
			    $volume	= $value;
			    break;
			}

			case 'reportno':
			{
			    $reportNo	= $value;
			    $getParms	= array('domain'	=> $domainCode,
							        'volume'	=> $volume,
							        'reportno'	=> $reportNo);
			    $report	    = new CountyMarriageReport($getParms);
			    break;
			}

			case 'page':
			case 'givennames':
			case 'surname':
			case 'faith':
			case 'residence':
			case 'image':
			case 'remarks':
			{
			    $report->set($column, $value);
			    break;
			}

            case 'lang':
            {
                if (strlen($value) == 2)
                    $lang       = strtolower($value);
			    break;
            }
	    }		// act on specific parameters
	}
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

	// update the last entry
	if ($report && $report->get('givennames') != 'New Minister')
	{
	    $report->save();
	    $reports[]	= $report;
	}

}			// perform update
else
{			// initial report
    $parmsText  = "<p class=\"label\">\$_GET</p>\n" .
                  "<table class=\"summary\">\n" .
                  "<tr><th class=\"colhead\">key</th>" .
                      "<th class=\"colhead\">value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
        $parmsText  .= "<tr><th class=\"detlabel\">$key</th>" .
                        "<td class=\"white left\">$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {
			case 'prov':
			{
			    $domainCode		= 'CA' . strtoupper($value);
			    break;
			}		// state/province code

			case 'domain':
			case 'regdomain':
            {
                if (strlen($value) >= 4)
			        $domainCode		= strtoupper($value);
			    break;
			}		// state/province code

			case 'volume':
			{
			    if (strlen($value) > 0)
					$volume		= $value;
			    break;
			}

			case 'offset':
			{
			    $offset		= $value;
			    break;
			}

			case 'count':
			{
			    $limit		= $value;
			    break;
            }

            case 'lang':
            {
                if (strlen($value) == 2)
                    $lang       = strtolower($value);
			    break;
            }

			case 'debug':
			{
			    break;
			}		// debug handled by common code

			default:
			{
			    if (strlen($value) > 0)
					$warn	.= "<p>Unexpected parameter $key=\"$value\".</p>";
			    break;
			}
	    }		// check supported parameters
	}		// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			// initial report

$domain	            = new Domain(array('domain'	    => $domainCode,
	                                   'language'	=> 'en'));
$province	        = $domain->get('name');
if ($domain->isExisting())
{
    $cc		        = substr($domainCode, 0, 2);
    $prov	        = substr($domainCode, 2, 2);
	if ($prov == 'UC')
	    $by		    = 'District';
    $country		= new Country(array('code' => $cc));
    $countryName	= $country->getName();
}
else
{                   // domain not supported
	$msg		    .= "Domain=\"$domainCode\" unsupported. ";
}                   // domain not supported
if (strlen($msg) == 0)
{		// no errors detected
	// execute the query to get the contents of the page
	$getParms	= array('regdomain'	=> $domainCode);
	if ($volume)
	    $getParms['volume']		= $volume;
	if ($offset)
	    $getParms['offset']		= $offset;
	if ($limit)
	    $getParms['limit']		= $limit;
	$reports	= new CountyMarriageReportSet($getParms);
}		// no errors detected

$title	= "$countryName: $province: Marriage Report Update";
htmlHeader($title,
	       array(	'/jscripts/CommonForm.js',
					'/jscripts/js20/http.js',
					'/jscripts/util.js',
					'CountyMarriageReportEdit.js'));
$breadCrumbs	= array(
			'/genealogy.php'	=> 'Genealogy',
			"/genCountry.php?CC=$cc"	=> $countryName,
			"/Canada/genProvince.php?domain=$domainCode"	=> $province,
			"/Ontario/CountyMarriageEditQuery.php?Domain=$domainCode"	=> $by . ' Marriage Query',
			"CountyMarriageVolumeSummary.php?Domain=$domainCode"
							=> 'Volume Summary');
if(is_null($volume))
	$breadCrumbs['CountyMarriageVolumeSummary.php'] =
					'VolumeSummary';
?>
  <body>
<?php
pageTop($breadCrumbs);
?>
    <div class="body">
      <h1>
        <span class="right">
          <a href="CountyMarriageReportEditHelpen.html" target="help">? Help</a>
        </span>
        <?php print $title; ?>
        <div style="clear: both;"></div>
      </h1>
<?php
showTrace();

if (strlen($msg) == 0)
{
	// notify the invoker if they are not authorized
	if (!canUser('edit'))
	{
?>
      <p class="warning">
    	You are not authorized.
    	<a href="/Signon.php" target="_blank">
    	<span class="button">Sign on</span></a>
    	to update the database.
      </p>
<?php
	    $readonly	= "readonly=\"readonly\"";
	    $disabled	= "disabled=\"disabled\"";
	    $codeclass	= 'black white code';
	    $textclass	= 'black white left';
	    $numclass	= 'black white right';
	}		// not authorized to update database
	else
	{		// authorized to update database
	    $readonly	= '';
	    $disabled	= '';
	    $codeclass	= 'black white code';
	    $textclass	= 'black white left';
	    $numclass	= 'black white rightnc';
	}		// authorized to update database

	if (isset($volume) && $reportNo === null)
	{		// display of whole volume
?>
      <!--- Put out a line with links to previous and next section of table -->
      <div class="center">
<?php
	    if ($volume > 1)
	    {
?>
<       span class="left">
          <a href="CountyMarriageReportEdit.php?RegDomain=<?php print $domainCode; ?>&Volume=<?php print $volume - 1; ?>">&lt;---&nbsp;Volume&nbsp;<?php print $volume - 1; ?></a>
        </span>
<?php
	    }
?>
        <span class="right">
          <a href="CountyMarriageReportEdit.php?RegDomain=<?php print $domainCode; ?>&Volume=<?php print $volume + 1; ?>">Volume&nbsp;<?php print $volume + 1; ?>&nbsp;---&gt;</a>
        </span>
        All of Volume <?php print $volume; ?>
      </div> <!-- class="center" -->
<?php
	}		// display of whole volume
?>
      <!--- Put out the response as a table -->
      <form name="countyForm"
        	action="CountyMarriageReportEdit.php" 
        	method="post" 
        	autocomplete="off" 
        	enctype="multipart/form-data">
        <input type="hidden" name="Domain" value="<?php print $domainCode; ?>">
<?php
	if ($debug)
	{
?>
        <input type="hidden" name="Debug" value="Y">
<?php
	}		// debug enabled
?>
        <table class="form" id="dataTbl">
<!--- Put out the column headers -->
          <thead>
			<tr id="hdrRow">
			  <th class="colhead">
	  Volume
			  </th>
			  <th class="colhead">
	  Report
			  </th>
			  <th class="colhead">
	  Page
			  </th>
			  <th class="colhead">
	  Given Names
			  </th>
			  <th class="colhead">
	  Surname
			  </th>
			  <th class="colhead">
	  Marriages
			  </th>
			  <th class="colhead">
	  % Linked
			  </th>
			  <th class="colhead">
	  Details
			  </th>
			  <th class="colhead">
	  Delete
			  </th>
			</tr>
          </thead>
          <tbody>
<?php
	// display the results
	$reportNo	    = 0;
	$page		    = 1;
	$row		    = 0;
	$image		    = '';
    $nextReportNo	= 1;
	foreach($reports as $report)
	{
	    $row++;
	    if (strlen($row) == 1)
			$row	        = '0' . $row;
	    $volume	= $report->get('volume');
	    $reportNo	        = $report->get('reportno');
        if ($reportNo == floor($reportNo))
        {
            $reportNo	    = intval($reportNo);
            $nextReportNo	= $reportNo + 1;
        }
        else
        {
            $nextReportNo	= intval($reportNo) + 1;
            $reportNo	    = intval($reportNo) . '½';
        }
	    $page	            = $report->get('page'); 
	    $image	            = trim($report->get('image'));
	    $transcribed        = $report->get('transcribed'); 
	    $linked	            = $report->get('linked'); 
	    if ($transcribed > 0)
	    {
			$pct	    = number_format(100 * $linked / $transcribed, 2) . '%';
			$pctClass	    = pctClass($pct);
	    }
	    else
	    {
			$pct		    = '';
			$pctClass	    = pctClass(0);
			$transcribed	= 0;
	    }
	    $transcribed	    = floor($transcribed / 2);
	    if ($image == '')
	    {
			$imageStatus	= 'disabled="disabled"';
	    }
	    else
	    {
			$imageStatus	= '';
	    }
			
?>
			<tr id="Row<?php print $row; ?>">
			  <td class="right">
        	    <input type="text" name="Volume<?php print $row; ?>"
					value="<?php print $volume; ?>"
					class="<?php print $numclass; ?>"
					size="4" maxlength="4" readonly="readonly">
			  </td>
			  <td class="left">
        	    <input type="text" name="ReportNo<?php print $row; ?>" 
					value="<?php print $reportNo; ?>" 
					class="<?php print $numclass; ?>"
                    <?php print $readonly; ?>
					size="4" maxlength="6">
			  </td>
			  <td class="left">
        	    <input type="text" name="Page<?php print $row; ?>" 
					value="<?php print $page; ?>" 
					class="<?php print $numclass; ?>"
                    <?php print $readonly; ?>
					size="4" maxlength="4">
			  </td>
			  <td class="left">
        	    <input type="text" name="GivenNames<?php print $row; ?>" 
					value="<?php print $report->get("givennames"); ?>" 
					class="<?php print $textclass; ?>"
                    <?php print $readonly; ?>
					size="16" maxlength="64">
			  </td>
			  <td class="left">
        	    <input type="text" name="Surname<?php print $row; ?>" 
					value="<?php print $report->get("surname"); ?>" 
					class="<?php print $textclass; ?>"
                    <?php print $readonly; ?>
					size="16" maxlength="64">
			  </td>
			  <td class="left">
        	    <input type="text" name="Transcribed<?php print $row; ?>" 
					value="<?php print $transcribed; ?>" 
					class="<?php print $numclass; ?>"
					readonly="readonly" disabled="disabled"
					size="8">
			  </td>
			  <td class="<?php print $pctClass; ?>">
                <?php print $pct; ?> 
			  </td>
			  <td class="center" style="white-space: nowrap;">
	            <button type="button" id="Details<?php print $row; ?>">
	        		Details
	            </button> 
&nbsp;
        	    <button type="button" id="EditMarriages<?php print $row; ?>">
        			Marriages
        	    </button> 
&nbsp;
<!--
	  <button type="button" id="DisplayImage<?php print $row; ?>" ?php print $imageStatus; ?>>
			Show Image
	  </button> -->
			  </td>
			  <td class="center">
	            <button type="button" id="Delete<?php print $row; ?>"
                        <?php print $disabled; ?>>
    			  Delete
	            </button> 
			  </td>
			</tr>
<?php
	}		// process all rows
	$row++;
	if (strlen($row) == 1)
	    $row	= '0' . $row;
?>
			<tr id="Row<?php print $row; ?>">
			  <td class="right">
			      <input type="text" name="Volume<?php print $row; ?>"
							value="<?php print $volume; ?>"
							class="<?php print $numclass; ?>"
							size="4" maxlength="4" readonly="readonly">
			  </td>
			  <td class="left">
			      <input type="text" name="ReportNo<?php print $row; ?>" 
							value="<?php print $nextReportNo; ?>" 
							class="<?php print $numclass; ?>"
                            <?php print $readonly; ?>
							size="4" maxlength="6">
			  </td>
			  <td class="left">
			      <input type="text" name="Page<?php print $row; ?>" 
							value="" 
							class="<?php print $numclass; ?>"
                            <?php print $readonly; ?>
							size="4" maxlength="4">
			  </td>
			  <td class="left">
			      <input type="text" name="GivenNames<?php print $row; ?>" 
							value="New Minister" 
							class="<?php print $textclass; ?>"
                            <?php print $readonly; ?>
							size="16" maxlength="64">
			  </td>
			  <td class="left">
			      <input type="text" name="Surname<?php print $row; ?>" 
							value="" 
							class="<?php print $textclass; ?>"
                            <?php print $readonly; ?>
							size="16" maxlength="64">
			  </td>
			  <td class="left">
			  </td>
			  <td class="left">
			  </td>
			  <td class="left">
			      <button type="button" id="Details<?php print $row; ?>">
					Details
			      </button> 
			  </td>
			  <td class="left">
			  </td>
			</tr>
		  </tbody>
		</table>
<?php
	if (canUser("edit"))
	{
?>
		<button type="submit" id="Submit">Update Database</button>
		&nbsp;
<?php
	}
?>
	  </form>
	  <h2>Also see:
	  </h2>
	  <p>
		<a href="http://www.archives.gov.on.ca/en/microfilm/v_mdistrict_t.aspx"
			target="_blank">District Marriage Registers - 1780-1858</a>
	  </p>
	  <p>
		<a href="http://www.archives.gov.on.ca/en/microfilm/v_mcounty_t.aspx"
			target="_blank">County Marriage Registers - 1858-1869</a>
	  </p>
<?php
}		// there are no messages to display, do search
else
{		// print error messages
?>
      <p class="message">
        <?php print($msg); ?>
      </p>
<?php
}		// print error messages
?>
    </div> <!-- class="body"-->
<?php
pageBot();
?>
    <!-- The remainder of the page consists of context specific help text.
    -->
    <div class="balloon" id="HelpCode">
      <p>This field contains a 3-character abbreviation for the county which
      is used to refer to this record.
      Except when adding a new county this is a read-only field.
      </p>
    </div>
    <div class="balloon" id="HelpName">
    <p>The name of the county.
    </p>
    </div>
    <div class="balloon" id="HelpStartYear">
      <p>The year the county came into existence as an administrative unit.
      For most counties in Ontario this is 1852 as a consequence of the
      implementation of the Durham Report.
      </p>
    </div>
    <div class="balloon" id="HelpEndYear">
      <p>The year the county ceased to exist.  For most counties this is 9999
      indicating the county is still in existence, but, for example Carleton
      County was merged into the City of Ottawa in the 1990s.
      </p>
    </div>
    <div class="balloon" id="HelpDelete">
      <p>This button is used to delete a county record from the list.
      </p>
    </div>
    <div class="balloon" id="HelpEditTownships">
      <p>This button is used to display a dialog for editing the lower level
      administrative units of the county, that is the townships and towns.
      In most cases some services, such as land and vital statistics
      registrations, are 
      handled by the county for a city, so the city appears in this list
      as well.
      </p>
    </div>
    <div class="balloon" id="HelpAdd">
      Click on this button to add another county into the list.
    </div>
    <div class="balloon" id="HelpSubmit">
      Click on this button to update the database to include the changes you
      have made to the counties list for the current province.
    </div>
    <div class="balloon" id="HelprightTop">
      Click on this button to signon to access extended features of the web-site
      or to manage your account with the web-site.
    </div>
    <div class="hidden" id="templates">
    </div>
  </body>
</html>
