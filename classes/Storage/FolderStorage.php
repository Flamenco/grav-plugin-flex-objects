<?php
declare(strict_types=1);

namespace Grav\Plugin\FlexObjects\Storage;

use Grav\Common\Filesystem\Folder;
use RocketTheme\Toolbox\File\File;
use InvalidArgumentException;

/**
 * Class FolderStorage
 * @package Grav\Plugin\FlexObjects\Storage
 */
class FolderStorage extends AbstractFilesystemStorage
{
    /** @var string */
    protected $dataFolder;
    /** @var array */
    protected $dataPattern = '%1s/%2s/item';

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options)
    {
        if (!isset($options['folder'])) {
            throw new InvalidArgumentException("Argument \$options is missing 'folder'");
        }
        if (!isset($options['pattern'])) {
            throw new InvalidArgumentException("Argument \$options is missing 'pattern'");
        }

        $this->initDataFormatter($options['formatter'] ?? []);

        $extension = $this->dataFormatter->getDefaultFileExtension();
        $pattern = !empty($options['pattern']) ? $options['pattern'] : $this->dataPattern;

        $this->dataPattern = \dirname($pattern) . '/' . basename($pattern, $extension) . $extension;
        $this->dataFolder = $options['folder'];

        // Make sure that the data folder exists.
        $folder = $this->resolvePath($this->dataFolder);
        if (!file_exists($folder)) {
            Folder::create($folder);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExistingKeys() : array
    {
        return $this->findAllKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function hasKey(string $key) : bool
    {
        return $key && file_exists($this->getPathFromKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function createRows(array $rows) : array
    {
        $list = [];
        foreach ($rows as $key => $row) {
            // Create new file and save it.
            $key = $this->getNewKey();
            $path = $this->getPathFromKey($key);
            $file = $this->getFile($path);
            $list[$key] = $this->saveFile($file, $row);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function readRows(array $rows, array &$fetched = null) : array
    {
        $list = [];
        foreach ($rows as $key => $row) {
            if (null === $row || (!\is_object($row) && !\is_array($row))) {
                // Only load rows which haven't been loaded before.
                $path = $this->getPathFromKey($key);
                $file = $this->getFile($path);
                $list[$key] = $this->hasKey($key) ? $this->loadFile($file) : null;
                if (null !== $fetched) {
                    $fetched[$key] = $list[$key];
                }
            } else {
                // Keep the row if it has been loaded.
                $list[$key] = $row;
            }
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function updateRows(array $rows) : array
    {
        $list = [];
        foreach ($rows as $key => $row) {
            $path = $this->getPathFromKey($key);
            $file = $this->getFile($path);
            $list[$key] = $this->hasKey($key) ? $this->saveFile($file, $row) : null;
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRows(array $rows) : array
    {
        $list = [];
        foreach ($rows as $key => $row) {
            $path = $this->getPathFromKey($key);
            $file = $this->getFile($path);
            $list[$key] = $this->hasKey($key) ? $this->deleteFile($file) : null;

            $storage = $this->getStoragePath($key);
            $media = $this->getMediaPath($key);

            $this->deleteFolder($storage, true);
            $media && $this->deleteFolder($media, true);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceRows(array $rows) : array
    {
        $list = [];
        foreach ($rows as $key => $row) {
            $path = $this->getPathFromKey($key);
            $file = $this->getFile($path);
            $list[$key] = $this->saveFile($file, $row);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function renameRow(string $src, string $dst) : bool
    {
        if ($this->hasKey($dst)) {
            throw new \RuntimeException("Cannot rename object: key '{$dst}' is already taken");
        }

        if (!$this->hasKey($src)) {
            return false;
        }

        return $this->moveFolder($this->getMediaPath($src), $this->getMediaPath($dst));
    }

    /**
     * {@inheritdoc}
     */
    public function getStoragePath(string $key = null) : string
    {
        if (null === $key) {
            $path = $this->dataFolder;
        } else {
            $path = sprintf($this->dataPattern, $this->dataFolder, $key);
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaPath(string $key = null) : string
    {
        return null !== $key ? dirname($this->getStoragePath($key)) : $this->getStoragePath();
    }

    /**
     * Get filesystem path from the key.
     *
     * @param string $key
     * @return string
     */
    public function getPathFromKey(string $key) : string
    {
        return sprintf($this->dataPattern, $this->dataFolder, $key);
    }

    /**
     * @param File $file
     * @return array|null
     */
    protected function loadFile(File $file) : ?array
    {
        return $file->exists() ? (array)$file->content() : null;
    }

    /**
     * @param File $file
     * @param array $data
     * @return array
     */
    protected function saveFile(File $file, array $data) : array
    {
        $file->save($data);

        return $data;
    }

    /**
     * @param File $file
     * @return array|string
     */
    protected function deleteFile(File $file)
    {
        $data = $file->content();
        $file->delete();

        return $data;
    }

    /**
     * @param string $src
     * @param string $dst
     * @return bool
     */
    protected function moveFolder(string $src, string $dst) : bool
    {
        Folder::move($this->resolvePath($src), $this->resolvePath($dst));

        return true;
    }

    /**
     * @param string $path
     * @param bool $include_target
     * @return bool
     */
    protected function deleteFolder(string $path, bool $include_target = false) : bool
    {
        return Folder::delete($this->resolvePath($path), $include_target);
    }

    /**
     * Get key from the filesystem path.
     *
     * @param  string $path
     * @return string
     */
    protected function getKeyFromPath(string $path) : string
    {
        return basename($path);
    }

    /**
     * Returns list of all stored keys in [key => timestamp] pairs.
     *
     * @return array
     */
    protected function findAllKeys() : array
    {
        $flags = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \FilesystemIterator($this->getStoragePath(), $flags);
        $list = [];
        /** @var \SplFileInfo $info */
        foreach ($iterator as $filename => $info) {
            if (!$info->isDir() || !($key = $this->getKeyFromPath($filename))) {
                continue;
            }

            $list[$key] = $info->getMTime();
        }

        ksort($list, SORT_NATURAL);

        return $list;
    }

    /**
     * @return string
     */
    protected function getNewKey() : string
    {
        // Make sure that the file doesn't exist.
        do {
            $key = $this->generateKey();
        } while (file_exists($this->getPathFromKey($key)));

        return $key;
    }
}
