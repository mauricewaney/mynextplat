<?php

namespace App\Console\Commands;

use App\Services\PSNService;
use Illuminate\Console\Command;

class ExportPsnLibrary extends Command
{
    protected $signature = 'psn:export-library
                            {--format=csv : Output format (csv, json)}
                            {--output= : Output file path (default: storage/exports/psn_library.csv)}';

    protected $description = 'Export all games from your PSN library for manual review/mapping';

    public function handle(PSNService $psnService): int
    {
        if (!$psnService->authenticateFromConfig()) {
            $this->error('Failed to authenticate with PSN');
            return 1;
        }

        $this->info('Fetching PSN library (trophy + owned games)...');
        $result = $psnService->getMyFullLibrary();

        if (isset($result['error'])) {
            $this->error('Error: ' . $result['message']);
            return 1;
        }

        $titles = $result['titles'];
        $this->info('Found ' . count($titles) . ' games');

        $format = $this->option('format');
        $output = $this->option('output') ?? storage_path("exports/psn_library.{$format}");

        // Ensure directory exists
        $dir = dirname($output);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($format === 'json') {
            $this->exportJson($titles, $output);
        } else {
            $this->exportCsv($titles, $output);
        }

        $this->info("Exported to: {$output}");
        return 0;
    }

    private function exportCsv(array $titles, string $path): void
    {
        $fp = fopen($path, 'w');

        // Header
        fputcsv($fp, ['PSN Title', 'Title ID', 'Source', 'Image URL']);

        foreach ($titles as $title) {
            fputcsv($fp, [
                $title['name'] ?? 'Unknown',
                $title['titleId'] ?? '',
                $title['source'] ?? 'unknown',
                $title['imageUrl'] ?? '',
            ]);
        }

        fclose($fp);
    }

    private function exportJson(array $titles, string $path): void
    {
        $data = array_map(function ($title) {
            return [
                'psn_title' => $title['name'] ?? 'Unknown',
                'title_id' => $title['titleId'] ?? '',
                'source' => $title['source'] ?? 'unknown',
                'image_url' => $title['imageUrl'] ?? '',
            ];
        }, $titles);

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
