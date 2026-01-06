<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Load helpers FIRST (no classes yet)
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/app/Helpers/functions.php';

/*
|--------------------------------------------------------------------------
| Load core classes (Helpers)
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/app/Helpers/Env.php';
require_once __DIR__ . '/app/Helpers/DB.php';
require_once __DIR__ . '/app/Helpers/Logger.php';
require_once __DIR__ . '/app/Helpers/View.php';
require_once __DIR__ . '/app/Helpers/Router.php';

/*
|--------------------------------------------------------------------------
| Load Models
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/LoginAttempt.php';
require_once __DIR__ . '/app/Models/AuditLog.php';
require_once __DIR__ . '/app/Models/Lead.php';
require_once __DIR__ . '/app/Models/Followup.php';

/*
|--------------------------------------------------------------------------
| Load Controllers
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/app/Controllers/BaseController.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/AdminLeadsController.php';
require_once __DIR__ . '/app/Controllers/AgentLeadsController.php';
require_once __DIR__ . '/app/Controllers/CeoController.php';

/*
|--------------------------------------------------------------------------
| Bootstrap app
|--------------------------------------------------------------------------
*/
use App\Helpers\Env;

// Load environment
Env::load(__DIR__ . '/.env');

// Start session safely
session_start([
    'cookie_httponly' => true,
    'use_strict_mode' => true,
]);
