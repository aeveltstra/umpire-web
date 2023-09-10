<?php
declare(strict_types=1);

/**
 * Request credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.910.103
 */

session_start();
$add_email_valid = $_SESSION['add_email_valid'];
$is_known = is_email_known($add_email_valid);

if (!$is_known) {
    $success = add_user($add_email_valid);
}

$reset_key = set_and_get_reset_key($add_email_valid);
$_SESSION['known_email'] = $reset_email_valid;
$_SESSION['reset_key'] = $reset_key;
header('Location: ./send');
die();
?>

