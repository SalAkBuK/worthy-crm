<?php
declare(strict_types=1);

use App\Helpers\Env;
use App\Models\User;

require_once __DIR__ . '/app/Helpers/functions.php';
require_once __DIR__ . '/app/Helpers/Env.php';
require_once __DIR__ . '/app/Helpers/Router.php';
require_once __DIR__ . '/app/Helpers/DB.php';
require_once __DIR__ . '/app/Helpers/Logger.php';
require_once __DIR__ . '/app/Helpers/View.php';

// Models
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/LoginAttempt.php';
require_once __DIR__ . '/app/Models/AuditLog.php';
require_once __DIR__ . '/app/Models/Lead.php';
require_once __DIR__ . '/app/Models/Followup.php';

// Controllers
require_once __DIR__ . '/app/Controllers/BaseController.php';
require_once __DIR__ . '/app/Controllers/AuthController.php';
require_once __DIR__ . '/app/Controllers/AdminLeadsController.php';
require_once __DIR__ . '/app/Controllers/AgentLeadsController.php';
require_once __DIR__ . '/app/Controllers/CeoController.php';

Env::load(__DIR__ . '/.env');

session_start([
  'cookie_httponly' => true,
  'use_strict_mode' => true,
]);

// Auto-seed users on first run (if tables exist)
try { User::ensureSeeded(); } catch (Throwable $e) { /* ignore seed issues if schema not yet imported */ }
