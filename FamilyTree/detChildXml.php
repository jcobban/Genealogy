<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  detChildXml.php														*
 *																		*
 *  Detach a specific individual as a child from a specific family.		*
 *																		*
 *  Parameters:															*
 *		idcr			unique key of child record in tblCR				*
 *		idir			if specified validates that deleted child		*
 *						record matches this IDIR						*
 *		idmr			if specified validates that deleted child		*
 *						record matches this IDMR						*
 *																		*
 *  History:															*
 *		2010/08/27		created											*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/11/11		do not invoke RecOwners::chkOwner without $idir	*
 *		2010/12/21		handle exception from new LegacyFamily			*
 *		2012/05/29		change parameter to IDCR						*
 *		2013/03/02		use LegacyChild delete method rather than SQL	*
 *						to delete record								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/12/22		additional parameter validation					*
 *						dump child record before deleting				*
 *						include command used to delete record			*
 *						print warning messages if present				*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/08/08		class LegacyChild renamed to class Child		*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *      2020/12/05      correct XSS vulnerabilities                     *
 *                      improve error handling                          *
 *																		*
 *  Copyright 2020 &copy; James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Person.inc";
require_once __NAMESPACE__ . "/Child.inc";
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<detached>";

$child  	= null;
$idcr   	= null;
$idir   	= null;
$idmr   	= null;
$childtext	= null;
$idcrtext	= null;
$idirtext	= null;
$idmrtext	= null;
$parmsText  = '';

// examine parameters
if (isset($_GET) && count($_GET) > 0)
{		                        // parameters passed by method=post
    foreach($_GET as $key => $value)
    {		                    // loop through all parameters
        $parmsText      .= "        <$key>" .
                            htmlspecialchars($value) . "</$key>\n"; 
		switch(strtolower($key))
        {
    		case 'idcr':
            {
                if (ctype_digit($value))
    		        $idcr	        = $value;
                else
                    $idcrtext       = htmlspecialchars($value);
    		    break;
    		}
    
    		case 'idir':
    		{
                if (ctype_digit($value))
    		        $idir	        = $value;
                else
                    $idirtext       = htmlspecialchars($value);
    		    break;
    		}
    
    		case 'idmr':
    		{
                if (ctype_digit($value))
    		        $idmr	        = $value;
                else
                    $idmrtext       = htmlspecialchars($value);
    		    break;
    		}
    
        }
    }
    $parmsText          .= "</table>\n";
}
print "    <parms>\n$parmsText    </parms>\n";

// validate parameters
if (is_string($idcrtext))
    $msg	        .= "Invalid value of IDCR=$idcrtext. ";
else
if ($idcr == null)
    $msg	        .= 'Missing mandatory parameter idcr. ';

if (is_string($idirtext))
    $msg	        .= "Invalid value of IDIR=$idirtext. ";

if (is_string($idmrtext))
    $msg	        .= "Invalid value of IDMR=$idmrtext. ";

if (!canUser('edit'))
{		// not authorized
    $msg	.= 'User not authorized to update database. ';
}		// not authorized

if (strlen($msg) == 0)
{		// no errors so far
    $child	        = new Child(array('idcr' => $idcr));
    if ($child->isExisting())
    {
        $cidir      = $child['idir'];
        $cidmr      = $child['idmr'];
        if (!is_null($idir) && $idir != $cidir)
    		$msg	.= "IDIR $cidir in Child record does not match explicit IDIR=$idir. ";
        if (!is_null($idmr) && $idmr != $cidmr)
    		$msg	.= "IDMR $cidmr in Child record does not match explicit IDMR=$idmr. ";
        $idir	    = $cidir;
        $person	    = new Person(array('idir' => $idir));

        // determine if permitted to detach child
        if (!$person->isOwner())
    		$msg	.= 'User is not an owner of individual ' . $idir . '. ';
    }
    else
    {
        $msg	.= "There is no existing Child record with IDCR=$idcr. ";
    }
}		// no errors so far

if (strlen($msg) > 0)
{
    print "    <msg>$msg</msg>\n";
}
else
{		// proceed
    $child->toXml("child");
    $child->delete("cmd");
}		// proceed
print "</detached>\n";
