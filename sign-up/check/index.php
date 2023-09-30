<?php 
declare(strict_types=1);
error_reporting(E_ALL);
$started = session_start();

/**
 * Request access to Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.927.0015
 */
/* 
echo var_dump($_POST);
echo "\nSession started: ${started}\n";
*/
$email_tainted = $_POST['email'];
$reason_tainted = $_POST['reason'];
$agreed = $_POST['agree'];

$_SESSION['add_user_reason_tainted'] = $reason_tainted;
$_SESSION['add_user_agreed_tainted'] = $agreed;

$is_valid = filter_var(
    $email_tainted, 
    FILTER_VALIDATE_EMAIL,
    FILTER_FLAG_EMAIL_UNICODE
);

if ($is_valid) {
    $_SESSION['add_user_email_valid'] = $email_tainted;
    session_write_close();
    header('Location: ./valid-email/');
    die();
} 

$_SESSION['add_user_email_invalid'] = $email_tainted;
session_write_close();
header('Location: ./invalid-email/');
die();
/* echo var_dump($_SESSION); */
?>


