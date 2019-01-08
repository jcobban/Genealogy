<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  legacyIndivid.php							*
 *									*
 *  Display a web page containing details of an particular individual	*
 *  from the Legacy table of individuals.				*
 *									*
 *  Parameters (passed by method='get')					*
 *	idir	unique numeric identifier of the individual to display	*
 *		Optional if UserRef is specified			*
 *	UserRef	user assigned identifier of the individual to display.	*
 *		Ignored if idir is specified				*
 *									*
 * History:								*
 *	2017/08/16	redirect to Person.php				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
header('Location: Person.php?' . $_SERVER['QUERY_STRING']);
