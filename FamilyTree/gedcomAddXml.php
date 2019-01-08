<?php
namespace Genealogy;
use \PDO;
use \Exception;
namespace Genealogy;
/************************************************************************
 *  gedcomAddXml.php											    	*
 *																		*
 *  Handle a request to update the database from a GEDCOM 5.5           *
 *  level 0 tag and its children.                                       *
 *  the Legacy family tree database.									*
 *																		*
 *  The following parameters must be passed using the POST method.		*
 *																		*
 *		userid	    requesting userid                                   *
 *		gedname		name of the GEDCOM file                             *
 *		line	    array of strings containing lines of tag            *
 *																		*
 *  History:															*
 *		2018/11/29      created                                         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Genealogy/GedCom/Document.inc';
require_once __NAMESPACE__ . '/Genealogy/common.inc';

// emit the XML header
header("Content-Type: text/xml");
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<tag>\n";

// initialize defaults
$logmsg	        = "gedcomAddXml.php?";
$and	        = '';
$username       = $userid;
$gedname        = "gedcom";
$lines          = array();

// if there were any errors detected, report them and terminate
// user must be authorized to edit the database
if (!canUser('edit'))
{  		            // the user not authorized
	$msg	.= 'User not authorized to update the database. ';
}  		            // the user not authorized


print "  <parms>\n";
foreach($_POST as $key => $value)
{		            // loop through parameters
    switch(strtolower($key))
    {               // switch on parameter name
        case 'userid':
        {
            print "    <$key>" . htmlspecialchars($value) . "</$key>\n";
            $username           = $value;
            break;
        }

        case 'gedname':
        {
            print "    <$key>" . htmlspecialchars($value) . "</$key>\n";
            $gedname            = $value;
            break;
        }

        case 'line':
        case 'lines':
        {
            if (is_string($value))
                $lines              = json_decode($value);
            else
                $lines              = $value;
            foreach($lines as $line)
                print "    <line>" . htmlspecialchars($line) . "</line>\n";
            break;
        }

        default:
        {
            if (is_string($value))
                print "    <$key>" . htmlspecialchars($value) . "</$key>\n";
            else
                print "    <$key>" . print_r($value, true) . "</$key>\n";
            break;
        }
    }               // switch on parameter name
}		            // loop through parameters
print "  </parms>\n";		// close off tag

$document       = new GedCom\Document($gedname, $lines);

if (strlen($msg) > 0)
{		            // messages to display
	print "<msg>$msg</msg>\n";
	print "</marriage>\n";
	exit;
}		            // messages to display
if (strlen($warn)> 0)
    print "    <warn>$warn</warn>\n";
	
// close off root node
print "</tag>\n";
