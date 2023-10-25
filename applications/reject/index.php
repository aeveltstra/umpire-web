<?php
/**
 * Deny access to a user who has requested it.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1015.1816
 */
declare(strict_types=1);
error_reporting(E_ALL);

/**
 * This script expects to receive input from querystring.
 * Invoke like this: reject/?id=value
 * in which value is the email address to reject.
 * If that doesn't exist, we check the session variable.
 * If neither exist, we throw an error.
 */
$user_email = null;
$user_email_variable = 'reject_user_application_email';
if (isset($_GET['id'])) {
    $user_email = $_GET['id'];
}
if (empty($user_email) {
    $user_email = get_session_variable($user_email_variable);
    unset_session_variable($user_email_variable);
    if (empty($user_email)) {
        header('Location: ./error-missing-required-parameters/');
        die();
    }
}

/**
 * Session utilities automatically start and secure the user
 * session.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

if (!did_user_authenticate()) {
    set_session_variable($user_email_variable, $user_email);
    set_session_variable('return_to', '/umpire/applications/reject/');
    header('Location: /umpire/sign-in/');
    die();
}

/**
 * DB Utils contains db functionality to query for information
 * and create more other information.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
$success = false;
if(is_email_known($user_email)) {
    $success = reject_access($user_email);
}
if (!$success) {
    header('Location: ./failed/');
    die();
}

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Successfully Rejected Application</title>
<meta name=description content="The application to access Umpire has been rejected successfully."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>Successfully Rejected Application</h1>
    <h2>The application to access Umpire has been rejected successfully.</h2>
    <?php
        if (!empty($user_email)) {
            echo '<p>E-mail address of the rejected application: ';
            echo htmlspecialchars($user_email);
            echo '.</p>';
        }
    ?>
    <p>Would you like to see <a href="../">other applications</a>?</p>
</body>
</html>
