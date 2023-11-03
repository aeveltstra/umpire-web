<?php
declare(strict_types=1);
/**
 * Shows the form with applications for special access.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1102.2036
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$did_authenticate = session_did_user_authenticate();
if (!$did_authenticate) {
    session_remember('return_to', '/umpire/applications/');
    header('Location: ../sign-in/');
    die();
}
$form_nonce = session_make_and_remember_nonce(
    'access_applications_form'
);
?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>Respond to Access Requests - Umpire</title>
<meta name=description content="Decide whether you want to accept or reject their applications."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>Respond to Access Requests - Umpire</h1>
<h2>Decide whether you want to accept or reject their applications.</h2>

<form method=post action="process/">
<fieldset><legend>Accept these</legend>
<p><label for="accept_emails">One or more email addresses, separated by a comma</label></p>
<p><input type=text id=accept_emails placeholder="some@one.here,someone@else.there" size=60 maxlength=512 /></p>
</fieldset>
<fieldset><legend>Reject these</legend>
<p><label for="reject_emails">One or more email addresses, separated by a comma</label></p>
<p><input type=text id=reject_emails placeholder="some@one.here,someone@else.there" size=60 maxlength=512 /></p>
</fieldset>
<fieldset><legend>Done?</legend>
<?php
    if ($form_nonce) {
        echo "<input type=hidden name=nonce value='$form_nonce' />\r\n";
    }
    echo "\r\n<p>";
    echo session_recall_user_token();
    echo "</p>\r\n";
?>
<p><label><input type=submit value="Process" /></label></p>
</fieldset>
</form>
</body>
</html>
