<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  test Forgot Password						*
 *									*
 ************************************************************************/
require_once __NAMESPACE__ . '/common.inc';

?>
  <h3>Test Forgot Password</h3>
  <form action="/forgotPassword.php" method="post"> 
    <p>
      <label for="userid">User ID:
	<input type='text' name="userid" id="userid">
    </p>
    <p>
      <label for="email">E-Mail Address:
	<input type='text' name="email" id="email">
    </p>
    <p>
	<button type='submit' id="Submit">Test</button>
    </p>
  </form>
