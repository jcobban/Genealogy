<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateMarriageXml.php												*
 *																		*
 *  Handle a request to update an individual marriage in				*
 *  the Legacy family tree database.									*
 *																		*
 *  The following parameters must be passed using the POST method.		*
 *																		*
 *		idmr			unique numeric key of marriage record			*
 *																		*
 *  The following parameters may be passed using the POST method.		*
 *																		*
 *		treename		name of the database subdivision				*
 *		IDIRHusb		unique numeric key of husband					*
 *		HusbIdir		unique numeric key of husband (old form)		*
 *		HusbOrder		sort order from husband's point of view			*
 *		HusbPrefMar		preferred marriage								*
 *		HusbGivenName	given name of husband							*
 *		HusbSurname		surname of husband								*
 *		HusbMarrSurname	married surname of husband						*
 *		HusbBirthSD		sort date										*
 *		IDIRWife		unique numeric key of wife						*
 *		WifeIdir		unique numeric key of wife (old form)			*
 *		WifeOrder		sort order from wife's point of view			*
 *		WifePrefMar		preferred marriage								*
 *		WifeGivenName	given name of wife								*
 *		WifeSurname		surname of wife									*
 *		WifeMarrSurname	married surname of husband						*
 *		WifeBirthSD		sort date										*
 *		MarriedNameRule is wife known by husband's name?				*
 *		MarD			date of marriage in text form					*
 *		MarLoc 			location of marriage							*
 *		Notes			textual notes									*
 *		NotMarried		never married indicator							*
 *		NoChildren		no children indicator							*
 *		SealD			date of LDS sealing in text form				*
 *		IDTRSeal		id of temple of sealing							*
 *		TrSeal			id of temple of sealing (old form)				*
 *		SealLoc 		name of temple of sealing (see Temple)			*
 *		IDMS			marriage status									*
 *		in general any valid field name									*
 *																		*
 *  History:															*
 *		2010/09/19		ensure new IDLR not unnecessarily generated		*
 *		2010/09/25		Check error on $result, not $connection after	*
 *						query/exec										*
 *		2010/10/22		use method='post'								*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/30		add support for marriage status					*
 *		2011/06/12		correct error on setting sort date for Sealing	*
 *		2011/11/26		update tblNX with alternate married name[s]		*
 *		2012/01/13		change class names								*
 *						renamed to updateMarriageXml.php				*
 *	    2012/08/27	    only update fields in the database if the       *
 *			            script specifies a different value from the     *
 *			            database copy automatically create a spouse     *
 *			            if the name has changed but there was no        *
 *			            current spouse IDIR value.                      *
 *			            These two changes permit changing the edit      *
 *			            marriage dialog to allow changing the name      *
 *			            fields                                          *
 *	    2012/10/16	    use setField and save methods to update         *
 *						database records								*
 *		2012/11/04		date functionality moved into class LegacyFamily*
 *		2012/11/12		avoid use of && in SQL commands					*
 *			            update database only through save method        *
 *			            if records                                      *
 *		2012/11/27		handle exception thrown by LegacyFamily::setName*
 *		2013/01/25		only create new individuals with non-empty names*
 *		2013/02/12		do not use LegacyIndiv::getIdir to determine	*
 *						whether or not the record is already in the		*
 *						database										*
 *		2013/03/14		LegacyLocation constructor no longer saves		*
 *		2013/03/23		perform all manipulation of children here,		*
 *						rather than through invocation of				*
 *						individual scripts								*
 *		2013/04/02		avoid creating empty spouse						*
 *		2013/05/20		act on NotMarried and NoChildren fields			*
 *		2013/06/01		remove use of deprecated interfaces				*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *						cannot set WifeBirthSD using setField			*
 *		2014/03/06		implement ability to add children using			*
 *						new rows in children table without requiring	*
 *						invocation of editIndivid.php					*
 *		2014/03/18		ensure IDIR set for children					*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/10/25		handle CIDIR field value 'undefined'			*
 *		2015/07/01		escape special chars in parameter values		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/08/12		support treename								*
 *		2017/03/18		husband is optional in family					*
 *		2017/03/19		use preferred parameters to new LegacyIndiv		*
 *						use preferred parameters to new LegacyFamily	*
 *		2017/09/02		class LegacyTemple renamed to class Temple		*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2018/05/16		return more information about children added	*
 *						to the family									*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
header("Content-Type: text/xml");
require_once __NAMESPACE__ . '/Family.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Location.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

// emit the XML header
print "<?xml version='1.0' encoding='UTF-8'?>\n";
print "<marriage>\n";

// get the updated values of the fields in the record

// user must be authorized to edit the database
if (!canUser('edit'))
{  		// the user not authorized
	$msg	        .= 'User not authorized to update the database. ';
}  		// the user not authorized

// obtain and validate IDMR
$idmr				= null;
$logmsg				= "updateMarriageXml.php?";
$and				= '';
foreach($_POST as $key => $value)
{
	$logmsg	        .= $and . $key . '=' . $value;
	$and	        = '&';
	if (strtolower($key) == 'idmr')
	    $idmr	    = $value;
}
error_log($logmsg . "\n", 3, $document_root . "/logs/ajax.log");

if (is_null($idmr))
{
	$msg	        .= 'Missing mandatory parameter idmr. ';
}
else
if (!is_string($idmr) || !ctype_digit($idmr))
	$msg	        .= "Value of idmr='$idmr' must be numeric key. ";
else
{		// idmr specified and numeric
    $family	        = new Family(array('idmr' => $idmr));
}		// idmr specified and numeric

// if there were any errors detected, report them and terminate
if (strlen($msg) > 0)
{		// missing or invalid value of idmr parameter
	print "<msg>$msg</msg>\n";
	print "</marriage>\n";
	exit;
}		// missing or invalid value of idmr parameter

try {
// get information on existing children in an associative array
// based on order
$children		= $family->getChildren();
$maxChildOrder	= count($children) - 1;

// process parameters

$indiv		= null;
$childr		= null;
$childOrder		= 0;
$treename		= '';

// checkboxes are special in that if they are unchecked the field
// is not passed in the parameters, so the absence of the field must
// be treated as not set
$family->set('notmarried', 0);
$family->set('nochildren', 0);

print "  <parms>\n";
foreach($_POST as $key => $value)
{		// loop through parameters
	print "    <$key>" . xmlentities($value);
	switch(strtolower($key))
	{	// act on each supported parameter
	    case 'idmr':
	    {		// key of record to update
		    // done
		    break;
	    }		// key of record to update

	    case 'idirhusb':
	    case 'husbidir':	// old form
		{
			$idirhusb	= (int)$value;
			if ($idirhusb > 0)
			{		// family has a husband
			    $husb	= new Person(array('idir' => $idirhusb));
			    if ($husb->isExisting())
					$gender	= $husb->getGender();
			    else
			    {
					$gender		= Person::MALE;
                    $husb->setTreename($treename);
                    $husb->set('sex',0);
					$husb->save(false);
					$idirhusb	= $husb->getIdir();
					$husb->set('gender', Person::MALE);
			    }
			    if ($gender == Person::MALE)
					$family->set('idirhusb', $idirhusb);
			}		// family has a husband
			else
			{		// a husband is optional
			    $family->set('idirhusb', $idirhusb);
			    $husb	= null;
			}		// a husband is optional
			break;
	    }

	    case 'treename':
	    {
		    $treename	= $value;
		    break;
	    }

	    case 'husbgivenname':
        {
			if ($husb && $husb->getIdir() > 0)
			    $husb->set('givenname', $value);
			else
			if ($value != '')
			{
                $husb	    = new Person();
			    $husb->setGender(Person::MALE);
			    $husb->save(false);
			    $husb->set('givenname', $value);
			    $idirhusb	= $husb->getIdir();
            }
			break;
	    }

	    case 'husbsurname':
	    {
			if ($husb && $value != '?')
			    $husb->set('surname', $value);
			else
			if ($value != '' && $value != '?')
			{
			    $husb	= new Person();
			    $husb->setGender(Person::MALE);
			    $husb->set('surname', $value);
			    $husb->save(false);
			    $idirhusb	= $husb->getIdir();
			}
			break;
	    }

	    case 'husbmarrsurname':
	    {
		    $family->set('husbmarrsurname', $value);
		    break;
	    }

	    case 'husborder':
	    {
	    	$family->set('husborder', $value);
	    	break;
	    }

	    case 'husbprefmar':
	    {
	    	$family->set('husbprefmar', $value);
	    	break;
	    }

	    case 'husbbirthsd':
	    {
	    	break;
	    }

	    case 'idirwife':
	    case 'wifeidir':	// old form
	    {
			$idirwife	= (int)$value;
			if ($idirwife > 0)
			{		// family has a wife
			    $wife	= new Person(array('idir' => $idirwife));
			    $gender	= $wife->getGender();
			    if ($gender == Person::FEMALE)
			    {
					$family->set('idirwife', $idirwife);
			    }
			}		// family has a wife
			else
			{		// create new wife
			    // for genealogical purposes a family must have
			    // a wife because a family is defined in terms of children
			    $wife	= new Person();
			    $wife->setGender(Person::FEMALE);
			    $wife->setTreeName($treename);
			}		// create new wife
			break;
	    }

	    case 'wifegivenname':
        {
			if ($wife && $wife->getIdir() > 0)
    		    $wife->set('givenname', $value);
			else
			if ($value != '')
			{
			    $wife	    = new Person();
			    $husb->setGender(Person::FEMALE);
			    $wife->save(false);
			    $wife->set('givenname', $value);
			    $idirwife	= $wife->getIdir();
            }
    		break;
	    }

	    case 'wifesurname':
        {
            if ($value != '?')
            {
    		    if ($wife)
                    $wife->set('surname', $value);
            }
    		break;
	    }

	    case 'wifemarrsurname':
	    {
			if ($value != $family->get('husbsurname') && 
			    $wife && $wife->isExisting())
            {
                if (!$family->isExisting())
                    $family->save(true);
			    $nameRec	= new Name(array('idir'		=> $wife,
									         'order'	=> -1,
									         'idmr'		=> $family));
			    $nameRec->set('surname', $value);
			    $nameRec->set('marriednamecreatedby', 1);
			    $nameRec->save(true);
			}
			break;
	    }

	    case 'wifeorder':
	    {
    		$family->set('wifeorder', $value);
    		break;
	    }

	    case 'wifeprefmar':
	    {
    		$family->set('wifeprefmar', $value);
    		break;
	    }

	    case 'wifebirthsd':
	    {
    		// cannot change from this dialog
    		break;
	    }

	    case 'marriednamerule':
	    {
    		$family->set('marriednamerule', $value);
    		break;
	    }

	    case 'mard':
	    {		// date of marriage
    		// this call also sets field 'marsd'
    		$family->set('mard', $value);
    		break;
	    }		// date of marriage

	    case 'marendd':
	    {		// end of marriage
    		// this call also sets field 'marendsd'
    		$family->set('marendd', $value);
    		break;
	    }		// end of marriage

	    case 'marloc':
	    {
			try
			{
			    if ($value && strlen($value) > 0)
			    {
					$marLocation	= new Location(array('location' => $value));
					if (!$marLocation->isExisting())
					    $marLocation->save(true);
					$IDLRMar	= $marLocation->getId();
			    }
			    else
					$IDLRMar	= 1;
			}
			catch(Exception $e)
			{
			    $IDLRMar		= 1;
			}
			$family->set('idlrmar', $IDLRMar);
			break;
	    }

	    case 'seald':
	    {		// date sealed to parents
    		// this call also sets field 'sealsd'
    		$family->set('seald', $value);
    		break;
	    }		// date sealed to parents

	    case 'sealloc':
	    {
			try
			{
			    if ($value && strlen($value) > 0)
			    {
					$SealTemple	= new Temple(array('idtr' => $value));
					$IDTRSeal	= $SealTemple->getIdtr();
			    }
			    else
					$IDTRSeal	= 1;
			}
			catch(Exception $e)
			{
			    $IDTRSeal		= 1;
			}
			$family->set('idtrseal', $idtrseal);
			break;
	    }

	    case 'idtrseal':
	    case 'trseal':	// old form
	    {
    		$family->set('idtrseal', $value);
    		break;
	    }

	    case 'idms':
	    {
    		$family->set('idms', $value);
    		break;
	    }

	    case 'notes':
	    {
    		$family->set('notes', $value);
    		break;
	    }

	    case 'notmarried':
	    {
    		$family->set('notmarried', 1);
    		break;
	    }

	    case 'nochildren':
	    {
    		$family->set('nochildren', 1);
    		break;
	    }

	    case 'submit':
	    case 'submit':
	    case 'idime':
	    case 'cittype':
	    case 'addcitation':
	    case 'addchild':
	    case 'addnewchild':
	    {		// buttons
    		break;
	    }		// buttons

	    default:
	    {
			$namePattern	= "/([a-zA-Z]+)([0-9]*)/";
			$rgResult	= preg_match($namePattern, $key, $matches);
			if ($rgResult === 1)
			{		// match
			    $field	= $matches[1];
			    $id		= $matches[2];
			}		// match
			else
			{		// no match
			    $field	= $key;
			    $id		= '';
			}		// no match
	
			switch(strtolower($field))
			{		// act on field portion of name
			    case 'source':
			    case 'idsx':
			    case 'page':
			    case 'editcitation':
			    case 'delcitation':
			    {	// ignore buttons
					break;
			    }	// ignore buttons
			   
			    case 'editchild':
			    {	// edit child button
					// $id contains IDIR of instance of Person
					break;
			    }	// edit child
	
			    case 'detchild':
			    {	// existing child
					// $id contains IDCR of instance of Child
					break;
			    }	// existing child
	
			    case 'cgiven':
			    {	// given name is first field in next child
					$givenName	= $value;
	
					// complete processing of previous line
					if ($indiv !== null)
					{		// editing previous row
					    if ($indiv->get('givenname') != '')
					    {
							$indiv->save(true);
							if ($childr->getIdir() == 0)
							    $childr->set('idir',
									 $indiv->getIdir());
					    }
					    $indiv		= null;
					}		// editing previous row
					$childr		= null;
					break;
			    }	// given name is first field in next child
	
			    case 'cidir':
			    {	// IDIR of child, may be zero
					$idir		= $value;
					break;
			    }	// IDIR of child
	
			    case 'cidcr':
			    {	// IDCR of child, may be zero
					$idcr		    = $value;
					// $id contains rownum of instance of Child
					if ($idcr == 0 || $id > $maxChildOrder)
					{		// new child
					    if (!isset($idir) ||
							    is_null($idir) ||
							    $idir == 0 ||
							    $idir == 'undefined' ||
	 				        !ctype_digit($idir))
					    {		// no valid IDIR
							$indiv	= new Person();
							$indiv->setTreeName($treename);
							$indiv->save(false);
							$idir	= $indiv->getIdir();
					    }		// no valid IDIR
					    else
					    {		// valid IDIR
							$indiv	= new Person(array('idir' => $idir));
					    }		// valid IDIR
                        print "\n      <idir$id>$idir</idir$id>\n";
                        if ($idmr == 0)
                        {
                            $family->save('createfamily');
                            $idmr   = $family->getIdmr();
                        }
					    $childr		= $family->addChild($idir);
					    $idcr		= $childr->get('idcr');
					    unset($children[$idcr]);
					}		// new child
					else
					{		// existing child
					    $childr		= $children[$idcr];
					    if ($childr->getIdir() == 0)
							$childr->set('idir',
								     $idir);
					    print "\n      <idir$id>$idir</idir$id>\n";
					    $indiv		= $childr->getPerson();
					    unset($children[$idcr]);
					}		// existing child
	
					$childr->set('order', $childOrder);
					$childOrder++;
					$childr->save(true);
					$idcr		= $childr->get('idcr');
					print "\n      <newidcr>$idcr</newidcr>\n";
					$oldname	= $indiv->set('givenname', $givenName);
					break;
			    }	// IDCR of child
	
			    case 'csurname':
			    {	// new child
					// $id contains order of instance of Child
					if ($childr === null)
					{
					    $childr		= $children[$idcr];
					    unset($children[$idcr]);
					    if ($childr)
							$indiv		= $childr->getPerson();
					}
					if ($indiv)
					    $indiv->set('surname', $value);
					break;
			    }	// new child
	
			    case 'cbirth':
			    {	// new child
					// $id contains order of instance of Child
					if ($childr === null)
					{
					    $childr		= $children[$idcr];
					    unset($children[$idcr]);
					    if ($childr)
							$indiv		= $childr->getPerson();
					}
					if ($indiv)
					    $indiv->set('birthd', $value);
					break;
			    }	// new child
	
			    case 'cdeath':
			    {	// new child
					// $id contains order of instance of Child
					if ($childr === null)
					{
					    $childr		= $children[$idcr];
					    unset($children[$idcr]);
					    if ($childr)
							$indiv		= $childr->getPerson();
					}
					if ($indiv)
					    $indiv->set('deathd', $value);
					break;
			    }	// new child
			    
			    case 'cgender':
			    {	// new child
					// $id contains order of instance of Child
					if ($childr === null)
					{
					    $childr		= $children[$idcr];
					    unset($children[$idcr]);
					    if ($childr)
							$indiv		= $childr->getPerson();
					}
					if ($indiv)
					    $indiv->set('gender', $value);
					break;
			    }	// new child
			    
			    default:
			    {		// ignore any unrecognized parameters
					print "      <p>ignore $key</p>\n";
					break;
			    }		// ignore any unrecognized parameters
			}		// act on field portion of name
			break;
	    }

	}	// act on each supported parameter
	print "    </$key>\n";	// close off tag
}		// loop through parameters
print "  </parms>\n";		// close off tag
	
// incomplete update
if ($indiv !== null &&
	$indiv->get('givenname') != '')
{		// editing existing child
	$indiv->save(true);
    print '<idir line="' . __LINE__ . '">' . $indiv->getIdir() . "</idir>\n";
	if ($childr->getIdir() == 0)
	    $childr->set('idir',
				      $indiv->getIdir());
	$indiv		= null;
}		// editing existing child
$childr		= null;

// check to see if the husband's name has changed
// print "<p>updateMarriageXml: \$husb=". gettype($husb) .", \$wife=" . gettype($wife) . "</p>\n";
// print "<p>updateMarriageXml: \$husb=". $husb->changed() .", \$wife=" . $wife->changed() . "</p>\n";
if (is_object($husb) && 
	(strlen($husb->getGivenName()) > 0 ||
	 strlen($husb->getSurname()) > 0 ))
{		// have husband
	try {
	    $husb->save(true);
	    $idirhusb	= $husb->getIdir();
	    $family->set('idirhusb', $idirhusb);
        $family->setName($husb);
        print '<family line="' . __LINE__ . '">' . $family->getName() . "</family>\n";
	} catch(Exception $e)
	{		// setName failed
	    $msg	.= "Husband changed: " . $e->getMessage();
	}		// setName failed
}		// husband changed
else
{		// no husband
	$family->setName(Person::MALE);
}		// no husband

print "<sethusbname>\n";
print "<idirhusb>" . $family->get('idirhusb');
print "</idirhusb>";
print "</sethusbname>\n";
// check to see if the wife's name has changed
if (is_object($wife) &&
	(strlen($wife->getGivenName()) > 0 ||
	 strlen($wife->getSurname()) > 0 ))
{		// have wife
    print "<setwifename>\n";
    $wife->set('sex',1);
	$wife->save(true);
	$idirWife	= $wife->getIdir();
    print "<idirwife>" . $family->get('idirwife');
    print "</idirwife>";
	$family->set('idirwife', $idirWife);
    print "<setnameforwife>\n";
	$family->setName($wife);
    print '<family line="' . __LINE__ . '">' . $family->getName() . "</family>\n";
    print "</setnameforwife>\n";
    print "</setwifename>\n";
}		// wife changed
else
{		// no wife
	$family->setName(Person::FEMALE);
}		// no wife
print "<p>updatefamilyrecord</p>\n";
// update the specified family record
if (is_object($family))
{
print "<savefamily>\n";
print "<idirhusb>" . $family->get('idirhusb');
print "</idirhusb>";
print "</savefamily>\n";
	// any record in the array $children which is still present
	// represents a record that did not match any child in the
	// input, and therefore is deleted from the input form
	if (count($children) > 0)
	{
print "<cleanupchildren>\n";
print "<idirhusb>" . $family->get('idirhusb');
print "</idirhusb>\n";
	    foreach($children as $idcr => $childr)
	    {
error_log(__LINE__ . ' idcr=' . $idcr . "\n", 3, $document_root . "/logs/ajax.log");
print "<child idcr='$idcr'/>\n";
		if ($childr->isExisting())
		    $family->detachChild($idcr, true);
	    }
print "</cleanupchildren>\n";
	}

	// this updates the record in tblMR and all associated records
	// in tblCR
	$family->save(true);

	// include the contents of the updated record	    
	$family->toXml(null);
}		// there is an update to make

} catch(Exception $e)
{
	$msg	.= "Global exception: " . $e->getMessage();
	print "<msg>$msg</msg>\n";
}
// close off root node
print "</marriage>\n";
