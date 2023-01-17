<?php

namespace Drmovi\ComposerMicroservice\Handlers;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PreCommandRunEvent;
use Drmovi\ComposerMicroservice\Traits\Microservice;

class RemovePackagesFromMicroservices
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
        $packages = $this->event->getInput()->getArgument('packages');
        $microservices = Context::getMicroservices();
        $removePackagesInRoot = in_array('root', $microservices) || in_array('all', $microservices);
        if (in_array('all', $microservices)) {
            $microservices = $this->getMicroservices();
        }
        $microservices = array_diff($microservices, ['root', 'all']);
        $mainComposerFileContent = $this->getMainComposerFileContent(false);
        $microservicesComposerFilesContent = [];
        foreach ($microservices as $microservice) {
            $microservicesComposerFilesContent[$microservice] = $this->getMicroserviceComposerFileContent($microservice, false);
        }

        try {
            foreach ($microservicesComposerFilesContent as $microservice => $microserviceComposerFileContent) {
                $this->removeFromMicroservice(
                    $microservice,
                    json_decode($microserviceComposerFileContent, true),
                    $packages
                );
            }
            if ($removePackagesInRoot) {
                $decodedMainComposerFileContent = json_decode($mainComposerFileContent, true);
                foreach ($packages as $package) {
                    unset($decodedMainComposerFileContent['require'][$package]);
                    unset($decodedMainComposerFileContent['require-dev'][$package]);
                }
                $this->setMainComposerFileContent($decodedMainComposerFileContent);
            }
            $this->installComposer();
        } catch (\Exception $e) {
            if (!$removePackagesInRoot) {
                $this->setMainComposerFileContent($mainComposerFileContent);
            }
            foreach ($microservicesComposerFilesContent as $microservice => $microserviceComposerFileContent) {
                $this->setMicroserviceFileContent($microservice, $microserviceComposerFileContent);
            }
            $this->io->writeError($e->getMessage());
        }

    }


    private function removeFromMicroservice(
        string $microservice,
        array  $content,
        array  $packages,
    ): void
    {
        foreach ($packages as $package) {
            unset($content['require'][$package]);
            unset($content['require-dev'][$package]);
        }
        $this->setMicroserviceFileContent($microservice, $content);
    }


}
