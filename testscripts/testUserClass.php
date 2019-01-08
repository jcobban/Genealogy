<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUserClass.php							*
 *									*
 *  Test the User constructor						*
 *									*
 *  History:								*
 *	2018/09/24	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/User.inc";
require_once __NAMESPACE__ . "/common.inc";

?>
<!DOCTYPE HTML >
<html lang="en">
  <head>
    <title>
	Test User Class
    </title>
    <meta charset='utf-8'>
    <meta http-equiv='default-style' content='text/css'>
    <meta name='author' content='James A. Cobban'>
    <meta name='copyright' content='&copy; 2018 James A. Cobban'>
    <meta name='keywords' content='genealogy, family, tree, ontario, canada'>
    <link rel='stylesheet' type='text/css' href='/styles.css'>
  </head>
<body>
  <h1>Test User Class</h1>
<?php
$debug	= true;

$record	= new User (array('id' => 1));

$record	= new User (array('username' => 'jcobban'));

$record	= new User (array('email' => 'webmaster@jamescobban.net'));

$record	= new User (array('email' => 'jamesalancobban@gmail.com'));

$record	= new User (array('email' => 'jamesalancobban@gmail.com',
			  'password' => 'Spyn12..'));
showTrace();
?>
</body>
</html>
