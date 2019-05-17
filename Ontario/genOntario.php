<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genOntario.php														*
 *																		*
 *  Menu of genealogical services for Ontario.							*
 *																		*
 *  History:															*
 *		2010/08/23		change to new standard layout					*
 *		2011/04/09		change to PHP									*
 *		2011/04/23		order death query after marriage query			*
 *		2012/05/09		add link to counties management					*
 *		2013/04/13		use functions pageTop and pageBot to standardize*
 *		2013/06/29		add support for Wesleyan Methodist Baptisms		*
 *		2013/08/01		defer facebook initialization until after load	*
 *		2013/08/16		display nominal index in separate tab			*
 *		2013/11/10		open more links in new window					*
 *		2013/12/24		use CSS for layout instead of tables			*
 *		2014/10/19		display counties link to everyone				*
 *		2014/12/30		Birth registration scripts moved to Canada		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/30		add County Marriage Query						*
 *						display trace data								*
 *		2016/05/20		CountiesEdit moved to folder Canada				*
 *		2017/07/18		separate district marriages from county			*
 *		2017/11/13		use Template									*
 *		2018/01/04		redirect to genProvince.php						*
 *																		*
 *  Copyright &copy; 2017 James A. Cobban								*
 ************************************************************************/
header('Location: /Canada/genProvince.php?domain=CAON');
