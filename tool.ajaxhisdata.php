<?php
/**
 * Created by PhpStorm.
 * User: jianlinz
 * Date: 2015/7/29
 * Time: 16:35
 */

include_once "config.php";
include_once "wx_iot.class.php";
header("Content-type:text/html;charset=utf-8");
//访问数据库数据
$db = new class_mysql_db();

$tmp = 234;
echo $tmp;

//以下是纯粹的官方例子，未来需要仔细重新修改，以便从累计数据库表单中中获取数据

/*
$q=$_GET["q"];

$con = mysql_connect('localhost', 'peter', 'abc123');
if (!$con)
{
    die('Could not connect: ' . mysql_error());
}

mysql_select_db("ajax_demo", $con);

$sql="SELECT * FROM user WHERE id = '".$q."'";

$result = mysql_query($sql);

echo "<table border='1'>
<tr>
<th>Firstname</th>
<th>Lastname</th>
<th>Age</th>
<th>Hometown</th>
<th>Job</th>
</tr>";

while($row = mysql_fetch_array($result))
{
    echo "<tr>";
    echo "<td>" . $row['FirstName'] . "</td>";
    echo "<td>" . $row['LastName'] . "</td>";
    echo "<td>" . $row['Age'] . "</td>";
    echo "<td>" . $row['Hometown'] . "</td>";
    echo "<td>" . $row['Job'] . "</td>";
    echo "</tr>";
}
echo "</table>";

mysql_close($con);
*/
?>