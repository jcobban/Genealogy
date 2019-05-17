<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testRecordSet.php													*
 *																		*
 *  Test the RecordSet constructor										*
 *																		*
 *  History:															*
 *		2017/11/02		created											*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban				                *
 ************************************************************************/
require_once __NAMESPACE__ . "/RecordSet.inc";
require_once __NAMESPACE__ . "/common.inc";

class Tester extends Record
{
    function __construct()
    {
		foreach (self::$primeKey as $table => $description)
		{
?>
  <h3>Test RecordSet('<?php print $table; ?>')</h3>
<?php
		    $parms	= array('limit' => 5);
		    $info	= Record::getInformation($table);
		    $initRow	= $info['initrow'];
		    foreach($initRow as $fldname => $value)
		    {
				if (strpos($fldname, 'name') !== false)
				{
				    $parms[$fldname]	= 'D';
				    break;
				}
		    }
		    $set	= new RecordSet($table, $parms); 
		    $info	= $set->getInformation();
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
		    showTrace();
		    foreach($info as $key => $value)
		    {
			    if (is_array($value))
			        $value	= print_r($value, true);
                else
                if (is_bool($value))
                {
                    if ($value)
                        $value  = 'true';
                    else
                        $value  = 'false';
                }
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
<?php
		    $first	= true;
		    foreach($set as $key => $record)
		    {		// loop through records
			    if ($first)
			    {	// put out header
?>
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
		</tr>
      </thead>
      <tbody>
<?php
			        }	// put out header
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
?>
		</tr>
<?php
			        $first	= false;
		        }		// loop through records
?>
      </tbody>
    </table>
<?php
		    showTrace();
		    flush();
		}
    }
}       // class Tester

htmlHeader('Test RecordSet');
?>
<body>
  <h3>Test RecordSet('Locations', OR Expression)</h3>
<?php
    $debug	= true;
    $set	= new RecordSet('Locations',
					        array(array('Location'	=> '^lon',
						                'ShortName'	=> '^lon'),
					        'limit'		=> 5));
    $debug	= false;
    showTrace();
    $info	= $set->getInformation();
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
                else
                if (is_bool($value))
                {
                    if ($value)
                        $value  = 'true';
                    else
                        $value  = 'false';
                }
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
<?php
		    $first	= true;
		    foreach($set as $key => $record)
		    {		// loop through records
			if ($first)
			{	// put out header
?>
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
		</tr>
      </thead>
      <tbody>
<?php
			}	// put out header
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
?>
		</tr>
<?php
			$first	= false;
		    }		// loop through records
?>
      </tbody>
    </table>
<?php
    flush();
    new Tester();
?>
</body>
</html>
