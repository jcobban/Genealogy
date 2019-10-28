<?php
/************************************************************************
 *  updateTemple.php													*
 *																		*
 *  Handle a request to update an individual temple in 					*
 *  the Legacy family tree database.									*
 *																		*
 *  Parameters:															*
 *		idtr	unique numeric identifier of the Temple record			*
 *				to update												*
 *		others	any field name defined in the Temple record				*
 *																		*
 *  History:															*
 *		2012/12/08		created											*
 *		2013/02/23		support new database table format				*
 *						simplify code									*
 *						correct minor errors in messages				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/09/02		class LegacyTemple renamed to Temple			*
 *		2017/09/12		use set(										*
 *		2019/06/25      merged into Temple.php                          *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
