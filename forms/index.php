<?php
/**
 * Shows an entry form. The fields are generated on the fly
 * based on the fields listed in the database.
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.707.1520
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

$page_title = $form_caption . ' - Umpire';

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$form_nonce = session_make_and_remember_nonce('form_' . $prefixed_form_id);
session_remember('last_case_form_id', $prefixed_form_id);

/**
 * Reads the enumerations from the database and generates a single
 * input constraint for each attribute.
 * 
 * @param $lang should be the language code known by the database, 
 *              that is assigned to the enumeration translations for
 *              the desired language.
 * 
 * @return void / nothing: this function writes straight to the web page.
 */
function show_enums(string $lang)
{
    $xs = db_read_enumerations($lang);
    if (!is_array($xs)) {
        return;
    }
    $last_id = '';
    $m = '';
    foreach ($xs as list(
        'attribute_id' => $attribute_id,
        'enum_value' => $enum_value,
        'caption' => $caption
    )) {
        if (empty($last_id)) {
            $last_id = $attribute_id;
        } else if ($last_id != $attribute_id) {
            if ($m) {
                $n = '<datalist id="list_' 
                    . addslashes($last_id) 
                    . '">' 
                    . $m 
                    . '</datalist>';
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
 * Generates HTML for the form fields and echoes the result
 * into the page at the place of its invocation.
 * 
 * @param $form_id should identify for which form to retrieve the 
 *                 entry fields. Should be a form identity known in the
 *                 database.
 * @param $lang    should be the language in which the form captions and
 *                 hints should be retrieved for the desired form.
 *                 The language code must exist in the database.
 * 
 * @return void / nothing: this function writes straight to the page.
 */
function show_form_entry_fields(string $form_id, string $lang) 
{
    $templates = array(
        'date' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=date 
                            name="%1$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            %7$s>
                    </p><hr>',
        'email' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=email 
                            size=60 
                            minlength="%5$d" 
                            maxlength="%6$d"
                            inputmode=email 
                            name="%1$s" 
                            placeholder="%2$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            %7$s>
                    </p><hr>',
        'enum' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=text 
                            size=60 
                            minlength="%5$d" 
                            maxlength="%6$d" 
                            name="%1$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            placeholder="%2$s" 
                            list="list_%1$s" 
                            %7$s>
                    </p><hr>',
        'image' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=file 
                            alt="%3$s"
                            capture=camera
                            size=60 
                            minlength="%5$d" 
                            maxlength="%6$d" 
                            name="%1$s" 
                            placeholder="%2$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            %7$s>
                    </p><hr>',
        'integer' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=text
                            inputmode=numeric 
                            name="%1$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            placeholder="%2$s" 
                            pattern="[0-9]*"
                            %7$s>
                    </p><hr>',
        'location' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type="text" 
                            maxlength="25" 
                            name="%1$s"
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            placeholder="%2$s" 
                            %7$s>
                    </p><hr>',
        'longtext' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><textarea cols=60 
                            rows=10 
                            maxlength="%6$d" 
                            name="%1$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            placeholder="%2$s" 
                            %7$s></textarea>
                    </p><hr>',
        'percent' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><abbr title="none">0%%</abbr> 
                        <input type=range 
                            min=0 
                            max=100 
                            inputmode=numeric 
                            step=5
                            name="%1$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            onchange="this.title = this.value"
                            %7$s
                    > <abbr title="all">100%%</abbr>
                    </p><hr>',
        'shorttext' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=text 
                            size=60 
                            minlength="%5$d" 
                            maxlength="%6$d" 
                            name="%1$s" 
                            placeholder="%2$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            %7$s>
                    </p><hr>',
        'time' => '<p><label for="%1$s">%3$s</label></p>
                    <p class=hint id="hint_%1$s">%4$s</p>
                    <p><input type=time 
                            name="%1$s" 
                            id="%1$s" 
                            aria-describedby="hint_%1$s"
                            placeholder="" 
                            %7$s>
                    </p><hr>'
    );
    $fields = db_read_form_entry_fields($form_id, $lang);
    if (!is_array($fields)) {
        return;
    }
    foreach ($fields as list(
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
                $min,
                $max,
                $is_disabled
            );
            echo "\r\n\r\n";
        }
    }
}

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8">
    <title><?php echo $page_title; ?></title>
    <meta name=description content="Please share as many details as available">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../../c/main.css">
</head>
<body>
    <header>
        <h1><?php echo $page_title; ?></h1>
        <h2>Please share as many details as available</h2>
    </header>
    <main>
    <form action="../register-form-entry/" method=post>
        <?php
            show_enums('en');
        ?>
        <fieldset>
            <legend>Terms and conditions</legend>
            <p><label><input type=checkbox name=agree> I accept </label><a
            href="../../terms/">the terms and conditions.</a></p>
        </fieldset>
        <?php
            show_form_entry_fields($prefixed_form_id, 'en');
            echo "<input type=hidden name=nonce value='$form_nonce'>\r\n";
            echo "<input type=hidden value='$prefixed_form_id' name=form_id>";
        ?>
        <fieldset>
            <legend>Done!</legend>
            <p><label><input type=submit value=Register> 
                Add it to the registry</label></p>
        </fieldset>
    </form>
    </main>
</body>
</html>
