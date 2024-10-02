<?php
/**
 * Manage Entry Form Fields for Umpire
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.1001.1929
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
$field_nonce = session_make_and_remember_nonce('manage_entry_form_field');

$field_choice = '';
if (isset($_GET['id'])) {
    $field_choice = $_GET['id'];
}
$is_field_known = false;
$field_translation = '';
$field_translations = [];
$languages_missing_from_field_translations = [];
$enumerations = [];
$enumerations_for_show = '';
$languages_missing_from_enums_for_show = '';
if (!empty($field_choice)) {
    $get_field_exists = query(
        'select `id` 
            from `attributes` 
            where `id` = ?',
        's',
        [$field_choice]
    );
    $is_field_known = false;
        $is_field_known = (count($get_field_exists) > 0);
        $field_translations = query(
            'select `attribute_id`, 
                `translation`, 
                `language_code`, 
                `hint` 
                from `attribute_translations` 
                where `attribute_id` = ?
                order by `language_code` asc',
            's',
            [$field_choice]
        );
    $amount = 0;
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
            order by `code` asc',
        's',
        [$field_choice]
    );
    $enumerations = query(
        'select `enum_value`, `language_code`, `caption`
         from `enums` where `attribute_id` = ? 
         order by 1, 2, 3',
        's',
        [$field_choice]
    );
    if ($enumerations) {
        $i = 0;
        foreach($enumerations as $enum) {
	    $enum_val_for_show = htmlspecialchars(
	        $enum['enum_value'], ENT_QUOTES
	    );
	    $enum_caption_for_show = htmlspecialchars(
	        $enum['caption'], ENT_QUOTES
	    );
            $language_for_show = htmlspecialchars(
                $enum['language_code'], ENT_QUOTES
            );
            $enumerations_for_show .= "<tr>
                <td>
			<span hidden class=changed
			id=changed_enum_{$i}
			title=Changed>&hellip;</span>
			<span hidden class=failed
			id=failed_changed_enum_{$i}
			title='Storing failed'>&otimes;</span>
			<span hidden class=succeeded
			id=succeeded_changed_enum_{$i}
			title='Stored successfully'>&radic;</span>
                </td>
		<td>{$enum_val_for_show}</td>
                <td>{$language_for_show}</td>
                <td><label 
                    title='What gets shown for this value and language'>
                    <input type=text size=48 maxlength=256
                      minlength=1 id=enum_caption_for_show_{$i}
                      name=enum_caption_for_show_{$i}
                      value='{$enum_caption_for_show}'
                     /></label>
                </td>
                <td>
			<label 
			title='Remove this value from this enumeration'
			><button type=button
			title='Remove this value from this enumeration'
			id=remove_enum_value_{$i}
			name=remove_enum_value_{$i}
			onclick='remove_enum_value(this, \"{$i}\")'
			class=remove
			>X</button> Remove</label>
                </td>
            </tr>\n";
            $i += 1;
        }
    }
    $languages_missing_from_enum_translations = query(
        'select `code` from `language_codes`
            where not exists ( 
                select 1 from `enums` 
                where `language_code` = `code`
                and `attribute_id` = ?
            )
            order by `code` asc',
        's',
        [$field_choice]
    );
    foreach($languages_missing_from_enum_translations as $m) {
        $m2 = htmlspecialchars(
            $m['code'],
            ENT_QUOTES
        );
        $languages_missing_from_enums_for_show .= "<option value='{$m2}'>{$m2}</option>";
    }
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
    if ('checkbox' === old_element.type) {
        return old_element.checked;
    } else {
        return old_element.value;
    }
}

/**
 * Attempts to replace the old field value 
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
    if ('checkbox' === new_element.type) {
        old_element.value = (new_element.checked) ? 1 : 0;
    } else {
        old_element.value = new_element.value;
    }
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

function store_field_translation_hint(input) {
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
                    fd.append('new_hint', input.value);
                    fd.append('old_hint', get_old_value(id));
                } else if ('add' == xs[0]) {
                    fd.append('new_hint', get_added_translation());
                }
                fd.append('nonce', '<?php echo addslashes($field_nonce); ?>');
                fetch(
                    './store_field_translation_hint.php?t=' + Date.now(),
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

function store(input) {
    "use strict";
/*
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
*/
    if (input) {
        show_changed(input.id);
            const fd = new FormData();
            fd.append('field_id', '<?php echo $field_id_for_show; ?>');
            fd.append('property', input.id);
            fd.append('old_value', get_old_value(input.id));
            if ('checkbox' === input.type) {
                fd.append('new_value', input.checked);
            } else {
                fd.append('new_value', input.value);
            }
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
                            set_new_as_old_value(input.id);
                            show_success(input.id);
                        } else {
                            show_fail(input.id, data);
                        }
                    }).catch(alert);
                } else {
                    response.json().then(data => 
                        show_fail(input.id, data)
                    );
                }
            }).catch(alert);
    }
    return false;
}

function remove_translation_row(row_id) {
    "use strict";
    //row_id is expected to be the element id of 
    //the button that got pressed. It lives in a label,
    //which lives in a td, which lives in a tr element.
    //We remove that tr element.
    const x = document.getElementById(row_id);
    if (!!x) {
        x.parentNode.parentNode.removeChild(x.parentNode);
    }
}

function remove(input, language_code) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input && language_code) {
        show_changed(input.id);
        const confirmed = confirm(
            'Remove translation for language '
            + language_code
            + ' from this field?'
        );
        if (!is_confirmed) {
            hide_changed(input.id);
            return false;
        }
        const fd = new FormData();
        fd.append('field_id', '<?php echo $field_id_for_show; ?>');
        fd.append('language_code', language_code);
        fd.append('nonce', '<?php echo $field_nonce; ?>');
        fetch(
            './remove_field_translation.php',
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
                        remove_translation_row(input.id);
                    } else {
                        show_fail(input.id, data);
                    }
                }).catch(alert);
            } else {
                response.json().then(data => {
                    show_fail(attrib_id, data)
                });
            }
        }).catch(alert);
    }
}

function show_enum_mgr_if_needed(evt) {
    "use strict";
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    const e = document.getElementById("new_data_type");
    if (!e || !e.selectedOptions) { return; }
    const m = document.getElementById("data_type_enum_mgr");
    if (!m) { return; }
    if (
        (e.value != "undefined")
        && (e.value != null)
        && (e.value === "enum")
    ) {
        m.hidden = false;
    } else {
        m.hidden = true;
    }
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
         where `language_code` = \'en\''
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
    echo <<<END
    <h2>Field being edited: <q>{$field_translation_for_show}</q>.</h2>
    <p>Note: field changes affect all forms to which a field has been added.</p>
    <section>
      <form>
        <h3>Change Translations and Hints</h3>
        <p>Note: changes happen immediately after leaving a field.</p>
        <fieldset><legend>Each language has its own:</legend>
        <table>
            <thead>
                <tr>
                    <th>Language</th>
                    <th>Translation</th>
                    <th>&nbsp;&nbsp;</th>
                    <th>Hint</th>
                    <th>&nbsp;&nbsp;</th>
                </tr>
            </thead>
            <tbody>
END;
    foreach ($field_translations as $translation) {
        $c = htmlspecialchars($translation['translation'], ENT_QUOTES);
        $t = htmlspecialchars($translation['language_code'], ENT_QUOTES);
        $h = htmlspecialchars($translation['hint'], ENT_QUOTES);
        echo <<<END
<tr>
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
            onchange='store_field_translation(this)'
        />
        </label>
    </td>
    <td>
        <span hidden class=changed
        id=changed_new_translation_{$t}
        title=Changed>&hellip;</span>
        <span hidden class=failed
        id=failed_new_translation_{$t}
        title='Storing failed'>&otimes;</span>
        <span hidden class=succeeded
        id=succeeded_new_translation_{$t}
        title='Stored successfully'>&radic;</span>
    </td>
    <td>
        <label for=new_translation_hint_{$t}>
        <input type=text 
            name=new_translation_hint_{$t} 
            id=new_translation_hint_{$t} 
            size=64 
            maxlength=255 
            placeholder='{$h}' 
            value='{$h}'
            onchange='store_field_translation_hint(this)'
        />
        </label>
    </td>
    <td>
        <span hidden class=changed
        id=changed_new_translation_hint_{$t}
        title=Changed>&hellip;</span>
        <span hidden class=failed
        id=failed_new_translation_hint_{$t}
        title='Storing failed'>&otimes;</span>
        <span hidden class=succeeded
        id=succeeded_new_translation_hint_{$t}
        title='Stored successfully'>&radic;</span>
    </td>
    <td>
        <input type=hidden
        name=old_translation_{$t}
        id=old_translation_{$t}
        value='{$c}'
        /><input type=hidden
        name=old_translation_hint_{$t}
        id=old_translation_hint_{$t}
        value='{$h}'
        />
        <label 
        title='Remove this translation from this field'
        ><button type=button
        title='Remove this translation from this field'
        id=remove_field_translation_{$t}
        name=remove_field_translation_{$t}
        onclick='remove(this, "{$t}")'
        class=remove
        >X</button> Remove</label>
    </td>
</tr>
END;
    }
    echo "</tbody>";
    if (count($languages_missing_from_field_translations) > 0)
    {
        $add_language_options = '';
        foreach ($languages_missing_from_field_translations as $x) {
            $add_language_options .= '<option>' . addslashes($x['code'])
            . '</option>' . "\r\n\t";
        }
        echo <<<END
        <tfoot>
            <tr>
            <th><select id=add_translation_lang_pick
                name=add_translation_lang_pick>
                {$add_language_options}
                </select></th>
            <td><input type=text
                id=added_translation
                name=added_translation
                size=24
                maxlength=255
                value='' 
                placeholder='New translation for chosen language'
            /></td>
            <td>
                <span hidden class=changed
                id=changed_add_translation_lang
                title=Changed>&hellip;</span>
                <span hidden class=failed
                id=failed_add_translation_lang
                title='Storing failed'>&otimes;</span>
                <span hidden class=succeeded
                id=succeeded_add_translation_lang
                title='Stored successfully'>&radic;</span>
            </td>
            <td><input type=text
                id=added_hint
                name=added_hint
                size=64
                maxlength=255
                value='' 
                placeholder='New hint for chosen language'
            /></td>
            <td>
                <span hidden class=changed
                id=changed_add_hint_lang
                title=Changed>&hellip;</span>
                <span hidden class=failed
                id=failed_add_hint_lang
                title='Storing failed'>&otimes;</span>
                <span hidden class=succeeded
                id=succeeded_add_hint_lang
                title='Stored successfully'>&radic;</span>
            </td>
            <td><label><input type=submit 
                id=add_translation_lang
                name=add_translation_lang
                onclick='store_field_translation(this);'
                value='+'
                title='Add new translation and hint for chosen language'
                /> Add</label></td>
            </tr>
        </tfoot>
END;
    }
    echo "</table></fieldset></form>
</section>
<section>
<h3>Change Field Attributes</h3>
<p>Note: display sequence and hide-on-entry are set on each entry
    form separately.</p>
<form>";

    $xs = query(
        'select `a`.*
            from `attributes` as `a` 
            where `a`.`id` = ?', 
        's', 
        [$field_choice]
    );
        foreach ($xs as $x) {
            $id = $x['id'];
            $attrib_id     = htmlspecialchars($id, ENT_QUOTES);
            $data_type     = htmlspecialchars($x['data_type'], ENT_QUOTES);
            $min           = $x['min'];
            $max           = $x['max'];
            $default       = htmlspecialchars($x['default'], ENT_QUOTES);
            $iwo_value     = $x['is_write_once'];
            $is_write_once = (
                (1 == $iwo_value) 
                ? 'checked=checked' 
                : ''
            );
            $enum_list = '';
            $enum_mgr_hidden = 'hidden';
            if ($x['data_type'] == 'enum') {
                $enum_mgr_hidden = '';
            }
            echo <<<END
<fieldset>
<p><label for=field_identity>Field Code</label></p>
<p class=hint>The identity cannot be changed.</p>
<p>{$id}</p>
</fieldset>
<fieldset>
<p><label for=new_data_type>Data Type</label>
  <span hidden class=changed
  id=changed_new_data_type
  title=Changed>&hellip;</span>
  <span hidden class=failed
  id=failed_new_data_type
  title='Storing failed'>&otimes;</span>
  <span hidden class=succeeded
  id=succeeded_new_data_type
  title='Stored successfully'>&radic;</span>
</p>
<p class=hint>The data type is required. It determines how a field gets shown.</p>
<p><select id=new_data_type name=new_data_type 
    onchange="show_enum_mgr_if_needed();store(this);"
    >
    <optgroup label='Current choice:'>
        <option selected=selected>{$data_type}</option>
    </optgroup>
    <optgroup label='All choices:'>
       <option value=date>Date</option>
       <option value=email>E-mail Address</option>
       <option value=enum>Enumeration (list of predefined choices)</option>
       <option value=image>Image</option>
       <option value=integer>Whole Number</option>
       <option value=location>Location</option>
       <option value=longtext>Long Text (up to 16 pages of text)</option>
       <option value=percent>Percentage</option>
       <option value=shorttext>Short Text (up to 255 letters)</option>
       <option value=time>Time</option>
    </optgroup>
</select> <label title="Open the enumeration manager"><input {$enum_mgr_hidden} type=button 
    value="Edit Enumeration Values"
    id=data_type_enum_mgr
    name=data_type_enum_mgr
    popovertarget=data_type_enum_values
/></label>
    <input id=old_data_type name=old_data_type value="{$data_type}" type=hidden />
</p>
</fieldset>
<div {$enum_mgr_hidden} popover=auto id=data_type_enum_values>
    <fieldset>
    <legend>Edit Enumeration Values</legend>
    <p>Choices shown on entry forms for the field. The user is advised to choose from this list.</p>
    <table>
        <thead>
            <tr>
            <th>&nbsp;&nbsp;</th>
            <th>Code</th>
            <th>Language</th>
            <th>Translation</th>
            <th>&nbsp;</th>
            <tr>
        </thead>
        <tbody>
	    {$enumerations_for_show}
        </tbody>
        <tfoot>
            <tr>
            <td>
                <span hidden class=changed
                id=changed_new_enum_val
                title=Changed>&hellip;</span>
                <span hidden class=failed
                id=failed_new_enum_val
                title='Storing failed'>&otimes;</span>
                <span hidden class=succeeded
                id=succeeded_new_enum_val
                title='Stored successfully'>&radic;</span>
            </td>
            <td><label title="Code to store. Won't be shown on entry forms.">
                <input type=text 
                    id=new_enum_val
                    name=new_enum_val
                    minlength=4
                    maxlength=24
                    size=12
                /></label>
            </td>
            <td><label title="Language of the caption">
                <select  
                    id=new_enum_lang
                    name=new_enum_lang
                    size=1
                >
                {$languages_missing_from_enums_for_show}
                </select></label>
            </td>
            <td><label title="Language-specific translation, shown on entry forms.">
                <input type=text 
                    id=new_enum_caption
                    name=new_enum_caption
                    minlength=4
                    maxlength=256
                    size=48
                /></label>
            </td>
            <td><label title="Create a new value">
                <input type=submit
                    id=add_new_enum
                    name=add_new_enum
                    value="+"
                /> Add</label>
            </td>
            </tr>
        </tfoot>
    </table>
    </fieldset>
</div>
<fieldset>
<p><label for=new_min>Minimum</label>
  <span hidden class=changed
  id=changed_new_min
  title=Changed>&hellip;</span>
  <span hidden class=failed
  id=failed_new_min
  title='Storing failed'>&otimes;</span>
  <span hidden class=succeeded
  id=succeeded_new_min
  title='Stored successfully'>&radic;</span>
</p>
<p class=hint>The minimum value is required. For texts, this
    determines the least amount of characters a user has to enter.
    For numbers, this determines the smallest number allowed to be
    entered. The default value is 0 (zero).</p>
<p><input id=new_min name=new_min type=number size=6 value="{$min}"
    placeholder="0" minlength=1 maxlength=18 onchange=store(this)>
    <input id=old_min name=old_min value="{$min}" type=hidden />
</p>
</fieldset>
<fieldset>
<p><label for=new_max>Maximum</label>
  <span hidden class=changed
  id=changed_new_max
  title=Changed>&hellip;</span>
  <span hidden class=failed
  id=failed_new_max
  title='Storing failed'>&otimes;</span>
  <span hidden class=succeeded
  id=succeeded_new_max
  title='Stored successfully'>&radic;</span>
<p class=hint>The maximum value is optional. For texts, this
    determines the highest amount of characters a user has to
    enter. For numbers, this determines the highest number allowed
    to be entered. The default value depends on data type. If you 
    don't specify a maximum, one will be enforced by the data store,
    depending on data type.
</p>
<p><input id=new_max name=new_max type=number size=6 value="{$max}"
    placeholder="256" minlength=0 maxlength=18 onchange=store(this)>
    <input id=old_max name=old_max value="{$max}" type=hidden />
</p>
</fieldset>
<fieldset>
<p><label for=new_default>Default Value</label>
  <span hidden class=changed
  id=changed_new_default
  title=Changed>&hellip;</span>
  <span hidden class=failed
  id=failed_new_default
  title='Storing failed'>&otimes;</span>
  <span hidden class=succeeded
  id=succeeded_new_default
  title='Stored successfully'>&radic;</span>
<p class=hint>Default Value is optional. This sets a value that
    will be assigned automatically, if the user chooses to enter
    nothing.</p>
<p><input id=new_default name=new_default type=text size=60 size=24
    value="{$default}" placeholder="Default Value" onchange=store(this)>
    <input id=old_default name=old_default value="{$default}" type=hidden />
</p>
</fieldset>
<fieldset>
<p><label for=new_is_write_once>Write-Once</label>
  <span hidden class=changed
  id=changed_new_is_write_once
  title=Changed>&hellip;</span>
  <span hidden class=failed
  id=failed_new_is_write_once
  title='Storing failed'>&otimes;</span>
  <span hidden class=succeeded
  id=succeeded_new_is_write_once
  title='Stored successfully'>&radic;</span>
<p class=hint>Mark the Write-Once checkbox to determine that the
   field's value can be entered, but not changed.</p>
<p><input id=new_is_write_once name=new_is_write_once 
    type=checkbox {$is_write_once} onclick=store(this) >
    <input id=old_is_write_once name=old_is_write_once 
      value="{$iwo_value}" type=hidden />
</p>
</fieldset>
END;
        } /* end for-each field attrib */
    }

?>
    </form>
</body>
</html>
