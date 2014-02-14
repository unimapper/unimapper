<?php

namespace UniMapper;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current mapper.
 */
abstract class Repository
{

    /** @var array $mappers Registered mappers */
    protected $mappers = array();

    protected $logger = null;

    /**
     * Constructor
     *
     * @param \UniMapper\Mapper $mapper Orm mapper
     */
    public function __construct(\UniMapper\Mapper $mapper = null, \UniMapper\Logger $logger = null)
    {
        if ($mapper) {
            $this->addMapper($mapper);
        }

        if ($logger) {
            $this->setLogger($logger);
        }
    }

    public function setLogger(\UniMapper\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function addMapper(\UniMapper\Mapper $mapper)
    {
        $this->mappers[] = $mapper;
    }

    public function createQuery($entityClass)
    {
        return new QueryBuilder(new $entityClass, $this->mappers, $this->logger);
    }

    public function getLogger()
    {
        return $this->logger;
    }

}