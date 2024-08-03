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
 * @version  2.24.714.1444
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once $_SERVER['DOCUMENT_ROOT'].'/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/umpire/db_utils.php';

if (false === session_did_user_authenticate()) {
    session_remember('return_to', '/umpire/view/form-entries/');
    header('Location: ../../../sign-in/');
    die();
}

$current_user    = session_recall_user_token();
$held_privileges = db_which_of_these_privileges_does_user_hold(
    $current_user,
    'may_see_all_cases'
);
if ([] === $held_privileges) {
    session_remember('return_to', '/umpire/view/form-entries/');
    header('Location: ./access-denied/');
    die();
}

/*
 * We expect a form id as the form query parameter.
 * It is passed in as a query parameter by a local .htaccess
 * setting, that transforms the user-friendly web address
 * to one expected by this script.
 */

$expected_query_param = 'form';
$given_form_id        = '';
if (true === isset($_GET[$expected_query_param])) {
    $given_form_id = $_GET[$expected_query_param];
}

if ('' === $given_form_id) {
    header('Location: ../forms/');
    die();
}

$form_id_prefix   = 'enter_';
$prefixed_form_id = $form_id_prefix.$given_form_id;

require_once $_SERVER['DOCUMENT_ROOT'].'/umpire/db_utils.php';
$ask_whether_form_exists = query(
    'select 1 as `it_exists` from `forms` where `id` = ?',
    's',
    [$prefixed_form_id]
);
$does_form_exist         = (
    isset($ask_whether_form_exists[0])
    && isset($ask_whether_form_exists[0]['it_exists'])
    && 1 == $ask_whether_form_exists[0]['it_exists']
);
if (false === $does_form_exist) {
    header('Location: ../forms/');
    die();
}

$ask_for_form_caption = query(
    "select `caption`
     from `form_caption_translations`
     where `form` = ?
     and `language` = 'en'",
    's',
    [$prefixed_form_id]
);
if (isset($ask_for_form_caption[0])
    && isset($ask_for_form_caption[0]['caption'])
    && '' !== $ask_for_form_caption[0]['caption']
) {
    $form_caption = $ask_for_form_caption[0]['caption'];
} else {
    $form_caption = $given_form_id;
}

// Make sure we display a page full of results at a time.
$page      = 0;
$page_size = 50;
if (isset($_GET['p'])) {
    $requested_page = $_GET['p'];
    if (is_numeric($requested_page) && $requested_page > 1) {
        $page = $requested_page - 1;
    }
}

if (isset($_GET['pp'])) {
    $requested_page_size = $_GET['pp'];
    if (is_numeric($requested_page_size) && $requested_page_size > 10) {
        $page_size = $requested_page_size;
    }
}


$entries = [];

$entry_ids = query(
    'select entry_id
     from entries 
     where form = ?
     order by entry_id desc
     limit ?, ?',
    'sii',
    [
        $prefixed_form_id,
        $page,
        $page_size,
    ]
);


$must_render_entries = true;
if (null === $entry_ids
    || 0 === count($entry_ids)
    || false === isset($entry_ids[0]['entry_id'])
) {
    $must_render_entries = false;
} else {
    // We need to read which attributes to expect from the form.
    $attributes = query(
        'select `attribute` 
         from `form_attributes` 
         where `form` = ?',
        's',
        [$prefixed_form_id],
    );
    if (null === $attributes
        || 0 === count($attributes)
        || false === isset($attributes[0]['attribute'])
    ) {
        $must_render_entries = false;
    } else {
        $entries = query(
            "call sp_get_form_entries_with_fields(?, ?)",
            'ss',
            [
                $prefixed_form_id,
                'en',
            ]
        );
    }
}

$page_title = $form_caption.' - Form Entries - Umpire';

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
    <title><?php echo $page_title; ?></title>
    <meta name=description content="Overview of stored records"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel=stylesheet href="../../../c/main.css"/>
    <style type="text/css">/* <![CDATA[ */
        body{max-width:none}
        table{border-left:thin solid silver;border-top:thin solid silver}
        th{margin:1pt;padding:4pt;border-right:thin solid grey;border-bottom:thin solid grey;text-align:left}
        td{margin:1pt;padding:4pt;border-right:thin solid silver;border-bottom:thin solid silver}
    /* ]]> */</style>
</head>
<body>
    <h1><?php echo $page_title; ?></h1>
    <h2>Overview of stored records</h2>
<?php

if ((0 < count($entries)) && (true === $must_render_entries)) {
    echo '<table><thead><tr><th>Case_ID</th>';
    foreach ($attributes as $record) {
        if (isset($record['attribute'])) {
            $id = $record['attribute'];
            echo "<th>{$id}</th>";
        } else {
            echo '<th>Field</th>';
        }
    }

    echo '</tr></thead>';
    $xs = [];
    $last_entry_id = '';
    while ($entry = array_shift($entries)) {
        $current_entry_id = $entry['entry_id'];
        if ($last_entry_id !== $current_entry_id) {
            $xs[$current_entry_id] = [];
            $last_entry_id = $current_entry_id;
        }        
        $xs[$current_entry_id][$entry['attribute']] = $entry['value'];
    }
    foreach ($xs as $k => $x) {
        echo '<tr><th>'.$k.'</th>';
        foreach ($attributes as $field) {
            $field_name = $field['attribute'];
            if (isset($x[$field_name])) {
                 echo '<td>'.$x[$field_name].'</td>';
            } else {
                echo '<td></td>';
            }
        }
        echo "</tr>\r\n";
    }
    echo "</table>\r\n";
} else {
    echo "<p>No entries available for this form.</p>";
}
?>
</body>
</html>
