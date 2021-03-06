<?php

/**
 * @see       https://github.com/laminas/laminas-server for the canonical source repository
 * @copyright https://github.com/laminas/laminas-server/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-server/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Server\Method;

use Laminas\Server;

/**
 * Method callback metadata
 */
class Callback
{
    /**
     * @var string Class name for class method callback
     */
    protected $class;

    /**
     * @var string|callable Function name or callable for function callback
     */
    protected $function;

    /**
     * @var string Method name for class method callback
     */
    protected $method;

    /**
     * @var string Callback type
     */
    protected $type;

    /**
     * @var array Valid callback types
     */
    protected $types = ['function', 'static', 'instance'];

    /**
     * Constructor
     *
     * @param  null|array $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Set object state from array of options
     *
     * @param  array $options
     * @return \Laminas\Server\Method\Callback
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Set callback class
     *
     * @param  string $class
     * @return \Laminas\Server\Method\Callback
     */
    public function setClass($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $this->class = $class;
        return $this;
    }

    /**
     * Get callback class
     *
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set callback function
     *
     * @param  string|callable $function
     * @return \Laminas\Server\Method\Callback
     */
    public function setFunction($function)
    {
        $this->function = $function;
        $this->setType('function');
        return $this;
    }

    /**
     * Get callback function
     *
     * @return null|string|callable
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Set callback class method
     *
     * @param  string $method
     * @return \Laminas\Server\Method\Callback
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Get callback class  method
     *
     * @return null|string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set callback type
     *
     * @param  string $type
     * @return \Laminas\Server\Method\Callback
     * @throws Server\Exception\InvalidArgumentException
     */
    public function setType($type)
    {
        if (! in_array($type, $this->types)) {
            throw new Server\Exception\InvalidArgumentException(sprintf(
                'Invalid method callback type "%s" passed to %s',
                $type,
                __METHOD__
            ));
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Get callback type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Cast callback to array
     *
     * @return array
     */
    public function toArray()
    {
        $type = $this->getType();
        $array = [
            'type' => $type,
        ];
        if ('function' == $type) {
            $array['function'] = $this->getFunction();
        } else {
            $array['class']  = $this->getClass();
            $array['method'] = $this->getMethod();
        }
        return $array;
    }
}
