<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  test class Domain							*
 *									*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/DomainSet.inc';

$domains	= new DomainSet(array());
$results	= $domains->getDistinct('language');
htmlHeader('Test Domain Class');
showTrace();
?>
<body>
<?php
pageTop(array());
showTrace();
?>
  <h3>Defined Language Codes</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Lang</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $index => $language)
{
?>
      <tr>
	<td class='left white'><?php print $index; ?></td>
	<td class='left white'><?php print $language; ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php
$results	= $domains->getDistinct('cc');
?>
  <h3>Defined Country Codes</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Country</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $index => $language)
{
?>
      <tr>
	<td><?php print $index; ?></td>
	<td><?php print $language; ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php

$results	= new DomainSet(array('lang' => 'en'));
?>
  <h3>Get DomainSet with Language 'en'</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Domain</th>
	<th>Lang</th>
	<th>Name</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $code => $domain)
{
?>
      <tr>
	<td><?php print $code; ?></td>
	<td><?php print $domain->get('code'); ?></td>
	<td><?php print $domain->get('language'); ?></td>
	<td><?php print $domain->get('name'); ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php

$results	= new DomainSet();
?>
  <h3>Get DomainSet with null</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Domain</th>
	<th>Lang</th>
	<th>Name</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $code => $domain)
{
?>
      <tr>
	<td><?php print $code; ?></td>
	<td><?php print $domain->get('code'); ?></td>
	<td><?php print $domain->get('language'); ?></td>
	<td><?php print $domain->get('name'); ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php
$domain		= $results->rewind();
try {
$domain->set('domain',$domain->get('domain'));
} catch (Exception $e) { print "<p>" . $e->getMessage(); }
try {
$domain->set('language',$domain->get('language'));
} catch (Exception $e) { print "<p>" . $e->getMessage(); }
try {
$domain->set('name',$domain->get('name'));
} catch (Exception $e) { print "<p>" . $e->getMessage(); }
?>
<p>Domain: <?php print $domain->get('domain'); ?> </p>
<p>CountryCode: <?php print $domain->get('cc'); ?> </p>
<p>State: <?php print $domain->get('state'); ?> </p>
<p>Language: <?php print $domain->get('language'); ?> </p>
<p>Lang: <?php print $domain->get('lang'); ?> </p>
<p>Name: <?php print $domain->get('name'); ?> </p>
<?php
try {
$domain->set('cc',$domain->get('cc'));
} catch (Exception $e) { print "<p>" . $e->getMessage(); }


$getParms	= array('language'	=> 'fr');
$results	= new DomainSet($getParms);
?>
  <h3>getDomains language=fr</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Domain</th>
	<th>Lang</th>
	<th>Name</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $code => $domain)
{
?>
      <tr>
	<td><?php print $code; ?></td>
	<td><?php print $domain->get('domain'); ?></td>
	<td><?php print $domain->get('language'); ?></td>
	<td><?php print $domain->get('name'); ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php
$debug		= true;
$getParms	= array('cc'		=> 'US');
$results	= new DomainSet($getParms);
showTrace();
$debug		= false;
?>
  <h3>getDomains in 'US' default language</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Domain</th>
	<th>Lang</th>
	<th>Name</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $code => $domain)
{
?>
      <tr>
	<td><?php print $code; ?></td>
	<td><?php print $domain->get('domain'); ?></td>
	<td><?php print $domain->get('language'); ?></td>
	<td><?php print $domain->get('name'); ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php
$getParms	= array('language'	=> 'fr',
			'cc'		=> 'CA');
$results	= new DomainSet($getParms);
?>
  <h3>getDomains in 'CA' language=fr</h3>
  <table border='1'>
    <thead>
      <tr>
	<th>Index</th>
	<th>Domain</th>
	<th>Lang</th>
	<th>Name</th>
      </tr>
    </thead>
    <tbody>
<?php
foreach ($results as $code => $domain)
{
?>
      <tr>
	<td><?php print $code; ?></td>
	<td><?php print $domain->get('domain'); ?></td>
	<td><?php print $domain->get('language'); ?></td>
	<td><?php print $domain->get('name'); ?></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
<?php








