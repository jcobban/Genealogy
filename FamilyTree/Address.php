<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Address.php															*
 *																		*
 *  Display a web page containing details of a particular Address		*
 *  from the Legacy database.  If the current user is authorized to		*
 *  edit the database, this web page supports that.						*
 *																		*
 *  If this web page is invoked with parameters passed by method='post'	*
 *  then it updates the database record to reflect the updated values	*
 *  passed as parameters.  But if it is invoked with parameters			*
 *  passed by method='post' it displays the record.						*
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
 *		name			name of the address								*
 *		given			given name of individual for whom mailing		*
 *						address is to be created, if idar=0 and kind=0	*
 *		surname			surname of individual for whom mailing address	*
 *						is to be created, if idar=0 and kind=0			*
 *		formname		name of the form containing the element with	*
 *						name='idar' that is to be updated with the		*
 *						idar of the newly created address.				*
 *						If idar=0 and kind=0.							*
 *																		*
 *  History:															*
 *		2010/12/04		created											*
 *		2010/12/08		add creation of new record						*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2012/01/13		change class names								*
 *		2012/01/28		cleaner parameter validation					*
 *		2012/07/26		change genOntario.html to genOntario.php		*
 *		2013/02/21		do not disable submit							*
 *						fix problems with creating new address			*
 *						only look for duplicates if updating			*
 *						add name= parameter								*
 *		2013/02/23		change invocation of Address to allow			*
 *						duplicate address names for different kinds of	*
 *						addresses										*
 *						support Google maps as in Location.php			*
 *		2013/03/04		visually mark home page input field as a link	*
 *		2013/03/12		use standard style on selection lists			*
 *						handle issue that browser sends nothing to		*
 *						action script if checkbox is not set			*
 *		2013/05/29		use pageTop and pageBot to standardize			*
 *						appearance										*
 *						include IDAR in mail subject					*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS instead of tables to layout form		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/08/28		move encoding of initial field values to		*
 *						initialization, simplifying HTML generation		*
 *						support invoking mergeAddressesXml.php by		*
 *						submit for debugging							*
 *						pass debug flag to next script					*
 *		2014/10/05		add support for associating instances of		*
 *						Picture with an address							*
 *						display any associated media files				*
 *		2014/12/12		print $warn, which may contain debug trace		*
 *		2015/05/27		add "Close" button								*
 *						add "Delete" button								*
 *						dialog and updating in separate scripts			*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *		2016/03/16		use https to load googleapis					*
 *		2016/12/09		determine geocoder search parm					*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/08/04		class LegacyAddress renamed to Address			*
 *		2017/09/12		use get( and set(								*
 *		2017/10/16		use class RecordSet								*
 *		2018/02/12		use Template									*
 *						merge functionality of UpdateAddress.php		*
 *		2019/02/18      use new FtTemplate constructor                  *
 *		2019/07/23      use FtTemplate::validateLang                    *
 *		2019/09/25      for action=delete do not access object          *
 *		                Address::getIdar no longer saves the object     *
 *		                fix display for deleted object                  *
 *      2019/11/17      move CSS to <head>                              *
 *      2020/12/05      correct XSS vulnerabilities                     *
 *      2021/10/17      minor cleanup in parameter processing           *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// initial values
$lang					= 'en';
$idar					= null;		// key of instance
$kind					= 0;		// kind of address
$given					= '';		// given name of ass'ed tblIR record
$surname				= '';		// surname of ass'ed tblIR record
$name					= 'new';	// create new address
$formname				= '';		// form name of invoking page
$address				= null;		// instance of Address
$list1Checked			= ''; 
$list2Checked			= ''; 
$list3Checked			= ''; 
$list4Checked			= ''; 
$list5Checked			= ''; 
$list6Checked			= ''; 
$usedChecked			= ''; 
$tag1Checked			= ''; 
$verifiedChecked		= ''; 
$qsTagChecked			= '';
$duplicates		    	= null; 
$action	            	= '';

// examine the value of all parameters passed with the request
if (isset($_GET) && count($_GET) > 0)
{		                        // parameters passed by method=get
    $parmsText  = "<p class='label'>\$_GET</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {		                    // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
		switch(strtolower($key))
		{	                    // act on specific key
		    case 'formname':
		    {
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.-]*$/', $value))
                    $formname	= $value;
                else
                    $msg        .= "Formname value '$safevalue' invalid. ";
				break;
		    }	                // formname

		    case 'idar':
		    case 'id':	        // backwards compatibility
		    {
				if (ctype_digit($value))
				    $idar	= intval($value);
				else
				{	            // invalid format
				    $name	= "IDAR value '" .
                                    htmlspecialchars($value) .
                                    "' invalid";
				    $msg	.= $name . '. ';
				}	            // invalid format
				break;
		    }	                // idar

		    case 'kind':
		    {
				if ($value == '0' || $value == '1' || $value == '2')
				    $kind	= intval($value);
				else
				{	            // invalid format
 			        $name	= "Kind value '" .
                                    htmlspecialchars($value) . 
                                    "' invalid";
				    $msg	.= $name . '. ';
				}	            // invalid format
				break;
		    }	                // record kind

		    case 'given':
		    {
				$given		= htmlspecialchars($value);
				break;
		    }	                // given name of associated individual

		    case 'surname':
		    {
				$surname	= htmlspecialchars($value);
				break;
		    }	                // surname of associated individual

		    case 'name':
		    {
				$name		= htmlspecialchars($value);
				break;
		    }	                // name of address

		    case 'lang':
		    {
                $lang       = FtTemplate::validateLang($value);
				break;
		    }	                // presentation language

		}	                    // act on specific key
    }		                    // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		                        // parameters passed by method=get
else
if (isset($_POST) && count($_POST) > 0)
{		                        // parameters passed by method=post
    $parmsText          = "<p class='label'>\$_POST</p>\n" .
                            "<table class='summary'>\n" .
                              "<tr><th class='colhead'>key</th>" .
                                "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {		                    // loop through all parameters
		if (is_array($value))
            $safevalue  = htmlspecialchars(var_export($value, true));
        else
            $safevalue  = htmlspecialchars($value);
        $parmsText      .= "<tr><th class='detlabel'>$key</th>" .
                            "<td class='white left'>$safevalue</td></tr>\n"; 
		switch(strtolower($key))
		{	                    // act on specific key
		    case 'formname':
            {
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.-]*$/', $value))
                    $formname	= $value;
                else
                    $msg        .= "Formname value '$safevalue' invalid. ";
				break;
		    }	                // formname

		    case 'action':
		    {
                if (preg_match('/^[a-zA-Z]+$/', $value))
				    $action		= strtolower($value);
                else
                    $msg        .= "Action value '$safevalue' invalid. ";
				break;
		    }	                // action

		    case 'idar':
		    case 'id':	        // backwards compatibility
		    {
				if (ctype_digit($value))
				    $idar	= intval($value);
				else
				{	            // invalid format
				    $name	= "IDAR value '$safevalue' invalid";
				    $msg	.= $name . '. ';
				}	            // invalid format
				break;
		    }	                // idar

		    case 'kind':
		    {
				if ($value == '0' || $value == '1' || $value == '2')
				    $kind	= intval($value);
				else
				{	            // invalid format
				    $name	= "Kind value '$safevalue' invalid";
				    $msg	.= $name . '. ';
				}	            // invalid format
				break;
		    }	                // record kind

		    case 'lang':
		    {
				$lang	    = FtTemplate::validateLang($value);
				break;
		    }	                // presentation language

		}	                    // act on specific key
    }		                    // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		                        // parameters passed by method=post

// calculate default address name
if ($kind == 0 &&			// mailing address
	$name == 'new' &&
	(strlen($given) > 0 || strlen($surname) > 0))
	$name	            = "$given $surname";
if ($kind == 2 &&			// repository
	$name == 'new')
	$name	            = 'New Repository';

// allocate an instance of Address based upon the parameters
// passed to the script
if ($idar > 0)
{			                    // existing numeric key
	$address	= new Address(array('idar' => $idar));
	if (!$address->isExisting())
	{		                    // failed to create instance of Address
	    $name	.= "Invalid value of idar=$idar";
	    $msg	.= "Invalid value of idar=$idar";
	}		                    // failed to create instance of Address
}			                    // existing numeric key
else
{			                    // search by name
	$address	= new Address(array('addrname'	=> $name, 
                                    'kind'	    => $kind));
	if (!$address->isExisting())
	{		                    // created new instance of Address
	    $name	= " New " . $name;
	}		                    // created new instance of Address
}			                    // search by name

// act on the instance of Address
if ($address)
{
	// actions depend upon whether the user is authorized to update
	// the record, whether parameters are passed by method='get' or
	// method='post', and whether it is an existing record or a new one
	if ($address->isOwner())
	{			// permit update
	    if ($action == 'delete')
	    {			            // delete
			$name			= $address['name'];

			// delete the record
			$address->delete(false);
			$address		= null;
			$idar			= 0;
	    }			            // delete
	    else
        if ($action == 'update')
        {
			// apply any changes passed in parameters
			$address->postUpdate(false);
			$name			= $address['name'];

			// update or insert the record into the database
			$address->save();
	    }			            // update
	    else
			$name			= $address['name'];
	    $action			    = 'Edit';

	    // until record is saved in database, we may not know the IDAR
	    // value, which is assigned by the database server
	    // until this is done, we may not know the IDAR
        // value, which is assigned by the database server
        if ($address)
        {                       // we have an instance of Address
            if ($idar == 0)
            {
                $address->save();
                $idar		= $address->getIdar();
            }

	        $kind		    = $address['kind'];

	        // check for duplicates of this address
	        $getParms	    = array('idar'		=> '!=' . $idar,
    	    			    		'kind'		=> $kind,
	        				    	'addrname'	=> "^$name$");
	        $duplicates		= new RecordSet('Addresses',
		    			            		$getParms,
                                            'IDAR');
        }                       // we have an instance of Address
	}			                // permit update
	else
	    $action			    = 'Display';

    if ($address)
    {
        $pattern			= $address['name'];
	    $kind				= $address['kind'];
    }
    else
    {
        $pattern            = $name;
        $kind               = 0;
    }
	if (strlen($pattern) > 5)
	    $pattern			= substr($pattern, 0, 5);

	switch($kind)
	{
	    case 0:
	    {
			$kindMail		= 'selected="selected"';
			$kindEvent		= '';
			$kindRepo		= '';
			break;
	    }
	
	    case 1:
	    {
			$kindMail		= '';
			$kindEvent		= 'selected="selected"';
			$kindRepo		= '';
			break;
	    }
	
	    case 2:
	    {
			$kindMail		= '';
			$kindEvent		= '';
			$kindRepo		= 'selected="selected"';
			break;
	    }
	
	    default:
	    {
			$kindMail		= '';
			$kindEvent		= '';
			$kindRepo		= '';
			break;
	    }
	
    }
    if ($address)
    {
		$name			    = $address['name'];
		$sortkey		    = $address['addrsort'];
		if (strlen($sortkey) == 0)
		    $sortkey		= $name;
	    $style			    = $address['style'];
    }
    else
        $style              = 0;

	switch($style)
	{
	    case 0:
	    {
			$styleAmerican	= 'selected="selected"';
			$styleEuropean	= '';
			break;
	    }
	
	    case 1:
	    {
			$styleAmerican	= '';
			$styleEuropean	= 'selected="selected"';
			break;
	    }
	
	    default:
	    {
			$styleAmerican	= '';
			$styleEuropean	= '';
			break;
	    }
	
	}
	
}		// got instance of Address
else
{		// did not construct instance
	$name				= 'Unable to Create Instance of Address';
	$action				= 'Display';
}

$template				= new FtTemplate("Address$action$lang.html");
$template->updateTag('otherStylesheets',	
    		         array('filename'   => '/FamilyTree/Address'));
$translate              = $template->getTranslate();
$t                      = $translate['tranTab'];

// handle idiosyncracies of Google geocoder implementation
$searchName				= $name;
$part1	    			= '';
$part2		    		= '';
$county		    		= '';
$geoPattern				= "/^\s*(.*),([a-zA-Z ]*),([a-zA-Z ]+),\s*CA\s*$/";
$results				= array();
$res1		    		= preg_match($geoPattern, $name, $results);
if ($res1)
{
	$part1				= trim($results[1]);	// street or lot location
	$county				= trim($results[2]);	// county or city name
	$province			= trim($results[3]);	// province
	$getParms			= array('domain'	        => 'CA' . $province,
        						'name'				=> $county);
	$counties			= new CountySet($getParms);
	$res2				= preg_match("/\b(lot|lots|con|cons)\b[^,]*,(.*)$/",
        						     $part1,
		        				     $results);
	if (count($counties) > 0)
	{
	    if ($res2)
	    {
			$part2				= trim($results[2]);
			$searchName			= "$part2, $county county, $province, CA";
	    }
	    else
			$searchName			= "$part1, $county county, $province, CA";
	}
	else
	{
	    if ($res2)
	    {
			$part2				= trim($results[2]);
			$searchName			= "$part2, $county, $province, CA";
	    }
	    else
			$searchName			= "$part1, $county, $province, CA";
	}
}

$template->set('NAME',			    $name);
if ($address)
    $template->set('NAMEANDKIND',		$address->getName($t));
else
    $template->set('NAMEANDKIND',		ucfirst($t['deleted']));

if (strlen($msg) == 0)
{		// no errors detected
    if ($address)
    {
	$template->set('IDAR',			$idar);
	$template->set('PATTERN',		$pattern);
	$template->set('KIND',			$kind);
	$template->set('SORTKEY',		$sortkey);
	$template->set('ADDRESS1',		$address['address1']);
	$template->set('ADDRESS2',		$address['address2']);
	$template->set('CITY',			$address['city']);
	$template->set('STATE',			$address['state']);
	$template->set('ZIPCODE',		$address['zipcode']);
	$template->set('COUNTRY',		$address['country']);
	$template->set('PHONE1',		$address['address1']);
	$template->set('PHONE2',		$address['phone1']);
	$template->set('EMAIL',			$address['phone2']);
	$template->set('HOMEPAGE',		$address['homepage']);
	$template->set('LATITUDE',		$address['latitude']);
	$template->set('LONGITUDE',		$address['longitude']);
	$template->set('ZOOM',			$address['zoom']);
	$template->set('NOTES',			'');
    $template->set('STYLE',			$style);
	$fsresolved		                = $address['fsresolved'];
	$veresolved		                = $address['veresolved'];
    for($i = 0; $i <= 2; $i++)
    {
        if ($i  == $fsresolved)
            $template->set("FSRESOLVED$i",	'checked="checked"');
        else
            $template->set("FSRESOLVED$i",	'');
    }
    for($i = 0; $i <= 2; $i++)
    {
        if ($i  == $veresolved)
            $template->set("VERESOLVED$i",	'checked="checked"');
        else
            $template->set("VERESOLVED$i",	'');
    }
	if ($address['list1'])
	    $list1Checked				= "checked='checked' "; 
	if ($address['list2'])
	    $list2Checked				= "checked='checked' "; 
	if ($address['list3'])
	    $list3Checked				= "checked='checked' "; 
	if ($address['list4'])
	    $list4Checked				= "checked='checked' "; 
	if ($address['list5'])
	    $list5Checked				= "checked='checked' "; 
	if ($address['list6'])
	    $list6Checked				= "checked='checked' "; 
	if ($address['used'])
	    $usedChecked				= "checked='checked' "; 
	if ($address['tag1'])
	    $tag1Checked				= "checked='checked' "; 
	if ($address['verified'])
	    $verifiedChecked			= "checked='checked' "; 
	if ($address['qstag'])
	    $qsTagChecked				=  "checked='checked' "; 
	$template->set('LIST1CHECKED',		$list1Checked);
	$template->set('LIST2CHECKED',		$list2Checked);
	$template->set('LIST3CHECKED',		$list3Checked);
	$template->set('LIST4CHECKED',		$list4Checked);
	$template->set('LIST5CHECKED',		$list5Checked);
	$template->set('LIST6CHECKED',		$list6Checked);
	$template->set('USEDCHECKED',		$usedChecked);
	$template->set('TAG1CHECKED',		$tag1Checked);
	$template->set('QSTAGCHECKED',		$qsTagChecked);
	$template->set('VERIFIEDCHECKED',	$verifiedChecked);
	$template->set('KINDMAIL',		    $kindMail);
	$template->set('KINDEVENT',		    $kindEvent);
	$template->set('KINDREPO',		    $kindRepo);
	$template->set('STYLEAMERICAN',		$styleAmerican);
	$template->set('STYLEEUROPEAN',		$styleEuropean);
	if (is_null($formname))
	    $formname			= '';
	$template->set('FORMNAME',		    $formname);
	$template->set('SEARCHNAME',		$searchName);

	if ($duplicates && $duplicates->count() > 0)
	    $template->updateTag('duprow$idar', $duplicates);
	else
        $template->updateTag('duplicates', null);

	// display any media files associated with the source
	ob_start();
	$address->displayPictures(Picture::IDTYPEAddress);
	$template->set('PICTURES', ob_get_clean());
    }               // have instance
    else
        $template['locForm']->update(null);
}
else
{
	$template['locForm']->update(null);
}

$template->display();
