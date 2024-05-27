<?php
declare(strict_types=1);
/**
 * Shows the form to reset one's authentication credentials
 * 
 * PHP Version 7.5.3.
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.430.1939
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$form_nonce = session_make_and_remember_nonce(
    'authentication_reset_form'
);
?>
<!DOCTYPE html>
<html lang="en" charset="utf-8">
<head><meta charset="utf-8" />
<title>Forgot password - Umpire</title>
<meta name=description content="You don't know how to log in anymore."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="../c/main.css"/>
</head> 
<body>
<h1>Forgot password - Umpire</h1>
<h2>You don't know how to log in anymore.</h2>
<p>Give us your email address and if we know it, we will send you a link 
    to a special web form you can use to set new credentials to access 
    Umpire.</p>
<form method=post action="./check/">
<fieldset><legend>Please let me set new credentials.</legend>
<p><label for=email>E-mail:</label></p>
<p><input type=email name=email id=email value="" size=50/>
<?php
if ($form_nonce) {
    echo "<input type=hidden name=nonce value='";
    echo $form_nonce;
    echo "'/>";
}
?>
</p>
<p><label><input type=submit value="Request Reset"/></label></p>
</fieldset>
</form>
<form method=get action="../sign-in/">
<fieldset><legend>Remember your password?</legend>
<p><label><input type=submit value="Yes, let me sign in!"/></label></p>
</fieldset>
</form>
<form method=get action="../sign-up/">
<fieldset><legend>Don't have an account yet?</legend>
<p><label><input type=submit value="Sign up"/></label></p>
</fieldset>
</form>
</body>
</html>
