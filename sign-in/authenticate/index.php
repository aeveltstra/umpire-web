<?php
/**
 * Attempt to match passed-in user credentials to the users table.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1015.1332
 */
declare(strict_types=1);

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * This script expects several inputs from form posting.
 * If those don't exist, we throw an error immediately.
 */
$form_id = 'authentication_form';
$is_form_nonce_valid = is_session_nonce_valid($form_id);
if (!$is_form_nonce_valid) {
    header('Location: ./error-wrong-form/');
    die;
}
$is_post_received = false;
$email = null;
if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $is_post_received = true;
}
$key = null;
if (isset($_POST['key'])) {
    $key = $_POST['key'];
    $is_post_received = true;
}
$secret = null;
if (isset($_POST['secret'])) {
    $secret = $_POST['secret'];
    $is_post_received = true;
}
if(!$is_post_received) {
    header('Location: ./error-wrong-form/');
    die;
}

$is_user_known = is_user_known($email, $key, $secret);
if (1 !== $is_user_known) {
    header('Location: ./failed/');
    die;
}
store_that_user_authenticated(make_session_hash("$email|$key"));
$return_to = get_session_variable('return_to');
if (empty($return_to)) {
    header('Location: ./success/');
    die;
}
header('Location: ' . $return_to);

?>
