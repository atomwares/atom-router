<?php
/**
 * @link http://www.atomframework.net/
 * @copyright Copyright (c) 2017 Safarov Alisher
 * @license https://github.com/atomwares/atom-router/blob/master/LICENSE (MIT License)
 */


namespace Atom\Router;

use Atom\Interfaces\RouteInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Class Route
 *
 * @package Atom\Router
 */
class Route implements RouteInterface
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var Group
     */
    protected $group;
    /**
     * @var array
     */
    protected $methods;
    /**
     * @var string
     */
    protected $pattern;
    /**
     * @var MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     */
    protected $handler;
    /**
     * @var int
     */
    protected static $counter = 0;

    /**
     * Route constructor.
     *
     * @param Group $group
     * @param string[] $methods
     * @param string $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     * @param string|null $name
     */
    public function __construct(Group $group, array $methods, $pattern, $handler, $name = null)
    {
        $this->id = $name !== null ? $name : 'route' . static::$counter++;
        $this->methods = $methods;
        $this->group = $group;
        $this->pattern = $group !== null ? $group->getPattern() . $pattern : $pattern;
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     *
     * @return $this
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
        $this->pattern = $group->getPattern() . $this->pattern;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param string[] $methods
     *
     * @return $this
     */
    public function setMethods($methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @return MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }
    /**
     * @return MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     */
    public function getHandlers()
    {
        $handlers = [];

        if ($group = $this->group) {
            $handlers = array_reverse(static::collectGroupHandlers($group));
        }

        if ($handler = $this->handler) {
            $handlers = array_merge(
                $handlers,
                is_array($handler) ? $handler : [$handler]
            );
        }

        return $handlers;
    }

    /**
     * @param Group $group
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handlers
     *
     * @return array
     */
    protected static function collectGroupHandlers($group, $handlers = [])
    {
        if ($handler = $group->getHandler()) {
            $handlers[] = $handler;
        }

        if ($parent = $group->getParent()) {
            return static::collectGroupHandlers($parent, $handlers);
        }

        return $handlers;
    }
}
