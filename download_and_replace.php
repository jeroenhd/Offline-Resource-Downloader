<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 2-2-17
 * Time: 18:57
 */
include_once("Resource.php");

function downloadAndReplace(string $fileName, string $outputDirectory)
{
    $html = file_get_contents($fileName);
    $length = strlen($html);

    $relativeOutputDirectory = getRelativePath($fileName, $outputDirectory);

    echo "Read $length characters from $fileName; output will be stored in $outputDirectory" . PHP_EOL;

    $links = getExternalResourceUrls($html);

    foreach($links as $link)
    {
        echo "\tDownloading resource " . $link->getOriginalUrl() . " (" . $link->getType() . ")" . PHP_EOL;
        $link->Download($outputDirectory);
        $html = str_replace($link->getOriginalUrl(), $link->getFullFilePath($relativeOutputDirectory), $html);
    }

    file_put_contents($fileName, $html);
}


function getRelativePath($basePath, $targetPath)
{
    if ($basePath === $targetPath) {
        return '';
    }
    $sourceDirs = explode('/', isset($basePath[0]) && '/' === $basePath[0] ? substr($basePath, 1) : $basePath);
    $targetDirs = explode('/', isset($targetPath[0]) && '/' === $targetPath[0] ? substr($targetPath, 1) : $targetPath);
    array_pop($sourceDirs);
    $targetFile = array_pop($targetDirs);
    foreach ($sourceDirs as $i => $dir) {
        if (isset($targetDirs[$i]) && $dir === $targetDirs[$i]) {
            unset($sourceDirs[$i], $targetDirs[$i]);
        } else {
            break;
        }
    }
    $targetDirs[] = $targetFile;
    $path = str_repeat('../', count($sourceDirs)).implode('/', $targetDirs);
    // A reference to the same base directory or an empty subdirectory must be prefixed with "./".
    // This also applies to a segment with a colon character (e.g., "file:colon") that cannot be used
    // as the first segment of a relative-path reference, as it would be mistaken for a scheme name
    // (see http://tools.ietf.org/html/rfc3986#section-4.2).
    return '' === $path || '/' === $path[0]
    || false !== ($colonPos = strpos($path, ':')) && ($colonPos < ($slashPos = strpos($path, '/')) || false === $slashPos)
        ? "./$path" : $path;
}

/**
 * Get the resources for the
 * @param $html string The HTML for the page to find external resources for
 * @return Resource[] The resources found
 */
function getExternalResourceUrls($html)
{
    $result = array();

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);

    $images = $doc->getElementsByTagName("img");
    foreach($images as $image)
    {
        /** @var $image DOMElement */
        $source = $image->getAttribute("src");
        if (substr($source, 0, '4') === "http")
            $result[]=new Resource($source, "img");
    }
    $links = $doc->getElementsByTagName("link");
    foreach($links as $link)
    {
        /** @var $link DOMElement */
        if ("stylesheet" === $link->getAttribute("rel") && substr($link->getAttribute("href"), 0, '4') === "http")
        {
            $result[]=new Resource($link->getAttribute("href"), "link");
        }
    }
    $scripts = $doc->getElementsByTagName("script");
    foreach($scripts as $script)
    {
        if (NULL != $script->getAttribute("src") && substr($script->getAttribute("src"), 0, '4') === "http")
        {
            $result[]=new Resource($link->getAttribute("src"), "script");
        }
    }

    return $result;
}