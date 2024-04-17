<?php
/**
 * Attempt to match passed-in user credentials to the users table.
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.312.1933
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
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

$form_id = 'authentication_form';
if(!session_is_nonce_valid($form_id)) {
    header('Location: ./error-wrong-form/');
    die();
}
session_forget_nonce($form_id);

/**
 * This script expects several inputs from form posting.
 * If those don't exist, we throw an error immediately.
 */
$is_post_received = false;
$email = null;
if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $is_post_received = true;
}
$key = null;
if (isset($_POST['username'])) {
    $key = $_POST['username'];
    $is_post_received = true;
}
$secret = null;
if (isset($_POST['password'])) {
    $secret = $_POST['password'];
    $is_post_received = true;
}
if(!$is_post_received) {
    header('Location: ./error-wrong-form/');
    die();
}

/**
 * DB Utils contains functions to read from and store into 
 * the database, like is_user_known().
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

$is_user_known = db_is_user_known($email, $key, $secret);
if (1 !== $is_user_known) {
    header('Location: ./failed/');
    die();
}
$user_token = session_make_user_token($email);
session_remember_user_token($user_token);
db_log_user_event('authenticated');
$return_to = session_recall('return_to');
if (empty($return_to)) {
    header('Location: ./success/');
    die();
}
session_forget('return_to');
header('Location: ' . $return_to);

?>
