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
            save_value($lineContent, $conn);
            unset($array[$line]);
        }
    }
    return $array;
}

function verify_log($array, $conn){
    $transaction_started = [];
    $transaction_commited = [];
    $transaction_ended = [];
    $operations = [];

    foreach($array as $index => $line){

        $order = array("<", ">");
        $replace = '';
        $line = str_replace($order, $replace, $line);
        $elements = explode(" ", $line);

        switch($elements[0]){
            case 'start':
                $transaction_started[] = $elements[1]; 
                break;
            case 'Start':
                $transaction_started_CKPT[] = $elements[1]; 
                break;
            case 'commit':
                $transaction_commited[] = $elements[1]; 
                break;

            case 'End':
                foreach($transaction_started_CKPT as $checkpoint){
                    $order2 = array("CKPT(", ")");
                    $replace2 = '';
                    $elements_checkpoint = str_replace($order2, $replace2, $checkpoint);
                    $elements_checkpoint = explode(',', $elements_checkpoint);
                    
                    foreach($elements_checkpoint as $ckpt_finished){

                        foreach($operations as $line => $op){
                            $elements_op = explode(",", $op);

                            if($elements_op[0] == $ckpt_finished){
                                unset($operations[$line]);
                            }
                        }

                        foreach($transaction_commited as $key => $value){
                            if($value == $ckpt_finished){
                                unset($transaction_commited[$key]);
                            }
                        }
                    } 
                }

                break;
            default:
                $operations[] = $elements[0]; 
                break;    
        }   
    }
    

    echo '<br>Realizou Redo: ';
    foreach($transaction_commited as $commit){
        echo '('.$commit.')';

        foreach($operations as $op){
            $elements_op = explode(",", $op);
            if($elements_op[0] == $commit){
                save_value($elements_op[2].','.$elements_op[1].'='.$elements_op[3], $conn);
            }
        }



    }

    $sql = "SELECT * FROM log where 1";
    $result = $conn->query($sql);
    
    while($item = mysqli_fetch_array($result)){
        echo('<br>'.$item[0].': (A:'.$item[1].' B:'.$item[2].')');
    }

}

function save_value($lineContent, $conn){

    $values = explode('=', $lineContent);
    $element = explode(',', $values[0]);

    $sql = "SELECT * FROM log where id=".$element[1];
    $result = $conn->query($sql);
    $item = mysqli_fetch_array($result);

    if($item == null ? 0 : 1){
        $sql = "UPDATE log set ".$element[0]."=".$values[1]." where id = ".$element[1];
    
        if (mysqli_query($conn, $sql)) {
        // echo "<br>Data changed successfully";
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