<?php
/**
 * Approve access to a user who has requested it.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1105.2010
 */
declare(strict_types=1);
error_reporting(E_ALL);
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * This script expects to receive input from querystring.
 * Invoke like this: accept/?id=value
 * in which value is the email address to accept.
 * If that doesn't exist, we check the session variable.
 * If neither exist, we throw an error.
 */
$accept_email = null;
$accept_email_variable = 'accept_user_application_email';
if (isset($_GET['id'])) {
    $accept_email = $_GET['id'];
}
if (empty($accept_email)) {
    $accept_email = session_recall($accept_email_variable);
    session_forget($accept_email_variable);
    if (empty($accept_email)) {
        header('Location: ./error-missing-required-parameters/');
        die();
    }
}

if (!session_did_user_authenticate()) {
    session_remember($accept_email_variable, $accept_email);
    session_remember('return_to', '/umpire/applications/accept/');
    header('Location: /umpire/sign-in/');
    die();
}

$current_user = session_recall_user_token();
$user_may_accept = db_may_authenticated_user_accept_access($current_user);
if (!$user_may_accept) {
    session_remember($accept_email_variable, $accept_email);
    session_remember('return_to', '/umpire/applications/accept/');
    header('Location: ../access-denied/'); 
    die();
}

$success = db_accept_access($current_user, $accept_email);
if (!$success) {
    header('Location: ./failed/');
    die();
}

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Successfully Accepted Application</title>
<meta name=description content="The application to access Umpire has been accepted successfully."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>Successfully Accepted Application</h1>
    <h2>The application to access Umpire has been accepted successfully.</h2>
    <?php
        if (!empty($accept_email)) {
            echo '<p>E-mail address of the accepted application: ';
            echo htmlspecialchars($accept_email);
            echo '.</p>';
        }
    ?>
    <p>Would you like to manage other <a href="../">access applications</a>?</p>
</body>
</html>
