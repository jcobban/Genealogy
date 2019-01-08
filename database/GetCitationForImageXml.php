<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  GetCitationForImageXml.php						*
 *									*
 *  Generate an XML document with information about the complete	*
 *  citation for a page identified by the URL of the image on the	*
 *  LAC web-site.							*
 *									*
 *  Parameters (passed by method GET):					*
 *	Image		URL of image					*
 *									*
 *  History:								*
 *	2011/09/14	created						*
 *	2012/01/27	renamed to identify XML return			*
 *	2013/11/26	handle database server failure gracefully	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2015/09/28	migrate from MDB2 to PDO			*
 *	2017/09/14	use class Page					*
 *	2017/11/17	use RecordSet instead of Page::getPages		*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Page.inc';
    require_once __NAMESPACE__ . '/common.inc';

    // the invoker must explicitly provide the URL of the image
    if (array_key_exists('Image', $_GET))
	$image	= $_GET['Image'];
    else
	$msg	= "Did not pass parameter Image by method=get. ";

    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");

    // execute the query
    if (strlen($msg) == 0)
    {
	// display the results
	// top node of XML result
	print("<ident>\n");
	print "<image>" . htmlspecialchars($image) . "</image>\n";

	$pages		= new RecordSet('Pages', 
					array('pt_image'	=> $image));

	// report on all matching records
	foreach($pages as $page)
	{		// loop through all result rows
	    print "<row>\n";
	    foreach($page as $key => $value)
	    {		// loop through fields in row
		print "<$key>$value</$key>\n";
	    }		// loop through fields in row
	    print "</row>\n";
	}		// loop through all result rows
	    
	print("</ident>\n");	// close off top node of XML result
    }		// user supplied needed parameters
    else
    {		// error
	print("<msg>$msg</msg>\n");
    }		// error
?>
