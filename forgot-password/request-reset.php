<?php
declare(strict_types=1);

/**
 * Request new credentials to access Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 20230906t2125
 */

$reset_email_tainted = $_POST['email'];
session_start();
$_SESSION['reset_email_tainted'] = $reset_email_tainted;

$is_valid = filter_var(
    $reset_email_tainted, 
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

