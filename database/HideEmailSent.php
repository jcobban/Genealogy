<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<head>
<meta http-equiv=Content-Type content="text/html; charset=utf8">
<meta name=AUTHOR content="James Cobban">
<title>Confirmation E-mail Sent</title>
<link rel="stylesheet" type="text/css" href="/styles.css"/>
</head>

<body>
    <h1>Confirmation E-mail Sent</h1>
<p>An E-mail has been sent to:
<?php
    $email	= $_GET['to'];
	print($email);
?>
<p>You must acknowledge this e-mail to complete the registration process.
</body>

</html>
