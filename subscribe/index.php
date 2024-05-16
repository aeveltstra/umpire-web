<?php
/**
 * Attempt to subscribe user to entered case.
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.108.2158
 */
declare(strict_types=1);

/**
 * If this process gets invoked by any method other than HTTP POST,
 * processing needs to halt and the user needs to be redirected.
 * The process requires to be invoked using HTTP POST.
 */
if (!isset($_SERVER['REQUEST_METHOD'])) {
    http_response_code(400);
    die();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die();
}
if (!isset($_POST['nonce'])) {
    header('Location: ./error-wrong-form/');
    die();
}

/**
 * Session Utils contain functions to read from and store into
 * session variables, and creates related things like nonces.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

$form_id = 'subscribe';
if (!session_is_nonce_valid($form_id)) {
    header('Location: ./error-wrong-form/');
    die();
}
session_forget_nonce($form_id);

/**
 * This script expects several inputs from form posting.
 * If those don't exist, we throw an error immediately.
 */
$is_post_received = false;
$email_tainted = null;
if (isset($_POST['email'])) {
    $email_tainted = $_POST['email'];
    $is_post_received = true;
}
$case_id = null;
if (isset($_POST['case'])) {
    $case_id = $_POST['case'];
    $is_post_received = true;
}
if (!$is_post_received) {
    header('Location: ./error-wrong-form/');
    die();
}

$is_valid = filter_var(
    $email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

$email = '';
if ($is_valid) {
    $email = $email_tainted;
} else {
    session_remember('subscription_email_invalid', $email_tainted);
    session_write_close();
    header('Location: ./invalid-email/');
    die();
}


/**
 * DB Utils contains functions to read from and store into 
 * the database, like is_user_known().
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

$is_subscribed = db_subscribe(intval($case_id), $email);
if ($is_subscribed) {
    header('Location: ./success/');
    die();
} else {
    header('Location: ./failed/');
    die();
}

?>
