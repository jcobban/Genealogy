<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updatePicture.php							*
 *									*
 *  Handle a request to update an individual picture in 		*
 *  the Legacy family tree database.					*
 *									*
 *  Parameters:								*
 *	idbr	unique numeric identifier of the Picture record		*
 *		to update						*
 *	others	any field name defined in the Picture record		*
 *									*
 *  History:								*
 *	2011/05/28	created						*
 *	2012/01/13	change class names				*
 *	2013/02/24	permit being called when the database record	*
 *			has not been written yet			*
 *	2013/12/07	$msg and $debug initialized by common.inc	*
 *	2014/10/06	error in updating existing picture		*
 *	2015/01/06	redirect diagnostic information to $warn	*
 *	2015/07/02	access PHP includes using include_path		*
 *	2016/01/19	add id to debug trace				*
 *			include http.js before util.js			*
 *	2017/07/23	class LegacyPicture renamed to class Picture	*
 *	2017/09/12	use set(					*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/common.inc';

    // identify the requested instance of Picture
    $idbr		= null;		// key of instance of Picture
    $idir		= null;
    $idtype		= null;
    $picture		= null;		// instance of Picture

    // examine parameters that identify the record instance
    foreach($_POST as $key => $value)
    {				// loop through all parameters
	if ($debug)
	    $warn	.= "<p>\$_POST['$key']='$value'</p>\n";
	switch(strtolower($key))
	{			// act on specific parameters
	    case 'idbr':
	    {			// identifier present
		$idbr	= $value;
		if (strlen($idbr) > 0 &&
		    ctype_digit($idbr) &&
		    $idbr > 0)
		{		// existing picture
		    try {
			$picture	= new Picture($idbr);
		    } catch (Exception $e) {
			$msg	.= $e->getMessage();
		    }
		}		// existing picture
		break;
	    }			// IDBR

	    case 'idir':
	    {			// adding new picture to record
		$idir		= $value;
		break;
	    }			// adding new picture to record

	    case 'idtype':
	    {			// adding new picture to record
		$idtype		= $value;
		break;
	    }			// adding new picture to record
	}			// act on specific parameters
    }				// loop through all parameters

    if (is_null($picture))
    {		// new picture
	if (!is_null($idir) && !is_null($idtype))
	    $picture	= new Picture('', $idir, $idtype);
	else
	    $msg	.= 'need at least idir and idtype parameters to create new instance. ';
    }		// new picture

    if (strlen($msg) == 0)
    {				// no errors
	// apply any changes passed in parameters
	foreach($_POST as $key => $value)
	{				// loop through all parameters
	    switch(strtolower($key))
	    {			// act on specific parameters
		case 'idbr':
		{		// IDBR;
		    // cannot be changed
		    break;
		}		// IDBR;

		case 'idir':
		case 'idtype':
		case 'pictype':
		case 'picorder':
		case 'picname':
		case 'picnameurl':
		case 'idbppic':
		case 'piccaption':
		case 'picd':
		case 'picsd':
		case 'picdate':
		case 'picdesc':
		case 'picprint':
		case 'picsoundname':
		case 'picsoundnameurl':
		case 'idbpsound':
		case 'used':
		case 'picpref':
		case 'filingref':
		{		// update field values
		    $picture->set($key, $value);
		    break;
		}		// update field values
	    }			// act on specific parameters
	}			// loop through all parameters

	// update the record in the database server
	$picture->save(false);
    }				// no errors

    // the web page is only displayed if there is an error
    htmlHeader($msg,
		array(	'/jscripts/js20/http.js',
			'/jscripts/util.js',
			'updatePicture.js'));
?>
<body>
  <h1>updatePicture.php:</h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
    <p class='message'><?php print $msg; ?></p>
<?php
    }
?>
<form name='updForm' action='<?php print $msg; ?>'>
<p>
  <button type='button' id='Close'>Close</button>
</p>
</form>
</body>
</html>
