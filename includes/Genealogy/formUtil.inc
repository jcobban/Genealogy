<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  formUtil.inc							*
 *									*
 *  Common routines and tables used by PHP forms.			*
 *									*
 *  History:								*
 *	2010/08/28	putTextElt suppress warning on missing db fields*
 *	2010/10/03	set lines per page when inserting into Pages	*
 *			table						*
 *	2010/11/10	in updPages only output if debug requested	*
 *	2010/11/17	use quote in updPages to support MDB2		*
 *	2010/11/20	support page increment other than 1		*
 *	2011/01/05	For censuses with numeric sub-district		*
 *			identifiers change algorithm for identifying	*
 *			previous and next sub-district identifier	*
 *	2011/01/15	updPages was not updating the Pages table	*
 *			because of incorrect WHERE clause		*
 *	2011/06/27	add support for 1916 census			*
 *	2012/09/13	move getNames to common.inc			*
 *			remove no longer used getnpPrev and getnpNext	*
 *	2013/04/04	remove doQuery and doExec			*
 *			do not change transcriber if already set in	*
 *			Pages						*
 *	2013/07/05	suppress no-update on page table		*
 *	2014/04/26	function updPages replaced by class Page	*
 *			remove no longer used functions			*
 *			entire include now obsolete			*
 *									*
 *  Copyright &copy; 2014 James A. Cobban				*
 ************************************************************************/

