<?php
declare(strict_types=1);
/**
 * Shows the sign-up form.
 * 
 * PHP Version 7.5.3.
 * 
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.312.1932
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$did_authenticate = session_did_user_authenticate();
$form_nonce = session_make_and_remember_nonce(
    'authentication_form'
);
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
<form method=post action="./authenticate/">
<fieldset><legend>Gain special access to Umpire.</legend>
<p><label for=username>Access Key:</label></p>
<p><input type=text name=username id=username size=60 maxlength=512 /></p>
<p><label for=password>Secret Pass Phrase:</label></p>
<p><input type=password name=password id=password size=60 maxlength=512 /></p>
<p><label for=email>E-mail Address:</label></p>
<p><input type=email name=email id=email size=60 maxlength=256 /></p>
<?php
if ($did_authenticate) {
    echo "<p class=warning>Warning: you already are logged in. 
    Pressing this button will log you out first.</p>\r\n";
}
if ($form_nonce) {
    echo "<input type=hidden name=nonce value='$form_nonce' />\r\n";
}
?>
<p><label><input type=submit value="Sign in"/></label></p>
</fieldset>
</form>
<form method=get action="/umpire/reset-secrets/">
<fieldset><legend>Forgot your secrets?</legend>
<p>Note: Umpire operatives CANNOT retrieve your access key or pass 
  phrase, and thus CANNOT email them to you. If you forgot, you will have
  to reset them.</p>
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
