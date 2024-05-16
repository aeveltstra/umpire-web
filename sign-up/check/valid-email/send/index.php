<?php
/**
 * Request credentials to access Umpire
 * Step 3: send the access request to the admin.
 * 
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.0202.0907
 */
 error_reporting(E_ALL);

/**
 * The db_utils.php contains db functionality like is_email_known()
 * and its required predecessor hash_candidate(). It also imports
 * the config file, which specifies the email addresses for the
 * system admin and support agents.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

$access_request_email = session_recall('access_request_email');
session_forget('access_request_email');
$access_request_reason = session_recall('access_request_reason');
session_forget('access_request_reason');
$access_request_agreed_to_terms = session_recall('access_request_agreed_to_terms');
session_forget('access_request_agreed_to_terms');
$is_form_nonce_valid = session_is_nonce_valid('access_request_form');

if (empty($access_request_email)
    || empty($access_request_reason)
    || empty($access_request_agreed_to_terms)
    || !$is_form_nonce_valid
) {
    header('Location: ../error-missing-input/');
    die();
}

$is_known = db_is_email_known(
    $access_request_email
);
if (!$is_known) {
    header('Location: ../error-storage-failure/');
    die;
}

/* $admin_email, $support_email, and $app_url are set in config.php */
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
${app_url}/manage/applications/accept/?id=${access_request_email}  
  
Use this link to reject it:  
${app_url}/manage/applications/reject/?id=${access_request_email}  
  
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
