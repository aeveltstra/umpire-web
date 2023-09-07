<?php
declare(strict_types=1);

/**
 * Request access to Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 20230906t2125
 */
$email_tainted = $_POST['email'];
$reason_tainted = $_POST['reason'];
session_start();
$_SESSION['add_email_tainted'] = $email_tainted;
$_SESSION['add_reason_tainted'] = $reason_tainted;

$is_valid = filter_var(
    $email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

if ($is_valid) {
    
    header('Location: ./received');
    die();
} else {
    header('Location: ./invalid-email');
    die();
}
?>

