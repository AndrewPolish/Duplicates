<?php

/**
 * Created by PhpStorm.
 * User: Andriy Polishchuk
 * Date: 12/14/2017
 * Time: 3:02 PM
 */
class Duplicate
{
    /**
     * Array of all file paths and hashes
     */
    private $allFiles;

    const NO_DUPLICATES = 'Duplicates not found';

    /**
     * @param $inputPath
     * Recursively scans all subdirectories and creates an array $allFiles
     */
    private function scanDir($inputPath)
    {
        $files = scandir($inputPath);

        foreach ($files as $name) {
            if ($name == '.' || $name == '..')
                continue;
            if (is_dir($inputPath . '\\' . $name)) {
                //recursively run the method:
                $this->scanDir($inputPath . '\\' . $name);
                continue;
            }
            $path = $inputPath . '\\' . $name;
            $this->allFiles[$path] = md5_file($path);
        }
    }

    /**
     * @return array|null
     * Finds all duplicates of file hashes
     *
     */
    private function checkHash()
    {
        if (!$this->allFiles)
            return null;
        $duplicatesQuantity = array_count_values($this->allFiles);
        $hashDuplicates = null;
        foreach ($duplicatesQuantity as $hash => $quantity) {
            if ($quantity > 1) $hashDuplicates[] = array_keys($this->allFiles, $hash);
        }
        return $hashDuplicates;
    }

    /**
     * @param $hashDuplicates
     * @return array|null
     * Checks the sizes and mime types of files
     */
    private function checkSizeAndMime($hashDuplicates)
    {
        $duplicates = null;
        if (!$hashDuplicates) {
            return $duplicates;
        }
        foreach ($hashDuplicates as $key1 => $duplicate) {
            $size = null;
            $mimeType = null;
            foreach ($duplicate as $key2 => $file) {
                    if ($size == null && $mimeType == null) {
                        $size = filesize($file);
                        $mimeType = mime_content_type($file);
                        $duplicates[$key1][$key2] = $file;
                    }
                    elseif ($size == filesize($file) && $mimeType == mime_content_type($file)) {
                        $duplicates[$key1][$key2] = $file;
                    }
                    else continue;
            }
        }
        return $duplicates;
    }

    /**
     * @param $path
     * Creates and/or writes duplicates to file
     */
    public function findDuplicates($path)
    {
        $this->scanDir($path);
        $duplicatesByHash = $this->checkHash();
        $duplicates = $this->checkSizeAndMime($duplicatesByHash);

        if (!$duplicates) {
            echo self::NO_DUPLICATES;
            die;
        }
        $file = @fopen("duplicates.txt", "w");
        foreach ($duplicates as $duplicate) {
            $duplicate = implode(', ', $duplicate);
            fwrite($file, $duplicate."\n");
        }
        echo 'success!';
        fclose($file);
    }
}
// Run the code:
try {
$path = __DIR__ . '\testDir';
if (!is_dir($path))
    throw new Exception('Invalid directory path');

$duplicates = new Duplicate();
$duplicates->findDuplicates($path);
}
catch (Exception $e) {
   echo $e->getMessage();
}