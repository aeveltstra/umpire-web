<?php 
declare(strict_types=1);
/**
 * Request new credentials to access Umpire
 * Step 1: check whether the passed-in email is considered valid.
 * 
 * PHP Version 7.5.3
 * 
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.429.2239
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$reset_email_tainted = $_POST['email'];
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


