<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  index.php 												            *
 *																		*
 *  obsolete reference to FamilyTree/nominalIndex.php                   *
 *																		*
 *  History:															*
 *		2020/02/17  	created                                         *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
$serverName     = $_SERVER['SERVER_NAME'];
$queryString    = $_SERVER['QUERY_STRING'];
header( "Location: https://$serverName/FamilyTree/nominalIndex.php$queryString", true, 301 );
