<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DisplayImage.php													*
 *																		*
 *  Display an image with controls for moving backward and forward		*
 *  through a folder of images and controlling zoom.					*
 *																		*
 *  Parameters:															*
 *		src				URL of image									*
 *																		*
 *  History:															*
 *		2015/04/19		created											*
 *		2015/05/01		expanded filename image match to support all	*
 *						census filename layouts							*
 *		2015/09/09		permit invoking dialog to identify fieldname to	*
 *						update when image URL changes					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2018/02/09		use Template									*
 *		2018/10/15      get language apology text from Languages        *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . "/Template.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/common.inc";

    // validate parameters
    $src	= '';
    $lang	= 'en';
    $fldName	= 'Image';

    foreach($_GET as $key => $value)
    {			// loop through all input parameters
		switch(strtolower($key))
		{		// process specific named parameters
		    case 'src':
		    {
				$src		= $value;
				// separate protocol from file name
				$result		= preg_match("#^([a-z]+:|)([0-9a-zA-Z_\-/:. ]+)$#",
								     $src,
								     $matches);
				if ($result == 1)
				{		// pattern matched
				    $protocol	= $matches[1];
				    $imageName	= $matches[2];
				}		// pattern matched
				else
				{		// invalid URL
				    $msg	.= "Src '$src' is not a valid URL. ";
				}		// invalue URL
				break;
		    }		// URL of image

		    case 'fldname':
		    {		// field name to update in invoking dialog
				$fldName	= $value;
				break;
		    }		// field name to update in invoking dialog

		    case 'lang':
		    {		// field name to update in invoking dialog
				if (strlen($value) >= 2)
				    $lang	= strtolower(substr($value,0,2));
				break;
		    }		// field name to update in invoking dialog
		}		// process specific named parameters
    }			// loop through all input parameters

    if ($src == '')
    {
		$msg		.= "URL of image omitted. ";
    }

    if (strlen($msg) == 0)
    {			// no errors
		$title		= "Display Image '$src'";

		$previmg	= null;
		$nextimg	= null;
		$images		= array();

		$lastsep	= strrpos($imageName, '/');
		if ($lastsep === false)
		    $dirname	= '.';
		else
		{
		    $dirname	= substr($imageName, 0, $lastsep);
		    $imageName	= substr($imageName, $lastsep + 1);
		}

		if ($protocol == '' || $protocol == 'file:')
		{			// protocol supported by opendir
            // open the image directory
		    $dh		= opendir("$document_root/$dirname");
		    if ($dh)
		    {			// found images directory
				while (($filename = readdir($dh)) !== false)
				{		// loop through files
				    $lastdot	= strrpos($filename, '.');
				    if ($lastdot === false)
						$filetype	= '';
				    else
						$filetype	= strtolower(substr($filename, $lastdot + 1));
				    if ($filetype == 'jpg' || $filetype == 'jpeg' ||
						$filetype == 'gif' || $filetype == 'png')
						$images[]	= $filename;
				}		// loop through files
				sort($images);
		    }			// found images directory
		    else
				$warn	.= "<p>unable to open directory $dirname</p>\n";

		    for ($i = 0; $i < count($images); $i++)
		    {			// loop through images in order
				$filename	= $images[$i];
				if ($filename > $imageName)
				{		// image name not found
				    $warn	.= "<p>Requested image not found.</p>\n";
				    $nextimg	= $filename;
				    break;
				}		// image name not found
				else
				if ($filename == $imageName)
				{		// found the file we are looking for
				    if ($i < count($images) - 1)
						$nextimg	= $images[$i + 1];
				    break;
				}		// found the file we are looking for
				$previmg	= $filename;
		    }			// loop through images in order
		}			// protocol supported by opendir
		else
		if ($protocol == "http:" || $protocol == "https:")
		{			// assume prev by decrement, next by increment
		    $dirname	= $protocol . $dirname;
		    $result	= preg_match("#([a-zA-Z]*|\d+_\d+-|\d+_)(\d+)([a-zA-Z]*\.\w+)#",
							     $imageName,
							     $matches);
		    if ($result === 1)
		    {			// successful match
				$prefix		= $matches[1];
				$seqnum		= $matches[2];
				$suffix		= $matches[3];
				$lnum		= strlen($seqnum);
				$previmg	= $seqnum - 1;
				$previmg	= $prefix .
							  substr('0000000000', 0,
								 $lnum - strlen($previmg)) .
							  $previmg . $suffix;
				$nextimg	= $seqnum + 1;
				$nextimg	= $prefix .
							  substr('0000000000', 0,
								 $lnum - strlen($nextimg)) .
							  $nextimg . $suffix;
		    }			// successful match
		}			// assume prev by decrement, next by increment
    }		// no errors
    else
    {		// errors detected
		$title		= "Display Image Error";
    }		// errors detected

    $tempBase		= $document_root . '/templates/';
    $template		= new FtTemplate("${tempBase}dialog$lang.html");
    $includeSub		= "DisplayImage$lang.html";
    if (!file_exists($tempBase . $includeSub))
    {
		$language	= new Language(array('code' => $lang));
		$langName	= $language->get('name');
		$nativeName	= $language->get('nativename');
		$sorry  	= $language->getSorry();
    $warn   	.= str_replace(array('$langName','$nativeName'),
                               array($langName, $nativeName),
                               $sorry);
		$includeSub	= 'DisplayImageen.html';
    }
    $template->includeSub($tempBase . $includeSub,
						  'MAIN');
    $template->set('TITLE',		$title);
    $template->set('LANG',		$lang);
    $template->set('IMAGENAME',		$imageName);
    $template->set('SRC',		$src);

    if (strlen($msg) == 0 && strlen($warn) == 0)
		$template->updateTag('head2', null);	// don't show <h2>

    // update the forward and backword scroll pointers
    if ($previmg)
		$template->updateTag('goToPrevImg',
						     array('dirname'	=> $dirname,
							   'previmg'	=> $previmg,
							   'fldName'	=> $fldName,
							   'lang'	=> $lang));
    else
		$template->updateTag('goToPrevImg', null);
    if ($nextimg)
		$template->updateTag('goToNextImg',
						     array('dirname'	=> $dirname,
							   'nextimg'	=> $nextimg,
							   'fldName'	=> $fldName,
							   'lang'	=> $lang));
    else
		$template->updateTag('goToNextImg', null);

    $template->display();

