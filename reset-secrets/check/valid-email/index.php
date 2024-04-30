<?php
declare(strict_types=1);
/**
 * Request new credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * 
 * PHP Version 7.5.3.
 * 
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.429.1043
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
$reset_email_valid = session_recall('reset_email_valid');
$is_known = db_is_email_known($reset_email_valid);

if ($is_known) {
    $reset_key = set_and_get_reset_key($reset_email_valid);
    session_remember('known_email', $reset_email_valid);
    session_rememember('reset_key', $reset_key);
    header('Location: ./send/');
    die();
} else {
    session_forget('known_email');
    session_forget('reset_key');
    header('Location: ./sent/');
    die();
}
?>

