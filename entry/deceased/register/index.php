<?php
/**
 * Stores the deceased person's case entry. Fields are generated on 
 * the fly based on the fields listed in the database.
 * @author A.E.Veltstra
 * @version 2.24.0114.1758
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');


/* If this process got invoked by any method other than HTTP POST,
 * processing needs to halt and the user needs to be redirected.
 * The process requires to be invoked using HTTP POST.
 */
if (!isset($_SERVER['REQUEST_METHOD'])) {
    http_response_code(400);
    die();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die();
}
$is_form_acceptable = true;
$form_nonce = null;
$form_id = null;
if (isset($_POST['nonce'])) {
    $form_nonce = $_POST['nonce'];
} else {
    $is_form_acceptable = false;
}
if (isset($_POST['form_id'])) {
    $form_id = $_POST['form_id'];
    if ($form_id != 'enter_deceased') {
        $is_form_acceptable = false;
    }
} else {
    $is_form_acceptable = false;
}
if (!$is_form_acceptable) {
    header('Location: ./error-wrong-form/');
    die();
}

/**
 * Session Utils contain functions to read from and store into
 * session variables, and creates related things like nonces.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$form_nonce_id = 'deceased_entry_form';
if (!session_is_nonce_valid($form_nonce_id)) {
    header('Location: ./error-wrong-form/');
    die();
} else {
    /* The fact that we had a valid nonce implies that a user token
     * exists. It could be for an anonymous but also for an authenticated
     * user. An anonymous user is likely to have a unique token, which
     * will not yet exist in the database. An authenticated user does
     * have their user token match a database user.
     */
    session_forget_nonce($form_id);
}

/**
 * DB Utils contains functions to read from and store into the database, like
 * the function to read form entry fields.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * We'll ask the database what fields are available for this form, as to
 * request only those from te POSTed data. If they can't be found, we halt.
 */
$expected_fields = db_read_form_entry_fields('enter_deceased', 'en');
if (!is_array($expected_fields)) {
    header('500');
    die();
}

/**
 * Form Saving Utils contains functions to store form entries into the database.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/form_saving_utils.php';

$result = form_enter_new('enter_deceased', $expected_fields, $_POST);
$new_case_id = $result['new_case_id'];
$fails = $result['fails'];

if (empty($fails)) {
    header('Location: ./success/');
    die();
}

session_remember('new_case_id', strval($new_case_id));

?>
<!DOCTYPE html>
<html lang=en>
<head>
    <meta charset="utf-8" />
    <title>Jinkies! - Umpire</title>
    <meta name=description content="Something went wrong while we tried to register your entry."/>
    <meta name=author value="OmegaJunior Consultancy, LLC" />
    <meta name=viewport content="width=device-width, initial-scale=1.0" />
    <link rel=stylesheet href="/umpire/c/main.css"/>
    <link rel=stylesheet href="/umpire/c/manage-form.css"/>
</head>
<body>
    <h1>Jinkies! - Umpire</h1>
    <h2>Something went wrong while we tried to register your entry.</h2>
    <h3>The following fields failed to have their values stored:</h3>
    <ul>
        <?php 
            $template = '<li>%1$s: %2$s</li>';
            foreach($fails as list(
                'case' => $case_id,
                'field' => $field_id,
                'value' => $field_value
            )) {
                echo sprintf(
                    $template,
                    addslashes($field_id),
                    addslashes(strval($field_value))
                );
            }
        ?>
    </ul>
    <p>Please <a href="../">try again</a>.</p>
</body>
</html>