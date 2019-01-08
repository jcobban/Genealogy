<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  legacyIndex.php							*
 *									*
 *  Primary dialog for searching for individuals in the family tree.	*
 *									*
 *  Parameters (passed by method='GET'):				*
 *	birthmin	individuals born in or after this year		*
 *	birthmax	individuals born in or before this year		*
 *	treename	sub-tree to limit search to			*
 *	name		name matches or after "surname, givennames" 	*
 *	incmarried	if not empty include married names in search	*
 *	includeparents	if not empty include parents names in response	*
 *	includespouse	if not empty include spouses names in response	*
 *	sex		limit search by sex				*
 *									*
 *  History (as legacyIndex.html):					*
 *	2017/08/16	replaced with redirect to nominalIndex.php	*
 *									*
 *  Copyright &copy; 2016 James A. Cobban				*
 ************************************************************************/
header('Location: nominalIndex.php?' . $_SERVER['QUERY_STRING']);
