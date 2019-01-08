<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  customization.php							*
 *									*
 *  Tables for customizing output to the local language.		*
 *									*
 *  History:								*
 *	2011/02/06	created						*
 *	2012/01/13	change class names				*
 *									*
 *  Copyright &copy; 2013 James A. Cobban				*
 ************************************************************************/

/************************************************************************
 *  tranTab								*
 *									*
 *  French translation for strings used in formatting dates		*
 *  for display.							*
 ************************************************************************/

static	$tranTab	= array(
		'about'		=> 'about',
		'AD'		=> 'AD',
		'after'		=> 'after',
		'after'		=> 'after',
		'and'		=> 'and',
		'Apr'		=> 'Apr',
		'April'		=> 'April',
		'at'		=> 'at',
		'Aug'		=> 'Aug',
		'August'	=> 'August',
		'Banns'		=> 'Banns',
		'BC'		=> 'BC',
		'before'	=> 'before',
		'between'	=> 'between',
		'bic'		=> 'bic',
		'calculated as'	=> 'calculated as',
		'Cancelled'	=> 'Cancelled',
		'Carriage Maker'=> 'Carriage Maker',
		'child'		=> 'child',
		'Child'		=> 'Child',
		'circa'		=> 'circa',
		'cleared'	=> 'cleared',
		'Completed'	=> 'Completed',
		'daughter'	=> 'daughter',
		'Dec'		=> 'Dec',
		'Deceased'	=> 'Deceased',
		'December'	=> 'December',
		'Did not say'	=> 'Did not say',
		'dns/can'	=> 'dns/can',
		'Done'		=> 'Done',
		'estimated as'	=> 'estimated as',
		'Farmer'	=> 'Farmer',
		'Feb'		=> 'Feb',
		'February'	=> 'February',
		'female'	=> 'female',
		'for'		=> 'for',
		'Grocer'	=> 'Grocer',
		'General'	=> 'General',
		'from'		=> 'from',
		'he'		=> 'he',
		'He'		=> 'He',
		'in'		=> 'in',
		'in Q'		=> 'in Q',
		'Individual Not Found'=>'Individual Not Found',
		'Infant'	=> 'Infant',
		'Jan'		=> 'Jan',
		'January'	=> 'January',
		'Jul'		=> 'Jul',
		'July'		=> 'July',
		'Jun'		=> 'Jun',
		'June'		=> 'June',
		'License'	=> 'License',
		'male'		=> 'male',
		'Mar'		=> 'Mar',
		'March'		=> 'March',
		'married'	=> 'married',
		'May'		=> 'May',
		'Merchant'	=> 'Merchant',
		'Never married'	=> 'Never married',
		'Not married'	=> 'Not married',
		'Nov'		=> 'Nov',
		'November'	=> 'November',
		'Oct'		=> 'Oct',
		'October'	=> 'October',
		'on'		=> 'on',
		'or'		=> 'or',
		'Postmaster'	=> 'Postmaster',
		'Private'	=> 'Private',
		'See Notes'	=> 'See Notes',
		'Sep'		=> 'Sep',
		'September'	=> 'September',
		'she'		=> 'she',
		'She'		=> 'She',
		'Shoemaker'	=> 'Shoemaker',
		'son'		=> 'son',
		'Stillborn'	=> 'Stillborn',
		'submitted'	=> 'submitted',
		'to'		=> 'to',
		'uncleared'	=> 'uncleared',
		'Unknown'	=> 'Unknown',
		'WFT estimate'	=> 'WFT estimate',
		'Young'		=> 'Young',
		);
?>
