<?php
$servername = "localhost";
$username = "adriano";
$password = "1a2b3c4d";
$dbname = 'bd2';

$log_file = $argv[1];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}else{
    $sql = "delete from log where id>1";
    mysqli_query($conn, $sql);
    main($conn, $log_file);
}

function main($conn, $log_file){
    $array = explode("\n", file_get_contents($log_file));
    $array = load_initial_values($array, $conn);
    $result = verify_log($array, $conn);
}   

function load_initial_values($array, $conn){
    foreach($array as $line => $lineContent){
        if($lineContent == ""){
            return $array;
        }else{
            save_value($lineContent, $conn);
        }
    }
    return $array;
}

function verify_log($array, $conn){
    $transaction_started = [];
    $transaction_commited = [];
    $operations = [];
    $checkpoint_transactions = null;    

    $array = array_reverse($array);
    
    $ckptEndFounded = null;    
    $ckptStartFounded = null;

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
                if($ckptEndFounded){
                    $order = array("Start CKPT(", ")");
                    $replace = '';
                    $line = str_replace($order, $replace, $line);
                    $elements = explode(",", $line);
    
                    $checkpoint_transactions = $elements;
                    $ckptStartFounded = $index;

                    make_redo(array_reverse($array),$checkpoint_transactions, $transaction_started, $transaction_commited, $conn);
                    exit;
                }
                break;
            case 'commit':
                $transaction_commited[] = $elements[1]; 
                break;
            case 'End':
                $ckptEndFounded = $index;
                break;
            case 'crash':
                break;
            case NULL || '':
                make_redo(array_reverse($array),$checkpoint_transactions, $transaction_started, $transaction_commited, $conn);
                exit;
            default:
                $operations[] = $elements[0];
                break;    
        }   
    }
}

function make_redo($array, $checkpoint_transactions, $transaction_started, $transaction_commited, $conn){

    //transação foi comitada (caso não houver checkpoint)
    foreach($transaction_started as $ckpt){
        if(in_array($ckpt, $transaction_commited)){
            echo 'Fez redo:';
            var_dump($ckpt);
        }else{
            echo 'Não fez redo:';
            var_dump($ckpt);
        }
    }
    //transação foi comitada (caso houver checkpoint)
    if($checkpoint_transactions){
        foreach($checkpoint_transactions as $ckpt){
            if(in_array($ckpt, $transaction_commited)){
                echo 'Fez redo:';
                var_dump($ckpt);
            }else{
                echo 'Não fez redo:';
                var_dump($ckpt);
            }
        }
    }
    //percorre o array fazendo o redo
    foreach($array as $index => $line){
      
        $order = array("<", ">");
        $replace = '';
        $line = str_replace($order, $replace, $line);
        $elements = explode(" ", $line);

        switch($elements[0]){
            case 'start': 
                break;
            case 'Start':
                break;
            case 'commit':
                break;
            case 'End':
                break;
            case 'crash':
                break;
            case NULL || '':
                break;
            default:
                $operation = explode(",", $elements[0]);
                if(count($operation) == 4){
                    if(in_array($operation[0], $checkpoint_transactions) OR in_array($operation[0], $transaction_started)){
                        if(in_array($operation[0], $transaction_commited)){
                            save_value($operation[2].','.$operation[1].'='.$operation[3], $conn);
                        }
                    }
                }
                break;    
        }   
    }

    $sql = "SELECT * FROM log where 1";
    $result = $conn->query($sql);
    
    while($item = mysqli_fetch_array($result)){
        var_dump($item[0].': (A:'.$item[1].' B:'.$item[2].')');
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
        mysqli_query($conn, $sql);
    }else{
        $sql = "INSERT INTO log (id, A, B) VALUES (".$element[1].", ".($element[0] == 'A' ? $values[1] : 'null').", ".($element[0] == 'B' ? $values[1] : 'null').")";
        mysqli_query($conn, $sql);
    }

    return;
}

?>