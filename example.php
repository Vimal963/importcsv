<?php

ini_set('memory_limit', '-1');

/** Class example */
Class example {

    public $argv;
    public $secondArgument;
    public $i;

    /** constructor to store all the passed arguments */
    public function __construct($argv)
    {
        $this->argv = $argv;
        $this->secondArgument = isset($argv[2]) ? $argv[2] : null;
        $this->i = 0;
    }

    /** Switching the type of file passed and based on that calling their respective functions */
    public function convertIntoCsv(){

        $error = 0;

        /** Check the argument is exists or not */
        do{
            $this->i++;

            if($this->i == 2 && $error == 1){
                $fileName = $this->argv[1] = readline("Enter File Name Again(Last Chance): ");
            }

            if (isset($this->argv[1])) {
                $fileName = $this->argv[1];

                $optionsArray = ['--unique-combination'];

                if(isset($this->argv[2])){
                    if(!in_array($this->argv[2], $optionsArray)){
                        echo 'Invalid Argument '. $this->argv[2];
                        die;
                    }
                }

                try{
                    if ( !file_exists($fileName) ) {

                        $error = 1;
                        throw new Exception("File not found.\n");
                    }
                    if (!fopen($fileName, 'r')) {
                        $error = 1;
                        throw new Exception("Can't open file... \n");
                    }
                }catch (Exception $e){
                    echo $e->getMessage();

                    if($this->i >= 2){
                        die;
                    }
                }

            } else {
                die("Something went wrong!, Argument not found.");
            }
        }while($this->i <= 2 && $error == 1);

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        switch ($ext) {
            case "tsv":
            case "csv":
            {
                $handle = fopen($fileName, "r");
                $linesArray = [];

                if($ext == "tsv"){
                    $fp = fopen('fileTsv.csv', 'w');
                    /** Tsv file to convert into an array */
                    if (($handle = fopen($fileName, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 102400, "\t")) !== FALSE) {
                            $linesArray[] = $data;
                        }
                    }
                }else{
                    $fp = fopen('fileCsv.csv', 'w');
                    /** Csv file to convert into an array */
                    if (($handle = fopen($fileName, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 102400, ",")) !== FALSE) {
                            $linesArray[] = $data;
                        }
                    }

                }
                fclose($handle);
                /** Import the data into csv and rename field and check the validation */
                $this->checkValidationWithImport($linesArray, $ext, $fp);
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
                $this->checkValidationWithImport($linesArray, $ext, $fp);
                fclose($fp);
                break;
            }
            case "xml":
            {
                $xml = file_get_contents($fileName);
                $xmls= simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOEMPTYTAG) or die("Error: Cannot create object");

                $attrs = $xmls->database->xpath('//table[last()]/column/@*');
                $headers = [];

                /** XML to Array */
                $ob= simplexml_load_string($xml);
                $json  = json_encode($ob);
                $configData = json_decode($json, true);

                /** XML Header into Array */
                $headerJson  = json_encode($attrs);
                $headerJsonData = json_decode($headerJson, true);

                foreach ($headerJsonData as $headerJsonDatam) {
                    array_push($headers, $headerJsonDatam['@attributes']['name']);
                }

                $body = [];
                $dataArr = [];

                /** Headers available for the XML file */
                foreach ($headers as $headKey => $header) {
                    $dataArr[''.$headers[$headKey].''] = $header;
                }
                array_push($body, $dataArr);

                /** Pushing the XML data into Array */
                foreach ($configData['database']['table'] as $bdData){
                    $dataArr = [];
                    foreach ($bdData['column'] as $key => $db) {
                        if(is_array($db)){
                            $dataArr[''.$headers[$key].''] = '';
                        }else{
                            $dataArr[''.$headers[$key].''] = $db;
                        }
                    }
                    array_push($body, $dataArr);
                }

                $fp = fopen('fileXml.csv', 'w');

                /** Import the data into csv and rename field and check the validation */
                $this->checkValidationWithImport($body, $ext, $fp);

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
                if (!$tables->length) {
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
                if (count($row_headers)) {
                    array_shift($linesArray);
                    array_unshift($linesArray, $row_headers);
                }

                $fp = fopen('fileHtml.csv', 'w');
                $this->checkValidationWithImport($linesArray, $ext, $fp);
                break;
            }
            case "xls":
            case "xlsx":
            case "ods":
            {
                if ($ext === 'xlsx') {
                    $fName = 'fileXlsx.csv';
                } else if ($ext === 'ods') {
                    $fName = "fileOds.csv";
                } else {
                    $fName = "fileXls.csv";
                }
                $fp = fopen($fName, 'w');
                $linesArray = $this->excelToArray($fileName);
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
                $this->checkValidationWithImport($linesArray, $ext, $fp);
                fclose($fp);
                break;
            }
            default:
            {
                die("This file can not import.");
            }

        }
        echo "Data imported successfully \n";
    }

    /** Factorial for each value */
    function factorial($val){
        $sum = 1;

        while($val!=0){
            $sum *= $val;
            $val--;
        }

        return $sum;
    }

    /** Convert XML to csv */
    function Csv($xml_data, $i)
    {

        foreach ($xml_data->children() as $data) {
            $hasChild = count($data->children()) > 0;

            if (!$hasChild) {
                $arr = array($data->getName(), $data);
                fputcsv($i, $arr, ',', '"');
            } else {

                $this->Csv($data, $i);
            }
        }
    }

    /** Rename the heading field name and store the required field validation */
    function renameHeading($line, $ext)
    {
        /** User input for rename the column*/
        $updateConfirmation = readline("You want to update the column field(y): ");
        if ($updateConfirmation === 'y') {
            echo "field name: rename field name (can't rename the field then without input press enter.) \n";

            foreach ($line as $k => $value) {
                if($k != '' || $k === 0){
                    $readValue = readline($value . ": ");
                    if ($readValue) {
                        $line[$k] = $readValue;
                    }
                }
            }
        }

        /** User input for set the data type */
        $dataTypeNumberArray = [];
        $dataTypeStringArray = [];
        $dataTypeConfirmation = readline("You want to change the data type (can't change datatype then as it is set default data type) (y): ");
        if ($dataTypeConfirmation === 'y') {
            foreach ($line as $k => $value) {
                if($k != '' || $k === 0){
                    $readDataType = readline($value . " datatype(string/number): ");
                    if ($readDataType == 'number') {
                        $dataTypeNumberArray[$k] = $value;
                    }
                    if ($readDataType == 'string') {
                        $dataTypeStringArray[$k] = $value;
                    }
                }
            }
        }

        /** User input for set the required field */
        $validationArray = [];
        $requiredConfirmation = readline("You want to set the required field(y): ");
        if ($requiredConfirmation === 'y') {
            foreach ($line as $k => $value) {
                if($k != '' || $k === 0){
                    $readValidation = readline($value . " required(y): ");
                    if ($readValidation == 'y') {
                        $validationArray[$k] = $value;
                    }
                }
            }
        }

        return [
            "line" => $line,
            "validation" => $validationArray,
            "dataTypeNumber" => $dataTypeNumberArray,
            "dataTypeString" => $dataTypeStringArray,
        ];
    }

    /** Checking the validation for each record  */
    function checkValidationWithImport($linesArray, $ext, $fp)
    {
        $validations = [];
        $keyFieldName = [];
        $dataTypeNumberKeys = [];
        $dataTypeStringKeys = [];

        $i = 1;
        foreach ($linesArray as $key => $line) {
            if (empty($key)) {
                /** Rename the heading field name and store the required field validation */
                $renameHeading = $this->renameHeading($line, $ext);

                $line = $renameHeading['line'];
                $validations = $renameHeading['validation'];
                $dataTypeNumberKeys = $renameHeading['dataTypeNumber'];
                $dataTypeStringKeys = $renameHeading['dataTypeString'];

                /** Create a new array for heading field name*/
                foreach ($line as $k=>$value) {
                    $keyFieldName[$k] = $value;
                }
            } else {

                foreach ($line as $ky=>$value) {
                    /** Check the required field validation */
                    if(array_key_exists($ky,$validations)){
                        if (!$value) {
                            die("The " . $keyFieldName[$ky] . " field is required.");
                        }
                    }

                    /** Check the dataType field validation */
                    if(array_key_exists($ky,$dataTypeNumberKeys)){
                        if(!is_numeric($value)){
                            die("Please import valid datatype, The " . $keyFieldName[$ky] . " datatype is number.");
                        }
                    }
                    if(array_key_exists($ky,$dataTypeStringKeys)){
                        if(!is_string($value)){
                            die("Please import valid datatype, The " . $keyFieldName[$ky] . " datatype is string.");
                        }
                    }
                }

            }

            /** If the user asks for unique combination */
            if($this->secondArgument == '--unique-combination'){
                if($i==1){
                    $line['count'] = 'count';
                }else{
                    $totalValues = count(array_unique(array_filter($line)));
                    $countValue = 0;

                    for($j = $totalValues; $j>=1;$j--){
                        $num1 = $this->factorial($totalValues);
                        $num2 = $this->factorial($j);

                        if($j == $totalValues){
                            $num3 = 1;
                        }else{
                            $num3 = $this->factorial($totalValues - $j);
                        }
                        if($num3 == 0){
                            die;
                        }
                        $countValue += ($num1/($num2 * $num3));
                    }
                    array_push($line, $countValue);
                }

                $i++;
            }

            /** Store the data in csv format */
            fputcsv($fp, $line);
        }
    }

    /** Converting excel to Array  */
    function excelToArray($filePath, $header = true)
    {
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
        if ($header) {
            $highestRow = $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true);
            $headingsArray = $headingsArray[1];

            $r = -1;
            $namedDataArray = array();
            for ($row = 2; $row <= $highestRow; ++$row) {
                $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, true);
                if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                    ++$r;
                    foreach ($headingsArray as $columnKey => $columnHeading) {
                        $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                    }
                }
            }
        } else {
            /** excel sheet with no header */
            $namedDataArray = $objWorksheet->toArray(null, true, true, true);
        }

        return $namedDataArray;
    }
}

/** Create an object of the class with constructor and calling respective functions */
$example = new example($argv);
$example->convertIntoCsv();

