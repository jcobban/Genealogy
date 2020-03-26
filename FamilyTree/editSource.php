<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  editSource.php														*
 *																		*
 *  Display a web page for editing a specific source					*
 *																		*
 *  Parameters (passed by method=get):									*
 *		idsr		unique numeric key of instance of Source to edit	*
 *		form		name of form including element to update in caller	*
 *				passed when idsr == 0 to add source to selection		*
 *		select		name of <select> in caller's form to update			*
 *				passed when idsr == 0 to add source to selection		*
 *																		*
 *  History:															*
 *		2010/08/14		Created											*
 *		2010/09/25		Check error on $result, not $connection			*
 *						after query/exec								*
 *		2010/10/17		Include source record key (IDSR) in title		*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/08		add link to help page							*
 *		2011/01/10		use LegacyRecord::getField method				*
 *						improve separation of HTML and PHP				*
 *		2011/05/07		use CSS for layout								*
 *						improve separation of HTML and Javascript		*
 *						handle apostrophe's in field values				*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/30		improve parameter validation					*
 *						add class to <select> tags						*
 *						display error message in red					*
 *						handle undefined value of idsr					*
 *						use CSS for layout instead of tables			*
 *						Source::getPubl renamed to getPublisher			*
 *		2014/03/06		change name of <label> class					*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/10/05		add support for associating instances of		*
 *						Picture with a source							*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2015/02/13		provide selection list of all existing authors	*
 *						to minimize number of distinct authors			*
 *		2015/05/18		do not escape text in textarea.  HTML tags		*
 *						are used by the rich-text editor.				*
 *		2015/06/01		add close button to bypass updating				*
 *						add accessKey attribute to buttons				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/09/28		migrate from MDB2 to PDO						*
 *		2016/02/06		use showTrace									*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/09/12		use get( and set(								*
 *		2017/11/28		use RecordSet									*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2018/12/02      use class Template                              *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/04/09      use common internationalization table           *
 *		2019/11/06      do not display 0 in title when creating         *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// validate parameters
$idsr	                = null;
$name                   = null;
$source	                = null;         // instance of Source
$lang                   = 'en';

// get the parameter
if (count($_GET) > 0)
{	        	    // invoked by URL to display current status of account
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{
		    case 'idsr':
		    case 'id':
		    {
				$idsr		        = $value;
				break;
		    }		// record id
	
		    case 'name':
		    case 'srcname':
		    {
				$name		        = $value;
				break;
            }		// source name

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
				break;
            }

		    case 'debug':
		    {		// handled by common code
				break;
		    }		// handled by common code
	
		}		// switch on parameter name
	}			// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

// create instance of Template
if (canUser('edit'))
    $action                 = 'Update';
else
    $action                 = 'Display';

$template		            = new FtTemplate("editSource$action$lang.html");

$translate                  = $template->getTranslate();  //i18n

// validate parameters
if (!is_null($idsr))
{                   // IDSR specified
    if (preg_match('/^\d+$/', $idsr) == 1)
	{
        $source		        = new Source(array('idsr' => $idsr));
        if ($source->isExisting())
        {
            if (canUser('edit'))
                $action     = $template['Edit']->innerHTML();
            else
                $action     = $template['Display']->innerHTML();
        }
        else
        {
            if (canUser('edit'))
            {
                $action     = $template['Create']->innerHTML();
                $idsr       = '';
                $warn   .= "<p>\$idsr set to ''</p>\n";
            }
            else
            {
                $action     = $template['Display']->innerHTML();
	            $text       = $template['InvalidIDSR']->innerHTML();
                $msg	    .= str_replace('$idsr', $idsr, $text);
            }
        }
	}
	else
	{
	    $text               = $template['InvalidIDSR']->innerHTML();
        $msg	            .= str_replace('$idsr', $idsr, $text);
        $idsr               = '';
    }
}                   // IDSR specified
else
if (!is_null($name))
{                   // Name specified
    $source		            = new Source(array('srcname' => $name));
    if ($source->isExisting())
    {
        $idsr               = $source->get('idsr');
        $action             = $template['Edit']->innerHTML();
    }
    else
    {
        $idsr               = '';
        $action             = $template['Create']->innerHTML();
    }
}                   // Name specified
else
{                   // neither IDSR nor Name specified
    $warn                   .= "<p>" . $template['noParms']->innerHTML() .
                                "</p>\n";
    $source		            = new Source();
    $idsr                   = 'Failed';
}                   // neither IDSR nor Name specified

$stSet                      = array();
$srcTypes                   = $translate['srcTypes'];
if ($srcTypes)
{                       // build a selection list
	foreach($srcTypes as $key => $text)
	{
        $stSet[$key]	    = array('idst'      => $key,
                                    'srctype'   => $text, 
                                    'selected'  => '');
    }
}                       // build a selection list
$idst                       = $source->get('idst');
$stSet[$idst]['selected']   = 'selected="selected"';
			        
// get the requested instance of Source
if (strlen($msg) == 0)
{
    if ($idsr !== '')
	    $idsr	            = (int)$idsr;

	// get all repository address records to create selection list
	$repoSet	            = new RecordSet('tblAR',
				        	        		array('Kind'	=> 2,
					        		              'Order'	=> 'AddrName'));

	// get all names of authors as an array of strings for selection list
	$sourceSet	            = new RecordSet('tblSR');
    $authorResult	        = $sourceSet->getDistinct('SrcAuthor');
}
else
	$idsr	                = 'Failed';

$srcname                    = $source->get('srcname');
$template->set('LANG',          $lang);
if ($debug)
{
    $template->set('DEBUG',     'Y');
    $submit                 = $template['submitNormal'];
    if ($submit)
        $submit->update(null);
}
else
{
    $template->set('DEBUG',     'N');
    $submit                 = $template['submitDebug'];
    if ($submit)
        $submit->update(null);
}
$template->set('ACTION',        $action);
$template->set('IDSR',          $idsr);
$template->set('SRCNAME',       $srcname);
$template->set('IDST',          $idst);
$template['IDSTOpt$idst']->update($stSet);
$template->set('SRCTITLE',      $source->get('srctitle'));
// author name part
$author                     = $source->get('author');
$template->set('AUTHOR',        $author);
$optionElt                  = $template['author$i'];
$optionText                 = $optionElt->outerHTML();
$data                       = '';
foreach($authorResult as $i => $srcauthor)
{
    $optionTemplate         = new Template($optionText);
    $optionTemplate->set('author',          $srcauthor);
    $optionTemplate->set('i',               $i);
    if ($srcauthor == $author)
        $optionTemplate->set('selected',    'selected="selected"');
    else
        $optionTemplate->set('selected',    '');
    $data                   .= $optionTemplate->compile();
}
$optionElt->update($data);
//
$template->set('SRCPUBLISHER',      $source->get('srcpubl'));
$template->set('SRCTEXT',           htmlspecialchars($source->get('srctext')));
$template->set('SRCNOTE',           htmlspecialchars($source->get('srcnote')));
// repository part
$idar                       = $source->get('idar');
$optionElt                  = $template['IDAROption$idar'];
$optionText                 = $optionElt->outerHTML();
$data                       = '';
foreach($repoSet as $idari => $instance)
{
    $optionTemplate         = new Template($optionText);
    $optionTemplate->set('name',    htmlspecialchars($instance['addrname']));
    $optionTemplate->set('idar',    $idari);
    if ($idari == $idar)
        $optionTemplate->set('selected',    'selected="selected"');
    else
        $optionTemplate->set('selected',    '');
    $data                   .= $optionTemplate->compile();
}
$optionElt->update($data);
//
$template->set('SRCCALLNUM',        $source->get('srccallnum'));

$template->display();
