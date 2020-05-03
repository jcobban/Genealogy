<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
use \Templating\TemplateTag;

/************************************************************************
 *  BlogPost.php                                                        *
 *                                                                      *
 *  Display a web page for creating a new Blog post.                    *
 *                                                                      *
 *  Parameters (passed by method='get')                                 *
 *      blogid  unique numeric identifier of the entry in table Blogs   *
 *              to which this ia a response or follow on.  Default 0.   *
 *      table   Table that this message is referencing.  Default 'Blogs'*
 *                                                                      *
 * History:                                                             *
 *      2018/09/12      created                                         *
 *      2018/12/12      change insertion in title to BLOGTITLE          *
 *      2019/02/18      use new FtTemplate constructor                  *
 *      2020/05/02      TABLE and LANG not set in blogTemplate          *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function responses                                                  *
 *                                                                      *
 *  This function is invoked recursively to display the tree of         *
 *  blog posts.                                                         *
 *                                                                      *
 *  Input:                                                              *
 *      $id         the identifier of the post at the top of a          *
 *                  tree of posts to display                            *
 *      $indent     the indentation level of the current post           *
 *                                                                      *
 *  Returns:                                                            *
 *      A string containing HTML representing the contents of the       *
 *      tree of blog posts.                                             *
 ************************************************************************/
function responses($id  , $indent)
{
    global  $document_root;
    global  $lang;
    global  $table;
    global  $userid;
    global  $months;
    global  $lmonths;
    global  $blogTemplate;
    global  $debug;
    global  $warn;

    $blog               = new Blog(array('id'   => $id  ));
    $matches            = array();
    $username           = $blog->get('username');
    $btemplate          = new Template($blogTemplate);
    if ($username != $userid && !canUser('all'))
    {
        $btemplate->updateTag('buttonRow$id ', null);
    }
    $btemplate->set('blogid',   $id );
    $btemplate->set('margin',   ($indent + 6) . 'em');
    $btemplate->set('datetime', $blog->get('datetime'));
    $btemplate->set('username', $blog->get('username'));
    $btemplate->set('TABLE',    $table);
    $btemplate->set('LANG',     $lang);
    if ($debug)
        $btemplate->set('debug',            'Y');
    else
        $btemplate->set('debug',            'N');
    $subject            = $blog->get('subject');
    if (strlen($subject) == 0)
        $subject        = '*not supplied*';
    $btemplate->set('blogname', $subject);
    $text               = $blog->get('text');
    $btemplate->set('message',  $text);
    $datetime           = $blog->get('datetime');
    if (preg_match('/^(\d+)-(\d+)-(\d+) (.*)$/', $datetime, $matches) == 1)
    {
        $btemplate->set('year',     $matches[1]);
        $btemplate->set('month',    $months[$matches[2] - 0]);
        $btemplate->set('lmonth',   $lmonths[$matches[2] - 0]);
        $btemplate->set('day',      $matches[3]);
        $btemplate->set('time',     $matches[4]);
    }
    else
    {
        $btemplate->set('year', '');
        $btemplate->set('month', '');
        $btemplate->set('lmonth', '');
        $btemplate->set('day', '');
        $btemplate->set('time', $datetime);
    }
    $posts      = $btemplate->compile();
    $indent     += 6;

    $blogParms      = array('keyvalue'  => $id  ,
                    'table'     => 'Blogs');
    $bloglist       = new RecordSet('Blogs', $blogParms);
    $blogCount      = $bloglist->count();
    foreach($bloglist as $blog)
    {
        $id         = $blog->get('id');
        $posts      .= responses($id    , $indent);
    }           // loop through responses

    return $posts;  // accumulated HTML string
}       // function responses

/************************************************************************
 *            OOO  PPPP  EEEEE N   N    CCC   OOO  DDDD  EEEEE          *
 *           O   O P   P E     NN  N   C   C O   O D   D E              *
 *           O   O PPPP  EEEE  N N N   C     O   O D   D EEEE           *
 *           O   O P     E     N  NN   C   C O   O D   D E              *
 *            OOO  P     EEEEE N   N    CCC   OOO  DDDD  EEEEE          *
 ************************************************************************/

// process input parameters
$id                 = 0;
$lang               = 'en';
$table              = 'Blogs';
$keyname            = 'blogid';
$edit               = false;
$update             = false;

if (isset($_GET) && count($_GET) > 0)
{                       // invoked to display message form
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {                   // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        $value          = trim($value);
        switch(strtolower($key))
        {               // act on specific parameters
            case 'blogid':
            case 'id':
            {           // message being followed up
                $id                 = trim($value);
                break;
            }           // message being followed up

            case 'idar':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblAR';
                break;
            }

            case 'idbp':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblBP';
                break;
            }

            case 'idbr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblBR';
                break;
            }

            case 'idcp':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblCP';
                break;
            }

            case 'idcr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblCR';
                break;
            }

            case 'ider':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblER';
                break;
            }

            case 'idhb':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblHB';
                break;
            }

            case 'idhl':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblHL';
                break;
            }

            case 'idir':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblIR';
                break;
            }

            case 'idlr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblLR';
                break;
            }

            case 'idmr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblMR';
                break;
            }

            case 'idms':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblMS';
                break;
            }

            case 'idnr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblNR';
                break;
            }

            case 'idnx':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblNX';
                break;
            }

            case 'idrm':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblRM';
                break;
            }

            case 'idsr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblSR';
                break;
            }

            case 'idsx':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblSX';
                break;
            }

            case 'idtc':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblTC';
                break;
            }

            case 'idtd':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblTD';
                break;
            }

            case 'idtl':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblTL';
                break;
            }

            case 'idtr':
            {
                $id                 = (int)$value;
                $keyname            = $key;
                $table              = 'tblTR';
                break;
            }

            case 'username':
            {
                $id                 = $value;
                $keyname            = $key;
                $table              = 'Users';
                break;
            }

            case 'table':
            {           // get the table name
                if (strlen($value) > 0)
                    $table          = $value;
                break;
            }           // get the table name

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

            case 'edit':
            {
                if (strtoupper($value) == 'Y')
                    $edit       = true;
                break;
            }

        }               // act on specific parameters
    }                   // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                       // invoked to display message form
else
if (isset($_POST) && count($_POST) > 0)
{                       // invoked to update database
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    $edit       = true;
    foreach($_POST as $key => $value)
    {                   // loop through all parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        $value          = trim($value);
        switch(strtolower($key))
        {               // act on specific parameters
            case 'blogid':
            case 'id':
            case 'keyvalue':
            {           // identifier of record that message applies to
                $keyname            = $key;
                $id                 = trim($value);
                break;
            }           // message being followed up

            case 'table':
            case 'tablename':
            {           // get the table name
                if (strlen($value) > 0)
                    $table          = $value;
                break;
            }           // get the table name

            case 'subject':
            {
                $subject            = $value;
                break;
            }       // message

            case 'message':
            case 'text':
            {
                $message            = $value;
                break;
            }       // message

            case 'email':
            case 'emailaddress':
            {
                $email              = $value;
                break;
            }       // email address of sender

            case 'update':
            {
                if (strtoupper($value) == 'Y')
                    $update         = true;
                break;
            }       // email address of sender

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

        }               // act on specific parameters
    }                   // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    $tableInfo                      = Record::getInformation($table);
    if ($tableInfo)
    {
        if ($table == 'Blogs')
            $blog   = new Blog(array('bl_index'         => $id,
                                     'table'            => 'Blogs',
                                     'keyvalue'         => $id,
                                     'keyname'          => 'BL_Index',
                                     'username'         => $userid,
                                     'blogname'         => $subject,
                                     'text'             => $message));
        else
            $blog   = new Blog(array('table'            => $table,
                                     'keyvalue'         => $id,
                                     'keyname'          => $tableInfo['prime'],
                                     'username'         => $userid,
                                     'blogname'         => $subject,
                                     'text'             => $message));
        $blog->save(false);
        $warn       .= "<p>Message posted</p>\n";
        $table      = 'Blogs';
        $className  = 'Blog';
        $id         = $blog['bl_index'];
    }
}                       // invoked to update database

// start the template
$template           = new FtTemplate("BlogPost$lang.html");
$trtemplate         = $template->getTranslate(); 

// internationalization support
$blogTemplate       = $template['blogTemplate'];
if ($blogTemplate)
    $blogTemplate       = $blogTemplate->innerHTML();
else
    error_log("BlogPost.php: " . __LINE__ . " Cannot find 'blogTemplate' in 'BlogPost$lang.html' " . $_SERVER['QUERY_STRING'] . "\n");
$months             = $trtemplate['Months'];
$lmonths            = $trtemplate['LMonths'];

// actions dependent on invoking user
if ($user instanceof User && $user->isExisting())
{
    $template['notLoggedOn']->update( null);
    $template->set('EMAILCLASS',    'ina');
    $template->set('READONLY',      'readonly="readonly"');
    $template->set('EMAIL',         $user->get('email'));
}
else
{                       // not signed in
    $template->set('EMAILCLASS',    'white');
    $template->set('READONLY',      '');
    $template->set('EMAIL',         '');
}                       // not signed in

// table name
$name                           = '';
$tableInfo                      = Record::getInformation($table);
if ($tableInfo)
{
    if ($table != 'Users')
    {
        $matches            = array();
        if (is_string($id) &&
            preg_match('/\d+/', $id, $matches) == 1)
        {           // numeric id somewhere in string
            $id             = (int)$matches[0];
        }           // numeric id somewhere in string
        else
        if (is_int($id) || ctype_digit($id))
        {
            $id             = (int)$id;
        }
        else
            $msg            .= "Invalid $keyname=$id. ";
    }

    $template['badTable']->update(null);        // remove error message
    $className                  = $tableInfo['classname'];
    if (!class_exists(__NAMESPACE__ . "\\$className"))
    {
        require __NAMESPACE__ . "/$className.inc";
    }
    $keyname                    = $tableInfo['prime'];
    if ((is_int($id) && $id > 0) ||
        (strlen($id) > 0 && $id != '0'))
    {
        $qualName               = __NAMESPACE__ . "\\$className";
        $instance               = new $qualName(array($keyname  => $id));
        if ($instance->isExisting())
        {
            $name               = $instance->getName();
            if ($className == 'User')
                $id             = $instance['id'];
        }
        else
        {
            $warn               .= "<p>BlogPost.php: " . __LINE__ .
        " Instance of $className with $keyname='$id' does not exist</p>\n";
        }
    }
}
else
{                           // unsupported table
    $template['badTable']->update(array('table' => $table));
    $className                  = $table;
    $keyname                    = null;
}                           // unsupported table

$template->set('NAME',              $name);
$template->set('CLASS',             $className);
$template->set('CONTACTTABLE',      $table);
$template->set('TABLE',             $table);
$template->set('KEYNAME',           $keyname);

// other parameters
$template->set('CONTACTKEY',        $id);
$template->set('userid',            $userid);
$template->set('blogid',            $id);
$template->set('margin',            '');
$template->set('LANG',              $lang);
if ($debug)
    $template->set('debug',         'Y');
else
    $template->set('debug',         'N');

if ($id > 0 && $table == 'Blogs')
{
    $blog               = new Blog(array('id'       => $id));
    $subject            = $blog->get('subject');
    if (strlen($subject) == 0)
        $subject        = $id;
    $template->set('BLOGTITLE',     $subject);

    if ($blog->isExisting())
    {                   // key of existing Blog record
        $parentblogid           = $blog->get('keyvalue');
        $template->set('parentblogid',$parentblogid);
        if ($parentblogid == 0)
            $template['parentBlog']->update( null);
        else
            $template['parentBlog']->update(
                                 array('parentblogid' => $parentblogid));

        if ($edit)
        {
            $template->set('POSTS',         '');
            $template->set('posttext',      $blog->get('text'));
            $username           = $blog->get('username');
            $owner              = new User(array('username' => $username));
            $template->set('EMAIL',     $owner->get('email'));
            $h1tag              = $template['EditBlog'];
            $template->set('TITLE',
                      str_replace('$BLOGTITLE', $subject, $h1tag->innerHTML()));
            $template['Response']->update(  null);
            $template['NewPost']->update(   null);
        }
        else
        {
            $template->set('POSTS',         responses($id, 0));
            $template->set('posttext',      '');
            $h1tag              = $template['AddBlog'];
            $template->set('TITLE',
                          str_replace('$BLOGTITLE', $subject, $h1tag->innerHTML()));
            $template['Edit']->update(  null);
            $template['NewPost']->update(   null);
        }
    }           // key of existing Blog record
    else
    {           // new Blog record
        $template->set('POSTS',         '');
        $template->set('posttext',      '');
        $template->set('parentblogid',  '');
        $h1tag          = $template['AddBlog'];
        $template->set('TITLE',
                       str_replace('$BLOGTITLE', $subject, $h1tag->innerHTML()));
        $template['Edit']->update(      null);
        $template['Response']->update(  null);
    }
}
else
{
    $template->set('POSTS',             '');
    $template->set('posttext',          '');
    $template->set('BLOGTITLE',         '');
    $template['parentBlog']->update(  null);
    if ($table == 'Blogs')
        $h1tag              = $template['NewBlog'];
    else
    if ((is_int($id) && $id > 0) ||
        (strlen($id) > 0 && $id != '0'))
        $h1tag              = $template['NewSpecific'];
    else
        $h1tag              = $template['NewMessage'];
    $template->set('TITLE',
                   str_replace(array('$CLASS', '$NAME'),
                               array($className, $name), 
                               $h1tag->innerHTML()));
    $template['Edit']->update(      null);
    $template['Response']->update(  null);
}

$template->display();
