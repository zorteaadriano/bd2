<?php
$servername = "localhost";
$username = "adriano";
$password = "1a2b3c4d";
$dbname = 'bd2';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}else{
    main($conn);
}

function main($conn){
    $array = explode("\n", file_get_contents('entradaLog'));
    $array = load_initial_values($array, $conn);
    $result = verify_log($array, $conn);
}   

function load_initial_values($array, $conn){
    foreach($array as $line => $lineContent){
        if($lineContent == ""){
            unset($array[$line]);
            return $array;
        }else{
            save_initial_value($lineContent, $conn);
            unset($array[$line]);
        }
    }
    return $array;
}

function verify_log($array, $conn){
    foreach($array as $line){
        $elements = explode(' ', $line);
        if(count($elements) > 1){
        
        }
        switch($line){
            
            default:
                print_r($line);
        }
    }
}

function save_initial_value($array, $conn){
    $values = explode('=', $array);
    $element = explode(',', $values[0]);

    $sql = "SELECT * FROM log where id=".$element[1];
    $result = $conn->query($sql);
    $item = mysqli_fetch_array($result);

    if($item == null ? 0 : 1){
        $sql = "UPDATE log set ".$element[0]."=".$values[1]." where id = ".$element[1];
    
        if (mysqli_query($conn, $sql)) {
        echo "<br>Data changed successfully";
        } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }

    }else{
        $sql = "INSERT INTO log (id, A, B) VALUES (".$element[1].", ".($element[0] == 'A' ? $values[1] : 'null').", ".($element[0] == 'B' ? $values[1] : 'null').")";
        if (mysqli_query($conn, $sql)) {
        echo "<br>New record created successfully";
        } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    }

    return;
}

?>