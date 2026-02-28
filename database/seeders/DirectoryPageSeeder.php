<?php

namespace Database\Seeders;

use App\Models\DirectoryPage;
use Illuminate\Database\Seeder;

class DirectoryPageSeeder extends Seeder
{
    public function run(): void
    {
        $genreIntros = [
            'adventure' => 'Explore vast worlds and unravel compelling stories with our curated list of Adventure trophy games. From narrative-driven epics to open-world exploration, these titles offer some of the most rewarding platinum journeys on PlayStation.',
            'arcade' => 'Relive the golden age of gaming with Arcade trophy games. These fast-paced, score-chasing titles deliver quick thrills and challenging platinums that test your reflexes and determination.',
            'card-and-board-game' => 'Discover trophy opportunities in Card & Board Game adaptations. These strategic titles offer a unique change of pace from action-heavy games, with platinums that reward patience and clever play.',
            'fighting' => 'Step into the ring with Fighting game trophies. From mastering combos to conquering arcade modes, these competitive titles offer platinums that prove your skills in one-on-one combat.',
            'hack-and-slash' => 'Unleash devastating combos in Hack and Slash trophy games. These action-packed titles reward aggressive gameplay and stylish combat, with platinums that demand mastery of fluid combat systems.',
            'indie' => 'Explore the creative world of Indie trophy games. These unique, often shorter titles frequently offer some of the most enjoyable and accessible platinums on PlayStation.',
            'moba' => 'Compete in the fast-paced world of MOBA trophy games. These team-based strategy titles offer challenging trophies that test your teamwork and tactical decision-making.',
            'music' => 'Feel the rhythm with Music game trophies. From guitar heroes to beat-matching challenges, these titles offer fun platinums that combine gaming with your love of music.',
            'pinball' => 'Tilt and score your way through Pinball trophy games. These digital recreations of classic tables offer unique platinums for players who enjoy precision and high-score chasing.',
            'platform' => 'Jump, run, and collect your way through Platform trophy games. From classic 2D side-scrollers to modern 3D adventures, these titles deliver satisfying platinums through precise movement and exploration.',
            'puzzle' => 'Challenge your mind with Puzzle trophy games. These brain-teasing titles offer some of the most satisfying platinums on PlayStation, rewarding creative thinking and problem-solving.',
            'quiz' => 'Test your knowledge with Quiz game trophies. These trivia-based titles offer entertaining platinums that reward your general knowledge and quick thinking.',
            'racing' => 'Hit the track with Racing trophy games. From realistic simulators to arcade speedsters, these high-octane titles offer platinums that test your driving skills and dedication.',
            'real-time-strategy' => 'Command your forces in Real Time Strategy trophy games. These tactical titles offer deep platinums that reward strategic thinking and resource management under pressure.',
            'role-playing' => 'Embark on epic quests with Role-Playing trophy games. These story-rich titles offer some of the most immersive platinum journeys, with hundreds of hours of content to explore.',
            'shooter' => 'Lock and load with Shooter trophy games. From first-person action to tactical shooters, these titles offer platinums that test your aim, reflexes, and combat strategy.',
            'simulation' => 'Experience realistic worlds in Simulation trophy games. From life sims to vehicle simulators, these titles offer unique platinums for players who enjoy methodical, detail-oriented gameplay.',
            'sport' => 'Score big with Sport trophy games. From football to basketball and beyond, these titles offer competitive platinums that combine athletic knowledge with gaming skill.',
            'strategy' => 'Plan your path to victory with Strategy trophy games. These thoughtful titles offer platinums that reward careful planning, resource management, and tactical brilliance.',
            'survival' => 'Brave the elements in Survival trophy games. These intense titles challenge you to scavenge, craft, and endure, with platinums that prove your resilience against all odds.',
            'tactical' => 'Execute precise maneuvers in Tactical trophy games. These thoughtful combat titles reward careful positioning and smart decision-making with deeply satisfying platinums.',
            'turn-based-strategy' => 'Outsmart your opponents in Turn-Based Strategy trophy games. These cerebral titles offer platinums that reward patience, forward thinking, and mastery of complex systems.',
            'visual-novel' => 'Immerse yourself in interactive storytelling with Visual Novel trophy games. These narrative-focused titles often offer accessible platinums through branching storylines and multiple endings.',
        ];

        $platformIntros = [
            'ps5' => 'Browse the latest PlayStation 5 trophy games. With stunning next-gen graphics and lightning-fast load times, PS5 offers the premium trophy hunting experience with both exclusive titles and enhanced cross-gen games.',
            'ps4' => 'Explore the massive PlayStation 4 trophy library. With thousands of titles spanning every genre, PS4 remains the ultimate platform for trophy hunters looking for variety and value.',
            'ps3' => 'Revisit classic PlayStation 3 trophy games. The console that introduced the trophy system still offers hundreds of rewarding platinums, from beloved exclusives to genre-defining multiplatform titles.',
            'ps-vita' => 'Discover PlayStation Vita trophy games. Sony\'s handheld powerhouse features a unique library of portable platinums, from console-quality adventures to Vita-exclusive gems.',
            'ps-vr' => 'Step into virtual reality with PS VR trophy games. These immersive titles offer a completely unique trophy hunting experience that puts you inside the game world.',
        ];

        $presetIntros = [
            'fast-and-easy' => 'Looking for a quick platinum? These games can be completed in under 15 hours with a difficulty rating of 4 or below, no online trophies, no missables, and only one playthrough required. Perfect for building your platinum collection fast.',
            'must-play' => 'The best of the best — games that score 80+ from both critics and players, with no online trophies or missables to worry about. These are the titles every trophy hunter should experience.',
            'no-stress' => 'Enjoy trophy hunting without the anxiety. These games have no online trophies and no missable trophies, so you can play at your own pace without worrying about servers shutting down or missing something permanently.',
            'easy-platinums' => 'Perfect for beginners or anyone looking to boost their platinum count. These games have a difficulty rating of 3 or below and a guaranteed platinum trophy waiting for you.',
            'quick-platinums' => 'Short on time? These platinum trophies can be earned in 10 hours or less. Ideal for trophy hunters who want to maximize their platinums per hour.',
            'offline-only' => 'Never worry about server shutdowns or finding online lobbies. Every trophy in these games can be earned completely offline, making them future-proof platinum choices.',
            'no-missables' => 'Play without a guide on your first run. These games have no missable trophies, meaning you can enjoy the experience naturally and clean up anything you missed afterward.',
            'hidden-gems' => 'Overlooked by critics but loved by players. These games have user scores of 75+ but critic scores under 75, making them hidden gems worth discovering for their unique platinum journeys.',
            'quality-epics' => 'For those who love long, critically acclaimed adventures. These RPGs, adventures, and strategy games score 80+ with critics and take 40+ hours to complete — epic platinum journeys for dedicated trophy hunters.',
        ];

        foreach ($genreIntros as $slug => $intro) {
            DirectoryPage::updateOrCreate(
                ['directory_type' => 'genre', 'slug' => $slug],
                ['intro_text' => $intro]
            );
        }

        foreach ($platformIntros as $slug => $intro) {
            DirectoryPage::updateOrCreate(
                ['directory_type' => 'platform', 'slug' => $slug],
                ['intro_text' => $intro]
            );
        }

        foreach ($presetIntros as $slug => $intro) {
            DirectoryPage::updateOrCreate(
                ['directory_type' => 'preset', 'slug' => $slug],
                ['intro_text' => $intro]
            );
        }

        $this->command->info('Seeded ' . (count($genreIntros) + count($platformIntros) + count($presetIntros)) . ' directory pages.');
    }
}
