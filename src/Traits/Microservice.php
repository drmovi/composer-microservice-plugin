<?php

namespace Drmovi\ComposerMicroservice\Traits;

use Composer\Console\Application;
use Drmovi\ComposerMicroservice\Enums\FilterMicroservicesPackages;
use Symfony\Component\Console\Input\ArgvInput;

trait Microservice
{
    protected string $microservice_path = 'microservices';

    private function getMicroservices(FilterMicroservicesPackages $filter = null): array
    {
        $directories = glob(getcwd() . '/' . $this->microservice_path . '/*', GLOB_ONLYDIR);
        $microservices = [];
        foreach ($directories as $directory) {
            if ($filter) {
                if ($this->checkMicroservicePackage(basename($directory), $filter)) {
                    $microservices[] = basename($directory);
                }
            } else {
                $microservices[] = basename($directory);
            }
        }
        return $microservices;
    }

    private function getMicroserviceChoices(FilterMicroservicesPackages $filter = null): array
    {
        $microservices = ['all'];
        if ($filter) {
            if ($this->checkFilter($this->getMainComposerFileContent(), $filter)) {
                $microservices[] = 'root';
            }
        } else {
            $microservices[] = 'root';
        }
        $microservices = [...$microservices, ...$this->getMicroservices($filter)];

        if ($this->event->getInput()->getOption('no-interaction')) {
            return [0];
        }
        $choices = $this->io->select(question: 'Please Select a microservice', choices: $microservices, default: 0, multiselect: true);
        foreach ($choices as $choice) {
            if ($choice > (count($microservices) - 1) || $choice < 0) {
                $this->io->write('Invalid choice');
                return $this->getMicroserviceChoices($filter);
            }
        }
        $result = [];
        foreach ($choices as $item) {
            $result[] = $microservices[$item];
        }

        return $result;

    }

    private function getMicroserviceComposerFileContent(string $name, bool $asArray = true): string|array
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


    private function checkMicroservicePackage(string $directory, FilterMicroservicesPackages $filter): bool
    {
        return $this->checkFilter($this->getMicroserviceComposerFileContent(basename($directory)), $filter);
    }

    private function checkFilter(array $composerFileContent, FilterMicroservicesPackages $filter): bool
    {
        foreach ($this->event->getInput()->getArgument('packages') as $package) {
            ;
            $package = explode(':', $package);
            $package = $package[0];
            if ((isset($composerFileContent['require'][$package]) || isset($composerFileContent['require-dev'][$package])) && $filter === FilterMicroservicesPackages::EXISTING_ONLY) {
                return true;
            }
        }
        return false;
    }

    private function installComposer(bool $autoExit = false): void
    {
        $input = new ArgvInput(['composer', 'install']);
        $application = new Application();
        $application->setAutoExit($autoExit);
        $application->run($input);
    }

}
