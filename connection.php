<?php
 
 
 function OpenCon() {
 $dbhost = "rei.cs.ndsu.nodak.edu";
 $dbuser = "rivers_martin_371s26";
 $dbpass = "6lGZXy2TKN4!";
 $dbname = "rivers_martin_db371s26";
 $conn = new mysqli($dbhost, $dbuser, $dbpass,$dbname) or die("Connect failed: %s\n". $conn -> error);
 return $conn;
 }
 function CloseCon($conn)
 {
 $conn -> close();
 }
 ?>