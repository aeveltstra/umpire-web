<?php
/**
 * Allow a user to set their access keys.
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.708.1849
 */

declare(strict_types=1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * This script expects to receive input from querystring.
 * Invoke like this: set-access/?reset_key=value1&email=value2
 * in which value1 is the reset key for the user, generated previously,
 * and value2 is the email address for the user, which they provided.
 * If either doesn't exist, we notify the user.
 */
$email = null;
$email_variable = 'email';
$reset_key = null;
$reset_key_variable = 'reset_key';

if (isset($_GET[$email_variable])) {
    $email = $_GET[$email_variable];
}
if (isset($_GET[$reset_key_variable])) {
    $reset_key = $_GET[$reset_key_variable];
}
if (empty($email) || empty($reset_key)) {
    if (empty($email)) {
        header('Location: ./error-missing-required-input/');
        die();
    }
}

$valid_email = filter_var(
    $email,
    FILTER_VALIDATE_EMAIL
);

if (false === $valid_email) {
    header('Location: ./error-missing-required-input/');
    die();
}

[$key, $secret] = db_reset_auth_key_for_user_if_valid($email, $reset_key);

if (empty($key) || empty($secret)) {
    header('Location: ./failed/');
    die();
}

$form_nonce = session_make_and_remember_nonce(
    'authentication_form'
);

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Successfully Reset Your Access Keys - Umpire</title>
<meta name=description content="Your reset request was accepted. 
    Save this info. It will be shown only once."
/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../../../c/main.css"/>
</head>
<body>
    <h1>Successfully Reset Your Access Keys - Umpire</h1>
    <h2>Your reset request was accepted. Save this info: it will be shown
        only once.
    </h2>
    <p>We created new access keys for you. You will be asked for these 
        when you log in. If you lose them, you have to reset them. Our 
        operatives CANNOT retrieve them, and CANNOT send them to you.
    </p>
    <p>Note: our operatives will NEVER ask for your access key or secret
        pass phrase. They may ask for your email address.</p>
    <p>Please print this page or save it as a PDF and store it somewhere 
        else.
    </p>
    <form method=post action="../sign-in/authenticate/">
    <p><label for=username>Access Key:</label></p>
    <p><input type=text name=username id=username size=60 maxlength=512
        autocomplete
        value="<?php echo addslashes($key) ?>"
    /></p>
    <p><label for=password>Secret Pass Phrase:</label></p>
    <p><input type=password name=password id=password size=60 
        maxlength=512 autocomplete
        value="<?php echo addslashes($secret) ?>"
    /></p>
    <p><label for=email>E-mail Address:</label></p>
    <p><input type=email name=email id=email size=60 maxlength=256 
        autocomplete
        value="<?php echo addslashes($email) ?>"
    /></p>
    <fieldset><legend>Did you save the keys?</legend>
        <p><label><input type=radio name=saved_how value="screenshot"/> 
            Yes, I took a screen shot;</label></p>
        <p><label><input type=radio name=saved_how value="print"/> 
            Yes, I printed it to pdf, paper, or similar;</label></p>
        <p><label><input type=radio name=saved_how value="manual_file"/> 
            Yes, I copied them to a file;</label></p>
        <p><label><input type=radio name=saved_how value="not"/> 
            No, I didn't.</label></p>
        <p><label><input type=submit value="Go sign in" /></label></p>
    </fieldset>
<?php if ($form_nonce) {
    echo "<input type=hidden name=nonce value='$form_nonce' />";
}
?>
    </form>
</body>
</html>