<?php
/**
 * Attempt to subscribe user to entered case.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1129.750
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

$form_id = 'subscribe';
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
$case_id = null;
if (isset($_POST['case_id'])) {
    $key = $_POST['case_id'];
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


?>
