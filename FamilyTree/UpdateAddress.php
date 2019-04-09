<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  UpdateAddress.php													*
 *																		*
 *  Update an instance of Address with parameters passed by				*
 *  method='post'														*
 *																		*
 *  Parameters (passed by method='get' to view or 'post' to update):	*
 *		idar			Unique numeric identifier of the address.		*
 *						For backwards compatibility this can be			*
 *						specified using the 'id' parameter.				*
 *						If this is 0 it is a request to create a		*
 *						new address record.								*
 *		kind			0 for mailing address							*
 *						1 for event address								*
 *						2 for repository address						*
 *		addrname		name of the address								*
 *		formname		name of the form in the invoking page			*
 *						containing the element with						*
 *						name='idar' that is to be updated with the		*
 *						idar of the newly created address.				*
 *						If idar=0 and kind=0.							*
 *		action			'update' or 'delete'							*
 *																		*
 *  History:															*
 *		2015/05/27		created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2018/02/12		obsolete										*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
