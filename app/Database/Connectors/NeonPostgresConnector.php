<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPostgresConnector extends PostgresConnector
{
    /**
     * Create a DSN string from a configuration.
     * Adds Neon endpoint option for clients without SNI support (e.g. older XAMPP libpq).
     *
     * @param  array  $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        if (! empty($config['endpoint'])) {
            $host = (string) ($config['host'] ?? '');
            // Pooler hostnames already convey the endpoint via SNI; forcing options=endpoint conflicts.
            if (! str_contains($host, '-pooler')) {
                $endpoint = preg_replace('/[^a-zA-Z0-9\-]/', '', $config['endpoint']);
                $dsn .= ";options=endpoint={$endpoint}";
            }
        }

        return $dsn;
    }
}
