<?php

namespace Drmovi\ComposerMicroservice\Handlers;

use Composer\Composer;
use Composer\Console\Application;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Drmovi\ComposerMicroservice\Traits\Microservice;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;

class RequirePostPostInstallOrUpdateHandler
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
        $microservice = Context::getMicroservice();
        if ($microservice === 0 || Context::getCommand() !== 'require') {
            return;
        }
        $microserviceName = $this->getMicroservices()[$microservice - 1];
        $mainComposerFileContent = $this->getMainComposerFileContent(false);
        $microserviceComposerFileContent = $this->getMicroservicesComposerFileContent($microserviceName,false);

        try {
            $this->moveMicroservice(
                $microserviceName,
                json_decode($mainComposerFileContent,true),
                json_decode($microserviceComposerFileContent,true)
            );
            $this->updateComposer();
        } catch (\Exception $e) {
            $this->setMainComposerFileContent($mainComposerFileContent);
            $this->setMicroserviceFileContent($microserviceName, $microserviceComposerFileContent);
            $this->io->writeError($e->getMessage());
        }

    }


    private function moveMicroservice(
        string $microserviceName,
        array  $mainComposerFileContent,
        array  $microserviceComposerFileContent
    ): void
    {
        $packages = Context::getInput()->getArgument('packages');
        $requireDev = Context::getInput()->getOption('dev');
        $data = [];
        $key = $requireDev ? 'require-dev' : 'require';
        foreach ($mainComposerFileContent[$key] as $package => $value) {
            if (in_array($package, $packages)) {
                $data[$package] = $value;
                unset($mainComposerFileContent[$key][$package]);
            }
        }
        if (empty($data)) {
            return;
        }
        $microserviceComposerFileContent[$key] = array_merge($microserviceComposerFileContent[$key], $data);
        $this->setMainComposerFileContent($mainComposerFileContent);
        $this->setMicroserviceFileContent($microserviceName, $microserviceComposerFileContent);
    }

    private function updateComposer(): void
    {
        $input = new ArgvInput(['composer', 'update']);
        $application = new Application();
        $application->setAutoExit(false);
        $application->run($input);
    }

    private function getMicroservicesComposerFileContent(string $name, bool $asArray = true): string|array
    {
        $data = file_get_contents(getcwd() . '/' . $this->microservice_path . '/' . $name . '/composer.json');
        return $asArray ? json_decode($data, true) : $data;
    }

    private function getMainComposerFileContent(bool $asArray = true): string|array
    {
        $data = file_get_contents(getcwd() . '/composer.json');
        return $asArray ? json_decode($data, true) : $data;
    }


    private function setMainComposerFileContent(array|string $content): void
    {
        $data = is_array($content) ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $content;
        file_put_contents(getcwd() . '/composer.json', $data);
    }


    private function setMicroserviceFileContent(string $choice, array|string $content): void
    {
        $data = is_array($content) ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $content;
        file_put_contents(getcwd() . '/' . $this->microservice_path . '/' . $choice . '/composer.json', $data);
    }
}
