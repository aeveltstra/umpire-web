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
 * @version  2.24.526.2116
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');


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

$stats = [];
$amount_of_form_entries = 0;
$ask_for_entries_for_form = query(
    "select count(*) as `amount_of_entries`,
        (
            select count(*) 
            from `form_attributes` 
            where `form` = ?
        ) as `amount_of_form_attributes`,
        (
            select count(*) 
            from `integer_values` 
            where `case_id` in (
                select `entry_id` from `entries` 
                where `form` = ?
            )
        ) as `estimated_amount_of_form_attribute_versions`
        from `entries` 
        where `form` = ?
        group by `form`
    ",
    'sss',
    [
        $prefixed_form_id,
        $prefixed_form_id,
        $prefixed_form_id
    ]
);
if (isset($ask_for_entries_for_form[0])) {
    $stat_labels = [
        "amount_of_entries",
        "amount_of_form_attributes",
        "estimated_amount_of_form_attribute_versions"
    ];
    $stats = [];
    foreach ($stat_labels as $label) {
        $stat = ["label"=>$label, "value"=>0];
        if (isset($ask_for_entries_for_form[0][$label])) {
            $stat['value'] = $ask_for_entries_for_form[0][$label];
        }
        $stats[] = $stat;
    }
}

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
if ($stats) {
    echo "<ul>";
    foreach ($stats as $stat) {
        echo "<li>$stat[label]: $stat[value]</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No statistics available for this form.</p>";
}
?>
</body>
</html>
