<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;
/************************************************************************
 *  editName.php														*
 *																		*
 *  Display a web page for editting one alternate name for an			*
 *  individual from the Legacy database which is represented			*
 *  by an instance of Name (a record in table tblNX).					*
 *																		*
 *  Parameters (passed by method="get"):								*
 *      $idir   unique numeric key of the instance of Person            *
 *		idnx	unique numeric key of instance of Name		            *
 *		form	name of the form in the invoking page					*
 * 																		*
 *  History: 															*
 *		2014/04/08		created											*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/10/01		add delete confirmation dialog					*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2014/03/09		Citation::getTitle is removed					*
 *		2015/05/18		do not escape textarea value.  HTML tags		*
 *						are used by the rich-text editor.				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/02/06		use showTrace									*
 *		2016/11/25		notes field needs to be named 'akanote'			*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2018/11/19      change Help.html to Helpen.html                 *
 *		2019/08/06      use FtTemplate                                  *
 *		2019/08/13      given name was not initialized                  *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Name.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// get parameter value defaults
// parameter values from URI
$idnx		        = null;	        // index of Name
$idnxText           = 'missing';    
$formname           = '';           // JavaScript feedback
$idir		        = null;	        // index of Person
$person		        = null;	        // instance of Person
$name		        = null;	        // instance of Name
$givenname          = null;
$surname            = null;
$treename           = null;
$nameuri		    = '';
$lang               = 'en';         // preferred language

// process input parameters 
if (isset($_GET) && count($_GET) > 0)
{                               // invoked by method=get
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
		    case 'idnx':
	        {	                // get the key of instance of Name
	            if (ctype_digit($value) & $value > 0)
	                $idnx	    = intval($value);
	            else
	                $idnxText       = $value;
				break;
		    }		            //idnx
	
		    case 'form':
	        {
	            if (strlen($value) > 0)
				    $formname	    = $value;
				break;
            }		            // form name

            case 'given':
            case 'givenname':
	        {
	            if (strlen($value) > 0)
				    $givenname	    = $value;
				break;
            }		            // given name

            case 'surname':
	        {
	            if (strlen($value) > 0)
				    $surname	    = $value;
				break;
            }		            // surname

            case 'treename':
	        {
	            if (strlen($value) > 0)
				    $treename	    = $value;
				break;
            }		            // tree name

			case 'lang':
            {
                $lang           = FtTemplate::validateLang($value);
                break;
            }
		}	                    // switch
	}		                    // loop through all parameters
    if ($debug)
        $warn                   .= $parmsText . "</table>\n";
}                               // invoked by method=get

if ($user->can('edit'))
    $action                     = 'Update';
else
    $action                     = 'Display';

$template                       = new FtTemplate("editName$action$lang.html",
                                                 true);
$types                          = $template['types'];
$template['otherStylesheets']->update(array('filename'  => 'editName'));

if (is_int($idnx))
{
	$template->set('IDNX',		                $idnx);
    $name		                = new Name(array('idnx' => $idnx));
    if ($name->isExisting())
    {
        $idir		    		= $name['idir'];
        if ($givenname)
            $name['givenname']  = $givenname;
        if ($surname)
            $name['surname']    = $surname;
        if ($treename)
            $name['treename']   = $treename;
        $template->set('IDIR',		            $idir);
        $template->set('PERSONNAME',$name->getName(Name::NAME_INCLUDE_DATES));
	    $surname				= $name['surname'];
		$template->set('SURNAME',		        $surname);
	    $soundslike				= $name['soundslike'];
		$template->set('SOUNDSLIKE',	        $soundslike);
	    $givenname				= $name['givenname'];
		$template->set('GIVENNAME',		        $givenname);
	    $prefix		    		= $name['prefix'];
		$template->set('PREFIX',		        $prefix);
	    $nametitle				= $name['title'];
		$template->set('NAMETITLE',		        $nametitle);
	    $userref				= $name['userref'];
		$template->set('USERREF',		        $userref);
	    $order		    		= $name['order'];
        $template->set('ORDER',		            $order);
        if ($order < 2)
            $template->set('TYPE',              $types[$order]);
        else
            $template->set('TYPE',              $types[1]);
	    $marriednamecreatedby	= $name['marriednamecreatedby'];
		$template->set('MARRIEDNAMECREATEDBY',	$marriednamecreatedby);
	    $preferredaka			= $name['preferredaka'];
		$template->set('PREFERREDAKA',		    $preferredaka);
	    if ($preferredaka == 0)
			$preferredakachecked= '';
	    else
			$preferredakachecked= 'checked="checked"';
		$template->set('PREFERREDAKACHECKED',	$preferredakachecked);
	    $notes		            = $name['akanote'];
		$template->set('NOTES',		            $notes);
	    $marriednamemaridid	    = $name['marriednamemaridid'];
        $template->set('MARRIEDNAMEMARIDID',	$marriednamemaridid);

        // set up parameters for nominal index link in page header
	    if (strlen($givenname) > 2)
			$givenPre	= substr($givenname, 0, 2);
	    else
	    if (strlen($givenname) == 0)
			$givenPre	= 'A';
	    else
			$givenPre	= $givenName;
	    $nameuri		= rawurlencode($surname . ', ' . $givenPre);
		$template->set('NAMEURI',		        $nameuri);

        // display association citations
        if ($order == Name::PRIMARY)
        {                   // move any citations to name in Person
	        $citParms	        = array('idime'	=> $idir,
		        		        		'type'	=> Citation::STYPE_NAME);
            $citations	        = new CitationSet($citParms);
            if ($citations->count() > 0)
                $citations->update(array('idime'=> $idnx,
		    				             'type'	=> Citation::STYPE_ALTNAME));
        }                   // move any citations to name in Person
	    $citParms	= array('idime'	=> $idnx,
		    				'type'	=> Citation::STYPE_ALTNAME);
        $citations	= new CitationSet($citParms);
        $row            = $template['sourceRow$IDSX'];
        $rowHtml        = $row->outerHTML;
        $data           = '';
	    foreach($citations as $idsx => $cit)
        {		        // loop through all citations to this fact
            $rtemplate  = new Template($rowHtml);
            $rtemplate['sourceRow$IDSX']->update($cit);
            $data       .= $rtemplate->compile();
        }		        // loop through citations
        $row->update($data);
    }
    else
    {
        $text       = $template['invalididnx']->innerHTML;
        $msg        .= str_replace('$IDNX', $idnx, $text);
		$template->set('TYPE',		            $types['invalid']);
        $template->set('PERSONNAME',		    $idnx);
        $template['personLink']->update(null);
        $template['nameForm']->update(null);
    }
}
else
{
    $text           = $template['invalididnx']->innerHTML;
    $msg            .= str_replace('$IDNX', $idnxText, $text);
	$template->set('TYPE',		                $types['invalid']);
    $template->set('PERSONNAME',		        $types['missing']);
    $template['personLink']->update(null);
    $template['nameForm']->update(null);
}

$template->display();
