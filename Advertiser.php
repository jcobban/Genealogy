<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \NumberFormatter;
/************************************************************************
 *  Advertiser.php												        *
 *																		*
 *  Display a web page containing all of the advertiser's statistics.	*
 *																		*
 *	Parameters:                                                         *
 *	    lang            preferred language of communication             *
 *																		*
 *  History:															*
 *		2020/01/17      created                                         *
 *      2020/01/22      use NumberFormatter                             *
 *      2020/01/26      improve handling of non-authorized user         *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Advertiser.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

$ademail                    = null;
$remoteName                 = null;
$tempName                   = null;
$error                      = -1;
$lang		    			= 'en';

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
			case 'lang':
			{		// lang
				$lang			    = FtTemplate::validateLang($value);
			    break;
			}		// lang

	    }		    // act on specific parameter
	}		        // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}			        // invoked by method=get
else
if (isset($_POST) && count($_POST) > 0)
{			        // invoked by method=post
    $parmsText      = "<p class='label'>\$_POST</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    $advertiser                     = null;
	foreach($_POST as $key => $value)
	{		                // loop through parameters
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$value</td></tr>\n"; 
        $fieldLc	                = strtolower($key);

	    switch($fieldLc)
	    {		            // act on specific parameter
			case 'ademail':
            {		        // email address
                $ademail            = $value;
			    break;
			}		        // email address

			case 'lang':
			{		        // lang
				$lang			        = FtTemplate::validateLang($value);
			    break;
			}		        // lang

	    }		    // act on specific parameter
	}		        // loop through parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";

    if (isset($_FILES) && count($_FILES) > 0)
    {
        $parmsText      = "<p class='label'>\$_FILES</p>\n" .
                          "<table class='summary'>\n" .
                          "<tr><th class='colhead'>key</th>" .
                              "<th class='colhead'>value</th></tr>\n";
        foreach($_FILES as $filename => $file)
        {
            foreach($file as $key => $value)
            {
                $parmsText  .= "<tr>" . 
                                "<th class='detlabel'>$filename.$key</th>" .
                                "<td class='white left'>$value</td></tr>\n";
                switch($key)
                {
                    case 'name':
                    {
                        $remoteName     = $value;
                        break;
                    }

                    case 'type':
                    {
                        break;
                    }

                    case 'tmp_name':
                    {
                        $tempName       = $value;
                        break;
                    }

                    case 'error':
                    {
                        $error          = $value;
                        break;
                    }

                    case 'size':
                    {
                        break;
                    }

                }
            }
        }
        if ($debug)
            $warn       .= $parmsText . "</table>\n";
    }
}			        // invoked by method=post

// create the Template instance
$template		    = new FtTemplate("Advertiser$lang.html");
$formatter          = $template->getFormatter();

// check if this is an Advertiser account
if (strlen($userid) > 0)
{
    $email		        = $user['email'];	    // user's email
    $advertiserParms    = array('ademail'   => $email);
    $advertisers        = new RecordSet('Advertisers', $advertiserParms);
}
if (strlen($userid) > 0 && $advertisers->count() > 0)
{                           // is an advertiser account
    $advertiser                         = $advertisers->rewind();

    $template->set('ADNAME',		    $advertiser['adname']);
    $template->set('ADEMAIL',		    $advertiser['ademail']);
    if ($ademail)
    {                       // permit advertiser to change e-mail
        $advertiser['ademail']          = $ademail;
        $advertiser->save(false);
        $user['email']                  = $ademail;
        $user->save(false);
    }                       // permit advertiser to change e-mail
    $adname                             = $advertiser['adname'];
    $adurl                              = "Advertisements/$adname.html";

    if ($error == UPLOAD_ERR_OK && 
        is_string($tempName) && 
        strlen($tempName) > 0) 
    {                       // upload new advertisement
        move_uploaded_file($tempName, "$document_root/$adurl");
        $warn           .= $template['fileUploaded']->outerHTML;
    }                       // upload new advertisement

    if (!file_exists("$document_root/$adurl"))
    {
        $contents       = file_get_contents("$document_root/Advertisements/AdForRent.html");
        $contents       = str_replace(array('This Space for Rent', 'webmaster@jamescobban.net'),
                                      array("Reserved for $adname", $ademail),
                                      $contents);
        file_put_contents("$document_root/$adurl", $contents);
        $warn           .= "<p>created $document_root/$adurl</p>\n";
    }
    else
        $contents       = file_get_contents("$document_root/$adurl");

    $adTemplate         = new \Templating\Template($contents);
    $adDoc              = $adTemplate->getDocument();
    $adBody             = $adDoc->getElementsByTagName('body');
    if (count($adBody) > 0)
    {
        $adBody         = current($adBody);
	    $template->set('ADVERTISEMENT',		$adBody->innerHTML);
    }
    else
    {
        $warn           .= $adTemplate->getDocument()->show();
        $template->set('ADVERTISEMENT',		'Not Available');
    }

	$template->set('TOTAL01',			$formatter->format($advertiser['count01']));
	$template->set('TOTAL02',			$formatter->format($advertiser['count02']));
	$template->set('TOTAL03',			$formatter->format($advertiser['count03']));
	$template->set('TOTAL04',			$formatter->format($advertiser['count04']));
	$template->set('TOTAL05',			$formatter->format($advertiser['count05']));
	$template->set('TOTAL06',			$formatter->format($advertiser['count06']));
	$template->set('TOTAL07',			$formatter->format($advertiser['count07']));
	$template->set('TOTAL08',			$formatter->format($advertiser['count08']));
	$template->set('TOTAL09',			$formatter->format($advertiser['count09']));
	$template->set('TOTAL10',			$formatter->format($advertiser['count10']));
	$template->set('TOTAL11',			$formatter->format($advertiser['count11']));
	$template->set('TOTAL12',			$formatter->format($advertiser['count12']));
}                           // is an advertiser account
else
{                           // not an advertiser account
    $advertiser     = null;
    $template->set('ADNAME',		    $userid);
    $msg            .= $template['notAnAdvertiser']->innerHTML;
    $template['locForm']->update(null);
}                           // not an advertiser account

$template->display();
