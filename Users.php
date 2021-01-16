<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Users.php															*
 *																		*
 *  Display a web page containing all of the users.						*
 *																		*
 *  History:															*
 *		2010/10/31		created											*
 *		2011/02/14		add delete button								*
 *		2011/03/02		change name of submit button to 'Submit'		*
 *		2011/10/19		hide this tool from unauthorized users			*
 *		2011/11/10		make e-mail address a mailto link				*
 *		2011/11/28		add confirm userid button						*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/05/29		implement popup help							*
 *						add bulk mail function							*
 *						add subject to all mailto URLs					*
 *						use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/08/12		only send bulk mail to users who have			*
 *						requested it									*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS instead of tables for layout			*
 *						suppress left arrow on first page of results	*
 *						add link to overall help page					*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/25		field UseMail renamed to Options				*
 *						script moved to top level of public_html		*
 *						numeric key on buttons changed to unique		*
 *						numeric key of User record from row number		*
 *		2014/09/29		put "Family Tree Mailing List" in to attribute	*
 *						of sent bulk mail								*
 *		2014/10/19		permit administrator to change authorizations	*
 *		2014/10/25		add search by e-mail pattern					*
 *		2015/03/25		top page of hierarchy is now genealogy.php		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/10		remove use of SQL and use class User to			*
 *						manipulate the database							*
 *						add ability to edit usernames and email address	*
 *						display debug trace								*
 *		2015/12/16		add help for e-mail address field and correct	*
 *						help for user name pattern.						*
 *		2015/12/30		fix conflict with autoload						*
 *						link for send bulk mail uses class 'buttonText'	*
 *						hide buttons if more than 10 lines in response	*
 *						add help for editing user name, e-mail address,	*
 *						and authorizations.								*
 *		2016/01/19		add id to debug trace							*
 *		2017/09/12		use get( and set(								*
 *		2017/11/21		use RecordSet to get set of user records		*
 *		2018/02/02		use class Template								*
 *		2018/04/28		limit Bulk Mail to matching users				*
 *		2018/10/15      get language apology text from Languages        *
 *		2019/02/18      use new FtTemplate constructor                  *
 *		2019/04/11      add broadcast to pending users                  *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/User.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang		    	= 'en';
$pattern			= '';
$authPattern		= '';
$mailPattern		= '';
$offset			    = 0;
$limit			    = 20;
$id				    = '';
$mainParms			= array();
$bccParms			= array('options'	=> "&1");

if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{		        // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>" .
                        htmlspecialchars($value) . "</td></tr>\n"; 
	    $fieldLc	= strtolower($key);
	    switch($fieldLc)
	    {		// act on specific parameter
			case 'lang':
			{		// lang
			    if (strlen($value) >= 2)
					$lang			= strtolower(substr($value,0,2));
			    break;
			}		// lang

			case 'pattern':
			{
			    if (strlen($value) > 0)
			    {
					$pattern		= $value;
					$mainParms['username']	= $pattern;
					$bccParms['username']	= $pattern;
			    }
			    break;
			}

			case 'authpattern':
			{
			    if (strlen($value) > 0)
			    {
					$authPattern		= $value;
					$mainParms['auth']	= $authPattern;
					$bccParms['auth']	= $authPattern;
			    }
			    break;
			}

			case 'mailpattern':
			{
			    if (strlen($value) > 0)
			    {
					$mailPattern		= $value;
					$mainParms['email']	= $mailPattern;
					$bccParms['email']	= $mailPattern;
			    }
			    break;
			}

			case 'offset':
			{
			    $offset			= (int)$value;
			    break;
			}

			case 'limit':
			{
			    $limit			= (int)$value;
			    break;
			}
	    }		    // act on specific parameter
	}		        // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			        // invoked by method=get

// create the Template instance
$template		= new FtTemplate("UsersEdit$lang.html");

// if not the administrator do nothing
if (canUser('all'))
{		// only the administrator can use this dialog
	// get the parameters
	$namePattern		= '/^([A-Za-z_]+)(\d+)$/';

    if (count($_POST) > 0)
    {
	    $parmsText  = "<p class='label'>\$_POST</p>\n" .
	                  "<table class='summary'>\n" .
	                  "<tr><th class='colhead'>key</th>" .
	                      "<th class='colhead'>value</th></tr>\n";
		foreach($_POST as $key => $value)
		{		// loop through all parameters
	        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
	                        "<td class='white left'>$value</td></tr>\n"; 
		    // fields for individual users are identified by the
		    // field name plus the record id number
		    $rgxResult		= preg_match($namePattern,
							    	     $key,
							    	     $matches);
		    if ($rgxResult == 1)
		    {
				$column	        = strtolower($matches[1]);
				if ($matches[2] != $id)
				{		// change to id number
				    if ($user)
						$user->save(false);
				    $id		    = $matches[2];
				    $user	    = new User(array('id' => $id));
				}		// get new User record
		    }
		    else
            {
                $column         = strtolower($key);
				if ($user)
				{
				    $user->save(false);
				    $user	= null;
				}
				$id		= '';
		    }
	
		    switch($column)
		    {		// act on specific parameter name
				case 'pattern':
				{
				    if (strlen($value) > 0)
				    {
						$pattern		= $value;
						$mainParms['username']	= $pattern;
						$bccParms['username']	= $pattern;
				    }
				    break;
				}
	
				case 'authpattern':
				{
				    if (strlen($value) > 0)
				    {
						$authPattern		= $value;
						$mainParms['auth']	= $authPattern;
						$bccParms['auth']	= $authPattern;
				    }
				    break;
				}
	
				case 'mailpattern':
				{
				    if (strlen($value) > 0)
				    {
						$mailPattern		= $value;
						$mainParms['email']	= $mailPattern;
						$bccParms['email']	= $mailPattern;
				    }
				    break;
				}
	
				case 'offset':
				{
				    $offset			= (int)$value;
				    break;
				}
	
				case 'limit':
				{
				    $limit			= (int)$value;
				    break;
				}
	
				case 'user':
				{			// update to user name
				    if ($user)
				    {
						$user->set('username', $value);
				    }
				    break;
				}			// update to user name
	
				case 'email':
				{			// update to email address
				    if ($user)
				    {
						$user->set('email', $value);
				    }
				    break;
				}			// update to email address
	
				case 'auth':
				{			// update to auth settings
				    if ($user)
                    {       // have instance of User
                        if ($value == '')
                        {   // request to delete record
                            $count  = $user->delete(false);
                            if ($count == 0)
                                $warn   .= $user->dump('delete failed');
                            $lastCmd    = $user->getLastSqlCmd();
                            if (strlen($lastCmd) > 0)
                                $warn   .= "<p>Command '$lastCmd' returned $count</p>\n";
                            $user   = null;
                        }   // request to delete record
                        else
                        {   // request to change auth
                            $user->set('auth', $value);
                        }   // request to change auth
				    }       // have instance of User
				    break;
                }			// update to auth settings

		    }		        // act on specific parameter name
		}		            // loop through all parameters
	    if ($debug)
	        $warn               .= $parmsText . "</table>\n";
    }
	$mainParms['limit']		    = $limit;
	$mainParms['offset']	    = $offset;

	$prevoffset		            = $offset - $limit;
	$nextoffset		            = $offset + $limit;


    // construct the blind carbon copy (BCC) list for bulk mailing
	$users			    		= new RecordSet('Users', $bccParms);
   
	$bcclist		    		= '';
    $bcomma             		= '';
    $pendlist		    		= '';
    $pcomma             		= '';
	$tolist			    		= '';
    $tcomma             		= '';
	foreach($users as $id => $user)
	{		// assemble bulk mailing list
	    $email		    		= $user->get('email');
	    $auth		    		= $user->get('auth');

	    // administrators are listed in the to: attribute of the message
	    // clients are listed in the bcc: attribute of the message
	    $pos	        		= strpos($auth, 'yes');
	    // stupid return value from PHP's strpos function
	    if ($pos === false)
        {		            // contributor
            $bcclist	        .= $bcomma . urlencode($email);
            $bcomma             = ',';
            if ($auth == 'pending')
            {
                $pendlist       .= $pcomma . urlencode($email);
                $pcomma         = ',';
            }
	    }		            // contributor
	    else
        {		            // administrator
            if ($tcomma == '')
                $tolist		    .= $tcomma .
                                urlencode("Family Tree Mailing List <$email>");
            else
                $tolist         .= urlencode($email);
            $tcomma             = ',';
	    }		            // administrator
	}		                // assemble bulk mailing list

    // construct the blind carbon copy (BCC) list for users with
    // insecure passwords
    $insecureParms              = array('password' => 'length>0');
	$insecure		    		= new RecordSet('Users', $insecureParms);
   
	$inslist		    		= '';
    $bcomma             		= '';
	foreach($insecure as $id => $user)
	{		// assemble bulk mailing list
	    $email		    		= $user->get('email');

        $inslist	            .= $bcomma . urlencode($email);
        $bcomma                 = ',';
	}		                // assemble bulk mailing list

	// then query the database for matches to the request
	$users		                = new RecordSet('Users', $mainParms);
	$readonly	                = $users->count() > 10;
	$info		                = $users->getInformation();
	$count		                = $info['count'];
	$rowtype	                = 'odd';
	foreach($users as $id => $user)
	{		                // create display of a page of users
        $user['rowtype']        = $rowtype;
        if ($user['auth'] == 'pending')
            $user['disabled']   = '';
        else
            $user['disabled']   = 'disabled="disabled"';
	    if ($rowtype == 'odd')
			$rowtype	        = 'even';
	    else
			$rowtype	        = 'odd';
	}		                // create display of a page of users

	$template->set('BCCLIST',                   $bcclist);
	$template->set('INSLIST',                   $inslist);
	$template->set('PENDLIST',                  $pendlist);
	$template->set('TOLIST',                    $tolist);
	$template->set('PATTERN',                   $pattern);
	$template->set('AUTHPATTERN',               $authPattern);
	$template->set('MAILPATTERN',               $mailPattern);
	$template->set('OFFSET',                    $offset);
	$template->set('LAST',                      min($offset + $limit, $count));
	$template->set('COUNT',                     $count);

	$template->updateTag('notadmin',            null);
	if ($offset - $limit > 0)
	    $template->updateTag('topPrev',
					         array('pattern'	=> $pattern,
						       'authpattern'	=> $authPattern,
						       'mailpattern'	=> $mailPattern,
						       'prevoffset'	    => $offset - $limit,
						       'limit'		    => $limit));
	else
	    $template->updateTag('topPrev',         null);
	if ($offset + $limit < $count)
	{
	    $template->updateTag('topNext',
					         array('pattern'	=> $pattern,
						       'authpattern'	=> $authPattern,
						       'mailpattern'	=> $mailPattern,
						       'nextoffset'	    => $offset + $limit,
						       'limit'		    => $limit));
	}
	else
	    $template->updateTag('topNext',         null);

    // display matching users
    $template->updateTag('Row$id',
					     $users);
}		// only administrator can use this dialog
else
{		// not administrator
	$template->updateTag('locForm', null);
	$template->updateTag('userCount', null);
}		// not administrator

$template->display();
