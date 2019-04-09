<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  getPersonXml.php													*
 *																		*
 *  Get the information on an individual as an XML response file so		*
 *  it can be retrieved by Javascript using AJAX.						*
 *																		*
 *  Parameters (passed by method='GET'):								*
 *		idir			numeric key of individual						*
 *		includeFamilies	if 'Y' include family information in response	*
 *		includeParents	if 'Y' include parent information in response	*
 *		includeNames	if 'Y' include names information in response	*
 *		includeEvents	if 'N' exclude events information in response	*
 *																		*
 *  History:															*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/12/20		handle exception from new LegacyIndiv			*
 *						accept both id= and idir= parameters			*
 *						issue error message for missing parameter		*
 *		2013/08/02		add option to include families					*
 *						add option to include parents					*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/04/08		add includeNames parameters						*
 *		2015/01/07		change require to require_once					*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/04/28		signature of LegacyIndiv::toXml changed			*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/07/30		add option to control whether events included	*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *						script renamed to getPersonXml.php				*
 *																		*
 *  Copyright &copy; 2017 James Alan Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values for parameters
$msg		= '';
$idir		= null;
$options		= Person::TOXML_INCLUDE_EVENTS;

foreach($_GET as $key => $value)
{
    switch(strtolower($key))
    {
        case 'id':
        case 'idir':
        {
    		$idir	= $value;
    		break;
        }

        case 'includefamilies':
        {
    		if (strtolower($value) == 'y')
    		    $options	|= Person::TOXML_INCLUDE_FAMILIES;
    		break;
        }

        case 'includeparents':
        {
    		if (strtolower($value) == 'y')
    		    $options	|= Person::TOXML_INCLUDE_PARENTS;
    		break;
        }

        case 'includenames':
        {
    		if (strtolower($value) != 'y')
    		    $options	|= Person::TOXML_INCLUDE_NAMES;
    		break;
        }

        case 'includeevents':
        {
    		if (strtolower($value) == 'y')
    		    $options	&= ~Person::TOXML_INCLUDE_EVENTS;
    		break;
        }

    }		// switch on parameter name
}			// loop through all parameters

if (is_null($idir))
    $msg	.= 'Missing parameter idir=. ';

// display the results
print("<?xml version='1.0' encoding='UTF-8'?>\n");

if (strlen($msg) > 0)
{
    print "<msg>\n";
    print "    " . $msg . "\n";
    print "</msg>\n";
}
else
{
    $person	= new Person(array('idir' => $idir));
    $person->toXml("indiv", true, $options);
}
