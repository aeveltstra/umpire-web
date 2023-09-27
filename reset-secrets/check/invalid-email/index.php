<?php
declare(strict_types=1);
session_start();
$last_email_tainted = $_SESSION['reset_email_tainted'];
?>
<!DOCTYPE html>
<html lang=en charset="utf-8">
<head><meta charset="utf-8" />
<title>Error: invalid e-mail - Forgot Password - Umpire</title>
<meta name=description content="That does not look like a valid e-mail address."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>Error: invalid e-mail - Forgot Password - Umpire</h1>
<h2>That did not look like a valid e-mail address. Try again.</h2>
<form method=post action="../check">
<fieldset><legend>Please let me set new credentials.</legend>
<p><label for=email>E-mail:</label></p>
<p><input type=email name=email id=email value="<?=htmlspecialchars($last_email_tainted)?>" size=50/></p>
<p><label><input type=submit value="Request Reset"/></label></p>
</fieldset>
</form>
</body>
</html>
