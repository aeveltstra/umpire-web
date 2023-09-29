<?php
declare(strict_types=1);
/**
 * Shows a case entry form. The fields are generated on the fly
 * based on the fields listed in the database.
 * @author A.E.Veltstra
 * @version 2.23.928.2211
 */
include_once '../../db_utils.php';

/**
 * Reads the enumerations from the database. They are stored as
 * separate values for each attribute, with a language code.
 */
function read_enumerations_from_db() {
    $sql = "select `attribute_id`, `enum_value`, `caption` from `enums` where `language_code` = 'en' order by `attribute_id`, `caption`";
    return query($sql);
}


/**
 * Reads the enumerations from the database and generates a single
 * input constraint for each attribute.
 */
function show_enums() {
  
    $xs = read_enumerations_from_db();
    
    $a = '';
    $m = '';
    foreach($xs as $x) {
        [$b, $e] = $x;
        if ($a != $b) {
            if ($m) {
                $n = '<datalist id="list_' . addslashes($a) . '">' . $m . '</datalist>';
                echo $n;
            }
            $a = $b;
            $m = '';
        }
        $v = addslashes($e);
        $m .= '<option value="' . $v . '">' . $v . '</option>';
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
function read_form_fields() {
    $sql = 'SELECT `id`, `data_type`, `caption`, `hint`, `min`, `max`, `hide_on_entry` FROM `vw_missing_entry_form_attributes_en` order by `display_sequence` asc'; 
    return query($sql);
}


/**
 * Generates HTML for the form fields and echoes the result
 * into the page at the place of its invocation.
 */
function show_entry_fields() {
    $templates = array(
        'shorttext' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=text size=60 minlength="%5$d" maxlength="%6$d" name="%1$s" id="%1$s"/></p></fieldset>',
        'integer' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=number min="%5$d" max="%6$d" name="%1$s" id="%1$s"/></p></fieldset>',
        'enum' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=text size=60 minlength="%5$d" maxlength="%6$d" name="%1$s" id="%1$s" list="list_%1$s"/></p></fieldset>',
        'date' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=date name="%1$s" id="%1$s"/></p></fieldset>',
        'longtext' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><textarea cols=60 rows=10 maxlength="%6$d" name="%1$s" id="%1$s"></textarea></p></fieldset>',
        'percent' => '<fieldset><legend>%3$s</legend><p><label for="%1$s">%4$s</label></p><p><input type=number min=0 max=100 name="%1$s" id="%1$s"/></p></fieldset>'
    );
    $fields = read_form_fields();
    foreach($fields as $field) {
       [$id, $data_type, $caption, $hint, $min, $max, $hide_on_entry] = $field;
        $t = $templates[$data_type];
        if (!is_null($t)) {
            echo sprintf($t, $id, $hide_on_entry, $caption, $hint, $min, $max);
            echo "\r\n\r\n";
        }
    }
}


?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>New Missing Person's Case Entry - Umpire</title>
<meta name=description content="Please share as many details as available"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>New Missing Person's Case Entry - Umpire</h1>
<h2>Please share as many details as available</h2>
<form action=register method=post>
    <?php 
        show_enums();
        show_entry_fields(); 
    ?>
    <fieldset>
        <legend>Done!</legend>
        <p><label><input type=checkbox name=agree /> I accept </label><a
        href="/umpire/terms">the terms and conditions.</a></p>
        <p><label><input type=submit value=Submit /></label></p>
    </fieldset>
</form>
</body>
</html>
