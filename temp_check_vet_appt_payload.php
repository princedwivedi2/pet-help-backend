<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VetProfile;
use App\Services\AppointmentService;

$vet = VetProfile::where('user_id', 20)->first();
if (!$vet) {
    echo "No vet profile for user_id=20\n";
    exit;
}

$service = app(AppointmentService::class);
$page = $service->getVetAppointments($vet->id, null, null, 15);
$items = $page->items();

echo 'count=' . count($items) . PHP_EOL;
if (!empty($items)) {
    $first = $items[0]->toArray();
    echo 'has_uuid=' . (array_key_exists('uuid', $first) ? 'yes' : 'no') . PHP_EOL;
    echo 'uuid=' . ($first['uuid'] ?? 'NULL') . PHP_EOL;
    echo 'id=' . ($first['id'] ?? 'NULL') . PHP_EOL;
    echo 'status=' . ($first['status'] ?? 'NULL') . PHP_EOL;
}
