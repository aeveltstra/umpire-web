<?php
/**
 * Shows data statistics. 
 * @author A.E.Veltstra
 * @version 2.24.0202.1312
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
$count_forms_found = count($forms_and_entries);
$we_have_any_forms = (0 < $count_forms_found);

$users_and_entries = query("
    select `forms`.`id`, `caption`,
     (
        select count(*) from `user_privileges` 
        where `form` = `forms`.`id`
     ) as `entry_count`
     from `forms` 
     left join `form_caption_translations`
     on `form_caption_translations`.`form` = `forms`.`id` 
     and `form_caption_translations`.`language` = 'en'
");
$count_users_found = count($users_and_entries);
$we_have_any_forms = (0 < $count_users_found);

/**
 * Session Utils contain functions to read from and store into
 * session variables, and creates related things like nonces.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$current_user = session_recall_user_token();
db_log_user_event('viewed_statistics');

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
    <title>View Statistics - Umpire</title>
    <meta name=description content="Business Intelligence Insights for Data Analysis"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>View Statistics - Umpire</h1>
    <h2>Business Intelligence Insights for Data Analysis</h2>
    
    <?php 
    if ($we_have_any_forms) {
        echo '<h3>Forms</h3>' . "\r\n\t";
        echo "<p>The system holds ${count_forms_found} forms: </p>\r\n\t<ul>\r\n";
        foreach($forms_and_entries as list(
            'id' => $form_id,
            'caption' => $caption,
            'entry_count' => $entry_count
        )) {
            echo "\t\t<li>Form <q>${caption}</q>, with ${entry_count} entries. </li>\r\n";
        }
        echo "</ul>\r\n";
    }
    ?>
    
</body>
</html>
