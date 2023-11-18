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
        'select `form`, `caption` from `form_caption_translations` where `language` = \'en\' and `form` = ?',
        's',
        [$form_choice]
    );
    if (count($rows) === 1 && isset($rows[0]['form'])) {
        $is_form_known = true;
        $form_caption = $rows[0]['caption'];
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
<link rel=stylesheet href="/umpire/c/manage-form.css"/>
</head>
<body>
    <h1>Manage Umpire Entry Forms</h1>
<?php
    if (!$is_form_known) {
        echo '<h2>Choose which form to edit:</h2><ul>';
        $rows = query('select `form`, `caption` from `form_caption_translations` where `language` = \'en\'');

        foreach($rows as $row) {
            $id_for_show = htmlspecialchars(
                $row['form'], ENT_QUOTES
            );
            $caption_for_show = htmlspecialchars(
                $row['caption'], ENT_QUOTES
            );
            echo "<li><a href='?id={$id_for_show}'>{$caption_for_show}</a></li>";
        }
        echo '</ul>';
    } else {
        $form_id_for_show = htmlspecialchars($form_choice, ENT_QUOTES);
        $form_caption_for_show = htmlspecialchars($form_caption, ENT_QUOTES);
        echo "
    <h2>Editing Form: {$form_caption_for_show}.</h2>
    <h3>These attributes are assigned currently.</h3>
    <form id='attriutes_for_form_{$form_id_for_show}'><table>
    <thead>
        <tr><th>Display Sequence</th>
            <th>Identity</th>
            <th>Data Type</th>
            <th>Minimum Value</th>
            <th>Maximum Value</th>
            <th>Default Value</th>
            <th>Write Once</th>
            <th>Hide on Entry</th>
        </tr>
    </thead>
    <tbody>
              ";
        $xs = query(
            'select `a`.*, `fa`.`display_sequence`, `fa`.`hide_on_entry` from `form_attributes` as `fa` inner join `attributes` as `a` on `a`.`id` = `fa`.`attribute` where `fa`.`form` = ? order by `fa`.`display_sequence`', 
            's', 
            [$form_choice]
        );

        foreach($xs as $x) {
            $display_seq   = $x['display_sequence'];
            $field_id      = htmlspecialchars($x['id'],        ENT_QUOTES);
            $data_type     = htmlspecialchars($x['data_type'], ENT_QUOTES);
            $min           = $x['min'];
            $max           = $x['max'];
            $default       = htmlspecialchars($x['default'],   ENT_QUOTES);
            $is_write_once = ((1 == $x['is_write_once']) ? 'checked=checked' : '');
            $hide_on_entry = ((1 == $x['hide_on_entry']) ? 'checked=checked' : '');
            echo "
        <tr><th>{$display_seq}</th>
            <td>{$field_id}</td>
            <td><select><option>{$data_type}</option></select></td>
            <td><input type=number value='{$min}'/></td>
            <td><input type=number value='{$max}'/></td>
            <td><input type=text value='{$default}'/></td>
            <td><input type=checkbox {$is_write_once} /></td>
            <td><input type=checkbox {$hide_on_entry} /></td>
        </tr>
    ";
        }
        echo "</tbody></table></form>\r\n";
    }
?>
</body>
</html>
