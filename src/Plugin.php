<?php

namespace Drmovi\ComposerMicroservice;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Drmovi\ComposerMicroservice\Handlers\Context;
use Drmovi\ComposerMicroservice\Handlers\RequirePostPostInstallOrUpdateHandler;
use Drmovi\ComposerMicroservice\Handlers\RequirePreCommandHandler;

class Plugin implements PluginInterface, EventSubscriberInterface
{

    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // TODO: Implement uninstall() method.
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_UPDATE_CMD => [
                ['onRequirePostInstallOrUpdate', 0]
            ],
            ScriptEvents::POST_INSTALL_CMD => [
                ['onRequirePostInstallOrUpdate', 0]
            ],
            PluginEvents::PRE_COMMAND_RUN => 'onPreCommandRun',
        ];
    }

    public function onPreCommandRun(PreCommandRunEvent $event): void
    {
        Context::setInput($event->getInput());
        Context::setCommand($event->getCommand());
        match ($event->getCommand()) {
            'require' => (new RequirePreCommandHandler($this->composer, $this->io, $event))->handle(),
            default => null,
        };
    }

    public function onRequirePostInstallOrUpdate(Event $event): void
    {
        $handler = new RequirePostPostInstallOrUpdateHandler($this->composer, $this->io, $event);
        $handler->handle();
    }
}
