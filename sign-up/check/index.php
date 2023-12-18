<?php 
declare(strict_types=1);
/**
 * Request access to Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1025.2245
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

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

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$form_id = 'sign_up_form';
$is_form_nonce_valid = session_is_nonce_valid($form_id);
session_forget_nonce($form_id);
if (!$is_form_nonce_valid) {
    header('Location: ./error-wrong-form/');
    die();
}

$is_form_post_received = false;
$email_tainted = null;
if (isset($_POST['email'])) {
    $email_tainted = $_POST['email'];
    $is_form_post_received = true;
}
$reason_tainted = null;
if (isset($_POST['reason'])) {
    $reason_tainted = $_POST['reason'];
    $is_form_post_received = true;
}
$agreed = null;
if (isset($_POST['agree'])) {
    $agreed = $_POST['agree'];
    $is_form_post_received = true;
}
if (!$is_form_post_received) {
    header('Location: ./error-wrong-form/');
    die();
}

session_remember('add_user_reason_tainted', $reason_tainted);
session_remember('add_user_agreed_tainted', $agreed);

$is_valid = filter_var(
    $email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

if ($is_valid) {
    session_remember('add_user_email_valid', $email_tainted);
    session_write_close();
    header('Location: ./valid-email/');
    die();
} 

session_remember('add_user_email_invalid', $email_tainted);
session_write_close();
header('Location: ./invalid-email/');
die();
?>
