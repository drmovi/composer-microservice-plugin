<?php

namespace Drmovi\ComposerMicroservice;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Drmovi\ComposerMicroservice\Handlers\AddPackagesToMicroservices;
use Drmovi\ComposerMicroservice\Handlers\Context;
use Drmovi\ComposerMicroservice\Handlers\RemovePreCommandHandler;
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
            InstallerEvents::PRE_OPERATIONS_EXEC => 'onPreOperationsExec',
            ScriptEvents::POST_UPDATE_CMD => [
                ['onPostInstallOrUpdate', 0]
            ],
            ScriptEvents::POST_INSTALL_CMD => [
                ['onPostInstallOrUpdate', 0]
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
            'remove' => (new RemovePreCommandHandler($this->composer, $this->io, $event))->handle(),
            default => null,
        };
    }

    public function onPostInstallOrUpdate(Event $event): void
    {
        match (Context::getCommand()) {
            'require' => (new AddPackagesToMicroservices($this->composer, $this->io, $event))->handle(),
            default => null,
        };

    }

    public function onPreOperationsExec(InstallerEvent $event): void
    {
        $inputPackages = Context::getInput()->getArgument('packages');
        $packages = [];
        foreach ($inputPackages as $inputPackage) {
            $inputPackage = explode(':', $inputPackage);
            $lockedPackage = $event->getComposer()->getLocker()->getLockedRepository()->findPackage($inputPackage[0], '*');
            if ($lockedPackage) {
                $packages[$inputPackage[0]] = $this->getMajorMinorVersion($lockedPackage->getVersion());
            }
        }
        foreach ($event->getTransaction()->getOperations() as $operation) {
            if ($operation instanceof InstallOperation) {
                $packages[$operation->getPackage()->getPrettyName()] = $this->getMajorMinorVersion($operation->getPackage()->getVersion());
            }
        }
        Context::setPackages($packages);
    }

    private function getMajorMinorVersion(string $version): string
    {
        $version = explode('.', $version);
        $result = '^'.$version[0];
        if (isset($version[1])) {
            $result .= '.' . $version[1];
        }
        return $result;
    }
}
