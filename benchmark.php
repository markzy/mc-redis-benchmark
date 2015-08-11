#!/usr/local/bin/php
<?php
    /**
     * Created by PhpStorm.
     * User: Mark
     * Date: 8/4/15
     * Time: 3:29 PM
     */
    define("MEMCACHED",11211);
    define("REDIS",6379);
    define("SET",1);
    define("GET",2);

//    class Stat extends Threaded{
//        protected $time = 0;
//        protected function increment($val){$this->time = $this->time+$val;}
//    }

    class Config{
        public static $port = 11211;
        public static $requests = 100000;
        public static $datasize = 32;
//        public static $keysize;
        public static $clients = 50;
        public static $mode = 1;
    }

    class Common{
        public static function parse_option(){
            $options = getopt("r:p:c:d:m:");
            if(isset($options['r'])) Config::$requests = intval($options['r']);
            if(isset($options['p'])) Config::$port = intval($options['p']);
            if(isset($options['c'])) Config::$clients = intval($options['c']);
            if(isset($options['d'])) Config::$datasize = intval($options['d']);
            if(isset($options['m'])) Config::$mode = intval($options['m']);
        }
    }

    class kv extends Stackable{
        private $mode;
        private $key;
        private $value;

        public function __construct($mode,$key,$value=0){
            $this->mode = $mode;
            $this->key = $key;
            $this->value = $value;
        }

        public function run(){
            $Connection = $this->worker->getConnection();
            if($this->mode == SET)
                $Connection->set($this->key,$this->value);
            if($this->mode == GET)
                $Connection->get($this->key);

        }
    }

    class Client extends Worker{
        public static $con;
        public static $port;

        public function __construct(){
        }

        public function run(){
            self::$port = Config::$port;
            if(self::$port == MEMCACHED) self::$con = new Memcache();
            elseif(self::$port == REDIS) self::$con = new Redis();
            else throw new Exception("Wrong port specified\n");
            if(!self::$con->connect("127.0.0.1",self::$port)) throw new Exception("Fail to connect");
        }

        public function getConnection(){
            return self::$con;
        }
    }

//    class Benchmark{
//        public $ClientPool;
//
//        public function __construct($clients){
//            $this->ClientPool = new Pool($clients,Client::class);
//        }
//
//        public function set($key,$value){
//            $this->ClientPool->submit(new kv(SET,$key,$value));
//        }
//
//        public function get($key){
//            $this->ClientPool->submit(new kv(GET,$key));
//        }
//
//        public function stop(){
//            $this->ClientPool->shutdown();
//        }
//    }

    class FlushHelper{
        public static function run(){
            if(Config::$port == MEMCACHED) {
                $memcache = new Memcache();
                if (!$memcache->connect("127.0.0.1", 11211)) throw new Exception("Fail to connect Memcache");
                $memcache->flush();
            }
            if(Config::$port == REDIS) {
                $redis = new Redis();
                if (!$redis->connect("127.0.0.1")) throw new Exception("Fail to connect Redis");
                $redis->flushAll();
            }
        }
    }

    Common::parse_option();

    if(Config::$mode == 1) {
        $bench1 = new Pool(Config::$clients,Client::class);
        foreach (range(0, Config::$requests - 1) as $key) {
            $requests[] = new kv(SET,rand(1,Config::$requests - 1), str_pad($key, Config::$datasize, "_", STR_PAD_LEFT));
        }
        $time = microtime(TRUE);
        foreach (range(0, Config::$requests - 1) as $key) {
            $bench1->submit($requests[$key]);
        }
        $bench1->shutdown();
        $time = microtime(TRUE) - $time;
        echo "$time\n";
//        FlushHelper::run();
    }

    if(Config::$mode == 2) {
        $bench2 = new Pool(Config::$clients,Client::class);
        foreach (range(0, Config::$requests - 1) as $key) {
            $requests[] = new kv(GET,rand(1,Config::$requests - 1));
        }
        $time = microtime(TRUE);
        foreach (range(0, Config::$requests - 1) as $key) {
            $bench2->submit($requests[$key]);
        }
        $bench2->shutdown();
        $time = microtime(TRUE) - $time;
        echo "$time\n";
//        FlushHelper::run();
    }


