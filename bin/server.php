<?php

declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: nexce_000
 * Date: 20.07.2016
 * Time: 13:13
 */
namespace MyApp;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use Ratchet\Http\Router;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require realpath(__DIR__) . '/../vendor/autoload.php';

define('ROOT', dirname(realpath(__DIR__)));
define('PUB_ROOT', ROOT . '/public');

// WTF? O.o
if (3 !== strlen('✓')) {
    throw new \DomainException('Bad encoding, length of unicode character ✓ should be 3. Ensure charset UTF-8 and check ini val mbstring.func_autoload');
}

$loop = Factory::create();

$socket = new Server($loop);
$socket->listen(8080, '0.0.0.0');

$routes = new RouteCollection();

$chat = new Chat;
$chatServer = new WsServer($chat);
$chatServer->setEncodingChecks(false);

$site = new Site;

$routes->add('ws', new Route('/ws', array('_controller' => $chatServer)));
$routes->add('site', new Route('/', array('_controller' => $site)));

function routeStaticFiles(string $path, RouteCollection &$routes, &$controller) {
    $path = rtrim($path, '/\\') . '/*';
//    echo 'Scanning: ' . $path . PHP_EOL;
    $entries = glob($path);
//    print_r($entries);
    foreach ($entries as $entry) {
        if (is_dir($entry)) {
            routeStaticFiles($entry, $routes, $controller);
        } else {
            $routePath = str_replace(PUB_ROOT, '', $entry);
            echo 'Route builder :: adding "' . $routePath . '"' . PHP_EOL;
            $routes->add('site/' . $routePath, new Route($routePath, array('_controller' => $controller)));
        }
    }
}

routeStaticFiles(PUB_ROOT, $routes, $site);

//var_dump($routes);

$server = new IoServer(
    new HttpServer(
        new Router(
            new UrlMatcher($routes, new RequestContext)
        )
    ),
    $socket,
    $loop
);

$server->run();
