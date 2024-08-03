<?php
/**
 * Manage Entry Forms for Umpire
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.508.2054
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

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
$form_nonce = session_make_and_remember_nonce('manage_entry_form');

$form_choice = '';
if (isset($_GET['id'])) {
    $form_choice = $_GET['id'];
}
$is_form_known = false;
$form_caption = '';
$form_redirect = '';
$languages_missing_from_form_captions = [];
if (!empty($form_choice)) {
    $get_form_exists = query(
        'select `id`, `url_after_entry` 
            from `forms` 
            where `id` = ?',
        's',
        [$form_choice]
    );
    $is_form_known = (count($get_form_exists) > 0);
    if ($is_form_known) {
        $form_record_has_url_after_entry = isset(
            $get_form_exists[0][
            'url_after_entry'
            ]
        );
        if ($form_record_has_url_after_entry) {
            $form_redirect = $get_form_exists[0]['url_after_entry'];
        }
    }
    $form_captions = query(
        'select `form`, `caption`, `language` 
            from `form_caption_translations` 
            where `form` = ?
            order by 3',
        's',
        [$form_choice]
    );
    $amount = count($form_captions);
    $form_has_captions = ($amount > 0);
    if ($form_has_captions) {
        for ($i = 0; $i < $amount; $i+=1) {
            if ('en' === $form_captions[$i]['language']) {
                $form_caption = $form_captions[$i]['caption'];
            }
        }
        if (empty($form_caption)) {
            $form_caption = $form_captions[0]['caption'];
        }
    }
    $languages_missing_from_form_captions = query(
        'select `code` from `language_codes`
            where not exists ( 
                select 1 from `form_caption_translations` 
                where `language` = `code`
                and form = ?
            )
            order by 1',
        's',
        [$form_choice]
    );
}

$form_id_for_show = htmlspecialchars($form_choice, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Manage Umpire Entry Forms</title>
<meta name=description content="Determine which form to configure."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../c/main.css"/>
<link rel=stylesheet href="../../c/manage-form.css?2.24.527.1609"/>
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
 * Attempts to replace the former old field value 
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
    const x = document.getElementById('add_caption_lang_pick');
    if (!!x && !!x.value) {
        return x.value;
    }
    return '';
}

function get_added_caption() {
    "use strict";
    const x = document.getElementById('added_caption');
    if (!!x && !!x.value) {
        return x.value;
    }
    return '';
}

function store_form_caption(input) {
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
                fd.append('form_id', '<?php echo addslashes($form_id_for_show); ?>');
                fd.append('language', x);
                if ('new' == xs[0]) {
                    fd.append('new_caption', input.value);
                    fd.append('old_caption', get_old_value(id));
                } else if ('add' == xs[0]) {
                    fd.append('new_caption', get_added_caption());
                }
                fd.append('nonce', '<?php echo addslashes($form_nonce); ?>');
                fetch(
                    './store_form_caption.php?t=' + Date.now(),
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
function store_form_redirect(input) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input) {
        const id = input.id;
        if (!!id) {
            show_changed(id);
            const fd = new FormData();
            fd.append('form_id', '<?php echo $form_id_for_show; ?>');
            fd.append('old_redirect', get_old_value(id));
            fd.append('new_redirect', input.value);
            fd.append('nonce', '<?php echo $form_nonce; ?>');
            fetch(
                './store_form_redirect.php?t=' + Date.now(),
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
                    ).catch(alert);
                }
            }).catch(alert);
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
            fd.append('form_id', '<?php echo $form_id_for_show; ?>');
            fd.append('attribute', attrib_id);
            fd.append('property', property);
            fd.append('old_value', get_old_value(property));
            fd.append('new_value', input.value);
            fd.append('nonce', '<?php echo $form_nonce; ?>');
            fetch(
                './store_form_attribute.php',
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
    <h1>Manage Umpire Entry Forms</h1>
<?php
if (!$is_form_known) {
    echo '<h2>Choose which form to edit:</h2><ul>';
    $rows = query(
        'select `form`, `caption` 
                from `form_caption_translations` 
                where `language` = \'en\''
    );
    foreach ($rows as $row) {
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
    $form_caption_for_show = htmlspecialchars($form_caption, ENT_QUOTES);
    $form_redirect_for_input = htmlspecialchars($form_redirect, ENT_QUOTES);
    echo "
    <h2>Form being edited: <q>{$form_caption_for_show}</q>.</h2>
    <p>Note: changes happen immediately after leaving a field.</p>
    <section>
        <h3>Change Form Captions</h3>
        <form><fieldset><legend>Each language has its own caption:</legend>
        <table>
            <thead>
                <tr>
                    <th>&nbsp;&nbsp;</th>
                    <th>Language</th>
                    <th>Translation</th>
                </tr>
            </thead>
            <tbody>";
    foreach ($form_captions as $translation) {
        $c = htmlspecialchars($translation['caption'], ENT_QUOTES);
        $t = htmlspecialchars($translation['language'], ENT_QUOTES);
        echo "
<tr>
    <td>
        <span hidden class=changed 
        id=changed_new_caption_{$t} 
        title='Changed'>&hellip;</span>
        <span hidden class=failed 
        id=failed_new_caption_{$t} 
        title='Storing failed'>&otimes;</span>
        <span hidden class=succeeded 
        id=succeeded_new_caption_{$t} 
        title='Stored successfully'>&radic;</span>
    </td>
    <th>{$t}</th>
    <td>
        <label for=new_caption_{$t}>
        <input type=text 
            name=new_caption_{$t} 
            id=new_caption_{$t} 
            size=60 
            maxlength=256 
            placeholder='{$c}' 
            value='{$c}' 
            onchange='store_form_caption(this)' />
        </label>
    </td>
    <td><input type=hidden 
        name=old_caption_{$t} 
        id=old_caption_{$t} 
        value=\"{$c}\"
    /></td>
</tr>
            ";
    }
    echo "</tbody>";
    if (count($languages_missing_from_form_captions) > 0) {
        $add_language_options = '';
        foreach ($languages_missing_from_form_captions as $x) {
            $add_language_options .= '<option>' . addslashes($x['code'])
            . '</option>' . "\r\n\t";
        }
        echo "<tfoot>
            <td>
                <span hidden class=changed 
                id=changed_add_caption_lang 
                title='Changed'>&hellip;</span>
                <span hidden class=failed 
                id=failed_add_caption_lang
                title='Adding failed'>&otimes;</span>
                <span hidden class=succeeded 
                id=succeeded_add_caption_lang
                title='Added successfully'>&radic;</span>
            </td>
            <th><select id=add_caption_lang_pick
                name=add_caption_lang_pick>
                {$add_language_options}
                </select></th>
            <td><input type=text
                id=added_caption
                name=added_caption
                size=60
                maxsize=256
                value='' 
                placeholder='New caption for chosen language'
            /></td>
            <td><label><input type=submit 
                id=add_caption_lang
                name=add_caption_lang
                onclick='store_form_caption(this);'
                value='+'
                title='Add new caption for chosen language'
                />&nbsp;Add</label></td>
        </tfoot>
        ";
    }
    echo "</table></fieldset></form>
</section>
<section>
    <h3>Redirect After Entry</h3>
    <p>Leave empty to show a generic receipt.</p>
    <p>Make sure the page to show actually exists.</p>
    <form><fieldset><legend>Which web page to show after successful 
        form submission?</legend>
    <table>
        <thead>
            <tr>
                <th>&nbsp;&nbsp;</th>
                <th>Web address</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <span hidden class=changed 
                    id=changed_new_redirect 
                    title='Changed'>&hellip;</span>
                    <span hidden class=failed 
                    id=failed_new_redirect 
                    title='Storing failed'>&otimes;</span>
                    <span hidden class=succeeded 
                    id=succeeded_new_redirect 
                    title='Stored successfully'>&radic;</span>
                </td>
                <td>
                    <label for=new_redirect>
                    <input type=text 
                        name=new_redirect 
                        id=new_redirect 
                        size=60 
                        maxlength=512 
                        placeholder='/umpire/subscribe/new/' 
                        value='{$form_redirect_for_input}' 
                        onchange='store_form_redirect(this)' 
                    />
                    </label>
                    <input type=hidden 
                    name=old_redirect 
                    id=old_redirect 
                    value=\"{$form_redirect_for_input}\"/>
                </td>
            </tr>
        </tbody>
    </table>
    </fieldset></form>
</section>
<section>
<h3>Change Form Fields</h3>
<p>Note: fields are shared among forms. Changing one will 
change it on all forms. The exception is Display Sequence: that is 
applied to each form separately.</p>
<fieldset><legend>These fields are assigned currently.</legend>
<table>
<thead>
    <tr>
        <th>&nbsp;&nbsp;</th>
        <th>Display Sequence</th>
        <th>Identity</th>
        <th>Data Type</th>
        <th>Minimum</th>
        <th>Maximum</th>
        <th>Default Value</th>
        <th>Write Once</th>
        <th>Hide on Entry</th>
    </tr>
</thead>
<tbody>
            ";
    $xs = query(
        'select `a`.*, `fa`.`display_sequence`, `fa`.`hide_on_entry` 
                from `form_attributes` as `fa` 
                inner join `attributes` as `a` 
                on `a`.`id` = `fa`.`attribute` 
                where `fa`.`form` = ? 
                order by `fa`.`display_sequence`', 
        's', 
        [$form_choice]
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
        $display_seq   = $x['display_sequence'];
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
        $hide_on_entry = (
            (1 == $x['hide_on_entry']) 
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
            <th><input type=number
                name=new_display_seq_{$attrib_id}
                id=new_display_seq_{$attrib_id}
                onchange='store(this, \"{$attrib_id}\");'
                value=\"{$display_seq}\"
            /><input type=hidden
                name=old_display_seq_{$attrib_id}
                id=old_display_seq_{$attrib_id}
                value=\"{$display_seq}\"
            /></th>
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
            <td><input type=checkbox 
                name=new_hide_on_entry_{$attrib_id} 
                id=new_hide_on_entry_{$attrib_id} 
                {$hide_on_entry} 
                onchange='store(this, \"{$attrib_id}\")' 
            /><input type=hidden 
                name=old_hide_on_entry_{$attrib_id} 
                id=old_hide_on_entry_{$attrib_id} 
                {$hide_on_entry} 
            /></td>
        </tr>
    ";
    }
}
?>
    </tbody></table></fieldset>
</body>
</html>
