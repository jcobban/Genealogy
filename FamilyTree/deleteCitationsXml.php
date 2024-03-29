<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  deleteCitationsXml.php												*
 *																		*
 *  Clear source citations associated with a fact and return XML so		*
 *  that this script can be												*
 *  invoked by JavaScript code using AJAX.  A number of parameters can	*
 *  be passed by method='post' to the script to control the action.		*
 *																		*
 *  One of the following parameters must be passed to identify			*
 *  the database record containing the fact that is being documented	*
 *  by the citation														*
 *		idir	the IDIR value of an Individual Record					*
 *		idmr	the IDMR value of a Marriage Record						*
 *		ider	the IDER value of an Event Record						*
 *		idcr	the IDCR value of a Child Record						*
 *		idnx	the IDNX value of an Alternate Name record				*
 *  or																	*
 *		idime	generic key of a record									*
 *																		*
 *		type	specify the specific fact within the associated data	*
 *				record that is documented by this citation.  			*
 *				If omitted this is set to Citation::STYPE_MAR (20)		*
 *																		*
 *  History:															*
 *		2012/02/28		created											*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/07		remove LegacyCitationList.inc					*
 *						and use new Citation::deleteCitations			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2019/01/18      Citation::deleteCitations replaced by           *
 *		                CitationSet->delete                             *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
header("content-type: text/xml");
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");

$parms	        = '';

// set default values for parameters
$idime	        = null;		// cited record
$idimetext      = null;		// for error messages
$type	        = Citation::STYPE_MAR;
$typetext       = null;
$parmsText      = '';

// determine if permitted to update database
if (!canUser('edit'))
{		// user not authorized to update database
    $msg	    .= 'Not authorized to delete citations. ';
}		// user not authorized to update database

// validate parameters
if (isset($_POST) && count($_POST) > 0)
{		                        // parameters passed by method=post
    foreach($_POST as $key => $value)
    {		                    // loop through all parameters
        $parmsText  .= "        <$key>" .
                            htmlspecialchars($value) . "</$key>\n"; 
	    switch($key)
	    {	// act on keys
	        case 'type':
            {	// 
                if (ctype_digit($value))
                    $type	        = (int)$value;
                else
                    $typetext       = htmlspecialchars($value);
	    		break;
	        }	//
	
	        // get the key of the record containing the fact that is cited
	        case 'idime':
	        case 'idir':
	        case 'idmr':
	        case 'idcr':
	        case 'idnx':
	        case 'ider':
	        {	// record key 
                if (ctype_digit($value))
                    $idime	        = (int)$value;
                else
                    $idimetext      = htmlspecialchars($value);
	    		break;
	        }	// record key
	    }	// act on keys
	}		// look at all parameters
}		                        // parameters passed by method=post

// check for missing mandatory parameters;
if (is_string($idimetext))
    $msg	.= "Invalid record key value '$idimetext'. ";
else
if (is_null($idime))
    $msg	.= 'Missing mandatory parameter idime. ';
if (is_string($typetext))
    $warn	.= "<p>Invalid event type value '$typetext' ignored.</p>\n";

// if any errors encountered in validating parameters
// terminate the request and return the error message
if (strlen($msg) > 0)
{		// return the message text in XML
    print "<msg>$msg<parms>$parmsText</parms></msg>\n";
}		// return the message text in XML
else
{		// no errors detected
    // print the root node of the XML tree
    // and include feedback parameters as attributes
    print "<deleted idime='$idime' type='$type'>\n";

    // include all of the input parameters as debugging information
    print "  <parms>\n$parmsText</parms>\n";

    // delete the associated citations
    $citations	= new CitationSet(array('idime'	=> $idime,
                                        'type'	=> $type));
    $count      = $citations->delete('cmd');
    print "<count>$count</count>\n";

    if (strlen($warn) > 0)
        print "<warnings>$warn</warnings>\n";
    // close off top level node 
    print "</deleted>\n";
}		// no errors detected
