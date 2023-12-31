<?php
declare(strict_types=1);

/**
 * Request new credentials to access Umpire
 * Step 1: check whether the passed-in email is considered valid.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.909.2224
 */

$reset_email_tainted = $_POST['email'];
$is_valid = filter_var(
    $reset_email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

session_start();
if ($is_valid) {
    $_SESSION['reset_email_valid'] = $reset_email_tainted;
    header('Location: ./valid-email');
    die();
} else {
    $_SESSION['reset_email_tainted'] = $reset_email_tainted;
    header('Location: ./invalid-email');
    die();
}
?>

