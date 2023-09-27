<?php
declare(strict_types=1);

/**
 * Request access to Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.927.0015
 */
$email_tainted = $_POST['email'];
$reason_tainted = $_POST['reason'];
$agreed = $_POST['agree'];

$is_valid = filter_var(
    $email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

session_start();
$_SESSION['add_user_reason_tainted'] = $reason_tainted;
$_SESSION['add_user_agreed_tainted'] = $agreed;

if ($is_valid) {
    $_SESSION['add_user_email_valid'] = $email_tainted;
    header('Location: ./valid-email');

    die();
} else {
    $_SESSION['add_user_email_invalid'] = $email_tainted;
    header('Location: ./invalid-email');
    die();
}
?>