<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  orderPicturesByDateXml.php											*
 *																		*
 *  Handle a request to reorder the picture records for an				*
 *  individual in the Legacy family tree database.  The `PicOrder`		*
 *  field in each record is updated so the pictures will display in		*
 *  order by the `PicSD` field.  This file generates an					*
 *  XML file, so it can be invoked from Javascript.						*
 *																		*
 *  Parameters:															*
 *		idir		unique numeric key of the individual				*
 *																		*
 *  History:															*
 *		2014/03/21		created											*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/17		use class RecordSet								*
 *		2019/12/19      replace xmlentities with htmlentities           *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . "/Picture.inc";
require_once __NAMESPACE__ . "/RecordSet.inc";

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?>\n");
print "<ordered>\n";

// until I find out why this include generates a new line
// character, I have to include it after the XML header fields
// are emitted.
require_once __NAMESPACE__ . '/common.inc';

// get the updated values of the fields in the record
// list parameters passed to this script
print "    <parms>\n";
$idir	= null;
foreach($_POST as $key => $value)
{
    if (strtolower($key) == 'idir')
        $idir	= $value;
    print "\t<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
}
print "    </parms>\n";

// determine if permitted to update database
if (($authorized != 'yes') &&
    (strpos($authorized, 'edit') === false))
{		// take no action
    $msg	.= 'Not authorized. ';
}		// take no action

// validate parameters
if ($idir == null)
    $msg		= 'Mandatory parameter "idir" omitted. ';

if (strlen($msg) == 0)
{		// no errors detected
    // get the current set of picture records for the requested
    // individual in order by date
    $picParms	= array('idir'		=> $idir,
    				'idtype'	=> 0,
    				'order'		=> 'PicSD');
    $pictures	= new RecordSet('Pictures',
    					$picParms);
    $newOrder	= 0;
    foreach($pictures as $idbr => $picture)
    {		// loop through all matching pictures
        // include results of query in XML response
        print "    <old>\n";
        print "\t<idbr>" . $idbr . "</idbr>\n";
        print "\t<picorder>" . $picture->get('picorder') . "</picorder>\n";
        print "\t<picturesd>" . $picture->get('picsd') . "</picturesd>\n";
        print "    </old>\n";

        $picture->set('order', $newOrder);
        $picture->save(false);

        // include results of update in XML response
        print "    <picture>\n";
        print "\t<idbr>" . $idbr . "</idbr>\n";
        print "\t<picorder>" . $picture->get('picorder') . "</picorder>\n";
        print "\t<picturesd>" . $picture->get('picsd') . "</picturesd>\n";
        print "    </picture>\n";
 	    $newOrder++;
    }		// loop through all matching pictures
}		// no errors detected
else
{		// errors in parameters
    print "    <msg>\n";
    print htmlentities($msg,ENT_XML1);
    print "    </msg>\n";
}		// errors in parameters

// close root node of XML output
print "</ordered>\n";
