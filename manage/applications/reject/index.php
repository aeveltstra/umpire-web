<?php
/**
 * Deny access to a user who has requested it.
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.707.1545
 */
declare(strict_types=1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * This script expects to receive input from querystring.
 * Invoke like this: reject/?id=value
 * in which value is the email address to reject.
 * If that doesn't exist, we check the session variable.
 * If neither exist, we throw an error.
 */
$reject_email = null;
$reject_email_variable = 'reject_user_application_email';
if (isset($_GET['id'])) {
    $reject_email = $_GET['id'];
}
if (empty($reject_email)) {
    $reject_email = session_recall($reject_email_variable);
    session_forget($reject_email_variable);
    if (empty($reject_email)) {
        header('Location: ./error-missing-required-parameters/');
        die();
    }
}

$valid_email = filter_var(
    $reject_email,
    FILTER_VALIDATE_EMAIL
);

if (false === $reject_email) {
    header('Location: ./error-missing-required-parameters/');
    die();
}

if (!session_did_user_authenticate()) {
    session_remember($reject_email_variable, $valid_email);
    session_remember('return_to', '/umpire/applications/reject/');
    header('Location: /umpire/sign-in/');
    die();
}

$current_user = session_recall_user_token();
$user_may_reject = db_may_authenticated_user_reject_access($current_user);
if (!$user_may_reject) {
    session_remember($reject_email_variable, $valid_email);
    session_remember('return_to', '/umpire/applications/reject/');
    header('Location: ../access-denied/'); 
    die();
}

$success = db_reject_access($current_user, $valid_email);
if (!$success) {
    header('Location: ./failed/');
    die();
}

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Successfully Rejected Application</title>
<meta name=description content="The request to access Umpire was rejected."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../../../c/main.css"/>
</head>
<body>
    <h1>Successfully Rejected Application</h1>
    <h2>The application to access Umpire has been rejected successfully.</h2>
    <?php
    if (!empty($reject_email)) {
        echo '<p>E-mail address of the rejected application: ',
            htmlspecialchars($valid_email),
                '.</p>';
    }
    ?>
    <p>Would you like to manage other <a href="../../">access applications</a>?</p>
</body>
</html>