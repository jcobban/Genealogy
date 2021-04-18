<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  FamilyHistoryRequest.php											*
 *																		*
 *  Get the information on an instance of Record as a JSON response		*
 *  file so it can be retrieved by Javascript.							*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *      recipient       email of target user                            *
 *      subject         subject of request                              *
 *      ...                                                             *
 *																		*
 *  History:															*
 *		2021/04/03		created					                        *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . "/common.inc";

// process input parameters
$id                 = 0;
$keyvalue           = 0;
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
        $safevalue      = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        $value          = trim($value);
        switch(strtolower($key))
        {               // act on specific parameters
            case 'recipient':
                $recipient      = $safevalue;
                break;

			case 'subject':
				$subject		= $safevalue;
				break;

			case 'fffname':
				$fffname		= $safevalue;
				break;

			case 'fffbirth':
				$fffbirth		= $safevalue;
				break;

			case 'fffbplace':
				$fffbplace		= $safevalue;
				break;

			case 'fffdeath':
				$fffdeath		= $safevalue;
				break;

			case 'ffname':
				$ffname		= $safevalue;
				break;

			case 'fffdplace':
				$fffdplace		= $safevalue;
				break;

			case 'ffbirth':
				$ffbirth		= $safevalue;
				break;

			case 'ffbplace':
				$ffbplace		= $safevalue;
				break;

			case 'ffdeath':
				$ffdeath		= $safevalue;
				break;

			case 'ffmname':
				$ffmname		= $safevalue;
				break;

			case 'ffdplace':
				$ffdplace		= $safevalue;
				break;

			case 'ffmbirth':
				$ffmbirth		= $safevalue;
				break;

			case 'ffmbplace':
				$ffmbplace		= $safevalue;
				break;

			case 'ffmdeath':
				$ffmdeath		= $safevalue;
				break;

			case 'fname':
				$fname		= $safevalue;
				break;

			case 'fmfdplace':
				$fmfdplace		= $safevalue;
				break;

			case 'fbirth':
				$fbirth		= $safevalue;
				break;

			case 'fbplace':
				$fbplace		= $safevalue;
				break;

			case 'fdeath':
				$fdeath		= $safevalue;
				break;

			case 'fmfname':
				$fmfname		= $safevalue;
				break;

			case 'fdplace':
				$fdplace		= $safevalue;
				break;

			case 'fmfbirth':
				$fmfbirth		= $safevalue;
				break;

			case 'fmfbplace':
				$fmfbplace		= $safevalue;
				break;

			case 'fmfdeath':
				$fmfdeath		= $safevalue;
				break;

			case 'fmname':
				$fmname		= $safevalue;
				break;

			case 'fmfdplace':
				$fmfdplace		= $safevalue;
				break;

			case 'fmbirth':
				$fmbirth		= $safevalue;
				break;

			case 'fmbplace':
				$fmbplace		= $safevalue;
				break;

			case 'fmdeath':
				$fmdeath		= $safevalue;
				break;

			case 'fmmname':
				$fmmname		= $safevalue;
				break;

			case 'fmdplace':
				$fmdplace		= $safevalue;
				break;

			case 'fmmbirth':
				$fmmbirth		= $safevalue;
				break;

			case 'fmmbplace':
				$fmmbplace		= $safevalue;
				break;

			case 'fmmdeath':
				$fmmdeath		= $safevalue;
				break;

			case 'name':
				$name		= $safevalue;
				break;

			case 'fmfdplace':
				$fmfdplace		= $safevalue;
				break;

			case 'birth':
				$birth		= $safevalue;
				break;

			case 'bplace':
				$bplace		= $safevalue;
				break;

			case 'email':
				$email		= $safevalue;
				break;

			case 'mffname':
				$mffname		= $safevalue;
				break;

			case 'mffbirth':
				$mffbirth		= $safevalue;
				break;

			case 'mffbplace':
				$mffbplace		= $safevalue;
				break;

			case 'mffdeath':
				$mffdeath		= $safevalue;
				break;

			case 'mfname':
				$mfname		= $safevalue;
				break;

			case 'mffdplace':
				$mffdplace		= $safevalue;
				break;

			case 'mfbirth':
				$mfbirth		= $safevalue;
				break;

			case 'mfbplace':
				$mfbplace		= $safevalue;
				break;

			case 'mfdeath':
				$mfdeath		= $safevalue;
				break;

			case 'mfmname':
				$mfmname		= $safevalue;
				break;

			case 'mfdplace':
				$mfdplace		= $safevalue;
				break;

			case 'mfmbirth':
				$mfmbirth		= $safevalue;
				break;

			case 'mfmbplace':
				$mfmbplace		= $safevalue;
				break;

			case 'mfmdeath':
				$mfmdeath		= $safevalue;
				break;

			case 'mname':
				$mname		    = $safevalue;
				break;

			case 'mmfdplace':
				$mmfdplace		= $safevalue;
				break;

			case 'mbirth':
				$mbirth		    = $safevalue;
				break;

			case 'mbplace':
				$mbplace		= $safevalue;
				break;

			case 'mdeath':
				$mdeath		= $safevalue;
				break;

			case 'mmfname':
				$mmfname		= $safevalue;
				break;

			case 'mdplace':
				$mdplace		= $safevalue;
				break;

			case 'mmfbirth':
				$mmfbirth		= $safevalue;
				break;

			case 'mmfbplace':
				$mmfbplace		= $safevalue;
				break;

			case 'mmfdeath':
				$mmfdeath		= $safevalue;
				break;

			case 'mmname':
				$mmname		= $safevalue;
				break;

			case 'mmfdplace':
				$mmfdplace		= $safevalue;
				break;

			case 'mmbirth':
				$mmbirth		= $safevalue;
				break;

			case 'mmbplace':
				$mmbplace		= $safevalue;
				break;

			case 'mmdeath':
				$mmdeath		= $safevalue;
				break;

			case 'mmmname':
				$mmmname		= $safevalue;
				break;

			case 'mmdplace':
				$mmdplace		= $safevalue;
				break;

			case 'mmmbirth':
				$mmmbirth		= $safevalue;
				break;

			case 'mmmbplace':
				$mmmbplace		= $safevalue;
				break;

			case 'mmmdeath':
				$mmmdeath		= $safevalue;
				break;

			case 'mmfdplace':
				$mmfdplace		= $safevalue;
				break;
        }               // act on specific parameters
    }                   // loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                       // invoked to display message form

// start the template
$template           = new FtTemplate("FamilyHistoryRequest$lang.html");

$body               = 

				"subject=$subject\n" . "<br>" .

				"name=$name\n" . "<br>" .	
				"birth=$birth\n" . "<br>" .	
				"bplace=$bplace\n" . "<br>" .	
				"email=$email\n" . "<br>" .	

				"fname=$fname\n" . "<br>" .	
				"fbirth=$fbirth\n" . "<br>" .	
				"fbplace=$fbplace\n" . "<br>" .	
				"fdeath=$fdeath\n" . "<br>" .	
				"fdplace=$fdplace\n" . "<br>" .	

				"ffname=$ffname\n" . "<br>" .	
				"ffbirth=$ffbirth\n" . "<br>" .	
				"ffbplace=$ffbplace\n" . "<br>" .	
				"ffdeath=$ffdeath\n" . "<br>" .	
				"ffdplace=$ffdplace\n" . "<br>" .	

				"fffname=$fffname\n" . "<br>" .	
				"fffbirth=$fffbirth\n" . "<br>" .	
				"fffbplace=$fffbplace\n" . "<br>" .	
				"fffdeath=$fffdeath\n" . "<br>" .	
				"fffdplace=$fffdplace\n" . "<br>" .	

				"ffmname=$ffmname\n" . "<br>" .	
				"ffmbirth=$ffmbirth\n" . "<br>" .	
				"ffmbplace=$ffmbplace\n" . "<br>" .	
				"ffmdeath=$ffmdeath\n" . "<br>" .	
				"ffmdplace=$ffmdplace\n" . "<br>" .	

				"fmname=$fmname\n" . "<br>" .	
				"fmbirth=$fmbirth\n" . "<br>" .	
				"fmbplace=$fmbplace\n" . "<br>" .	
				"fmdeath=$fmdeath\n" . "<br>" .	
				"fmdplace=$fmdplace\n" . "<br>" .	

				"fmfname=$fmfname\n" . "<br>" .	
				"fmfbirth=$fmfbirth\n" . "<br>" .	
				"fmfbplace=$fmfbplace\n" . "<br>" .	
				"fmfdeath=$fmfdeath\n" . "<br>" .	
				"fmfdplace=$fmfdplace\n" . "<br>" .	

				"fmmname=$fmmname\n" . "<br>" .	
				"fmmbirth=$fmmbirth\n" . "<br>" .	
				"fmmbplace=$fmmbplace\n" . "<br>" .	
				"fmmdeath=$fmmdeath\n" . "<br>" .	
				"fmmdplace=$fmmdplace\n" . "<br>" .	

				"mname=$mname\n" . "<br>" .	
				"mbirth=$mbirth\n" . "<br>" .	
				"mbplace=$mbplace\n" . "<br>" .	
				"mdeath=$mdeath\n" . "<br>" .	
				"mdplace=$mdplace\n" . "<br>" .	

				"mfname=$mfname\n" . "<br>" .	
				"mfbirth=$mfbirth\n" . "<br>" .	
				"mfbplace=$mfbplace\n" . "<br>" .	
				"mfdeath=$mfdeath\n" . "<br>" .	
				"mfdplace=$mfdplace\n" . "<br>" .	

				"mffname=$mffname\n" . "<br>" .	
				"mffbirth=$mffbirth\n" . "<br>" .	
				"mffbplace=$mffbplace\n" . "<br>" .	
				"mffdeath=$mffdeath\n" . "<br>" .	
				"mffdplace=$mffdplace\n" . "<br>" .	

				"mfmname=$mfmname\n" . "<br>" .	
				"mfmbirth=$mfmbirth\n" . "<br>" .	
				"mfmbplace=$mfmbplace\n" . "<br>" .	
				"mfmdeath=$mfmdeath\n" . "<br>" .	
				"mfmdplace=$mfmdplace\n" . "<br>" .	

				"mmname=$mmname\n" . "<br>" .	
				"mmbirth=$mmbirth\n" . "<br>" .	
				"mmbplace=$mmbplace\n" . "<br>" .	
				"mmdeath=$mmdeath\n" . "<br>" .	
				"mmdplace=$mmdplace\n" . "<br>" .	

				"mmfname=$mmfname\n" . "<br>" .	
				"mmfbirth=$mmfbirth\n" . "<br>" .	
				"mmfbplace=$mmfbplace\n" . "<br>" .	
				"mmfdeath=$mmfdeath\n" . "<br>" .	
				"mmfdplace=$mmfdplace\n" . "<br>" .	

				"mmmname=$mmmname\n" . "<br>" .	
				"mmmbirth=$mmmbirth\n" . "<br>" .	
				"mmmbplace=$mmmbplace\n" . "<br>" .	
				"mmmdeath=$mmmdeath\n" . "<br>" .	
                "mmmdplace=$mmmdplace\n";

$userparms          = array('auth'  => array('yes','all','historian'));
$users              = new RecordSet('Users', $userparms);
$blogid             = '';
$comma              = '';
foreach($users as $username => $user)
{
    $blog       = new Blog(array('table'        => 'Users',
                                 'keyname'      => 'id',
                                 'keyvalue'      => $user['id'],
                                 'username'     => $username,
                                 'blogname'     => $subject,
                                 'text'         => $body));
    $blog->save(false);
    $blogid     .= $comma . $blog['id'] . " to family historian $username";
    $comma      = ',';
}

$template->set('BLOGID', $blogid);
$template->set('BODY', $body);
$template->display();
