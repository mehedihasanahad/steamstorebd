<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Console\Command;

class GenerateReferralCodes extends Command
{
    protected $signature   = 'referral:generate-codes';
    protected $description = 'Generate referral codes for all existing users who do not have one.';

    public function handle(ReferralService $referralService): int
    {
        $users = User::whereNull('referral_code')->get();

        if ($users->isEmpty()) {
            $this->info('All users already have a referral code.');
            return self::SUCCESS;
        }

        $this->info("Generating referral codes for {$users->count()} user(s)...");
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $user->update(['referral_code' => $referralService->generateUniqueCode()]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
