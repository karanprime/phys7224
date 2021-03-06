<!DOCTYPE html>
<html>
<body>
 
<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "phys7224";

$hwNo = "hw16";
$tsubmit = "hw16_submit";
$tkey = "hw16_key";
$tgrade = "hw16_grades";
    
// connect to the mySQL database
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// echo "Connected successfully";

$sql = " select * from $tsubmit where hasGraded=0 ";    
$ungraded = $conn->query($sql);
if($ungraded->num_rows > 0){
    while($row = $ungraded->fetch_assoc()){
        // calculate grades and store it in an array
        $points = array();
        foreach($row as $key => $value){
            if($key != 'time' and $key != 'email' and $key != 'hasGraded'){
                $points[$key] = grade_one_entry($conn, $tkey, $key, $value);
            }
        }
        $total = array_sum($points);
        $result = $conn->query("SELECT SUM(points) AS value_sum FROM $tkey"); 
        $maxim = $result->fetch_assoc(); 
        $sum = $maxim['value_sum'];
        //print_r($points);
        
        // write the grade
        // create the student entry for this student
        $tmp = array('email' => "'".$row['email']."'", 
                     'time' => "'".$row['time']."'",
                     'points' => $total,
                     'percent' => $total/$sum );
        $new_entry = array_merge($tmp, $points);
        $columns = implode(", ", array_keys($new_entry));
        $new_row = implode(", ", array_values($new_entry));
        $sql = "insert into $tgrade ($columns) values ($new_row)";
        if($conn->query($sql) == FALSE){
            echo "error:" . $sql . "<br>" . $conn->error;
        }
        
        // change the hasGraded status
        $sql = "update $tsubmit set hasGraded=1 where email='"
             .$row['email']."' and time='".$row['time']."'";
        // echo $sql;
        $conn->query($sql);

        // mail the grade
        mail_grade($conn, $hwNo, $row['email'], $new_entry, $tkey);

    }
}

echo "successfully graded. ", "<br>";
	
$conn->close();

// tkey : the key table
// key  : the problem is about to grade
// value : student's answer
// Note: the answer should be set up first.
function grade_one_entry($conn, $tkey, $key, $value){
    // only return the first answer
    $sql = "select * from $tkey where question = '".$key."' limit 1";
    $result = $conn->query($sql);
    if($result->num_rows == 1){
        $answer = $result->fetch_assoc();
        if($value >= $answer['left_value'] and $value <= $answer['right_value']){
            return $answer['points'];                   
        } else {
            return 0;
        }
    }
    
    // error case
    return 0;
}

function mail_grade($conn, $hwNo, $email, $grade, $tkey){
        $to = $email;
        $subject = "Nonliear Dynamics Online Course : Your grade for $hwNo";
        $txt = " Your grade: \r\n\r\n";
        foreach($grade as $key => $value){
            $txt .= "$key"." : "."$value \r\n";
        }

        $txt .= "\r\n ======================================= \r\n";
        $txt .= "\r\n Assigned points for each problem : \r\n\r\n";
        
        $tmp = $conn->query("select question, points from $tkey");
        while ($answer = $tmp->fetch_assoc()){
            foreach($answer as $key => $value){
                $txt .= "$key" . " : " . "$value \r\n";
            }
        }

        mail($to, $subject, $txt);

}

?>



</body>
</html>
