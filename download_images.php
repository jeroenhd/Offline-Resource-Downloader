<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 2-2-17
 * Time: 18:55
 */
include_once('download_and_replace.php');


if (php_sapi_name() !== "cli")
{
    error_log("Please run this program from the CLI!");
    die();
}

if ($argc < 3)
{
    error_log("Please pass the forum directory and the resource directory as the command line argument");
    die();
}

function enumerate_files(string $dir, $outputDir)
{
    /** @var string[] $files */
    $files = scandir($dir);

    foreach($files as $file)
    {
        if ($file === "." || $file === "..")
            continue;

        /** @var string $path */
        $path = "$dir/$file";

        if (is_dir($path))
        {
            enumerate_files($path, $outputDir);
        } else {
            echo "Processing $path..." . PHP_EOL;
            downloadAndReplace($path, $outputDir);
        }
    }
}

enumerate_files($argv[1], $argv[2]);