<?php
/**
 * Management menu for Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1107.2106
 */
declare(strict_types=1);
error_reporting(E_ALL);

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

if (!session_did_user_authenticate()) {
    session_remember('return_to', '/umpire/manage/');
    header('Location: /umpire/sign-in/');
    die();
}

$current_user = session_recall_user_token();
$held_privileges = db_which_of_these_privileges_does_user_hold(
    $current_user,
    'may_manage_forms',
    'may_manage_users',
    'may_manage_subscriptions'
);
if (empty($held_privileges)) {
    session_remember('return_to', '/umpire/manage/');
    header('Location: ./access-denied/'); 
    die();
}


?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Manage Umpire</title>
<meta name=description content="Make decisions about Umpire configurations and user subscriptions"/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>Manage Umpire</h1>
    <h2>Make desicions about Umpire configurations and user subscriptions</h2>
    <?php
    ?>
    <p>Would you like to manage:</p>
    <ul>
        <li><a href="forms/">entry forms</a>?</li>
        <li><a href="../subscriptions/">user subscriptions</a>?</li>
        <li><a href="../applications/">access applications</a>?</li>
    </ul>
</body>
</html>
