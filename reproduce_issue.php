<?php
require_once __DIR__ . '/init.php';
use App\Models\Notification;

echo "Attempting to call Notification::unreadCount...\n";

try {
    // Mimic the call in topbar.php: App\Models\Notification::unreadCount(1, NULL)
    // Note: 1 is likely a User ID. Make sure User 1 exists or the query handles it.
    // The error happens at DB::conn(), so the query itself hasn't run yet.
    Notification::unreadCount(1, null);
    echo "Success! No error thrown.\n";
} catch (Throwable $e) {
    echo "CAUGHT EXCEPTION:\n";
    echo $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
