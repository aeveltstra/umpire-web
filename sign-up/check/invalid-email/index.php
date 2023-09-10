<?php
declare(strict_types=1);
session_start();
$add_email_valid = $_SESSION['add_email_valid'];
$add_reason_tainted = $_SESSION['add_reason_tainted'];
?>
<!DOCTYPE html>
<html lang=en charset="utf-8">
<head>
<title>Error: invalid e-mail - Sign up - Umpire</title>
<meta name=description value="That does not look like a valid e-mail address."/>
</head>
<body>
<h1>Error: invalid e-mail - Sign up - Umpire</h1>
<h2>That did not look like a valid e-mail address. Try again.</h2>
<form method=post action="../check">
<fieldset><legend>I want an account to gain special access to Umpire.</legend>
<p><label for=email>My E-mail:</label></p>
<p><input type=email name=email id=email value="<?=htmlspecialchars($last_email_tainted)?>" size=50/></p>
<p><label for=reason>My reason for wanting access:</label></p>
<p><textarea name=reason id=reason cols=60 rows=10><?=htmlspecialchars($last_reason_tainted)?></textarea></p>
<p><label><input type=submit value="Request Access"/></label></p>
</fieldset>
</form>
</body>
</html>
