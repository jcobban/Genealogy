<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  testUser.php														*
 *																		*
 *  This script tests the User class.                                   *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/UserSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
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

$tempBase	        = $document_root . '/templates/';
$template           = new Template("{$tempBase}pageen.html");

$template->set('TITLE', 'test UserSet');
ob_start();
$debug              = true;

// get all users
$set	 = new UserSet();
print '<p>$set	 = new UserSet();' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>";                 
// get all users except the current user
$set     = new UserSet(array('username' => '!' . $userid));
print '<p>$set     = new UserSet(array(\'username\' => \'!' . $userid . '\'));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
// get all users whose name contains 'sam'
$set     = new UserSet(array('username' => 'sam'));
print '<p>$set     = new UserSet(array(\'username\' => \'sam\'));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
// get all users whose name contains 'sam' but excluding the current user
$set     = new UserSet(array('username' => array('!' . $userid, 'sam')));
print '<p>$set     = new UserSet(array(\'username\' => array(\'!' . $userid . '\', \'sam\')));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
// get all users who are awaiting confirmation
$set     = new UserSet(array('auth' => 'pending'));
print '<p>$set     = new UserSet(array(\'auth\' => \'pending\'));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
?>
    <table class='summary'>
      <tr><th class='colhead'>UserName</th>" .
        <th class='colhead'>Email</th>
        <th class='colhead'>Auth</th>
      </tr>
<?php
foreach($set as $user)
{
?>
      <tr>
        <td class="odd"><?php print $user['username']; ?></td>
        <td class="odd"><?php print $user['email']; ?></td>
        <td class="odd"><?php print $user['auth']; ?></td>
      </tr>
<?php
}
?>
    </table>
<?php
// get all users who can edit the database
$set     = new UserSet(array('auth' => 'edit'));
print '<p>$set     = new UserSet(array(\'auth\' => \'edit\'));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
?>
    <table class='summary'>
      <tr><th class='colhead'>UserName</th>" .
        <th class='colhead'>Email</th>
        <th class='colhead'>Auth</th>
      </tr>
<?php
foreach($set as $user)
{
?>
      <tr>
        <td class="odd"><?php print $user['username']; ?></td>
        <td class="odd"><?php print $user['email']; ?></td>
        <td class="odd"><?php print $user['auth']; ?></td>
      </tr>
<?php
}
?>
    </table>
<?php
// get all Users who accept e-mails
$set     = new UserSet(array('options' => 1));
print '<p>$set     = new UserSet(array(\'options\' => 1));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
// get all administrators
$set     = new UserSet(array('auth' => 'all'));
print '<p>$set     = new UserSet(array(\'auth\' => \'all\'));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
?>
    <table class='summary'>
      <tr><th class='colhead'>UserName</th>" .
        <th class='colhead'>Email</th>
        <th class='colhead'>Auth</th>
      </tr>
<?php
foreach($set as $user)
{
?>
      <tr>
        <td class="odd"><?php print $user['username']; ?></td>
        <td class="odd"><?php print $user['email']; ?></td>
        <td class="odd"><?php print $user['auth']; ?></td>
      </tr>
<?php
}
?>
    </table>
<?php
// get all users who can update a specific record
$set     = new UserSet(array('table' => 'Persons', 'recordid' => 611));
print '<p>$set     = new UserSet(array(\'table\' => \'Persons\', \'recordid\' => 611));' . "</p>\n";
$info		= $set->getInformation();
print "<p>" . $info['query'] . "</p>\n";
?>
    <table class='summary'>
      <tr><th class='colhead'>UserName</th>" .
        <th class='colhead'>Email</th>
        <th class='colhead'>Auth</th>
      </tr>
<?php
foreach($set as $user)
{
?>
      <tr>
        <td class="odd"><?php print $user['username']; ?></td>
        <td class="odd"><?php print $user['email']; ?></td>
        <td class="odd"><?php print $user['auth']; ?></td>
      </tr>
<?php
}
?>
    </table>
<?php


print "<p class='message'>$msg</p>\n";
showTrace();
$template->set('MAIN', ob_get_clean());
$template->display();
