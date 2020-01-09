<?php
namespace Genealogy;
/************************************************************************
 *  WmbUpdate.php														*
 *																		*
 *  Update an entry in the Wesleyan Methodist baptisms table.			*
 *																		*
 *  History:															*
 *		2016/03/11		created											*
 *		2016/10/13		update birth and christening events when		*
 *						linking to an individual						*
 *		2016/12/13		display next baptism on page after update		*
 *		2017/03/19		use preferred parameters for new Person			*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/10/29		use standard invocation of new Citation			*
 *		2020/01/05      update birth date, add name citation            *
 *		                use Template                                    *
 *																		*
 *  Copyright &copy; 2020 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/MethodistBaptism.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// default values
$domain		    		= 'CAON';
$domainName				= 'Ontario';
$regYear				= null;
$idmb		    		= null;
$volume		    		= null;
$page		    		= null;
$idirChanged			= false;
$lang           		= 'en';
$baptism	            = null;
$person		            = null;
$volumeText             = null;
$pageText               = null;

// expand only if authorized
$update                 = canUser('edit');

// get key values from parameters
if (isset($_POST) && count($_POST) > 0)
{
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach ($_POST as $key => $value)
    {
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$value</td></tr>\n"; 
	    switch(strtolower($key))
	    {		            // act on specific parameter
			case 'idmb':
            {
                $idmbtext           = trim($value);
                if (ctype_digit($value))
                {           // valid value
                    $idmb           = intval($idmbtext);
                    if ($update)
                    {       // User authorized
                        $baptism	=
                            new MethodistBaptism(array('idmb' => $idmb));
                        $idir		= $baptism['idir'];
                    }       // User authorized
                }           // valid value
                break;
            }               // idmb

			case 'idir':
			{
			    $idirChanged	    = $idir != $value;
			    if ($idirChanged && $baptism)
			    {
					$idir		    = $value;
					$baptism->set($key, $value);
			    }
			    break;
			}

			case 'date':
			case 'place':
            {
                if ($baptism)
			        $baptism->set('birth' . $key, $value);
			    break;
			}

			case 'volume':
			{
			    if (ctype_digit($value))
			    {
                    $volume		    = intval($value);
                    if ($baptism)
					    $baptism->set('volume', $volume);
			    }
                else
                    $volumeText     = $value;
			    break;
			}

			case 'page':
			{
			    if (ctype_digit($value))
			    {
					$page		    = intval($value);
                    if ($baptism)
					    $baptism->set('page', $page);
			    }
			    else
					$pageText       = $value;
			    break;
			}

			case 'district':
			case 'area':
			case 'givenname':
			case 'surname':
			case 'father':
			case 'mother':
			case 'residence':
			case 'birthplace':
			case 'birthdate':
			case 'baptismdate':
			case 'baptismplace':
			case 'minister':
			{
                if ($baptism)
			        $baptism->set($key, $value);
			    break;
			}

			case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }                   // lang
        }
    }	                        // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}		                        // invoked by submit to update baptism record

$template               = new FtTemplate("WmbUpdate$lang.html");

if (!$update)
{		            // not authorized
	$msg	.= 'You are not authorized to update baptism registrations. ';
}		            // not authorized

if (is_null($idmb))
{                   // IDMB not specified
    if ($idmbtext)
        $msg	.= "IDMB='$idmbtext' is not numeric. ";
    else
        $msg	.= 'Baptism record number not specified. ';
}			        // IDMB not specified

if ($volumeText)
	$msg	    .= "Volume='$volumeText' is not numeric. ";

if ($pageText)
	$msg	    .= "Page='$pageText' is not numeric. ";


if ($baptism && strlen($msg) == 0)
{
    $baptism->save(false);

	// if IDIR was not already set check for the case where the
	// family tree already cites the specific registration
	if ($idir == 0)
	{			// IDIR not set
	    $parms	        	    = array('IDSR'		=> 158,
		        		    	    	'Type'		=> Citation::STYPE_CHRISTEN,
				        	    	    'SrcDetail'	=> "$idmb",
						                'limit'		=> 1);
	    $citations	    	    = new CitationSet($parms,
					    		                  'IDSX');
	    $info	        	    = $citations->getInformation();
	    $count	        	    = $info['count'];
	    $count	        	    = $parms['count'];
	    if ($count > 0)
	    {			// there is already a citation to this
			$citation		    = current($citations);
			if ($citation instanceof Record)
			{
			    $idir			= $citation->get('idime');
			    $baptism->set('idir', $idir);
			    $idirChanged	= true;
			} 
			else
			    print_r($citation);
	    }			// there is already a citation to this
	}			// IDIR not set

	// update the baptism record
	$baptism->save(false);

	// support adding citation to family tree
	if ($idirChanged && $idir > 0)
	{			// the associated individual has changed
		$person		            = new Person(array('idir' => $idir));
		$source		            = new Source(array('srcname' =>
	                				'Wesleyan Methodist Baptisms, Ontario'));
		$idsr		            = $source->get('idsr');
		$srcdetail	            = 'vol ' . $baptism['volume'] .
		        	        		  ' page ' . $baptism['page'];

		// create or update the christening event
		$christEvent	        = $person->getChristeningEvent(true);
		$christEvent->setDate($baptism['BaptismDate']);
        $baptismPlace           = $baptism['BaptismPlace'];
        $locationSet            = new RecordSet('Locations',
                                    array('shortname' => "^$baptismPlace$"));
        if (count($locationSet) == 1)
        {
            $location           = $locationSet->rewind();
            $baptismPlace       = $location['location'];
        }
        $christEvent->setLocation($baptismPlace);
        $christEvent->save(false);
		$citParms	            = array('idsr'	    => $idsr,
                                        'srcdetail' => $srcdetail,
                                        'type'      => Citation::STYPE_EVENT);
		$citation	            = new Citation($citParms);
		$christEvent->addCitations($citation);
        $christEvent->save(false);

		// create or update the birth event
		$birthEvent	            = $person->getBirthEvent(true);
        $birthEvent->save(false);
        $internalDate           = $birthEvent['eventd'];
		if (strlen($internalDate) == 0 ||           // birth date not set
		    substr($internalDate, 0, 1) != '0' ||   // not precise date
		    substr($internalDate, 2, 2) == '00')    // no day
		    $birthEvent->setDate($baptism['birthdate']);
        $birthPlace             = $baptism['birthPlace'];
        $locationSet            = new RecordSet('Locations',
                                    array('shortname' => "^$birthPlace$"));
        if (count($locationSet) == 1)
        {
            $location           = $locationSet->rewind();
            $birthPlace         = $location['location'];
        }
		if ($birthEvent->getLocation() == '' ||
		    $birthEvent->getLocation() == 'Ontario, Canada')
		    $birthEvent->setLocation($birthPlace);
		$citParms	            = array('idsr'	    => $idsr,
						  	            'srcdetail' => $srcdetail,
                                        'type'      => Citation::STYPE_EVENT);
		$citation	            = new Citation($citParms);
		$birthEvent->addCitations($citation);
        $birthEvent->save(false);

	    // add a citation for the name to the registration
	    $givenName	            = $baptism['givenname'];
	    $surName	            = $baptism['surname'];
	    $citParms	            = array('idime'		=> $idir, 
			                		    'type'		=> Citation::STYPE_NAME, 
			            	    	    'idsr'		=> $idsr,
	    	            	    	    'srcdetail'	=> $srcdetail,
			            	    	    'srcdettext'=> "$givenName $surName");
	    $citation	            = new Citation($citParms);
	    $citation->save(false);	// write into the database
	}			// the associated individual has changed
}				// no errors

// Identify next registration to update
if (strlen($msg) == 0 && strlen($warn) == 0)
{		        // redirect immediately to next registration
	header("Location: WmbDetail.php?Volume=$volume&Page=$page&IDMB=>$idmb"); 
}		        // redirect immediately to next registration
else
{		        // display page
    $template->set('IDMB',          $idmb);
    if ($baptism)
    {
        $template->set('VOLUME',            $baptism['volume']);
        $template->set('PAGE',              $baptism['page']);
    }
    else
    {
        $template->set('VOLUME',            '');
        $template->set('PAGE',              '');
        $template['pageDispay']->update(null);
    }
    $template->display();
}
