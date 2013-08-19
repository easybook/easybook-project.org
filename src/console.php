<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

$console = new Application('easybook-project.org website', '2.0');
$console
    ->register('doc:update-contents')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $docDir = __DIR__.'/../cache/doc';
        $repoCloneDir = $docDir.'/tmp';

        $fs = new Filesystem();

        if (!$fs->exists($docDir)) {
            $fs->mkdir($docDir);
        }

        if ($fs->exists($repoCloneDir)) {
            $fs->remove($repoCloneDir);
        }
        $fs->mkdir($repoCloneDir);

        $process = new Process(sprintf(
            "git clone git@github.com:javiereguiluz/easybook.git %s",
            $repoCloneDir
        ));
        $process->run();

        $fs->mirror(
            $repoCloneDir."/doc/easybook-doc-en",
            $docDir
        );

        $fs->remove($repoCloneDir);
    })
;

$console
    ->register('doc:publish')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        $bookDir = __DIR__.'/../cache/';
        $docDir  = __DIR__.'/../templates/documentation/';
        $editionName = 'easybook-project.org';

        $process = new Process(sprintf(
            "easybook publish --dir=%s doc %s",
            $bookDir, $editionName
        ));
        $process->run();

        $fs = new Filesystem();
        $fs->mirror(
            $bookDir."/doc/Output/".$editionName."/book",
            $docDir
        );
    })
;

return $console;
