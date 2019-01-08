<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testGetPersons.php							*
 *									*
 *  Test new PersonSet class						*
 *									*
 *  History:								*
 *	2017/12/13	created						*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
    require_once __NAMESPACE__ . '/Person.inc';
    require_once __NAMESPACE__ . '/PersonSet.inc';

    $idlr	= 17;	// Caradoc, Middlesex, ON, CA
    $surname	= 'Cobban';
    $givenname	= '';
    $occupation	= '';
    $givennames	= array();
    $birthsdlo	= '';
    $birthsdhi	= '';
    $loose	= false;
    $gender	= 2;
    foreach($_GET as $fldname => $value)
    {
	switch(strtolower($fldname))
	{
	    case 'idlr':
	    {
		$idlr		= $value;
		break;
	    }

	    case 'surname':
	    {
		$surname	= $value;
		break;
	    }

	    case 'givenname':
	    {
		$givenname	= $value;
		$givennames	= explode(' ',$givenname);
		break;
	    }

	    case 'occupation':
	    {
		$occupation	= $value;
		break;
	    }

	    case 'birthsdlo':
	    {
		$birthsdlo	= $value;
		break;
	    }

	    case 'birthsdhi':
	    {
		$birthsdhi	= $value;
		break;
	    }

	    case 'gender':
	    {
		$gender		= $value;
		break;
	    }

	    case 'loose':
	    {
		if (strtolower($value) == 'y')
		    $loose	= true;
		break;
	    }
	}	// switch
    }		// foreach

    / enable debug output
    $subject	= rawurlencode("Test class PersonSet");

    htmlHeader('Test class PersonSet');
?>
<body>
<?php
  pageTop(array('/genealogy.php'		=> 'Genealogy',
		'/FamilyTree/Services.php'	=> 'Family Tree Services'));
?>
<div class='body'>
    <h1>
	Test class PersonSet
   </h1>
    <h2>Search by Major Event IDLR</h2>
    <p>
      <br>    $indParms	= array(array('idlrbirth' =&gt; <?php print $idlr; ?>,
      <br>			      'idlrchris' =&gt; <?php print $idlr; ?>,
      <br>			      'idlrdeath' =&gt; <?php print $idlr; ?>,
      <br>			      'idlrburied' =&gt; <?php print $idlr; ?>)
      <br>			'surname' =&gt; '<?php print $surname; ?>');
    </p>
<?php
	    $indParms	= array(array('idlrbirth' => $idlr,
					'idlrchris' => $idlr,
					'idlrdeath' => $idlr,
					'idlrburied' => $idlr),
				'surname' => $surname,
				'order'	  => 'tblIR.Surname, tblIR.GivenName, tblIR.BirthSD, tblIR.DeathSD');
);
	    $indivs	= new PersonSet($indParms);
	    $info	= $indivs->getInformation();
	    print "<p>" . $info['query'] . "</p>\n";
	    foreach($indivs as $idir => $indiv)
	    {
?>
    <p>IDIR=<?php print $idir; ?>
	<a href='/Person.php?idir=<?php print $idir; ?>'><?php print $indiv->getName(); ?></a></p>
<?php
	    }
?>
    <h2>Search by Birth Location</h2>
    <p>
      <br>    $indParms	= array(idlrbirth' =&gt; <?php print $idlr; ?>,
				'surname' =&gt; '<?php print $surname; ?>');
    </p>
<?php
	    $indParms	= array('idlrbirth' => $idlr,
				'surname'   => $surname,
				'order'	    => 'tblIR.Surname, tblIR.GivenName, tblIR.BirthSD, tblIR.DeathSD');
	    $indivs	= new PersonSet($indParms);
	    $info	= $indivs->getInformation();
	    print "<p>" . $info['query'] . "</p>\n";
	    foreach($indivs as $idir => $indiv)
	    {
?>
    <p>IDIR=<?php print $idir; ?>
	<a href='/Person.php?idir=<?php print $idir; ?>'><?php print $indiv->getName(); ?></a></p>
<?php
	    }

	    if (strlen($occupation) > 0)
	    {	// search for occupation
?>
    <h2>Search by Occupation</h2>
    <p>
      <br>    $indParms	= array('occupation' =&gt; '<?php print $occupation; ?>',
      <br>			'surname' =&gt; '<?php print $surname; ?>');
    </p>
<?php
	    $occupationList	= explode(',',$occupation);
	    $indParms	= array('occupation' => $occupationList,
				'surname'    => $surname,
				'order'	     => 'tblIR.Surname, tblIR.GivenName, tblIR.BirthSD, tblIR.DeathSD');
	    $indivs	= new PersonSet($indParms);
	    $info	= $indivs->getInformation();
	    print "<p>" . $info['query'] . "</p>\n";
	    foreach($indivs as $idir => $indiv)
	    {
?>
    <p>IDIR=<?php print $idir; ?>
	<a href='/Person.php?idir=<?php print $idir; ?>'><?php print $indiv->getName(); ?></a></p>
<?php
	    }
	    }	// search for occupation

	if ($loose)
	{
?>
    <h2>Search by Loose Surname</h2>
    <p>
      <br>    $indParms	= array('loose' =&gt; true,
				'surname' =&gt; '<?php print $surname; ?>',
				'givenname' =&gt; '<?php print $givenname; ?>'
<?php
	    if ($gender == 0 || $gender == 1)
	    {
?>
		,'gender' =&gt; <?php print $gender; ?>
<?php
	    }

	    if (strlen($birthsdlo) > 0 && strlen($birthsdhi))
	    {
?>
		,'birthsd' =&gt; array(<?php print $birthsdlo; ?>, <?php print $birthsdhi; ?>)
<?php
	    }
?>
);
    </p>
<?php
	    $indParms	= array('loose' => true,
				'surname' => $surname);
	    if (count($givennames) > 1)
		$indParms['givenname']	= $givennames;
	    else
	    if (strlen($givenname) > 0)
		$indParms['givenname']	= $givenname;

	    if ($gender == 0 || $gender == 1)
		$indParms['gender']	= $gender;
	    if (strlen($birthsdlo) > 0 && strlen($birthsdhi))
	    {
		$indParms['birthsd']	= array($birthsdlo, $birthsdhi);
	    }
	    $indParms['order']		= 'tblNX.Surname, tblNX.GivenName, tblIR.BirthSD, tblIR.DeathSD';
	    $indivs	= new PersonSet($indParms);
	    $info	= $indivs->getInformation();
	    print "<p>" . $info['query'] . "</p>\n";
	    foreach($indivs as $idir => $indiv)
	    {
?>
    <p>IDIR=<?php print $idir; ?>
	<a href='/Person.php?idir=<?php print $idir; ?>'><?php print $indiv->getName(); ?></a></p>
<?php
	    }
	}
?>
    <form name='queryForm' action='testGetPersons.php' method='get'>
      <div class='row'>
	<label class='column1' for='Surname'>Surname:</label>
	<input type='text' name='Surname' id='Surname' class='white left'
		value='<?php print $surname; ?>' size='20'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='GivenName'>Given Name:</label>
	<input type='text' name='GivenName' id='GivenName' class='white left'
		value='<?php print $givenname; ?>' size='20'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='Gender'>Gender:</label>
	<select name='Gender' id='Gender' class='white left' size='1'>
	  <option value='0' <?php if ($gender == 0) print "selected='selected'"; ?>>Male</option>
	  <option value='1' <?php if ($gender == 1) print "selected='selected'"; ?>>Female</option>
	  <option value='2' <?php if ($gender == 2) print "selected='selected'"; ?>>Don't Care</option>
	</select>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='IDLR'>IDLR:</label>
	<input type='text' name='IDLR' id='IDLR' class='white rightnc'
		value='<?php print $idlr; ?>' size='5'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='Occupation'>Occupation:</label>
	<input type='text' name='Occupation' id='Occupation' class='white left'
		value='<?php print $occupation; ?>' size='25'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='BirthSDLo'>Birth SD Lo:</label>
	<input type='text' name='BirthSDLo' id='BirthSDLo' class='white rightnc'
		value='<?php print $birthsdlo; ?>' size='8'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='BirthSDHi'>Birth SD Hi:</label>
	<input type='text' name='BirthSDHi' id='BirthSDHi' class='white rightnc'
		value='<?php print $birthsdhi; ?>' size='8'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<label class='column1' for='Loose'>Loose:</label>
	<input type='checkbox' name='Loose' id='Loose' class='white left'
		<?php if ($loose) print "checked='checked'"; ?> value='Y'>
	<div style='clear: both;'></div>
      </div>
      <div class='row'>
	<button type='submit' id='Submit'>Submit</button>
	<div style='clear: both;'></div>
      </div>

    </form>
</div>
<?php
    pageBot();
?>
</body>
</html>
