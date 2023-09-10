<?php
declare(strict_types=1);

/**
 * Request new credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.909.2224
 */

session_start();
$reset_email_valid = $_SESSION['reset_email_valid'];
$is_known = is_email_known($reset_email_valid);

if ($is_known) {
    $reset_key = set_and_get_reset_key($reset_email_valid);
    $_SESSION['known_email'] = $reset_email_valid;
    $_SESSION['reset_key'] = $reset_key;
    header('Location: ./send');
    die();
} else {
    $_SESSION['known_email'] = '';
    $_SESSION['reset_key'] = '';
    header('Location: ./sent');
    die();
}
?>

