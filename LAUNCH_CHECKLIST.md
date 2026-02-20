# Launch Checklist

## Infrastructure (Forge)
- [X] Add Laravel scheduler cron: `php /path/to/artisan schedule:run` (every minute)
- [X] Add queue worker daemon: `php artisan queue:work --sleep=3 --tries=3`
- [-] Set up mail provider (Mailgun/Resend free tier) + configure `.env` mail settings
- [-] Test notifications: `php artisan notifications:new-guides --dry-run`
- [-] Register and point domain name

## Data Verification
- [ ] Finish first verification pass (difficulty, time, playthroughs, online, missable)
- [ ] Second pass: verify has_platinum on already-verified games
- [X] Spot-check PSNP-only games (quick fill parsing issues)

## Frontend / UX
- [ ] Pick domain name
- [ ] Review robots.txt for production
- [ ] Test PSN lookup rate limiting with real users
- [ ] Hard refresh / cache bust after deploy

## Marketing
- [ ] Draft r/Trophies launch post (proofread with Claude)
- [ ] Prepare screenshots / examples for the post
- [ ] Launch clean - no monetization on day one
- [ ] Be responsive to feedback in comments

## Post-Launch (2-4 months)
- [ ] Monitor usage patterns and PSN API rate limits
- [ ] Consider adding supporter tier / subtle affiliate links once trust is built
- [ ] Community notes feature (simple: user-submitted, admin-approved)
- [ ] has_platinum second pass if not done pre-launch
