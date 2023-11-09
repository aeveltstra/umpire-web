<?php
/**
 * Approve and reject access for multiple users at a time.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1106.2035
 */
declare(strict_types=1);
error_reporting(E_ALL);

/**
 * This script expects to receive input from a form.
 * We expect a valid nonce.
 */
if (!isset($_SERVER['REQUEST_METHOD'])) {
    http_response_code(400);
    die();
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(400);
    die();
}
if (!isset($_POST['nonce'])) {
    header('Location: ./error-wrong-form/');
    die();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
$form_id = 'access_applications_form';
if (!session_is_nonce_valid($form_id)) {
    header('Location: ./error-wrong-form/');
    die();
}
session_forget_nonce($form_id);

$is_post_received = false;
$accept_emails = null;
$rejectt_emails = null;
if (isset($_POST['accept_emails'])) {
    $accept_emails = $_POST['accept_emails'];
}
if (isset($_POST['reject_emails'])) {
    $reject_emails = $_POST['reject_emails'];
}
if (empty($accept_emails) && empty($reject_emails)) {
    header('Location: ./error-missing-required-parameters/');
    die();
}

if (!session_did_user_authenticate()) {
    session_remember('accept_emails', $accept_emails);
    session_remember('reject_emails', $reject_emails);
    session_remember('return_to', '/umpire/applications/');
    header('Location: /umpire/sign-in/');
    die();
}

$current_user = session_recall_user_token();
$user_may_accept = db_may_authenticated_user_accept_access(
    $current_user
);
$user_may_reject = db_may_authenticated_user_reject_access(
    $current_user
);
if (!$user_may_accept && !$user_may_reject)  {
    session_remember('accept_emails', $accept_emails);
    session_remember('reject_emails', $reject_emails);
    session_remember('return_to', '/umpire/applications/');
    header('Location: ../access-denied/'); 
    die();
}

$xs_a = [];
if (!empty($accept_emails)) {
    $xs_a = preg_split("/\R/", $accept_emails);
}
$xs_r = [];
if (!empty($reject_emails)) {
    $xs_r = preg_split("/\R/", $reject_emails);
}
if (!empty($xs_a)) {
    $xs_a = array_map(function ($x) use (&$current_user) {
        $x = filter_var($x, FILTER_VALIDATE_EMAIL);
        if (false === $x || empty($x)) { return null; }
        $success = db_accept_access($current_user, $x);
        if ($success) { return $x; }
        return null;
    }, $xs_a);
    $xs_a = array_filter($xs_a, function($x) {
        return (!empty($x));
    });
}
if (!empty($xs_r)) {
    $xs_r = array_map(function ($x) use (&$current_user) {
        $x = filter_var($x, FILTER_VALIDATE_EMAIL);
        if (false === $x || empty($x)) { return null; }
        $success = db_reject_access($current_user, $x);
        if ($success) { return $x; }
        return null;
    }, $xs_r);
    $xs_r = array_filter($xs_r, function($x) {
        return (!empty($x));
    });
}

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Done Processing Applications</title>
<meta name=description content="The applications to access Umpire have been processed."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>Done Processing Applications</h1>
    <h2>The applications to access Umpire has been processed.</h2>
    <?php
        if (empty($xs_a)) {
            echo "<p>No email addresses were accepted.</p>\r\n";
        } else {
            echo "<p>E-mail addresses of accepted applications:</p><ul>\r\n";
            array_walk($xs_a, function($x) {
                echo '<li>', htmlspecialchars($x), '</li>', "\r\n";
            });
            echo "</ul>\r\n";
        }
        if (empty($xs_r)) {
            echo "<p>No email addresses were rejected.</p>\r\n";
        } else {
            echo "<p>E-mail addresses of rejected applications:</p><ul>\r\n";
            array_walk($xs_r, function($x) {
                echo '<li>', htmlspecialchars($x), '</li>', "\r\n";
            });
            echo "</ul>\r\n";
        }
    ?>
    <p>Would you like to manage other <a href="../">access applications</a>?</p>
</body>
</html>
