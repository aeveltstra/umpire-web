<?php
/**
 * Manage Entry Forms for Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1116.1848
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

if (!session_did_user_authenticate()) {
    session_remember('return_to', '/umpire/manage/forms/');
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
    session_remember('return_to', '/umpire/manage/forms/');
    header('Location: ../access-denied/'); 
    die();
}


$form_choice = '';
$is_form_known = false;
if (isset($_GET['id'])) {
    $form_choice = $_GET['id'];
}
if (!empty($form_choice)) {
    $rows = query(
        'select `id` from `forms` where `id` = ?',
        's',
        [$form_choice]
    );
    if (count($rows) === 1 && isset($rows[0]['id'])) {
        $is_form_known = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Manage Umpire Entry Forms</title>
<meta name=description content="Determine which form to configure."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
    <h1>Manage Umpire Entry Forms</h1>
<?php
    if (!$is_form_known) {
        echo '<h2>Choose which form to edit:</h2><ul>';
        $rows = query(
            'select `id` from `forms`'
        );
        foreach($rows as $row) {
            $id_for_show = htmlspecialchars(
                $row['id'], ENT_QUOTES
            );
            echo "<li><a href='?id={$id_for_show}'>{$id_for_show}</a></li>";
        }
        echo '</ul>';
    } else {
        $form_id_for_show = htmlspecialchars($form_choice, ENT_QUOTES);
        echo "
    <h2>Editing form {$form_id_for_show}.</h2>
    <h3>These attributes are assigned currently.</h3>
    <p>Follow their link to configure their properties.</p>
    <ol>
              ";
        $xs = query(
            'select `a`.*, `fa`.`display_sequence`, `fa`.`hide_on_entry` from `form_attributes` as `fa` inner join `attributes` as `a` on `a`.`id` = `fa`.`attribute` where `fa`.`form` = ? order by `fa`.`display_sequence`', 
            's', 
            [$form_choice]
        );
        foreach($xs as $x) {
            $field_id_for_show = htmlspecialchars($x['id'], ENT_QUOTES);
            echo "
        <li value='{$x['display_sequence']}'>
            <a href='../fields/?id={$field_id_for_show}' 
                title='Manage the field {$field_id_for_show}'>
            {$field_id_for_show} 
            </a>
        </li>
    ";
        }
        echo "</ol>\r\n";
    }
?>
</body>
</html>
