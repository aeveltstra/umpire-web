<?php 
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$new_case_id = session_recall('new_case_id');
$nonce = session_make_and_remember_nonce('subscribe');
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Entry registered successfully - Umpire</title>
<meta name=description content="Do you want to subscribe to updates?"/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>

<h1>Entry registered successfully - Umpire</h1>
<?php if (!empty($new_case_id)) { ?>
    <p>This is your case ID: <?php echo $new_case_id; ?>.</p>
    <p>Please keep this with your records. You will need it if you want to talk to us about it.</p>
    <h2>Do you want to receive updates?</h2>
    <form action="/umpire/subscribe/" method=post><fieldset><legend>Yes, please subscribe!</legend>
        <p>Note: subscribing lets us read your email address.</p>
        <p><label><input type=checkbox name=agree/> I have read and agree</label> to the <a href="/umpire/subscribe/terms/">terms and conditions</a>.</p>
        <p><label for=email>Send notifications to this email address:</label></p>
        <p><input type=email size=60 maxlength=256 id=email name=email /></p>
        <p><label><input type=submit value=Subscribe /></label></p>
        <input type=hidden hidden name=case value='<?php echo $new_case_id; ?>' />
        <input type=hidden hidden name=nonce value='<?php echo $nonce; ?>'/>
    </fieldset></form>
    <form action="/umpire/" method=get><fieldset><legend>No, thanks!</legend>
        <p><label><input type=submit value="I'm fine with not receiving updates."/></label></p>
    </fieldset></form>
<?php } ?>
<p>Where to next? Would you like to return to <a href="/umpire/">the home page</a>, or <a href="/umpire/forms/">submit a new case</a>? Or maybe you want to view our <a href="/umpire/view/statistics/">statistics</a>?</p>
</body>
</html>
