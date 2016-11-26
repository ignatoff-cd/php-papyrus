<?php

error_reporting(~E_WARNING);

$ip = gethostbyname(gethostname());
echo 'Client IP: '. $ip ."\n";

$clientPort = 11500;
$serverPort = 59569;
$remoteClientPort = 50001;

$ipPool = array();

if(!($clientSock  = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)))
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die("Couldn't create Client socket: [$errorcode] $errormsg \n");
}
 
echo "Client socket created \n";

if(!($serverSock  = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)))
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die("Couldn't create Server socket: [$errorcode] $errormsg \n");
}
 
echo "Server socket created \n";

if( !socket_bind($clientSock , "0.0.0.0" , $clientPort) )
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die("Could not bind socket : [$errorcode] $errormsg \n");
}
 
echo "Client socket bind OK \n";

if( !socket_bind($serverSock , $ip, $serverPort) )
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die("Could not bind socket : [$errorcode] $errormsg \n");
}
 
echo "Server socket bind OK \n";

while(true)
{
    $r = socket_recvfrom($clientSock , $buf, 1024, 0, $remote_ip, $remote_port);
    echo "$remote_ip : $remote_port -- " . $buf."\n";
    
    if($buf == "IS_SERVER"){
        $r = socket_sendto($serverSock , $ip, strlen($ip) , 0 , $remote_ip , $remote_port);
        echo "SEND-TO --> $remote_ip : $remote_port -- " . $ip."\n";
    }    
    elseif($buf == "REGISTER"){
        $ipPool[$remote_ip] = 1;
        print_r($ipPool);
        $ipPoolSize = count($ipPool);
    }
    elseif('SIZE' == substr($buf,0,4)){
        $params = explode('|',$buf);
        $ipPool[$remote_ip] = array('width' => $params[1], 'height'=>$params[2]);
    }
    else{
        if(isset($ipPoolSize) && $ipPoolSize > 1 ){
            foreach($ipPool as $bindIp => $isSet){
                if($bindIp != $remote_ip){
                    $command = (object) json_decode($buf);
                    $command->pointX = round($command->pointX * $ipPool[$bindIp]['width'] / $ipPool[$remote_ip]['width'],6);
                    $command->pointY = round($command->pointY * $ipPool[$bindIp]['height'] / $ipPool[$remote_ip]['height'],6);
                    $buf = json_encode($command);
                    echo "SEND-TO --> $bindIp : $remoteClientPort -- " . $buf."\n";
                    $r = socket_sendto($serverSock , $buf, strlen($buf) , 0 , $bindIp , $remoteClientPort);
                }
            }
        }
    }
}
socket_close($clientSock );