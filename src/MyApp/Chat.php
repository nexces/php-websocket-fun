<?php

namespace MyApp;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Created by PhpStorm.
 * User: nexce_000
 * Date: 21.07.2016
 * Time: 08:15
 */
class Chat implements MessageComponentInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var \SplObjectStorage
     */
    protected $clients;
    /**
     * @var \SplObjectStorage
     */
    protected $sessionStorage;

    public function __construct()
    {
        $stdout = new StreamHandler('php://stdout');

        $this->logger = new Logger('Chat', [$stdout]);
        $this->logger->info('__construct()');

        $this->clients = new \SplObjectStorage;
        $this->sessionStorage = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->logger->info('onOpen()');
        $this->clients->attach($conn);
        $this->sessionStorage[$conn] = new ClientModel;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->logger->info('onMessage() => ' . $msg);
        $message = json_decode($msg);
        if ($message === null) {
            $this->logger->warn('onMessage => Malformed message!');
            return;
        }

        if (!property_exists($message, 'type')) {
            $this->logger->warn('onMessage => Unknown message type!');
            return;
        }

        switch ($message->type) {
            case 'token':
                $this->handleToken($from, $message);
                break;
            case 'auth':
                $this->handleAuth($from, $message);
                break;
            case 'chat':
                $this->handleChat($from, $message);
                break;
            default:
                $this->logger->warn('onMessage => Unknown message type!');
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->logger->info('onClose()');
        $this->clients->detach($conn);
        unset ($this->sessionStorage[$conn]);

        $this->clientsUpdate();
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error('onError()');
        trigger_error("An error has occurred: {$e->getMessage()}\n", E_USER_WARNING);
        $conn->close();
    }

    public function handleToken(ConnectionInterface $client, $message)
    {
        if (!property_exists($message, 'token')) {
            $message->token = null;
        }

        $client->send('{"okay": "thanks!"}');
    }

    public function handleAuth(ConnectionInterface $from, $message)
    {
        // TODO make some DB queries
        if (property_exists($message, 'user') && property_exists($message, 'password') && strlen($message->user) > 1 && $message->password == 'password') {
            $this->sessionStorage[$from]->auth = true;
            $this->sessionStorage[$from]->name = $message->user;
            $from->send('{"type":"auth", "result": true}');

            $this->clientsUpdate();

            return;
        }
        $from->send('{"type":"auth", "result": false}');
    }

    public function handleChat(ConnectionInterface $from, $message) {
        if ($this->sessionStorage[$from]->auth !== true) {
            return;
        }

        foreach ($this->clients as $client) {
            /** @var $client ConnectionInterface */
//            if ($from === $client) {
//                continue;
//            }
            $payload = new \stdClass();
            $payload->type = "message";
            $payload->date = date("c");
            $payload->text = $message->text;
            $payload->from = $this->sessionStorage[$from]->name;
            $client->send(json_encode($payload));
        }
    }

    public function clientsUpdate()
    {
        $clientsPayload = new \stdClass();
        $clientsPayload->type = "clients";
        $clientsPayload->clients = [];
        foreach ($this->clients as $client) {
            if ($this->sessionStorage[$client]->auth == true) {
                $clientsPayload->clients[] = $this->sessionStorage[$client]->name;
            }
        }
        foreach ($this->clients as $client) {
            if ($this->sessionStorage[$client]->auth == true) {
                $client->send(json_encode($clientsPayload));
            }
        }
    }
}
