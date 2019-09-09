<?php

namespace App\Extensions\Queue;

use InvalidArgumentException;
use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Resque;

/**
 * Class ResqueQueue
 *
 * @package App\Extensions\Queue
 */
class ResqueQueue extends Queue implements QueueContract
{
    /** @var string The connection name. */
    protected $connection;

    /** @var string The name of the default queue. */
    protected $default;

    /** @var int The expiration time of a job. */
    protected $retryAfter = 60;

    /**
     * Create a new Resque queue instance.
     *
     * @param  string $default
     * @param  string $connection
     * @param  int $retryAfter
     * @return void
     */
    public function __construct($default = 'default', $connection = null, $retryAfter = 60)
    {
        $this->default = $default;
        $this->connection = $connection;
        $this->retryAfter = $retryAfter;
        $this->setConnection();
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     * @return int
     */
    public function size($queue = null)
    {
        return Resque::size($queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     * @param  boolean $track
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null, $track = false)
    {
        $jobName = ((is_object($job) && property_exists($job, 'jobName'))) ? $job->jobName : $job;

        $data = ((is_object($job) && property_exists($job, 'data'))) ? $job->data : $data;
        if (!is_array($data)) {
            $data = $data;
        }

        $queue = (is_null($queue) ? $jobName : $queue);

        return Resque::enqueue($queue, $jobName, $data, $track);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {

    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int $delay
     * @param  string|object $job
     * @param  mixed $data
     * @param  string $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {

    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @return array|\Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        return Resque::pop($queue);
    }

    /**
     * Set the connection.
     */
    protected function setConnection()
    {
        if (is_null(Resque::$redis)) {
            $this->resolve($this->connection);
        }
    }

    /**
     * Resolve the given connection by name.
     *
     * @param string|null $name
     *
     * @throws \InvalidArgumentException
     */
    public function resolve($name = null)
    {
        $name = $name ?: 'default';

        $config = config('database.redis.' . $name);

        if (isset($config)) {

            if (!isset($config['host'])) {
                $config['host'] = '127.0.0.1';
            }

            if (!isset($config['port'])) {
                $config['port'] = 6379;
            }

            if (!isset($config['database'])) {
                $config['database'] = 0;
            }

            return Resque::setBackend($config['host'] . ':' . $config['port'], $config['database']);
        }

        throw new InvalidArgumentException(
            "Resque connection [{$name}] not configured."
        );
    }
}