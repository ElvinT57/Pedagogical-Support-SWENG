<?php
/**
 * @param array 2D array of the records containing the Name Column.
 */
function sortTable($table, $field){
    $len = count($table) - 1;
    $swapped = false;

    do{
        $swapped = false;
        for($i = 0; $i < $len; $i++){
            if(strcmp($table[$i][$field], $table[$i+1][$field]) > 0){
                $temp = $table[$i];
                $table[$i] = $table[$i+1];
                $table[$i+1] = $temp;
                $swapped = true;
            }
        }
        $len--;
    }while($swapped);

    return $table;
}