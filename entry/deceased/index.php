<?php
declare(strict_types=1);
/**
 * Shows a case entry form. The fields are generated on the fly
 * based on the fields listed in the database.
 * @author A.E.Veltstra
 * @version 2.23.1107.2048
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

/**
 * Reads the enumerations from the database and generates a single
 * input constraint for each attribute.
 */
function show_enums() {
    $xs = read_enumerations_from_db('en');
    if (!is_array($xs)) {
        return;
    }
    $last_id = '';
    $m = '';
    foreach($xs as list(
        'attribute_id' => $attribute_id, 
        'enum_value' => $enum_value, 
        'caption' => $caption
    )) {
        if (empty($last_id)) {
            $last_id = $attribute_id;
        } else if ($last_id != $attribute_id) {
            if ($m) {
                $n = '<datalist id="list_' . addslashes($last_id) . '">' . $m . '</datalist>';
                echo $n;
                echo "\r\n\t";
            }
            $last_id = $attribute_id;
            $m = '';
        }
        $v1 = addslashes($enum_value);
        $c2 = htmlspecialchars($caption);
        $m .= '<option value="' . $v1 . '">' . $c2 . '</option>';
    }
}

/**
 * Reads the database fields and their attributes, so they can be
 * used in the strtr() function to render HTML form fields.
 * @return a list of tuples. Every field is a tuple, with the
 * field attributes being the tuple elements. The list is sorted
 * by display sequence.
 * Like so:
 * [
 *     ['id' => 'aliases', 'data_type' => 'shorttext'],
 *     ['id' => 'birth year', 'data_type' => 'integer']
 * ]
 */
function read_deceased_form_entry_fields() {
    $sql = 'SELECT `id`, `data_type`, `caption`, `hint`, `min`, `max`, `default`, `is_write_once` FROM `vw_deceased_entry_form_attributes_en` order by `display_sequence` asc'; 
    return query($sql);
}


/**
 * Generates HTML for the form fields and echoes the result
 * into the page at the place of its invocation.
 */
function show_deceased_form_entry_fields() {
    $templates = array(
        'shorttext' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=text size=60 minlength="%5$d" maxlength="%6$d" name="%1$s" placeholder="%2$s" id="%1$s" %7$s/></p></fieldset>',
        'integer' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=number min="%5$d" max="%6$d" name="%1$s" id="%1$s" placeholder="%2$s" %7$s/></p></fieldset>',
        'enum' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=text size=60 minlength="%5$d" maxlength="%6$d" name="%1$s" id="%1$s" placeholder="%2$s" list="list_%1$s" %7$s/></p></fieldset>',
        'date' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=date name="%1$s" id="%1$s" placeholder="%2$s" %7$s/></p></fieldset>',
        'longtext' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><textarea cols=60 rows=10 maxlength="%6$d" name="%1$s" id="%1$s" placeholder="%2$s" %7$s></textarea></p></fieldset>',
        'percent' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=number min=0 max=100 name="%1$s" id="%1$s" placeholder="%2$s" %7$s/></p></fieldset>'
    );
    $fields = read_deceased_form_entry_fields();
    if (!is_array($fields)) {
        return;
    }
    foreach($fields as list(
        'id' => $id, 
        'data_type' => $data_type, 
        'caption' => $caption, 
        'hint' => $hint, 
        'min' => $min, 
        'max' => $max, 
        'is_write_once' => $is_write_once,
        'default' => $default
    )) {
        $t = $templates[$data_type];
        if (!is_null($t)) {
            $is_disabled = '';
            if ($is_write_once) {
                $is_disabled = 'disabled="disabled"';
            }
            echo sprintf(
                $t, 
                addslashes($id), 
                addslashes($default), 
                addslashes($caption), 
                addslashes($hint), 
                addslashes($min), 
                addslashes($max),
                $is_disabled
            );
            echo "\r\n\r\n";
        }
    }
}

$form_nonce = session_make_and_remember_nonce('deceased_entry_form');

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>New Deceaseed Person's Case Entry - Umpire</title>
<meta name=description content="Please share as many details as available"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>New Deceased Person's Case Entry - Umpire</h1>
<h2>Please share as many details as available</h2>
<form action="register/" method=post>
    <?php 
        show_enums();
    ?>
    <fieldset>
        <legend>Terms and conditions</legend>
        <p></p>
        <p><label><input type=checkbox name=agree /> I accept </label><a
        href="/umpire/terms/">the terms and conditions.</a></p>
    </fieldset>
    <?php 
        show_deceased_form_entry_fields(); 
        if ($form_nonce) {
            echo "<input type=hidden name=nonce value='$form_nonce' />\r\n";
        }
    ?>
    <fieldset>
        <legend>Done!</legend>
        <p><label><input type=submit value=Register /></label></p>
    </fieldset>
</form>
</body>
</html>
