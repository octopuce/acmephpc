<?php

namespace Octopuce\Acme\Storage;

use Octopuce\Acme\StorableInterface;
use Symfony\Component\Finder\Finder;

class FileSystem implements StorageInterface
{
    /**
     * File names
     */
    const FILE_NONCE       = 'nonce';
    const FILE_STATUS      = 'status';
    const FILE_OWNERSHIP   = 'ownership.json';
    const FILE_CERTIFICATE = 'certificate.json';

    /**
     * Target base dir
     * @var string
     */
    private $baseDir;

    /**
     * Finder instance
     * @var Finder
     */
    private $finder;

    /**
     * Constructor
     *
     * @param string $baseDir
     * @param Finder $finder
     */
    public function __construct($baseDir, Finder $finder)
    {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
        $this->finder  = $finder;
    }

    /**
     * {@inheritDoc}
     */
    public function save(StorableInterface $obj, $type)
    {
        $saveData = $obj->getStorableData();

        $file = $this->getFileName($type, $saveData);

        return $this->write($this->baseDir.DIRECTORY_SEPARATOR.$file, $saveData);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(StorableInterface $obj, $type)
    {
        $file = $this->getFileName($type, $obj->getStorableData());

        unlink($this->baseDir.DIRECTORY_SEPARATOR.$file);
    }

    /**
     * {@inheritDoc}
     */
    public function loadStatus()
    {
        $nonceData = $this->readNonce();

        $output = array(
            'nonce'   => $nonceData['nonce'],
            'noncets' => $nonceData['noncets'],
            'apiurls' => $this->read($this->baseDir.DIRECTORY_SEPARATOR.self::FILE_STATUS),
        );

        return $output;
    }

    /**
     * Read nonce file
     *
     * @return array
     */
    private function readNonce()
    {
        $data = array('nonce' => '', 'noncets' => '');

        $fileData = $this->read($this->baseDir.DIRECTORY_SEPARATOR.self::FILE_NONCE);

        array_replace_recursive($data, $fileData);

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus($nonce, $apiUrls)
    {
        $this->updateNonce($nonce);

        return $this->write($this->baseDir.DIRECTORY_SEPARATOR.self::FILE_STATUS, $apiUrls);

    }

    /**
     * {@inheritDoc}
     */
    public function updateNonce($nonce)
    {
        $data = array(
            'nonce' => $nonce,
            'noncets' => time()
        );

        return $this->write($this->baseDir.DIRECTORY_SEPARATOR.self::FILE_NONCE, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id, $type)
    {
        switch ($type) {

            case 'ownership':
                $file = sprintf(
                    '%s%s%s',
                    $id,
                    DIRECTORY_SEPARATOR,
                    self::FILE_OWNERSHIP
                );
                break;

            case 'account':
                $file = sprintf('%s.json', $id);
                break;
        }

        return $this->read($this->baseDir.DIRECTORY_SEPARATOR.$file);
    }

    /**
     * {@inheritDoc}
     */
    public function findOwnershipByDomain($domain)
    {
        $file = $this->baseDir.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.self::FILE_OWNERSHIP;

        return (array) $this->read($file);
    }

    /**
     * {@inheritDoc}
     *
     * @throws CertificateNotFoundException
     */
    public function findCertificateByDomain($domain)
    {
        $files = $this->finder
            ->files()
            ->name(self::FILE_CERTIFICATE)
            ->in($this->baseDir)
            ->contains(json_encode($domain));

        $output = array();

        if (iterator_count($files) == 1) {
            foreach ($files as $file) {
                $output = $this->read($file->getRealPath());
            }
        }

        return $output;
    }

    /**
     * Get file name
     *
     * @param string $type
     * @param array  $data
     *
     * @return string
     */
    private function getFileName($type, $data)
    {
        switch ($type) {

            case 'certificate':
                $file = sprintf(
                    '%s%s%s',
                    $data['fqdn'],
                    DIRECTORY_SEPARATOR,
                    self::FILE_CERTIFICATE
                );
                break;

            case 'ownership':
                $file = sprintf(
                    '%s%s%s',
                    $data['value'],
                    DIRECTORY_SEPARATOR,
                    self::FILE_OWNERSHIP
                );
                break;

            case 'account':
                $file = sprintf('%s.json', $data['mailto']);
                break;
        }

        return $file;
    }

    /**
     * Read file
     *
     * @param string $file
     *
     * @return mixed
     */
    private function read($file)
    {
        $output = array();
        if (file_exists($file)) {
            $output = json_decode(file_get_contents($file), true);
        }

        return $output;
    }

    /**
     * Write file
     *
     * @param string $file
     * @param mixed  $content
     *
     * @return int Bytes written
     *
     * @throws \RuntimeException
     */
    private function write($file, $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $bytes = file_put_contents($file, json_encode($content), LOCK_EX);

        if (!$bytes) {
            throw new \RuntimeException(sprintf('No data written for file %s', realpath($file)));
        }

        return $bytes;
    }
}
