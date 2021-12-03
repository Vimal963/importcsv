# Steps For Testing

#### Requirement: 

You can parse multiple different formats of files like json, xml, xls, xlsx, html, tsv, csv and ods.

#### Allowed Arguments
 - examples/migrations.xml(First Argument): Your file to be parsed. It can be anyone from the list provided.
 - --unique-combination(First Argument): To get grouped count for each unique combination. It is fixed 

#### Available Files for now
- examples/migrations.xml
- examples/migrations.json
- examples/index.html
- examples/index.xlsx
- examples/index.xls
- examples/index.ods
- examples/products.tsv
- examples/products_comma_separated.csv
- examples/products_tab_separated.tsv
- examples/combination_count.tsv
- examples/app_settings.xml

#### Flow and Exception Handling

`php -f example.php examples/migrations.xml --unique-combination`

    where, 
    example.php is our code. 
    examples/migrations.xml - File you want to parse. If you enter wrong file name, you'll get one more chance to enter.
    --unque-combination(optional) - The only allowed agrument for now. No other argument is accepted, else it'll throw an error as invalid argument.
    You have to pass --unique-combination argument only if you want unique pssible combiantions in the file.

## Steps
### Step 1

  `php -f example.php examples/migrations.xml --unique-combination`

OR

   `php -f example.php examples/migrations.xml`
### Step 2

- After you've entered the correct file name, it will ask if you want to update the column fields. If you type 'y', then it'll allow you to change column field and also asks for the fields names to be replaced with. If you don't want any changes, just press enter. It'll only take 'y' as an answer. 
- After that it will ask if you want to update/restrict the data type of each field. Press 'y' to move further. If you type 'y', then it'll ask you for dataType for each column field which is either string/Number. If you don't want any changes, just press enter. It'll only take 'string/number' as an answer.
- Finally, it will ask if you want certain fields as required. If yes, press 'y' and then it'll ask you for each field. Whichever field you want as required just press 'y' for them and leave the rest by pressing enter.

### Step 3

- As soon as you do this, it'll import the data into file<fileType>.csv. Eg: fileXml.csv
- It'll have the count field only if you've passed the --unique-combination argument. 
