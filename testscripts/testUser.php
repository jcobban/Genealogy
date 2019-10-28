<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testUser.php														*
 *																		*
 *  This script tests the User class.                                   *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

// common
$lang	    	    = 'en';

foreach($_GET as $key => $value)
{
    switch(strtolower($key))
    {
        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang           = strtolower(substr($value,0,2));
            break;
        }
    }
}
print "<h1>test User</h1>\n";
$debug              = true;
$user               = new User('garbage3');
showTrace();
$user['auth']       = 'edit';
$user['email']      = 'garbage3@gmail.com';
$user->dump('after setting auth=edit');
showTrace();
$user['auth']       = 'pending';
$user->dump('after setting auth=pending');
showTrace();
$user->save(false);
print "<p>$msg</p>\n";
$user->dump('after save');
showTrace();
