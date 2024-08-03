<?php
/**
 * Manage Entry Form Fields for Umpire
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.803.1800
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

if (!session_did_user_authenticate()) {
    session_remember('return_to', '/umpire/manage/fields/');
    header('Location: /umpire/sign-in/');
    die();
}

$current_user = session_recall_user_token();
$held_privileges = db_which_of_these_privileges_does_user_hold(
    $current_user,
    'may_manage_forms'
);
if (empty($held_privileges)) {
    session_remember('return_to', '/umpire/manage/fields/');
    header('Location: ../access-denied/'); 
    die();
}
$field_nonce = session_make_and_remember_nonce('manage_entry_form_fields');

$field_choice = '';
if (isset($_GET['id'])) {
    $field_choice = $_GET['id'];
}
$is_field_known = false;
$field_translation = '';
$languages_missing_from_field_translations = [];
if (!empty($field_choice)) {
    $get_field_exists = query(
        'select `id` 
            from `attributes` 
            where `id` = ?',
        's',
        [$field_choice]
    );
    $is_field_known = (count($get_field_exists) > 0);
    $field_translations = query(
        'select `attribute_id`, 
            `translation`, 
            `language_code`, 
            `hint` 
            from `attribute_translations` 
            where `attribute_id` = ?
            order by 3',
        's',
        [$field_choice]
    );
    $amount = count($field_translations);
    $field_has_translations = ($amount > 0);
    if ($field_has_translations) {
        for ($i = 0; $i < $amount; $i+=1) {
            if ('en' === $field_translations[$i]['language_code']) {
                $field_translation = $field_translations[$i]['translation'];
            }
        }
        if (empty($field_translation)) {
            $field_translation = $field_translations[0]['translation'];
        }
    }
    $languages_missing_from_field_translations = query(
        'select `code` from `language_codes`
            where not exists ( 
                select 1 from `attribute_translations` 
                where `language_code` = `code`
                and `attribute_id` = ?
            )
            order by 1',
        's',
        [$field_choice]
    );
}

$field_id_for_show = htmlspecialchars($field_choice, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Manage Umpire Entry Form Field</title>
<meta name=description content="Change details and translations."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../c/main.css"/>
<link rel=stylesheet href="../../c/manage-field.css?v=2.24.803.1807"/>
<script type="text/javascript">/* <![CDATA[ */

function hide_changed(input_id) {
    "use strict";
    if (!!input_id) {
        const notice = document.getElementById("changed_" + input_id);
        if (!!notice) {
            notice.hidden = true;
        }
    }
}
function hide_fail(input_id) {
    "use strict";
    if (!!input_id) {
        const notice = document.getElementById("failed_" + input_id);
        if (!!notice) {
            notice.hidden = true;
        }
    }
}
function hide_success(input_id) {
    "use strict";
    if (!!input_id) {
        const notice = document.getElementById("succeeded_" + input_id);
        if (!!notice) {
            notice.hidden = true;
        }
    }
}
function show_success(input_id) {
    "use strict";
    if (!!input_id) {
        hide_fail(input_id);
        hide_changed(input_id);
        const notice = document.getElementById("succeeded_" + input_id);
        if (!!notice) {
            notice.hidden = false;
        }
    }

}
function show_changed(input_id, response_status) {
    "use strict";
    if (!!input_id) {
        hide_success(input_id);
        hide_fail(input_id);
        const notice = document.getElementById("changed_" + input_id);
        if (!!notice) {
            notice.hidden = false;
        }
    }
}
function show_fail(input_id, data) {
    "use strict";
    if (!!input_id) {
        hide_success(input_id);
        hide_changed(input_id);
        const notice = document.getElementById("failed_" + input_id);
        if (!!notice) {
            notice.hidden = false;
            notice.title = 'Storing Failed';
            if (data) {
                if (!data.success && !!data.errors) {
                    data.errors.forEach(err => 
                        notice.title += "  \r\n" + err
                    );
                }
                console.log(data);
            }
        }
    }
}

/**
 * This function hopes to retrieve an older version of a value
 * based on the identity of the new value's input field.
 * It expects that the new input field has its ID starting with
 * 'new_', and looks for a field for the old value that has 
 * its ID starting with 'old_'. If such a field is found, its
 * value is returned. Otherwise, null is returned.
 */
function get_old_value(id) {
    "use strict";
    if (!id || !id.replace) {
        return null;
    }
    const old_id = id.replace('new_', 'old_');
    if (old_id == id) {
        return null;
    }
    const old_element = document.getElementById(old_id);
    if (!old_element || !old_element.value) {
        return null;
    }
    return old_element.value;
}

/**
 * Attempts to replace the fielder old field value 
 * with the new one, to enable repeated updates.
 * The function assumes the ID of the elements
 * involved start with 'new_' and 'old_'. If either
 * can't be found, this function returns false.
 * If all goes well, it returns true.
 */
function set_new_as_old_value(id) {
    "use strict";
    if (!id || !id.replace) {
        return false;
    }
    const old_id = id.replace('new_', 'old_');
    if (old_id == id) {
        return false;
    }
    const new_element = document.getElementById(id);
    const old_element = document.getElementById(old_id);
    if (!new_element || !old_element) {
        return false;
    }
    old_element.value = new_element.value;
    return true;
}

function get_picked_added_language() {
    "use strict";
    const x = document.getElementById('add_translation_lang_pick');
    if (!!x && !!x.value) {
        return x.value;
    }
    return '';
}

function get_added_translation() {
    "use strict";
    const x = document.getElementById('added_translation');
    if (!!x && !!x.value) {
        return x.value;
    }
    return '';
}

function store_field_translation(input) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input) {
        const id = input.id;
        if (!!id) {
            show_changed(id);
            const xs = id.split('_');
            if (xs.length) {
                let x = xs[xs.length - 1];
                if ('lang' == x) {
                  x = get_picked_added_language();
                }
                const fd = new FormData();
                fd.append('field_id', '<?php echo addslashes($field_id_for_show); ?>');
                fd.append('language', x);
                if ('new' == xs[0]) {
                    fd.append('new_translation', input.value);
                    fd.append('old_translation', get_old_value(id));
                } else if ('add' == xs[0]) {
                    fd.append('new_translation', get_added_translation());
                }
                fd.append('nonce', '<?php echo addslashes($field_nonce); ?>');
                fetch(
                    './store_field_translation.php?t=' + Date.now(),
                    {
                        method: "POST",
                        body: fd,
                        cache: "no-store",
                        mode: "same-origin",
                        credentials: "include"
                    }
                ).then((response) => {
                    if (response.ok) {
                        response.json().then(data => {
                            if (data.success) {
                                show_success(id);
                                set_new_as_old_value(id);
                            } else {
                                show_fail(id, data);
                            }
                        }).catch(alert);
                    } else {
                        response.json().then(data =>
                            show_fail(id, data)
                        );
                    }
                }).catch(alert);
            }
        }
    }
    return false;
}
function store(input, attrib_id) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input && attrib_id) {
        show_changed(attrib_id);
        const property = input.id;
        if (!!property) {
            const fd = new FormData();
            fd.append('field_id', '<?php echo $field_id_for_show; ?>');
            fd.append('attribute', attrib_id);
            fd.append('property', property);
            fd.append('old_value', get_old_value(property));
            fd.append('new_value', input.value);
            fd.append('nonce', '<?php echo $field_nonce; ?>');
            fetch(
                './store_field_attribute.php',
                {
                    method: "POST",
                    body: fd,
                    cache: "no-store",
                    mode: "same-origin",
                    credentials: "include"
                }
            ).then((response) => {
                if (response.ok) {
                    response.json().then(data => {
                        if (data.success) {
                            set_new_as_old_value(attrib_id);
                            show_success(attrib_id);
                        } else {
                            show_fail(attrib_id, data);
                        }
                    }).catch(alert);
                } else {
                    response.json().then(data => 
                        show_fail(attrib_id, data)
                    );
                }
            }).catch(alert);
        }
    }
    return false;
}

/* ]]> */</script>
</head>
<body>
    <h1>Manage Umpire Entry Form Field</h1>
<?php
if (!$is_field_known) {
    echo '<h2>Choose which field to edit:</h2><ul>';
    $rows = query(
        'select `attribute_id`, `translation` 
                from `attribute_translations` 
                where `language` = \'en\''
    );
    foreach ($rows as $row) {
        $id_for_show = htmlspecialchars(
            $row['attribute_id'], ENT_QUOTES
        );
        $translation_for_show = htmlspecialchars(
            $row['translation'], ENT_QUOTES
        );
        echo "<li><a href='?id={$id_for_show}'>{$translation_for_show}</a></li>";
    }
    echo '</ul>';
} else {
    $field_translation_for_show = htmlspecialchars($field_translation, ENT_QUOTES);
    echo "
    <h2>Field being edited: <q>{$field_translation_for_show}</q>.</h2>
    <p>Note: changes happen immediately after leaving an attribute.</p>
    <section>
        <h3>Change Translations and Hints</h3>
        <field><fieldset><legend>Each language has its own:</legend>
        <table>
            <thead>
                <tr>
                    <th>&nbsp;&nbsp;</th>
                    <th>Language</th>
                    <th>Translation</th>
                    <th>Hint</th>
                </tr>
            </thead>
            <tbody>";
    foreach ($field_translations as $translation) {
        $c = htmlspecialchars($translation['translation'], ENT_QUOTES);
        $t = htmlspecialchars($translation['language_code'], ENT_QUOTES);
        $h = htmlspecialchars($translation['hint'], ENT_QUOTES);
        echo "
<tr>
    <td>
        <span hidden class=changed 
        id=changed_new_translation_{$t} 
        title='Changed'>&hellip;</span>
        <span hidden class=failed 
        id=failed_new_translation_{$t} 
        title='Storing failed'>&otimes;</span>
        <span hidden class=succeeded 
        id=succeeded_new_translation_{$t} 
        title='Stored successfully'>&radic;</span>
    </td>
    <th>{$t}</th>
    <td>
        <label for=new_translation_{$t}>
        <input type=text 
            name=new_translation_{$t} 
            id=new_translation_{$t} 
            size=24 
            maxlength=255 
            placeholder='{$c}' 
            value='{$c}' 
            onchange='store_field_translation(this)' />
        </label>
        <input type=hidden 
            name=old_translation_{$t} 
            id=old_translation_{$t} 
            value=\"{$c}\"
        />
    </td>
    <td>
        <label for=new_hint_{$t}>
        <input type=text 
            name=new_hint_{$t} 
            id=new_hint_{$t} 
            size=64 
            maxlength=255 
            placeholder='{$h}' 
            value='{$h}' 
            onchange='store_field_hint(this)' />
        </label>
        <input type=hidden 
            name=old_hint_{$t} 
            id=old_hint_{$t} 
            value=\"{$h}\"
        />
    </td>
</tr>
            ";
    }
    echo "</tbody>";
    if (count($languages_missing_from_field_translations) > 0) {
        $add_language_options = '';
        foreach ($languages_missing_from_field_translations as $x) {
            $add_language_options .= '<option>' . addslashes($x['code'])
            . '</option>' . "\r\n\t";
        }
        echo "<tfoot>
            <td>
                <span hidden class=changed 
                id=changed_add_translation_lang 
                title='Changed'>&hellip;</span>
                <span hidden class=failed 
                id=failed_add_translation_lang
                title='Adding failed'>&otimes;</span>
                <span hidden class=succeeded 
                id=succeeded_add_translation_lang
                title='Added successfully'>&radic;</span>
            </td>
            <th><select id=add_translation_lang_pick
                name=add_translation_lang_pick>
                {$add_language_options}
                </select></th>
            <td><input type=text
                id=added_translation
                name=added_translation
                size=24
                maxsize=255
                value='' 
                placeholder='New translation for chosen language'
            /></td>
            <td><input type=text
                id=added_hint
                name=added_hint
                size=64
                maxsize=255
                value='' 
                placeholder='New hint for chosen language'
            /></td>
            <td><label><input type=submit 
                id=add_translation_lang
                name=add_translation_lang
                onclick='add_field_translation_and_hint(this);'
                value='+'
                title='Add new translation and hint for chosen language'
                />&nbsp;Add</label></td>
        </tfoot>
        ";
    }
    echo "</table></fieldset></field>
</section>
<section>
<h3>Change Attributes</h3>
<p>Note: display sequence and hide-on-entry are particular to entry forms,
not to the fields.</p>
<details><summary>Attributes Explained</summary>
<ol>
<li>The identity cannot be changed.</li>
<li>The data type is required. It determines how a field gets shown.</li>
<li>The minimum value is optional. For texts, this determines the least amount of characters a user has to enter. For numbers, this determines the smallest number allowed to be entered.</li>
<li>The maximum value is optional. For texts, this determines the highest amount of characters a user may enter. For numbers, this determines the highest number allowed to be entered.</li>
<li>Default Value is optional. This sets a value that will be assigned automatically, if the user chooses to enter nothing.</li>
<li>Mark the Write-Once checkbox to determine that the field's value can be entered, but not changed.</li>
</ol>
</details>
<form>
<fieldset>
<table>
<thead>
    <tr>
        <th>&nbsp;&nbsp;</th>
        <th>Identity</th>
        <th>Data Type</th>
        <th>Minimum</th>
        <th>Maximum</th>
        <th>Default Value</th>
        <th>Write-Once</th>
    </tr>
</thead>
<tbody>
            ";
    $xs = query(
        'select `a`.*
            from `attributes` as `a` 
            where `a`.`id` = ?', 
        's', 
        [$field_choice]
    );
    $dt_options = '';
    $dts = [
        'date',
        'email',
        'enum',
        'image',
        'integer',
        'location',
        'longtext', 
        'percent',
        'shorttext', 
        'time'
    ];
    foreach ($dts as $dt) {
        $dt_options .= "<option>{$dt}</option>";
    }
    foreach ($xs as $x) {
        $id = $x['id'];
        $attrib_id     = htmlspecialchars($id, ENT_QUOTES);
        $data_type     = htmlspecialchars($x['data_type'], ENT_QUOTES);
        $min           = $x['min'];
        $max           = $x['max'];
        $default       = htmlspecialchars($x['default'], ENT_QUOTES);
        $is_write_once = (
            (1 == $x['is_write_once']) 
            ? 'checked=checked' 
            : ''
        );
        $enum_list = '';
        $enum_mgr_hidden = 'hidden';
        if ($x['data_type'] == 'enum') {
            $enum_mgr_hidden = '';
        }
        echo "
        <tr>
            <td>
                <span hidden class=changed 
                id=changed_{$attrib_id} 
                title='Changed'>&hellip;</span>
                <span hidden class=failed 
                id=failed_{$attrib_id} 
                title='Storing failed'>&otimes;</span>
                <span hidden class=succeeded 
                id=succeeded_{$attrib_id} 
                title='Stored successfully'>&radic;</span>
            </td>
            <td>{$attrib_id}</td>
            <td><select 
                name=new_data_type_{$attrib_id}
                id=new_data_type_{$attrib_id}
                onchange='store(this, \"{$attrib_id}\");'>
                <optgroup label='Current choice:'>
                    <option selected=selected>{$data_type}</option>
                </optgroup>
                <optgroup label='Other choices:'>
                    {$dt_options}
                </optgroup>
            </select><input type=hidden 
                name=old_data_type_{$attrib_id}
                id=old_data_type_{$attrib_id}
                value=\"{$data_type}\"
            /></td>
            <td><input type=number 
                name=new_min_{$attrib_id} 
                id=new_min_{$attrib_id} 
                onchange='store(this, \"{$attrib_id}\")'
                value='{$min}'
            /><input type=hidden 
                name=old_min_{$attrib_id}
                id=old_min_{$attrib_id}
                value=\"{$min}\"
            /></td>
            <td><input type=number 
                name=new_max_{$attrib_id} 
                id=new_max_{$attrib_id} 
                onchange='store(this, \"{$attrib_id}\")' 
                value='{$max}'
            /><input type=hidden 
                name=old_max_{$attrib_id}
                id=old_max_{$attrib_id}
                value=\"{$max}\"
            /></td>
            <td><input type=text 
                name=new_default_{$attrib_id} 
                id=new_default_{$attrib_id} 
                onchange='store(this, \"{$attrib_id}\")' 
                value='{$default}' {$enum_list} 
            /><input type=hidden 
                name=old_default_{$attrib_id}
                id=old_default_{$attrib_id}
                value=\"{$default}\"
            /></td>
            <td><input type=checkbox 
                name=new_is_write_once_{$attrib_id} 
                id=new_is_write_once_{$attrib_id} 
                {$is_write_once} 
                onchange='store(this, \"{$attrib_id}\")'
            /><input type=hidden 
                name=old_is_write_once_{$attrib_id} 
                id=old_is_write_once_{$attrib_id} 
                {$is_write_once} 
            /></td>
        </tr>
    ";
    }
    echo '</tbody></table></fieldset>';
}
?>
</body>
</html>
