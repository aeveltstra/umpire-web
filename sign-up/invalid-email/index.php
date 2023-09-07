<?php
declare(strict_types=1);
session_start();
$last_email_tainted = $_SESSION['email_tainted'];
$last_reason = $_SESSION['reason_tainted'];
?>
<!DOCTYPE html>
<html lang="en" charset="utf-8">
<head>
<title>Error: invalid e-mail - Sign up - Umpire</title>
<meta name=description value="That does not look like a valid e-mail address."/>
</head>
<body>
<h1>Error: invalid e-mail - Sign up - Umpire</h1>
<h2>That did not look like a valid e-mail address. Try again.</h2>
<form method=post action="../request.php">
<fieldset><legend>You want an account to gain special access to Umpire.</legend>
<p><label for=email>E-mail:</label></p>
<p><input type=email name=email id=email
value="<?=htmlspecialchars($last_email)?>" size=50/></p>
<p><label for=reason>Reason for wanting access:</label></p>
<p><textarea name=reason id=reason width=60
height=5><?=htmlspecialchars($last_reason)?></textarea></p>
<p><label><input type=submit value="Request Access"/></label></p>
</fieldset>
</form>
</body>
</html>
