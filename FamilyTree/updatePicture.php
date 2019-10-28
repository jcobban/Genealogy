<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updatePicture.php													*
 *																		*
 *  Handle a request to update an individual picture in 				*
 *  the Legacy family tree database.									*
 *																		*
 *  Parameters:															*
 *		idbr	unique numeric identifier of the Picture record			*
 *				to update												*
 *		others	any field name defined in the Picture record			*
 *																		*
 *  History:															*
 *		2011/05/28		created											*
 *		2012/01/13		change class names								*
 *		2013/02/24		permit being called when the database record	*
 *						has not been written yet						*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/10/06		error in updating existing picture				*
 *		2015/01/06		redirect diagnostic information to $warn		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/09/12		use set(										*
 *		2019/07/26      merged into editPicture.php                     *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
