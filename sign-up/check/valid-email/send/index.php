<?php
declare(strict_types=1);

/**
 * Request credentials to access Umpire
 * Step 3: send link to email of known user.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.910.105
 */

session_start();
$known_email = $_SESSION['known_email'];
$reset_key = $_SESSION['reset_key'];
$success = mail(
    $known_email,
    'Umpire: set your access credentials',
    "Hello,\r\n\r\nUse the below link to set your credentials to access the database for Unidentified and Missing People, Independent Research and Education.\r\n\r\nhttps://www.umpi.re/set-access/?reset_key=${reset_key}&email=${known_email}\r\n\r\n"
);


if ($success) {
    header('Location: ../sent');
    die();
} else {
    header('Location: ../failed-to-send');
    die();
}
?>

