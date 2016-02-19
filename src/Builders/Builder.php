<?php

namespace LaravelDoctrine\Fluent\Builders;

use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use LaravelDoctrine\Fluent\Fluent;
use LogicException;

/**
 * @method $this array($name, callable $callback = null)
 */
class Builder extends AbstractBuilder implements Fluent
{
    use Traits\Fields;
    use Traits\Dates;
    use Traits\Aliases;
    use Traits\Relations;
    use Traits\Macroable;
    use Traits\Queueable;
    use Traits\QueuesMacros;

    /**
     * @param string|callable $name
     * @param callable|null   $callback
     *
     * @return Table
     */
    public function table($name, callable $callback = null)
    {
        $this->disallowInEmbeddedClasses();

        $table = new Table($this->builder);

        if (is_callable($name)) {
            $name($table);
        } else {
            $table->setName($name);
        }

        if (is_callable($callback)) {
            $callback($table);
        }

        return $table;
    }

    /**
     * @param callable|null $callback
     *
     * @return Entity
     */
    public function entity(callable $callback = null)
    {
        $this->disallowInEmbeddedClasses();

        $entity = new Entity($this->builder, $this->namingStrategy);

        if (is_callable($callback)) {
            $callback($entity);
        }

        return $entity;
    }

    /**
     * @param string        $type
     * @param callable|null $callback
     *
     * @return Inheritance\Inheritance
     */
    public function inheritance($type, callable $callback = null)
    {
        $inheritance = Inheritance\InheritanceFactory::create($type, $this->builder);

        if (is_callable($callback)) {
            $callback($inheritance);
        }

        return $inheritance;
    }

    /**
     * @param callable|null $callback
     *
     * @return Inheritance\Inheritance
     */
    public function singleTableInheritance(callable $callback = null)
    {
        return $this->inheritance(Inheritance\Inheritance::SINGLE, $callback);
    }

    /**
     * @param callable|null $callback
     *
     * @return Inheritance\Inheritance
     */
    public function joinedTableInheritance(callable $callback = null)
    {
        return $this->inheritance(Inheritance\Inheritance::JOINED, $callback);
    }

    /**
     * @param array|string $columns
     *
     * @return Index
     */
    public function index($columns)
    {
        return $this->constraint(
            Index::class,
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * @param array|string $fields
     *
     * @return Primary
     */
    public function primary($fields)
    {
        return $this->constraint(
            Primary::class,
            is_array($fields) ? $fields : func_get_args()
        );
    }

    /**
     * @param array|string $columns
     *
     * @return UniqueConstraint
     */
    public function unique($columns)
    {
        return $this->constraint(
            UniqueConstraint::class,
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * @param string $class
     * @param array  $columns
     *
     * @return mixed
     */
    protected function constraint($class, array $columns)
    {
        $constraint = new $class($this->builder, $columns);

        $this->queue($constraint);

        return $constraint;
    }

    /**
     * @param string        $embeddable
     * @param string|null   $field
     * @param callable|null $callback
     *
     * @return Embedded
     */
    public function embed($embeddable, $field = null, callable $callback = null)
    {
        $embedded = new Embedded(
            $this->builder,
            $this->namingStrategy,
            $this->guessSingularField($embeddable, $field),
            $embeddable
        );

        $this->callbackAndQueue($embedded, $callback);

        return $embedded;
    }

    /**
     * @param string   $name
     * @param callable $callback
     *
     * @return Overrides\Override
     */
    public function override($name, callable $callback)
    {
        $override = new Overrides\Override(
            $this->getBuilder(),
            $this->getNamingStrategy(),
            $name,
            $callback
        );

        $this->queue($override);

        return $override;
    }

    /**
     * @param callable|null $callback
     *
     * @return LifecycleEvents
     */
    public function events(callable $callback = null)
    {
        $events = new LifecycleEvents($this->builder);

        $this->callbackAndQueue($events, $callback);

        return $events;
    }

    /**
     * @return bool
     */
    public function isEmbeddedClass()
    {
        return $this->builder->getClassMetadata()->isEmbeddedClass;
    }

    /**
     * @param string        $name
     * @param callable|null $callback
     *
     * @return Field
     */
    protected function setArray($name, callable $callback = null)
    {
        return $this->field(Type::TARRAY, $name, $callback);
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        // Workaround for reserved keywords
        if ($method === 'array') {
            return call_user_func_array([$this, 'setArray'], $params);
        }

        if ($this->hasMacro($method)) {
            return $this->queueMacro($method, $params);
        }

        throw new InvalidArgumentException('Fluent builder method [' . $method . '] does not exist');
    }

    /**
     * @param  string         $message
     * @throws LogicException
     */
    protected function disallowInEmbeddedClasses($message = "")
    {
        if ($this->isEmbeddedClass()) {
            throw new LogicException($message);
        }
    }
}
