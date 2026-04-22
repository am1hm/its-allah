<?php

namespace App\Console\Commands;

use App\Services\QuranTextNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QuranCompareTanzilCommand extends Command
{
    protected $signature = 'innahu:quran-compare-tanzil {--source=} {--source-url=} {--source-format=} {--download-live} {--disable-normalization}';

    protected $description = 'Compare canonical KFGQPC verses with Tanzil dataset and emit diff report';

    public function handle(QuranTextNormalizer $normalizer): int
    {
        $source = $this->option('source');
        $sourceUrl = $this->option('source-url');
        $downloadLive = (bool) $this->option('download-live');
        $disableNormalization = (bool) $this->option('disable-normalization');

        if ($downloadLive) {
            $source = $this->downloadTanzilLive();
            $sourceUrl = 'https://tanzil.net/pub/download/v1.0/download.php';
        } elseif ($sourceUrl) {
            $source = $this->downloadToTemp((string) $sourceUrl);
        }

        if (! $source || ! File::exists($source)) {
            $this->error('Please provide valid --source or --source-url, or use --download-live');

            return self::FAILURE;
        }

        $runId = DB::table('quran_ingestion_runs')->insertGetId([
            'source_id' => 'tanzil',
            'run_type' => 'compare',
            'started_at' => Carbon::now(),
            'status' => 'running',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        try {
            $rows = $this->readRows($source, $this->option('source-format'));
            DB::table('quran_verse_diffs')->delete();

            $processed = 0;
            $exact = 0;
            $normalized = 0;
            $mismatch = 0;
            $missingCanonical = 0;
            $diffRows = [];

            foreach ($rows as $row) {
                $surah = (int) ($row['surah_number'] ?? $row['surah'] ?? 0);
                $ayah = (int) ($row['ayah_number'] ?? $row['ayah'] ?? 0);
                $tanzilText = (string) ($row['ayah_text'] ?? $row['text'] ?? '');
                $qiraah = Str::lower((string) ($row['qiraah'] ?? 'asim'));
                $riwayah = Str::lower((string) ($row['riwayah'] ?? 'hafs'));

                if (! $surah || ! $ayah || $tanzilText === '') {
                    continue;
                }

                // Step1 constraint: compare only Hafs an Asim references.
                if ($qiraah !== 'asim' || $riwayah !== 'hafs') {
                    continue;
                }

                $processed++;
                $canonical = DB::table('quran_verses')
                    ->where('surah_number', $surah)
                    ->where('ayah_number', $ayah)
                    ->where('qiraah', 'asim')
                    ->where('riwayah', 'hafs')
                    ->first();

                if (! $canonical) {
                    $missingCanonical++;
                    $mismatch++;
                    DB::table('quran_verse_diffs')->insert([
                        'surah_number' => $surah,
                        'ayah_number' => $ayah,
                        'kfgqpc_text' => null,
                        'tanzil_text' => $tanzilText,
                        'normalized_kfgqpc' => null,
                        'normalized_tanzil' => $normalizer->normalize($tanzilText),
                        'diff_type' => 'mismatch',
                        'notes' => 'Canonical Hafs an Asim verse missing in database.',
                        'qiraah' => 'asim',
                        'riwayah' => 'hafs',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    $diffRows[] = [
                        'surah_number' => $surah,
                        'ayah_number' => $ayah,
                        'diff_type' => 'mismatch',
                        'notes' => 'missing_canonical',
                    ];
                    continue;
                }

                $normCanonical = $disableNormalization
                    ? (string) $canonical->ayah_text
                    : $normalizer->normalize((string) $canonical->ayah_text);
                $normTanzil = $disableNormalization
                    ? $tanzilText
                    : $normalizer->normalize($tanzilText);

                if ((string) $canonical->ayah_text === $tanzilText) {
                    $type = 'exact_match';
                    $exact++;
                } elseif ($normCanonical === $normTanzil) {
                    $type = 'normalized_match';
                    $normalized++;
                } else {
                    $type = 'mismatch';
                    $mismatch++;
                }

                DB::table('quran_verse_diffs')->insert([
                    'surah_number' => $surah,
                    'ayah_number' => $ayah,
                    'kfgqpc_text' => $canonical->ayah_text,
                    'tanzil_text' => $tanzilText,
                    'normalized_kfgqpc' => $normCanonical,
                    'normalized_tanzil' => $normTanzil,
                    'diff_type' => $type,
                    'notes' => $type === 'mismatch' ? 'Text mismatch after normalization.' : null,
                    'qiraah' => 'asim',
                    'riwayah' => 'hafs',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                if ($type !== 'exact_match') {
                    $diffRows[] = [
                        'surah_number' => $surah,
                        'ayah_number' => $ayah,
                        'diff_type' => $type,
                        'canonical' => $canonical->ayah_text,
                        'tanzil' => $tanzilText,
                        'normalized_canonical' => $normCanonical,
                        'normalized_tanzil' => $normTanzil,
                    ];
                }
            }

            $reportPath = base_path('../exports/quran_diff_report.json');
            File::ensureDirectoryExists(dirname($reportPath));
            File::put($reportPath, json_encode([
                'source' => 'tanzil',
                'constraint' => 'hafs_an_asim_only',
                'summary' => [
                    'total_compared' => $processed,
                    'exact_matches' => $exact,
                    'normalized_matches' => $normalized,
                    'mismatches' => $mismatch,
                    'missing_canonical' => $missingCanonical,
                    'normalization_enabled' => ! $disableNormalization,
                ],
                'diffs' => $diffRows,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            DB::table('quran_ingestion_runs')->where('id', $runId)->update([
                'completed_at' => Carbon::now(),
                'status' => 'completed',
                'rows_processed' => $processed,
                'mismatch_count' => $mismatch,
                'output_report_path' => $reportPath,
                'meta' => json_encode([
                    'exact_matches' => $exact,
                    'normalized_matches' => $normalized,
                    'missing_canonical' => $missingCanonical,
                    'source_url' => $sourceUrl ?: $source,
                    'normalization_enabled' => ! $disableNormalization,
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => Carbon::now(),
            ]);

            $this->info("Compare completed. compared={$processed} exact={$exact} normalized={$normalized} mismatch={$mismatch}");
            $this->info("Report: {$reportPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::table('quran_ingestion_runs')->where('id', $runId)->update([
                'completed_at' => Carbon::now(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at' => Carbon::now(),
            ]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRows(string $source, ?string $forcedFormat = null): array
    {
        $ext = Str::lower($forcedFormat ?: pathinfo($source, PATHINFO_EXTENSION));
        if ($ext === 'json') {
            $decoded = json_decode(File::get($source), true);
            if (! is_array($decoded)) {
                throw new \RuntimeException('Invalid JSON source format.');
            }

            return array_values($decoded);
        }

        if ($ext === 'csv') {
            $lines = file($source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (! $lines || count($lines) < 2) {
                return [];
            }
            $headers = str_getcsv(array_shift($lines));
            $rows = [];
            foreach ($lines as $line) {
                $cells = str_getcsv($line);
                $rows[] = array_combine($headers, $cells) ?: [];
            }

            return $rows;
        }

        if ($ext === 'txt') {
            $lines = file($source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (! $lines) {
                return [];
            }
            $rows = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || Str::startsWith($line, '#')) {
                    continue;
                }
                $parts = explode('|', $line, 3);
                if (count($parts) !== 3) {
                    continue;
                }
                $rows[] = [
                    'surah_number' => (int) $parts[0],
                    'ayah_number' => (int) $parts[1],
                    'ayah_text' => $parts[2],
                    'qiraah' => 'asim',
                    'riwayah' => 'hafs',
                ];
            }

            return $rows;
        }

        throw new \RuntimeException('Unsupported source extension. Use JSON, CSV, or TXT.');
    }

    private function downloadToTemp(string $url): string
    {
        $response = Http::withoutVerifying()->timeout(120)->get($url);
        if (! $response->successful()) {
            throw new \RuntimeException("Failed to download source URL: {$url}");
        }
        $tempDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tempDir);
        $filename = 'tanzil_'.md5($url.'_'.microtime(true)).'.txt';
        $path = $tempDir.'/'.$filename;
        File::put($path, $response->body());

        return $path;
    }

    private function downloadTanzilLive(): string
    {
        $response = Http::withoutVerifying()->asForm()
            ->timeout(120)
            ->post('https://tanzil.net/pub/download/v1.0/download.php', [
                'quranType' => 'uthmani',
                'outType' => 'txt-2',
                'agree' => 'true',
                'marks' => 'true',
                'sajdah' => 'true',
                'rub' => 'true',
                'alef' => 'true',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to download Tanzil live source.');
        }

        $tempDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tempDir);
        $path = $tempDir.'/tanzil_live_'.md5((string) microtime(true)).'.txt';
        File::put($path, $response->body());

        return $path;
    }
}

