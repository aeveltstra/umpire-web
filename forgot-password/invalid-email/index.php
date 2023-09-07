<?php
declare(strict_types=1);
session_start();
$last_email_tainted = $_SESSION['reset_email_tainted'];
?>
<!DOCTYPE html>
<html lang="en" charset="utf-8">
<head>
<title>Error: invalid e-mail - Forgot Password - Umpire</title>
<meta name=description value="That does not look like a valid e-mail address."/>
</head>
<body>
<h1>Error: invalid e-mail - Forgot Password - Umpire</h1>
<h2>That did not look like a valid e-mail address. Try again.</h2>
<form method=post action="../request-reset.php">
<fieldset><legend>Please let me set new credentials.</legend>
<p><label for=email>E-mail:</label></p>
<p><input type=email name=email id=email value="<?=htmlspecialchars($last_email_tainted)?>" size=50/></p>
<p><label><input type=submit value="Request Reset"/></label></p>
</fieldset>
</form>
</body>
</html>
