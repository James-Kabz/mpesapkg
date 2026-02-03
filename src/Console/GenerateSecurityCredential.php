<?php

namespace JamesKabz\MpesaPkg\Console;

use Illuminate\Console\Command;
use JamesKabz\MpesaPkg\Services\MpesaHelper;

class GenerateSecurityCredential extends Command
{
    protected $signature = 'mpesa:security-credential {password? : Initiator password}';
    protected $description = 'Generate M-Pesa SecurityCredential using the configured certificate';

    public function handle(): int
    {
        $password = $this->argument('password');

        if (! $password) {
            $password = $this->secret('Initiator password');
        }

        if (! $password) {
            $this->error('Initiator password is required.');
            return self::FAILURE;
        }

        try {
            $credential = MpesaHelper::generateSecurityCredential($password);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->line($credential);
        return self::SUCCESS;
    }
}
