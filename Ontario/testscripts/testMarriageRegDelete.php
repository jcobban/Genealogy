<!DOCTYPE HTML>
<html>
<head>
    <title>Ontario: Test Marriage Registration Delete</title>
    <meta http-equiv='content-type' CONTENT='text/html; charset=UTF-8'>
    <meta http-equiv='default-style' CONTENT='text/css'>
    <meta name='author' content='James A. Cobban'>
    <meta name='copyright' content='&copy; 2013 James A. Cobban'>
<!--
 *  testMarriageRegDelete.html
 *
 *  Prompt the user to enter parameters for a search of the Ontario
 *  Marriage Registration database.
 *
 *  History:
 *	2013/01/09	created
-->
    <script src='../jscripts/util.js' language='JavaScript'>
    </script>
    <link rel='stylesheet' type='text/css' href='../../styles.css'/>
</head>
<body>
 <div class='body'>
  <div class='fullwidth'>
    <span class='h1'>
	Ontario: Test Marriage Registration Delete
    </span>
    <span class='right'>
	<a href='testMarriageRegDeleteHelp.html' target='_blank'>Help?</a>
    </span>
    <div style='clear: both;'></div>
  </div>
<form action='../deleteMarriageRegXml.php' method='post' name='distForm'>
  <table id='formTable' class='form'>
    <tr>
      <th class='labelSmall'>Domain:</th>
      <td>
	<input name='RegDomain' type='text' value='CAON'
		class='white leftnc' size='4' maxlength='4'/></td>
    </tr>
    <tr>
      <th class='labelSmall'>Identification:&nbsp;Year:</th>
      <td>
	<input name='RegYear' type='text'
		class='white rightnc' size='4' maxlength='4'/></td>
      <th class='labelSmall'>Number:</th>
      <td>
	<input name='RegNum' type='text'
		class='white rightnc' size='6' maxlength='6'/></td>
      <th class='labelSmall'>Count:</th>
    </tr>
    <tr>
      <th class='labelSmall'>Row Identifier (yyyynnnnn):</th>
      <td>
	<input name='RowNum' type='text' value=''
		class='white rightnc' size='16' maxlength='16'/></td>
    </tr>
  </table>
  <p>
	<button type='submit' id='Delete'>Delete</button>
  </p>
</form>
</div>
<div class='balloon' id='HelpRegYear'>
The year the marriage was registered.
</div>
<div class='balloon' id='HelpRegNum'>
The registration number within the year.
</div>
<div class='balloon' id='HelpDelete'>
Clicking on this button performs the delete.
</div>
<div class='popup' id='loading'>
Loading...
</div>
</body>
</html>
