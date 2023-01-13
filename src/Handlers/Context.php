<?php

namespace Drmovi\ComposerMicroservice\Handlers;

use Symfony\Component\Console\Input\InputInterface;

class Context
{
    static array $microservices = [];
    static ?string $command = null;
    static ?InputInterface $input = null;
    static array $packages = [];

    public static function setMicroservices(array $value): void
    {
        self::$microservices = $value;
    }

    public static function getMicroservices(): array
    {
        return self::$microservices;
    }


    public static function getInput(): ?InputInterface
    {
        return self::$input;
    }

    public static function setInput(InputInterface $input): void
    {
        self::$input = $input;
    }

    public static function getCommand(): ?string
    {
        return self::$command;
    }

    public static function setCommand(string $value): void
    {
        self::$command = $value;
    }
    public static function getPackages(): array
    {
        return self::$packages;
    }

    public static function setPackages(array $packages): void
    {
        self::$packages = $packages;
    }
}
