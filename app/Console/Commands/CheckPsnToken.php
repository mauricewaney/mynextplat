<?php

namespace App\Console\Commands;

use App\Services\PSNService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckPsnToken extends Command
{
    protected $signature = 'psn:check-token';

    protected $description = 'Verify the PSN NPSSO token can still authenticate; email admin on failure.';

    public function handle(PSNService $psn): int
    {
        Log::info('[psn-token-check] starting');

        $npsso = config('services.psn.npsso');

        if (!$npsso) {
            return $this->notifyFailure('PSN_NPSSO is empty in config — set it on Forge.');
        }

        Cache::forget('psn_auth_data');

        if (!$psn->authenticate($npsso)) {
            return $this->notifyFailure('PSN authentication failed with the current NPSSO. Token has likely expired.');
        }

        Log::info('[psn-token-check] passed — token is valid');
        $this->info('PSN token is valid.');
        return Command::SUCCESS;
    }

    private function notifyFailure(string $reason): int
    {
        Log::warning('[psn-token-check] FAILED — ' . $reason);

        $to = config('mail.admin_notify_to', 'mjwaney@gmail.com');
        $appUrl = config('app.url');

        $body = "PSN token check FAILED on {$appUrl}\n\n"
            . "Reason: {$reason}\n\n"
            . "Steps to refresh:\n"
            . "  1. Go to https://store.playstation.com and sign in (NL store, mjwaney@gmail.com)\n"
            . "  2. Open https://ca.account.sony.com/api/v1/ssocookie in another tab\n"
            . "  3. Copy the \"npsso\" value from the JSON response\n"
            . "  4. Update PSN_NPSSO on Forge and deploy\n"
            . "  5. Run: php artisan cache:forget psn_auth_data\n\n"
            . "Until then, PSN library imports will not work for users.";

        try {
            Mail::raw($body, function ($msg) use ($to) {
                $msg->to($to)->subject('[MyNextPlat ⚠] PSN token check failed');
            });
        } catch (\Throwable $e) {
            report($e);
        }

        $this->error($reason);
        return Command::FAILURE;
    }
}
