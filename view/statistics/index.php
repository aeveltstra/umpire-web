<?php
/**
 * Shows data statistics. 
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.526.2003
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

$forms_and_entries = query(
    "
    select `forms`.`id`, `caption`,
     (
        select count(*) from `entries` 
        where `form` = `forms`.`id`
     ) as `entry_count`
     from `forms` 
     left join `form_caption_translations`
     on `form_caption_translations`.`form` = `forms`.`id` 
     and `form_caption_translations`.`language` = 'en'
    "
);
$count_forms_found = count($forms_and_entries);
$we_have_any_forms = (0 < $count_forms_found);

$count_known_users = 0;
$known_users_counter = scalar(
    "
    select count(1) as `count_known_users` 
     from `users` 
     where `users`.`seq` in (
        select `user` from `user_role_users`
     )
    "
);
$we_have_any_known_users = isset($known_users_counter[0]);
if ($we_have_any_known_users) {
    $count_known_users = $known_users_counter[0];
}

$count_anonymous_users = 0;
$anonymous_users_counter = scalar(
    "
    select count(1) as `count_anonymous_users` 
     from `users` 
     where `users`.`seq` not in (
        select `user` from `user_role_users`
     )
    "
);
$we_have_any_anonymous_users = isset($anonymous_users_counter[0]);
if ($we_have_any_anonymous_users) {
    $count_anonymous_users = $anonymous_users_counter[0];
}

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
    <meta name=description 
        content="Business Intelligence Insights for Data Analysis"/>
    <meta name="viewport" 
        content="width=device-width, initial-scale=1.0"/>
    <link rel=stylesheet href="../../c/main.css"/>
</head>
<body>
    <h1>View Statistics - Umpire</h1>
    <h2>Business Intelligence Insights for Data Analysis</h2>
    
    <?php 
    if ($we_have_any_forms) {
        echo "<h3>Forms</h3>\r\n\t";
        echo '<p>The system holds ';
        echo $count_forms_found;
        echo " forms: </p>\r\n\t<ul>\r\n";
        foreach ($forms_and_entries as list(
            'id' => $form_id,
            'caption' => $caption,
            'entry_count' => $entry_count
        )) {
            $link = "<a href='form-entries/$form_id/'>View 
                stats for the form <q>$caption</q>.</a>";
            echo "\t\t<li>Form <q>$caption</q>, with ";
            echo $entry_count;
            echo " entries. $link</li>\r\n";
        }
        echo "</ul>\r\n";
    }
    
        echo '<h3>Users</h3>' . "\r\n\t";
        echo "<p>The system holds ";
        echo $count_anonymous_users + $count_known_users;
        echo " users: </p>\r\n\t<ul>\r\n";
        echo "\t\t<li>$count_anonymous_users Anonymous users, ";
        echo "and </li>\r\n";
        echo "\t\t<li>$count_known_users Known users.</li>\r\n";
        echo "</ul>\r\n";
    
    ?>
    
</body>
</html>
