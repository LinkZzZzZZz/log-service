<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Http\Contracts\ResponseFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;

abstract class BaseController extends Controller
{
    public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory,
    ) {
    }
}
