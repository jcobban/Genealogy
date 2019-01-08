<?php
namespace Genealogy;
use \PDO;
use \Exception;
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/common.inc';

$db_username = ''; //username
$db_password = ''; //password
//path to database file
$database_path = "/home/jcobban/FamilyTree/Cobban.mdb";
//check file exist before we proceed
if (!file_exists($database_path)) {
    die("Access database file not found !");
}
//create a new PDO object
$database = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$database_path; Uid=$db_username; Pwd=$db_password;");
$sql  = "SELECT * FROM tblHR";
$result = $database->query($sql);
while ($row = $result->fetch()) {
    echo $row["First Name"];
}
