<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CountyMarriageReportEdit.php										*
 *																		*
 *  Display form for editting information about an individual marriage	*
 *  report submitted by a minister of religion to report marriages		*
 *  performed during a year prior to confederation.						*
 *																		*
 *  Parameters (passed by method=get):									*
 *		Domain		2 letter country code + 2 letter province code		*
 *		Volume		volume number										*
 *		ReportNo	report number										*
 *																		*
 *  History:															*
 *		2017/03/11		created											*
 *		2017/07/18		use Canada West instead of Ontario				*
 *		2018/02/03		change breadcrumbs to new standard				*
 *		2018/03/25		script was only expecting Domain param			*
 *						but links passed RegDomain						*
 *						do not display .0								*
 *		2018/11/20      change xxxxHelp.html to xxxxHelpen.html         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/CountyMarriageReport.inc";
require_once __NAMESPACE__ . "/Country.inc";
require_once __NAMESPACE__ . "/Domain.inc";
require_once __NAMESPACE__ . "/common.inc";

// defaults
$domain				= 'CACW';
$prov				= 'CW';
$province			= 'Canada West';
$cc					= 'CA';
$countryName		= 'Canada';
$by					= 'County';
$volume				= null;
$reportNo			= null;
$report				= null;		// instance of CountyMarriageReport
$offset				= null;
$limit				= null;

// process update
if (count($_POST) > 0)
{			// update
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $field => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($field))
	    {		// act on specific fields
			case 'domain':
			case 'regdomain':
			{	// Domain
			    $domain		= $value;
			    break;
			}	// Domain

			case 'volume':
			{	// volume number in archives
			    $volume		= $value;
			    break;
			}   // volume number in archives

			case 'showvolume':
			{	// include volume number in report
			    $newvolume		= $value;
			    break;
			}	// include volume number in report

			case 'reportno':
			{   // report number within volume
			    $reportNo	= $value;
			    if ($reportNo == floor($reportNo))
			        $reportNo	= intval($reportNo);
			    $getParms	= array('domain'	=> $domain,
		            				'volume'	=> $volume,
				            		'reportno'	=> $reportNo);
			    $report	= new CountyMarriageReport($getParms);
			    //$report->set('volume',$newvolume);
			    break;
			}   // report number within volume

			case 'page':
			{   // page number within report
			    $report->set('page',$value);
			    break;
			}   // page number within report

			case 'givennames':
			{   // given name of officiant
			    $report->set('givennames',$value);
			    break;
			}   // given name of officiant

			case 'surname':
			{   // surname of officiant
			    $report->set('surname',$value);
			    break;
			}   // surname of officiant

			case 'faith':
			{   // affiliation of officiant
			    $report->set('faith',$value);
			    break;
			}   // affiliation of officiant

			case 'residence':
			{   // residence of officiant
			    $report->set('residence',$value);
			    break;
			}   // residence of officiant

			case 'image':
			{   // URL of image of original document
			    $report->set('image',$value);
			    break;
			}   // URL of image of original document

			case 'idir':
			{   // link to family tree of officiant
			    $report->set('idir',$value);
			    break;
			}   // link to family tree of officiant

			case 'remarks':
			{
			    $report->set('remarks',$value);
			    break;
			}

	    }		// act on specific fields
	}
    if ($debug)
        $warn   .= $parmsText . "</table>\n";

	$report->save(false);
}			// update
else
{			// validate parameters
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{		// loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		// act on specific parameters
			case 'domain':
			case 'regdomain':
			{	// Domain
			    $domain		= $value;
			    break;
			}	// Domain
    
			case 'volume':
			{	// volume
			    $volume		= $value;
			    break;
			}
    
			case 'reportno':
			{
			    $reportNo		= $value;
			    if ($reportNo == floor($reportNo))
				    $reportNo	= intval($reportNo);
			    break;
			}
    
	    }		// act on specific parameters
	}		// loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

	// interpret domain
	$cc		= substr($domain, 0, 2);
	$prov		= substr($domain, 2, 2);
	$domainObj	= new Domain(array('domain'	=> $domain,
				       'language'	=> 'en'));
	if ($domainObj->isExisting())
	{
	    $province	= $domainObj->get('name');
	    if ($prov == 'UC')
		$by	= 'District';
	}
	else
	{
	    $msg	.= "Domain='$value' unsupported. ";
	    $province	= 'Unknown';
	}
	$countryObj	= new Country(array('code' => $cc));
	$countryName	= $countryObj->getName();

	// get data for report
	$getParms	= array('domain'	=> $domain,
				'volume'	=> $volume,
				'reportno'	=> $reportNo);
	$report		= new CountyMarriageReport($getParms);
}			// validate parameters

$title	= "$countryName: $province: $by Marriage Report Update";
htmlHeader($title,
	       array(	'/jscripts/CommonForm.js',
					'/jscripts/js20/http.js',
					'/jscripts/util.js',
					'/tinymce/jscripts/tiny_mce/tiny_mce.js',
					'CountyReportDetails.js'));
?>
  <body>
<?php
pageTop(array(	'/genealogy.php'	        => 'Genealogy',
				'/genCountry.php?cc=CA'	    => 'Canada',
				'/Canada/genProvince.php?Domain=CAON'
											=> 'Ontario',
				"/Ontario/CountyMarriageEditQuery.php"
											=> $by . ' Marriage Query',
				"/Ontario/CountyMarriageVolumeSummary.php?Domain=$domain"
											=> 'Volume Summary',
				"CountyMarriageReportEdit.php?Domain=$domain&Volume=$volume"
											=> "Volume"));
?>
    <div class='body'>
      <h1>
        <span class='right'>
    		<a href='CountyReportDetailsHelpen.html' target='help'>? Help</a>
        </span>
		<?php print $title; ?>
        <div style='clear: both;'></div>
      </h1>
<?php
showTrace();

if (strlen($msg) == 0)
{
	// notify the invoker if they are not authorized
	if (!canUser('edit'))
	{
?>
  <p class='warning'>
		You are not authorized.
		<a href='/Signon.php' target='_blank'>
		<span class='button'>Sign on</span></a>
		to update the database.
  </p>
<?php
        $readonly		= "readonly='readonly'";
        $disabled		= "disabled='disabled'";
        $codeclass		= 'ina code';
        $textclass		= 'ina left';
        $textclassnc	= 'ina leftnc';
        $numclass		= 'ina right';
    }		// not authorized to update database
    else
    {		// authorized to update database
        $readonly		= '';
        $disabled		= '';
        $codeclass		= 'white code';
        $textclass		= 'white left';
        $textclassnc	= 'white leftnc';
        $numclass		= 'white rightnc';
    }		// authorized to update database

?>
      <!--- Put out a line with links to previous and next section of table -->
      <div class='center' id="topBrowse">
<?php
if ($reportNo > 1)
    {
?>
        <span class='left' id="topPrev">
          <a href='CountyReportDetails.php?Domain=<?php print $domain; ?>&Volume=<?php print $volume; ?>&ReportNo=<?php print $reportNo - 1; ?>'>&lt;---&nbsp;Report&nbsp;<?php print $reportNo - 1; ?></a>
        </span>
<?php
    }
?>
        <span class='right' id="topNext">
          <a href='CountyReportDetails.php?Domain=<?php print $domain; ?>&Volume=<?php print $volume; ?>&ReportNo=<?php print $reportNo + 1; ?>'>Report&nbsp;<?php print $reportNo + 1; ?>&nbsp;---&gt;</a>
        </span>
Volume <?php print $volume; ?> Report <?php print $reportNo; ?>
      </div>
<?php
?>
    <!--- Put out the response as a table -->
    <form name='reportForm'
		action='CountyReportDetails.php' 
		method='post' 
		autocomplete='off' 
		enctype='multipart/form-data'>
<?php
if ($debug)
{
?>
      <input type='hidden' name='Debug' value='Y'>
<?php
}		// debug enabled

// get contents of record
$domain	    = $report->get('domain');
$volume	    = $report->get('volume');
$reportNo	= $report->get('reportno');
if ($reportNo == floor($reportNo))
	$reportNo	= intval($reportNo);
$page	    = $report->get('page'); 
$image	    = trim($report->get('image'));
$givennames	= $report->get('givennames');
$surname	= $report->get('surname');
$faith	    = $report->get('faith');
$residence	= $report->get('residence');
$image	    = $report->get('image');
$idir	    = $report->get('idir');
$remarks	= $report->get('remarks');
if (canUser('edit'))
{
?>
<button type="submit" id="Submit">Update Database</button>
&nbsp;
<?php
}
?>
<div class="row" id="DomainRow">
  <div class="column1">
							<label class="labelSmall" for "Domain">
							    Domain:
							</label>
							<input name="ShowDomain" id="ShowDomain"
								type="text" size="4" maxlength="4" 
								value="<?php print $domain; ?>" 
								class="<?php print $textclass; ?>" 
								readonly="readonly" disabled="disabled"/>
							<input name="Domain" id="Domain" type="hidden"
								value="<?php print $domain; ?>"> 
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="VolumeRow">
  <div class="column1">
							<label class="labelSmall" for "Volume">
							    Volume:
							</label>
							<input name="ShowVolume" id="ShowVolume"
								type="text" size="4" maxlength="8" 
								value="<?php print $volume; ?>" 
								class="<?php print $numclass; ?>"/>
							<input name="Volume" id="Volume" type="hidden"
								value="<?php print $volume; ?>"> 
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="ReportNoRow">
  <div class="column1">
							<label class="labelSmall" for "ReportNo">
							    ReportNo:
							</label>
							<input name="ReportNo" id="ReportNo" type="text"
								type="text" size="4" maxlength="8" 
								value="<?php print $reportNo; ?>" 
								class="<?php print $numclass; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="PageRow">
  <div class="column1">
							<label class="labelSmall" for "Page">
							    Page:
							</label>
							<input name="Page" id="Page" type="text"
								type="text" size="4" maxlength="8" 
								value="<?php print $page; ?>" 
								class="<?php print $numclass; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="GivenNamesRow">
  <div class="column1">
							<label class="labelSmall" for "GivenNames">
							    GivenNames:
							</label>
							<input name="GivenNames" id="GivenNames" type="text"
								type="text" size="20" maxlength="40" 
								value="<?php print $givennames; ?>" 
								class="<?php print $textclass; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="SurnameRow">
  <div class="column1">
							<label class="labelSmall" for "Surname">
							    Surname:
							</label>
							<input name="Surname" id="Surname" type="text"
								type="text" size="20" maxlength="40" 
								value="<?php print $surname; ?>" 
								class="<?php print $textclass; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="FaithRow">
  <div class="column1">
							<label class="labelSmall" for "Faith">
							    Faith:
							</label>
							<input name="Faith" id="Faith" type="text"
								type="text" size="20" maxlength="40" 
								value="<?php print $faith; ?>" 
								class="<?php print $textclass; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="ResidenceRow">
  <div class="column1">
							<label class="labelSmall" for "Residence">
							    Residence:
							</label>
							<input name="Residence" id="Residence" type="text"
								type="text" size="32" maxlength="128" 
								value="<?php print $residence; ?>" 
								class="<?php print $textclass; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="ImageRow">
  <div class="column1">
							<label class="labelSmall" for "Image">
							    Image URL:
							</label>
							<input name="Image" id="Image" type="text"
								type="text" size="128" maxlength="255" 
								value="<?php print $image; ?>" 
								class="<?php print $textclassnc; ?>" 
								<?php print $readonly; ?>/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="IDIRRow">
  <div class="column1">
							<label class="labelSmall" for "IDIR">
							    IDIR:
							</label>
							<input name="IDIR" id="IDIR" type="text"
								type="text" size="20" maxlength="40" 
								value="<?php print $idir; ?>" 
								class="<?php print $numclass; ?>" 
								readonly="readonly" disabled="disabled"/>
  </div>
  <div style="clear: both;"></div>
</div>
<div class="row" id="RemarksRow">
  <div class="column1">
							<label class="labelSmall" for "Remarks">
							    Remarks:
							</label>
							<textarea name="Remarks" id="Remarks" 
								class="<?php print $textclass; ?>"
								cols="64" rows="4"
								<?php print $readonly; ?>/><?php print $remarks; ?></textarea>
  </div>
  <div style="clear: both;"></div>
</div>
</form>
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
  </div>
<?php
pageBot();
?>
<!-- The remainder of the page consists of context specific help text.
-->
</div>
<div class='balloon' id='HelpName'>
<p>The name of the county.
</p>
</div>
<div class='balloon' id='HelpStartYear'>
<p>The year the county came into existence as an administrative unit.
For most counties this is 1852.
</p>
</div>
<div class='balloon' id='HelpEndYear'>
<p>The year the county ceased to exist.  For most counties this is 9999
indicating the county is still in existence, but, for example Carleton
County was merged into the City of Ottawa in the 1990s.
</p>
</div>
<div class='balloon' id='HelpDelete'>
<p>This button is used to delete a county record from the list.
</p>
</div>
<div class='balloon' id='HelpEditTownships'>
<p>This button is used to display a dialog for editing the lower level
administrative units of the county, that is the townships and towns. In most
cases some services, such as land and vital statistics registrations, are 
handled by the county for a city, so the city appears in this list as well.
</p>
</div>
<div class='balloon' id='HelpAdd'>
Click on this button to add another county into the list.
</div>
<div class='balloon' id='HelpSubmit'>
Click on this button to update the database to include the changes you
have made to the counties list for the current province.
</div>
<div class='hidden' id='templates'>
</div>
</body>
</html>
