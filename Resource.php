<?php

/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 4-2-17
 * Time: 22:55
 */
class Resource
{
    /**
     * @var string The original URL
     */
    private $originalUrl;
    /**
     * @var string[] The parsed parts of the URL
     */
    private $urlParts;
    /**
     * @var string The type of the resource. Either a, link or script
     */
    private $type;

    /**
     * Resource constructor.
     * @param string $originalUrl
     * @param string $filePath
     * @param string $type
     */
    public function __construct($originalUrl/*, $filePath*/, $type)
    {
        $this->originalUrl = $originalUrl;
        $this->urlParts = parse_url($originalUrl);
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    /**
     * @param string $originalUrl
     */
    public function setOriginalUrl(string $originalUrl)
    {
        $this->originalUrl = $originalUrl;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        $url = parse_url($this->originalUrl);
        $result = "";

        if (array_key_exists('host', $url))
            $result .= $url['host'] . '/';

        if (array_key_exists('path', $url) && substr($url['path'],-1) !== '/')
            $result .=  $url['path'];
        else
            $result .= 'index';

        return $result;
    }

    /**
     * Stolen from https://stackoverflow.com/questions/20522605/what-is-the-best-way-to-resolve-a-relative-path-like-realpath-for-non-existing
     * Stick basePath in from of filePath and make it a full path
     *
     * @param $basePath string
     * @return string
     */
    public function getFullFilePath($basePath): string
    {
        $path = [];
        $filePath = $basePath . '/' . $this->getFilePath();
        /*foreach(explode('/', $filePath) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') continue;

            if ($part !== '..') {
                // cool, we found a new part
                array_push($path, $part);
            }
            else if (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            } else {
                // now, here we don't like
                throw new \Exception('Climbing above the root is not permitted.');
            }
        }

        // prepend my root directory
        //array_unshift($path, '');

        $fullPath = join('/', $path);*/
        return $filePath;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function Download($outputDirectory)
    {
        $outFile = $this->getFullFilePath($outputDirectory);

        if (file_exists($outFile))
        {
            // We're already done!
            return;
        }

        $dirName = dirname($outFile);

        if ($dirName == NULL || strlen($dirName) == 0)
        {
            error_log("Invalid directory because wait wut");
            return;
        }

        // Make sure the parent folder exists
        if (!file_exists($dirName))
            mkdir($dirName, 0755, true);


        $inFile = $this->getOriginalUrl();

        $outFp = fopen($outFile, "w+");
        if ($outFp == NULL)
        {
            error_log("Unable to open file $outFile for writing");
            return;
        }

        // Try downloading from the regular source
        $ch = curl_init($inFile);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Arch; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0");
        curl_setopt($ch, CURLOPT_FILE, $outFp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        // This is breaking a lot of downloads; don't wait too long!
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Close the file pointer; if we downloaded a 404, we will overwrite it below
        fclose($outFp);

        if ($result === FALSE || ($httpCode >= 400 && $httpCode <= 600))
        {
            // This failed, let's try the Archive.org version
            error_log("Failed to download " . $this->originalUrl . ", (result: " . ($result?"true":"false") . ", httpCode=$httpCode) trying the Web Archive...");

            $outFp = fopen($outFile, "w+");

            do {
                // Let's get the snapshot closest to 1 June 2006, because that's back when Geocities still existed and most forumposts were already made
                $json = @file_get_contents("https://archive.org/wayback/available?url=" . $this->originalUrl . "&timestamp=20060601");

                if ($json == NULL){
                    $waybackStatus = NULL;
                    break;
                }
                $waybackStatus = json_decode($json);
            } while($waybackStatus == NULL);

            if ($waybackStatus != NULL && isset($waybackStatus->archived_snapshots->closest) && $waybackStatus->archived_snapshots->closest->available) {
                $archiveUrl = $waybackStatus->archived_snapshots->closest->url;

                // Try downloading from the regular source
                $ch = curl_init($archiveUrl);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Arch; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0");
                curl_setopt($ch, CURLOPT_FILE, $outFp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);

                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                curl_close($ch);

                if (!$result || ($httpCode >= 400 && $httpCode <= 600)) {
                    error_log("Download from archive.org failed! Giving up!");
                }
            } else {
                error_log("Archive.org doesn't have " . $this->originalUrl . " :(");
            }
        }
    }


}