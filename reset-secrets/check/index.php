<?php 
declare(strict_types=1);
/**
 * Request new credentials to access Umpire
 * Step 1: check whether the passed-in email is considered valid.
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.430.1948
 */

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

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$reset_email_tainted = $_POST['email'];
$form_nonce = $_POST['nonce'];
$form_id = 'authentication_reset_form';
if (!session_is_nonce_valid($form_id)) {
    header('Location: ./error-wrong-form/');
    die();
}
session_forget_nonce($form_id);

$is_valid = filter_var(
    $reset_email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

if ($is_valid) {
    session_remember('reset_email_valid', $reset_email_tainted);
    header('Location: ./valid-email/');
    die();
} else {
    session_remember('reset_email_tainted', $reset_email_tainted);
    header('Location: ./invalid-email/');
    die();
}
?>


