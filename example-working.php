<?php

ini_set('memory_limit', '-1');

/** Check the argument is exists or not */
if (isset($argv[1])) {
    $fileName = $argv[1];
    if (!($fp = fopen($fileName, 'r'))) {
        die("Can't open file...");
    }
} else {
    die("Something went wrong!, Argument not found.");
}

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

/** User input for rename the column*/
if ($ext != 'xml') {
    $read = readline("You want to update the column field(y/n): ");
    if ($read === 'y') {
        echo "field name: rename field name (can't rename the field then without input press enter.) \n";
    }
}


switch ($ext) {

    case "tsv":
    {
        $handle = fopen($fileName, "r");
        $linesArray = [];
        $fp = fopen('fileTsv.csv', 'w');
        /** Tsv file to convert into an array */
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 102400, "\t")) !== FALSE) {
                $linesArray[] = $data;
            }
            fclose($handle);
        }
        /** Import the data into csv and rename field and check the validation */
        checkValidationWithImport($linesArray, $read, $fp);
        fclose($fp);
        break;
    }
    case "json":
    {
        $fp = fopen('fileJson.csv', 'w');
        $fileGet = file_get_contents($fileName);
        $linesArray = json_decode($fileGet, true);
        /** Push the heading field in array */
        foreach ($linesArray as $key => $line) {
            if (empty($key)) {
                $arrKey = array_keys($line);
                $arrCombine = array_combine($arrKey, $arrKey);
                array_unshift($linesArray, $arrCombine);
                break;
            }
        }
        /** Import the data into csv and rename field and check the validation */
        checkValidationWithImport($linesArray, $read, $fp);
        fclose($fp);
        break;
    }
    case "xml":
    {

        $xml_data = simplexml_load_file($fileName);
        $fp = fopen('fileXml.csv', 'w');
        Csv($xml_data, $fp);
        fclose($fp);
        break;
    }
    case "html":
    {

        $dom = new DOMDocument();
        $html = $dom->loadHTMLFile($fileName);

        /** discard white space */
        $dom->preserveWhiteSpace = false;

        /** the table by its tag name */
        $tables = $dom->getElementsByTagName('table');
        if(!$tables->length){
            die("Table tag not found");
        }

        /** get all rows from the table */
        $rows = $tables->item(0)->getElementsByTagName('tr');
        /** get each column by tag name */
        $cols = $rows->item(0)->getElementsByTagName('th');
        $row_headers = [];
        foreach ($cols as $node) {
            $row_headers[] = $node->nodeValue;
        }
        $linesArray = array();
        /** get all rows from the table */
        $rows = $tables->item(0)->getElementsByTagName('tr');
        foreach ($rows as $row) {
            /** get each column by tag name */
            $cols = $row->getElementsByTagName('td');
            $row = array();
            $i = 0;
            foreach ($cols as $node) {
                $row[] = $node->nodeValue;
                $i++;
            }
            $linesArray[] = $row;
        }
        if(count($row_headers)){
            array_shift($linesArray);
            array_unshift($linesArray,$row_headers);
        }

        $fp = fopen('fileHtml.csv', 'w');
        checkValidationWithImport($linesArray, $read, $fp);
        break;
    }
    case "xls" || "xlsx" || "ods":
    {
        if($ext === 'xlsx'){
            $fName = 'fileXlsx.csv';
        } else if($ext === 'ods'){
            $fName = "fileOds.csv";
        }else{
            $fName = "fileXls.csv";
        }
        $fp = fopen($fName, 'w');
        $linesArray = excelToArray($fileName);
        /** Push the heading field in array */
        foreach ($linesArray as $key => $line) {
            if (empty($key)) {
                $arrKey = array_keys($line);
                $arrCombine = array_combine($arrKey, $arrKey);
                array_unshift($linesArray, $arrCombine);
                break;
            }
        }
        /** Import the data into csv and rename field and check the validation */
        checkValidationWithImport($linesArray, $read, $fp);
        fclose($fp);
        break;
    }
    default:
    {
        die("This file can not import.");
    }

}
echo 'Data import successfully.';

/** Convert XML to csv */
function Csv($xml_data, $i)
{
    foreach ($xml_data->children() as $data) {
        $hasChild = count($data->children()) > 0;

        if (!$hasChild) {
            $arr = array($data->getName(), $data);
            fputcsv($i, $arr, ',', '"');
        } else {

            Csv($data, $i);
        }
    }
}

/** Rename the heading field name and store the required field validation */
function renameHeading($line, $read = "n")
{

    $dataTypeArray = [];
    $validationArray = [];
    if ($read === 'y') {
        foreach ($line as $k => $value) {
            $readValue = readline($value . ": ");
//            $dataTypeValidation = readline($value . " DataType(string/number): ");
            $readValidation = readline($value . " required(y): ");
            if ($readValue) {
                $line[$k] = $readValue;
            }

//            if ($dataTypeValidation == 'number') {
//                array_push($dataTypeArray, $line[$k]);
//            }

            if ($readValidation == 'y') {
                array_push($validationArray, $line[$k]);
            }
        }
    }
    return ["line" => $line, "validation" => $validationArray, "dataType" => $dataTypeArray];
}

function checkValidationWithImport($linesArray, $read, $fp)
{
    $validationKeys = [];
    $validationKeyNames = [];
//    $dataTypeKeys = [];
    foreach ($linesArray as $key => $line) {

        if (empty($key)) {
            /** Rename the heading field name and store the required field validation */
            $renameHeading = renameHeading($line, $read);

            $line = $renameHeading['line'];
            $validations = $renameHeading['validation'];
            $dataTypeKeys = $renameHeading['dataType'];

            /** Create a new validation array for get the key and heading field name */
            foreach ($validations as $validationField) {
                $validationKey = array_search($validationField, $line);
                array_push($validationKeys, $validationKey);
                $validationKeyNames[$validationKey] = $line[$validationKey];
            }
        } else {
            /** Check the required field validation */
            foreach ($validationKeys as $validationKey) {

                if (isset($line[$validationKey]) || !$line[$validationKey]) {
                    die("The " . $validationKeyNames[$validationKey] . " field is required.");

                }
            }
        }
        /** Store the data in csv format */
        fputcsv($fp, $line);
    }
}


function excelToArray($filePath, $header=true){
    require('PHPExcel/IOFactory.php');

    /**  Create excel reader after determining the file type */
    $inputFileName = $filePath;
    /**  Identify the type of $inputFileName  */
    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    /**  Create a new Reader of the type that has been identified  */
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    /** Set read type to read cell data onl */
    $objReader->setReadDataOnly(true);
    /**  Load $inputFileName to a PHPExcel Object  */
    $objPHPExcel = $objReader->load($inputFileName);
    /** Get worksheet and built array with first row as header */
    $objWorksheet = $objPHPExcel->getActiveSheet();

    /** excel with first row header, use header as key */
    if($header){
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $headingsArray = $objWorksheet->rangeToArray('A1:'.$highestColumn.'1',null, true, true, true);
        $headingsArray = $headingsArray[1];

        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A'.$row.':'.$highestColumn.$row,null, true, true, true);
            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                ++$r;
                foreach($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                }
            }
        }
    }
    else{
        /** excel sheet with no header */
        $namedDataArray = $objWorksheet->toArray(null,true,true,true);
    }

    return $namedDataArray;
}

