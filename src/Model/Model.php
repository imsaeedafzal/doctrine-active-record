<?php

namespace Doctrine\ActiveRecord\Model;

use Doctrine\ActiveRecord\Exception\ModelException;
use Doctrine\ActiveRecord\Exception\Exception;
use Doctrine\ActiveRecord\Dao\Dao as Dao;
use Closure;

/**
 * Models are logically located between the controllers, which render
 * the views and validate user input, and the DAOs, that are the low-level
 * interface to the storage backend.
 *
 * The public interface of models is high-level and should reflect the
 * all use cases for the business domain.
 *
 * If you want to build on pre-implemented ActiveRecord functionality,
 * use EntityModel instead of the basic Model, which only offers a number of
 * basic factory methods.
 *
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
abstract class Model
{
    /**
     * Reference to the model factory
     *
     * @var Factory
     */
    private $_factory;

    /**
     * Name of associated data access object (DAO) without namespace & postfix (see Factory)
     *
     * @var string
     */
    protected $_daoName = '';

    /**
     * Reference to related DAO instance, see $_daoName
     *
     * @var
     */
    protected $_dao;

    /**
     * @param Factory $factory Model factory instance
     * @param Dao $dao An instance of a DOA to initialize this instance (otherwise, you must call find/search)
     */
    public function __construct(Factory $factory, Dao $dao = null)
    {
        $this->setFactory($factory);

        if (!empty($dao)) {
            $this->setDao($dao);
        }
    }

    /**
     * Creates a new model instance
     *
     * @param string $name Optional model name (current model name if empty)
     * @param Dao $dao DB DAO instance
     * @throws Exception
     * @return Model|EntityModel
     */
    public function createModel(string $name = '', Dao $dao = null)
    {
        $modelName = empty($name) ? $this->getModelName() : $name;

        $model = $this->getFactory()->create($modelName, $dao);

        return $model;
    }

    /**
     * Creates a new data access object (DAO) instance
     *
     * @param string $name Class name without prefix namespace and postfix
     * @throws Exception
     * @return Dao
     */
    protected function createDao(string $name = '')
    {
        $daoName = empty($name) ? $this->getDaoName() : $name;

        $dao = $this->getFactory()->createDao($daoName);

        return $dao;
    }

    /**
     * Set factory instance
     *
     * @param Factory $factory
     * @return $this
     */
    protected function setFactory(Factory $factory)
    {
        $this->_factory = $factory;

        return $this;
    }

    /**
     * Returns factory instance
     *
     * @return Factory
     * @throws ModelException
     */
    private function getFactory(): Factory
    {
        if (empty($this->_factory)) {
            throw new ModelException ('Factory instance not set');
        }

        return $this->_factory;
    }

    /**
     * Set related DaoEntity name (only possible once)
     *
     * @param string $name DAO entity name for factory
     * @throws ModelException
     * @return $this
     */
    public function setDaoName(string $name)
    {
        if (empty($name)) {
            throw new ModelException ('DAO name was empty');
        }

        if (!empty($this->_daoName)) {
            throw new ModelException ('DAO name already set');
        }

        $this->_daoName = $name;

        return $this;
    }

    /**
     * Returns related DaoEntity name
     *
     * @return string
     */
    public function getDaoName(): string
    {
        return $this->_daoName;
    }

    /**
     * Returns main DAO instance; automatically creates an instance, if $this->_dao is empty
     *
     * @return Dao
     */
    protected function getDao(): Dao
    {
        if (empty($this->_dao)) {
            $this->setDao($this->createDao());
        }

        return $this->_dao;
    }

    /**
     * Sets DAO instance
     *
     * @param Dao $dao
     * @return $this
     */
    protected function setDao(Dao $dao)
    {
        $this->_dao = $dao;

        return $this;
    }

    /**
     * Resets the internal DAO reference
     */
    protected function resetDao()
    {
        $this->_dao = $this->createDao();
    }

    /**
     * Returns the model name without prefix and postfix
     *
     * @return string
     */
    public function getModelName(): string
    {
        $className = get_class($this);

        $postfix = $this->getFactory()->getFactoryPostfix();
        $namespace = $this->getFactory()->getFactoryNamespace();

        if ($postfix != '') {
            $result = substr($className, strlen($namespace), strlen($postfix) * -1);
        } else {
            $result = substr($className, strlen($namespace));
        }

        return $result;
    }

    /**
     * Executes a function in a transaction.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param \Closure $func The function to execute transactionally.
     *
     * @throws \Exception
     */
    public function transactional(Closure $func)
    {
        $this->getDao()->transactional($func);
    }
}