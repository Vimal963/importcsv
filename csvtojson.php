<?php
ini_set('memory_limit', '-1');
if(isset($argv[1])){
    if (!($fp = fopen($argv[1], 'r'))) {
        die("Can't open file...");
    }
}else{
    die("Something went wrong!, Argument not found.");
}
$read = readline("Are you sure you want to do this? y/n: ");

if(trim($read) != 'y'){
    echo "Exit!";
    exit;
}

//echo "Are you sure you want to do this? y/n: ";
//$handle = fopen ("php://stdin","r");
//$line = fgets($handle);
//if(trim($line) != 'y'){
//    echo "ABORTING!\n";
//    exit;
//}


$key = fgetcsv($fp,"102400",",");
$validationMakeKey = array_search('make',$key);
$validationModelKey = array_search('model',$key);


$json = array();
while ($row = fgetcsv($fp,"102400",",")) {

   if(isset($row[$validationMakeKey]) && !$row[$validationMakeKey]){
        die("The make field is required.");
   } else if(isset($row[$validationModelKey]) && !$row[$validationModelKey]){
       die("The model field is required.");
   }

    $json[] = array_combine($key, $row);
}

fclose($fp);
$filename = rand(1,100).".json";
$myfile = fopen($filename, "w") or die("Unable to open file!");
fwrite($myfile, json_encode($json));
fclose($myfile);
//var_dump(json_encode($json));
echo 'Success.';


