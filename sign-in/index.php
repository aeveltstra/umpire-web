<?php
declare(strict_types=1);
/**
 * Shows the sign-up form.
 * @author A.E.Veltstra
 * @versio 2.23.1014.1010
 */
?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>Sign in - Umpire</title>
<meta name=description content="Tell us how to recognize you"/>
<meta name=viewport content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>Sign in - Umpire</h1>
<h2>Let us recognize you</h2>
<form method=post action="authenticate/">
<fieldset><legend>Gain special access to Umpire.</legend>
<p><label for=email>E-mail Address:</label></p>
<p><input type=email name=email id=email value="" size=60 maxlength=256 /></p>
<p><label for=key>Access Key:</label></p>
<p><input type=text name=key id=key value="" size=60 maxlength=512 /></p>
<p><label for=secret>Secret Pass Phrase:</label></p>
<p><input type=password name=secret id=secret size=60 maxlength=512 /></p>
<p><label><input type=submit value="Sign in"/></label></p>
</fieldset>
</form>
<form method=get action="/umpire/reset-secrets/">
<fieldset><legend>Forgot your secrets?</legend>
<p>Note: Umpire operatives CANNOT retrieve your access key or pass phrase, and thus CANNOT email them to you. If you forgot, you will have to reset them.</p>
<p><label><input type=submit value="Reset Secrets"/></label></p>
</fieldset>
</form>
<form method=get action="/umpire/sign-up/">
<fieldset><legend>Don't have an account yet?</legend>
<p><label><input type=submit value="Sign up"/></label></p>
</fieldset>
</form>
</body>
</html>