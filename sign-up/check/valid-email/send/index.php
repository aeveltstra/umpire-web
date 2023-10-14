<?php
/**
 * Request credentials to access Umpire
 * Step 3: send the access request to the admin.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1007.1750
 */
 error_reporting(E_ALL);

/**
 * db_utils.php contains db functionality like is_email_known()
 * and its required predecessor hash_candidate(). It also imports
 * the config file, which specifies the email addresses for the
 * system admin and support agents.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

session_start();
if (
    !isset($_SESSION['access_request_email'])
    || !isset($_SESSION['access_request_reason'])
    || !isset($_SESSION['access_request_agreed_to_terms'])
) {
    header('Location: ../error-missing-input/');
    die();
}

$access_request_email = $_SESSION['access_request_email'];
unset($_SESSION['access_request_email']);
$access_request_reason = $_SESSION['access_request_reason'];
unset($_SESSION['access_request_reason']);
$access_request_agreed_to_terms = $_SESSION['access_request_agreed_to_terms'];
unset($_SESSION['access_request_agreed_to_terms']);

$email_hash = hash_candidate(
    $access_request_email 
);
$is_known = is_email_known(
    $email_hash
);
if (!$is_known) {
    header('Location: ../error-storage-failure/');
    die;
}

/* $admin_email and $support_email are set in config.php */
$success = mail(
    $admin_email,
    'Umpire access requested',
    "Hello,  
  
Special access has been requested to Umpire for this email address:  
${access_request_email}  
  
Their reason is:  
${access_request_reason}  
  
Did they agree to the terms and conditions?  
${access_request_agreed_to_terms}  
  
Use this link to accept the application:  
https://www.umpi.re/applications/accept/?id=${access_request_email}  
  
Use this link to reject it:  
https://www.umpi.re/applications/reject/?id=${access_request_email}  
  
--
I am a robot. I cannot read your reply. For feedback and support, reach out to ${support_email}."
);

if ($success) {
    header('Location: ../sent/');
    die;
}

header('Location: ../failed-to-send/');
die;

?>
