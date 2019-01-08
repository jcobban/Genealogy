<?php
namespace Genealogy;
use \PDO;
use \Exception;
/***********************************************************************&
 *  testObjectToXml.php							*
 *									*
 *  Get the information on an instance of Record as an XML response	*
 *  History:								*
 *	2016/02/22	created						*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

function arrayToXml($top, $object)
{
    $retval	= "<$top>\n";
    foreach($object as $key => $value)
    {
	if (is_string($key))
	{
	    if (is_array($value))
		$retval	.= arrayToXml($key, $value);
	    else
	    {
		$text	= str_replace('<','&lt;',str_replace('&','&amp;',$value));
		$retval	.= "<$key>$text</$key>\n";
	    }
	}
	else
	{
	    $text	= str_replace('<','&lt;',str_replace('&','&amp;',$value));
	    $retval	.= $text;
	}
    }
    $retval	.= "</$top>\n";
    return $retval;
}		// function arrayToXml
    $object	= array('field'	=> 'value',
			'champs' => 'valeur',
			'array' => array('first'	=> 'James',
					 'last'		=> 'Cobban',
					 'phone'	=> '613-592-9438',
					 'parents'	=> 'Don &Lorna',
					 'age'		=> '<70'),
			'text',
			'number' => 6);
    // display the results
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    $object['dump']	= print_r($object, true);
    print arrayToXml('test',$object);
?>
