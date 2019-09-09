<?php

namespace App\Extensions\Queue\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;
use App\Extensions\Queue\ResqueQueue;

/**
 * Class ResqueConnector
 *
 * @package App\Extensions\Queue\Connectors
 */
class ResqueConnector implements ConnectorInterface
{
    /**
     * The connection name.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new Redis queue connector instance.
     *
     * @param  string|null $connection
     * @return void
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new ResqueQueue(
            $config['queue'],
            $config['connection'] ?? $this->connection,
            $config['retry_after'] ?? 60
        );
    }
}