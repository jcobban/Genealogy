<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testRecord.php							*
 *									*
 *  Test the Record constructor					*
 *									*
 *  History:								*
 *	2017/11/02	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/Record.inc";
require_once __NAMESPACE__ . "/common.inc";

htmlHeader('Test Record');
?>
<body>
  <h3>Test Record('ChildStatuses')</h3>
<?php
    $info	= Record::getInformation('ChildStatuses');
    $record	= new Record(array('idcs' => 1),
			     'ChildStatuses');
?>
    <p>Information</p>
    <table>
      <thead>
	<tr>
	  <th class='colhead'>Key</th>
	  <th class='colhead'>Value</th>
	</tr>
      </thead>
      <tbody>
<?php
	    foreach($info as $key => $value)
	    {
		if (is_array($value))
		    $value	= print_r($value, true);
?>
        <tr>
	  <th class='label'><?php print $key; ?></th>
	  <td class='odd'><?php print $value; ?></td>
        </tr>
<?php
	    }
?>
      </tbody>
    </table>
    <p>Contents:</p>
    <table>
      <thead>
	<tr>
	  <th class='colhead'>Key</th>
<?php
	    foreach($record as $field => $value)
	    {
?>
	  <th class='colhead'><?php print $field;?></th>
<?php
	    }
?>
	  <th class='colhead'>New?</th>
	</tr>
      </thead>
      <tbody>
<?php
	for($idcs = 1; $idcs <= 17; $idcs++)
	{
	    $record	= new Record(array('idcs' => $idcs),
				     'ChildStatuses');
?>
	<tr>
	  <th class='label'><?php print $key; ?></th>
<?php
	    foreach($record as $field => $value)
	    {
		if (strlen($value) > 32)
		    $value	= substr($value,0,29) . '...';
?>
	  <td class='odd'><?php print $value;?></td>
<?php
	    }

	    if ($record->isExisting())
	    {
?>
	  <td class='odd'>Old</td>
<?php
	    }
	    else
	    {
?>
	  <td class='odd'>New</td>
<?php
	    }
?>
	</tr>
<?php
	}
?>
      </tbody>
    </table>
</body>
</html>
