<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use JsonException;

final class NoPrivateConstructorPlugin implements PluginInterface, EventSubscriberInterface
{
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPackageUpdate',
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @throws JsonException
     */
    public function onPackageInstall(PackageEvent $event): void
    {
        if (!$this->isComposerWorkingOn('easycorp/easyadmin-bundle', $event) && !$this->isComposerWorkingOn('easycorp/easyadmin-no-final-plugin', $event) && !$this->isComposerWorkingOn('bytespin/easyadmin-no-private-plugin', $event)) {
            return;
        }

        $this->updatePrivateConstructorFromAllEasyAdminClasses();
    }

    /**
     * @throws JsonException
     */
    public function onPackageUpdate(PackageEvent $event): void
    {
        if (!$this->isComposerWorkingOn('easycorp/easyadmin-bundle', $event)) {
            return;
        }

        $this->updatePrivateConstructorFromAllEasyAdminClasses();
    }

    /**
     * @throws JsonException
     */
    public function updatePrivateConstructorFromAllEasyAdminClasses(): void
    {
        $vendorDirPath = $this->getVendorDirPath();
        $easyAdminDirPath = $vendorDirPath.'/easycorp/easyadmin-bundle';
        foreach ($this->getFilePathsOfAllEasyAdminClasses($easyAdminDirPath) as $filePath) {
            file_put_contents(
                $filePath,
                str_replace('private function __construct', 'public function __construct', file_get_contents($filePath)),
                flags: \LOCK_EX
            );

            if (str_contains($filePath, 'TemplateRegistry.php')) {
                file_put_contents(
                    $filePath,
                    str_replace('private array $templates', 'public array $templates', file_get_contents($filePath)),
                    flags: \LOCK_EX
                );
            }

            if (str_contains($filePath, 'Resources/config/service.php')) {
                file_put_contents(
                    $filePath,
                    str_replace('->defaults()->private()', '->defaults()->public()', file_get_contents($filePath)),
                    flags: \LOCK_EX
                );
            }
        }


        $this->io->write('Updated all EasyAdmin PHP files to make constructors public');
    }

    private function isComposerWorkingOn(string $packageName, PackageEvent $event): bool
    {
        /** @var PackageInterface|null $package */
        $package = null;

        foreach ($event->getOperations() as $operation) {
            if ('install' === $operation->getOperationType()) {
                /** @var InstallOperation $operation */
                $package = $operation->getPackage();
            } elseif ('update' === $operation->getOperationType()) {
                /** @var UpdateOperation $operation */
                $package = $operation->getInitialPackage();
            }
        }

        return $packageName === $package?->getName();
    }

    /**
     * @throws JsonException
     */
    private function getVendorDirPath(): string
    {
        $composerJsonFilePath = Factory::getComposerFile();
        $composerJsonContents = json_decode(file_get_contents($composerJsonFilePath), associative: true, flags: JSON_THROW_ON_ERROR);
        $projectDir = dirname(realpath($composerJsonFilePath));

        return $composerJsonContents['config']['vendor-dir'] ?? $projectDir.'/vendor';
    }

    /**
     * @return iterable Returns the file paths of all PHP files that contain EasyAdmin classes
     */
    private function getFilePathsOfAllEasyAdminClasses(string $easyAdminDirPath): iterable
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($easyAdminDirPath, \FilesystemIterator::SKIP_DOTS)) as $filePath) {
            if (is_dir($filePath) || !str_ends_with($filePath, '.php')) {
                continue;
            }

            yield $filePath;
        }
    }
}
