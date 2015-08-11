<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 8/6/15
 * Time: 4:35 PM
 */

    echo "start benchmark...\n";
    $requests = 100000;
    $clients = array(1,5,10,20,30,50,100,200);
    $x = '#';

    $memset = "";
    $memget = "";
    $redisset = "";
    $redisget = "";

    echo "memcache benchmark...\n";
    foreach($clients as $client){
        $set = 0;
        $get = 0;
        foreach(range(0,2) as $k) {
            exec("php ./benchmark.php -c $client -r $requests", $mbuf);
            sleep(1);
            exec("php ./benchmark.php -c $client -r $requests -m 2", $mbuf);
            sleep(1);
            $set += $mbuf[0];
            $get += $mbuf[1];
            unset($mbuf);
        }
        $set = intval($requests/($set/3));
        $get = intval($requests/($get/3));

        $memset = $memset."$client,$set ";
        $memget = $memget."$client,$get ";
        printf("progress:[%-8s]%d clients...\r", $x, $client);
        $x = "#".$x;
    }
    echo "\n";
    echo $memset."\n";
    echo $memget."\n";

    echo "redis benchmark...\n";
    foreach($clients as $client){
        $set = 0;
        $get = 0;
        foreach(range(0,2) as $k) {
            exec("php ./benchmark.php -c $client -r $requests -p 6379", $rbuf);
            sleep(1);
            exec("php ./benchmark.php -c $client -r $requests -m 2 -p 6379", $rbuf);
            sleep(1);
            $set += $rbuf[0];
            $get += $rbuf[1];
            unset($rbuf);
        }
        $set = intval($requests/($set/3));
        $get = intval($requests/($get/3));

        $redisset = $redisset."$client,$set ";
        $redisget = $redisget."$client,$get ";
        printf("progress:[%-8s]%d clients...\r", $x, $client);
        $x = "#".$x;
    }
    echo "\n";
    echo $redisset."\n";
    echo $redisget."\n";

    $mysql = new mysqli("162.243.121.98","mysql","mysql888","myblog","3306");
    $sql = "INSERT INTO benchmark (redisset,redisget,memset,memget) values ('{$redisset}','{$redisget}','{$memset}','{$memget}')";
    $mysql->query($sql);


