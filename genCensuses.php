<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genCensuses.php                                                     *
 *                                                                      *
 *  Display a web page containing details of an particular Location     *
 *  from the Legacy database.                                           *
 *                                                                      *
 *  History:                                                            *
 *      2013/12/05      target moved to subdirectory database           *
 *      2014/12/02      enclose comment blocks                          *
 *                                                                      *
 *  Copyright &copy; 2014 James A. Cobban                               *
 ************************************************************************/
header('HTTP/1.1 301 Moved Permanently');
header('Location: database/genCensuses.php');
exit();

