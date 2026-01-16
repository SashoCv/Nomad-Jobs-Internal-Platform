<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\City;
use App\Models\Country;

echo "Starting City-Country Import...\n";

// Get Countries
$bulgaria = Country::where('name', 'България')->orWhere('code', 'BGR')->first();
$macedonia = Country::where('name', 'Северна Македония')->orWhere('code', 'MKD')->first();

if (!$bulgaria || !$macedonia) {
    echo "Error: Could not find Bulgaria or North Macedonia in countries table.\n";
    exit(1);
}

echo "Found Bulgaria (ID: {$bulgaria->id}) and North Macedonia (ID: {$macedonia->id})\n";

// Process Cities from dump data
// Based on the provided dump:
// IDs 1-178 are Bulgarian cities
// IDs 179-212 are Macedonian cities

$cities = City::all();
$updatedCount = 0;

foreach ($cities as $city) {
    $countryId = null;

    // Logic based on the dump data ranges
    if ($city->id >= 1 && $city->id <= 178) {
        $countryId = $bulgaria->id;
    } elseif ($city->id >= 179 && $city->id <= 212) {
        $countryId = $macedonia->id;
    }

    if ($countryId) {
        // Only update if country_id is missing or different
        if ($city->country_id !== $countryId) {
            $city->country_id = $countryId;
            $city->save();
            echo "Updated {$city->name} (ID: {$city->id}) -> Country ID: {$countryId}\n";
            $updatedCount++;
        }
    } else {
        echo "Skipping {$city->name} (ID: {$city->id}) - No mapping defined\n";
    }
}

echo "\nDone! Updated {$updatedCount} cities.\n";
