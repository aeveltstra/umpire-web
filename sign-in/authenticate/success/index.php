<?php
/**
 * Conveys that the user signed in successfully, and offers next
 * actions.
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.711.2152
 */
declare(strict_types=1);

/* DB Utils contains functions to retrieve information from the DB. */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Authenticated Successfully - Umpire</title>
<meta name=description content="Welcome back!" />
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../../c/main.css"/>
</head>
<body>

<h1>Authenticated Successfully - Umpire</h1>
<h2>Welcome back! Here's some options:</h2>
<ul>
    <li><a href="<?php echo $app_url; ?>/forms/missing/">Enter a new missing person's case</a>;</li>
    <li><a href="<?php echo $app_url; ?>/forms/deceased/">Enter a new deceased person's case</a>;</li>
    <li><a href="<?php echo $app_url; ?>/view/forms/">View which forms are available</a>;</li>
    <li><a href="<?php echo $app_url; ?>/view/statistics/">View statistics about what has been gathered thus far</a>.</li>
    <li><a href="<?php echo $app_url; ?>/manage/forms/">Manage forms, form fields, etc.</a>.</li>
</ul>
</body>
</html>
