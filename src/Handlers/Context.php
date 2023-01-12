<?php

namespace Drmovi\ComposerMicroservice\Handlers;

use Symfony\Component\Console\Input\InputInterface;

class Context
{
    static int $microservice = 0;
    static ?string $command = null;
    static ?InputInterface $input = null;

    public static function setMicroservice(int $value): void
    {
        self::$microservice = $value;
    }

    public static function getMicroservice(): int
    {
        return self::$microservice;
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
}
