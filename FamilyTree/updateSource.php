<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateSource.php													*
 *																		*
 *  Handle a request to update an individual source in 					*
 *  the Legacy family tree database.  Generate an XML response file		*
 *  so this script can be invoked using AJAX.							*
 *																		*
 *  Parameters (passed by POST):										*
 *		idsr	unique numeric identifier of source.					*
 *				If this is zero (0) a new source is created.			*
 *		others	valid field names within the Source record.				*
 *																		*
 *  History:															*
 *		2010/08/21		do not emit copy of parms to XML, this is		*
 *						done in LegacyRecord::postUpdate				*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2012/01/13		change class names								*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/01/30		Source::__construct supports name parm			*
 *		2015/02/13		support NewRepo name field						*
 *						create instance of Address to initialize		*
 *						IDAR, which eliminates need for user to			*
 *						manually refresh the editSource.php dialog		*
 *						to get an added instance						*
 *		2015/03/24		parameter values were not translated for XML	*
 *		2015/03/31		IDAR value was not set when updated record		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/09/12		use set(										*
 *		2018/12/02      only update those fields for whom parameters    *
 *		                have been passed because others are NULL        *
 *		2019/12/19      replace xmlentities with htmlentities           *
 *		2023/01/23      display error message from constructor          *
 *																		*
 *  Copyright &copy; 2023 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print("<?xml version='1.0' encoding='UTF-8'?" . ">\n");
print "<update>\n";

// get the updated values of the fields in the record
$idsr		    				= 0;
$srcname						= '';
$srctitle						= '';
$idst		    				= 1;
$srcauthor						= '';
$srcpubl						= '';
$srctext						= '';
$psrctext						= 0;
$fsrctext						= 0;
$tsrctext						= 0;
$srcnote						= '';
$psrcnote						= 0;
$fsrcnote						= 0;
$tsrcnote						= 0;
$srccallnum						= '';
$srctag		    				= 0;
$qstag		    				= 0;
$srcexclude						= 0;
$idar		    				= 0;
$idar2		    				= 0;
$enteredsd						= -99999999;
$enteredd						= '';
$filingref						= '';
$used		    				= 0;
$published						= 0;
$verified						= 0;
$srcmpub						= '';
$srcrollnum						= '';
$templateid						= 0;
$contents						= '';
$usestandard					= 0;
$bibliography					= 0;
$override						= '';
$overridefootnote				= 0;
$overridesubsequent				= 0;

print "<parms>\n";
foreach($_POST as $key => $value)
{			// loop through all parameters
	print "<$key>" . htmlentities($value,ENT_XML1) . "</$key>\n";
	switch(strtolower($key))
	{		// act on specific parameters
	    case 'idsr':
	    {
			$idsr		    	= $value;
			break;
	    }

	    case 'srcname':
	    {
			$srcname	    	= $value;
			break;
	    }

	    case 'srctitle':
	    {
			$srctitle	    	= $value;
			break;
	    }

	    case 'idst':
	    {
			$idst	        	= $value;
			break;
	    }

	    case 'srcauthor':
	    {
			$srcauthor	    	= $value;
			break;
	    }

	    case 'newauthor':
	    {
			if (strlen($value) > 0)
			    $srcauthor		= $value;
			break;
	    }

	    case 'srcpubl':
	    {
			$srcpubl	    	= $value;
			break;
	    }

	    case 'srctext':
	    {
			$srctext	    	= $value;
			break;
	    }

	    case 'psrctext':
	    {
			$psrctext	    	= $value;
			break;
	    }

	    case 'fsrctext':
	    {
			$fsrctext	    	= $value;
			break;
	    }

	    case 'tsrctext':
	    {
			$tsrctext	    	= $value;
			break;
	    }

	    case 'srcnote':
	    {
			$srcnote	    	= $value;
			break;
	    }

	    case 'psrcnote':
	    {
			$psrcnote	    	= $value;
			break;
	    }

	    case 'fsrcnote':
	    {
			$fsrcnote	    	= $value;
			break;
	    }

	    case 'tsrcnote':
	    {
			$tsrcnote	    	= $value;
			break;
	    }

	    case 'srccallnum':
	    {
			$srccallnum	    	= $value;
			break;
	    }

	    case 'srctag':
	    {
			$srctag		    	= $value;
			break;
	    }

	    case 'qstag':
	    {
			$qstag		    	= $value;
			break;
	    }

	    case 'srcexclude':
	    {
			$srcexclude	    	= $value;
			break;
	    }

	    case 'idar':
	    {
			$idar		    	= $value;
			break;
	    }

	    case 'newrepo':
	    {
			if (strlen($value) > 0)
			{
			    $repo	    	= new Address(
				        	array('addrname'	=> $value,
					              'kind'		=> Address::REPOSITORY));
			    if (!$repo->isExisting())
					$repo->save();
			    $idar	    	= $repo->getIdar();
			}
			break;
	    }

	    case 'idar2':
	    {
			$idar2		    	= $value;
			break;
	    }

	    case 'enteredsd':
	    {
			$enteredsd	    	= $value;
			break;
	    }

	    case 'enteredd':
	    {
			$enteredd	    	= $value;
			break;
	    }

	    case 'filingref':
	    {
			$filingref	    	= $value;
			break;
	    }

	    case 'used':
	    {
			$used		    	= $value;
			break;
	    }

	    case 'published':
	    {
			$published	    	= $value;
			break;
	    }

	    case 'verified':
	    {
			$verified	    	= $value;
			break;
	    }

	    case 'srcmpub':
	    {
			$srcmpub	    	= $value;
			break;
	    }

	    case 'srcrollnum':
	    {
			$srcrollnum	    	= $value;
			break;
	    }

	    case 'templateid':
	    {
			$templateid	    	= $value;
			break;
	    }

	    case 'contents':
	    {
			$contents	    	= $value;
			break;
	    }
 
	    case 'usestandard':
	    {
			$usestandard		= $value;
			break;
	    }

	    case 'bibliography':
	    {
			$bibliography		= $value;
			break;
	    }

	    case 'override':
	    {
			$override	    	= $value;
			break;
	    }

	    case 'overridefootnote':
	    {
			$overridefootnote	= $value;
			break;
	    }

	    case 'overridesubsequent':
	    {
			$overridesubsequent	= $value;
			break;
	    }

	    case 'debug':
	    {
			break;
	    }
	}		// act on specific parameters
}			// loop through all parameters
print "</parms>\n";

// locate existing source record, or create a new record
if ($idsr == 0 && strlen($srcname) > 0)
	$source		= new Source(array('srcname'	=> $srcname));
else
	$source		= new Source(array('idsr'	=> $idsr));

// update object from $_POST parameters
foreach($_POST as $field => $value)
{
    $fieldLc    = strtolower($field);
    if ($fieldLc != 'idsr')
        $source->set($fieldLc,    $value);
}                   // loop through new values

// save object state to server
$count          = $source->save();

if ($count == 0)
    print "<errors>count=$count, msg='" . $source->getErrors() . "</errors>";
else
    print "<cmd>" . htmlspecialchars($source->getLastSqlCmd()) . "</cmd>\n";

if (strlen($msg) > 0)
    print "<p class='message'>$msg</p>\n";

// include XML representation of updated record in response
$source->toXml('source');

// close root node
print "</update>\n";
