<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ToDoList.php							*
 *									*
 *  List of candidate features for the web site family tree software.	*
 *									*
 *  History:								*
 *	2010/12/25	created						*
 *	2017/10/16	use class RecordSet				*
 *									*
 *  Copyright &copy; 2017 James A. Cobban				*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';

function showBlog($projectId)
{
    global $userid;
    global $authorized;

    // show any blog postings
    $bloglist	= new RecordSet('Blogs',
				array(	'table'		=> 'ToDoList',
					'keyvalue'	=> $projectId));

    // display existing blog entries
    foreach($bloglist as $blog)
    {	// loop through all blog entries
	$datetime	= $blog->getTime();
	$username	= $blog->getUser();
	$text		= $blog->getText();
	$text		= str_replace("\n", "</p>\n<p>", $text);
?>
    <p class='label'>
	At <?php print $datetime; ?> user '<?php print $username; ?>' 
	wrote:
    </p>
    <p>
	<?php print $text; ?> 
    </p>
<?php
    }	// loop through all blog entries
    
    if (strlen($authorized) > 0)
    {	// authorized to blog
?>
<form name='blogForm<?php print $projectId; ?>' 
	action='/postProjectBlog.php' method='post'
	enctype='multipart/form-data'>
  <p>
    <input type='hidden' name='projectId<?php print $projectId; ?>'
		value='<?php print $projectId; ?>'>
    <textarea name='message<?php print $projectId; ?>'
		rows='5' cols='100'>[enter message]</textarea>
  </p>
  <p>
    <input type='submit' name='PostBlog<?php print $projectId; ?>' value='Post Blog'>
  </p>
</form>
<?php
    }	// authorized to blog
}	// showBlog

    htmlHeader('To Do List');
?>
<body>
  <div class='topcrumbs'>
    <table class='fullwidth'>
      <tr>
	<td>
	<a href='/genealogy.php'>Genealogy</a>:
	<a href='/genCountry.php?cc=CA'>Canada</a>:
	<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
	<a href='/FamilyTree/Services.php'>Services</a>:
	<td class='right'>
<?php
	rightTop();
?>	
	</td>
      </tr>
    </table>
  </div>
  <div class='body'>
  <table class='fullwidth'>
    <tr>
      <td class='left'>
  <h1>To Do List
  </h1>
      </td>
      <td class='right'>
	<a href='ToDoListHelp.html' target='help'>? Help</a>
      </td>
    </tr>
  </table>
<ul>
  <li>Merge individuals.
    <br>At present merging must be done manually:
    <ol>
	<li>In general one of the individuals is already a child of
	    a family.  Add the other individuals to that family, and
	    sort the children by birth date.
	<li>If there is more than one marriage among the individuals
	    to be merged, ensure that all of the marriages are associated
	    with the individual to be retained by selecting that individual
	    as the spouse.  If the marriages represent different views of
	    the same marriage, then delete all but one of the marriages;
	    This detaches all of the children, who must then be attached
	    one by one to the one retained marriage.
	<li>Change the surname of all but one of the individuals to be
	    merged to 'Delete'.  If one of the individuals in question
	    has an associated marriage then it simplifies the process
	    to retain that one with the original surname.  
	<li>Copy fact and citation information from the individuals to
	    be deleted into the one to be retained.  Do not use alternate
	    birth, christening, death, or burial facts.  Identify conflicting
	    evidence in the citation data.
	<li>Detach the children with surname 'Delete' from the family.
	<li>Edit each of the individuals with surname 'Delete'.  Since these
	    individuals no longer have any parental or marriage relationships
	    the edit page gives you the option to delete them out of the
	    database.
    </ol>
<?php
	showBlog(1);
?>
  <li>Outline descendants report.
<?php
	showBlog(2);
?>
  <li>Outline ancestors report.
<?php
	showBlog(3);
?>
  <li>Relationship calculator.
<?php
	showBlog(4);
?>
</ul>
</div>
<div class='botcrumbs'>
<table class='fullwidth'>
  <tr>
    <td class='label'>
	<a href='mailto:webmaster@jamescobban.net?subject=To@20Do@20List'>Contact Author</a>
	<br/>
	<a href='/genealogy.php'>Genealogy</a>:
	<a href='/genCountry.php?cc=CA'>Canada</a>:
	<a href='/Canada/genProvince.php?domain=CAON'>Ontario</a>:
	<a href='/FamilyTree/Services.php'>Services</a>:
    </td>
    <td class='right'>
	<img SRC='/logo70.gif' height='70' width='70' alt='James Cobban Logo'>
    </td>
  </tr>
</table>
</div>
</body>
</html>
