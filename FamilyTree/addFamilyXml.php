<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  addFamilyXml.php							*
 *									*
 *  Add a family record into the Legacy database and return the		*
 *  contents of the family record as XML so this can be invoked through	*
 *  AJAX from a web script.						*
 *									*
 *  Parameters passed by POST, one of the following:			*
 *	idir	if specified indicates to create a new			*
 *		marriage in which the person identified			*
 *		by this IDIR value is a spouse				*
 *	child	if specified indicates to create a new			*
 *		marriage in which the person identified			*
 *		by this IDIR value is a child.				*
 *									*
 *  History:								*
 *	2011/06/14	created						*
 *	2012/01/13	change class names				*
 *	2012/07/26	change genOntario.html to genOntario.php	*
 *	2012/09/20	clean up parameter validation			*
 *	2012/11/27	static LegacyFamily::add replaced by extended	*
 *			constructor call				*
 *	2013/04/02	initialize names to blank			*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/04/26	formUtil.inc obsoleted				*
 *	2014/09/27	RecOwners class renamed to RecOwner		*
 *			use Record method isOwner to check ownership	*
 *	2014/12/22	use LegacyIndiv::getBirthEvent			*
 *	2015/02/19	no longer used					*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
