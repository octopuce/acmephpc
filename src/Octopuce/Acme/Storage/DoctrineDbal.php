<?php

namespace Octopuce\Acme\Storage;

use Doctrine\DBAL\DriverManager;
use Octopuce\Acme\StorableInterface;

class DoctrineDbal implements StorageInterface
{
    /**
     * Maximum time we try to use a nonce before generating a new one.
     */
    const NONCE_MAX_AGE = 86400;

    /**
     * Connection instance
     * @var \Doctrine\DBAL\Connection
     */
    private $con;

    /**
     * Table names
     * @var array
     */
    private $tables = array(
        'ownership',
        'certificate',
        'account',
        'status',
    );

    /**
     * Contructor
     *
     * @var string $dsn          The connection string
     * @var string $tablePrefix  Table prefix
     */
    public function __construct($dsn, $tablePrefix = null)
    {
        $this->con = \Doctrine\DBAL\DriverManager::getConnection(array('url' => $dsn));
        $this->con->setFetchMode(\PDO::FETCH_ASSOC);

        if (!empty($tablePrefix)) {
            foreach ($this->tables as $tableName) {
                $this->tables[$tableName] = rtrim($tablePrefix, '_').'_'.$tableName;
            }
        }
    }

    public function lock()
    {

    }

    public function unlock()
    {

    }

    /**
     * Load status
     *
     * @return array|false
     */
    public function loadStatus()
    {
        return $this->con->createQueryBuilder()
            ->select(
                'nonce',
                'noncets',
                'apiurls',
                'modified'
            )
            ->from($this->tables['status'])
            ->execute()
            ->fetch();
    }

    /**
     * Update status
     *
     * @param string $nonce
     * @param string $apiUrls
     *
     * @return int
     */
    public function updateStatus($nonce, $apiUrls)
    {
        // Truncate table
        $this->con->executeQuery(
            $this->con->getDatabasePlatform()->getTruncateTableSQL($this->tables['status'])
        );

        // Insert new row
        return $this->con->insert(
            $this->tables['status'],
            array(
                'nonce' => $nonce,
                'noncets' => time(),
                'apiurls' => $apiUrls,
                'modified' => new \DateTime,
            ),
            array(
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                'datetime',
            )
        );
    }

    /**
     * Update nonce
     *
     * @param string $nonce
     *
     * @return bool
     */
    public function updateNonce($nonce)
    {
        return (bool) $this->con
            ->createQueryBuilder()
            ->update($this->tables['status'])
            ->set('nonce', ':nonce')
            ->setParameter('nonce', $nonce, \PDO::PARAM_STR)
            ->execute();
    }

    /**
     * Find ownership by domain
     *
     * @param string $domain
     *
     * @return array|false
     */
    public function findOwnershipByDomain($domain)
    {
        return $this->con
            ->createQueryBuilder()
            ->select('*')
            ->from($this->tables['ownership'])
            ->where('value = :domain')
            ->setParameter('domain', $domain, \PDO::PARAM_STR)
            ->execute()
            ->fetch();
    }

    /**
     * Find any object by Id
     *
     * @param int    $id
     * @param string $type
     *
     * @return array|false
     */
    public function findById($id, $type)
    {
        return $this->con
            ->createQueryBuilder()
            ->select('*')
            ->from($this->tables[$type])
            ->where('id = :id')
            ->setParameter('id', $id, \PDO::PARAM_INT)
            ->execute()
            ->fetch();
    }

    /**
     * Save an object into DB
     *
     * @param StorableInterface $obj
     * @param string            $type
     *
     * @return int The object ID
     */
    public function save(StorableInterface $obj, $type)
    {
        // If object has ID, update
        if ($obj->getId() !== null) {
            $this->update($obj, $type);
        } else {
            // otherwise insert it
            $id = $this->insert($obj, $type);
            $obj->setId($id);
        }

        return $obj->getId();
    }

    /**
     * Update object
     *
     * @param StorableInterface $obj
     * @param string            $type
     *
     * @return void
     */
    protected function update(StorableInterface $obj, $type)
    {
        $qb = $this->con
            ->createQueryBuilder()
            ->update($this->tables[$type])
            ->where('id = :id')
            ->setParameter('id', $obj->getId(), \PDO::PARAM_INT);

        foreach ($obj->getStorableData() as $name => $value) {
            $qb->set($name, $this->con->quote($value));
        }

        return $qb->execute();
    }

    /**
     * Insert object
     *
     * @param StorableInterface $obj
     * @param string            $type
     *
     * @return int
     */
    protected function insert(StorableInterface $obj, $type)
    {
        $insertParams = array();
        foreach ($obj->getStorableData() as $name => $value) {
            $insertParams[$name] = $value;
        }

        $this->con->insert(
            $this->tables[$type],
            $insertParams
        );

        return $this->con->lastInsertId();
    }
}
