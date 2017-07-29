<?php
/**
 * @link http://www.atomframework.net/
 * @copyright Copyright (c) 2017 Safarov Alisher
 * @license https://github.com/atomwares/atom-router/blob/master/LICENSE (MIT License)
 */


namespace Atom\Router;

use Atom\Http\Exception\MethodNotAllowedException;
use Atom\Http\Exception\NotFoundException;
use Atom\Interfaces\RouterInterface;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Class Router
 *
 * @package Atom\Router
 */
class Router implements RouterInterface
{
    /**
     * @var Group|null
     */
    protected $group;
    /**
     * @var Route[]
     */
    protected $routes = [];
    /**
     * @var string
     */
    protected $basePath = '';
    /**
     * @var RouteParser
     */
    protected static $routeParser;

    /**
     * Router constructor.
     *
     * @param Group|null $group
     * @param string $basePath
     */
    public function __construct(Group $group = null, $basePath = '')
    {
        $this->setGroup($group ?: new Group());
        $this->setBasePath($basePath);
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param $path
     */
    public function setBasePath($path)
    {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * @return Group|null
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

        return $this;
    }

    /**
     * @param string $id
     *
     * @return Route|null
     */
    public function getRoute($id)
    {
        $route = null;
        if (isset($this->routes[$id])) {
            $route = $this->routes[$id];
        }

        return $route;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $this->group->addRoute($route);
        $this->routes[$route->getId()] = $route;

        return $this;
    }

    /**
     * @param string|array|Route $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     * @param string|string[] $methods
     *
     * @return $this
     */
    public function route(
        $pattern,
        $handler,
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']
    ) {
        if (! is_string($pattern) && ! is_array($pattern)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid pattern argument; must be a string or array, received %s',
                (is_object($pattern) ? get_class($pattern) : gettype($pattern))
            ));
        }

        $name = null;

        if (is_array($pattern)) {
            $name = key($pattern);
            $pattern = current($pattern);
        }

        $route = new Route(
            $this->group,
            (array)$methods,
            $pattern,
            $handler,
            $name
        );

        return $this->addRoute($route);
    }

    /**
     * @param string|array $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return Router
     */
    public function get($pattern, $handler)
    {
        return $this->route($pattern, $handler, 'GET');
    }

    /**
     * @param string|array $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return Router
     */
    public function post($pattern, $handler)
    {
        return $this->route($pattern, $handler, 'POST');
    }

    /**
     * @param string|array $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return Router
     */
    public function put($pattern, $handler)
    {
        return $this->route($pattern, $handler, 'PUT');
    }

    /**
     * @param string|array $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return Router
     */
    public function delete($pattern, $handler)
    {
        return $this->route($pattern, $handler, 'DELETE');
    }

    /**
     * @param string|array $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return Router
     */
    public function patch($pattern, $handler)
    {
        return $this->route($pattern, $handler, 'PATCH');
    }

    /**
     * @param string|array $pattern
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[] $handler
     *
     * @return Router
     */
    public function options($pattern, $handler)
    {
        return $this->route($pattern, $handler, 'OPTIONS');
    }

    /**
     * @param string|callback $patternOrCallback
     * @param callable|null $callback
     * @param MiddlewareInterface|MiddlewareInterface[]|callable|callable[]|null $handler
     *
     * @return $this
     */
    public function mount($patternOrCallback, callable $callback = null, $handler = null)
    {
        if (is_callable($patternOrCallback)) {
            $patternOrCallback($this);
        } else {
            if (! is_string($patternOrCallback)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid pattern argument; must be a string, received %s',
                    (is_object($patternOrCallback) ? get_class($patternOrCallback) : gettype($patternOrCallback))
                ));
            }

            if ($callback === null) {
                throw new InvalidArgumentException(
                    '$callback argument must be a callable if patternOrCallback argument is a string'
                );
            }

            $router = $callback(
                $this->setGroup(new Group(
                    $this->group->getPattern() . $patternOrCallback,
                    $handler,
                    $this->group
                ))
            );

            if (! $router instanceof Router) {
                throw new UnexpectedValueException(sprintf(
                    'Invalid router value; must be an instance of %s, received %s',
                    self::class,
                    (is_object($router) ? get_class($router) : gettype($router))
                ));
            }

            $this->routes = array_merge($this->routes, $router->group->getRoutes());
            $this->group = $router->group->getParent();
        }

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Route
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $route = null;

        $routeInfo = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute(
                    $route->getMethods(),
                    $this->basePath . $route->getPattern(),
                    $route->getId()
                );
            }
        })->dispatch($request->getMethod(), $request->getUri()->getPath());

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundException();
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedException($routeInfo[1], sprintf(
                    'Request method "%s" not supported; following request methods are allowed: %s.',
                    $request->getMethod(),
                    implode(', ', $routeInfo[1])
                ));
            case Dispatcher::FOUND:
                foreach ($routeInfo[2] as $name => $value) {
                    $request = $request->withAttribute($name, $value);
                }
                $route = $this->getRoute($routeInfo[1]);
                break;
        }

        return $route;
    }

    /**
     * @param string $pattern
     * @param array $params
     *
     * @throws InvalidArgumentException
     * @return string
     */
    public function createUrl($pattern, $params = [])
    {
        if ($route = $this->getRoute($pattern)) {
            $pattern = $route->getPattern();
        }

        if (static::$routeParser === null) {
            static::$routeParser = new RouteParser();
        }

        $url = '';
        $queryParams = $params;

        foreach (static::$routeParser->parse($pattern) as $routeData) {
            foreach ($routeData as $segment) {
                if (is_array($segment)) {
                    if (! isset($params[$segment[0]])) {
                        $url = false;
                        break;
                    }

                    $url .= $params[$segment[0]];
                    unset($queryParams[$segment[0]]);
                } else {
                    $url .= $segment;
                }
            }

            if (empty($queryParams) && $url !== false) {
                break;
            }

            $url = '';
        }

        if ($url === false) {
            throw new InvalidArgumentException('Wrong pattern provided');
        } elseif ($url === '') {
            $url = $pattern;
        }

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }
}
