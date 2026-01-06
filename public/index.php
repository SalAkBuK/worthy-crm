<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap application
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../init.php';

use App\Helpers\Router;
use App\Controllers\AuthController;
use App\Controllers\AdminLeadsController;
use App\Controllers\AgentLeadsController;
use App\Controllers\CeoController;

/*
|--------------------------------------------------------------------------
| Create router
|--------------------------------------------------------------------------
*/
$router = new Router();

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/
$auth  = new AuthController();
$admin = new AdminLeadsController();
$agent = new AgentLeadsController();
$ceo   = new CeoController();

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

// Home (role-based redirect)
$router->get('/', function () {
    if (function_exists('current_user') && current_user()) {
        $role = current_user()['role'] ?? null;

        if ($role === 'ADMIN') redirect('/admin/leads');
        if ($role === 'AGENT') redirect('/agent/leads');
        if ($role === 'CEO')   redirect('/ceo/dashboard');
    }

    redirect('/login');
});

// Auth
$router->get('/login', function () use ($auth) { $auth->showLogin(); });
$router->post('/login', function () use ($auth) { $auth->login(); });
$router->get('/logout', function () use ($auth) { $auth->logout(); });

// Admin
$router->get('/admin/leads', function () use ($admin) { $admin->index(); });
$router->post('/admin/leads', function () use ($admin) { $admin->storeBulk(); });
$router->post('/admin/leads/import', function () use ($admin) { $admin->importCsv(); });
$router->post('/admin/leads/assign-bulk', function () use ($admin) { $admin->assignBulk(); });
$router->get('/admin/lead', function () use ($admin) { $admin->show(); });
$router->get('/admin/leads/export', function () use ($admin) { $admin->exportLeads(); });
$router->post('/admin/lead/reopen', function () use ($admin) { $admin->reopen(); });

$router->get('/admin/agents', function () use ($admin) { $admin->agents(); });
$router->get('/admin/agent', function () use ($admin) { $admin->agentShow(); });
$router->post('/admin/agent/update', function () use ($admin) { $admin->agentUpdate(); });
$router->post('/admin/agent/delete', function () use ($admin) { $admin->agentDelete(); });
$router->get('/admin/agent/add', function () use ($admin) { $admin->agentAdd(); });
$router->post('/admin/agent/create', function () use ($admin) { $admin->agentCreate(); });
$router->get('/admin/agents/export', function () use ($admin) { $admin->exportAgents(); });
$router->post('/admin/agents/bulk', function () use ($admin) { $admin->agentsBulk(); });
$router->post('/admin/agents/bulk-reset', function () use ($admin) { $admin->agentsBulkResetPassword(); });
$router->post('/admin/agent/reset-password', function () use ($admin) { $admin->agentResetPassword(); });

// Agent
$router->get('/agent/leads', function () use ($agent) { $agent->index(); });
$router->get('/agent/lead', function () use ($agent) { $agent->openLead(); });
$router->post('/agent/followup', function () use ($agent) { $agent->storeFollowup(); });

// CEO
$router->get('/ceo/dashboard', function () use ($ceo) { $ceo->dashboard(); });
$router->get('/ceo/summary', function () use ($ceo) { $ceo->summary(); });
$router->get('/ceo/agent', function () use ($ceo) { $ceo->agentPerformance(); });
$router->get('/ceo/export', function () use ($ceo) { $ceo->exportCsv(); });

/*
|--------------------------------------------------------------------------
| Run router (MUST BE LAST)
|--------------------------------------------------------------------------
*/
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
