<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Test get_browser</title>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=UTF-8">
	<META NAME="CREATED" CONTENT="20060124;11200221">
	<META NAME="Author" CONTENT="James Cobban">
	<link rel="stylesheet" type="text/css" href="styles.css"/>
<!-- Copyright James Cobban 2009 -->
</head>
<body LANG="en-US" DIR="LTR">
<h1>Test get_browser</h1>
<?php  
    $browser	= get_browser();
?>
    <table>
<?php  
    foreach($browser as $key => $value)
    {
?>
	<tr>
	    <th class='left'><?php print $key;?>
	    </th>
	    <td><?php print $value;?>
	    </td>
	</tr>
<?php  
    }
?>
    </table>
</body>
</html>


