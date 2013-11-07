<?php

namespace AmericanReading\PdfExtractor\Components;

use AmericanReading\CliTools\App\AppException;

class GitHubRelease
{
    const RELEASES_URI_PATTERN = 'https://api.github.com/repos/%s/%s/releases';

    private $owner;
    private $repository;
    private $release;

    public function __construct($owner, $repository)
    {
        $this->owner = $owner;
        $this->repository = $repository;
        $this->requestReleases();
    }

    private function requestReleases()
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => sprintf(self::RELEASES_URI_PATTERN, $this->owner, $this->repository),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPGET => 1
        ));

        // Make the cURL request.
        $result = curl_exec($ch);

        // Throw an exception in the event of a cURL error.
        if ($result === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new AppException("Unable to make request to api.github.com [$errno] $error");
        }

        $releases = json_decode($result);

        // Sort releases by version number.
        usort($releases, function ($a, $b) {
                if (isset($a->tag_name, $b->tag_name)) {
                    return version_compare($a->tag_name, $b->tag_name, '<');
                }
                return 0;
            });

        // Store the most current release to the instance.
        $this->release = $releases[0];
    }

    public function getVersion()
    {
        return $this->release->tag_name;
    }

    /**
     * @param string $assetName Name of the asset to download from the current release
     * @param string $filePath File path to the download the asset to
     * @return bool True indicates the asset was downloaded successfully
     */
    public function downloadAsset($assetName, $filePath)
    {
        $asset = $this->getAsset($assetName);
        if ($asset === null) {
            return false;
        }

        // Attempt to download the file.
        $f = fopen($filePath, "w+");
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => $this->getAssetUrl($assetName),
                CURLOPT_FILE => $f,
                CURLOPT_FOLLOWLOCATION => true
            ));
        curl_exec($ch); // get curl response
        curl_close($ch);
        fclose($f);

        // Check if the downloaded file's size is the same as the asset's published size.
        if (isset($asset->size)) {
            return filesize($filePath) === (int) $asset->size;
        }
        return false;
    }

    public function getAsset($assetName)
    {
        if (isset($this->release->assets)) {
            foreach ($this->release->assets as $asset) {
                if ($asset->name === $assetName) {
                    return $asset;
                }
            }
        }
        return null;
    }

    private function getAssetUrl($assetName)
    {
        $version = $this->getVersion();
        return "https://github.com/$this->owner/$this->repository/releases/download/$version/$assetName";
    }
}
