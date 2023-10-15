<?php
/**
 * Deny access to a user who has requested it.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1015.1305
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
if (empty($_GET['id'])) {
    $user_email = get_session_variable('reject_user_application_email');
    if (empty($user_email)) {
        header('Location: ./error-missing-required-parameters/');
        die;
    }
} else {
    $user_email = $_GET['id'];
}


/**
 * Session utilities automatically start and secure the user
 * session.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

if (!did_user_authenticate()) {
    set_session_variable('reject_user_application_email', $user_email);
    set_session_variable('return_to', '/umpire/applications/reject/');
    header('Location: /umpire/sign-in/');
    die;
}

/**
 * db_utils.php contains db functionality like query(sql)
 * and db_exec(sql, params_typestring, params).
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';


?>
