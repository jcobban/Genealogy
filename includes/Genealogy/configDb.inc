<?php
namespace Genealogy;
/************************************************************************
 *  configDb.inc														*
 *																		*
 *  Customization of access to the database server						*
 *																		*
 *  Sets the following global variables that can be used by any script:	*
 *																		*
 *		$servername		the domain name of the web server				*
 *		$dsn			associative array containing the parameters 	*
 *						used to initialize the database connection		*
 *		$databaseName	the name of the SQL database					*
 *																		*
 *  History:															*
 *		2015/07/04		renamed from "db.inc" to avoid potential		*
 *						conflict on file names							*
 *		2015/09/28		migrate to support for PDO						*
 *		2018/11/04      support setting Google API key                  *
 *		2019/01/07      add namespace                                   *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
    // identify database server
    $servers	= array (
		    'local'	    => array(
						'phptype'  => 'mysql',
						'username' => 'mysqluserid',
						'password' => 'astrongpassword',
						'hostspec' => 'localhost',
						'database' => 'genealogy',
                        'googlekey'=> ''
						),
		    'isp'	    => array(
						'phptype'  => 'mysql',
						'username' => 'mysqluserid',
						'password' => 'astrongpassword',
						'hostspec' => 'localhost',
						'database' => 'genealogy',
                        'googlekey'=> ''
						),
		    'remote'	=> array(
						'phptype'  => 'mysql',
						'username' => 'mysqluserid',
						'password' => 'astrongpassword',
						'hostspec' => 'domain.com',
						'database' => '_genealogy',
                        'googlekey'=> ''
						)
					);

/************************************************************************
 *																		*
 *  The following bit of open code is executed on every page to			*
 *  set $dsn to a structure with information for connecting to			*
 *  the SQL server														*
 *																		*
 ************************************************************************/
    $servername		= strtolower($_SERVER['SERVER_NAME']);
    if ($servername == 'www.domain.com' ||
		$servername == 'genealogy.domain.com' ||
		$servername == 'domain.com')
		$dsn		= $servers['isp'];
    else
		$dsn		= reset($servers);	// get first entry

    // get the database name from the selected entry
    if (is_array($dsn))
		$databaseName	= $dsn['database'];

