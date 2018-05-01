<?php
namespace App\Lib;

use App\Lib\Config;
use PDO;

class Core {
    public $dbh; // handle of the db connexion
    private static $instance;

    private function __construct() {
        $dsn = Config::read('db.driver').
            ':host=' .Config::read('db.host') .
            ';dbname='. Config::read('db.basename') .
            ';port='. Config::read('db.port') .
            ';connect_timeout=15';

        $user = Config::read('db.username');
        $password = Config::read('db.password');

        //опции подключения
        $opt = array(
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES      => true,
            PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES utf8"
        );


        $this->dbh = new PDO($dsn, $user, $password,$opt);
    }

    public static function getInstance() {
        if (!isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }

    // others global functions
}