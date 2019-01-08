<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  testObjectToXml.php							*
 *									*
 *  Get the information on an instance of Record as an XML response	*
 *  History:								*
 *	2016/02/22	created						*
 *									*
 *  Copyright &copy; 2015 James A. Cobban				*
 ************************************************************************/

require_once __NAMESPACE__ . "/Name.inc";
require_once __NAMESPACE__ . "/common.inc";
$name	= new Name(2000);
if(is_array($name))
    $isarray	= 'yes';
else
    $isarray	= 'no';
htmlHeader('Test PHP function is_array');
?>
<body>
<p>is_array($name)=<?php print $isarray; ?></p>
</body>
</html>
