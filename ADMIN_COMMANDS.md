# Admin Commands Reference

## IGDB Game Import
```bash
# Import new games (default: 500 per batch, continues from last release date)
php artisan igdb:import

# Limit batch size
php artisan igdb:import --limit=100

# Run via queue instead of synchronously
php artisan igdb:import --queue
```

## Trophy Guide URL Discovery
```bash
# Search for new guides published in last 24h (PSNProfiles, PST, PowerPyx)
php artisan trophy:daily-search

# Scrape PowerPyx guides index for new URLs
php artisan trophy:scrape --new --limit=200

# Match unmatched trophy URLs to games
php artisan trophy:match-urls
```

## PSN Lookup
```bash
# Lookup a PSN user's library
php artisan psn:lookup {username}

# Lookup with auto-matching
php artisan psn:lookup {username} --match
```

## Notifications
```bash
# Send new guide notifications (email)
php artisan notifications:new-guides

# Dry run (preview without sending)
php artisan notifications:new-guides --dry-run

# Test for a specific user
php artisan notifications:new-guides --user={id}
```

## Scheduled Jobs (daily, automatic via Forge cron)
| Time  | Task                          | Command                              |
|-------|-------------------------------|--------------------------------------|
| 00:05 | Mark shutdown games unobtainable | Inline (console.php)              |
| 03:00 | IGDB game import              | ImportIGDBGames job (queued)         |
| 04:00 | PowerPyx trophy scrape        | `trophy:scrape --new --limit=200`    |
| 05:00 | Daily guide URL search        | `trophy:daily-search`                |
| 06:00 | New guide notifications       | `notifications:new-guides`           |

## Queue
```bash
# Process queued jobs (needed for IGDB import + notification emails)
php artisan queue:work --sleep=3 --tries=3

# Check failed jobs
php artisan queue:failed
```
