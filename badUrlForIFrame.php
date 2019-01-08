<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  badUrlForIFrame.php							*
 *									*
 *  Handle an attempt to load an insecure page into an <iframe>		*
 *									*
 *  Parameters:								*
 *	src	requested URL						*
 *									*
 *    History:								*
 *	2016/06/23	created						*
 ************************************************************************/

?>
<!DOCTYPE HTML >
<html>
  <head>
    <title>Bad URL for IFRAME SRC Attribute</title>
    <link rel='stylesheet' type='text/css' href='/styles.css'/>
  </head>
<body>
<div class='body'>
    <h1>Bad URL for IFRAME SRC Attribute</h1>
<p>The URL "<a href='<?php print $_GET['src']; ?>' target='_blank'><?php print $_GET['src']; ?></a>"
is insecure and cannot be loaded.
</div> <!-- body -->
</body>
</html>
