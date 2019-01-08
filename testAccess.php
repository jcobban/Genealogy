<?php
namespace Genealogy;
use \PDO;
use \Exception;
require_once __NAMESPACE__ . '/LegacyHeader.inc';
require_once __NAMESPACE__ . '/common.inc';

$header     = new LegacyHeader();
$db_username = ''; //username
$db_password = ''; //password
//path to database file
$database_path = "/home/jcobban/FamilyTree/Cobban.mdb";
//check file exist before we proceed
if (!file_exists($database_path)) {
    die("Access database file not found !");
}
//create a new PDO object
$database = new PDO("odbc:DRIVER=MDBtools; DBQ=$database_path; Uid=$db_username; Pwd=$db_password;");

$sql  = "SELECT * FROM tblER LIMIT 10";
$result = $database->query($sql);
?>
<p><?php print $sql; ?></p>
<?php
if ($result)
{
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print "<p>" . print_r($row, true) . "</p>\n";
    }
}
else
    print "<p>" . __LINE__ . " '$sql' result=" . 
						print_r($connection->errorInfo(),true) . "</p>\n";
