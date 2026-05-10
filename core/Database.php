<?php

class Database
{
    // Create a private variable to hold the connection
    private $connection;

    public function __construct()
    {
        // Parse the .env file to get the database connection details
        $env = parse_ini_file(__DIR__ . '/../.env');

        // Create connection with secret env variables
        $this->connection = mysqli_connect(
            $env['DB_HOST'],
            $env['DB_USER'],
            $env['DB_PASSWORD'],
            $env['DB_NAME'],
            $env['DB_PORT']
        );

        // If the connection fails die with an error message
        if (!$this->connection) {
            die("Database connection failed");
        }
    }

    // If the connection is succesful return the connection
    public function getConnection()
    {
        return $this->connection;
    }
}