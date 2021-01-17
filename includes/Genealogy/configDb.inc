<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  configDb.inc														*
 *																		*
 *  Customization of access to the database server						*
 *																		*
 *  History:															*
 *		2015/09/28		migrate to support for PDO						*
 *		2018/11/04      support setting Google API key                  *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
    // identify database server
    $servers	= array (
		    'original'	=> array(
						'phptype'  => 'mysql',
						'username' => 'root',
						'password' => '..',
						'hostspec' => 'localhost',
						'database' => 'genealogy',
                        'googlekey'=> '...'
						)
					);

