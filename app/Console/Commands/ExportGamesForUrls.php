<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;

class ExportGamesForUrls extends Command
{
    protected $signature = 'trophy:export-games
                            {output : Output CSV file path}
                            {--missing=psnprofiles : Only games missing this URL field (psnprofiles, playstationtrophies, powerpyx, any)}
                            {--limit= : Limit number of games}
                            {--recent : Only games from last 5 years (more likely to have guides)}';

    protected $description = 'Export games to CSV for manually adding trophy guide URLs';

    public function handle(): int
    {
        $output = $this->argument('output');
        $missing = $this->option('missing');
        $limit = $this->option('limit');
        $recent = $this->option('recent');

        $this->info("Exporting games for trophy URL collection...");

        $query = Game::query();

        // Filter by missing URL field
        if ($missing === 'any') {
            $query->where(function ($q) {
                $q->whereNull('psnprofiles_url')
                    ->orWhereNull('playstationtrophies_url')
                    ->orWhereNull('powerpyx_url');
            });
        } elseif ($missing) {
            $urlField = match ($missing) {
                'psnprofiles' => 'psnprofiles_url',
                'playstationtrophies' => 'playstationtrophies_url',
                'powerpyx' => 'powerpyx_url',
                default => null,
            };

            if ($urlField) {
                $query->whereNull($urlField);
            }
        }

        // Filter recent games
        if ($recent) {
            $fiveYearsAgo = now()->subYears(5);
            $query->where('release_date', '>=', $fiveYearsAgo);
        }

        // Order by release date (newer first - more likely to have guides)
        $query->orderBy('release_date', 'desc');

        if ($limit) {
            $query->limit((int) $limit);
        }

        $games = $query->get();

        if ($games->isEmpty()) {
            $this->info("No games found matching criteria.");
            return Command::SUCCESS;
        }

        // Create CSV
        $handle = fopen($output, 'w');
        if (!$handle) {
            $this->error("Could not create file: {$output}");
            return Command::FAILURE;
        }

        // Write header
        fputcsv($handle, [
            'id',
            'igdb_id',
            'title',
            'slug',
            'release_date',
            'psnprofiles_url',
            'playstationtrophies_url',
            'powerpyx_url',
        ]);

        // Write games
        foreach ($games as $game) {
            fputcsv($handle, [
                $game->id,
                $game->igdb_id,
                $game->title,
                $game->slug,
                $game->release_date?->format('Y-m-d'),
                $game->psnprofiles_url ?? '',
                $game->playstationtrophies_url ?? '',
                $game->powerpyx_url ?? '',
            ]);
        }

        fclose($handle);

        $this->info("Exported {$games->count()} games to: {$output}");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("  1. Open the CSV in a spreadsheet");
        $this->line("  2. Fill in the URL columns for games you find guides for");
        $this->line("  3. Import with: php artisan trophy:import-urls {$output} --source=psnprofiles");

        return Command::SUCCESS;
    }
}
