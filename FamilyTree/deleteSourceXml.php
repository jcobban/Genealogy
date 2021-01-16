<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteSourceXml.php													*
 *																		*
 *  Delete an existing source record from tblSR.						*
 *  This generates an XML file with response information so that it may	*
 *  be invoked using AJAX.												*
 *																		*
 *  History:															*
 *		2013/03/28		created											*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/08/28		use class Source::delete to delete record		*
 *		2014/12/25		do not delete if there are citations to this	*
 *						source											*
 *		2015/01/07		change require to require_once					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Source.inc";
require_once __NAMESPACE__ . '/common.inc';

$idsr	            = null;
$idsrtext           = null;
$parmsText          = '';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<deleted>\n";
if (isset($_POST) && count($_POST) > 0)
{		                        // parameters passed by method=post
    foreach($_POST as $key => $value)
    {		                    // loop through all parameters
        $parmsText      .= "    <$key>" .
                            htmlspecialchars($value) . "</$key>\n"; 
        switch(strtolower($key))
        {
            case 'idsr':
            {
                if (ctype_digit($value))
                    $idsr           = intval($value);
                else
                    $idsrtext       = htmlspecialchars($value);
                break;
            }
        }
    }
}

print "    <parms>\n$parmsText    </parms>\n";

// output trace if debugging
showTrace();

// determine if permitted to update database
if (!canUser('edit'))
{		// take no action
    $msg	.= 'Not authorized to delete source. ';
}		// take no action

// validate parameters
if (is_string($idsrtext))
    $msg    .= "Invalid value for IDSR='$idsrtext'. ";
else
if (is_null($idsr))
    $msg	.= 'Missing mandatory parameter idsr. ';
else
{
    $source	        = new Source(array('idsr' => $idsr));
    if ($source->isExisting())
    {
        $count	= $source->getCitations(0);
        if ($count > 0)
            $msg	.= "Source not deleted because $count citations refer to it. ";
        else
            $count	= $source->delete("cmd");
    }
    else
    {
        $msg	    .= "No existing instance of class Source with IDSR=$idsr. ";
    }
}

if (strlen($msg) > 0)
{			// errors detected in parameters
    print "<msg>$msg</msg>\n";
}			// errors detected in parameters
 
print "</deleted>\n";
