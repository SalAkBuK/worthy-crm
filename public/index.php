<?php
declare(strict_types=1);

use App\Helpers\Router;

require_once __DIR__ . '/../init.php';

$router = new Router();

$auth = new App\Controllers\AuthController();
$admin = new App\Controllers\AdminLeadsController();
$agent = new App\Controllers\AgentLeadsController();
$ceo = new App\Controllers\CeoController();

$router->get('/', function() {
  if (current_user()) {
    $role = current_user()['role'];
    if ($role === 'ADMIN') redirect('admin/leads');
    if ($role === 'AGENT') redirect('agent/leads');
    if ($role === 'CEO') redirect('ceo/dashboard');
  }
  redirect('login');
});

$router->get('/login', fn() => $auth->showLogin());
$router->post('/login', fn() => $auth->login());
$router->get('/logout', fn() => $auth->logout());

// Admin
$router->get('/admin/leads', fn() => $admin->index());
$router->post('/admin/leads', fn() => $admin->storeBulk());
$router->get('/admin/lead', fn() => $admin->show());
$router->get('/admin/leads/export', fn() => $admin->exportLeads());
$router->post('/admin/lead/reopen', fn() => $admin->reopen());
$router->get('/admin/agents', fn() => $admin->agents());
$router->get('/admin/agent', fn() => $admin->agentShow());
$router->post('/admin/agent/update', fn() => $admin->agentUpdate());
$router->post('/admin/agent/delete', fn() => $admin->agentDelete());
$router->get('/admin/agent/add', fn() => $admin->agentAdd());
$router->post('/admin/agent/create', fn() => $admin->agentCreate());
$router->get('/admin/agents/export', fn() => $admin->exportAgents());
$router->post('/admin/agents/bulk', fn() => $admin->agentsBulk());
$router->post('/admin/agents/bulk-reset', fn() => $admin->agentsBulkResetPassword());
$router->post('/admin/agent/reset-password', fn() => $admin->agentResetPassword());

// Agent
$router->get('/agent/leads', fn() => $agent->index());
$router->get('/agent/lead', fn() => $agent->openLead());
$router->post('/agent/followup', fn() => $agent->storeFollowup());

// CEO
$router->get('/ceo/dashboard', fn() => $ceo->dashboard());
$router->get('/ceo/summary', fn() => $ceo->summary());
$router->get('/ceo/agent', fn() => $ceo->agentPerformance());
$router->get('/ceo/export', fn() => $ceo->exportCsv());

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
