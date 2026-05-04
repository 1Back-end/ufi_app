<?php

namespace App\Console\Commands;

use App\Services\SessionCaisseService;
use Illuminate\Console\Command;

class AutoCloseSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-close-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fermeture automatique des sessions de caisse';

    /**
     * Execute the console command.
     */
    public function handle(SessionCaisseService $service)
    {
        $service->autoClose(null);

        $this->info('Sessions fermées automatiquement avec succès');
    }
}
