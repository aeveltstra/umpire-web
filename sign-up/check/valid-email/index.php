<?php
/**
 * Request credentials to access Umpire.
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1024.2244
 */
 error_reporting(E_ALL);

/**
 * db_utils.php contains db functionality like query(sql) and 
 * db_exec(sql, params_typestring, params).
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

$email = session_remember('add_user_email_valid');
$reason = session_remember('add_user_reason_tainted');
$agreed = session_remember('add_user_agreed_tainted');
$is_form_nonce_valid = session_is_nonce_valid('sign_up_form');
session_forget_nonce('sign_up_form');
session_forget('add_user_agreed_tainted');
session_forget('add_user_reason_tainted');
session_forget('add_user_email_valid');

if (   empty($email) 
    || empty($reason) 
    || empty($agreed) 
    || !$is_form_nonce_valid
) {
    header('Location: ./error-missing-input/');
    die();
}

if ('on' === $agreed) {
    $agreed = 'yes';
}

$is_known = db_is_email_known(
    $email
);
if ($is_known) {
    header('Location: ./sent/');
    die;
}

$candidate_hash = db_hash_candidate($email);
$user_added = db_add_user($candidate_hash);
if (empty($user_added)) {
    header('Location: ./error-storage-failure/');
    die;
} 

[$key, $secret] = $user_added;
session_remember('access_request_email', $email);
session_remember('access_request_reason', $reason);
session_remember('access_request_agreed_to_terms', $agreed);
$form_nonce = session_make_and_remember_nonce('access_request_form');
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>E-mail Address Accepted - Umpire</title>
<meta name="description" value="Save this info. It will be shown only once."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>E-mail Address Accepted - Umpire</h1>
<h2>Save this info. It will be shown only once.</h2>
<p>We created some access keys for you. You will be asked for these when you log in. If you lose them, you have to reset them. Our operatives CANNOT retrieve them, and CANNOT send them to you.</p>
<p>Note: our operatives will NEVER ask for your key or secret pass phrase. They may ask for your email address.</p>
<p>Please print this page or save it as a PDF and store it somewhere else.</p>
<form method=post action="send/">
    <fieldset><legend>The email address you provided:</legend>
        <p><?php echo addslashes($email) ?></p>
    </fieldset>
    <fieldset><legend>This is your access key:</legend>
        <p><?php echo addslashes($key) ?></p>
    </fieldset>
    <fieldset><legend>And this is your secret pass phrase:</legend>
        <p><?php echo addslashes($secret) ?></p>
    </fieldset>
    <fieldset><legend>Did you save the keys?</legend>
        <p><label><input type=radio name=saved_how value="screenshot"/> Yes, I took a screen shot;</label></p>
        <p><label><input type=radio name=saved_how value="print"/> Yes, I printed it to pdf, paper, or similar;</label></p>
        <p><label><input type=radio name=saved_how value="manual_file"/> Yes, I copied them to a file;</label></p>
        <p><label><input type=radio name=saved_how value="not"/> No, I didn't.</label></p>
    </fieldset>
    <fieldset><legend>Last step (step 2 of 2):</legend>
<?php
    if ($form_nonce) {
        echo "        <input type=hidden value='${form_nonce}' id='nonce' />\r\n";
    }
?>
        <p><label><input type=submit value="Apply for Access"/></label></p>
    </fieldset>
</form>
</body>
</html>
