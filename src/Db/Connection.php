<?php

namespace Mailserver\Db;

/**
 * It is a MySQL PDO Connection
 */
class Connection
{
	static private $instance;

	static private $config;

	static public function setConfig(array $config)
	{
		self::$config = $config;
	}

	static public function getInstance()
	{
		if (!self::$instance) {
			$dsn = sprintf('mysql:dbname=%s;host=%s', self::$config['db'], self::$config['host']);
			self::$instance = new \PDO($dsn, self::$config['user'], self::$config['password']);
		}

		return self::$instance;
	}
}
