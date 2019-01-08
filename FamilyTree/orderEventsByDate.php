<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  orderEventsByDate.php						*
 *									*
 *  Handle a request to reorder the event records for an		*
 *  individual in the Legacy family tree database.  The `Order` field	*
 *  in each record is updated so the events will display in order	*
 *  by the `EventSD` field.  This file generates an			*
 *  XML file, so it can be invoked from Javascript.			*
 *									*
 *  Parameters:								*
 *	idir	unique numeric key of the individual			*
 *									*
 *  History:								*
 *	2010/08/10	created						*
 *	2010/09/25	Check error on $result, not $connection after	*
 *			query/exec					*
 *	2010/10/23	move connection establishment to common.inc	*
 *	2012/01/13	change class names				*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/03/14	use only methods of Event			*
 *	2014/04/26	formUtil.inc obsoleted				*
 *	2014/11/02	sort events with no date appropriate to type	*
 *	2014/12/22	obsolete					*
 *	2015/07/02	access PHP includes using include_path		*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . "/common.inc";

    // emit the XML header
    header("Content-Type: text/xml");
    print("<?xml version='1.0' encoding='UTF-8'?>\n");
    print "<ordered>\n";
    print "</ordered>\n";
?>
