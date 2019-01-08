<!DOCTYPE HTML>
<html>
<head>
    <title>Ontario: Test County Marriage Delete</title>
    <meta http-equiv='content-type' CONTENT='text/html; charset=UTF-8'>
    <meta http-equiv='default-style' CONTENT='text/css'>
    <meta name='author' content='James A. Cobban'>
    <meta name='copyright' content='&copy; 2016 James A. Cobban'>
<!--
 *  testDeleteCountyMarriage.html
 *
 *  Prompt the user to enter parameters for a search of the Ontario
 *  County Marriage  database.
 *
 *  History:
 *	2016/01/30	created
-->
    <script src='../jscripts/js20/http.js' language='JavaScript'>
    <script src='../jscripts/util.js' language='JavaScript'>
    </script>
    <link rel='stylesheet' type='text/css' href='../../styles.css'/>
</head>
<body>
 <div class='body'>
  <div class='fullwidth'>
    <span class='h1'>
	Ontario: Test County Marriage Delete
    </span>
    <span class='right'>
	<a href='testMarriageRegDeleteHelp.html' target='_blank'>Help?</a>
    </span>
    <div style='clear: both;'></div>
  </div>
<form action='../deleteCountyMarriageXml.php' 
	method='post' name='distForm'>
  <table id='formTable' class='form'>
    <tr>
      <th class='labelSmall'>Domain:</th>
      <td>
	<input name='RegDomain' type='text' value='CAON'
		class='white leftnc' size='4' maxlength='4'/></td>
    </tr>
    <tr>
      <th class='labelSmall'>Volume:</th>
      <td>
	<input name='Volume' type='text'
		class='white rightnc' size='4' maxlength='4'/></td>
      <th class='labelSmall'>Report Number:</th>
      <td>
	<input name='ReportNo' type='text'
		class='white rightnc' size='6' maxlength='6'/></td>
    </tr>
    <tr>
      <th class='labelSmall'>Item No:</th>
      <td>
	<input name='ItemNo' type='text'
		class='white rightnc' size='4' maxlength='4'/></td>
      <th class='labelSmall'>Count:</th>
      <td>
	<input name='Count' type='text' value='2'
		class='white rightnc' size='4' maxlength='4'/></td>
    </tr>
    <tr>
      <th class='labelSmall'>Row Identifier:</th>
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
<div class='balloon' id='HelpVolume'>
The volume identifier of the original archived records
</div>
<div class='balloon' id='HelpReportNo'>
The report number within the volume.
</div>
<div class='balloon' id='HelpItemNo'>
The item number within the report.
</div>
<div class='balloon' id='HelpCount'>
The maximum number of items to return.
</div>
<div class='balloon' id='HelpDelete'>
Clicking on this button performs the delete.
</div>
<div class='balloon' id='HelprightTop'>
Click on this button to signon to access extended features of the web-site
or to manage your account with the web-site.
</div>
<div class='popup' id='loading'>
Loading...
</div>
</body>
</html>
