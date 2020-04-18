<?php
namespace Supsign\ComposerZip;

use Composer\Config;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;


class ScriptHandler
{
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

        if ($createOutputFolderRealPath !== false AND is_dir($createOutputFolderRealPath)) {
            $event->getIO()->write(sprintf('<info>Folder "%s" already exists.<info>',$outputFolder['dir']));
        }else{
            $createOutputFolderCmd = "mkdir ".$outputFolder['dir'];
            exec($createOutputFolderCmd);
            $event->getIO()->write(sprintf('<info>Folder "%s" was created.<info>',$outputFolder['dir']));
        }


     //   var_dump($archives);

        foreach ($archives as $OutputZipName => $RelativeFolderToBeZipped) {

            $source = realpath($RelativeFolderToBeZipped);

            $zip = new \ZipArchive();
            $zip->open($outputFolder['dir'].'/'.$OutputZipName.'.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            if (is_dir($source)) {
                $iterator = new \RecursiveDirectoryIterator($source);
                // skip dot files while iterating 
                $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
                var_dump($files);
                foreach ($files as $file) {
                    $file = realpath($file);
                    if (is_dir($file)) {
                        $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                    } else if (is_file($file)) {
                        $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                    }
                }
            } else if (is_file($source)) {
                $zip->addFromString(basename($source), file_get_contents($source));
            }



            $zip->close();


            // Remove trailing slash that can cause the target to be deleted by ln.
     //       $targetRelativePath = rtrim($targetRelativePath, '/');

      //      $sourceAbsolutePath = sprintf('%s/%s', $rootPath, $sourceRelativePath);
      //      $targetAbsolutePath = sprintf('%s/%s', $rootPath, $targetRelativePath);
      //      if (!file_exists($sourceAbsolutePath)) {
      //          continue;
      //      }

      //      if (file_exists($targetAbsolutePath)) {
      //          $filesystem->remove($targetAbsolutePath);
      //      }

      //      $event->getIO()->write(sprintf(
      //          '<info>Creating symlink for "%s" into "%s"</info>',
      //          $sourceRelativePath,
       //         $targetRelativePath
       //     ));

       //     $targetDirname = dirname($targetAbsolutePath);
        //    $sourceRelativePath = substr($filesystem->makePathRelative($sourceAbsolutePath, $targetDirname), 0, -1);

        //    $command = 'ln -s';
        //    if (!$event->isDevMode()) {
        //        $command = 'cp -r';
        //    }

            // Escape spaces in path.
       //     $targetDirname = preg_replace('/(?<!\\))[ ]/', '\\ ', $targetDirname);

            // Build and execute final command.
       //     $mkdirCmd = 'mkdir -p ' . $targetDirname;
       //     exec($mkdirCmd);
       //     $cmd = 'cd ' . $targetDirname . ' && ' . $command . ' ' . $sourceRelativePath . ' ' . basename($targetRelativePath);
       //     exec($cmd);

        }
        
    }
}
