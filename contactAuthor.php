<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  contactAuthor.php                                                   *
 *                                                                      *
 *  Implement contacting the author of a page by using the internal     *
 *  blog support.                                                       *
 *  If tablename and id are specified then the message is sent to all   *
 *  of the owners of the specified record.                              *
 *  If username is specified the message is sent to the specified user  *
 *                                                                      *
 *  Parameters:                                                         *
 *      id              unique key of associated record instance        *
 *      tablename       database table the key refers to                *
 *      username        specific user to send the message to            *
 *      subject         information about the referrer                  *
 *      text            additional text to include in message           *
 *                                                                      *
 *  History:                                                            *
 *      2014/03/27      use common layout routines                      *
 *                      use HTML 4 features, such as <label>            *
 *      2015/02/05      add accessKey attributes to form elements       *
 *                      change text in button to "Send"                 *
 *                      correct class name from RecOwners to RecOwner   *
 *      2015/03/05      separate initialization logic and HTML          *
 *      2015/03/25      top page of hierarchy is now genealogy.php      *
 *      2015/05/26      add optional text to initialize message         *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/12/30      fix conflict with autoload                      *
 *      2016/01/19      add id to debug trace                           *
 *      2017/08/16      script legacyIndivid.php renamed to Person.php  *
 *                      use preferred form of new LegacyIndiv           *
 *      2017/09/12      use get( and set(                               *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/10/17      use class UserSet instead of RecOwner           *
 *                      correct placement of page top                   *
 *      2018/09/07      default template namemisspelled                 *
 *      2018/10/15      get language apology text from Languages        *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2019/06/13      support I18N by moving "About :" template       *
 *                      support explicitly sending message to a user    *
 *                                                                      *
 *  Copyright &copy; 2019 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/UserSet.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

if (!canUser('blog'))
    $userid     = '';
$recordid       = 0;
$tableName      = 'tblIR';
$about          = '';
$text           = '';
$username       = '';
$user           = null;
$lang           = 'en';
$record         = null;

foreach($_GET as $name => $value)
{
    switch(strtolower($name))
    {
        case 'id':
        case 'idir':
        {
            $recordid       = $value;
            break;
        }       // key value

        case 'tablename':
        {
            $info           = Record::getInformation($value);
            if ($info)
                $tableName  = $info['table'];
            else
                $tableName  = $value;
            break;
        }       // table name

        case 'subject':
        {
            $about          = $value;
            break;
        }       // table name

        case 'text':
        {
            $text           = $value;
            break;
        }       // table name

        case 'username':
        {
            $username       = $value;
            $user           = new User(array('username' => $username));
            if ($user->isExisting())
            {
                $tableName  = 'Users';
                $recordid   = $user['id'];
            }
            break;
        }       // table name

        case 'lang':
        {
            if (strlen($value) >= 2)
                $lang       = strtolower(substr($value,0,2));
            break;
        }       // language selection

        case 'debug':
        {
            break;
        }       // handled by common code

        default:
        {
            if (is_string($value))
                $about      .= "&$name=$value";
            break;
        }
    }           // act on specific keys
}               // loop through all parameters

$template           = new FtTemplate("ContactAuthor$lang.html");

if ($user && !$user->isExisting())
    $warn           .= "<p>parameter user=$username does not identify a registered user</p>\n";

// take any table specific action
if (strlen($about) == 0)
{
    if ($recordid)
    {
        switch($tableName)
        {           // act on specific table names
            case 'tblIR':
            {
                $record     = new Person(array('idir' => $recordid));
                if ($record->isExisting())
                    $about  = $record->getName() .  " (IDIR=$recordid)\n";
                else
                    $about  = "RecordID=$recordid"; 
                break;
            }

            default:
            {
                $about      = "$tableName: id=$recordid";
                break;
            }
        }
    }
    else
        $about      = "Subject not specified";
}           // act on specific table names

// get a list of all the owners of the current record
// this includes all of the administrators
if (strlen($recordid) > 0 && strlen($tableName) > 0)
{
    if ($tableName == 'User')
        $contacts   = new UserSet(array('id'        => $recordid));
    else
        $contacts   = new UserSet(array('recordid'  => $recordid,
                                        'table'     => $tableName));
}
else
    $contacts       = new UserSet(array('auth'      => 'yes'));

$contactIds         = '';
$comma              = '';
foreach ($contacts as $ic => $contact)
{
    $contactIds     .= $comma . $contact->get('id');
    $comma          = ',';
}

$user               = new User(array("username" => $userid));
$email              = $user->get('email');
$template->set('USERID',        $userid);
$template->set('EMAIL',         $email);
$template->set('LANG',          $lang);
$template->set('ABOUT',         $about);
$template->set('TEXT',          $text);
$template->set('TABLENAME',     $tableName);
$template->set('CONTACTIDS',    $contactIds);

// for registered users the E-Mail address is a private attribute of
// the User record, do not expose it to prying eyes
$email      = null;
if ($userid && strlen($userid) > 0)
{           // have userid of current user
    $user       = new User(array('username' => $userid));
    $email      = $user->get('email');
    if ($user->isExisting())
        $template->updateTag('promptforemail', null);
    else
        $template->updateTag('hiddenemail', null);
}           // have userid of current user
else
    $template->updateTag('hiddenemail', null);

$template->display();
