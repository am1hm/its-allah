<?php

namespace App\Console\Commands;

use App\Services\ApprovedExportService;
use Illuminate\Console\Command;

class ExportApprovedCommand extends Command
{
    protected $signature = 'innahu:export-approved';

    protected $description = 'Export approved publication content to JSON and Markdown';

    public function handle(ApprovedExportService $service): int
    {
        $base = base_path('../exports');
        $json = $service->exportJson($base.'/approved_content.json');
        $md = $service->exportMarkdown($base.'/approved_book.md');

        $this->info('Exported JSON: '.$json);
        $this->info('Exported Markdown: '.$md);

        return self::SUCCESS;
    }
}

