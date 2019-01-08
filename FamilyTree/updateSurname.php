<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  updateSurname.php													*
 *																		*
 *  Handle a request to update an individual surname in 				*
 *  the Legacy family tree database.									*
 *																		*
 *  Parameters (passed by POST):										*
 *		surname		unique value of surname.							*
 *		others		valid field names within the Surname record.		*
 *																		*
 *  History:															*
 *		2015/05/18		created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2016/02/06		use showTrace									*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2018/02/04		update links									*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Surname.inc';
require_once __NAMESPACE__ . '/common.inc';

    $surnameRec	= null;
    $title	= "Update Surname Record Failed";
    foreach($_POST as $key => $value)
    {
		if (strtolower($key) == 'surname')
		{		// loop through all parameters
		    $surname	= $value;
            $surnameRec	= new Surname(array('surname' => $surname));
		    $title	= "Update Surname '$surname'";
		}		// surname parameter
    }			// loop through all parameters
    if (is_null($surnameRec))
		$msg	.= "Missing mandatory parameter surname. ";

    if (strlen($msg) == 0)
    {		// update object from $_POST parameters
        $surnameRec->postUpdate(false);

        // save object state to server
        $surnameRec->save(false);

    }		// update object from $_POST parameters

    if (strlen($warn) == 0 && strlen($msg) == 0)
    {		// redirect without output
		header("Location: Names.php?Surname=" . $surname);
		die();
    }		// redirect without output

    htmlHeader($title,
				array(	'/jscripts/js20/http.js',
						'/jscripts/util.js',
						'/FamilyTree/updateSurname.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				'/FamilyTree/Services.php'	=> 'Services',
				"/FamilyTree/nominalIndex.php?name=$surname"
									=> 'Nominal Index'));
?>
  <div class="body">
  <h1>
      <span class="right">
		<a href="updateSurnameHelpen.html" target="help">? Help</a>
      </span>
		<?php print $title; ?>
  </h1>
<?php
    showTrace();

    if (strlen($msg) > 0)
    {
?>
    <p class="message"><?php print $msg; ?></p>
<?php
    }
    else
    {		// update object from $_POST parameters
        $surnameRec->postUpdate(false);

        // save object state to server
        $surnameRec->save(false);

    }		// update object from $_POST parameters
?>
    <p>
      <a href="Names.php?Surname=<?php print $surname; ?>">
		Return to display of members of family '<?php print $surname; ?>'
      </a>
    </p>
<?php
    showTrace();
?>
  </div>
<?php
    pageBot();
?>
</body>
</html>
