<?php

namespace Drmovi\ComposerMicroservice\Handlers;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PreCommandRunEvent;
use Drmovi\ComposerMicroservice\Traits\Microservice;

class RequirePreCommandHandler
{

    use Microservice;

    public function __construct(
        protected readonly Composer           $composer,
        protected readonly IOInterface        $io,
        protected readonly PreCommandRunEvent $event,
    )
    {
    }

    public function handle(): void
    {
        Context::setMicroservices($this->getMicroserviceChoices());
    }

}
