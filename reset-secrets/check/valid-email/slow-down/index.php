<?php
declare(strict_types=1);
/**
 * Notify user that they're requesting reset keys too often than
 * seems appropriate.
 * 
 * PHP Version 7.5.3.
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.707.1527
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/config.php';

?><!DOCTYPE html>
<html lang="en" encoding="utf-8">
<head><meta charset="utf-8" />
<title>Slow Down - Credentials Reset - Umpire</title>
<meta name=description content="Requesting a reset key too often."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="../../../../c/main.css"/>
</head>
<body>
<h1>Is everything OK? - Umpire</h1>
<h2>You seem to be doing requesting a reset key too often.</h2>
<p>For assistance, please reach out to the support team at 
<?php 
    echo $support_email;
?>
.</p>
</body>
</html>
