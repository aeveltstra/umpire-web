<?php
/**
 * Lists form entries. The fields are generated on the fly
 * based on the fields listed in the database.
 *
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for Omega Junior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.712.0050
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

if (!session_did_user_authenticate()) {
    session_remember('return_to', '/umpire/view/form-entries/');
    header('Location: ../../sign-in/');
    die();
}

$current_user = session_recall_user_token();
$held_privileges = db_which_of_these_privileges_does_user_hold(
    $current_user,
    'may_see_all_cases'
);
if (empty($held_privileges)) {
    session_remember('return_to', '/umpire/view/form-entries/');
    header('Location: ../../access-denied/'); 
    die();
}

/**
 * We expect a form id as the form query parameter.
 */
$expected_query_param = 'form';
$given_form_id = '';
if (isset($_GET[$expected_query_param])) {
    $given_form_id = $_GET[$expected_query_param];
}
if (!$given_form_id) {
    header('Location: ../forms/');
    die();
}

$form_id_prefix = 'enter_';
$prefixed_form_id = $form_id_prefix . $given_form_id;

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
$ask_whether_form_exists = query(
    'select 1 as `it_exists` from `forms` where `id` = ?',
    's',
    [$prefixed_form_id]
);
$does_form_exist = (
    isset($ask_whether_form_exists[0])
    && isset($ask_whether_form_exists[0]['it_exists'])
    && $ask_whether_form_exists[0]['it_exists'] == 1
);
if (!$does_form_exist) {
    header('Location: ../forms/');
    die();
}
$ask_for_form_caption = query(
    "select `caption` from `form_caption_translations` 
     where `form` = ? and `language` = 'en'",
    's',
    [$prefixed_form_id]
);
if (isset($ask_for_form_caption[0])
    && isset($ask_for_form_caption[0]['caption'])
    && $ask_for_form_caption[0]['caption'] != ''
) {
    $form_caption = $ask_for_form_caption[0]['caption'];
} else {
    $form_caption = $given_form_id;
}

$entries = query(
    "call sp_get_form_entries_with_fields(?, ?)",
    'ss',
    [
        $prefixed_form_id,
	'en'
    ]
);


$page_title = $form_caption . ' - Form Entries - Umpire';

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
    <title><?php echo $page_title; ?></title>
    <meta name=description content="Overview of stored records"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel=stylesheet href="../../../c/main.css"/>
</head>
<body>
    <h1><?php echo $page_title; ?></h1>
    <h2>Overview of stored records</h2>
<?php

if ($entries) {
    echo "<table>\r\n<tr><th>Case ID</th><th>Field</th><th>Value</th><th>Entered at</th><th>By user</th></tr>";
    while ($entry = array_shift($entries)) {
        echo "<tr>
        <th>{$entry['case_id']}</th>
	<th>{$entry['translation']}</th>
	<td>{$entry['value']}</td>
	<td>{$entry['at']}</td>
	<td>{$entry['user']}</td>
        </tr>\r\n";
    }
    echo "</table>\r\n";
} else {
    echo "<p>No entries available for this form.</p>";
}
?>
</body>
</html>
