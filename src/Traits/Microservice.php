<?php

namespace Drmovi\ComposerMicroservice\Traits;

trait Microservice
{
    protected string $microservice_path = 'microservices';

    private function getMicroservices(): array
    {
        $directories = glob(getcwd() . '/' . $this->microservice_path . '/*', GLOB_ONLYDIR);
        $microservices = [];
        foreach ($directories as $directory) {
            $microservices[] = basename($directory);
        }
        return $microservices;
    }
}
