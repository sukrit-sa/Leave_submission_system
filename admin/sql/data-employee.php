<?php
include('conn/conn.php');

$sql3 = "SELECT * FROM staffstatus"; 
$result3 = $conn->query($sql3);

$sql5 = "SELECT *  FROM position";
$result5 = $conn->query($sql5);

$sql6 = "SELECT *  FROM prefix";
$result6 = $conn->query($sql6);

$sql7 = "SELECT *  FROM subdepart";
$result7 = $conn->query($sql7);

$sql8 = "SELECT *  FROM headepart";
$result8 = $conn->query($sql8);

$sql4 = "SELECT employees.id,employees.fname,employees.lname,staffstatus.staffid, CONCAT(prefix.prefixname,employees.fname, ' ', employees.lname) AS fullname, 
                employees.department,subdepart.subdepartname,staffstatus.staffname AS staffstatus ,position.positionname,position.positionid,startwork,prefixid,employees.email,employees.pic,startappoint
         FROM employees 
         LEFT JOIN staffstatus ON employees.staffstatus = staffstatus.staffid
         LEFT JOIN subdepart ON employees.department = subdepart.subdepartid
         LEFT JOIN prefix on employees.prefix = prefix.prefixid
         LEFT JOIN position on employees.position = position.positionid
         ";

$result4 = $conn->query($sql4);
?>