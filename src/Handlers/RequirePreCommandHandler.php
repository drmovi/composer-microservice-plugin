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
        $microservices = ['root', ...$this->getMicroservices()];
        Context::setMicroservice($this->getMicroserviceChoice($microservices));
    }

    private function getMicroserviceChoice(array $microservices): int
    {
        if ($this->event->getInput()->getOption('no-interaction')) {
            return 0;
        }
        $choice = $this->io->select('Please Select a microservice', $microservices, 0);
        if ($choice > (count($microservices) - 1) || $choice < 0) {
            $this->io->write('Invalid choice');
            return $this->getMicroserviceChoice($microservices);
        }

        return $choice;

    }




}
