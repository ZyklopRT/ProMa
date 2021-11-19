<?php

namespace jjansen\factory;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

class RouterFactory
{


    public static function create(): Router
    {
        // Load routes from the yaml file
        $fileLocator = new FileLocator([
            __DIR__ . '/../../config'
        ]);
        // Init RequestContext object
        $context = new RequestContext();
        $context->fromRequest(Request::createFromGlobals());
        // all in one router
        $router = new Router(
            new \Symfony\Component\Routing\Loader\YamlFileLoader($fileLocator),
            'routes.yaml',
            [],
            $context
        );

        return $router;
    }

}