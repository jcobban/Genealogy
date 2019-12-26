<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Advertisers.php												        *
 *																		*
 *  Display a web page containing all of the advertisers statistics.	*
 *																		*
 *	Parameters:                                                         *
 *	    offset          starting offset in results set                  *
 *	    limit           maximum number of rows to display               *
 *	    name            pattern to limit by adname                      *
 *	    lang            preferred language of communication             *
 *																		*
 *  History:															*
 *		2019/12/20		created											*
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Advertiser.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/UserSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$lang		    			= 'en';
$offset			    		= 0;
$limit			    		= 20;
$id				    		= '';
$adname             		= '^.+';    // at least one character
$mainParms					= array();
$administrator      		= canUser('all');

if (isset($_GET) && count($_GET) > 0)
{			        // invoked by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{		        // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    $fieldLc	= strtolower($key);
	    switch($fieldLc)
	    {		    // act on specific parameter
			case 'name':
			case 'adname':
            {		// lang
				$adname             = $value;
			    break;
			}		// lang

			case 'lang':
			{		// lang
				$lang			    = FtTemplate::validateLang($value);
			    break;
			}		// lang

			case 'offset':
			{
			    $offset			    = (int)$value;
			    break;
			}

			case 'limit':
			{
			    $limit			    = (int)$value;
			    break;
			}
	    }		    // act on specific parameter
	}		        // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
	if ($administrator)
	{		        // only the administrator can use this dialog
	    // create the main counter entry if it does not already exist
	    $advertiser                 = new Advertiser(array('adname' => ''));
	    $advertiser->save(false);
	
	    // synchronize the statistics with the Advertisements folder
		$dh			                = opendir("$document_root/Advertisements");
		if ($dh)
		{			        // found advertisements directory
			while (($filename = readdir($dh)) !== false)
			{		        // loop through files
			    if (strlen($filename) > 5 &&
				    $filename != "index.html" &&
	                substr($filename, strlen($filename) - 5) == '.html')
	            {           // advertiser banner ad
	                $adname         = substr($filename, 0, strlen($filename) - 5);
	                $advertiser     = new Advertiser(array('adname' => $adname));
	                $advertiser->save(false);
	            }           // advertiser banner ad
			}		        // loop through files
		}			        // found advertisements directory
	}		        // only the administrator can use this dialog
}			        // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{			        // invoked by method=post
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
	foreach($_POST as $key => $value)
	{		                // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
        $fieldLc	                = strtolower($key);
        if (preg_match('/^(\w\+)(\d*)$/', $fieldLc, $matches))
        {
            $col                    = $matches[1];
            $row                    = $matches[2];
        }
        else
        {
            $col                    = $fieldLc;
            $row                    = '';
        }
	    switch($col)
	    {		            // act on specific parameter
			case 'name':
			case 'adname':
            {		        // advertiser name
				$adname             = $value;
			    break;
			}		        // advertiser name

			case 'email':
			case 'ademail':
            {		        // email address
                if ($administrator)
                {
                    $advertiser     = new Advertiser(array('adname' => $adname));
                    $advertiser['ademail']  = $value;
                    $advertiser->save(false);
                }
			    break;
			}		        // email address

			case 'lang':
			{		        // lang
				$lang			    = FtTemplate::validateLang($value);
			    break;
			}		        // lang

			case 'offset':
			{
			    $offset			    = (int)$value;
			    break;
			}

			case 'limit':
			{
			    $limit			    = (int)$value;
			    break;
			}
	    }		    // act on specific parameter
	}		        // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			        // invoked by method=post

// create the Template instance
$template		= new FtTemplate("Advertisers$lang.html");

// if not the administrator do nothing
if ($administrator)
{		// only the administrator can use this dialog
	$mainParms['limit']			= $limit;
	$mainParms['offset']		= $offset;
    $mainParms['adname']		= $adname;

	$prevoffset		        	= $offset - $limit;
	$nextoffset		        	= $offset + $limit;

    // display matching advertisers
	$advertisers 		    	= new RecordSet('Advertisers', $mainParms);
	$info		            	= $advertisers->getInformation();
    $count		            	= $info['count'];
    $bcclist                	= '';
    $comma                  	= '';
    foreach($advertisers as $advertiser)
    {
        $email              	= $advertiser['ademail'];
        if (strlen($email) > 0)
        {
            $bcclist            .= $comma . urlencode($email);
            $comma          	= ',';
        }
    }

    $tolist                 	= '';
    $tcomma                 	= '';
    $admins                 	= new UserSet(array('auth'  => 'yes'));
    foreach($admins as $admin)
    {
	    $email		    		= $admin->get('email');
        if ($tcomma == '')
            $tolist		        .= $tcomma .
                                urlencode("Family Tree Mailing List <$email>");
        else
            $tolist             .= urlencode($email);
        $tcomma             	= ',';
    }
	$template->set('TOLIST',                    $tolist);
	$template->set('BCCLIST',                   $bcclist);
	$template->set('OFFSET',                    $offset);
	$template->set('FIRSTOFFSET',               $offset + 1);
	$template->set('LAST',                      min($offset + $limit, $count));
	$template->set('COUNT',                     $count);

	$template->updateTag('notadmin',            null);
	if ($offset - $limit > 0)
	    $template->updateTag('topPrev',
					         array('prevoffset'	    => $offset - $limit,
						           'limit'		    => $limit));
	else
	    $template->updateTag('topPrev',         null);
	if ($offset + $limit < $count)
	{
	    $template->updateTag('topNext',
					         array('nextoffset'	    => $offset + $limit,
						           'limit'		    => $limit));
	}
	else
	    $template->updateTag('topNext',         null);

    $rowTag                 = $template['Row$id'];
    $rowText                = $rowTag->outerHTML;
    $data                   = '';
    $rowtype	            = 'odd';
    $id                     = 1;

	foreach($advertisers as $adname => $advertiser)
    {		                // create display of a page of advertisers
        $rtemplate          = new \Templating\Template($rowText);
        $rtemplate->set('adname',		$advertiser['adname']);
        $rtemplate->set('ademail',		$advertiser['ademail']);
        $rtemplate->set('rowtype',		$rowtype);
        $rtemplate->set('id',		    $id);
        $rtemplate->set('count01',		number_format($advertiser['count01']));
        $rtemplate->set('count02',		number_format($advertiser['count02']));
        $rtemplate->set('count03',		number_format($advertiser['count03']));
        $rtemplate->set('count04',		number_format($advertiser['count04']));
        $rtemplate->set('count05',		number_format($advertiser['count05']));
        $rtemplate->set('count06',		number_format($advertiser['count06']));
        $rtemplate->set('count07',		number_format($advertiser['count07']));
        $rtemplate->set('count08',		number_format($advertiser['count08']));
        $rtemplate->set('count09',		number_format($advertiser['count09']));
        $rtemplate->set('count10',		number_format($advertiser['count10']));
        $rtemplate->set('count11',		number_format($advertiser['count11']));
        $rtemplate->set('count12',		number_format($advertiser['count12']));
        $data               .= $rtemplate->compile();
	    if ($rowtype == 'odd')
			$rowtype	        = 'even';
	    else
            $rowtype	        = 'odd';
        $id++;
    }		                // create display of a page of advertisers
    $rowTag->update($data);
}		// only administrator can use this dialog
else
{		// not administrator
	$template->updateTag('locForm', null);
	$template->updateTag('advertisersCount', null);
}		// not administrator

$template->display();
