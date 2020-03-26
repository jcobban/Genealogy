<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Person.php 												            *
 *																		*
 *  obsolete reference to FamilyTree/Person.php                         *
 *																		*
 *  History:															*
 *		2020/02/17  	created                                         *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
$serverName     = $_SERVER['SERVER_NAME'];
$queryString    = $_SERVER['QUERY_STRING'];
header( "Location: https://$serverName/FamilyTree/Person.php$queryString", true, 301 );
