<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\Dotenv\Dotenv;

new Dotenv()->load('.env');

return DependencyFactory::fromConnection(
    new PhpFile('migrations.php'),
    new ExistingConnection(
        DriverManager::getConnection([
            'dbname' => $_ENV['MYSQL_DATABASE'],
            'user' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASSWORD'],
            'host' => 'database',
            'driver' => 'pdo_mysql',
        ])
    )
);
