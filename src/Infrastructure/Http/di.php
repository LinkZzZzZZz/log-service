<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Http\Contracts\ResponseFactoryInterface;
use App\Infrastructure\Http\EventListener\ApiExceptionListener;
use App\Infrastructure\Http\Factory\ResponseFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;

return static function (ContainerConfigurator $di): void {
    $di->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->set(ResponseFactoryInterface::class, ResponseFactory::class)
        ->set(ApiExceptionListener::class)
        ->set('property_info.phpdoc_extractor', PhpDocExtractor::class)
            ->tag('property_info.type_extractor', ['priority' => -100]);
};
