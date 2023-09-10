<?php
/**
 * Shows a case entry form. The fields are generated on the fly
 * based on the fiels listed in the database.
 * @author A.E.Veltstra
 * @version 2.23.909.1834
 */


/**
 * Reads the database fields and their attributes, so they can be
 * used in the strtr() function to render HTML form fields.
 * @return a list of tuples. Every field is a tuple, with the
 * field attributes being the tuple elements. The list is sorted
 * by display sequence.
 * Like so:
 * [
 *     ('id' => 'aliases', 'data_type' => 'shorttext'),
 *     ('id' => 'birth year', 'data_type' => 'integer')
 * ]
 */
function read_form_fields() {
    $mock = array(
        array('$id' => 'aliases', 'data_type' => 'shorttext', '$caption' => 'Other known names'),
        array('$id' => 'birth year', 'data_type' => 'integer', '$caption' => 'Born in which year?'),
        array('$id' => 'last seen on date', 'data_type' => 'date', '$caption' => 'Last Seen Date', '$hint' => 'To your best knowledge, at what date was this person seen last?')
        array('$id' => 'last seen date accuracy', 'data_type' => 'percent',
        '$caption' => 'Accuracy of the Last Seen Date', '$hint' => 'On a
        scale of 0 to 100%, how accurate is the date at which this person
        was last seen?')
    );
    return $mock;
}

/**
 * Generates HTML for the form fields and echoes the result
 * into the page at the place of its invocation.
 */
function show_entry_fields() {
    $form_field_entry_templates = array(
        'shorttext' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=text size=60 maxlength=256 name="$id" id="$id"/></p></fieldset>',
        'integer' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=number size=8 name="$id" id="$id"/></p></fieldset>',
        'enum' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=text size=60 maxlength=256 name="$id" id="$id"/></p></fieldset>',
        'date' => '<fieldset><legend>$caption</legend><p><label for="$id">$hint</label></p><p><input type=date name="$id" id="$id"/></p></fieldset>',
        'longtext' => '<fieldset><legend>$id</legend><p><label for="$id">$desc</label></p><p><textarea cols=60 rows=10 name="$id" id="$id"></textarea></p></fieldset>'
    );
    $form_fields = read_form_fields();
    foreach($form_fields as $field) {
        $t = $form_field_entry_templates[$field['data_type']];
        echo strtr($t, $field);
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
<form action="register.php" method=post>
    <?php show_entry_fields(); ?>
    <fieldset>
        <legend>Done!</legend>
        <p><label><input type=checkbox name=agree /> I accept </label><a
        href="terms">the terms and conditions.</a></p>
        <p><label><input type=submit value=Submit /></label></p>
    </fieldset>
</form>
</body>
</html>
