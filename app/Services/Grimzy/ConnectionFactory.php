<?php

namespace App\Services\Grimzy;

use Grimzy\LaravelMysqlSpatial\Connectors\ConnectionFactory as GrimzyConnectionFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Connectors\ConnectionFactory as IlluminateConnectionFactory;

class ConnectionFactory extends GrimzyConnectionFactory
{
    protected function createConnection(
        $driver,
        $connection,
        $database,
        $prefix = '',
        array $config = []
    ): ConnectionInterface {
        if ($this->container->bound($key = "db.connection.{$driver}")) {
            return $this->container->make($key, [$connection, $database, $prefix, $config]);
        }

        // Call the parent's parent `createConnection`.
        return IlluminateConnectionFactory::createConnection(
            $driver,
            $connection,
            $database,
            $prefix,
            $config
        );
    }
}
