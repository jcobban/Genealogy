<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetSubDists.php
 *
 *  test the getSubDists.php script
 *
 *  Copyright 2015 James A. Cobban
 *
 *  History:
 ************************************************************************/
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

    $title	= "Test RecordSet('SubDistricts')";
    $subject	= rawurlencode($title);
    $subdists	= null;
    if (count($_GET) > 0)
    {
	$getParms	= array();
	foreach($_GET as $field => $value)
	{
	    switch(strtolower($field))
	    {
		case 'census':
		case 'district':
		{
		    $getParms[$field]	= $value;
		    break;
		}
	    }
	}

	$subdists	= new RecordSet('SubDistricts', $getParms);
    }

    htmlHeader($title);
?>
<body>
<?php
    pageTop(array("/genealogy.php"	=> "Genealogy",
		"/genCanada.html"	=> "Canada",
		"/genCensuses.php"	=> "Censuses"));
?>
<div class='body'>
    <h1>
	<?php print $title; ?> 
    </h1>
<?php
    showTrace();
    if ($subdists && $subdists->count() > 0)
    {
?>
<p>
  <table class='details'>
    <thead>
      <tr>
	<th class='colhead'>index</th>
	<th class='colhead'>census</th>
	<th class='colhead'>distid</th>
	<th class='colhead'>id</th>
	<th class='colhead'>div</th>
	<th class='colhead'>sched</th>
	<th class='colhead'>name</th>
	<th class='colhead'>pages</th>
	<th class='colhead'>page1</th>
	<th class='colhead'>population</th>
	<th class='colhead'>lacreel</th>
	<th class='colhead'>ldsreel</th>
	<th class='colhead'>imagebase</th>
	<th class='colhead'>relframe</th>
	<th class='colhead'>framect</th>
	<th class='colhead'>bypage</th>
	<th class='colhead'>remarks</th>
     </tr>
    </thead>
    <tbody>
<?php
	foreach($subdists as $index => $subdist)
	{
?>
      <tr>
	<th><?php print $index; ?></th>
	<td class='odd left'><?php print $subdist->get('sd_census'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_distid'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_id'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_div'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_sched'); ?></td>
	<td class='odd left'><?php print $subdist->get('sd_name'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_pages'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_page1'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_population'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_lacreel'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_ldsreel'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_imagebase'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_relframe'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_framect'); ?></td>
	<td class='odd right'><?php print $subdist->get('sd_bypage'); ?></td>
	<td class='odd left'><?php print $subdist->get('sd_remarks'); ?></td>
      </tr>
<?php
	}
?>
    </tbody>
  </table>
</p>
<?php
    }
?>
<form name='evtForm' action='testMethodGetSubdists.php' method='get'>
    <p>
     <label for='Census'>Census:
	<select name='Census' id='Census'>
	    <option value='CW1851'>1851</option>
	    <option value='CW1861'>1861</option>
	    <option value='CA1871'>1871</option>
	    <option value='CA1881'>1881</option>
	    <option value='CA1891'>1891</option>
	    <option value='CA1901'>1901</option>
	    <option value='CA1906'>1906</option>
	    <option value='CA1911'>1911</option>
	    <option value='CA1916'>1916</option>
	    <option value='CA1921'>1921</option>
	</select>
      </label>
    </p>
    <p>
      <label for='District'>District:
	<input type='text' name='District' value='1'>
      </label>
    </p>
<p>
  <button type='submit'>Execute</button>
</p>
</form>
</div>
<?php
    pageBot();
?>
<div class='balloon' id='HelpSurname'>
<p>Edit the surname of the individual.  Note that changing the surname causes
a number of other fields and records to be updated.  In particular the Soundex
value, stored in field 'SoundsLike' in the individual records is updated.
Also if the surname does not already appear in the database, a record is
added into the table 'tblNR'.
</p>
</div>
</body>
</html>
