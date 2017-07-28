<?php
/**
 * @link http://www.atomframework.net/
 * @copyright Copyright (c) 2017 Safarov Alisher
 * @license https://github.com/atomwares/atom-router/blob/master/LICENSE (MIT License)
 */

namespace Atom\Router;

use Interop\Http\ServerMiddleware\MiddlewareInterface;

/**
 * Class Group
 *
 * @package Atom\Router
 */
class Group
{
    /**
     * @var string
     */
    protected $pattern = '';
    /**
     * @var MiddlewareInterface|MiddlewareInterface[]|callable|callable[]|null $handler
     */
    protected $handler;
    /**
     * @var Group|null
     */
    protected $parent;
    /**
     * @var
     */
    protected $routes;

    /**
     * Group constructor.
     *
     * @param string $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[]|null $handler
     * @param Group|null $parent
     */
    public function __construct($pattern = '', $handler = null, Group $parent = null)
    {
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->parent = $parent;
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
     * @return MiddlewareInterface|MiddlewareInterface[]|callable|callable[]|null $handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[]|null $handler
     *
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return Group|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $this->routes[$route->getId()] = $route;

        return $this;
    }
}
