<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTrophyUrls extends Command
{
    protected $signature = 'trophy:import-urls
                            {file : Path to CSV file with game_id/slug and URL}
                            {--source=psnprofiles : URL field to update (psnprofiles, playstationtrophies, powerpyx)}
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Bulk import trophy guide URLs from a CSV file';

    public function handle(): int
    {
        $file = $this->argument('file');
        $source = $this->option('source');
        $dryRun = $this->option('dry-run');

        $urlField = match ($source) {
            'psnprofiles' => 'psnprofiles_url',
            'playstationtrophies' => 'playstationtrophies_url',
            'powerpyx' => 'powerpyx_url',
            default => null,
        };

        if (!$urlField) {
            $this->error("Unknown source: {$source}");
            return Command::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $this->info("Trophy URL Import");
        $this->info("=================");
        $this->info("Source: {$source}");
        $this->info("Field: {$urlField}");
        if ($dryRun) {
            $this->warn("DRY RUN - No changes will be made");
        }
        $this->newLine();

        // Read CSV
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$file}");
            return Command::FAILURE;
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("Empty file or invalid CSV");
            fclose($handle);
            return Command::FAILURE;
        }

        // Normalize header
        $header = array_map('strtolower', array_map('trim', $header));

        // Find columns
        $idCol = $this->findColumn($header, ['id', 'game_id', 'igdb_id']);
        $slugCol = $this->findColumn($header, ['slug', 'game_slug']);
        $titleCol = $this->findColumn($header, ['title', 'name', 'game', 'game_title']);
        $urlCol = $this->findColumn($header, ['url', 'guide_url', 'psnprofiles_url', 'playstationtrophies_url', 'powerpyx_url', 'link']);

        if ($urlCol === null) {
            $this->error("Could not find URL column. Expected: url, guide_url, link, or {$urlField}");
            fclose($handle);
            return Command::FAILURE;
        }

        if ($idCol === null && $slugCol === null && $titleCol === null) {
            $this->error("Could not find game identifier column. Expected: id, slug, or title");
            fclose($handle);
            return Command::FAILURE;
        }

        $this->info("Found columns:");
        if ($idCol !== null) $this->line("  - ID: column " . ($idCol + 1));
        if ($slugCol !== null) $this->line("  - Slug: column " . ($slugCol + 1));
        if ($titleCol !== null) $this->line("  - Title: column " . ($titleCol + 1));
        $this->line("  - URL: column " . ($urlCol + 1));
        $this->newLine();

        $stats = ['updated' => 0, 'not_found' => 0, 'skipped' => 0, 'invalid' => 0];
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            $url = trim($data[$urlCol] ?? '');
            if (empty($url)) {
                $stats['skipped']++;
                continue;
            }

            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $this->warn("Row {$row}: Invalid URL - {$url}");
                $stats['invalid']++;
                continue;
            }

            // Find game
            $game = null;

            if ($idCol !== null && !empty($data[$idCol])) {
                $id = trim($data[$idCol]);
                $game = Game::find($id) ?? Game::where('igdb_id', $id)->first();
            }

            if (!$game && $slugCol !== null && !empty($data[$slugCol])) {
                $game = Game::where('slug', trim($data[$slugCol]))->first();
            }

            if (!$game && $titleCol !== null && !empty($data[$titleCol])) {
                $game = Game::where('title', trim($data[$titleCol]))->first();
            }

            if (!$game) {
                $identifier = $data[$idCol] ?? $data[$slugCol] ?? $data[$titleCol] ?? 'unknown';
                $this->warn("Row {$row}: Game not found - {$identifier}");
                $stats['not_found']++;
                continue;
            }

            // Update
            if ($dryRun) {
                $this->line("Would update: {$game->title} -> {$url}");
            } else {
                $game->{$urlField} = $url;
                $game->save();
            }

            $stats['updated']++;
        }

        fclose($handle);

        $this->newLine();
        $this->info("Import complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                [$dryRun ? 'Would Update' : 'Updated', $stats['updated']],
                ['Not Found', $stats['not_found']],
                ['Skipped (empty)', $stats['skipped']],
                ['Invalid URL', $stats['invalid']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Find a column by possible names
     */
    protected function findColumn(array $header, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            $index = array_search($name, $header);
            if ($index !== false) {
                return $index;
            }
        }
        return null;
    }
}
