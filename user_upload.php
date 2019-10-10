<?php
/**
 * user_upload.php
 * Created by: nick
 * @ 9/10/2019 3:29 PM
 * Project: catalyst
 *
 */ 

class UserUploadByCsv{

    /**
     * @var string Database name
     * This is an assumption, it can be easily added in the code.
     * The requirements do not offer a command line parameter for DB Name,
     * so this has been added in here for the time being.
     */
    const DB_NAME = "users";

    /**
     * @var string Database table name
     */
    const DB_TABLE = "users";

    /**
     * @var mysqli dbConnection The connection to the database
     */
    private $dbConnection;

    /**
     * @var string The filename of the csv file for processing
     */
    private $csvFilename;

    /**
     * @var bool Is this a dry run or not?
     */
    private $isDryRun;

    /**
     * UserUploadByCsv constructor.
     * @param $csvFilename string The csv filename
     */
    function __construct($csvFilename="", $isDryRun=false)
    {
        $this->csvFilename = $csvFilename;
        $this->isDryRun = $isDryRun;
    }

    /**
     * Echos help message.
     */
    public static function displayHelp()
    {
        $helpMessage = <<<EOT
This script includes the command line options (directives):
  --file [csv file name] – this is the name of the CSV to be parsed
  --create_table – this will cause the MySQL users table to be built (and no further action will be taken)
  --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered
  -u – MySQL username
  -p – MySQL password
  -h – MySQL host
  --help – which will output the above list of directives with details.

EOT;
        echo $helpMessage;
    }

    /**
     * Function for connecting to the database
     * @param $dbValues array Array of values to connect to the database
     * @throws Exception
     */
    public function connectDb(array $dbValues)
    {
        if (!in_array("host", array_keys($dbValues)) || !in_array("username", array_keys($dbValues))
                || !in_array("password", array_keys($dbValues))) {
            throw new \Exception("Missing database values");
        }
        $this->dbConnection = @new \mysqli($dbValues['host'], $dbValues['username'], $dbValues['password'],
            UserUploadByCsv::DB_NAME);
        if (mysqli_connect_errno()) {
            throw new \Exception("Database connection failed: " . mysqli_connect_error());
        }
    }

    /**
     * Creates the database table.
     * @throws Exception
     */
    public function createTable()
    {
        if ($this->dbConnection) {
            $sql = <<<EOT
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `users` ADD UNIQUE KEY `email unique` (`email`);
EOT;
            if( $this->dbConnection->multi_query($sql) === FALSE)
            {
                throw new \Exception("Cannot create database table `users`");
            } else {
                while ($this->dbConnection->next_result()) {;} // flush multi_queries
            }
        }
    }

    /**
     * Process the file
     */
    public function process()
    {
        $rows = 0;
        $headers = array();
        if (($handle = fopen($this->csvFilename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (!$rows)
                {
                    $headers = $data;
                    $headers[2] = trim($headers[2]);
                } else {
                    $rowData = array();
                    for ($c=0; $c < count($data); $c++) {
                        $rowData[$headers[$c]] = trim($data[$c]);
                    }
                    $this->csvRowToDb($rowData);
                }
                $rows++;
            }
            fclose($handle);
        }

        // close the connection, we are done!
        $this->dbConnection->close();
    }

    /**
     * Validates the input, formats it and enters a row into the db
     * @param $row array The csv row values
     */
    private function csvRowToDb($row)
    {
        // validate input
        // apparently exclamation marks are legit for email addresses - https://answers.yahoo.com/question/index?qid=20100803050517AAdtLKu
        if(!filter_var($row["email"], FILTER_VALIDATE_EMAIL))
        {
            echo "Email ". $row["email"]. " is not valid, skipping row.\n" ;
            return;
        }

        // format input
        $row["name"] = ucwords(strtolower($row["name"]), " \t\r\n\f\v'");
        $row["surname"] = ucwords(strtolower($row["surname"]), " \t\r\n\f\v'");
        $row["email"] = strtolower($row["email"]);

        // process
        if (!$this->isDryRun)
        {
            $sql = "INSERT INTO " . UserUploadByCsv::DB_TABLE . "(`name`,`surname`,`email`)
                    VALUES (?,?,?)";
            $statement = $this->dbConnection->prepare($sql);
            if ($statement)
            {
                $statement->bind_param("sss", $row["name"], $row["surname"], $row["email"]);
                $result = $statement->execute();
                if(!$result)
                {
                    // this area is hit if the statement fails, currently fails on duplicate emails
                    // will do nothing except fail silently as spec does not ask for warning on this
                    // issue
                    // $email = $row["email"];
                    // echo "Warning: Insert failed! Possible cause duplicate email ($email)\n";
                }
            } else {
                echo "Error: " . $this->dbConnection->error . "\n";
            }
        }
    }

}

/**
 * Execute the script.
 */
$options = getopt("u:h:p:", ["file:","help","create_table","dry_run"]);
$optionsKeys = array_keys($options);

if (in_array("help", $optionsKeys))
{
    UserUploadByCsv::displayHelp();
    exit();
}

$dbValues = array();
if (in_array("h", $optionsKeys) && in_array("u", $optionsKeys)
        && in_array("p", $optionsKeys))
{
    $dbValues["host"] = $options["h"];
    $dbValues["username"] = $options["u"];
    $dbValues["password"] = $options["p"] ? $options["p"] : '';
}

// get csv filename
$csvFilename = "";
$key = "file";
if (in_array($key, $optionsKeys))
{
    $csvFilename = $options[$key];
}

// if create table is not applied, process the file
if (!in_array("create_table", $optionsKeys))
{
    // check to see if the filename is supplied
    if ($csvFilename)
    {
        if (file_exists($csvFilename))
        {
            try{
                $isDryRun = in_array("dry_run", $optionsKeys);
                $upload = new UserUploadByCsv($csvFilename, $isDryRun);
                $upload->connectDb($dbValues);
                $upload->createTable();
                $upload->process();
            }catch(\Exception $ex){
                echo $ex->getMessage() . "\n";
            }
        } else {
            echo "$csvFilename does not exist.\n";
        }
    } else {
        echo "Filename not supplied. See --help\n";
        exit();
    }
} else {
    // just create the table
    try {
        $upload = new UserUploadByCsv();
        $upload->connectDb($dbValues);
        $upload->createTable();
    }catch(\Exception $ex){
        echo $ex->getMessage() . "\n";
    }
}

