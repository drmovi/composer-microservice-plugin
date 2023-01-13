<?php

namespace Drmovi\ComposerMicroservice\Handlers;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Drmovi\ComposerMicroservice\Traits\Microservice;

class AddPackagesToMicroservices
{

    use Microservice;

    public function __construct(
        protected readonly Composer    $composer,
        protected readonly IOInterface $io,
        protected readonly Event       $event,
    )
    {
    }


    public function handle(): void
    {
        $microservices = Context::getMicroservices();
        if (empty($microservices)) {
            return;
        }
        $preservePackageInRoot = in_array('root', $microservices) || in_array('all', $microservices);
        if (in_array('all', $microservices)) {
            $microservices = $this->getMicroservices();
        }
        $mainComposerFileContent = $this->getMainComposerFileContent(false);
        $microservicesComposerFileContent = [];
        foreach ($microservices as $microservice) {
            $microservicesComposerFileContent[$microservice] = $this->getMicroserviceComposerFileContent($microservice, false);
        }

        try {
            foreach ($microservicesComposerFileContent as $microservice => $microserviceComposerFileContent) {
                $this->moveMicroservice(
                    $microservice,
                    json_decode($microserviceComposerFileContent, true)
                );
            }
            if (!$preservePackageInRoot) {
                $decodedMainComposerFileContent = json_decode($mainComposerFileContent, true);
                $requireDev = Context::getInput()->getOption('dev');
                $key = $requireDev ? 'require-dev' : 'require';
                foreach (Context::getPackages() as $package) {
                    unset($decodedMainComposerFileContent[$key][$package]);
                }
                $this->setMainComposerFileContent($decodedMainComposerFileContent);
            }


            $this->installComposer();
        } catch (\Exception $e) {
            if (!$preservePackageInRoot) {
                $this->setMainComposerFileContent($mainComposerFileContent);
            }
            foreach ($microservicesComposerFileContent as $microservice => $microserviceComposerFileContent) {
                $this->setMicroserviceFileContent($microservice, $microserviceComposerFileContent);
            }
            $this->io->writeError($e->getMessage());
        }

    }


    private function moveMicroservice(
        string $microserviceName,
        array  $microserviceComposerFileContent
    ): void
    {
        $packages = Context::getPackages();
        $packagesNames = array_keys($packages);
        $requireDev = Context::getInput()->getOption('dev');
        $data = [];
        $key = $requireDev ? 'require-dev' : 'require';
        foreach ($packages as $package => $value) {
            if (in_array($package, $packagesNames)) {
                $data[$package] = $value;
            }
        }
        if (empty($data)) {
            return;
        }
        $microserviceComposerFileContent[$key] = array_merge($microserviceComposerFileContent[$key], $data);
        $this->setMicroserviceFileContent($microserviceName, $microserviceComposerFileContent);
    }

}
