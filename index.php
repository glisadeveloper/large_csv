<?php
$start_time = microtime(true);
function csv_get_contents_chunked($link, $file, $chunk_size, $queryPrefix, $callback)
{
    try {
        $handle = fopen($file, "r");
        $i = 0;
        while (! feof($handle)) {
            call_user_func_array($callback, array(
                fread($handle, $chunk_size),
                &$handle,
                $i,
                &$queryPrefix,
                $link
            ));
            $i ++;
        }
        fclose($handle);
    } catch (Exception $e) {
        trigger_error("csv_get_contents_chunked::" . $e->getMessage(), E_USER_NOTICE);
        return false;
    }
    return true;
}

function str_rep($str){    
    $search = array(',-', ',"', '"');  
    return str_replace($search, '', $str);
}

function db_date($date_inp){
    if (preg_match_all('#\d{2}/\d{2}/\d{4}#', $date_inp, $results)){
       return $results[0][0];
    }else{
       return '00/00/0000';
    }
}

$link = mysqli_connect("localhost", "root", "", "brd");
$success = csv_get_contents_chunked($link, "brd.csv", 2048, '', function ($chunk, &$handle, $iteration, &$queryPrefix, $link) {
    $TABLENAME = 'books ';
    $chunk = $queryPrefix . $chunk;

    $lineArray = preg_split('/\r\n|\n|\r/', $chunk);
    $numberOfRecords = count($lineArray);
    $query = ' REPLACE INTO ' . $TABLENAME . '(ID, Series, Number, Name, Type, Publisher, Author, Price, ReleaseDate) VALUES ';
 
    for ($i = 0; $i < $numberOfRecords - 2; $i ++) {
        // split single CSV row to columns
        $colArray = explode(',', $lineArray[$i]);
        $query = $query . '("' . str_rep($colArray[0]) . '","' . str_rep($colArray[1]) . '","' . str_rep($colArray[2]) . '","'.str_rep($colArray[3]).'","'.str_rep($colArray[4]).'","'.str_rep($colArray[5]).'","'.str_rep($colArray[6]).'","'.str_rep($colArray[7]).'","'.str_rep(db_date($colArray[8])).str_rep((!empty($colArray[9]) ? '","'. '' : '' )).'"),';
    }
    // last row without a comma
    $colArray = explode(',', $lineArray[$i]);
    $query = $query . '("' . str_rep($colArray[0]) . '","' . str_rep($colArray[1]) . '","' . str_rep($colArray[2]) . '","'.str_rep($colArray[3]).'","'.str_rep($colArray[4]).'","'.str_rep($colArray[5]).'","'.str_rep($colArray[6]).'","'.str_rep($colArray[7]).'","'.str_rep(db_date($colArray[8])).str_rep((!empty($colArray[9]) ? '","'.'' : '') ).'")';  
    $i = $i + 1;

    $queryPrefix = $lineArray[$i];
    mysqli_query($link, $query) or die(mysqli_error($link));
    /*
     
     */
});

if (! $success) {
    // It Failed
    echo "Something is wrong, make some logic here ...";
}else{
    echo 'Import time for CSV to MySQL is: '.(number_format(microtime(true) - $start_time, 2)).' seconds';
}

?>