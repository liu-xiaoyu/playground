<?php
// server.php #2 socket server (port 8000)
$socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $err) or die($err);
$conns = array($socket);
$conn_ids = array(0);
$conn_user = array();
$msgs = array();

// server loop
while (true) {
  $reads = $conns;
  // get number of connections with new data
  $mod = stream_select($reads, $write, $except, 5);
  if ($mod===false) break;

  foreach ($reads as $read) {
    if ($read===$socket) {
      $conn = stream_socket_accept($socket);
      $recv = fread($conn, 1024);
      if (empty($recv)) continue;

      if (strpos($recv, "GET / ")===0) {
        // serve static html page from memory
        fwrite($conn, "HTTP/1.1 200 OK\r\n". "Connection: close\r\n".
          "Content-Type: text/html; charset=UTF-8\r\n\r\n");
        fwrite($conn, $html);
        stream_socket_shutdown($conn, STREAM_SHUT_RDWR);

      } else if (strpos($recv, "GET /msg/")===0) {
        // ajax request: send a message
        // syntax: GET /msg/user_from/user_to%20message
        // e.g. GET /msg/john/mary%20hello
        stream_socket_shutdown($conn, STREAM_SHUT_RDWR);
        preg_match("!GET /msg/([^/]+)/(\S+)!", $recv, $match);
        $user = $match[1];
        $match[2] = urldecode($match[2]);
        if (!strpos($match[2], " ")) continue;
        list($target, $msg) = explode(" ", $match[2], 2);

        if ($target=="all") {
          // send message to all users
          foreach ($conns as $i=>$conn) {
            if ($i!=0) fwrite($conn, "data: ".$user." to all: ".$msg."\n\n");
          }

        } else if (isset($conn_user[$target])) {
          // send message to one user and to the originator
          if ($target!=$user) foreach ($conn_user[$target] as $conn) {
            fwrite($conn, "data: ".$user.": ".$msg."\n\n");
          }
          if (isset($conn_user[$user])) foreach ($conn_user[$user] as $conn) {
            fwrite($conn, "data: You to ".$target.": ".$msg."\n\n");
          }

        } else {
          // user is offline, keep message in memory for later delivery
          if (!isset($msgs[$target])) $msgs[$target] = "";
          $msgs[$target] .= "data: ".$user." (".@date("Y-m-d H:i")."): ".$msg."\n\n";
          foreach ($conn_user[$user] as $conn) {
            fwrite($conn, "data: You to ".$target." (offline): ".$msg."\n\n");
          }
        }

      } else if (strpos($recv, "text/event-stream")===false) {
        // block other requests like favicon.ico
        stream_socket_shutdown($conn, STREAM_SHUT_RDWR);

      } else {
        // login as new user
        // syntax: GET /username e.g. GET /john
        preg_match("!GET /(\S+)!", $recv, $match);
        if (!isset($match[1])) continue;
        $user = $match[1];
        echo "connect ".$user." from ".stream_socket_get_name($conn, true)."\n";

        fwrite($conn, "HTTP/1.1 200 OK\r\n". "Connection: close\r\n".
          "Content-Type: text/event-stream\r\n\r\n");
        fwrite($conn, "data: Welcome ".$user."!\n\n");
        fwrite($conn, "data: now online: ".implode(", ", array_keys($conn_user))."\n\n");

        // deliver messages sent when user was offline
        if (isset($msgs[$user])) {
          fwrite($conn, $msgs[$user]);
          unset($msgs[$user]);
        }
        // notify other users
        foreach ($conns as $i=>$c) {
          if ($i!=0) fwrite($c, "data: ".$user." has joined.\n\n");
        }
        // register connection in pool
        $conns[] = $conn;
        $conn_ids[] = $user;
        // allow multiple connections for 1 user
        $conn_user[$user][] = $conn;
      }
    } else {
      $data = fread($read, 1024);
      if ($data=="" or $data===false) {
        // user/browser closed connection
        if ($data!==false) stream_socket_shutdown($read, STREAM_SHUT_RDWR);
        $conn_id = array_search($read, $conns, true);
        unset($conns[$conn_id]);

        // unregister connection for user
        $user = $conn_ids[$conn_id];
        unset($conn_ids[$conn_id]);
        $conn_id = array_search($read, $conn_user[$user], true);
        unset($conn_user[$user][$conn_id]);

        if (empty($conn_user[$user])) {
          unset($conn_user[$user]);
          // notify other users
          foreach ($conns as $i=>$c) {
            if ($i!=0) fwrite($c, "data: ".$user." has left.\n\n");
} } } } } }
?>
