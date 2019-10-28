<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \ArrayAccess;
use \Countable;
use \Iterator;
/************************************************************************
 *  LegacyDate.inc														*
 *																		*
 *  This class represents dates as they are represented in a			*
 *  Legacy database.													*
 *																		*
 *  History:															*
 *		2010/09/18		add method toString with privatization			*
 *		2010/09/19		fix handling of dates with prefixes				*
 *						support date format "mmm dd yyyy"				*
 *						support date format "dd/mm/yyyy"				*
 *		2010/10/04		add support for BC dates						*
 *		2010/10/21		make members private							*
 *						getDay, getMonth, and getYear to return 0		*
 *						if date empty or unrecognized					*
 *		2010/12/11		internationalize dates							*
 *						remove method showDate as no longer used		*
 *						use template to layout date						*
 *						add static function setTemplate					*
 *		2010/12/13		trim off space resulting from expansion of		*
 *						template										*
 *		2011/04/01		correct sort date where day of month is missing	*
 *		2012/01/14		change class name to LegacyDate					*
 *		2012/04/22		correct various small date parsing errors		*
 *	                    do not include leading space in unparseable date*
 *						support [AD] token in formatting template		*
 *						only emit [BC] and [AD] tokens if a date is		*
 *						not null										*
 *						permit explicitly specifying AD, BC, CE, BCE,	*
 *								OS, NS, and, just for fun,				*
 *								Ab Urbe Condita.						*
 *		2012/06/13		correct implementation of Qn YYYY				*
 *		2012/08/04		ignore upper case letters in tokens				*
 *						ignore periods in state FIRST_TOKEN to permit	*
 *						parsing GEDCOM 5.5 compliant dates				*
 *						treat numbers longer than 4 digits as			*
 *						syntax error									*
 *		2013/04/21		allow quarter to be expressed as either			*
 *						Qn or nQ yyyy									*
 *		2013/08/09		add static method setDebug						*
 *						pass numeric tokens as integers					*
 *						break state code into separate methods for		*
 *						clarity											*
 *						use switch to clarify token handling			*
 *						add support for the French Republican Calendar	*
 *						add formatting tag [xxxx] to display the year in*
 *						Roman numerals									*
 *		2013/09/02		improve leap day calculation for French			*
 *						Republican calendar								*
 *						fix a number of bugs by restructuring the parse	*
 *						so both dates of a range are parsed by the same	*
 *						states											*
 *		2013/11/22		add magic __toString							*
 *		2014/01/15		getYear returns integer							*
 *		2014/02/07		null sort date erroneously set to -999999999	*
 *		2015/06/27		fix remaining comment blocks					*
 *						add support for YYYY/MM/DD						*
 *		2016/04/28		internal date format at least 10 chars long		*
 *		2017/09/13		support '.' as standard ordinal indicator		*
 *						simplify tokenization							*
 *		2018/02/10		support passing context specific translation	*
 *						table to method toString						*
 *		2018/04/04      support PERL style substitutions in template    *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/

class WorkingDate
{
    public	$epoch	= '0';	// default epoch AD
    public	$day	= 0;	// extracted day of month (1 to 31)
    public	$month	= 0;	// extracted month (1 to 12, 1=January)
    public	$year	= 0;	// extracted year (1 to 9999)
}	// class WorkingDate

class LegacyDate 
{
    /********************************************************************
     *	$date		internal representation of date						*
     ********************************************************************/
    private	$date		= '';

    /********************************************************************
     *	function LegacyDate::getDate									*
     *																	*
     *  Obtain the internal representation of the date.					*
     *																	*
     *  Returns:                                                        *
     *      internal representation of the date according to the	    *
     *	Legacy Family Tree specification as a string.					*
     ********************************************************************/
    function getDate() { return $this->date; }

    /********************************************************************
     *	function LegacyDate::isPresent									*
     *																	*
     *  Determine whether the date is set or not		            	*
     *																	*
     *	Returns:                                                        *
     *      true if the date has been set to a non-null value.		    *
     ********************************************************************/
    function isPresent() { return strlen($this->date) > 0; }

    /********************************************************************
     *	$sortdate		sort key for dates								*
     ********************************************************************/
    private	$sortdate	= -99999999;

    /********************************************************************
     *	function LegacyDate::getSortDate								*
     *																	*
     *  Get the date in a form which can be sorted.				       	*
     *	For common era (CE or AD) dates this is yyyymmdd where mm is	*
     *	a number representing the month with January as 1.  For dates	*
     *  before the common era (BCE or BC) the number is					*
     *	(-yyyy * 10000) + mmdd.											*
     *																	*
     *	Returns:                                                        *
     *      the date in a numerical format which can be used for	    *
     *		sorting.													*
     ********************************************************************/
    function getSortDate() { return $this->sortdate; }

    /********************************************************************
     *	function LegacyDate::getJulianDate								*
     *																	*
     *  Get the Julian Day number corresponding to noon at				*
     *	Greenwich on the date as an integer.  This is a sequential  	*
     *	number since  January 1, 4713 BC in the proleptic Julian	    *
     *	calendar as defined by Joseph Justus Scaliger (1540-1609).		*
     *																	*
     *  Returns:                                                        *
     *      the sequential Julian Day number						    *
     ********************************************************************/
    function getJulianDate() 
    {
    	$year	= floor($this->sortdate / 10000);
    	$mmdd	= $this->sortdate - $year * 10000;
    	if ($year < 0)
    	    $year++;	// 1BCE = 0
    	$mm	= floor($mmdd / 100);
    	$dd	= $mmdd % 100;
    	$a	= floor((14 - $mm) / 12);
    	$y	= $year + 4800 - $a;	// years starting at March
    	$m	= $mm + 12 * $a - 3;	// month number with March = 0
    	$jdn	= $dd + floor((153 * $m + 2)/5) + 365 * $y + floor($y / 4) -
    			32083;
    	if ($jdn >= 2299159)
    	{		// on or after 15 October 1582
    	    // apply gregorian correction
    	    $jdn= $jdn - floor($y / 100) + floor($y / 400) + 38;
    	}		// on or after 15 October 1582
    	return $jdn;
    }		// function LegacyDate::getJulianDate

    /********************************************************************
     *	$msg				error message for parse failure				*
     ********************************************************************/
    private	$msg		= '';

    /********************************************************************
     *	function LegacyDate::getMessage									*
     *																	*
     *  Get any error or warning message arising out of parsing a		*
     *	textual date.													*
     *																	*
     *  Returns:                                                        *
     *      string containing message text if parse errors occurred.    *
     ********************************************************************/
    function getMessage() { return $this->msg; }

    /********************************************************************
     *	LegacyDate::state			        							*
     *																	*
     *	This member variable contains the name of the function for		*
     *	handling tokens in the current state.							*
     *	The value is the name of the function to be called to handle    *
     *	tokens as a string.                                             *
     ********************************************************************/

    private	$state		= "doFirstToken";

    /********************************************************************
     *  LegacyDate::$debug												*
     *																	*
     *  Determine whether internal trace messages are generated.        *
     ********************************************************************/

    private static	$debug		= false;

    /********************************************************************
     *	function LegacyDate::setDebug									*
     *																	*
     *  Change the global debug output option for the class.			*
     *																	*
     *	Input:															*
     *		$newval			true to enable debugging output or false to	*
     *						disable debugging output.					*
     *																	*
     *  Returns:                                                        *
     *      previous value of the debug option.						    *
     ********************************************************************/
    public static function setDebug($newval)
    {
    	$oldval	= self::$debug;
    	self::$debug	= $newval;
    	return $oldval;
    }		// function LegacyDate::setDebug

    /********************************************************************
     *	LegacyDate::$months							            		*
     *	LegacyDate::$lmonths											*
     *																	*
     *  Tables of the default names of the months for output.			*
     *  The contents of these tables are overridden by tranTab          *
     ********************************************************************/

    // 3 character abbreviations
    private static	$months	= array('',
            			'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
    	            	'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

    // full names for output in English
    private static	$lmonths	= array('',
    									'January', 
    									'February', 
    									'March', 
    									'April', 
    									'May', 
    									'June', 
    									'July', 
    									'August', 
    									'September', 
    									'October', 
    									'November', 
    									'December');

    /********************************************************************
     *	LegacyDate::$monthLens											*
     *																	*
     *  Table of lengths of the months in the western calendar.			*
     ********************************************************************/

    private static	$monthLens	= array(31,	// slot 0 is December
    									31,	// January
    									28,	// February
    									31,	// March
    									30,	// April
    									31,	// May
    									30,	// June
    									31,	// July
    									31,	// August
    									30,	// September
    									31,	// October
    									30,	// November
                            			31	// December
                            			);

    /********************************************************************
     *	LegacyDate::$template											*
     *																	*
     *	Default template string for formatting a date for output.		*
     *	Substitutions are identified by codes enclosed in square		*
     *	brackets:														*
     *	    dd			day of month as a number						*
     *	    OSdd		day of month as a number, Julian calendar		*
     *					after 1582										*
     *	    Mon			3 character abbreviation for month name			*
     *	    OSMon		3 char abbr for month, Julian calendar			*
     *					after 1582										*
     *	    Month		full month name									*
     *	    OSMonth		full month name, Julian calendar after 1582		*
     *	    yyyy		year (numeric)									*
     *	    xxxx		year (roman numerals)							*
     *	    OSyyyy		year (numeric), Old Style: Julian plus			*
     *					year starting on 25th March						*
     *	    BC			display epoch if BC								*
     *	    AD			display epoch if AD								*
     *	    BCE			display epoch if BC								*
     *	    CE			display epoch if AD								*
     ********************************************************************/
    private static	$template	= '[dd] [Mon] [yyyy] [BC]';

    /********************************************************************
     *	function LegacyDate::getTemplate								*
     *																	*
     *  Get the current template string used to format dates for		*
     *	presentation to the user.										*
     *																	*
     *  Returns:                                                        *
     *      current template string.							    	*
     ********************************************************************/
    public static function getTemplate()
    {
    	return self::$template;
    }		// function LegacyDate::getTemplate

    /********************************************************************
     *	function LegacyDate::setTemplate								*
     *																	*
     *  Change the template string used to format dates for				*
     *	presentation to the user.										*
     *																	*
     *	Input:															*
     *	    $newfmt		string containing substitution tags as defined	*
     *					above.											*
     *																	*
     *  Returns:                                                        *
     *      former template string.										*
     ********************************************************************/
    public static function setTemplate($newfmt)
    {
    	$oldfmt			= self::$template;
    	self::$template		= $newfmt;
    	return $oldfmt;
    }		// function LegacyDate::setTemplate

    /********************************************************************
     *	LegacyDate::$monTab												*
     *																	*
     *  Translate English month names and abbreviations to indices on	*
     *  input. 															*
     ********************************************************************/

    private static	$monTab	= array(
    			'Jan'				=> 1,
    			'Jany'				=> 1,
    			'January'			=> 1,
    			'Feb'				=> 2,
    			'Feby'				=> 2,
    			'February'			=> 2,
    			'Mar'				=> 3,
    			'March'				=> 3,
    			'Apr'				=> 4,
    			'Aprl'				=> 4,
    			'April'				=> 4,
    			'May'				=> 5,
    			'Jun' 				=> 6,
    			'June' 				=> 6,
    			'Jul'				=> 7,
    			'July'				=> 7,
    			'Aug'				=> 8,
    			'Augt'				=> 8,
    			'August'			=> 8,
    			'Sep'				=> 9,
    			'Sept'				=> 9,
    			'September'			=> 9,
    			'Oct'				=> 10,
    			'Octr'				=> 10,
    			'October'			=> 10,
    			'Nov'				=> 11,
    			'Novr'				=> 11,
    			'November'			=> 11,
    			'Dec'				=> 12,
    			'Decr'				=> 12,
    			'December'			=> 12
    			);

    /********************************************************************
     *	LegacyDate::$pfxTab												*
     *																	*
     *  Prefix value for internal date representation based upon 		*
     *	initial reserved word.											*
     ********************************************************************/

    private static	$pfxTab	= array(
    			'In' 				=> '0',
    			'On' 				=> '0',
    			'Abt' 				=> '1',
    			'About' 			=> '1',
    			'Cir' 				=> '2',
    			'Circa' 			=> '2',
    			'Bef' 				=> '3',
    			'Before' 			=> '3',
    			'Aft'				=> '4',
    			'After'				=> '4',
    			'Between' 			=> '5',
    			'Bet'	 			=> '5',
    			'WFT' 				=> '8',
    			'Est' 				=> 'g',
    			'Cal' 				=> 'h',
    			'Calculated' 		=> 'h',
    			'From' 				=> 'F',
    			'To' 				=> 'T',
    			'<' 				=> '<'
    			);

    /********************************************************************
     *	LegacyDate::$tranTab											*
     *																	*
     *  English translation for strings used in formatting dates		*
     *  for display.													*
     ********************************************************************/

    private static	$tranTab	= array(
    		''				    => '',
    		'about'				=> 'about',
    		'AD'				=> 'AD',
    		'after'				=> 'after',
    		'and'				=> 'and',
    		'Apr'				=> 'Apr',
    		'April'				=> 'April',
    		'Aug'				=> 'Aug',
    		'August'			=> 'August',
    		'Banns'				=> 'Banns',
    		'BC'				=> 'BC',
    		'BCE'				=> 'BCE',
    		'before 1970'		=> 'before 1970',
    		'before'			=> 'before',
    		'between'			=> 'between',
    		'bic'				=> 'bic',
    		'calculated as'		=> 'calculated as',
    		'Cancelled'			=> 'Cancelled',
    		'CE'				=> 'CE',
    		'Child'				=> 'Child',
    		'circa'				=> 'circa',
    		'cleared'			=> 'cleared',
    		'Completed'			=> 'Completed',
    		'Dec'				=> 'Dec',
    		'Deceased'			=> 'Deceased',
    		'December'			=> 'December',
    		'Did not say'		=> 'Did not say',
    		'dns/can'			=> 'dns/can',
    		'Done'				=> 'Done',
    		'estimated as'		=> 'estimated as',
    		'Feb'				=> 'Feb',
    		'February'			=> 'February',
    		'from'				=> 'from',
    		'in Q'				=> 'in Q',
    		'in'				=> 'in',
    		'Infant'			=> 'Infant',
    		'Jan'				=> 'Jan',
    		'January'			=> 'January',
    		'Jul'				=> 'Jul',
    		'July'				=> 'July',
    		'Jun'				=> 'Jun',
    		'June'				=> 'June',
    		'License'			=> 'License',
    		'Mar'				=> 'Mar',
    		'March'				=> 'March',
    		'May'				=> 'May',
    		'Never married'		=> 'Never married',
    		'Not married'		=> 'Not married',
    		'Nov'				=> 'Nov',
    		'November'			=> 'November',
    		'Oct'				=> 'Oct',
    		'October'			=> 'October',
    		'on'				=> 'on',
    		'or'				=> 'or',
    		'Private'			=> 'Private',
    		'See Notes'			=> 'See Notes',
    		'Sep'				=> 'Sep',
    		'September'			=> 'September',
    		'Stillborn'			=> 'Stillborn',
    		'submitted'			=> 'submitted',
    		'to'				=> 'to',
    		'uncleared'			=> 'uncleared',
    		'Unknown'			=> 'Unknown',
    		'WFT estimate'		=> 'WFT estimate',
    		'Young'				=> 'Young'
    		);

    /********************************************************************
     *	internal parse work variables									*
     ********************************************************************/
    private	$prefix		= '0';	// default to simple date
    private	$epoch		= '0';	// AD (CE) date
    private	$date1		= null;	// 1st instance of WorkDate
    private	$date2		= null;	// 2nd instance of WorkDate
    private	$workDate	= null;	// current instance of WorkDate

    /********************************************************************
     *	pattern for roman numerals										*
     ********************************************************************/
    private static	$rmPattern	=
    	"/^(?=[MDCLXVI])M*(C[MD]|D?C{0,3})(X[CL]|L?X{0,3})(I[XV]|V?I{0,3})$/i";

    /********************************************************************
     *	function LegacyDate::__construct								*
     *																	*
     *  Construct an instance of LegacyDate.							*
     *																	*
     *	Input:															*
     *	    $value		value of date.  If it starts with a space		*
     *					then it is a human readable date to be			*
     *					translated to internal form. Otherwise it is	*
     *					already an internal date.						*
     *																	*
     ********************************************************************/
    function __construct($value)
    {
    	global	$debug;
    	global	$warn;

    	if (LegacyDate::$debug)
    	    print "<p>function LegacyDate::construct('$value')</p>\n";

    	// there can be two dates in the value to support ranges
    	$this->date1		= new WorkingDate();
    	$this->workDate		= $this->date1;
    	$this->date2		= new WorkingDate();

    	if (strlen($value) > 0 && substr($value, 0, 1) == ' ')
        {		// external text format
            if (substr($value, 0, 2) == ' 0' && strlen($value) == 19)
                $value      = substr($value, 1);  // error passing internal
    	    if (strlen($value) == 1)
    	    {
    			$this->date	= '';
    			$this->sortdate	= -99999999;
    			return;
    	    }

    	    // tokenize the input string
    	    // first insert a space into any token that consists of both
    	    // numeric and alphabetic characters.  For example:
    	    // "7th" becomes "7 th".  This supports dates where the input
    	    // was mashed together on input, for example "6Jun1943"
    	    $value	= preg_replace("/([a-zA-Z])([^a-zA-Z])/","$1 $2",
    						       $value);
    	    $value	= preg_replace("/([^a-zA-Z])([a-zA-Z])/","$1 $2",
    						       $value);
    	    $value	= preg_replace("/([\d])([^\d])/","$1 $2",
    						       $value);
    	    $value	= preg_replace("/([^\d])([\d])/","$1 $2",
    						       $value);
    	    if ($debug)
    			$warn	.= '<p>LegacyDate.inc: ' . __LINE__ .
    						   " \$value='$value'</p>\n";
    	    // split into tokens wherever there is one or more spaces
    	    $tokens		= preg_split("/\s+/", $value);

    	    // split tokens that start with a punctuation mark
    	    $tmptokens		        = array();
    	    foreach($tokens as $token)
    	    {			        // loop through tokens
    			if (strlen($token) == 0)
    			    continue;	// can happen in last token
    			while(strlen($token) > 0 && ctype_punct(substr($token, 0, 1)))
    			{
    			    $tmptokens[]	= substr($token, 0, 1);
    			    $token		    = substr($token, 1);
    			}
    			if (strlen($token) > 0)
    			    $tmptokens[]	= $token;
    	    }
    	    $tokens		            = $tmptokens;
    	    if (count($tokens) == 0 ||
    			(count($tokens) == 1 && strlen($tokens[0]) == 0)) 
    	    {		        	// no tokens in value
    			$this->date	        = '';
    			$this->sortdate	    = -99999999;
    			return;
    	    }		        	// no tokens in value

    	    // process the tokens
    	    foreach($tokens as $token)
    	    {		        	// loop through tokens
    			// ignore empty tokens
    			if (strlen($token) == 0)
    			    continue;	// can happen in last token

    			// extract next token
    			if (ctype_digit($token))
    			{		// a numeric token
    			    $tok	= intval($token);
    			}		// a numeric token
    			else
    			if (ctype_punct(substr($token, 0, 1)))
    			{		// a punctuation mark
    			    $tok	= $token;
    			}		// a punctuation mark
    			else
    			if (preg_match(self::$rmPattern, $token) == 1)
    			{		// roman numeral
    			    $tok	= self::romanToInteger($token);
    			}		// roman numeral
    			else
    			{		// word, set up for switching
    			    $tok	= ucfirst(strtolower($token));
    			}		// word

    			// interpret the token based upon the current state
    			$stateName	= $this->state;
    			if ($debug)
    			    $warn	.= '<p>LegacyDate.inc: ' . __LINE__ .
    						   " \$this->$stateName('$tok')</p>\n";
    			$continue	= $this->$stateName($tok);
    	    }			// loop through tokens

    	    // if no tokens encountered empty date
    	    if (($this->prefix == '0') &&
    			($this->state == "doFirstToken"))
    	    {		// still looking for first token
    			$this->date	= '';
    			$this->sortdate	= -99999999;
    			return;
    	    }		// still looking for first token

    	    // if the parse encountered an invalid token
    	    // flag the date as invalid
    	    if (!$continue)
    			$this->prefix	= ':';

    	    // assemble the date from the parts
    	    if (($this->prefix == '0') &&
    			($this->date1->day == 0) &&
    			($this->date1->month == 0) &&
    			($this->date1->year == 0) &&
    			($this->date2->day == 0) &&
    			($this->date2->month == 0) &&
    			($this->date2->year == 0))
    	    {		// no part of the date interpreted
    			$this->date	= ':' . substr($value, 1);
    			$this->sortdate	= -99999999;
    			return;
    	    }		// no part of the date interpreted

    	    // handle years <= 31
    	    if ($this->date1->year == 0 && 
    			$this->date1->month == 0 &&
    			$this->date1->day != 0)
    	    {
    			$this->date1->year	= $this->date1->day;
    			$this->date1->day	= 0;
    	    }

    	    if ($this->date2->year == 0 && 
    			$this->date2->month == 0 &&
    			$this->date2->day != 0)
    	    {
    			$this->date2->year	= $this->date2->day;
    			$this->date2->day	= 0;
    	    } 

    	    if (LegacyDate::$debug)
    	    {		// emit XML for parts of date
    			print "<p>Prefix: {$this->prefix}</p>\n";
    			print "<p>Epoch: {$this->epoch}</p>\n";
    			print "<p>date1->epoch: {$this->date1->epoch}</p>\n";
    			print "<p>date1->day: {$this->date1->day}</p>\n";
    			print "<p>date1->month: {$this->date1->month}</p>\n";
    			print "<p>date1->year: {$this->date1->year}</p>\n";
    			print "<p>date2->epoch: {$this->date2->epoch}</p>\n";
    			print "<p>date2->day: {$this->date2->day}</p>\n";
    			print "<p>date2->month: {$this->date2->month}</p>\n";
    			print "<p>date2->year: {$this->date2->year}</p>\n";
    	    }		// emit XML for parts of date

    	    // handle French republican calendar
    	    if ($this->date1->epoch == 'F')
    			$this->adjustFrDate($this->date1);
    	    if ($this->date2->epoch == 'F')
    			$this->adjustFrDate($this->date2);

    	    // validate order of dates in a range
    	    if ($this->date2->year > 0)
    	    {		// range specified, validate order
    			if ($this->date1->epoch == '0' && $this->date2->epoch == '0')
    			{	// AD-AD range
    			    if ($this->date2->year < $this->date1->year ||
    					($this->date2->year == $this->date1->year &&
    						($this->date2->month < $this->date1->month ||
    						($this->date2->month == $this->date2->month &&
    						 $this->date2->day < $this->date1->day))))
    			    {	// dates out of order
    					$this->msg	.=	
    						"Second date before first date in range. ";
    					$this->prefix	= ':';
    			    }	// dates out of order
    			}	// AD-AD range
    			else
    			if ($this->date1->epoch == '1' && $this->date2->epoch == '1')
    			{	// BC-BC range
    			    if ($this->date2->year > $this->date1->year ||
    					($this->date2->year == $this->date1->year &&
    						($this->date2->month > $this->date1->month ||
    						($this->date2->month == $this->date2->month &&
    						 $this->date2->day > $this->date1->day))))
    			    {	// dates out of order
    					$this->msg	.=	
    						"Second date before first date in range. ";
    					$this->prefix	= ':';
    			    }	// dates out of order
    			}	// BC-BC range
    			else
    			if ($this->date1->epoch == '0' && $this->date2->epoch == '1')
    			{	// AD-BC range
    			    $this->msg	.=	
    						"Second date before first date in range. ";
    					$this->prefix	= ':';
    			}	// AD-BC range
    	    }		// range specified, validate order

    	    // determine epoch indicator for result
    	    $this->epoch	= $this->date1->epoch;
    	    if ($this->epoch == '1' && $this->date2->epoch == '0')
    			$this->date2->month	+= 50;

    	    // append first date of range
    	    $this->date		= $this->prefix . $this->epoch;
    	    if ($this->date1->day < 10)
    			$this->date	.= '0' . $this->date1->day;
    	    else
    			$this->date	.= $this->date1->day;
    	    if ($this->date1->month < 10)
    			$this->date	.= '0' . $this->date1->month;
    	    else
    			$this->date	.= $this->date1->month;
    	    $this->date	.= str_pad($this->date1->year, 4, "0000", STR_PAD_LEFT);

    	    // append second date of range
    	    if ($this->date2->day < 10)
    			$this->date	.= '0' . $this->date2->day;
    	    else
    			$this->date	.= $this->date2->day;
    	    if ($this->date2->month < 10)
    			$this->date	.= '0' . $this->date2->month;
    	    else
    			$this->date	.= $this->date2->month;
    	    $this->date	.= str_pad($this->date2->year, 4, "0000", STR_PAD_LEFT);

    	    // if there was any minor problem in parsing the date
    	    // append the original input text to the internal format
    	    if ($this->prefix == ':')
    			$this->date	.= '\\' . substr($value, 1);

    	    if (LegacyDate::$debug)
    	    {		// emit XML for parts of date
    			print "<date>{$this->date}</date>\n";
    	    }		// emit XML for parts of date

    	    // calculate sort date based on first date of range
    	    if (($this->epoch == '0') || ($this->epoch == ' '))
    			$yearc		= $this->date1->year;
    	    else
    			$yearc		= -$this->date1->year;

    	    if ($this->date1->month == 0)
    	    {			// month not specified
    			if ($this->prefix == '3')	// before
    			    $monthc	= 0;	// before january
    			else
    			if ($this->prefix == '4')	// after
    			    $monthc	= 13;	// after december
    			else
    			    $monthc	= 6;	// June
    	    }			// month not specified
    	    else
    	    {			// month specified
    			$monthc		= $this->date1->month;
    	    }			// month specified

    	    if ($this->date1->day == 0)
    	    {			// day not specified
    			if ($this->prefix == '3')	// before
    			    $dayc	= 0;	// before 1st of month
    			else
    			if ($this->prefix == '4')	// after
    			    $dayc	= 32;	// after end of month
    			else
    			    $dayc	= 15;	// middle of month
    	    }			// day not specified
    	    else
    	    {			// day specified
    			$dayc		= $this->date1->day;
    	    }			// day specified
    	    $this->sortdate	= $yearc * 10000 + $monthc * 100 + $dayc;
    	}			// external format date
    	else
    	if (strlen($value) >= 10)
    	{			// internal format date
    	    $this->date	= $value;
    	    if (is_int(strpos("0123458ghFb[?|", substr($this->date, 0, 1))) && 
    			(strlen($this->date) >= 10))
    	    {			// date includes interpretation
    			$this->epoch		= substr($this->date, 1, 1);
    			$yyyy			= intval(substr($this->date, 6, 4));
    			$mm			= intval(substr($this->date, 4, 2));
    			$dd		  	= intval(substr($this->date, 2, 2));
    			if (($this->epoch != ' ') && ($this->epoch != '0'))
    			{		// BC date
    			    $this->sortdate	= -$yyyy * 10000 + $mm * 100 + $dd;
    			}		// BC date
    			else
    			{		// AD date
    			    $this->sortdate	= $yyyy * 10000 + $mm * 100 + $dd;
    			}		// AD date
    	    }			// date includes interpretation
    	}			// internal format date
    }		// function LegacyDate::__construct

    /********************************************************************
     *	function LegacyDate::badToken									*
     *																	*
     *	This function is called to perform common processing when an	*
     *	unexpected token is received in a state.						*
     *																	*
     *	Input:															*
     *	    $tok		a token extracted from the input string			*
     ********************************************************************/
    private function badToken($tok)
    {
    	$this->msg	.=
    					"Unexpected token '$tok' in state {$this->state}. ";
    }		// function LegacyDate::badToken

    /********************************************************************
     *	function LegacyDate::doFirstToken								*
     *																	*
     *	Handle the first token in a date.								*
     *	Expecting prefix reserved word, or								*
     *	day portion of date (dd mmm yyyy | dd/mm/yyyy) or				*
     *	month portion of date (mmm yyyy | mmm dd, yyyy) or				*
     *	year portion of date (yyyy mmm dd, yyyy/mm/dd) or				*
     *	a quarter date (Qn yyyy)										*
     *																	*
     *	Programming Note applicable to all token handlers:				*
     *		On a parse error, that is an unexpected token for the		*
     *		the current state, append an error message to 				*
     *		$this->msg. You can then abort the remainder of the		    *
     *		parse by returning false.  Alternatively just set		    *
     *		$this->prefix to ':' and break, which permits the		    *
     *		parse to continue.										    *
     *																	*
     *	Input:															*
     *	    $tok		a token extracted from the input string			*
     *																	*
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doFirstToken($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doFirstToken: '$tok'</p>\n";

    	if (is_int($tok) && $tok <= 9999)
    	{	// all numeric token
    	    if ($tok > 31)
    	    {	// too large to be day of month or month
    			$this->workDate->year	= $tok;
    			$this->workDate->month	= 0;
    			$this->workDate->day	= 0;
    			$this->state            = "doExpMonthAfterYear";
    	    }	// too large to be day of month or month
    	    else
    	    {	// year and month yet to collect
    			$this->workDate->day	= $tok;
    			$this->workDate->year	= 0;
    			$this->workDate->month	= 0;
    			$this->state            = "doExpMonth";
    	    }	// year and month yet to collect
    	}	// all numeric
    	else
    	switch($tok)
    	{		// act on text token
    	    case 'In':		// '0',
    	    case 'On':		// '0',
    	    case 'Abt':		// '1',
    	    case 'About':	// '1',
    	    case 'Cir':		// '2',
    	    case 'Circa':	// '2',
    	    case 'Bef':		// '3',
    	    case 'Before':	// '3',
    	    case 'Aft':		// '4',
    	    case 'After':	// '4',
    	    case 'Between':	// '5',
    	    case 'Bet':		// '5',
    	    case 'Est':		// 'g',
    	    case 'Cal':		// 'h',
    	    case 'Calculated':	// 'h',
    	    case 'From':	// 'F',
    	    case 'To':		// 'T',
    	    case '<':		// '<'
    	    {	// reserved word
    			if ($this->prefix != ':')
    			    $this->prefix	= self::$pfxTab[$tok];
    			$this->state            = "doDateFirstToken";
    			break;
    	    }	// reserved word

    	    case 'Wft':
    	    {	// start of compound prefix
    			$this->prefix	= '8';
    			$this->state            = "doExpEst";
    			break;
    	    }	// start of compound prefix

    	    case 'Jan':		// 1,
    	    case 'Jany':	// 1,
    	    case 'January':	// 1,
    	    {	// month name
    			$this->workDate->month	= 1;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Feb':		// 2,
    	    case 'Feby':	// 2,
    	    case 'February':	// 2,
    	    {	// month name
    			$this->workDate->month	= 2;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Mar':		// 3,
    	    case 'March':	// 3,
    	    {	// month name
    			$this->workDate->month	= 3;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Apr':		// 4,
    	    case 'Aprl':	// 4,
    	    case 'April':	// 4,
    	    {	// month name
    			$this->workDate->month	= 4;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'May':		// 5,
    	    {	// month name
    			$this->workDate->month	= 5;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Jun':		// 6,
    	    case 'June':	// 6,
    	    {	// month name
    			$this->workDate->month	= 6;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Jul':		// 7,
    	    case 'July':	// 7,
    	    {	// month name
    			$this->workDate->month	= 7;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Aug':		// 8,
    	    case 'Augt':	// 8,
    	    case 'August':	// 8,
    	    {	// month name
    			$this->workDate->month	= 8;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Sep':		// 9,
    	    case 'Sept':	// 9,
    	    case 'September':	// 9,
    	    {	// month name
    			$this->workDate->month	= 9;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Oct':		// 10,
    	    case 'Octr':	// 10,
    	    case 'October':	// 10,
    	    {	// month name
    			$this->workDate->month	= 10;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Nov':		// 11,
    	    case 'Novr':	// 11,
    	    case 'November':	// 11,
    	    {	// month name
    			$this->workDate->month	= 11;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name7 thermidor xIX bc

    	    case 'Dec':		// 12,
    	    case 'Decr':	// 12,
    	    case 'December':	// 12
    	    {	// month name
    			$this->workDate->month	= 12;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Vendémiaire':
    	    case 'Vendemiaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 1;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Brumaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 2;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Frimaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 3;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Nivôse':
    	    case 'Nivose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 4;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Pluviôse':
    	    case 'Pluviose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 5;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Ventôse':
    	    case 'Ventose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 6;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Germinal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 7;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Floréal':
    	    case 'Floreal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 8;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Prairial':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 9;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Messidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 10;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Thermidor':
    	    case 'Fervidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 11;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Fructidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 12;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Kalendae':
    	    case 'Kalends':
    	    {	// 1st day of month in old Roman calendar
    			$this->workDate->day	= 1;
    			$this->state            = "doExpMonth";
    			break;
    	    }	// 1st day of month

    	    case 'Nonae':
    	    case 'Nones':
    	    {	// Nones in old Roman Calendar
    			$this->workDate->day	= 'nones';
    			$this->state            = "doExpMonth";
    			break;
    	    }	// Nones

    	    case 'Idus':
    	    case 'Ides':
    	    {	// Nones in old Roman Calendar
    			$this->workDate->day	= 'ides';
    			$this->state            = "doExpMonth";
    			break;
    	    }	// Nones


    	    case 'Q':
    	    {	// a quarter
    			$this->prefix	= 'b';
    			$this->state            = "doExpQUARTER";
    			break;
    	    }	// a quarter

    	    case '.':
    	    {		// ignore period
    			break;
    	    }		// ignore period

    	    default:
    	    {	// unexpected
    			// skip over but mark error

    			$this->badToken($tok);
    			$this->prefix	= ':';
    			break;
    	    }	// unexpected
    	}		// act on text token
    	return true;		// continue parsing
    }		// function LegacyDate::doFirstToken

    /********************************************************************
     *	function LegacyDate::doDateFirstToken							*
     *																	*
     *	Handle the first token in a date.								*
     *	Expecting 														*
     *	day portion of date (dd mmm yyyy | dd/mm/yyyy) or				*
     *	month portion of date (mmm yyyy | mmm dd, yyyy) or				*
     *	year portion of date (yyyy mmm dd, yyyy/mm/dd) or				*
     *	a quarter date (Qn yyyy)										*
     *																	*
     *	Input:															*
     *		   $tok		a token extracted from the input string			*
     *																	*
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doDateFirstToken($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doDateFirstToken: '$tok'</p>\n";

    	if (is_int($tok) && $tok <= 9999)
    	{	// all numeric token
    	    if ($tok > 31)
    	    {	// too large to be day of month or month
    			$this->workDate->year	= $tok;
    			$this->workDate->month	= 0;
    			$this->workDate->day	= 0;
    			$this->state            = "doExpMonthAfterYear";
    	    }	// too large to be day of month or month
    	    else
    	    {	// year and month yet to collect
    			$this->workDate->day	= $tok;
    			$this->workDate->year	= 0;
    			$this->workDate->month	= 0;
    			$this->state            = "doExpMonth";
    	    }	// year and month yet to collect
    	}	// all numeric
    	else
    	switch($tok)
    	{		// act on text token
    	    case 'Jan':		// 1,
    	    case 'Jany':	// 1,
    	    case 'January':	// 1,
    	    {	// month name
    			$this->workDate->month	= 1;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Feb':		// 2,
    	    case 'Feby':	// 2,
    	    case 'February':	// 2,
    	    {	// month name
    			$this->workDate->month	= 2;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Mar':		// 3,
    	    case 'March':	// 3,
    	    {	// month name
    			$this->workDate->month	= 3;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Apr':		// 4,
    	    case 'Aprl':	// 4,
    	    case 'April':	// 4,
    	    {	// month name
    			$this->workDate->month	= 4;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'May':		// 5,
    	    {	// month name
    			$this->workDate->month	= 5;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Jun':		// 6,
    	    case 'June':	// 6,
    	    {	// month name
    			$this->workDate->month	= 6;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Jul':		// 7,
    	    case 'July':	// 7,
    	    {	// month name
    			$this->workDate->month	= 7;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Aug':		// 8,
    	    case 'Augt':	// 8,
    	    case 'August':	// 8,
    	    {	// month name
    			$this->workDate->month	= 8;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Sep':		// 9,
    	    case 'Sept':	// 9,
    	    case 'September':	// 9,
    	    {	// month name
    			$this->workDate->month	= 9;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Oct':		// 10,
    	    case 'Octr':	// 10,
    	    case 'October':	// 10,
    	    {	// month name
    			$this->workDate->month	= 10;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Nov':		// 11,
    	    case 'Novr':	// 11,
    	    case 'November':	// 11,
    	    {	// month name
    			$this->workDate->month	= 11;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name7 thermidor xIX bc

    	    case 'Dec':		// 12,
    	    case 'Decr':	// 12,
    	    case 'December':	// 12
    	    {	// month name
    			$this->workDate->month	= 12;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Vendémiaire':
    	    case 'Vendemiaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 1;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Brumaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 2;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Frimaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 3;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Nivôse':
    	    case 'Nivose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 4;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Pluviôse':
    	    case 'Pluviose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 5;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Ventôse':
    	    case 'Ventose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 6;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Germinal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 7;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Floréal':
    	    case 'Floreal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 8;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Prairial':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 9;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Messidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 10;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Thermidor':
    	    case 'Fervidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 11;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Fructidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 12;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Kalendae':
    	    case 'Kalends':
    	    {	// 1st day of month in old Roman calendar
    			$this->workDate->day	= 1;
    			$this->state            = "doExpMonth";
    			break;
    	    }	// 1st day of month

    	    case 'Nonae':
    	    case 'Nones':
    	    {	// Nones in old Roman Calendar
    			$this->workDate->day	= 'nones';
    			$this->state            = "doExpMonth";
    			break;
    	    }	// Nones

    	    case 'Idus':
    	    case 'Ides':
    	    {	// Nones in old Roman Calendar
    			$this->workDate->day	= 'ides';
    			$this->state            = "doExpMonth";
    			break;
    	    }	// Nones


    	    case 'Q':
    	    {	// a quarter
    			$this->prefix	= 'b';
    			$this->state            = "doExpQUARTER";
    			break;
    	    }	// a quarter

    	    case '.':
    	    {		// ignore period
    			break;
    	    }		// ignore period

    	    default:
    	    {	// unexpected
    			// skip over but mark error

    			$this->badToken($tok);
    			$this->prefix	= ':';
    			break;
    	    }	// unexpected
    	}		// act on text token
    	return true;		// continue parsing
    }		// function LegacyDate::doDateFirstToken

    /********************************************************************
     *	function LegacyDate::doExpEst												*
     *																		*
     *		Expecting second part of compound prefix WFT EST				*
     *																		*
     *	Input:																*
     *		    $tok		a token extracted from the input string				*
     *																		*
     *		Returns:														*
     *		    true if the parse is to continue, otherwise false				*
     ********************************************************************/
    private function doExpEst($tok) 
    {		// Expecting second part of compound prefix 
    	if (LegacyDate::$debug)
    	    print "<p>doExpEst: '$tok'</p>\n";
    	if ($tok == 'Est')
    	{	// completion of compound prefix
    	    $this->state            = "doDateFirstToken";
    	}	// completion of compound prefix
    	else
    	{	// unexpected
    	    $this->badToken($tok);
    	    $this->prefix	= ':';
    	}	// unexpected
    	return true;		// continue parsing
    }		// function LegacyDate::doExpEst

    /********************************************************************
     *	function LegacyDate::doExpMonth									*
     *																	*
     *	Expecting month portion of date after day of month				*
     *																	*
     *	Input:															*
     *	    $tok	a token extracted from the input string				*
     *																	*
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpMonth($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doExpMonth: '$tok'</p>\n";

    	if (is_int($tok))
    	{	// all numeric token
    	    $this->badToken($tok);
    	    $this->prefix	= ':';
    	}	// all numeric
    	else
    	switch($tok)
    	{		// act on text token
    	    case 'Jan':		// 1,
    	    case 'Jany':	// 1,
    	    case 'January':	// 1,
    	    {	// month name
    			$this->workDate->month	= 1;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Feb':		// 2,
    	    case 'Feby':	// 2,
    	    case 'February':	// 2,
    	    {	// month name
    			$this->workDate->month	= 2;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Mar':		// 3,
    	    case 'March':	// 3,
    	    {	// month name
    			$this->workDate->month	= 3;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 7;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 15;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Apr':		// 4,
    	    case 'Aprl':	// 4,
    	    case 'April':	// 4,
    	    {	// month name
    			$this->workDate->month	= 4;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'May':		// 5,
    	    {	// month name
    			$this->workDate->month	= 5;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 7;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 15;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Jun':		// 6,
    	    case 'June':	// 6,
    	    {	// month name
    			$this->workDate->month	= 6;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Jul':		// 7,
    	    case 'July':	// 7,
    	    {	// month name
    			$this->workDate->month	= 7;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 7;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 15;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Aug':		// 8,
    	    case 'Augt':	// 8,
    	    case 'August':	// 8,
    	    {	// month name
    			$this->workDate->month	= 8;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Sep':		// 9,
    	    case 'Sept':	// 9,
    	    case 'September':	// 9,
    	    {	// month name
    			$this->workDate->month	= 9;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Oct':		// 10,
    	    case 'Octr':	// 10,
    	    case 'October':	// 10,
    	    {	// month name
    			$this->workDate->month	= 10;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 7;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 15;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Nov':		// 11,
    	    case 'Novr':	// 11,
    	    case 'November':	// 11,
    	    {	// month name
    			$this->workDate->month	= 11;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Dec':		// 12,
    	    case 'Decr':	// 12,
    	    case 'December':	// 12
    	    {	// month name
    			$this->workDate->month	= 12;
    			if ($this->workDate->day == 'nones')
    			    $this->workDate->day	= 5;
    			else
    			if ($this->workDate->day == 'ides')
    			    $this->workDate->day	= 13;
    			$this->state            = "doExpYear";
    			break;
    	    }	// month name

    	    case 'Vendémiaire':
    	    case 'Vendemiaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 1;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Brumaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 2;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Frimaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 3;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Nivôse':
    	    case 'Nivose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 4;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Pluviôse':
    	    case 'Pluviose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 5;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Ventôse':
    	    case 'Ventose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 6;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Germinal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 7;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Floréal':
    	    case 'Floreal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 8;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Prairial':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 9;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Messidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 10;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Thermidor':
    	    case 'Fervidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 11;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Fructidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 12;
    			$this->state            = "doExpYear";
    			break;
    	    }	// French republican month name

    	    case 'Q':
    	    {	// a quarter
    			$this->prefix		= 'b';
    			$this->workDate->month	= $this->workDate->day * 3;
    			$this->workDate->day	= 0;
    			$this->state            = "doExpYear";
    			break;
    	    }	// a quarter

    	    case '/':
    	    {	// separator in all numeric date
    			$this->state            = "doExpMonthNum";
    			break;
    	    }	// separator in all numeric date

    	    case '?':
    	    {	// questionable date
    			$this->prefix		= '?';
    			break;
    	    }	// questionable date

    	    case '>':
    	    {	// uncertain date
    			if ($this->prefix == '<')
    			    $this->state            = "doExpEnd";
    			else
    			{
    			    $this->badToken($tok);
    			    $this->prefix	= ':';
    			}

    			break;
    	    }	// uncertain date

    	    case '-':
    	    case 'To':
    	    case 'And':
    	    {		// end of simple range
    			if ($this->workDate === $this->date1)
    			{	// working on first date of range
    			    if ($this->prefix == '0')
    					$this->prefix	= 'M';
    			    else
    			    if ($this->prefix == 'F')
    					$this->prefix	= '0';
    			    else
    					$this->badToken($tok);

    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// working on first date of range
    			else
    			{
    			    $this->badToken($tok);
    			    $this->prefix == ':';
    			}
    			break;
    	    }		// end of simple range

    	    case '|': 
    	    case  'Or':
    	    {		// choice
    			if ($this->workDate === $this->date1) 
    			{
    			    if ($this->prefix == '0')
    					$this->prefix	= '|';
    			    else
    					$this->badToken($tok);

    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}
    			else
    			{
    			    $this->badToken($tok);
    			    $this->prefix == ':';
    			}
    			break;
    	    }		// choice

    	    case 'Th':
    	    case 'St':
    	    case 'Rd': 
    	    case 'Nd': 
    	    case '.': 
    	    {		// ordinal suffices on day number
    			// skipped, state remains unchanged
    			break;
    	    }		// ordinal suffices

    	    case 'Bc': 
    	    case 'Bce': 
    	    {			// proleptic Julian Calendar Before Common Era
    			$this->workDate->epoch	= '1';
    			// state remains unchanged
    			break;
    	    }			// proleptic Julian Calendar Before Common Era

    	    case 'Ad': 
    	    case 'Ce': 
    	    case 'Ns':
    	    {			// Gregorian Calendar Common Era
    			$this->workDate->epoch	= '0';
    			// state remains unchanged
    			break;
    	    }			// Gregorian Calendar Common Era

    	    case 'Os': 
    	    {			// Old Style (Julian and year starts 25 Mar)
    			$this->adjustOsDate($this->workDate);
    			// state remains unchanged
    			break;
    	    }			// old style date

    	    case 'Auc':
    	    {			// Ab Urbe Condita date
    			$this->adjustAucDate($this->workDate, true);
    			// state remains unchanged
    			break;
    	    }			// Ab Urbe Condita date

    	    default:
    	    {
    			$this->badToken($tok);
    			$this->prefix == ':';
    	    }
    	}		// act on text token
    	return true;
    }		// function LegacyDate::doExpMonth

    /********************************************************************
     *	function LegacyDate::doExpMonthAfterYear						*
     *                                                                  *
     *		Expecting month portion of date after year						*
     *                                                                  *
     *	Input:																*
     *		    $tok		a token extracted from the input string				*
     *                                                                  *
     *		Returns:														*
     *		    true if the parse is to continue, otherwise false				*
     ********************************************************************/
    private function doExpMonthAfterYear($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doExpMonthAfterYear: '$tok'</p>\n";

    	if (is_int($tok))
    	{	// numeric month
    	    $this->workDate->month	= $tok;
    	    $this->state            = "doExpDay";
    	}	// all numeric
    	else
    	switch($tok)
    	{		// act on text token
    	    case 'Jan':		// 1,
    	    case 'Jany':	// 1,
    	    case 'January':	// 1,
    	    {	// month name
    			$this->workDate->month	= 1;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Feb':		// 2,
    	    case 'Feby':	// 2,
    	    case 'February':	// 2,
    	    {	// month name
    			$this->workDate->month	= 2;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Mar':		// 3,
    	    case 'March':	// 3,
    	    {	// month name
    			$this->workDate->month	= 3;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Apr':		// 4,
    	    case 'Aprl':	// 4,
    	    case 'April':	// 4,
    	    {	// month name
    			$this->workDate->month	= 4;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'May':		// 5,
    	    {	// month name
    			$this->workDate->month	= 5;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Jun':		// 6,
    	    case 'June':	// 6,
    	    {	// month name
    			$this->workDate->month	= 6;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Jul':		// 7,
    	    case 'July':	// 7,
    	    {	// month name
    			$this->workDate->month	= 7;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Aug':		// 8,
    	    case 'Augt':	// 8,
    	    case 'August':	// 8,
    	    {	// month name
    			$this->workDate->month	= 8;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Sep':		// 9,
    	    case 'Sept':	// 9,
    	    case 'September':	// 9,
    	    {	// month name
    			$this->workDate->month	= 9;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Oct':		// 10,
    	    case 'Octr':	// 10,
    	    case 'October':	// 10,
    	    {	// month name
    			$this->workDate->month	= 10;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Nov':		// 11,
    	    case 'Novr':	// 11,
    	    case 'November':	// 11,
    	    {	// month name
    			$this->workDate->month	= 11;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Dec':		// 12,
    	    case 'Decr':	// 12,
    	    case 'December':	// 12
    	    {	// month name
    			$this->workDate->month	= 12;
    			$this->state            = "doExpDay";
    			break;
    	    }	// month name

    	    case 'Vendémiaire':
    	    case 'Vendemiaire':
    	    {	// French republican month name
    			$this->workdate->epoch	= 'F';
    			$this->workDate->month	= 1;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Brumaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 2;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Frimaire':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 3;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Nivôse':
    	    case 'Nivose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 4;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Pluviôse':
    	    case 'Pluviose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 5;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Ventôse':
    	    case 'Ventose':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 6;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Germinal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 7;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Floréal':
    	    case 'Floreal':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 8;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Prairial':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 9;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Messidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 10;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Thermidor':
    	    case 'Fervidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 11;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case 'Fructidor':
    	    {	// French republican month name
    			$this->workDate->epoch	= 'F';
    			$this->workDate->month	= 12;
    			$this->state            = "doExpDay";
    			break;
    	    }	// French republican month name

    	    case '/':
    	    {	// separator in all numeric date
    			$this->state            = "doExpMonthAfterYear";
    			break;
    	    }	// separator in all numeric date

    	    case '?':
    	    {	// questionable date
    			$this->prefix	= '?';
    			break;
    	    }	// questionable date

    	    case '>':
    	    {	// uncertain date
    			if ($this->prefix == '<')
    			    $this->state            = "doExpEnd";
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}

    			break;
    	    }	// uncertain date

    	    case '-':
    	    case 'To':
    	    case 'And':
    	    {		// end of simple range
    			if ($this->workDate === $this->date1)
    			{	// end of first date
    			    if ($this->prefix == '0')
    					$this->prefix	= 'M';
    			    else
    			    if ($this->prefix == 'F')
    					$this->prefix	= '0';
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// end of first date
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    			break;
    	    }		// end of simple range

    	    case '|': 
    	    case  'Or':
    	    {		// choice
    			if (($this->workDate === $this->date1) && 
    			    ($this->prefix == '0'))
    			{
    			    $this->prefix	= '|';
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// date or date
    			else
    			{
    			    $this->badToken($tok);
    			    $this->prefix	= ':';
    			}
    			break;
    	    }		// choice

    	    case 'Bc': 
    	    case 'Bce': 
    	    {			// proleptic Julian Calendar Before Common Era
    			$this->workDate->epoch	= '1';
    			// state remains unchanged
    			break;
    	    }			// proleptic Julian Calendar Before Common Era

    	    case 'Ad': 
    	    case 'Ce': 
    	    case 'Ns':
    	    {			// Gregorian Calendar Common Era
    			$this->workDate->epoch	= '0';
    			// state remains unchanged
    			break;
    	    }			// Gregorian Calendar Common Era

    	    case 'Os': 
    	    {			// Old Style (Julian and year starts 25 Mar)
    			$this->adjustOsDate($this->workDate);
    			// state remains unchanged
    			break;
    	    }			// old style date

    	    case 'Auc':
    	    {			// Ab Urbe Condita date
    			$this->adjustAucDate($this->workDate, true);
    			// state remains unchanged
    			break;
    	    }			// Ab Urbe Condita date

    	    default:
    	    {
    			$this->badToken($tok);
    			$this->prefix		= ':';
    			$this->state            = "doExpDay";
    	    }
    	}		// act on text token
    	return true;
    }		// function LegacyDate::doExpMonthAfterYear

    /********************************************************************
     *	function LegacyDate::doExpQUARTER								*
     *                                                                  *
     *	Expecting a quarter number (1,2,3,4)								*
     *                                                                  *
     *	Input:																*
     *		    $tok		a token extracted from the input string				*
     *                                                                  *
     *		Returns:														*
     *		    true if the parse is to continue, otherwise false				*
     ********************************************************************/
    private function doExpQUARTER($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>EXP_QUARTER: $tok</p>\n";
    	if (is_int($tok))
    	{	// number from 1 to 4
    	    if ($tok > 0 && $tok <= 4)
    	    {
    			$this->workDate->month	= $tok * 3;
    			$this->workDate->day	= 0;
    	    }
    	    else
    	    {
    			$this->badToken($tok);
    			$this->prefix		= ':';
    	    }
    	    $this->state            = "doExpYear";
    	}	// all numeric
    	else
    	{
    	    $this->badToken($tok);
    	    return false;
    	}
    	return true;
    }		// function LegacyDate::doExpQUARTER

    /********************************************************************
     *	function LegacyDate::doExpYear									*
     *                                                                  *
     *	Expecting year portion of date  							    *
     *                                                                  *
     *	Input:															*
     *	    $tok	a token extracted from the input string				*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpYear($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doExpYear: '$tok'</p>\n";
    	if (is_int($tok))
    	{	// all numeric
    	    $this->workDate->year	= $tok;
    	    $this->state            = "doExpSuffix";
    	}	// all numeric
    	else
    	switch($tok)
    	{	// act on text token
    	    case '?':
    	    {
    			$this->prefix		= '?';
    			break;
    	    }

    	    case '>':
    	    {
    			if ($this->prefix == '<')
    			    $this->state            = "doExpEnd";
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    	    }

    	    case '-':
    	    {		// end of simple range
    			if ($this->workDate === $this->date1)
    			{	// working on first date
    			    if ($this->prefix == '0')
    			    {
    					$this->prefix	= 'M';
    			    // look for end date of range
    					$this->workDate	= $this->date2;
    					$this->state            = "doDateFirstToken";
    			    }
    			    else
    			    if ($this->prefix == 'F')
    			    {
    					$this->prefix	= '0';
    			    // look for end date of range
    					$this->workDate	= $this->date2;
    					$this->state            = "doDateFirstToken";
    			    }
    			    else
    			    if ($this->prefix == '5')
    			    {
    			    // look for end date of range
    					$this->workDate	= $this->date2;
    					$this->state            = "doDateFirstToken";
    			    }
    			    else
    			    {
    					$this->badToken($tok);
    					return false;
    			    }
    			}	// working on first date
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    	    }		// end of simple range

    	    case 'To':
    	    {		// end of simple range
    			if (($this->workDate === $this->date1) && 
    			    ($this->prefix == '0' || $this->prefix == 'F'))
    			{	// there was no prefix or From prefix
    			    $this->prefix	= 'M';
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// there was no prefix or From prefix
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    	    }		// end of simple range

    	    case 'And':
    	    {		// end of beween range
    			if (($this->workDate === $this->date1) && 
    			    ($this->prefix == '5'))
    			{	// between date and date
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// between date - date
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    	    }		// end of between range

    	    case '|':
    	    case 'Or':
    	    {		// choice
    			if (($this->workDate === $this->date1) && 
    			    ($this->prefix == '0'))
    			{	// date1 or date2
    			    $this->prefix	= '|';
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// date1 or date 2
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    	    }		// choice

    	    case 'Bc': 
    	    case 'Bce': 
    	    {			// proleptic Julian Calendar Before Common Era
    			$this->workDate->year	= $this->workDate->day;
    			$this->workDate->day	= 0;
    			$this->workDate->epoch	= '1';
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// proleptic Julian Calendar Before Common Era

    	    case 'Ad':
    	    case 'Ce':
    	    case 'Ns':
    	    {			// Gregorian Calendar Common Era
    			$this->workDate->year	= $this->workDate->day;
    			$this->workDate->day	= 0;
    			$this->workDate->epoch	= '0';
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Gregorian Calendar Common Era

    	    case 'Os': 
    	    {			// Old Style (Julian and year starts 25 Mar)
    			$this->workDate->year	= $this->workDate->day;
    			$this->workDate->day	= 0;
    			$this->adjustOsDate($this->workDate);
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Old Style (Julian and year starts 25 Mar)

    	    case 'Auc':
    	    {			// Ab Urbe Condita date
    			$this->workDate->year	= $this->workDate->day;
    			$this->workDate->day	= 0;
    			$this->adjustAucDate($this->workDate, true);
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Ab Urbe Condita date

            case 'An':
            {           // permitted French prefix
                break;
            }           // permitted French prefix

    	    default:
    	    {	// unexpected
    			$this->badToken($tok);
    			$this->prefix	= ':';
    	    }	// unexpected
    	}	// act on text token
    	return true;
    }		// function LegacyDate::doExpYear

    /********************************************************************
     *	function LegacyDate::doExpDay									*
     *                                                                  *
     *	Expecting day portion of date after month 						*
     *                                                                  *
     *	Input:															*
     *	    $tok	a token extracted from the input string				*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpDay($tok)
    {
    	if (LegacyDate::$debug) 
    	    print "<p>doExpDay: $tok</p>\n";
    	if (is_int($tok))
    	{	// all numeric
    	    $this->workDate->day	= $tok;
    	    if ($this->workDate->day > 31)
    	    {		// year
    			$this->badToken($tok);
    			return false;
    	    }		// year
    	    else
    	    {		// day of month
    			$this->state            = "doExpSuffix";
    	    }		// day of month
    	}	// all numeric
    	else
    	switch($tok)
    	{	// act on text token
    	    case '>':
    	    {
    			if ($this->prefix == '<')
    			    $this->state            = "doExpEnd";
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    	    }

    	    case 'Bc': 
    	    case 'Bce': 
    	    {			// proleptic Julian Calendar Before Common Era
    			$this->workDate->epoch	= '1';
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// proleptic Julian Calendar Before Common Era

    	    case 'Ad':
    	    case 'Ce':
    	    case 'Ns':
    	    {			// Gregorian Calendar Common Era
    			$this->workDate->epoch	= '0';
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Gregorian Calendar Common Era

    	    case 'Os': 
    	    {			// Old Style (Julian and year starts 25 Mar)
    			$this->adjustOsDate($this->workDate);
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Old Style (Julian and year starts 25 Mar)

    	    case 'Auc':
    	    {			// Ab Urbe Condita date
    			$this->adjustAucDate($this->workDate, true);
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Ab Urbe Condita date

    	    case '/':
    	    {			// separator in all numeric date
    			break;
    	    }			// separator in all numeric date

    	    default:
    	    {	// unexpected
    			$this->badToken($tok);
    			$this->prefix	= ':';
    	    }	// unexpected
    	}	// act on text token
    	return true;
    }		// function LegacyDate::doExpDay

    /********************************************************************
     *	function LegacyDate::doExpSuffix								*
     *                                                                  *
     *	Have accumulated a possibly complete date.						*
     *	Expect last part of date or date suffix.						*
     *                                                                  *
     *	Input:															*
     *	    $tok	a token extracted from the input string				*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpSuffix($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doExpSuffix: $tok</p>\n";
    	if (is_int($tok))
    	{	// all numeric
    	    if ($this->workDate->day == 0 && $this->workDate->year <= 31)
    	    {		// must be year
    			$this->workDate->day	= $this->workDate->year;
    			$this->workDate->year	= $tok;
    			switch($this->prefix)
    			{
    			    case ':':
    			    case '1':
    			    case '2':
    			    case '3':
    			    case '4':
    			    case '8':
    			    case 'g':
    			    case 'h':
    			    case 'T':
    			    case 'b':
    			    case '<':
    			    case '[':
    			    case '?':
    			    {		// formats that do not expect second date
    					$this->state            = "doExpEnd";
    					return true;
    			    }		// formats that do not expect second date

    			    default:
    			    {		// look for second date
    					$this->state            = "doExpSeparator";
    					return true;
    			    }		// look for second date
    			}
    	    }		// year
    	    else
    	    {		// already have year
    			$this->badToken($tok);
    			return false;
    	    }		// already have year
    	}		// all numeric token
    	else
    	switch($tok)
    	{		// act on text token
    	    case ',':
    	    {		// ignore comma between dates
    			break;
    	    }		// ignore comma between dates

    	    case '>':
    	    {
    			if ($this->prefix == '<')
    			    $this->state            = "doExpEnd";
    			else
    			{
    			    $this->badToken($tok);
    			    return false;
    			}
    			break;
    	    }

    	    case '-':
    	    {		// end of simple range
    			if ($this->workDate === $this->date1)
    			{	// working on first date
    			    if ($this->prefix == '0' || $this->prefix == 'F')
    			    {	// date1 - date2 or From date1 - date2
    					$this->prefix	= 'M';
    			    }	// date1 - date2 or From date1 - date2
    			    else
    			    if ($this->prefix == '5')
    			    {	// between date - date
    			    }	// between date - date
    			    else
    			    {	// unexpected token
    					$this->badToken($tok);
    			    }	// unexpected

    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// working on first date
    			else
    			{	// unexpected token after second date
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }		// end of simple range

    	    case 'To':
    	    {		// end of simple range
    			if ($this->workDate === $this->date1) 
    			{	// From date1 to date2
    			    if ($this->prefix == '0' || $this->prefix == 'F')
    					$this->prefix	= 'M';
    			    else
    					$this->badToken($tok);
    			}	// From date1 to date2
    			else
    			{	// unexpected token after second date
    			    $this->badToken($tok);
    			}	// unexpected
    			// look for end date of range
    			$this->workDate	= $this->date2;
    			$this->state            = "doDateFirstToken";
    			break;
    	    }		// end of simple range

    	    case 'And':
    	    {		// end of between range
    			if ($this->workDate === $this->date1) 
    			{	// between date and date
    			    if ($this->prefix != '5')
    					$this->badToken($tok);
    			}	// between date and date
    			else
    			{	// unexpected token after second date
    			    $this->badToken($tok);
    			}	// unexpected

    			// look for end date of range
    			$this->workDate	= $this->date2;
    			$this->state            = "doDateFirstToken";
    			break;
    	    }		// end of between range

    	    case '?':
    	    {
    			$this->prefix	= '?';
    			break;
    	    }

    	    case '|':
    	    case 'Or':
    	    {		// choice
    			if ($this->workDate === $this->date1) 
    			{	// at end of first date
    			    if ($this->prefix == '0')
    					$this->prefix	= '|';
    			    else
    					$this->badToken($tok);

    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// at end of first date
    			else
    			{	// unexpected token after second date
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }		// choice

    	    case 'Th':
    	    case 'St':
    	    case 'Rd':
    	    case 'Nd': 
    	    case '.': 
    	    {		// ordinal suffices
    			// state remains unchanged
    			break;
    	    }		// ordinal suffices

    	    case 'Bc': 
    	    case 'Bce': 
    	    {			// proleptic Julian Calendar Before Common Era
    			$this->workDate->epoch	= '1';
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// proleptic Julian Calendar Before Common Era

    	    case 'Ad':
    	    case 'Ce':
    	    case 'Ns':
    	    {			// Gregorian Calendar Common Era
    			$this->workDate->epoch	= '0';
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Gregorian Calendar Common Era

    	    case 'Os': 
    	    {			// Old Style (Julian and year starts 25 Mar)
    			$this->adjustOsDate($this->workDate);
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Old Style (Julian and year starts 25 Mar)

    	    case 'Auc':
    	    {			// Ab Urbe Condita date
    			$this->adjustAucDate($this->workDate, true);
    			$this->state            = "doExpSeparator";
    			break;
    	    }			// Ab Urbe Condita date

    	    default:
    	    {	// unexpected token
    			$this->badToken($tok);
    			return false;
    	    }	// unexpected
    	}		// act on text token
    	return true;
    }		// function LegacyDate::doExpSuffix

    /********************************************************************
     *	function LegacyDate::doExpSeparator								*
     *                                                                  *
     *	Expecting separator between two dates							*
     *                                                                  *
     *	Input:															*
     *	    $tok	a token extracted from the input string				*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpSeparator($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doExpSeparator: $tok</p>\n";

    	if (is_int($tok))
    	{	// all numeric
    	    $this->badToken($tok);
    	}	// all numeric
    	else
    	switch($tok)
    	{	// act on text token
    	    case '-':
    	    {		// end of simple or between range
    			if ($this->workDate === $this->date1) 
    			{	// working on first date
    			    if ($this->prefix == '0'|| $this->prefix == 'F')
    			    {	// from to range
    					$this->prefix	= 'M';
    			    }	// from to range
    			    else
    			    if ($this->prefix == '5')
    			    {	// between date - date
    			    }	// between date - date
    			    else
    					$this->badToken($tok);

    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// working on first date
    			else
    			{	// unexpected after second date
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }		// end of simple or between range

    	    case 'To':
    	    {		// end of simple range
    			if ($this->workDate === $this->date1)
    			{	// working on first date of range
    			    if ($this->prefix == '0'|| $this->prefix == 'F')
    					$this->prefix	= 'M';
    			    else
    					$this->badToken($tok);
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// working on first date of range
    			else
    			{	// unexpected after second date
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }		// end of simple range

    	    case 'And':
    	    {		// end of between range
    			if ($this->workDate === $this->date1)
    			{
    			    if ($this->prefix != '5')
    					$this->badToken($tok);

    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}
    			else
    			{	// unexpected after second date
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }		// end of between range

    	    case '?':
    	    {
    			$this->prefix	= '?';
    			break;
    	    }

    	    case '>':
    	    {
    			if ($this->prefix == '<')
    			    $this->state            = "doExpEnd";
    			else
    			{	// unexpected
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }

    	    case '|':
    	    case 'Or':
    	    {		// choice
    			if ($this->workDate === $this->date1)
    			{	// working on first date
    			    if ($this->prefix == '0')
        				$this->prefix	= '|';
    			    else
    	    			$this->badToken($tok);
    			    // look for end date of range
    			    $this->workDate	= $this->date2;
    			    $this->state            = "doDateFirstToken";
    			}	// no prefix
    			else
    			{	// unexpected
    			    $this->badToken($tok);
    			    return false;
    			}	// unexpected
    			break;
    	    }		// choice

    	    case 'Bc': 
    	    case 'Bce': 
    	    {			// proleptic Julian Calendar Before Common Era
    	    	$this->workDate->epoch	= '1';
    		break;
    	    }			// proleptic Julian Calendar Before Common Era

    	    case 'Ad':
    	    case 'Ce':
    	    case 'Ns':
    	    {			// Gregorian Calendar Common Era
    		    $this->workDate->epoch	= '0';
    		    break;
    	    }			// Gregorian Calendar Common Era

    	    case 'Os': 
    	    {			// Old Style (Julian and year starts 25 Mar)
    		    $this->adjustOsDate($this->workDate);
    		    break;
    	    }			// Old Style (Julian and year starts 25 Mar)

    	    case 'Auc':
    	    {			// Ab Urbe Condita date
    		    $this->adjustAucDate($this->workDate, true);
    		    break;
    	    }			// Ab Urbe Condita date

    	    default:
    	    {	// unexpected
    	    	$this->badToken($tok);
    		    return false;
    	    }	// unexpected
    	}	// act on text token
    	return true;
    }		// function LegacyDate::doExpSeparator

    /********************************************************************
     *	function LegacyDate::doExpMonthNum								*
     *                                                                  *
     *	Expecting second number in all numeric date dd/mm/yyyy			*
     *                                                                  *
     *	Input:															*
     *	    $tok		a token extracted from the input string			*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpMonthNum($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>EXP_MonthNum: '$tok'</p>\n";
    	if (is_int($tok))
    	{	// all numeric token
    	    $this->date1->month	= $tok;
    	    $this->state            = "doExpMonthYearSep";
    	}	// all numeric token
    	else
    	{
    	    $this->badToken($tok);
    	    return false;
    	}
    	return true;
    }		// function LegacyDate::doExpMonthNum

    /********************************************************************
     *	function LegacyDate::doExpMonthYearSep							*
     *                                                                  *
     *	Expecting separator between month number and year number		*
     *	in all numeric date dd/mm/yyyy.									*
     *                                                                  *
     *	Input:															*
     *	    $tok		a token extracted from the input string			*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpMonthYearSep($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>EXP_MonthYearSep: '$tok' num=" .
    			is_int($tok) . "</p>\n";
    	if ($tok == '/')
    	{
    	    $this->state            = "doExpYearNum";
    	}
    	else
    	{
    	    $this->badToken($tok);
    	    return false;
    	}
    	return true;
    }		// function LegacyDate::doExpMonthYearSep

    /********************************************************************
     *	function LegacyDate::doExpYearNum								*
     *                                                                  *
     *	Expecting third number in all numeric date dd/mm/yyyy			*
     *                                                                  *
     *	Input:															*
     *	    $tok		a token extracted from the input string			*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpYearNum($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>doExpYearNum: '$tok'</p>\n";

    	if (is_int($tok))
    	{	// all numeric token
    	    if ($this->workDate->day > 31)
    	    {		// 1st number is year yyyy/mm/dd
    		$this->workDate->year	= $this->workDate->day;
    		$this->workDate->day	= $tok;
    	    }		// 1st number is year
    	    else
    	    if ($this->workDate->month > 12)
    	    {		// 2nd number is day mm/dd/yyyy
    		$this->workDate->year	= $tok;
    		$tmp			= $this->workDate->day;
    		$this->workDate->day	= $this->workDate->month;
    		$this->workDate->month	= $tmp;
    	    }		// 2nd number is day
    	    else	// dd/mm/yyyy
    		$this->workDate->year	= $tok;
    	    $this->state            = "doExpSuffix";
    	}	// all numeric token
    	else
    	{	// text token
    	    $this->badToken($tok);
    	    return false;
    	}	// text token
    	return true;
    }		// function LegacyDate::doExpYearNum

    /********************************************************************
     *	function LegacyDate::doExpEnd									*
     *                                                                  *
     *	Complete valid date parsed.  There should be no further tokens.	*
     *                                                                  *
     *	Input:															*
     *	    $tok		a token extracted from the input string			*
     *                                                                  *
     *	Returns:														*
     *	    true if the parse is to continue, otherwise false			*
     ********************************************************************/
    private function doExpEnd($tok)
    {
    	if (LegacyDate::$debug)
    	    print "<p>EXP_END: '$tok'</p>\n";
    	$this->badToken($tok);
    	$this->prefix	= ':';		// flag problem
    	return true;			// continue parse
    }		// function LegacyDate::doExpEnd

    /********************************************************************
     *  function LegacyDate::adjustOsDate								*
     *                                                                  *
     *  Given an old-style date (Julian Calendar with year start		*
     *	25th March, the Feast of the Annunciation aka "Lady Day")		*
     *	calculate the corresponding new style (Gregorian) date.			*
     *                                                                  *
     *	Input:															*
     *	    $date		instance of WorkingDate							*
     *                                                                  *
     *	Returns															*
     *	    updated instance of WorkingDate								*
     ********************************************************************/
    private function adjustOsDate($date)
    {
    	// get values from specified date
    	$this->epoch	= '0';	// always AD
    	$day		= $date->day;
    	$month		= $date->month;
    	$year		= $date->year;
    	// print "<p>adjustOsDate($position): \$day=$day, \$month=$month, \$year=$year</p>\n";
    	// only change if valid OS date
    	if ($month == 0 || $day == 0 || $year < 1582)
    	    return $date;

    	// in Old Style year started 25th March
    	if ($month < 3 || ($month == 3 && $day < 25))
    	    $year++;

    	// Gregorian correction is number of excess leap days in Julian
    	$gcorr	= floor($year/100) - floor($year/400) - 2;

    	// advance day of month
    	$day	+= $gcorr;
    	if (($year % 4) == 0 && $month == 2)
    	    $leapDay	= 1;
    	else
    	    $leapDay	= 0;

    	// check for overflow into following month
    	if ($day > (self::$monthLens[$month] + $leapDay))
    	{	// new style day of month > days in current month
    	    $day		-= self::$monthLens[$month] +$leapDay;
    	    $month		++;
    	    if ($month > 12)
    	    {	// overflow into following year
    		$month	-= 12;
    		$year	++;
    	    }	// overflow into following year
    	}	// new style day of month > days in current month

    	// return values in specified date
    	$date->day	= $day;
    	$date->month	= $month;
    	$date->year	= $year;
    	return $date;
    }		// function LegacyDate::adjustOsDate

    /********************************************************************
     *  function LegacyDate::adjustAucDate								*
     *                                                                  *
     *  Given an Ab Urbe Condita date									*
     *	calculate the corresponding Christian Era date					*
     *                                                                  *
     *	Input:															*
     *	    $date		instance of WorkingDate							*
     ********************************************************************/
    private function adjustAucDate($date)
    {
    	if ($date->year < 757)
    	{	// convert to BC date
    	    $date->year		= 757 - $date->year;
    	    $date->epoch	= '1';
    	}	// convert to BC date
    	else
    	{	// convert to AD date
    	    $date->year		= $date->year - 756;
    	    $date->epoch	= '0';
    	}	// convert to AD date
    }		// function LegacyDate::adjustAucDate

    /********************************************************************
     *  function LegacyDate::adjustFrDate								*
     *                                                                  *
     *  Given a French republican calendar date							*
     *	calculate the corresponding Christian Era date					*
     *                                                                  *
     *	Input:															*
     *	    $date		instance of WorkingDate							*
     ********************************************************************/
    private function adjustFrDate($date)
    {
    	// calculate the number of leap days in all the years preceding
    	// the current year.  The first rule reflects that in the actual
    	// operating calendar the leap day occurred at the end of years
    	// 3, 7, and 11, and would have occurred at the end of year 15.
    	// The second hypothetical formula simplifies the conversion between
    	// the calendars by adding the leap day in the same year as the
    	// Gregorian calendar
    	if ($date->year < 16)
    	    $leapDays	= floor($date->year / 4);
    	else
    	    $leapDays	= floor(($date->year - 1) / 4) -
            			  floor(($date->year - 9) / 100) +
    		        	  floor(($date->year + 191) / 400);
    	$julianDate	= 2375839 + 365 * ($date->year - 1) +
    				    $leapDays +
    				    30 * ($date->month - 1) +
    				    $date->day; 
    	$date->epoch	= '0';

    	// convert integer portion of Julian Date to
    	// year, month and day of month in Gregorian Calendar
    	// Gregorian century
    	$Z	= $julianDate + 0.5;	// convert to astronomical form
    	$Gint   = floor(($julianDate-1867216.25)/36524.25);
    	// adjust by Gregorian correction
    	$Aint   = $julianDate + 1 + $Gint - floor($Gint/4);
    	$Bint   = $Aint + 1524;
    	// year starting at March
    	$Cint   = floor(($Bint - 122.1)/365.25);
    	$Year   = $Cint - 4716;
    	// Julian Day of March 0 of current year
    	$Dint   = floor(365.25 * $Cint);
    	// approximation to month number
    	$Month  = floor(($Bint - $Dint)/30.6001);
    	// day of month
    	$date->day    = floor($Bint - $Dint - floor(30.6001*$Month) + 0.5);
    	if ($Month < 14)
    	    $date->month	= $Month - 1;
    	else
    	    $date->month 	= $Month - 13;
    	if ($date->month < 3)
    	    $date->year 	= $Year + 1;
    	else
    	    $date->year		= $Year;
    }		// function LegacyDate::adjustFrDate

/**
 * @author Elad Yosifon <elad.yosifon@gmail.com>
 */
    private static	$roman_to_decimal = array(
    								'I' => 1,
    								'V' => 5,
    								'X' => 10,
    								'L' => 50,
    								'C' => 100,
    								'D' => 500,
    								'M' => 1000);

    /********************************************************************
     *  function LegacyDate:romanToInteger								*
     *                                                                  *
     *  Given a valid roman numeral this computes the decimal value.	*
     *  @author Elad Yosifon <elad.yosifon@gmail.com>					*
     *                                                                  *
     *	Input:															*
     *	    $roman		a valid number in roman numerals 				*
     *                                                                  *
     *  Returns:                                                        *
     *      integer numeric value.										*
     ********************************************************************/
    static function romanToInteger($roman)
    {
    	// breaks the string into an array of chars
    	$roman		= strtoupper($roman);
    	$digits		= str_split($roman);
    	$lastIndex	= count($digits)-1;
    	$sum		= 0;

    	foreach($digits as $index => $digit)
    	{
    	    if(!isset($digits[$index]))
    	    {
    		continue;
    	    }

    	    if(isset(self::$roman_to_decimal[$digit]))
    	    {
    		if($index < $lastIndex)
    		{
    		    $left	= self::$roman_to_decimal[$digits[$index]];
    		    $right	= self::$roman_to_decimal[$digits[$index+1]];
    		    if($left < $right)
    		    {
    			$sum	+= ($right - $left);
    			unset($digits[$index+1],$left, $right);
    			continue;
    		    }
    		    unset($left, $right);
    		}
    	    }
    	    $sum += self::$roman_to_decimal[$digit];
    	}

    	return $sum;
    }		// function LegacyDate::romanToInteger

    /********************************************************************
     *	function LegacyDate::getDay										*
     *                                                                  *
     *  Extract the day portion of a Legacy internal date.				*
     *                                                                  *
     *  Returns:														*
     *		day of month if specified, otherwise 15						*
     ********************************************************************/
    function getDay()
    {
    	if ($this->sortdate == -99999999)
    	    return 15;
    	return $this->sortdate % 100;
    }		// function LegacyDate::getDay

    /********************************************************************
     *	function LegacyDate::getActualDay								*
     *                                                                  *
     *  Extract the numeric interpretation of the day in the date.      *
     *                                                                  *
     *  Returns:														*
     *		month number in year (Jan = 1) if present, otherwise 0		*
     ********************************************************************/
    function getActualDay()
    {
        return $this->date1->day;
    }		// function LegacyDate::getActualDay

    /********************************************************************
     *	function LegacyDate::getMonth									*
     *                                                                  *
     *  Extract the month portion of a Legacy internal date.			*
     *                                                                  *
     *  Returns:														*
     *		month number in year (Jan = 1) if present, otherwise 6		*
     ********************************************************************/
    function getMonth()
    {
    	if ($this->sortdate == -99999999)
    	    return 6;
    	return ($this->sortdate / 100) % 100;
    }		// function LegacyDate::getMonth

    /********************************************************************
     *	function LegacyDate::getActualMonth								*
     *                                                                  *
     *  Extract the numeric interpretation of the month in the date.    *
     *                                                                  *
     *  Returns:														*
     *		month number in year (Jan = 1) if present, otherwise 0		*
     ********************************************************************/
    function getActualMonth()
    {
        return $this->date1->month;
    }		// function LegacyDate::getActualMonth

    /********************************************************************
     *	function LegacyDate::getYear									*
     *                                                                  *
     *  Extract the year portion of a Legacy internal date.				*
     *                                                                  *
     *  Returns:														*
     *		   year if defined, otherwise 0								*
     ********************************************************************/
    function getYear()
    {
    	if ($this->sortdate == -99999999)
    	    return 0;
    	return floor($this->sortdate / 10000);
    }		// function LegacyDate::getYear

    /********************************************************************
     *	function LegacyDate::add    									*
     *                                                                  *
     *  Increment the date by a number of days.                         *
     *                                                                  *
     *  Input:                                                          *
     *      $period         number of days expressed as an integer      *
     *                      which may be negative or a string           *
     *                      containing only decimal digits              *
     *                                                                  *
     *  Result:                                                         *
     *      The object becomes an instance of a calculated date.        *
     *      For example if the instance represented the date            *
     *      '21 Jun 1845' calling $date->add(15); changes it into an    *
     *      instance representing 'Calculated 6 Jul 1845'               *
     *      If the original date represented a range the calculated     *
     *      ate is based upon the beginning of the range.               *
     ********************************************************************/
    function add($period)
    {
        if (is_int($period) ||
            is_string($period) && ctype_digit($period))
        {
            $d              = $this->getDay();
            $m              = $this->getMonth();
            $y              = $this->getYear();
            $d              += $period;
            while ($d < 1)
            {               // called with negative number
                $m--;
                if ($m < 1)
                {
                    $m      += 12;
                    $y--;
                }
                $d          += $monthLens[$m];
            }               // called with negative number

            while ($d > $monthLens[$m])
            {               // called with positive number
                $d          -= $monthLens[$m];
                $m++;
                if ($m > 12)
                {
                    $m      -= 12;
                    $y++;
                }
            }               // called with positive number

            $this->date         = 'h0' . str_pad($d, 2, '0', STR_PAD_LEFT) .
                                     str_pad($m, 2, '0', STR_PAD_LEFT) .
                                     str_pad($y, 4, '0', STR_PAD_LEFT) .
                                     '00000000';
            $this->sortdate     = $y * 10000 + $m * 100 + $d;
        }
        else
            throw new Exception("unsupported period '$period'");
    }		// function LegacyDate::add


    /********************************************************************
     *	function LegacyDate::toString									*
     *                                                                  *
     *  Convert a Legacy internal date to a string.						*
     *                                                                  *
     *	Substitutions are identified by codes enclosed in square		*
     * 	brackets:														*
     * 	    dd		day of month as a number							*
     * 	    OSdd	day of month as a number, Julian calendar after 	*
     * 				1582												*
     * 	    Mon		3 character abbreviation for month name				*
     * 	    OSMon	3 char abbr for month, Julian calendar after		*
     * 				1582												*
     * 	    Month	full month name										*
     * 	    OSMonth	full month name, Julian calendar after 1582			*
     * 	    yyyy	year (numeric)										*
     * 	    OSyyyy	year (numeric), Old Style: Julian plus 25th			*
     * 				March												*
     * 	    BC		display epoch if BC									*
     * 	    AD		display epoch if AD									*
     *                                                                  *
     *  Parameters:														*
     *	    privlim		the latest year that may be displayed.  Dates	*
     *					after this year are displayed as "Private".		*
     *					Defaults to no privacy limit.					*
     *	    needon		if true then simple dates are prefixed by 'in'	*
     *					or 'on' as required by language convention		*
     *					Defaults to false.								*
     *	    trantab		language specific translate table for text		*
     *	                either an array or an instance of               *
     *	                \Templating\TemplateTag                         *
     *					Defaults to English								*
     *                                                                  *
     *  Returns:                                                        *
     *      date as a string.										    *
     ********************************************************************/
    function toString($privlim	= 9999,
    		          $needon	= false,
    		          $trantab	= null)
    {
        global $warn;

    	if ($trantab == null)
            $trantab	= self::$tranTab;
    	if (($this->sortdate / 10000) > $privlim)
    	    return $trantab['Private'];

    	if (strlen($this->date) == 0)
    	    return '';

    	$prefix		    = substr($this->date, 0, 1);
    	$bc	    	    = substr($this->date, 1, 1);
    	$d1		        = '';		// accumulate first date
    	$d2		        = '';		// accumulate second date

    	if ($prefix == ':')
    	{		// date could not be fully interpreted
    	    // return text just as entered
    	    if ((strlen($this->date) > 18) &&
    		    (substr($this->date, 18, 1) == '\\'))
    		    return substr($this->date, 19);
    	    return substr($this->date, 1);
    	}		// date could not be fully interpreted
    	else
    	if (strlen($this->date) >= 18)
    	{		// starts with encoded date

    	    // format first date of range
    	    $day	    = intval(substr($this->date, 2, 2));
    	    $mon	    = intval(substr($this->date, 4, 2));
    	    $year	    = intval(substr($this->date, 6, 4));

    	    // Gregorian correction
    	    $gcorr	    = floor($year/100) - floor($year/400) - 2;
    	    $OSday	    = $day;
    	    $OSmon	    = $mon;
    	    $OSyear	    = $year;
    	    if ($day > 0 && $year >= 1582 && 
    		    (($bc == ' ') || ($bc == '0')))
    	    {		// day of month specified
    			if ($day <= $gcorr)
    			{
    			    if (($year % 4) == 0 && $mon == 3)
    				    $leapDay	= 1;
    			    else
    				    $leapDay	= 0;
    			    $OSday	= $day + self::$monthLens[$mon - 1] +
    						    $leapDay - $gcorr;
    			    $OSmon	= $mon - 1;
    			    if ($OSmon < 1)
    			    {
    				    $OSmon	+= 12;
    				    $OSyear	= $year - 1;
    			    }
    			}
    			else
    			{
    			    $OSday	= $day - $gcorr;
    			}
    			// in Old Style year begins on the Feast of the Annunciation,
    			// 25th March, which in England is called "Lady Day".
    			if ($OSmon < 3 || ($OSmon == 3 && $OSday < 25))
    			    $OSyear--;
    	    }		// day of month specified

            $pieces	                = explode('[', self::$template);
            $square                 = count($pieces) > 1;
            if (!$square)
            {                   // no square brackets found
                $pieces             = explode('$', self::template);
            }                   // no square brackets found
    		$d1		                .= $pieces[0];
            for($i = 1; $i < count($pieces); $i++)
            {		            // loop through pieces of template
                $piece              = $pieces[$i];
                if ($square)
                {               // square bracket substitutions
                    $result         = preg_match('/^(\w*)]?(.*)/',
                                                 $piece,
                                                 $matches);
                }               // square bracket substitutions
                else
                {               // PERL style substitutions
                    $result         = preg_match('/^(\w*|{\w*})(.*)/',
                                                 $piece,
                                                 $matches);
                }               // PERL style substitutions
                $subname            = $matches[1];
                if (substr($subname, 0, 1) == '{')
                    $subname        = substr($subname, 1, strlen($subname) - 2);
                $rest               = $matches[2];
			    switch($subname)
			    {		                // substitute
					case 'dd':
					{
					    if ($day > 0)
					    	$d1	.= $day;
					    break;
					}	                // case 'dd'

					case 'ddord':
					{	                // ordinal day
					    if ($day > 0)
					    {
							$d1	            .= $day ;
							switch($day)
							{	        // suffix depends upon value
							    case 1:
							    case 21:
							    case 31:
							    {
								    $d1	    .= 'st';
								    break;
							    }
	
							    case 2:
							    case 22:
							    {
								    $d1	    .= 'nd';
								    break;
							    }
	
							    case 3:
							    case 23:
							    {
								    $d1	    .= 'rd';
								    break;
							    }
	
							    default:
							    {
								    $d1	    .= 'th';
								    break;
							    }
	
							}	        // suffix depends upon value
					    }	            // day present
					    break;
					}	                // case 'ddord'

					case 'OSdd':
					{	// Old style day after 1582
					    if ($day > 0)
					    {	// day of month specified
						    $d1	.= $OSday;
					    }	// day of month specified
					    break;
					}	// case 'OSddd'

					case 'Mon':
                    {
                        $enmonth    = self::$months[$mon];
					    if ($mon > 0 && $mon <= 13)
						    $d1	    .= $trantab[self::$months[$mon]];
					    else
					    if ($mon > 12)
						    $d1	    .= '??' . $mon . '??';
					    break;
					}	// case 'Mon'

					case 'OSMon':
					{	// old style month after 1582
					    if ($OSmon > 0 && $OSmon <= 13)
						    $d1	    .= $trantab[self::$months[$OSmon]];
					    else
					    if ($OSmon > 12)
						    $d1	    .= '??' . $OSmon . '??';
					    break;
					}	// case 'OSMon'

					case 'Month':
					{
                        $enmonth    = self::$lmonths[$mon];
					    if ($mon > 0 && $mon <= 13)
					    {
						    $d1	    .= $trantab[self::$lmonths[$mon]];
					    }
					    else
					    if ($mon > 12)
						    $d1	    .= '??' . $mon . '??';
					    break;
					}	// case 'Month'

					case 'OSMonth':
					{
					    if ($OSmon > 0 && $OSmon <= 13)
						$d1	.= $trantab[self::$lmonths[$OSmon]];
					    else
					    if ($OSmon > 12)
						$d1	.= '??' . $OSmon . '??';
					    break;
					}	// case 'OSMonth'

					case 'yyyy':
					{
					    if ($year != 0)
						$d1	.= $year;
					    break;
					}	// case 'yyyy'

					case 'OSyyyy':
					{
					    if ($OSyear != 0)
					    {
						$d1	.= $OSyear . ' OS';
					    }
					    break;
					}	// case 'OSyyyy'

					case 'xxxx':
					{
					    if ($year != 0)
						    $d1	.= integerToRoman($year);
					    break;
					}	// case 'xxxx'

					case 'BC':
					{
					    if ((strlen(trim($d1)) > 0) && 
						    ($bc != ' ') && ($bc != '0'))
						    $d1	    .= $trantab['BC'];
					    break;
					}	// case 'BC'

					case 'AD':
					{
					    if ((strlen(trim($d1)) > 0) && 
						    (($bc == ' ') || ($bc == '0')))
						    $d1	.= $trantab['AD'];
					    break;
					}	// case 'AD'

					case 'BCE':
					{
					    if ((strlen(trim($d1)) > 0) && 
						    ($bc != ' ') && ($bc != '0'))
						    $d1	.= $trantab['BCE'];
					    break;
					}	// case 'BC'

					case 'CE':
					{
					    if ((strlen(trim($d1)) > 0) && 
						    (($bc == ' ') || ($bc == '0')))
						    $d1	.= $trantab['CE'];
					    break;
					}	// case 'AD'

			    }		// switch on substitute id

			    $d1		.= $rest;	    // add text between subtitutions
    	    }		// loop through pieces of template

    	    // format second date of range
    	    $day	    = intval(substr($this->date, 10, 2));
    	    $mon	    = intval(substr($this->date, 12, 2));

    	    if ($mon >= 50)
    	    {                   // BC date
        		$mon	-= 50;
        		$bc	    = '0';
    	    }                   // BC date
    	    $year	= intval(substr($this->date, 14, 4));
    	    if ($day != 0 || $mon != 0 || $year != 0)
    	    {	                // second date specified
    		    $pieces	= explode('[', self::$template);
    		    $sub	= false;	// first piece no sub name
    		    foreach($pieces as $piece)
    		    {	        	// loop through pieces of template
    				if ($sub)
    				{       	// piece starts with sub name
    				    $elts	= explode(']', $piece);
    				    switch($elts[0])
    				    {		// substitute
    						case 'dd':
    						{
    						    if ($day > 0)
    						    	$d2	.= $day;
    						    break;
    						}	// case 'ddd'
    		
    						case 'Mon':
    						{
    						    if ($mon > 0 && $mon <= 13)
    						    	$d2	.= $trantab[self::$months[$mon]];
    						    else
    						    if ($mon > 12)
    							    $d2	.= '??' . $mon . '??';
    						    break;
    						}	// case 'Mon'
    		
    						case 'Month':
    						{
    						    if ($mon > 0 && $mon <= 13)
    							    $d2	.= $trantab[self::$lmonths[$mon]];
    						    else
    						    if ($mon > 12)
    							    $d2	.= '??' . $mon . '??';
    						    break;
    						}	// case 'Month'
    		
    						case 'yyyy':
    						{
    						    if ($year != 0)
    							    $d2		.= $year;
    						    break;
    						}	// case 'yyyy'
    		
    						case 'xxxx':
    						{
    						    if ($year != 0)
    							    $d1	.= integerToRoman($year);
    						    break;
    						}	// case 'xxxx'
    		
    						case 'BC':
    						{
    						    if ((strlen(trim($d2)) > 0) && 
    							    ($bc != ' ') && ($bc != '0'))
    							    $d2	.= $trantab['BC'];
    						    break;
    						}	// case 'BC'
    		
    						case 'AD':
    						{
    						    if ((strlen(trim($d2)) > 0) && 
    						    	(($bc == ' ') || ($bc == '0')))
    						    	$d2	.= $trantab['AD'];
    						    break;
    						}	// case 'AD'
    		
    						case 'BCE':
    						{
    						    if ((strlen(trim($d2)) > 0) && 
    						    	($bc != ' ') && ($bc != '0'))
    							    $d2	.= $trantab['BCE'];
    						    break;
    						}	// case 'BCE'
    		
    						case 'CE':
    						{
    						    if ((strlen(trim($d2)) > 0) && 
    							    (($bc == ' ') || ($bc == '0')))
    							    $d2	.= $trantab['CE'];
    						    break;
    						}	// case 'CE'
    	
    				    }		// switch on substitute id
    				    $d2		.= $elts[1];	// add in text
    				}	        // piece starts with sub name
    				else
    				{	        // first piece does not start with sub name
    				    $d2		.= $piece;
    				    $sub	= true;
    				}	        // first piece does not start with sub name
    	
    		    }		        // loop through pieces of template
    	    }	                // second date specified

    	}		                // starts with encoded date
    	$d1	    = trim($d1);	// excess space from template expansion
    	$d2	    = trim($d2);

    	if ($prefix == '0')
    	{		// normal date
    	    if (strlen($d2) > 0)
    		    return $trantab['from'] . ' ' . $d1 . ' ' .
    		        	$trantab['to'] . ' ' . $d2;
    	    else
            if (substr($this->date, 2, 2) != '00')
            {
        		if ($needon)
        		    return $trantab['on'] . ' ' . $d1;
        		else
                    return $d1;
            }
    	    else
    		if ($needon)
    		    return $trantab['in'] . ' ' . $d1;
    		else
    		    return $d1;
    	}		// normal
    	else
    	if ($prefix == '1')
    	{		// about
    	    return $trantab['about'] . ' ' .  $d1;
    	}		// about
    	else
    	if ($prefix == '2')
    	{		// circa
    	    return $trantab['circa'] . ' ' . $d1;
    	}		// circa
    	else
    	if ($prefix == '3')
    	{		// before
    	    return $trantab['before'] . ' ' . $d1;
    	}		// before
    	else
    	if ($prefix == '4')
    	{		// after
    	    return $trantab['after'] . ' ' . $d1;
    	}		// after
    	else
    	if ($prefix == '5')
    	{		// between a and b
    	    return $trantab['between'] . ' ' . $d1 .
    	    	    ' ' . $trantab['and'] . ' ' . $d2;
    	}		// between a and b
    	else
    	if ($prefix == '6')
    	{		// BC
    	    return $d1 . ' ' . $trantab['BC'];
    	}		// BC
    	else
    	if ($prefix == '7')
    	{		// explicit AD
    	    return $d1 . ' ' . $trantab['AD'];
    	}		// explicit AD
    	else
    	if ($prefix == '8')
    	{		// WFT estimate
    	    return $trantab['WFT estimate'] . ' ' . $d1;
    	}		// WFT estimate
    	else
    	if ($prefix == 'F')
    	{		// from
    	    return $trantab['from'] . ' ' . $d1;
    	}		// from
    	else
    	if ($prefix == 'T')
    	{		// to
    	    return $trantab['to'] . ' ' . $d1;
    	}		// to
    	else
    	if ($prefix == 'M')
    	{		// from a to b
    	    return $trantab['from'] . ' ' . $d1 . ' ' .
    	    	    $trantab['to'] . ' ' . $d2;
    	}		// from a to b
    	else
    	if ($prefix == 'a')
    	{		// dead
    	    return substr($this->date, 1);
    	}		// dead
    	else
    	if ($prefix == 'b')
    	{		// quarter or deceased
    	    if (strlen($this->date) >= 18)
    	    {
    		    return $trantab['in Q'] .
    		        	floor((intval(substr($this->date, 4, 2)) + 2)/3) .
    		        	' ' . substr($this->date, 6, 4);
    	    }
    	    return $trantab['Deceased'];
    	}		// quarter
    	else
    	if ($prefix == 'c')
    	{		// Child
    	    return $trantab['Child'];
    	}		// Child
    	else
    	if ($prefix == 'd')
    	{		// Infant
    	    return $trantab['Infant'];
    	}		// Infant
    	else
    	if ($prefix == 'e')
    	{		// stillborn
    	    return $trantab['Stillborn'];
    	}		// stillborn
    	else
    	if ($prefix == 'f')
    	{		// young
    	    return $trantab['Young'];
    	}		// youngl
    	else
    	if ($prefix == 'g')
    	{		// estimate or submitted
    	    if (strlen($this->date) >= 18)
    		    return $trantab['estimated as'] . ' ' . $d1;
    	    else
    		    return $trantab['submitted'];
    	}		// estimate or submitted
    	else
    	if ($prefix == 'h')
    	{		// calculated or cleared
    	    if (strlen($this->date) >= 18)
    		    return $trantab['calculated as'] . ' ' . $d1;
    	    else
    		    return $trantab['cleared'];
    	}		// calculated or cleared
    	else
    	if ($prefix == 'i')
    	{		// normal
    	    return $trantab['uncleared'];
    	}		// normal
    	else
    	if ($prefix == 'j')
    	{		// normal
    	    return $trantab['Completed'];
    	}		// normal
    	else
    	if ($prefix == 'k')
    	{		// normal
    	    return $trantab['Cancelled'];
    	}		// normal
    	else
    	if ($prefix == 'l')
    	{		// normal
    	    return $trantab['bic'];
    	}		// normal
    	else
    	if ($prefix == 'm')
    	{		// normal
    	    return $trantab['Did not say'];
    	}		// normal
    	else
    	if ($prefix == 'n')
    	{		// normal
    	    return $trantab['dns/can'];
    	}		// normal
    	else
    	if ($prefix == 'o')
    	{		// normal
    	    return $trantab['See Notes'];
    	}		// normal
    	else
    	if ($prefix == 'p')
    	{		// normal
    	    return $trantab['before 1970'];
    	}		// normal
    	else
    	if ($prefix == 'q')
    	{		// normal
    	    return $trantab['Done'];
    	}		// normal
    	else
    	if ($prefix == 'r')
    	{		// normal
    	    return $trantab['Private'];
    	}		// normal
    	else
    	if ($prefix == 's')
    	{		// normal
    	    if (strlen($this->date) >= 18)
    		    return $trantab['Banns'] . ' ' . $d1;
    	    else
    		    return $trantab['Banns'];
    	}		// normal
    	else
    	if ($prefix == 't')
    	{		// normal
    	    if (strlen($this->date) >= 18)
    		    return $trantab['License'] . ' ' . $d1;
    	    else
    		    return $trantab['License'];
    	}		// normal
    	else
    	if ($prefix == 'u')
    	{		// normal
    	    return $trantab['Not married'];
    	}		// normal
    	else
    	if ($prefix == 'v')
    	{		// normal
    	    return $trantab['Never married'];
    	}		// normal
    	else
    	if ($prefix == '<')
    	{		// normal
    	    return '&lt;' . $d1 . '&gt;';
    	}		// normal
    	else
    	if ($prefix == '[')
    	{		// normal
    	    return '[' . $d1 . ']';
    	}		// normal
    	else
    	if ($prefix == '?')
    	{		// normal
    	    if (strlen($this->date) >= 18)
    		    return $d1 . '?';
    	    return $trantab['Unknown'];
    	}		// normal
    	else
    	if ($prefix == '|')
    	{		// choice of dates
    	    return $d1 . ' ' . $trantab['or'] . ' ' . $d2;
    	}		// choice of dates
    	else
    	{		// unrecognized
    	    return $this->date;
    	}		// unrecognized
    }		// function LegacyDate::toString

}		// class LegacyDate

// from site #! code by Philip Norton
function integerToRoman($integer)
{
    // Convert the integer into an integer (just to make sure)
    $integer	    = intval($integer);
    $result	        = '';

    // Create a lookup array that contains all of the Roman numerals
    // Note that this only generates classic Roman numerals as used 
    // during the Middle Ages, not the extension used in copyrights. 
    // For example:
    //      for 1999 it generates MCMXCIX not MIM
    //      for 1998 it generates MCMXCVIII not MXMVIII
    //      for 1997 it generates MCMXCVII not MXMVII
    //      for 1996 it generates MCMXCVI not MXMVI
    //      for 1995 it generates MCMXCV not MVM
    //      for 1994 it generates MCMXCIV not MXMIV
    $lookup = array('M'     => 1000,
    				'CM'	=> 900,
    				'D'	    => 500,
    				'CD'	=> 400,
    				'C'	    => 100,
    				'XC'	=> 90,
    				'L'	    => 50,
    				'XL'	=> 40,
    				'X'	    => 10,
    				'IX'	=> 9,
    				'V'	    => 5,
    				'IV'    => 4,
            		'I'     => 1);

    foreach($lookup as $roman => $value)
    {           // Determine the number of matches
    	$matches	= intval($integer/$value);

    	// Add the same number of characters to the string
    	$result 	.= str_repeat($roman,$matches);

    	// Set the integer to be the remainder of the integer and the value
    	$integer 	= $integer % $value;
    }           // Determine the number of matches

    // The Roman numeral should be built, return it
    return $result;
}		// function integerToRoman
