<?php
/**
 * Shows which forms exist. Allows to view form entries.
 * @author A.E.Veltstra
 * @version 2.24.0202.0940
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/**
 * DB Utils contains functions to read from and store into
 * the database.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
$forms_and_entries = query("
    select `forms`.`id`, `caption`,
     (
        select count(*) from `entries` 
        where `form` = `forms`.`id`
     ) as `entry_count`
     from `forms` 
     left join `form_caption_translations`
     on `form_caption_translations`.`form` = `forms`.`id` 
     and `form_caption_translations`.`language` = 'en'
");
$count_found = count($forms_and_entries);
$we_have_any = (0 < $count_found);
if (!$we_have_any) {
    header('Location: ./no-forms-exist-yet/');
    die();
}

/**
 * Session Utils contain functions to read from and store into
 * session variables, and creates related things like nonces.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$allow_viewing_entries = false;
$current_user = session_recall_user_token();
$did_user_authenticate = session_did_user_authenticate();
if ($did_user_authenticate) {
    $held_privileges = db_which_of_these_privileges_does_user_hold(
        $current_user,
        'may_see_all_cases'
    );
    if (empty($held_privileges)) {
        $allow_viewing_entries = false;
    } else {
        $allow_viewing_entries = true;
    }
}
db_log_user_event('viewed_existing_forms');

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
    <title>View Entry Forms - Umpire</title>
    <meta name=description content="These forms exist"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>View Entry Forms - Umpire</h1>
    <h2>These forms exist:</h2>
    <ul>
    <?php 
    if ($allow_viewing_entries) {
        foreach($forms_and_entries as list(
            'id' => $form_id,
            'caption' => $caption,
            'entry_count' => $entry_count
        )) {
            $new_form_id = '' . $form_id;
            if (false !== strpos($form_id, 'enter_')) {
                $new_form_id = str_replace('enter_', '', $form_id);
            }
            $view_entries_link = '/umpire/view/form-entries/' . $new_form_id . '/'; 
            $enter_new_case_link = '/umpire/forms/' . $new_form_id . '/';
            echo "<li>Form <q>${caption}</q>, with ${entry_count} entries. 
                    <a href='${view_entries_link}'>View entries for this form</a> 
                    or <a href='${enter_new_case_link}'>submit a new entry</a>.</li>";
        }
    } else {
        foreach($forms_and_entries as list(
            'id' => $form_id,
            'caption' => $caption,
            'entry_count' => $entry_count
        )) {
            $new_form_id = '' . $form_id;
            if (false !== strpos($form_id, 'enter_')) {
                $new_form_id = str_replace('enter_', '', $form_id);
            }            
            $enter_new_case_link = '/umpire/forms/' . $new_form_id . '/';
            echo "<li>Form <q>${caption}</q>, with ${entry_count} entries. 
                  <a href='${enter_new_case_link}'>Submit a new entry</a>,</li>";
        }            
    } 
    ?>
    </ul>
</body>
</html>
