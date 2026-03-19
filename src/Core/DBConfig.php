<?php

/**
 * DBConfig reads DB connection settings from .env via Env class.
 */

class DBConfig
{
    public function getHost(): string
    {
        return Env::get('DB_HOST', '127.0.0.1');
    }

    public function getPort(): string
    {
        return Env::get('DB_PORT', '3306');
    }

    public function getDatabase(): string
    {
        return Env::get('DB_DATABASE', '');
    }

    public function getUsername(): string
    {
        return Env::get('DB_USERNAME', '');
    }

    public function getPassword(): string
    {
        return Env::get('DB_PASSWORD', '');
    }

    public function getCharset(): string
    {
        return Env::get('DB_CHARSET', 'utf8mb4');
    }
}
