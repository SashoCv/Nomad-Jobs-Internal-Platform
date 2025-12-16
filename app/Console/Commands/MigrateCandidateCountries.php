<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class MigrateCandidateCountries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:migrate-countries {--dry-run : Run without saving changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate candidate country strings to country_id foreign keys';

    /**
     * Country mapping dictionary
     * Maps various country name variations to the correct country name in countries table
     *
     * @var array
     */
    protected $countryMapping = [
        // India variations
        'INDIA' => 'India',
        'Индия' => 'India',

        // Nepal variations + regions
        'NEPAL' => 'Nepal',
        'Непал' => 'Nepal',
        'DANG' => 'Nepal',
        'GORKHA' => 'Nepal',
        'ILAM' => 'Nepal',
        'JHAPA' => 'Nepal',
        'KASKI' => 'Nepal',
        'KAVREPALANCHOK' => 'Nepal',
        'MORANG' => 'Nepal',
        'PANCHTHAR' => 'Nepal',
        'RUKUM' => 'Nepal',
        'SANKHUWASABHA' => 'Nepal',

        // Bangladesh variations
        'BANGLADESH' => 'Bangladesh',
        'Бангладеш' => 'Bangladesh',

        // Uzbekistan variations + regions
        'Uzbekistan' => 'Uzbekistan',
        'UZB' => 'Uzbekistan',
        'Andijan' => 'Uzbekistan',
        'Andijan Region' => 'Uzbekistan',
        'Andijon' => 'Uzbekistan',

        // Kyrgyzstan variations (multiple misspellings)
        'Kyrgyzstan' => 'Kyrgyzstan',
        'KYRGYZ REPUBLIC' => 'Kyrgyzstan',
        'Киргизия' => 'Kyrgyzstan',
        'Киргизстан' => 'Kyrgyzstan',
        'Киргизтан' => 'Kyrgyzstan',
        'Киргистан' => 'Kyrgyzstan',

        // Tajikistan variations
        'Tajikistan' => 'Tajikistan',
        'Таджикистан' => 'Tajikistan',

        // Other countries
        'Philippines' => 'Philippines',
        'Malta' => 'Malta',
        'North Macedonia' => 'North Macedonia',

        // Bulgarian names to English
        'Азърбейджан' => 'Azerbaijan',
        'Албания' => 'Albania',
        'Армения' => 'Armenia',
        'Беларус' => 'Belarus',
        'Босна и Херцеговина' => 'Bosnia and Herzegovina',
        'България' => 'България',
        'Великобритания' => 'United Kingdom',
        'Виетнам' => 'Vietnam',
        'Грузия' => 'Georgia',
        'Египет' => 'Egypt',
        'Израел' => 'Israel',
        'Ирак' => 'Iraq',
        'Иран' => 'Iran',
        'Ирска' => 'Ireland',
        'Казахстан' => 'Kazakhstan',
        'Кения' => 'Kenya',
        'Китай' => 'China',
        'Косово' => 'Kosovo',
        'Куба' => 'Cuba',
        'Кыргызстан' => 'Kyrgyzstan',
        'Кыргызстын' => 'Kyrgyzstan',
        'Ливан' => 'Lebanon',
        'Македония' => 'North Macedonia',
        'Република Северна Македония' => 'North Macedonia',
        'С.Македония' => 'North Macedonia',
        'Северна Македония' => 'North Macedonia',
        'Мароко' => 'Morocco',
        'Маршалските Острови' => 'Marshall Islands',
        'Молдавия' => 'Moldova',
        'Молдова' => 'Moldova',
        'Непал' => 'Nepal',
        'Нигерия' => 'Nigeria',
        'Пакистан' => 'Pakistan',
        'Руанда' => 'Rwanda',
        'Русия' => 'Russia',
        'Северна Ирландия' => 'United Kingdom',
        'Сирия' => 'Syria',
        'Србия' => 'Serbia',
        'Сърбия' => 'Serbia',
        'Таджикистан' => 'Tajikistan',
        'Тайланд' => 'Thailand',
        'Таџикистан' => 'Tajikistan',
        'Туркменистан' => 'Turkmenistan',
        'Турция' => 'Turkey',
        'Узбекистан' => 'Uzbekistan',
        'Узбекстан' => 'Uzbekistan',
        'узбекистон' => 'Uzbekistan',
        'Украина' => 'Ukraine',
        'Украйна' => 'Ukraine',
        'Филипини' => 'Philippines',
        'хкхйкйх' => null, // Invalid test data - skip
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be saved');
        }

        $this->info('🚀 Starting candidate country migration...');
        $this->newLine();

        // Get all countries from database
        $countries = Country::all()->keyBy('name');

        // Get all distinct country values from candidates
        $distinctCountries = Candidate::whereNotNull('country')
            ->distinct()
            ->pluck('country')
            ->filter()
            ->sort();

        $this->info("📊 Found {$distinctCountries->count()} distinct country values in candidates table");
        $this->newLine();

        // Statistics
        $stats = [
            'total' => 0,
            'mapped' => 0,
            'unmapped' => 0,
            'updated' => 0,
        ];

        $unmappedCountries = [];

        // Process each distinct country
        foreach ($distinctCountries as $oldCountry) {
            // Skip test/invalid data
            if (in_array($oldCountry, ['.', '22222', 'teeessttt', 'TEST', 'SALWA ROAD'])) {
                continue;
            }

            $stats['total']++;

            // Try to find mapping
            $mappedCountry = $this->countryMapping[$oldCountry] ?? $oldCountry;

            // Check if country exists in database
            if ($countries->has($mappedCountry)) {
                $countryId = $countries->get($mappedCountry)->id;
                $stats['mapped']++;

                // Count how many candidates will be updated
                $count = Candidate::where('country', $oldCountry)
                    ->whereNull('country_id')
                    ->count();

                if ($count > 0) {
                    $this->info("✅ '{$oldCountry}' → '{$mappedCountry}' (ID: {$countryId}) - {$count} candidates");

                    if (!$isDryRun) {
                        Candidate::where('country', $oldCountry)
                            ->whereNull('country_id')
                            ->update(['country_id' => $countryId]);
                        $stats['updated'] += $count;
                    }
                }
            } else {
                $stats['unmapped']++;
                $unmappedCountries[] = $oldCountry;
                $this->warn("❌ '{$oldCountry}' → NOT FOUND in countries table");
            }
        }

        // Summary
        $this->newLine();
        $this->info('📈 Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total unique countries', $stats['total']],
                ['Successfully mapped', $stats['mapped']],
                ['Not mapped', $stats['unmapped']],
                ['Candidates updated', $isDryRun ? 'DRY RUN' : $stats['updated']],
            ]
        );

        if (!empty($unmappedCountries)) {
            $this->newLine();
            $this->warn('⚠️  Unmapped countries:');
            foreach ($unmappedCountries as $country) {
                $this->line("   - {$country}");
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
