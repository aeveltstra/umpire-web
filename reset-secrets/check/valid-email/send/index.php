<?php
declare(strict_types=1);
/**
 * Request new credentials to access Umpire
 * Step 3: send reset to email of known user.
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.526.2152
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$known_email = session_recall('known_email');
$reset_key = session_recall('reset_key');
$success = false;
if (!empty($known_email) && !empty($reset_key)) {
    $success = mail(
        $known_email,
        'Umpire: set your access credentials',
        "Hello,\r\n
        \r\n
        Use the below link to set your credentials to access the database 
        for Unidentified and Missing People, Independent Research and
        Education.\r\n
        \r\n
        https://www.umpi.re/set-access/?reset_key=${reset_key}&email=${known_email}
        \r\n
        \r\n"
    );
}
if ($success) {
    header('Location: ../sent/');
    die();
} else {
    header('Location: ../failed-to-send/');
    die();
}
?>

