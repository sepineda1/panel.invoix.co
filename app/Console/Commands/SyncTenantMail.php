<?php

namespace App\Console\Commands;

use App\Services\Tenants\TenantMailSync;
use Illuminate\Console\Command;

class SyncTenantMail extends Command
{
    protected $signature = 'panel:sync-mail';

    protected $description = 'Copia el correo del panel al environment de cada empresa y reinicia las que están en línea si cambió.';

    public function handle(TenantMailSync $sync): int
    {
        $updated = $sync->run();
        $this->info('Empresas con correo actualizado: '.$updated.'.');

        return self::SUCCESS;
    }
}
