<?php
declare(strict_types=1);

/**
 * Request access to Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 20230906t2125
 */
$email_tainted = $_POST['email'];
$reason_tainted = $_POST['reason'];

$is_valid = filter_var(
    $email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

session_start();
if ($is_valid) {
    $_SESSION['add_email_valid'] = $email_tainted;
    $_SESSION['add_reason_tainted'] = $reason_tainted;
    header('Location: ./valid-email');
    die();
} else {
    $_SESSION['add_email_invalid'] = $email_tainted;
    $_SESSION['add_reason_tainted'] = $reason_tainted;
    header('Location: ./invalid-email');
    die();
}
?>

