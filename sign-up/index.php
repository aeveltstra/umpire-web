<?php
/**
 * Account creation form.
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.430.2039
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$did_authenticate = session_did_user_authenticate();
$form_nonce = session_make_and_remember_nonce('sign_up_form');
?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>Sign up - Umpire</title>
<meta name=description content="Ask us to create an account for you"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>Sign up - Umpire</h1>
<h2>Ask us to create an account for you</h2>
<p>Remember that anyone can enter cases. The process is anonymous. You do
    not need an account to enter cases. Reasons for getting an account, 
    include:</p>
<ul><li>Reviewing cases already entered;</li>
    <li>Correcting existing cases;</li>
    <li>Comparing cases of missing people against those of deceased 
        people;</li>
    <li>Exporting our case lists as a spreadsheet;</li>
    <li>And more!</li>
</ul>
<p>Your request WILL be reviewed by one of our operatives. Access will 
    NOT be granted automatically. Argue your need to gain access to our 
    system. Please bear in mind our operatives are volunteers. They'll 
    get around to your request as soon as possible.</p>
<form method=post action="check/">
<?php
if ($form_nonce) {
    echo "<input type=hidden value='${form_nonce}' name=nonce />\r\n";
}
?>
    <fieldset><legend>Terms of Use</legend>
        <p><label><input 
            type=checkbox 
            name=agree />I have read and agree to the <a 
            href="terms/">terms of use</a>.</label>
        </p>
    </fieldset>
    <fieldset><legend>I want special access to Umpire.</legend>
        <p><label for=email>My E-mail Address:</label></p>
        <p><input type=email name=email id=email value="" size=60 /></p>
<?php
if ($did_authenticate) {
    echo "<p><label for=reason class=warning><strong>Warning:</strong> 
            you appear to be logged in. Please explain why you would need
            a second user account:</label></p>\r\n";
} else {
    echo "<p><label for=reason>My reason for wanting access:</label></p>";
}
?>
        <p><textarea name=reason id=reason cols=60 rows=5></textarea></p>
    </fieldset>
    <fieldset><legend>Step 1 of 2</legend>
        <p><label><input 
            type=submit 
            value="Check my E-mail Address"/></label>
        </p>
    </fieldset>
</form>
<?php if (!$did_authenticate) { ?>
    <form method=get action="../sign-in/">
        <fieldset><legend>Already have an account?</legend>
            <p><label><input 
                type=submit 
                value="Yes, let me sign in!"/></label>
            </p>
        </fieldset>
    </form>
<?php } ?>
</body>
</html>

