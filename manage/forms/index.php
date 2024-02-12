<?php
/**
 * Manage Entry Forms for Umpire
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.24.0211.2314
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
$form_nonce = session_make_and_remember_nonce('manage_entry_form');

$form_choice = '';
if (isset($_GET['id'])) {
    $form_choice = $_GET['id'];
}
$is_form_known = false;
$form_caption = '';
$form_redirect = '';
if (!empty($form_choice)) {
    $get_form_exists = query(
        'select `id`, `url_after_entry` from `forms` where `id` = ?',
        's',
        [$form_choice]
    );
    $amount = count($get_form_exists);
    if ($amount > 0) {
        $is_form_known = true;
        if (isset($get_form_exists[0][`url_after_entry`])) {
            $form_redirect = $get_form_exists[0][`url_after_entry`];
        }
    }
    $form_captions = query(
        'select `form`, `caption`, `language` from `form_caption_translations` where `form` = ?',
        's',
        [$form_choice]
    );
    $amount = count($form_captions);
    if ($amount > 0) {
        for ($i = 0; $i < $amount; $i+=1) {
            if ('en' === $form_captions[$i]['language']) {
                $form_caption = $form_captions[$i]['caption'];
            }
        }
        if (empty($form_caption)) {
            $form_caption = $form_captions[0]['caption'];
        }
    }
}


function show_enums() {
    $xs = db_read_enumerations('en');
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
                $n = '<datalist id="list_' . addslashes($last_id) . '"><label>Or choose: <select>' . $m . '</select></label></datalist>';
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

$form_id_for_show = htmlspecialchars($form_choice, ENT_QUOTES);
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
function show_fail(input_id, response_status) {
    "use strict";
    if (!!input_id) {
        hide_success(input_id);
        hide_changed(input_id);
        const notice = document.getElementById("failed_" + input_id);
        if (!!notice) {
            notice.hidden = false;
        }
    }
}
function store_form_caption(input, old_caption) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input) {
        const id = input.id;
        if (!!id) {
            const xs = id.split('_');
            if (xs.length) {
                const x = xs[xs.length - 1];
                const fd = new FormData();
                fd.append('form_id', '<?php echo $form_id_for_show; ?>');
                fd.append('language', x);
                fd.append('old_caption', old_caption);
                fd.append('new_caption', input.value);
                fd.append('nonce', '<?php echo $form_nonce; ?>');
                fetch(
                    './store_form_caption.php',
                    {
                        method: "POST",
                        body: fd,
                        cache: "no-cache",
                        mode: "same-origin",
                        credentials: "omit"
                    }
                ).then((response) => {
                    if (response.ok) {
                        show_success(id);
                    } else {
                        show_fail(id, response.status);
                    }
                }).catch(alert);
            }
        }
    }
    return false;
}
function store_form_redirect(input, old_value) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input) {
        const id = input.id;
        if (!!id) {
            const fd = new FormData();
            fd.append('form_id', '<?php echo $form_id_for_show; ?>');
            fd.append('old_value', old_value);
            fd.append('new_value', input.value);
            fd.append('nonce', '<?php echo $form_nonce; ?>');
            fetch(
                './store_form_redirect.php',
                {
                    method: "POST",
                    body: fd,
                    cache: "no-cache",
                    mode: "same-origin",
                    credentials: "omit"
                }
            ).then((response) => {
                if (response.ok) {
                    show_success(id);
                } else {
                    show_fail(id, response.status);
                }
            }).catch(alert);
        }
    }
    return false;
}
function store(input, old_value) {
    "use strict";
    const evt = window.event;
    if (evt && evt.preventDefault) {
        evt.preventDefault();
    }
    if (input) {
        const id = input.id;
        if (!!id) {
            const fd = new FormData();
            fd.append('form_id', '<?php echo $form_id_for_show; ?>');
            fd.append('attribute', id);
            fd.append('old_value', old_value);
            fd.append('new_value', input.value);
            fd.append('nonce', '<?php echo $form_nonce; ?>');
            fetch(
                './store_form_attribute.php',
                {
                    method: "POST",
                    body: fd,
                    cache: "no-cache",
                    mode: "same-origin",
                    credentials: "omit"
                }
            ).then((response) => {
                if (response.ok) {
                    show_success(id);
                } else {
                    show_fail(id, response.status);
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
        $form_caption_for_show = htmlspecialchars($form_caption, ENT_QUOTES);
        $form_redirect_for_input = htmlspecialchars($form_redirect, ENT_QUOTES);
        echo "
    <h2>Form being edited: {$form_caption_for_show}.</h2>
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
                            <span hidden class=changed id=changed_new_caption_{$t} title='Changed'>&hellip;</span>
                            <span hidden class=failed id=failed_new_caption_{$t} title='Storing failed'>&otimes;</span>
                            <span hidden class=succeeded id=succeeded_new_caption_{$t} title='Stored successfully'>&radic;</span>
                        </td>
                        <th>{$t}</th>
                        <td>
                            <label for=new_caption_{$t}>
                            <input type=text name=new_caption_{$t} id=new_caption_{$t} 
                                size=60 
                                maxlength=256 
                                placeholder='{$c}' 
                                value='{$c}' 
                                onchange='store_form_caption(this, \"{$c}\")' />
                            </label>
                        </td>
                    </tr>
            ";
        }
        echo "
        </tbody></table></fieldset></form>
    </section>
    <section>
        <h3>Redirect After Entry</h3>
        <form><fieldset><legend>Which web page to show after successful form submission?</legend>
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
                        <span hidden class=changed id=changed_new_redirect title='Changed'>&hellip;</span>
                        <span hidden class=failed id=failed_new_redirect title='Storing failed'>&otimes;</span>
                        <span hidden class=succeeded id=succeeded_new_redirect title='Stored successfully'>&radic;</span>
                    </td>
                    <td>
                        <label for=new_redirect>
                        <input type=text name=new_redirect id=new_redirect 
                            size=60 
                            maxlength=512 
                            placeholder='/umpire/subscribe/new/' 
                            value='{$form_redirect_for_input}' 
                            onchange='store_form_redirect(this, \"{$form_redirect_for_input}\")' 
                        />
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        </fieldset></form>
    </section>
    <section>
    <form id='attributes_for_form'>";

    show_enums();

    echo "<fieldset><legend>These attributes are assigned currently:</legend>

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
            'select `a`.*, `fa`.`display_sequence`, `fa`.`hide_on_entry` from `form_attributes` as `fa` inner join `attributes` as `a` on `a`.`id` = `fa`.`attribute` where `fa`.`form` = ? order by `fa`.`display_sequence`', 
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
        foreach($dts as $dt) {
            $dt_options .= "<option>{$dt}</option>";
        }
        foreach($xs as $x) {
            $display_seq   = $x['display_sequence'];
            $field_id      = htmlspecialchars($x['id'],        ENT_QUOTES);
            $data_type     = htmlspecialchars($x['data_type'], ENT_QUOTES);
            $min           = $x['min'];
            $max           = $x['max'];
            $default       = htmlspecialchars($x['default'],   ENT_QUOTES);
            $is_write_once = ((1 == $x['is_write_once']) ? 'checked=checked' : '');
            $hide_on_entry = ((1 == $x['hide_on_entry']) ? 'checked=checked' : '');
            $enum_list = '';
            if ($x['data_type'] == 'enum') {
                $enum_list = 'role="listbox" aria-required="true" aria-autocomplete="list" aria-controls="list_' . $field_id . '" list="list_' . $field_id . '"';
            }
            echo "
        <tr>
            <td>
                <span hidden class=changed id=changed_{$field_id} title='Changed'>&hellip;</span>
                <span hidden class=failed id=failed_{$field_id} title='Storing failed'>&otimes;</span>
                <span hidden class=succeeded id=succeeded_{$field_id} title='Stored successfully'>&radic;</span>
            </td>
            <th>{$display_seq}</th>
            <td>{$field_id}</td>
            <td>
                <select name=data_type id=data_type onchange='store(this, \"{$field_id}\", \"{$data_type}\")'>
                    <optgroup label='Currently Stored'>
                    <option selected=selected>{$data_type}</option>
                    </optgroup>
                    <optgroup label='Options'>
                    {$dt_options}
                    </optgroup>
                </select>
            </td>
            <td><input type=number name=min id=min onchange='store(this, \"{$field_id}\", \"{$min}\")' value='{$min}'/></td>
            <td><input type=number name=max id=max onchange='store(this, \"{$field_id}\", \"{$max}\")' value='{$max}'/></td>
            <td><input type=text name=default id=default onchange='store(this, \"{$field_id}\", \"{$default}\")' value='{$default}' {$enum_list} /></td>
            <td><input type=checkbox name=is_write_once id=is_write_once {$is_write_once} onchange='store(this, \"{$field_id}\", \"{$x['is_write_once']}\")' /></td>
            <td><input type=checkbox name=hide_on_entry id=hide_on_entry {$hide_on_entry} onchange='store(this, \"{$field_id}\", \"{$x['hide_on_entry']}\")' /></td>
        </tr>
    ";
        }
    }
?>
    </tbody></table></fieldset></form>
</body>
</html>
