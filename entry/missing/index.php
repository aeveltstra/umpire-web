<?php
/**
 * Shows a case entry form. The fields are generated on the fly
 * based on the fiels listed in the database.
 * @author A.E.Veltstra
 * @version 2.23.909.1834
 */

include_once (dirname(__FILE__) . '/../../config.php');

/**
 * Reads the enumerations from the database. They are stored as
 * separate values for each attribute, with a language code.
 */
function read_enumerations_from_db() {

}

/**
 * Reads the enumerations from the database and generates a single
 * input constraint for each attribute.
 */
function show_enums() {

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
    global $eraskcsstufgyc, $vhjilfyhkkot, $bjkyfvbnkiyf, $yaefgvcaoelo;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = new mysqli($eraskcsstufgyc, $vhjilfyhkkot, $bjkyfvbnkiyf, $yaefgvcaoelo);
    $sql = 'SELECT `$id`, `data_type`, `$caption`, `$hint`, `$min`, `$max`, `$hide_on_entry` FROM `vw_missing_entry_form_attributes` order by `display_sequence` asc'; 
    $result = $mysqli->query($sql, MYSQLI_STORE_RESULT);
    $buffer = $result->fetch_all(MYSQLI_BOTH);
    $mock = [
        ['$id' => 'aliases', 'data_type' => 'shorttext', '$caption' => 'Other known names', '$hint' => 'Nick names, government names, pen names, etc.'],
        ['$id' => 'birth year', 'data_type' => 'integer', '$caption' => 'Born in which year?', '$hint' => 'Include the century.'],
        ['$id' => 'last seen on date', 'data_type' => 'date', '$caption' => 'Last Seen Date', '$hint' => 'To your best knowledge, at what date was this person seen last?'],
        ['$id' => 'last seen date accuracy', 'data_type' => 'percent', '$caption' => 'Accuracy of the Last Seen Date', '$hint' => 'On a scale of 0 to 100%, how accurate is the date at which this person was last seen?']
    ];
    return $buffer;
}

/**
 * Generates HTML for the form fields and echoes the result
 * into the page at the place of its invocation.
 */
function show_entry_fields() {
    $templates = array(
        'shorttext' => '<fieldset><legend>$caption</legend><p><label
        for="$id">$hint</label></p><p><input type=text size=60
        minlength="$min" maxlength="$max" name="$id" id="$id"/></p></fieldset>',
        'integer' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=number min="$min" max="$max" name="$id" id="$id"/></p></fieldset>',
        'enum' => '<fieldset><legend>$caption</legend><p><label
        for="$id">$hint</label></p><p><input type=text size=60
        minlength="$min" maxlength=256 name="$id" id="$id"/></p></fieldset>',
        'date' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=date name="$id" id="$id"/></p></fieldset>',
        'longtext' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><textarea cols=60 rows=10 maxlength="$max" name="$id" id="$id"></textarea></p></fieldset>',
        'percent' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=number min=0 max=100 name="$id" id="$id"/></p></fieldset>'
    );
    $fields = read_form_fields();
    foreach($fields as $field) {
        $t = $templates[$field['data_type']];
        if (!is_null($t)) {
            echo strtr($t, $field);
            echo "\r\n\r\n";
        }
    }
}

?>
<!DOCTYPE html>
<html lang=en encoding="utf-8">
<head>
<title>New Case Entry - Umpire</title>
<meta name=description value="Please share as many details as available"/>
</head>
<body>
<h1>New Case Entry - Umpire</h1>
<h2>Please share as many details as available</h2>
<form action=register method=post>
    <?php show_entry_fields(); ?>
    <fieldset>
        <legend>Done!</legend>
        <p><label><input type=checkbox name=agree /> I accept </label><a
        href="../terms">the terms and conditions.</a></p>
        <p><label><input type=submit value=Submit /></label></p>
    </fieldset>
</form>
</body>
</html>
