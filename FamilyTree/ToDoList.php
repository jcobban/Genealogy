<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  ToDoList.php														*
 *																		*
 *  List of candidate features for the web site family tree software.	*
 *																		*
 *  History:															*
 *		2010/12/25		created											*
 *		2012/01/13		change class names								*
 *		2013/06/01		use pageTop and pageBot to standardize			*
 *						appearance										*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2014/03/12		use CSS rather than tables to perform layout	*
 *		2015/07/02		access PHP includes using include_path			*
 *		2017/09/13		class BlogList replaced by Blog::getBlogs		*
 *		2017/10/16		Blog::getBlogs replaced by RecordSet			*
 *		2018/11/19      change Helpen.html to Helpen.html                 *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/RecordSet.inc';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *  function showBlog													*
 *																		*
 *  Display blog entries for a project.									*
 *																		*
 *  Input:																*
 *		$projectId		project identifier								*
 ************************************************************************/
function showBlog($projectId)
{
    global $userid;
    global $authorized;

    // show any blog postings
    $getParms	= array('bl_table'	=> 'ToDoList',
						'bl_keyvalue'	=> $projectId);
    $bloglist	= new RecordSet('Blogs', $getParms);

    // display existing blog entries
    foreach($blogList as $blog)
    {		// loop through all blog entries
		$datetime	= $blog->getTime();
		$username	= $blog->getUser();
		$text		= $blog->getText();
		$text		= str_replace("\n", "</p>\n<p>", $text);
?>
    <p class="label">
		At <?php print $datetime; ?> user '<?php print $username; ?>' 
		wrote:
    </p>
    <p>
		<?php print $text; ?> 
    </p>
<?php
    }		// loop through all blog entries
    
    if (strlen($authorized) > 0)
    {		// authorized to blog
?>
<form name="blogForm<?php print $projectId; ?>" 
		action="postProjectBlog.php" method="post"
		enctype="multipart/form-data">
  <p>
    <input type="hidden" name="projectId<?php print $projectId; ?>"
		value="<?php print $projectId; ?>">
    <textarea name="message<?php print $projectId; ?>"
				rows="5" cols="100">[enter message]</textarea>
  </p>
  <p>
    <input type="button" name="PostBlog<?php print $projectId; ?>" value="Post Blog">
  </p>
</form>
<?php
    }		// authorized to blog
}		// showBlog

    htmlHeader('To Do List',
				array(  '/jscripts/util.js',
						'/jscripts/js20/http.js',
						'ToDoList.js'));
?>
<body>
<?php
    pageTop(array('/genealogy.php'		=> 'Genealogy',
				  '/FamilyTree/Services.php'	=> 'Services'));
?>
  <div class="body">
  <h1>
      <span class="right">
		<a href="ToDoListHelpen.html" target="help">? Help</a>
      </span>
      Software Candidate Features
  </h1>
  <h2>Outline descendants report</h2>
    <p>Basic report now provided.
    <p>Report customization through HTML and CSS yet to do.
<?php
		showBlog(2);
?>
  <h2>Outline ancestors report</h2>
    <p>Basic report now provided.
    <p>Report customization through HTML and CSS yet to do.
<?php
		showBlog(3);
?>
  <h2>Relationship calculator</h2>
    <p>Current implementation is imperfect.
    <p>
<?php
		showBlog(4);
?>
</div>
<?php
    pageBot();
?>
</body>
</html>
