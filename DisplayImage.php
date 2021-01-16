<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DisplayImage.php                                                    *
 *                                                                      *
 *  Display an image with controls for moving backward and forward      *
 *  through a folder of images and controlling zoom.                    *
 *                                                                      *
 *  Parameters:                                                         *
 *      src             URL of image                                    *
 *      fldname         field name in opener to update if URL changed   *
 *      lang            language of communication with user             *
 *                                                                      *
 *  History:                                                            *
 *      2015/04/19      created                                         *
 *      2015/05/01      expanded filename image match to support all    *
 *                      census filename layouts                         *
 *      2015/09/09      permit invoking dialog to identify fieldname to *
 *                      update when image URL changes                   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2016/01/19      add id to debug trace                           *
 *                      include http.js before util.js                  *
 *      2018/02/09      use Template                                    *
 *      2018/10/15      get language apology text from Languages        *
 *      2020/06/03      correct prev and next image links               *
 *      2020/06/17      moved to top folder                             *
 *      2020/07/01      display credit to source                        *
 *		2021/01/03      correct XSS vulnerability                       *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . "/FtTemplate.inc";
require_once __NAMESPACE__ . "/Language.inc";
require_once __NAMESPACE__ . "/common.inc";

// validate parameters
$src                    = '';
$lang                   = 'en';
$fldName                = 'Image';

// if invoked by method=get process the parameters
if (count($_GET) > 0)
{                   // invoked by URL to display current status of account
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                                "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {           // loop through all input parameters
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                htmlspecialchars($value) . "</td></tr>\n";
        switch(strtolower($key))
        {       // process specific named parameters
            case 'src':
            {
                $src        = urldecode($value);
                break;
            }       // URL of image

            case 'fldname':
            {       // field name to update in invoking dialog
                $fldName    = $value;
                break;
            }       // field name to update in invoking dialog

            case 'lang':
            {       // field name to update in invoking dialog
                $lang       = FtTemplate::validateLang($value);
                break;
            }       // field name to update in invoking dialog
        }       // process specific named parameters
    }           // loop through all input parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}                   // invoked by URL to display current status of account

// get the template
$template           = new FtTemplate("DisplayImage$lang.html", true);
$template->updateTag('otherStylesheets',    
                     array('filename'   => '/DisplayImage'));

if ($src == '')
{
    $msg            .= "URL of image omitted. ";
}
else
{                       // separate protocol from file name
    $result         = preg_match("#^([a-z]+(:|%3A)|)([0-9a-zA-Z_\-/:. ?&=]+)$#",
                                 $src,
                                 $matches);
    if ($result == 1)
    {                   // pattern matched
        $protocol               = str_replace('%3A',':',$matches[1]);
        $imageName              = $matches[3];
    }                   // pattern matched
    else
    {                   // invalid URL
        $protocol               = 'https:';
        $imageName              = $src;
    }                   // invalue URL
}                       // separate protocol from file name

if (strlen($msg) == 0)
{           // no errors
    $previmg                    = null;
    $nextimg                    = null;
    $images                     = array();

    $lastsep                    = strrpos($imageName, '/');
    if ($lastsep === false)
        $dirname                = '.';
    else
    {
        $dirname                = substr($imageName, 0, $lastsep);
        $imageName              = substr($imageName, $lastsep + 1);
    }

    $urldirname                 = $dirname . '/';
    if ($protocol == '' || $protocol == 'file:')
    {           // protocol supported by opendir
        $credit                 = $template['creditOA']->innerHTML;
        // open the image directory
        if (substr($dirname,0,1) == '/')
        {
            if (substr($dirname, 0, 7) == '/Images')
                $dirname        = "$document_root$dirname";
            else
            {
                $dirname        = "$document_root/Images$dirname";
                $src            = '/Images' . $src;
            }
        }
        else
            $dirname            = "./$dirname";
        $dh                     = opendir($dirname);
        $dirname                = "$dirname/";
        if ($dh)
        {           // found images directory
            while (($filename = readdir($dh)) !== false)
            {       // loop through files
                $lastdot        = strrpos($filename, '.');
                if ($lastdot === false)
                    $filetype   = '';
                else
                    $filetype   = strtolower(substr($filename, $lastdot + 1));
                if ($filetype == 'jpg' || $filetype == 'jpeg' ||
                    $filetype == 'gif' || $filetype == 'png')
                    $images[]   = $filename;
            }       // loop through files
            sort($images);
        }           // found images directory
        else
        {
            $text               = $template['baddir']->innerHTML;
            $text               = str_replace('$dirname', 
                                    htmlspecialchars($dirname), $text);
            $msg                .= "$text.\n";
        }

        for ($i = 0; $i < count($images); $i++)
        {           // loop through images in order
            $filename           = $images[$i];
            if ($filename > $imageName)
            {       // image name not found
                $text           = $template['notfound']->innerHTML;
                $msg            .= "$text/ \n";
                $nextimg        = $filename;
                break;
            }       // image name not found
            else
            if ($filename == $imageName)
            {       // found the file we are looking for
                if ($i < count($images) - 1)
                    $nextimg    = $images[$i + 1];
                break;
            }       // found the file we are looking for
            $previmg            = $filename;
        }           // loop through images in order
    }               // protocol supported by opendir
    else
    if ($protocol == "http:" || $protocol == "https:")
    {               // assume prev by decrement, next by increment
        $headers                = get_headers($src);
        if ($headers && stripos($headers[0], '200 OK'))
        {           // valid URL
        $text                   = $template['creditUrl']->innerHTML;
        if (preg_match('#//([a-zA-Z0-9_.-]+)#', $dirname, $matches))
        {
            $hostname           = $matches[1];
            if ($hostname == 'data2.collectionscanada.ca' ||
                $hostname == 'central.bac-lac.gc.ca')
                $credit         = $template['creditLac']->innerHTML;
            else
                $credit         = str_replace('$hostname', $hostname, $text);
        }
        else
            $credit             = str_replace('$hostname', $dirname, $text);
        $dirname                = $protocol . $dirname;
        $result = preg_match("#([a-zA-Z]*|\d+_\d+-|\d+_)(\d+)([a-zA-Z]*\.\w+)#",
                             $imageName,
                             $matches);
        $msg   .= __LINE__ . " dirname='$dirname'";
        if ($result === 1)
        {           // successful match
            $urldirname         = $dirname . '/';
            $prefix             = $matches[1];
            $seqnum             = $matches[2];
            $suffix             = $matches[3];
            $lnum               = strlen($seqnum);
            $previmg            = $seqnum - 1;
            $previmg            = $prefix .
                                    substr('0000000000', 0,
                                           $lnum - strlen($previmg)) .
                                    $previmg . $suffix;
            $nextimg            = $seqnum + 1;
            $nextimg            = $prefix .
                                  substr('0000000000', 0,
                                         $lnum - strlen($nextimg)) .
                                  $nextimg . $suffix;
        }           // successful match
        else
        {           // not a simple file
            $result = preg_match("#(.*e)(\d+)#",
                                 $imageName,
                                 $matches);
            if ($result == 1)
            {
                $urldirname     = $dirname;
                $prefix         = urlencode($matches[1]);
                $seqnum         = $matches[2];
                $suffix         = '';
                $lnum           = strlen($seqnum);
                $previmg        = $seqnum - 1;
                $previmg        = $prefix .
                                    substr('0000000000', 0,
                                           $lnum - strlen($previmg)) .
                                    $previmg . $suffix;
                $nextimg        = $seqnum + 1;
                $nextimg        = $prefix .
                                  substr('0000000000', 0,
                                         $lnum - strlen($nextimg)) .
                                  $nextimg . $suffix;
            }
            else
                $warn   .= "<p>imageName='" . 
                            htmlspecialchars($imageName) . "'</p>\n";
        }           // not a simple file
        $msg   .= __LINE__ . " urldirname='$urldirname'";
        }           // good URL
        else
        {           // bad URL
            $text       = $template['notfoundURL']->innerHTML;
            $msg        .= str_replace('$src', $src, $text);
        }           // bad URL
    }               // assume prev by decrement, next by increment
    else
    {
        $msg        .= "DisplayImage.php: " . __LINE__ . " protocol=$protocol. ";
    }
}                   // no errors
else
{                   // errors detected
    $imageName                 = "Error";
    $credit                     = '';
    $template['imageForm']->update(null);
}                   // errors detected

$template->set('TITLE',     $title);
$template->set('LANG',      $lang);
$template->set('IMAGENAME', htmlspecialchars($imageName));
$template->set('SRC',       htmlspecialchars($src));
$template->set('CREDIT',    $credit);

if (strlen($msg) == 0 && strlen($warn) == 0)
{
    $template->updateTag('head2', null);    // don't show <h2>
}

// update the forward and backword scroll pointers
if ($previmg)
    $template->updateTag('goToPrevImg',
                         array('dirname'    => $urldirname,
                               'previmg'    => $previmg,
                               'fldName'    => $fldName,
                               'lang'       => $lang));
else
    $template->updateTag('goToPrevImg', null);
if ($nextimg)
    $template->updateTag('goToNextImg',
                         array('dirname'    => $urldirname,
                               'nextimg'    => $nextimg,
                               'fldName'    => $fldName,
                               'lang'       => $lang));
else
    $template->updateTag('goToNextImg', null);

if (strlen($msg) > 0)
{
    $template['imageForm']->update(null);
    $template['image']->update(null);
}

$template->display();

