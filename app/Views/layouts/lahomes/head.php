<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../Helpers/functions.php';
$title = $title ?? 'Dashboard';
?>
<meta charset="utf-8">
<title><?= e($title) ?> | Lahomes - Agent Performance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Agent performance tracking dashboard.">
<meta name="author" content="Worthy Square">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<link rel="shortcut icon" href="<?= e(url('assets/lahomes/images/favicon.ico')) ?>">

<link href="<?= e(url('assets/lahomes/css/vendor.min.css')) ?>" rel="stylesheet" type="text/css">
<link href="<?= e(url('assets/lahomes/css/icons.min.css')) ?>" rel="stylesheet" type="text/css">
<link href="<?= e(url('assets/lahomes/css/app.min.css')) ?>" rel="stylesheet" type="text/css">

<script src="<?= e(url('assets/lahomes/js/config.min.js')) ?>"></script>
