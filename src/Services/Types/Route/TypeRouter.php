<?php

namespace Kiwilan\Typescriptable\Services\Types\Route;

use Illuminate\Routing\Route;
use Kiwilan\Typescriptable\TypescriptableConfig;

class TypeRouter
{
    protected function __construct(
        /** @var TypeRoute[] */
        protected array $routes = [],
        protected string $routesFull = '',
        protected string $routesNameTypes = '',
        protected string $routesUriTypes = '',
        protected string $routesParamsTypes = '',
        protected string $routesType = '',
        protected string $routesEntity = '',
        protected ?string $typescript = null,
        protected ?string $typescriptRoutes = null,
    ) {
    }

    public static function make(): self
    {
        $type = new self();
        $type->routes = $type->setRoutes();

        $type->routesFull = $type->setRoutesFull();
        $type->routesNameTypes = $type->setRoutesNameTypes();
        $type->routesUriTypes = $type->setRoutesUriTypes();
        $type->routesParamsTypes = $type->setRoutesParamsTypes();
        $type->routesType = $type->setRoutesType();
        $type->routesEntity = $type->setRoutesEntity();

        $type->typescript = $type->setTypescript();
        $type->typescriptRoutes = $type->setTypescriptRoutes();

        return $type;
    }

    public function typescript(): string
    {
        return $this->typescript;
    }

    public function setTypescript(): string
    {
        return <<<typescript
        declare namespace Route {
          export type List = {
        {$this->routesFull}
          };

          export type Name = {$this->routesNameTypes};
          export type Uri = {$this->routesUriTypes};
          export type Params = {
        {$this->routesParamsTypes}
          };

          export interface Type {
        {$this->routesType}
          };

          export type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
          export interface Entity { name: Route.Name; path: Route.Uri; params?: Route.Params[Route.Name],  method: Route.Method; }
        }
        typescript;
    }

    public function typescriptRoutes(): string
    {
        return $this->typescriptRoutes;
    }

    public function setTypescriptRoutes(): string
    {
        return <<<typescript
        const routes: Record<Route.Name, Route.Entity> = {
        {$this->routesEntity},
        }

        export default routes

        typescript;
    }

    private function setRoutesFull(): string
    {
        return collect($this->routes)
            ->map(function (TypeRoute $route, string $key) {
                $methods = json_encode($route->methods());

                return "    '{$route->name()}': { 'uri': '{$route->uri()}', 'methods': {$methods} }";
            })
            ->join("\n");
    }

    private function setRoutesNameTypes(): string
    {
        return collect($this->routes)
            ->map(function (TypeRoute $route, string $key) {
                return "'{$route->name()}'";
            })
            ->join(' | ');
    }

    private function setRoutesUriTypes(): string
    {
        return collect($this->routes)
            ->map(function (TypeRoute $route, string $key) {
                return "'{$route->fullUri()}'";
            })
            ->join(' | ');
    }

    private function setRoutesParamsTypes(): string
    {
        return collect($this->routes)
            ->map(function (TypeRoute $route, string $key) {
                $hasParams = count($route->parameters()) > 0;

                if ($hasParams) {
                    $params = collect($route->parameters())
                        ->map(function (string $param) {
                            return "'{$param}': string";
                        })
                        ->join(",\n");

                    return "    '{$route->name()}': {\n      {$params}\n    }";
                } else {
                    return "    '{$route->name()}': never";
                }
            })
            ->join(",\n");
    }

    private function setRoutesType(): string
    {
        return  <<<'typescript'
            name: Route.Name
            params?: Route.Params[Route.Name]
            query?: Record<string, string | number | boolean>
            hash?: string
        typescript;
    }

    private function setRoutesEntity(): string
    {
        return collect($this->routes)
            ->map(function (TypeRoute $route, string $key) {
                $params = collect($route->parameters())
                    ->map(function (string $param) {
                        return "{$param}: 'string',";
                    })
                    ->join(",\n");

                if (empty($params)) {
                    $params = 'undefined';
                } else {
                    $params = <<<typescript
                    {
                          {$params}
                        }
                    typescript;
                }

                return <<<typescript
                  '{$route->name()}': {
                    name: '{$route->name()}',
                    path: '{$route->fullUri()}',
                    params: {$params},
                    method: '{$route->methods()[0]}',
                  }
                typescript;
            })
            ->join(",\n");
    }

    private function setRoutes(): array
    {
        /** @var TypeRoute[] $routes */
        $routes = collect(app('router')->getRoutes())
            ->mapWithKeys(function ($route) {
                return [$route->getName() => $route];
            })
            ->filter()
            ->map(function (Route $route) {
                return TypeRoute::make($route);
            })
            ->toArray();

        $list = [];

        foreach ($routes as $route) {
            if (! $this->skipRouteName($route)) {
                $list[$route->name()] = $route;
            }
        }

        foreach ($list as $route) {
            if ($this->skipRoutePath($route)) {
                unset($list[$route->name()]);
            }
        }

        return $list;
    }

    private function skipRouteName(TypeRoute $route): bool
    {
        $skip_name = [];
        $skippable_name = TypescriptableConfig::routesSkipName();

        foreach ($skippable_name as $item) {
            $item = str_replace('.*', '', $item);
            array_push($skip_name, $item);
        }

        foreach ($skip_name as $type => $item) {
            if (str_starts_with($route->name(), $item)) {
                return true;
            }
        }

        return false;
    }

    private function skipRoutePath(TypeRoute $route): bool
    {
        $skip_path = [];
        $skippable_path = TypescriptableConfig::routesSkipPath();

        foreach ($skippable_path as $item) {
            $item = str_replace('/*', '', $item);
            array_push($skip_path, $item);
        }

        foreach ($skip_path as $type => $item) {
            if (str_starts_with($route->uri(), $item)) {
                return true;
            }
        }

        return false;
    }
}
