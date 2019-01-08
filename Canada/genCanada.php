<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  genCanada.php							*
 *									*
 *  This script displays the main page for Canada.			*
 *									*
 *    History:								*
 *	2017/10/19	convert genCanada.html to PHP script with	*
 *			language specific templates			*
 *	2018/01/04	redirect to genCountry.php			*
 *									*
 *  Copyright &copy; 2018 James A. Cobban				*
 ************************************************************************/
    header('Location: /genCountry.php?cc=CA&lang=en');
