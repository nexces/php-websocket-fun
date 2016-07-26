<?php
/**
 * Created by PhpStorm.
 * User: nexce_000
 * Date: 20.07.2016
 * Time: 13:12
 */

namespace MyApp;


use Guzzle\Http\Message\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Guzzle\Http\Message\RequestInterface;

class Site implements HttpServerInterface
{
    private $logger;

    private $request;

    public function __construct()
    {
        $stdout = new StreamHandler('php://stdout');

        $this->logger = new Logger('stdout', [$stdout]);
        $this->logger->info('Site()');
    }

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        $this->logger->info('Site::onOpen()');
        $this->request = $request;

        $targetPath = $request->getPath() == '/' ? '/index.html' : $request->getPath();

        $body = file_get_contents(PUB_ROOT . $targetPath);
        $mime = mime_content_type(PUB_ROOT . $targetPath);


        $response = new Response(200);
        $response->addHeader('Connection', 'close');
        $response->addHeader('Content-Length', strlen($body));
        if (substr($mime, 0, 5) === 'text/') {
            $response->addHeader('Content-Type', $mime . '; charset=UTF-8');
        } else {
            $response->addHeader('Content-Type', $mime);
        }
        $response->setBody($body);
        $conn->send($response);
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->logger->info('Site::onMessage() :: ' . $msg);
        $response = new Response(204);
        $from->send($response);
        $from->close();
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->logger->info('Site::onClose()');
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error('Site::onError()');
        $conn->close();

    }
}
