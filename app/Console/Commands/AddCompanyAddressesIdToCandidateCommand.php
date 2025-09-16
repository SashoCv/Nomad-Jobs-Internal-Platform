<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddCompanyAddressesIdToCandidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidate:add-company-addresses-id {--limit=0 : Limit number of candidates to process} {--show-failures-only : Show only failed matches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add company addresses ID to candidates';

    public function handle()
    {
        $limit = $this->option('limit');
        $showFailuresOnly = $this->option('show-failures-only');

        $candidates = \App\Models\Candidate::all();

        if ($limit > 0) {
            $candidates = $candidates->take($limit);
        }

        $processedCount = 0;
        $matchedCount = 0;
        $noMatchCount = 0;

        foreach ($candidates as $candidate) {
            $companyId = $candidate->company_id;

            if (!$companyId) {
                if (!$showFailuresOnly) {
                    $this->warn("No company ID found for candidate ID {$candidate->id}");
                }
                continue;
            }

            $addressOfWork = $candidate->addressOfWork;

            if (!$addressOfWork) {
                if (!$showFailuresOnly) {
                    $this->warn("No addressOfWork found for candidate ID {$candidate->id}");
                }
                continue;
            }

            $companyAddress = \App\Models\CompanyAdress::where('company_id', $companyId)->get();

            if ($companyAddress->count() === 0) {
                if (!$showFailuresOnly) {
                    $this->warn("No company address found for candidate ID {$candidate->id} with company ID {$companyId}");
                }
                continue;
            }

            if ($companyAddress->count() === 1) {
                $candidate->company_adresses_id = $companyAddress->first()->id;
                $candidate->save();
                $matchedCount++;
                if (!$showFailuresOnly) {
                    $this->info("✅ Updated candidate ID {$candidate->id} with single company address ID {$companyAddress->first()->id}");
                }
            } else {
                // Multiple addresses - need to find the best match
                $matchedAddress = $this->findBestAddressMatch($addressOfWork, $companyAddress, $candidate->id);

                if ($matchedAddress) {
                    $candidate->company_adresses_id = $matchedAddress->id;
                    $candidate->save();
                    $matchedCount++;
                    if (!$showFailuresOnly) {
                        $this->info("✅ Updated candidate ID {$candidate->id} with matched company address ID {$matchedAddress->id}");
                    }
                } else {
                    $noMatchCount++;
                    $this->warn("❌ Multiple company addresses found for candidate ID {$candidate->id}, but no match found for addressOfWork '{$addressOfWork}'");

                    // Show available addresses for debugging
                    $this->warn("Available addresses:");
                    foreach ($companyAddress as $index => $addr) {
                        $this->warn("  {$index}: {$addr->address}");
                    }
                    $this->warn("Candidate address: {$addressOfWork}");
                    $this->warn("---");
                }
            }

            $processedCount++;
        }

        // Summary
        if (!$showFailuresOnly || $noMatchCount > 0) {
            $this->info("=== SUMMARY ===");
            $this->info("Total candidates processed: {$processedCount}");
            $this->info("Successfully matched: {$matchedCount}");
            $this->info("No matches found: {$noMatchCount}");
        }
    }

    private function findBestAddressMatch($candidateAddress, $companyAddresses, $candidateId = null)
    {
        $bestMatch = null;
        $bestScore = 0;

        $normalizedCandidate = $this->normalizeAddressForComparison($candidateAddress);

        foreach ($companyAddresses as $address) {
            $normalizedDb = $this->normalizeAddressForComparison($address->address);

            $score = $this->calculateAddressMatchScore($normalizedCandidate, $normalizedDb);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $address;
            }
        }

        // Lower threshold - accept matches above 60%
        if ($bestScore >= 0.6) {
            return $bestMatch;
        }

        // Debug output for failed matches
        $this->warn("=== DEBUG: No match found for candidate ID {$candidateId} ===");
        $this->warn("Candidate: '{$candidateAddress}'");
        $this->warn("Normalized: '{$normalizedCandidate}'");
        $this->warn("Available DB addresses:");

        foreach ($companyAddresses as $index => $address) {
            $normalizedDb = $this->normalizeAddressForComparison($address->address);
            $score = $this->calculateAddressMatchScore($normalizedCandidate, $normalizedDb);
            $this->warn("  [{$index}] Original: '{$address->address}'");
            $this->warn("       Normalized: '{$normalizedDb}'");
            $this->warn("       Score: " . round($score * 100, 2) . "%");
        }
        $this->warn("Best score was: " . round($bestScore * 100, 2) . "% (needed 60%+)");
        $this->warn("==================");

        return null;
    }

    private function normalizeAddressForComparison($address)
    {
        if (!$address) {
            return '';
        }

        $normalized = mb_strtolower(trim($address));

        // Remove common prefixes and administrative terms
        $prefixes = [
            'обл\.?', 'област', 'общ\.?', 'община', 'п\.к\.?', 'пощенски код',
            'с\.?', 'село', 'гр\.?', 'град', 'ул\.?', 'улица', 'бул\.?', 'булевард',
            'к\.к\.?', 'кв\.?', 'квартал', 'ж\.к\.?', 'жилищен комплекс', 'район',
            'пл\.?', 'площад', 'м\.?', 'местност', 'р-н', 'идентификатор', 'пи'
        ];

        $normalized = preg_replace('/\b(' . implode('|', $prefixes) . ')\s*/u', '', $normalized);

        // Clean up various quote types and special characters
        $normalized = str_replace(['"', '"', '"', '„', '\'\'', '№', ',', ';', ':', '.', '/', '\\', '(', ')', '[', ']'], ' ', $normalized);

        // Normalize ordinal numbers (treti/3-ti -> 3)
        $numberReplacements = [
            'първи' => '1', '1-ви' => '1', '1-ри' => '1',
            'втори' => '2', '2-ри' => '2',
            'трети' => '3', '3-ти' => '3',
            'четвърти' => '4', '4-ти' => '4',
            'пети' => '5', '5-ти' => '5',
            'шести' => '6', '6-ти' => '6',
            'седми' => '7', '7-ми' => '7',
            'осми' => '8', '8-ми' => '8',
            'девети' => '9', '9-ти' => '9',
            'десети' => '10', '10-ти' => '10'
        ];

        foreach ($numberReplacements as $word => $digit) {
            $normalized = str_ireplace($word, $digit, $normalized);
        }

        // Remove letter suffixes from house numbers (1А -> 1, 15Б -> 15)
        $normalized = preg_replace('/(\d+)[а-я]+/u', '$1', $normalized);

        // Handle ranges and complex numbers (101-103 -> 101 103)
        $normalized = preg_replace('/(\d+)-(\d+)/', '$1 $2', $normalized);

        // Normalize multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    private function calculateAddressMatchScore($candidateAddress, $dbAddress)
    {
        if (!$candidateAddress || !$dbAddress) {
            return 0;
        }

        // Get words from both addresses
        $candidateWords = array_filter(explode(' ', trim($candidateAddress)), function($word) {
            return strlen($word) >= 2; // Skip very short words
        });

        $dbWords = array_filter(explode(' ', trim($dbAddress)), function($word) {
            return strlen($word) >= 2; // Skip very short words
        });

        if (empty($candidateWords) || empty($dbWords)) {
            return 0;
        }

        // Count matching words
        $matchedWords = 0;
        $usedDbWords = [];

        foreach ($candidateWords as $candidateWord) {
            $bestMatch = null;
            $bestMatchIndex = -1;

            foreach ($dbWords as $index => $dbWord) {
                if (in_array($index, $usedDbWords)) {
                    continue; // Skip already used words
                }

                // Exact match
                if ($candidateWord === $dbWord) {
                    $bestMatch = $dbWord;
                    $bestMatchIndex = $index;
                    break;
                }

                // Fuzzy match for similar words
                $similarity = 0;
                similar_text($candidateWord, $dbWord, $similarity);

                if ($similarity >= 85) { // 85% similarity
                    $bestMatch = $dbWord;
                    $bestMatchIndex = $index;
                    break;
                }

                // Levenshtein distance for very similar words
                if (strlen($candidateWord) > 3 && strlen($dbWord) > 3) {
                    $distance = levenshtein($candidateWord, $dbWord);
                    $maxLen = max(strlen($candidateWord), strlen($dbWord));
                    $similarity = (1 - $distance / $maxLen) * 100;

                    if ($similarity >= 80) {
                        $bestMatch = $dbWord;
                        $bestMatchIndex = $index;
                        break;
                    }
                }
            }

            if ($bestMatch !== null) {
                $matchedWords++;
                $usedDbWords[] = $bestMatchIndex;
            }
        }

        // Calculate match score based on the address with fewer words
        $minWords = min(count($candidateWords), count($dbWords));
        $matchScore = $minWords > 0 ? $matchedWords / $minWords : 0;

        return $matchScore;
    }
}
