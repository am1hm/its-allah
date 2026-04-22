<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QuranImportPrimaryCommand extends Command
{
    protected $signature = 'innahu:quran-import-primary {--source=} {--source-url=} {--source-format=}';

    protected $description = 'Import canonical Quran verses from primary KFGQPC source (Hafs an Asim only)';

    public function handle(): int
    {
        $source = $this->option('source');
        $sourceUrl = $this->option('source-url');

        if (! $source && ! $sourceUrl) {
            $this->error('Please provide either --source=local_file or --source-url=https://...');

            return self::FAILURE;
        }

        if ($sourceUrl) {
            $source = $this->downloadToTemp((string) $sourceUrl);
        }

        if (! $source || ! File::exists($source)) {
            $this->error("Source file not found: {$source}");

            return self::FAILURE;
        }

        $runId = DB::table('quran_ingestion_runs')->insertGetId([
            'source_id' => 'kfgqpc',
            'run_type' => 'import',
            'started_at' => Carbon::now(),
            'status' => 'running',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        try {
            $rows = $this->readRows($source, $this->option('source-format'));
            $processed = 0;
            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $processed++;

                $surah = (int) ($row['surah_number'] ?? $row['surah'] ?? 0);
                $ayah = (int) ($row['ayah_number'] ?? $row['ayah'] ?? 0);
                $text = (string) ($row['ayah_text'] ?? $row['text'] ?? '');
                $simple = (string) ($row['ayah_text_simple'] ?? $row['text_simple'] ?? '');
                $surahName = (string) ($row['surah_name'] ?? $row['surah_arabic'] ?? '');
                $qiraah = Str::lower((string) ($row['qiraah'] ?? 'asim'));
                $riwayah = Str::lower((string) ($row['riwayah'] ?? 'hafs'));

                if (! $surah || ! $ayah || $text === '') {
                    $skipped++;
                    continue;
                }

                // Hard rule: Hafs an Asim only.
                if ($qiraah !== 'asim' || $riwayah !== 'hafs') {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'surah_number' => $surah,
                    'surah_name' => $surahName !== '' ? $surahName : "Surah {$surah}",
                    'ayah_number' => $ayah,
                    'ayah_text' => $text,
                    'ayah_text_simple' => $simple !== '' ? $simple : null,
                    'qiraah' => 'asim',
                    'riwayah' => 'hafs',
                    'source_ref' => 'kfgqpc',
                    'source_url' => $sourceUrl ?: $source,
                    'ingestion_timestamp' => Carbon::now(),
                    'parser_version' => 'step1-v1',
                    'source_hash' => hash('sha256', "{$surah}|{$ayah}|{$text}|asim|hafs"),
                    'updated_at' => Carbon::now(),
                ];

                $existing = DB::table('quran_verses')
                    ->where('surah_number', $surah)
                    ->where('ayah_number', $ayah)
                    ->first();

                if (! $existing) {
                    $payload['created_at'] = Carbon::now();
                    DB::table('quran_verses')->insert($payload);
                    $inserted++;
                } else {
                    DB::table('quran_verses')
                        ->where('id', $existing->id)
                        ->update($payload);
                    $updated++;
                }
            }

            DB::table('quran_ingestion_runs')->where('id', $runId)->update([
                'completed_at' => Carbon::now(),
                'status' => 'completed',
                'rows_processed' => $processed,
                'rows_inserted' => $inserted,
                'rows_updated' => $updated,
                'rows_skipped' => $skipped,
                'meta' => json_encode(['constraint' => 'hafs_an_asim_only'], JSON_UNESCAPED_UNICODE),
                'updated_at' => Carbon::now(),
            ]);

            $this->info("Import completed. processed={$processed} inserted={$inserted} updated={$updated} skipped={$skipped}");

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

            // KFGQPC mirror format: list of ayah objects with sora/aya_no/aya_text.
            $rows = [];
            foreach (array_values($decoded) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $rows[] = [
                    'surah_number' => $item['surah_number'] ?? $item['sora'] ?? null,
                    'surah_name' => $item['surah_name'] ?? $item['sora_name_ar'] ?? null,
                    'ayah_number' => $item['ayah_number'] ?? $item['aya_no'] ?? null,
                    'ayah_text' => $item['ayah_text'] ?? $item['aya_text'] ?? null,
                    'ayah_text_simple' => $item['ayah_text_simple'] ?? $item['aya_text_emlaey'] ?? null,
                    'qiraah' => $item['qiraah'] ?? 'asim',
                    'riwayah' => $item['riwayah'] ?? 'hafs',
                ];
            }

            return $rows;
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

        throw new \RuntimeException('Unsupported source extension. Use JSON or CSV.');
    }

    private function downloadToTemp(string $url): string
    {
        $response = Http::withoutVerifying()->timeout(120)->get($url);
        if (! $response->successful()) {
            throw new \RuntimeException("Failed to download source URL: {$url}");
        }

        $tempDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tempDir);
        $filename = 'kfgqpc_'.md5($url.'_'.microtime(true)).'.json';
        $path = $tempDir.'/'.$filename;
        File::put($path, $response->body());

        return $path;
    }
}

