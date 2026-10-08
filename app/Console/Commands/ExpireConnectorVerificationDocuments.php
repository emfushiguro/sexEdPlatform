<?php

namespace App\Console\Commands;

use App\Services\Connectors\ConnectorVerificationService;
use Illuminate\Console\Command;

class ExpireConnectorVerificationDocuments extends Command
{
    protected $signature = 'connectors:expire-verification-documents';

    protected $description = 'Warn about and expire configured connector verification evidence';

    public function __construct(
        private readonly ConnectorVerificationService $verification,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $asOf = now();
        $warnings = $this->verification->sendExpirationWarnings($asOf);
        $expired = $this->verification->expireDueDocuments($asOf);

        $this->info("Connector verification expiry pass complete: {$warnings} warning(s), {$expired} document(s) expired.");

        return self::SUCCESS;
    }
}
