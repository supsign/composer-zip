<?php

namespace Supsign\ComposerZip;

use Composer\Config;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Alchemy\Zippy as Zippy;


class ScriptHandler
{

    public static function zip_files(Event $event, $source, $destination)
    {
        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZIPARCHIVE::CREATE) === true) {
            $source = realpath($source);
            if (is_dir($source)) {
                $iterator = new \RecursiveDirectoryIterator($source);
                $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($files as $file) {
                    $file = realpath($file);
                    if (is_dir($file)) {
                        $zip->addEmptyDir(str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
                    } elseif (is_file($file)) {
                        $zip->addFile($file, str_replace($source . DIRECTORY_SEPARATOR, '', $file));
                    }
                }
            } elseif (is_file($source)) {
                $zip->addFile($source, basename($source));
            }
        }
        $event->getIO()->write(sprintf('<info>Created/Updated "%s" in "%s"<info>', $source, $destination));
        return $zip->close();
    }

    public static function createZip(Event $event, Filesystem $filesystem = null)
    {
        /** @var PackageInterface $package */
        $package = $event->getComposer()->getPackage();
        /** @var Config $config */
        $config = $event->getComposer()->getConfig();
        $archives = (array) $package->getExtra()['archives'] ? (array) $package->getExtra()['archives'] : [];
        $outputFolder = (array) $package->getExtra()['outputFolder'] ? (array) $package->getExtra()['outputFolder'] : [];
        $vendorPath = $config->get('vendor-dir');
        $rootPath = dirname($vendorPath);
        //    $filesystem = $filesystem ?: new Filesystem;


        //  Create Output Folder if not exist
        $createOutputFolderRealPath = realpath($outputFolder['dir']);

        if ($createOutputFolderRealPath !== false and is_dir($createOutputFolderRealPath)) {
            $event->getIO()->write(sprintf('<info>Folder "%s" already exists.<info>', $outputFolder['dir']));
        } else {
            $createOutputFolderCmd = "mkdir " . $outputFolder['dir'];
            exec($createOutputFolderCmd);
            $event->getIO()->write(sprintf('<info>Folder "%s" was created.<info>', $outputFolder['dir']));
        }



        foreach ($archives as $OutputZipName => $RelativeFolderToBeZipped) {

         //   $source = realpath($RelativeFolderToBeZipped);
        //  $fileName = $OutputZipName . '.zip';
         //   $destination = realpath($outputFolder['dir']) . DIRECTORY_SEPARATOR . $fileName;
         //   self::zip_files($event, $source, $destination);


         //   $zippy = Zippy::load();


         print_r(get_declared_classes());


        }
    }
}
