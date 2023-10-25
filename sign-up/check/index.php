<?php 
declare(strict_types=1);
/**
 * Request access to Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.927.0015
 */

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

$email_tainted = $_POST['email'];
$reason_tainted = $_POST['reason'];
$agreed = $_POST['agree'];

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


