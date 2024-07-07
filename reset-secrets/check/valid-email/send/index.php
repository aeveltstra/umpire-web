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
 * @version  2.24.620.2133
 */

// Config module knows the server domain
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/config.php';

// Session Utils module remembers stuff for the user
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
Use the below link to set your credentials to access the database \r\n
for Unidentified and Missing People, Independent Research and \r\n
Education.\r\n
\r\n
${app_url}/set-access/?reset_key=${reset_key}&email=${known_email}\r\n
\r\n
--\r\n
I am a bot. \r\n
For feedback and support, reach out to: \r\n
${support_email}.\r\n",
        "Reply-To: ${support_email}"
    );
}
if ($success) {
    header('Location: ../sent/');
    die();
}
header('Location: ../failed-to-send/');
die();
?>
