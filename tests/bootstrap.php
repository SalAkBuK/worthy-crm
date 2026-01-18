<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

$root = dirname(__DIR__);
require $root . '/app/Helpers/functions.php';
require $root . '/app/Helpers/DB.php';
require $root . '/app/Models/Listing.php';
require $root . '/app/Models/Lead.php';
