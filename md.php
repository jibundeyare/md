<?php

declare(strict_types = 1);

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\UnicodeString;

require __DIR__.'/vendor/autoload.php';

$description = <<<EOT
Generates an HTML file from a Markdown source.

If the Markdown source has a main title (any string beginning with a
single '#' symbol), an interpolation of the string '{{title}}' will be
made in the HTML head file. This makes it possible to set the title of
the HTML document using the Markdown source file.
EOT;

$app = (new SingleCommandApplication())
    ->setName('Markdown to HTML converter')
    ->setDescription($description)
    ->setVersion('1.0.0')
    ->addArgument('markdown', InputArgument::REQUIRED, 'Markdown source')
    ->addOption('head', null, InputOption::VALUE_REQUIRED, 'HTML head')
    ->addOption('tail', null, InputOption::VALUE_REQUIRED, 'HTML tail')
    ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file')
    ->addOption('directory', 'd', InputOption::VALUE_OPTIONAL, 'Output directory')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $sourceFile = $input->getArgument('markdown');
        $headFile = $input->getOption('head');
        $tailFile = $input->getOption('tail');
        $targetFile = $input->getOption('output');
        $targetDirectory = $input->getOption('directory');

        $filesystem = new Filesystem();

        if ($headFile && !$filesystem->exists($headFile)) {
            throw new Exception("The head file \"{$headFile}\" does not exist");
        }

        if ($tailFile && !$filesystem->exists($tailFile)) {
            throw new Exception("The tail file \"{$tailFile}\" does not exist");
        }

        if ($targetDirectory) {
            $targetDirectory = realpath($targetDirectory);

            if ($targetDirectory && !$filesystem->exists($targetDirectory)) {
                throw new Exception("The target directory \"{$targetDirectory}\" does not exist");
            }
        }

        $finder = new Finder();

        $htmlHead = '';

        if ($headFile) {
            $htmlHead = file_get_contents($headFile);
        }

        $htmlTail = '';

        if ($tailFile) {
            $htmlTail = file_get_contents($tailFile);
        }

        // Find the Markdown file
        $sourceFileInfo = pathinfo($sourceFile);
        $finder->files()->in($sourceFileInfo['dirname'])->name($sourceFileInfo['basename']);

        // Check if the Markdown file exists or if more than one file
        // has been found
        if (!$finder->hasResults()) {
            throw new Exception("The source file \"{$sourceFile}\" does not exist");
        } elseif (iterator_count($finder) > 1) {
            throw new Exception("More than one source file \"{$sourceFile}\" found");
        }

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        // Get the Markdown file
        // We have already checked that we have exactly one file
        $files = iterator_to_array($finder);
        // The array_shift() function expects a variable, that is why
        // we do not pass directly the output of iterator_to_array()
        // function
        $file = array_shift($files);

        // Find the title from the Markdown source
        $title = '';
        $contents = new UnicodeString($file->getContents());
        // The match() function only matches the first occurence
        $matches = $contents->match('/# (.*)/');

        if ($matches) {
            // The first item of the array contains the full string with
            // the # symbol
            // We only want the second item of the array, without the #
            // symbol
            $title = $matches[1];
        }

        // If the user didn't specify any target directory, use the
        // source file directory
        if (!$targetDirectory) {
            $targetDirectory = $file->getPathInfo()->getRealPath();
        }

        // If the user didn't specify ant target file, use the source
        // file name as a base
        if (!$targetFile) {
            $directory = $file->getPathInfo()->getRealPath();
            $extension = $file->getExtension();
            $basename = $file->getBasename(".{$extension}");

            $targetFile = "{$basename}.html";
        }

        if ($filesystem->exists("{$targetDirectory}/{$targetFile}")) {
            throw new Exception("The target file \"{$targetDirectory}/{$targetFile}\" already exists");
        }

        $filesystem->touch("{$targetDirectory}/{$targetFile}");

        $htmlHead = str_replace('{{title}}', $title, $htmlHead);

        $filesystem->appendToFile("{$targetDirectory}/{$targetFile}", $htmlHead);

        $markdown = $file->getContents();
        $html = $converter->convertToHtml($markdown);

        $filesystem->appendToFile("{$targetDirectory}/{$targetFile}", $html);
        $filesystem->appendToFile("{$targetDirectory}/{$targetFile}", $htmlTail);
    })
    ->run();

