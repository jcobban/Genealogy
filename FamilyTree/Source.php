<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Source.php															*
 *																		*
 *  Display a web page containing details of an particular source		*
 *  from the Legacy database.											*
 *																		*
 *  Parameters:															*
 *		idsr	unique numeric identifier of instance of Source			*
 *		id		synonym for idsr for backwards compatibility			*
 *		name	locate source by name									*
 *																		*
 *  History:															*
 *		2010/08/21		Reformat to new format							*
 *						Add parameter checking							*
 *						Use idsr as main parameter name					*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/08		add link to help page							*
 *		2012/01/13		change class names								*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2012/08/12		use standard HtmlHeader							*
 *						include javascript library						*
 *						add button to edit source						*
 *		2013/05/16		layout information in clearer way in columns	*
 *						display more information about the source		*
 *						use standard page header and footer functions	*
 *		2013/06/10		display date created							*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/30		improved parameter validation					*
 *						display error message in red					*
 *						handle undefined value of IDSR					*
 *						Source::getPubl renamed to getPublisher			*
 *		2014/03/12		use CSS rather than tables for layout			*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/10/07		display any associated media files				*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						recognize https and ftp URL protocols			*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/09/12		use get( and set(								*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2018/12/02      use class Template                              *
 *		                exploit internationalization of Source          *
 *		2019/02/19      use new FtTemplate constructor                  *
 *		2019/09/26      use Address::get in place of obsolete special   *
 *		                get methods                                     *
 *		2020/03/13      use FtTemplate::validateLang                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *		Open Code														*
 *																		*
 *  Validate parameters													*
 ************************************************************************/

$idsr		            = null;
$source		            = null;
$name		            = 'not specified';
$lang                   = 'en';

// get the parameters
if (count($_GET) > 0)
{	        	    // invoked by URL 
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
	foreach($_GET as $key => $value)
	{
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
		switch(strtolower($key))
		{		// act on specific parameters
		    case 'id':
		    case 'idsr':
		    {		// numeric identifier of the requested source
				$idsr		= $value;
				break;
		    }		// numeric identifier of the requested source

		    case 'name':
		    {		// search by name
				$name		= $value;
				break;
		    }		// search by name

            case 'lang':
            {
                $lang       = FtTemplate::validateLang($value);
				break;
            }

		    case 'debug':
		    {		// debug handled by common code
				break;
		    }		// debug handled by common code
		}		// act on specific parameters
    }			// loop through all parameters
    if ($debug)
        $warn       .= $parmsText . "</table>\n";
}	        	    // invoked by URL to display current status of account

// create instance of Template
$template		    = new FtTemplate("Source$lang.html", true);

$template['otherStylesheets']->update(array('filename' => 'Source'));
// validate parameters
if (!is_null($idsr))
{                   // IDSR specified
	if (preg_match('/^\d+$/', $idsr) == 1)
	{	// valid identifier
		$source		= new Source(array('idsr'       => $idsr,
                                       'template'   => $template));
		$name		= $source->getName();
	}	// valid identifier
	else
        $msg	.= "Invalid value for idsr='$value'. ";
}                   // IDSR specified

if (!is_null($name))
{                   // Name specified
	if (canUser('edit'))
	{		        // only authorized contributors
        $source		= new Source(array('srcname'    => $name,
                                       'template'   => $template));
	    if (!$source->isExisting())
	    {
    		$warn		.= "<p>New Source '$name' created.</p>\n";
			$source->set('srctitle', $name);
	        $source->save(false);
	    }
	    $idsr		= $source->getIdsr();
	}		        // only authorized contributors
	else
	    $msg	.= "Only signed on contributors may use the name parameter since it can cause a new source to be created. ";
}                   // Name specified

$template->set('IDSR',              $idsr);
$srcname                            = $source->get('srcname');
$srctitle                           = $source->get('srctitle');
$idst                               = $source->get('idst');
$typetext                           = $source->getTypeText();
$srcauthor                          = $source->get('srcauthor');
$srcpubl                            = $source->get('srcpubl');
$srctext                            = $source->get('srctext');
$srcnote                            = $source->get('srcnote');
$srccallnum                         = $source->get('srccallnum');
$idar                               = $source->get('idar');
$enteredd                           = $source->get('enteredd');
$enteredd                           = (new LegacyDate($enteredd))->toString();
$filingref                          = $source->get('filingref');

$template->set('NAME',		        $srcname);
$template->set('SRCNAME',		    $srcname);
$template->set('SRCTITLE',		    $srctitle);
$template->set('IDST',		        $idst);
$template->set('SRCAUTHOR',		    $srcauthor);
$template->set('SRCPUBL',		    $srcpubl);
$template->set('SRCTEXT',		    $srctext);
$template->set('SRCNOTE',		    $srcnote);
$template->set('SRCCALLNUM',		$srccallnum);
$template->set('IDAR',		        $idar);
$template->set('ENTEREDD',		    $enteredd);
$template->set('FILINGREF',		    $filingref);
$template->set('TYPETEXT',		    $typetext);

if (strlen($srccallnum) > 0)
{		                // CallNum present in record
	$pres	                        = preg_match('/^(\w+):/', $srccallnum, $parts);
	if ($pres == 1)
	{
	    $protocol	                = strtolower($parts[1]);
	    if ($protocol == 'http' ||
	        $protocol == 'https' ||
	        $protocol == 'ftp')
	    {		        // CallNum contains supported protocol
        }		        // CallNum contains supported protocol
        else
        {               // unsupported protocol
            $template['callNum']->update($srccallnum);
        }               // unsupported protocol
	}		            // CallNum matches URL
	else
	{		            // CallNum is plain text
        $template['callNum']->update($srccallnum);
	}		            // CallNum is plain text
}		                // CallNum present in record

// check for repository
$repository	            = $source->getRepository();
if ($repository)
{		// repository reference present in record
	$repoName		    = $repository['name'];
	$repoAddress1		= $repository['address1'];
	$repoAddress2		= $repository['address2'];
	$repoCity		    = $repository['city'];
	$repoState		    = $repository['state'];
	$repoPostalCode		= $repository['postalcode'];
    $repoCountry		= $repository['country'];

	$template->set('REPONAME',		    $repoName);
	$template->set('REPOADDRESS1',		$repoAddress1);
	$template->set('REPOADDRESS2',		$repoAddress2);
	$template->set('REPOCITY',		    $repoCity);
	$template->set('REPOSTATE',		    $repoState);
	$template->set('REPOPOSTALCODE',	$repoPostalCode);
    $template->set('REPOCOUNTRY',		$repoCountry);

	if (strlen($repoName) == 0)
	    $template['repoName']->update(null);
	if (strlen($repoAddress1) == 0)
	    $template['repoAddress1']->update(null);
	if (strlen($repoAddress2) == 0)
	    $template['repoAddress2']->update(null);
	if (strlen($repoCity) == 0)
	    $template['repoCity']->update(null);
	if (strlen($repoState) == 0)
	    $template['repoState']->update(null);
	if (strlen($repoPostalCode) == 0)
	    $template['repoPostalCode']->update(null);
	if (strlen($repoCountry) == 0)
        $template['repoCountry']->update(null);

    $homePage	                    = $repository['homepage'];
    $template->set('HOMEPAGE',		$homePage);
	if (strlen($homePage) > 0)
    {
		$pres	                = preg_match('/^(\w+):/', $homePage, $parts);
		if ($pres == 1)
		{
		    $protocol	        = strtolower($parts[1]);
		    if ($protocol == 'http' ||
		        $protocol == 'https' ||
		        $protocol == 'ftp')
		    {		        // CallNum contains supported protocol
	        }		        // CallNum contains supported protocol
	        else
	        {               // unsupported protocol
	            $template['repoHomePageLink']->update($srccallnum);
	        }               // unsupported protocol
		}		            // CallNum matches URL
		else
		{		            // CallNum is plain text
	        $template['repoHomePageLink']->update($srccallnum);
		}		            // CallNum is plain text

    }
    else
    {
        $template['homePageRow']->update(null);
    }
    $notes	                = $repository->getNotes();
    $template->set('REPONOTES',     $notes);
	if (strlen($notes) > 0)
    {		//  Notes
	    if (substr($notes,0,5) == 'http:' ||
			substr($notes,0,6) == 'https:')
	    {		// a URL
	    }		// a URL
	    else
	    {		// simple text
	        $template['repoNotesLink']->update($notes);
	    }		// simple text
    }
    else
        $template['repoNotesRow']->update(null);
}		// repository reference present in record
else
    $template['repositoryRow']->update('null');
// display any media files associated with the source
//$source->displayPictures(Picture::IDTYPESrcMaster);

if (!canUser('edit'))
{		// hide button to edit the source
    $template['editButtonRow']->update(null);
}		// hide button to edit the source

$template->display();
