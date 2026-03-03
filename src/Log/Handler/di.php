<?php

declare(strict_types=1);

namespace App\Log\Handler;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->set(IngestLogsHandler::class);
};
