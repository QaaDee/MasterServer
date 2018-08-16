<?php

namespace QaaDee\MasterServer;

/**
 * @author QaaDee <user.qade@gmail.com>
 */

/**
 * Class MasterServer
 *
 * @package QaaDee\MasterServer
 *
 * @uses MasterServerException
 * @uses MasterServerClient
 */
class MasterServer
{
    /**
     * Bit for add server to server list.
     */
    private const SERVER_ADD = "\x71";

    /**
     * Bit for remove server of server list.
     */
    private const SERVER_SHOUT_DOWN = "\x62\x0a";

    /**
     * Bit to get the list of servers from the Master server.
     */
    private const SERVERS_GET = "\x31";

    /**
     * @var array
     */
    private $servers = [];

    /**
     * @var resource
     */
    private $socket;

    /**
     * Flag to allow adding a server to the server list.
     *
     * @var bool
     */
    private $isAllowServerAdd;

    /**
     * @var int
     */
    private $returnCountServers;

    /**
     * @var int
     */
    private $gameId;

    /**
     * MasterServer constructor.
     *
     * @param bool $isAllowServerAdd Allow adding a call server to the server list.
     * @param int $returnCountServers Number of servers to return
     * @param int $gameId
     *
     * @throws MasterServerException
     */
    final public function __construct(bool $isAllowServerAdd = true, int $returnCountServers = 10, int $gameId = 0)
    {
        $this->isAllowServerAdd = $isAllowServerAdd;

        if ($this->returnCountServers < 1 && $this->returnCountServers > 677) {
            throw new MasterServerException('The number of servers returned should not be less than 1 and more than 677');
        }

        $this->returnCountServers = $returnCountServers;

        $this->gameId = $gameId;
    }

    /**
     * Create server.
     *
     * @param string $address
     * @param int $port
     *
     * @throws MasterServerException
     */
    final public function create(string $address, int $port = 27010)
    {
        if ($this->socket) {
            throw new MasterServerException('The server has already been created earlier.');
        }

        if (!($this->socket = @socket_create(AF_INET, SOCK_DGRAM, 0))) {
            $socketErrorCode = socket_last_error($this->socket);
            $socketErrorMessage = socket_strerror($socketErrorCode);
            throw new MasterServerException($socketErrorMessage, $socketErrorCode);
        }

        if (!@socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $socketErrorCode = socket_last_error($this->socket);
            $socketErrorMessage = socket_strerror($socketErrorCode);
            throw new MasterServerException($socketErrorMessage, $socketErrorCode);
        }

        if (!@socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0])) {
            $socketErrorCode = socket_last_error($this->socket);
            $socketErrorMessage = socket_strerror($socketErrorCode);
            throw new MasterServerException($socketErrorMessage, $socketErrorCode);
        }

        if(!@socket_bind($this->socket, $address, $port)) {
            $socketErrorCode = socket_last_error($this->socket);
            $socketErrorMessage = socket_strerror($socketErrorCode);
            throw new MasterServerException($socketErrorMessage, $socketErrorCode);
        }
    }

    /**
     * Close server.
     */
    final public function close()
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Read to socket server.
     *
     * @param int $len
     *
     * @return MasterServerClient | null
     */
    final protected function read(int $len = 4096)
    {
        if (!@socket_recvfrom($this->socket, $buffer, $len, MSG_PEEK / 64, $address, $port)) {
            return null;
        }

        return new MasterServerClient($address, $port, $buffer);
    }

    /**
     * Send to socket server
     *
     * @param string $address
     * @param int $port
     * @param string $message
     */
    final protected function send(string $address, int $port, string $message)
    {
        socket_sendto($this->socket, $message, strlen($message), 0, $address, $port);
    }

    /**
     * Called before processing the request.
     *
     * return true - allow processing.
     * return false - deny processing.
     *
     * @param MasterServerClient $client
     *
     * @return bool
     */
    protected function beforeProcessing(MasterServerClient $client): bool
    {
        return true;
    }

    /**
     * Called when the server cannot process the request.
     *
     * @param MasterServerClient $client
     *
     * @return void
     */
    protected function requestUnprocessed(MasterServerClient $client): void
    {

    }

    /**
     * Accepts the request from the client and processes it.
     */
    final public function listen()
    {
        $client = $this->read();

        if (!$client || !$this->beforeProcessing($client)) {
            return;
        }

        if ($client->buffer == self::SERVER_ADD) {
            if ($this->isAllowServerAdd) {
                $this->addServer("{$client->address}:{$client->port}");
            }
        } else if ($client->buffer == self::SERVER_SHOUT_DOWN) {
            if ($this->isAllowServerAdd) {
                $this->removeServer("{$client->address}:{$client->port}");
            }
        } else if ($client->buffer[0] == self::SERVERS_GET) {
            $message = substr($client->buffer, 2, strlen($client->buffer) - 2);

            $parts = explode('\\', $message);

            $lastAddress = trim(array_shift($parts));
            $gameId = trim(array_pop($parts));

            if ($this->gameId == 0 || $this->gameId == $gameId) {

                $serversBits = $this->getServers($lastAddress);

                $output = "\xFF\xFF\xFF\xFF\x66\x0A";

                if ($serversBits) {
                    $output .= implode(null, $serversBits);
                }

                if (sizeof($serversBits) != $this->returnCountServers) {
                    $output .= "\x00\x00\x00\x00\x00\x00";
                }

                $this->send($client->address, $client->port, $output);
            }
        } else {
            $this->requestUnprocessed($client);
        }
    }

    /**
     * Add a server to the server list.
     *
     * @param string $server
     *
     * @return MasterServer
     */
    final public function addServer(string $server): MasterServer
    {
        $server = trim($server);

        if (!array_key_exists($server, $this->servers)) {
            if (preg_match("#[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}:[0-9]{1,5}#", $server)) {
                list($ip, $port) = explode(':', $server);

                $ip = explode('.', $ip);
                $this->servers[$server] = pack('ccccn', $ip[0], $ip[1], $ip[2], $ip[3], $port);
            }
        }

        return $this;
    }

    /**
     * Adds a servers to the server list.
     *
     * @param array $servers
     *
     * @return MasterServer
     */
    final public function addServers(array $servers): MasterServer
    {
        foreach ($servers as $server) {
            $this->addServer($server);
        }

        return $this;
    }

    /**
     * Cleaning the server list.
     */
    final public function clearServers()
    {
        $this->servers = [];
    }

    /**
     * Remove a server to the server list.
     *
     * @param string $server
     */
    final public function removeServer(string $server)
    {
        if (array_key_exists($server, $this->servers)) {
            unset($this->servers[$server]);
        }
    }

    /**
     * Returns the server based on the last server address.
     *
     * @param string $lastServer
     *
     * @return array
     */
    final protected function getServers(string $lastServer): array
    {
        $key = -1;

        if ($lastServer != '0.0.0.0:0') {
            $keys = array_keys($this->servers);
            $key = array_search($lastServer, $keys);
        }

        $servers = [];

        if ($key !== false) {
            $servers = array_slice($this->servers, $key + 1, $this->returnCountServers);
        }

        return $servers;
    }
}


?>