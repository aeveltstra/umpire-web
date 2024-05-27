<?php
/**
 * Notify user that their email address appears invalid.
 * 
 * PHP Version 7.5.3.
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.526.2132
 */
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$last_email_tainted = session_recall('reset_email_tainted');
$form_nonce = session_make_and_remember_nonce(
    'authentication_reset_form'
);
?>
<!DOCTYPE html>
<html lang=en charset="utf-8">
<head><meta charset="utf-8" />
<title>Error: invalid e-mail - Forgot Password - Umpire</title>
<meta name=description content="That does not look like a valid e-mail address."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="../../../c/main.css"/>
</head>
<body>
<h1>Error: invalid e-mail - Forgot Password - Umpire</h1>
<h2>That did not look like a valid e-mail address. Try again.</h2>
<form method=post action="../../check/">
<fieldset><legend>Please let me set new credentials.</legend>
<p><label for=email>E-mail:</label></p>
<p><input type=email name=email id=email 
    value="<?php echo htmlspecialchars($last_email_tainted) ?>" 
    size=50/>
</p>
<?php
if ($form_nonce) {
    echo "<input type=hidden name=nonce value='";
    echo $form_nonce;
    echo "'/>";
}
?>
<p><label><input type=submit value="Request Reset"/></label></p>
</fieldset>
</form>
</body>
</html>
