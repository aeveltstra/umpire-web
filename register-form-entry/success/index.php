<?php 
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$new_case_id = session_recall('new_case_id');
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Entry registered successfully - Umpire</title>
<meta name=description content="Where to next?"/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../../c/main.css"/>
</head>
<body>

<h1>Entry registered successfully - Umpire</h1>
<?php if (!empty($new_case_id)) { ?>
    <p>This is your case ID: <?php echo $new_case_id; ?>.</p>
    <p>Please keep this with your records. You will need it if you want to talk to us about it.</p>
<?php } ?>
<h2>Where to next?</h2>
<p>Would you like to return to <a href="/umpire/">the home page</a>, or <a href="/umpire/forms/">submit a new case</a>? Or maybe you want to view our <a href="/umpire/view/statistics/">statistics</a>?</p>
</body>
</html>
