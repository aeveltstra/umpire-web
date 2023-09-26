<?php
declare(strict_types=1);

/**
 * Request credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.926.1815
 */

require_once('../../../config.php');

function is_email_known($candidate) {
    $hashed_candidate = hash('sha512', $candidate);
    $sql = 'select count(*) as `amount` from `users` where `email_hash` = \'' . $hashed_candidate . '\'';
    $rows = query($sql);
    if ($rows) {
        $row = $rows[0];
        if ($row) {
            $amount = $row['amount'];
            return $amount > 0;
        }
    }
    return false;
}

function add_user($candidate) {
    $hashed_candidate = hash('sha512', $candidate);
    
    $sql = 'insert into `users` (' .
            '`email_hash`, `access_requested_on`, `key_hash`, `secret_hash`, `hashing_algo`, `hashing_version`, `last_hashed_date`) ' .
            ' values ();';
}

session_start();
$add_email_valid = $_SESSION['add_email_valid'];
$is_known = is_email_known($add_email_valid);

if ($is_known) {
    header('Location: ./thank-you');
} else {
    $phrase = add_user($add_email_valid);
}

header('Location: ./send');
die();
?>

