<?php
namespace Genealogy;
use \PDO;
use \Exception;
    // parameters for constructing a Mail object
    $mailparms	= array(
	'host'		=> 'mail.jamescobban.net',
	'port'		=> '25',
	'username'	=> 'services@jamescobban.net',
	'password'	=> '9<Fg$p2a,q6j',
	'auth'		=> true
			);
//    $mailparms	= array(
//	'host'		=> 'smtphm.sympatico.ca',
//	'port'		=> '25',
//	'username'	=> 'jamescobban@sympatico.ca',
//	'password'	=> 'Spyn12..',
//	'auth'		=> true
//			);

    $mailfactory = Mail::factory('smtp',
			       $mailparms);
    if (PEAR::isError($mailfactory)) {
	die( "Unable to connect to mail server: " . $mailfactory->getMessage());
    }
?>
