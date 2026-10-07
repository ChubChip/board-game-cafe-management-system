<?php
//mysqli_connect("server","username","password","database_name");

$conn = mysqli_connect("localhost","root","","boardgame"); //Loacalhost
// $conn = mysqli_connect("fdb1028.awardspace.net","4791339_tabletalk","1910ChubChip47?","4791339_tabletalk"); //AWARDSPACE

mysqli_set_charset($conn, "utf8");//SET LANGUAGE TO DATABASE

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>