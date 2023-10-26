<?php
declare(strict_types=1);

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
session_forget_nonce('sign_up_form');
$form_nonce = session_make_and_remember_nonce('sign_up_form');
$add_user_email_invalid = session_recall('add_user_email_invalid');
$add_user_reason_tainted = session_recall('add_user_reason_tainted');
?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>Error: invalid e-mail - Sign up - Umpire</title>
<meta name=description content="That does not look like a valid e-mail address."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>Error: invalid e-mail - Sign up - Umpire</h1>
<h2>That did not look like a valid e-mail address. Try again.</h2>
<form method=post action="../../check/">
<fieldset><legend>I want special access to Umpire.</legend>
<p><label for=email>My E-mail:</label></p>
<p><input type=email name=email id=email
value="<?php echo htmlspecialchars($add_user_email_invalid); ?>" size=60/></p>
<p><label for=reason>My reason for wanting access:</label></p>
<p><textarea name=reason id=reason cols=60
rows=5><?php echo htmlspecialchars($add_user_reason_tainted); ?></textarea></p>
</fieldset>
<fieldset><legend>Options</legend>
<p><label><input type=checkbox name=agree /> I have read and agree to </label><a href="../../terms/">the terms and conditions</a></p>
<?php
    if ($form_nonce) {
        echo "<input name=nonce type=hidden value='${form_nonce}'/>\r\n";
    }
?>
<p><label><input type=submit value="Request Access"/></label></p>
</fieldset>
</form>
</body>
</html>

