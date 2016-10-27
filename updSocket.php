<?php

error_reporting(~E_WARNING);

$ip = getHostByName(getHostName());
echo 'Client IP: '. $ip ."\n";

$clientPort = 11500;
$serverPort = 59569;
$remoteClientPort = 50001;

$ipPool = array();

//Create a UDP socket
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

// Bind the source address
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
/*$mysqli = new mysqli('localhost', 'root', '12345,rabbit', 'test');
if (!($stmt = $mysqli->prepare("insert into queue (ip, command) values (inet_aton(?), ?)"))){
    echo  $mysqli->errno . " " . $mysqli->error . "\n";
}*/
//Do some communication, this loop can handle multiple clients
while(1)
{
   
    //Receive some data
    $r = socket_recvfrom($clientSock , $buf, 1024, 0, $remote_ip, $remote_port);
    echo "$remote_ip : $remote_port -- " . $buf."\n";
    if($buf == "REGISTER"){
        $ipPool[$remote_ip] = 1;
        print_r($ipPool);
        $ipPoolSize = count($ipPool);
    } else{
        if(isset($ipPoolSize) && $ipPoolSize > 1 ){
            foreach($ipPool as $bindIp => $isSet){
                if($bindIp != $remote_ip){
                    $r = socket_sendto($serverSock , $buf, strlen($buf) , 0 , $bindIp , $remoteClientPort);
                }
            }
        }
    }

    //$stmt->bind_param("ss", $remote_ip, $buf);
    //$stmt->execute();

     
    //Send back the data to the client
    //$resp = "OK " . $buf ;
    //$r = socket_sendto($clientSock , $resp, strlen($resp) , 0 , $remote_ip , $remote_port);
}
//$mysqli->close();
socket_close($clientSock );