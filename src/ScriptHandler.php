<?php
namespace Supsign\ComposerZip;

use Composer\Config;
use Composer\Package\PackageInterface;
use Composer\Script\Event;

class ScriptHandler
{
    public static function createZip(Event $event, Filesystem $filesystem = null)
    {
        /** @var PackageInterface $package */
        $package = $event->getComposer()->getPackage();
        /** @var Config $config */
        $config = $event->getComposer()->getConfig();
        $archives = (array) $package->getExtra()['archives'] ? (array) $package->getExtra()['archives'] : [];
        $vendorPath = $config->get('vendor-dir');
        $rootPath = dirname($vendorPath);
        $filesystem = $filesystem ?: new Filesystem;

        foreach ($archives as $sourceRelativePath => $targetRelativePath) {
            // Remove trailing slash that can cause the target to be deleted by ln.
            $targetRelativePath = rtrim($targetRelativePath, '/');

            $sourceAbsolutePath = sprintf('%s/%s', $rootPath, $sourceRelativePath);
            $targetAbsolutePath = sprintf('%s/%s', $rootPath, $targetRelativePath);
            if (!file_exists($sourceAbsolutePath)) {
                continue;
            }

            if (file_exists($targetAbsolutePath)) {
                $filesystem->remove($targetAbsolutePath);
            }

            $event->getIO()->write(sprintf(
                '<info>Creating symlink for "%s" into "%s"</info>',
                $sourceRelativePath,
                $targetRelativePath
            ));

            $targetDirname = dirname($targetAbsolutePath);
            $sourceRelativePath = substr($filesystem->makePathRelative($sourceAbsolutePath, $targetDirname), 0, -1);

            $command = 'ln -s';
            if (!$event->isDevMode()) {
                $command = 'cp -r';
            }

            // Escape spaces in path.
            $targetDirname = preg_replace('/(?<!\\))[ ]/', '\\ ', $targetDirname);

            // Build and execute final command.
            $mkdirCmd = 'mkdir -p ' . $targetDirname;
            exec($mkdirCmd);
            $cmd = 'cd ' . $targetDirname . ' && ' . $command . ' ' . $sourceRelativePath . ' ' . basename($targetRelativePath);
            exec($cmd);

        }
    }
}
