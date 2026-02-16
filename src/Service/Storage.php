<?php

declare(strict_types=1);

namespace M2Boilerplate\CriticalCss\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Handles file system operations for storing and retrieving generated Critical CSS.
 */
class Storage
{
    private const DIRECTORY = 'critical-css';

    /**
     * @var WriteInterface
     */
    protected WriteInterface $directory;

    /**
     * @param Filesystem $filesystem
     * @throws FileSystemException
     */
    public function __construct(
        protected Filesystem $filesystem
    ) {
        $this->directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Delete the existing critical CSS directory.
     *
     * @return void
     * @throws FileSystemException
     */
    public function clean(): void
    {
        if ($this->directory->isExist(self::DIRECTORY)) {
            $this->directory->delete(self::DIRECTORY);
        }
    }

    /**
     * Write Critical CSS content to a file.
     *
     * @param string $identifier
     * @param string|null $content
     * @return bool
     * @throws FileSystemException
     */
    public function saveCriticalCss(string $identifier, ?string $content): bool
    {
        // Ensure directory exists
        $this->directory->create(self::DIRECTORY);
        
        $filePath = $this->getFilePath($identifier);
        $this->directory->writeFile($filePath, (string)$content);
        
        return true;
    }

    /**
     * Read Critical CSS content from a file.
     *
     * @param string $identifier
     * @return string|null
     * @throws FileSystemException
     */
    public function getCriticalCss(string $identifier): ?string
    {
        $filePath = $this->getFilePath($identifier);
        
        if (!$this->directory->isReadable($filePath)) {
            return null;
        }
        
        return $this->directory->readFile($filePath);
    }

    /**
     * Get the size of the stored Critical CSS file in bytes.
     *
     * @param string $identifier
     * @return int|null
     */
    public function getFileSize(string $identifier): ?int
    {
        $filePath = $this->getFilePath($identifier);
        
        try {
            $stat = $this->directory->stat($filePath);
            return isset($stat['size']) ? (int)$stat['size'] : null;
        } catch (FileSystemException $e) {
            return null;
        }
    }

    /**
     * Get relative file path for the identifier.
     *
     * @param string $identifier
     * @return string
     */
    private function getFilePath(string $identifier): string
    {
        return self::DIRECTORY . '/' . $identifier . '.css';
    }
}
