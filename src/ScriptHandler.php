<?php

namespace Supsign\ComposerZip;

use Composer\Config;
use Composer\Package\PackageInterface;
use Alchemy\Zippy\Zippy;




class ScriptHandler
{

    private static function run_npm_scripts($event, $dir)
    {
        $event->getIO()->write(sprintf('<info></info>'));
        $event->getIO()->write(sprintf('<info>*** NPM-Install-Process Start ***</info>'));
        if (!file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')) {
            $event->getIO()->write(sprintf('<info>Folder "%s" does not include "package.json"<info>', $dir));
            return;
        }
        exec('cd ' . $dir . ' && npm i --loglevel=error');
        exec('cd ' . $dir . ' && npm run composer-build --if-present --loglevel=error');
        $event->getIO()->write(sprintf('<info>*** NPM-Install-Process Finished***</info>'));
        $event->getIO()->write(sprintf('<info></info>'));


        return;
    }



    private static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        self::rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }




    private static function custom_copy($src, $dst)
    {

        // open the source directory 
        $dir = opendir($src);

        // Make the destination directory if not exist 
        @mkdir($dst);

        // Loop through the files in source directory 
        while ($file = readdir($dir)) {

            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {

                    // Recursively calling custom copy function 
                    // for sub directory  
                    self::custom_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }




    public static function createZip($event)
    {
        /** @var PackageInterface $package */
        $package = $event->getComposer()->getPackage();
        /** @var Config $config */
        $config = $event->getComposer()->getConfig();
        $archives = (array) $package->getExtra()['archives'] ? (array) $package->getExtra()['archives'] : [];
        $outputFolder = (array) $package->getExtra()['outputFolder'] ? (array) $package->getExtra()['outputFolder'] : [];
        $vendorPath = $config->get('vendor-dir');
        $rootPath = dirname($vendorPath);


        // Whole Building Process (just copy it to get rid of symbolic Links Problem)

        $event->getIO()->write(sprintf('<info></info>'));
        $event->getIO()->write(sprintf('<info>*** Copy-Process Start ***</info>'));

        if (!file_exists($outputFolder['build'])) {
            mkdir($outputFolder['build'], 0777, true);
            $event->getIO()->write(sprintf('<info>Created Folder "%s"<info>', $outputFolder['build']));
        } else {
            $event->getIO()->write(sprintf('<info>Folder "%s" already exists. This is not good!<info>', $outputFolder['build']));
        }

        $event->getIO()->write(sprintf('<info>*** Copy-Process Finished ***</info>'));
        $event->getIO()->write(sprintf('<info></info>'));



        $file = $outputFolder['build'] . '/del.txt';

        if (!is_file($file)) {
            $contents = 'This file is for creating an empty zip';           // Some simple example content.
            file_put_contents($file, $contents);     // Save our content to the file.
        }


        foreach ($archives as $OutputZipName => $RelativeFolderToBeZipped) {

            $copy_destination = $outputFolder['build'] . strstr($RelativeFolderToBeZipped, '/');

            $copy_destination_root_path = str_replace(strrchr($copy_destination, '/'), "", $copy_destination);


            if (!file_exists($copy_destination_root_path)) {
                mkdir($copy_destination_root_path, 0777, true);
                $event->getIO()->write(sprintf('<info>Created Folder "%s"<info>', $copy_destination_root_path));
            } else {
                $event->getIO()->write(sprintf('<info>Folder "%s" already exists. This is not good!<info>', $copy_destination_root_path));
            }

            self::run_npm_scripts($event, $RelativeFolderToBeZipped);
            self::custom_copy($RelativeFolderToBeZipped, $copy_destination);
        }



        //  Create Output Folder if not exist
        $createOutputFolderRealPath = realpath($outputFolder['archives']);

        if ($createOutputFolderRealPath !== false and is_dir($createOutputFolderRealPath)) {
            $event->getIO()->write(sprintf('<info>Folder "%s" already exists. This is totaly fine!<info>', $outputFolder['archives']));
        } else {
            $createOutputFolderCmd = "mkdir " . $outputFolder['archives'];
            exec($createOutputFolderCmd);
            $event->getIO()->write(sprintf('<info>Folder "%s" was created.<info>', $outputFolder['archives']));
        }


        // Create Zip
        foreach ($archives as $OutputZipName => $RelativeFolderToBeZipped) {

            $fileName = $OutputZipName . '.zip';


            $zippy = Zippy::load();



            $subfolder = str_replace('/', '', strrchr($copy_destination, '/'));

            $filesToZip = $files = array_diff(scandir($copy_destination), array('.', '..'));

            foreach ($filesToZip as &$value) {
                $value = $copy_destination . '/' . $value;
            }
            unset($value);




            $archive = $zippy->create($outputFolder['archives'] . '/' . $fileName, array(
                'Buildtmp/del.txt'
            ), true);




            $archive->addMembers($filesToZip, true);
            $archive->removeMembers('del.txt');
            $event->getIO()->write(sprintf('<info>Created "%s"</info>', $outputFolder['archives'] . '/' . $fileName));
        }
        self::rrmdir($outputFolder['build']);
        $event->getIO()->write(sprintf('<info>Cleaned up and removed Folder "%s"</info>', $outputFolder['archives']));
    }
}
