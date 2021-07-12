<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  configDb.inc                                                        *
 *                                                                      *
 *  Customization of access to the database server                      *
 *                                                                      *
 *  Sets the following global variables that can be used by any script: *
 *                                                                      *
 *      $servername     the domain name of the web server               *
 *      $dsn            associative array containing the parameters     *
 *                      used to initialize the database connection      *
 *      $databaseName   the name of the SQL database                    *
 *                                                                      *
 *  History:                                                            *
 *      2015/07/04      renamed from "db.inc" to avoid potential        *
 *                      conflict on file names                          *
 *      2015/09/28      migrate to support for PDO                      *
 *      2018/11/04      support setting Google API key                  *
 *                                                                      *
 *  Copyright &copy; 2018 James A. Cobban                               *
 ************************************************************************/
    // identify database server
    $servers    = array (
            'backup'    => array(
                        'phptype'  => 'mysql',
                        'username' => 'jamescob_update',
                        'password' => '-ft^w,MN2@NJ',
                        'hostspec' => 'localhost',
                        'database' => 'jamescob_genealogy',
                        'googlekey'=> 'AIzaSyBEod4HhrMroO2vBaYUiTnoOur-0RC1UpY'
                        ),
            'isp'       => array(
                        'phptype'  => 'mysql',
                        'username' => 'jamescob_update',
                        'password' => '-ft^w,MN2@NJ',
                        'hostspec' => 'localhost',
                        'database' => 'jamescob_genealogy',
                        'googlekey'=> 'AIzaSyBEod4HhrMroO2vBaYUiTnoOur-0RC1UpY'
                        ),
            'remote'    => array(
                        'phptype'  => 'mysql',
                        'username' => 'jamescob_update',
                        'password' => '-ft^w,MN2@NJ',
                        'hostspec' => 'jamescobban.net',
                        'database' => 'jamescob_genealogy',
                        'googlekey'=> 'AIzaSyBEod4HhrMroO2vBaYUiTnoOur-0RC1UpY'
                        ),
            'original'  => array(
                        'phptype'  => 'mysql',
                        'username' => 'root',
                        'password' => 'Spyn12..',
                        'hostspec' => 'localhost',
                        'database' => 'genealogy',
                        'googlekey'=> 'AIzaSyCpSvn6_xeFTdVMue8hwxWpdYmoUCKXebc'
                        )
                    );

/************************************************************************
 *                                                                      *
 *  The following bit of open code is executed on every page to         *
 *  set $dsn to a structure with information for connecting to          *
 *  the SQL server                                                      *
 *                                                                      *
 ************************************************************************/
    $servername     = strtolower($_SERVER['SERVER_NAME']);
    if ($servername == 'www.jamescobban.net' ||
        $servername == 'genealogy.jamescobban.net' ||
        $servername == 'jamescobban.net')
        $dsn        = $servers['isp'];
    else
        $dsn        = reset($servers);  // get first entry

    // get the database name from the selected entry
    if (is_array($dsn))
        $databaseName   = $dsn['database'];

