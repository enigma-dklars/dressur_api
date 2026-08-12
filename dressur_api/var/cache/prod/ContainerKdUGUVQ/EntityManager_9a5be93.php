<?php

namespace ContainerKdUGUVQ;

class EntityManager_9a5be93 extends \Doctrine\ORM\EntityManager implements \ProxyManager\Proxy\VirtualProxyInterface
{
    private $valueHolderfa13b = null;
    private $initializer387dd = null;
    private static $publicPropertiesd66cc = [
        
    ];
    public function getConnection()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getConnection', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getConnection();
    }
    public function getMetadataFactory()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getMetadataFactory', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getMetadataFactory();
    }
    public function getExpressionBuilder()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getExpressionBuilder', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getExpressionBuilder();
    }
    public function beginTransaction()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'beginTransaction', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->beginTransaction();
    }
    public function getCache()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getCache', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getCache();
    }
    public function transactional($func)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'transactional', array('func' => $func), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->transactional($func);
    }
    public function wrapInTransaction(callable $func)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'wrapInTransaction', array('func' => $func), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->wrapInTransaction($func);
    }
    public function commit()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'commit', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->commit();
    }
    public function rollback()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'rollback', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->rollback();
    }
    public function getClassMetadata($className)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getClassMetadata', array('className' => $className), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getClassMetadata($className);
    }
    public function createQuery($dql = '')
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'createQuery', array('dql' => $dql), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->createQuery($dql);
    }
    public function createNamedQuery($name)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'createNamedQuery', array('name' => $name), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->createNamedQuery($name);
    }
    public function createNativeQuery($sql, \Doctrine\ORM\Query\ResultSetMapping $rsm)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'createNativeQuery', array('sql' => $sql, 'rsm' => $rsm), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->createNativeQuery($sql, $rsm);
    }
    public function createNamedNativeQuery($name)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'createNamedNativeQuery', array('name' => $name), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->createNamedNativeQuery($name);
    }
    public function createQueryBuilder()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'createQueryBuilder', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->createQueryBuilder();
    }
    public function flush($entity = null)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'flush', array('entity' => $entity), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->flush($entity);
    }
    public function find($className, $id, $lockMode = null, $lockVersion = null)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'find', array('className' => $className, 'id' => $id, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->find($className, $id, $lockMode, $lockVersion);
    }
    public function getReference($entityName, $id)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getReference', array('entityName' => $entityName, 'id' => $id), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getReference($entityName, $id);
    }
    public function getPartialReference($entityName, $identifier)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getPartialReference', array('entityName' => $entityName, 'identifier' => $identifier), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getPartialReference($entityName, $identifier);
    }
    public function clear($entityName = null)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'clear', array('entityName' => $entityName), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->clear($entityName);
    }
    public function close()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'close', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->close();
    }
    public function persist($entity)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'persist', array('entity' => $entity), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->persist($entity);
    }
    public function remove($entity)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'remove', array('entity' => $entity), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->remove($entity);
    }
    public function refresh($entity, ?int $lockMode = null)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'refresh', array('entity' => $entity, 'lockMode' => $lockMode), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->refresh($entity, $lockMode);
    }
    public function detach($entity)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'detach', array('entity' => $entity), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->detach($entity);
    }
    public function merge($entity)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'merge', array('entity' => $entity), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->merge($entity);
    }
    public function copy($entity, $deep = false)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'copy', array('entity' => $entity, 'deep' => $deep), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->copy($entity, $deep);
    }
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'lock', array('entity' => $entity, 'lockMode' => $lockMode, 'lockVersion' => $lockVersion), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->lock($entity, $lockMode, $lockVersion);
    }
    public function getRepository($entityName)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getRepository', array('entityName' => $entityName), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getRepository($entityName);
    }
    public function contains($entity)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'contains', array('entity' => $entity), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->contains($entity);
    }
    public function getEventManager()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getEventManager', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getEventManager();
    }
    public function getConfiguration()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getConfiguration', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getConfiguration();
    }
    public function isOpen()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'isOpen', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->isOpen();
    }
    public function getUnitOfWork()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getUnitOfWork', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getUnitOfWork();
    }
    public function getHydrator($hydrationMode)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getHydrator', array('hydrationMode' => $hydrationMode), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getHydrator($hydrationMode);
    }
    public function newHydrator($hydrationMode)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'newHydrator', array('hydrationMode' => $hydrationMode), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->newHydrator($hydrationMode);
    }
    public function getProxyFactory()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getProxyFactory', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getProxyFactory();
    }
    public function initializeObject($obj)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'initializeObject', array('obj' => $obj), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->initializeObject($obj);
    }
    public function isUninitializedObject($obj): bool
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'isUninitializedObject', array('obj' => $obj), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->isUninitializedObject($obj);
    }
    public function getFilters()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'getFilters', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->getFilters();
    }
    public function isFiltersStateClean()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'isFiltersStateClean', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->isFiltersStateClean();
    }
    public function hasFilters()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'hasFilters', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return $this->valueHolderfa13b->hasFilters();
    }
    public static function staticProxyConstructor($initializer)
    {
        static $reflection;
        $reflection = $reflection ?? new \ReflectionClass(__CLASS__);
        $instance   = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $instance, 'Doctrine\\ORM\\EntityManager')->__invoke($instance);
        $instance->initializer387dd = $initializer;
        return $instance;
    }
    public function __construct(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config, ?\Doctrine\Common\EventManager $eventManager = null)
    {
        static $reflection;
        if (! $this->valueHolderfa13b) {
            $reflection = $reflection ?? new \ReflectionClass('Doctrine\\ORM\\EntityManager');
            $this->valueHolderfa13b = $reflection->newInstanceWithoutConstructor();
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
        }
        $this->valueHolderfa13b->__construct($conn, $config, $eventManager);
    }
    public function & __get($name)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, '__get', ['name' => $name], $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        if (isset(self::$publicPropertiesd66cc[$name])) {
            return $this->valueHolderfa13b->$name;
        }
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa13b;
            $backtrace = debug_backtrace(false, 1);
            trigger_error(
                sprintf(
                    'Undefined property: %s::$%s in %s on line %s',
                    $realInstanceReflection->getName(),
                    $name,
                    $backtrace[0]['file'],
                    $backtrace[0]['line']
                ),
                \E_USER_NOTICE
            );
            return $targetObject->$name;
        }
        $targetObject = $this->valueHolderfa13b;
        $accessor = function & () use ($targetObject, $name) {
            return $targetObject->$name;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __set($name, $value)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, '__set', array('name' => $name, 'value' => $value), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa13b;
            $targetObject->$name = $value;
            return $targetObject->$name;
        }
        $targetObject = $this->valueHolderfa13b;
        $accessor = function & () use ($targetObject, $name, $value) {
            $targetObject->$name = $value;
            return $targetObject->$name;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = & $accessor();
        return $returnValue;
    }
    public function __isset($name)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, '__isset', array('name' => $name), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa13b;
            return isset($targetObject->$name);
        }
        $targetObject = $this->valueHolderfa13b;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
    public function __unset($name)
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, '__unset', array('name' => $name), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        $realInstanceReflection = new \ReflectionClass('Doctrine\\ORM\\EntityManager');
        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolderfa13b;
            unset($targetObject->$name);
            return;
        }
        $targetObject = $this->valueHolderfa13b;
        $accessor = function () use ($targetObject, $name) {
            unset($targetObject->$name);
            return;
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $accessor();
    }
    public function __clone()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, '__clone', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        $this->valueHolderfa13b = clone $this->valueHolderfa13b;
    }
    public function __sleep()
    {
        $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, '__sleep', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
        return array('valueHolderfa13b');
    }
    public function __wakeup()
    {
        \Closure::bind(function (\Doctrine\ORM\EntityManager $instance) {
            unset($instance->config, $instance->conn, $instance->metadataFactory, $instance->unitOfWork, $instance->eventManager, $instance->proxyFactory, $instance->repositoryFactory, $instance->expressionBuilder, $instance->closed, $instance->filterCollection, $instance->cache);
        }, $this, 'Doctrine\\ORM\\EntityManager')->__invoke($this);
    }
    public function setProxyInitializer(?\Closure $initializer = null): void
    {
        $this->initializer387dd = $initializer;
    }
    public function getProxyInitializer(): ?\Closure
    {
        return $this->initializer387dd;
    }
    public function initializeProxy(): bool
    {
        return $this->initializer387dd && ($this->initializer387dd->__invoke($valueHolderfa13b, $this, 'initializeProxy', array(), $this->initializer387dd) || 1) && $this->valueHolderfa13b = $valueHolderfa13b;
    }
    public function isProxyInitialized(): bool
    {
        return null !== $this->valueHolderfa13b;
    }
    public function getWrappedValueHolderValue()
    {
        return $this->valueHolderfa13b;
    }
}

if (!\class_exists('EntityManager_9a5be93', false)) {
    \class_alias(__NAMESPACE__.'\\EntityManager_9a5be93', 'EntityManager_9a5be93', false);
}
