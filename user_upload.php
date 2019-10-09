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
     * @var string The csv filename
     */
    private $csvFilename;

    function __construct($csvFilename)
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

}

$csvFilename = "";
if ($csvFilename)
{
    if (file_exists($csvFilename))
    {
        $upload = new UserUploadByCsv($csvFilename);
    } else {
        echo "File $csvFilename does not exist.\n";
    }

} else {
    echo "Filename not supplied.\n";
}
