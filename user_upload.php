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
     * @var object dbConnection The connection to the database
     */
    private $dbConnection;

    /**
     * @var string
     */
    private $csvFilename;

    /**
     * UserUploadByCsv constructor.
     * @param $csvFilename string The csv filename
     */
    function __construct($csvFilename="")
    {
        $this->csvFilename = $csvFilename;
    }

    /**
     * Function for connecting to the database
     * @param $dbValues array Array of values to connect to the database
     */
    public function connectDb(array $dbValues)
    {

    }

    /**
     * Creates the database table.
     */
    public function createTable()
    {

    }

    /**
     * Echos help message.
     */
    public static function displayHelp()
    {
        $helpMessage = <<<EOT
This script should include these command line options (directives):
  --file [csv file name] – this is the name of the CSV to be parsed
  --create_table – this will cause the MySQL users table to be built (and no further action will be taken)
  --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. 
              All other functions will be executed, but the database won't be altered
  -u – MySQL username
  -p – MySQL password
  -h – MySQL host
  --help – which will output the above list of directives with details.
EOT;
        echo $helpMessage;
    }

}

/**
 * Execute the script.
 */
$options = getopt("u:p:h", ["file:","help","create_table:","dry_run"]);
print_r($options);die();

if (in_array("--help", $argv))
{
    UserUploadByCsv::displayHelp();
    die();
}

$dbValues = array();
if (in_array("h", $options) && in_array("u", $options) && in_array("p", $options))
{
    $dbValues["host"] = $options["h"];
    $dbValues["username"] = $options["u"];
    $dbValues["password"] = $options["p"];
}

$csvFilename = "";
$key = "file";
if (in_array(array_keys($options), $key))
{
    $csvFilename = $options[$key];
}

if ($csvFilename)
{
    if (file_exists($csvFilename))
    {
        try{
            $upload = new UserUploadByCsv($csvFilename);
        }catch(\Exception $ex){

        }
    } else {
        echo "File $csvFilename does not exist.\n";
    }

} else {
    if ($options["create_table"])
    {

    } else {
        echo "Filename not supplied.\n";
        UserUploadByCsv::displayHelp();
        die();
    }
}

