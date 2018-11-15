<?php

namespace QaaDee\MasterServer;

/**
 * @author QaaDee <user.qade@gmail.com>
 */

/**
 * Class MasterServerClient
 *
 * @package QaaDee\MasterServer
 */
class MasterServerClient
{
    /**
     * @var string
     */
    public $address;

    /**
     * @var int
     */
    public $port;

    /**
     * @var string
     */
    public $buffer;

    /**
     * MasterServerClient constructor.
     *
     * @param string $address
     * @param int $port
     * @param string $buffer
     */
    public function __construct($address, $port, $buffer)
    {
        $this->address = $address;
        $this->port = $port;
        $this->buffer = $buffer;
    }
}


?>