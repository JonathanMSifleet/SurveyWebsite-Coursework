<?php

// execute the header script:
require_once "header.php";

if (! isset($_SESSION['loggedInSkeleton'])) {
    // user isn't logged in, display a message saying they must be:
    echo "You must be logged in to view this page.<br>";
} // the user must be signed-in, show them suitable page content
else {

    $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

    $questionID = $_GET['questionID'];

    getOptions($connection, $numOptions, $questionID);
}

function getOptions($connection, $numOptions, $questionID)
{
    $arrayOfOptions = Array();

    if (isset($_POST['optionName[]'])) {

        for ($i = 0; $i < $numOptions; $i ++) {
            $arrayOfOptions[$i] = sanitise($_POST['option[]'], $connection);
        }

        insertOptions($connection, $arrayOfOptions);

        print_r($arrayOfOptions);

        // insertOptions($connection, $arrayOfOptions, $numOptions, $numOptionsInserted);
    } else {
        displayOptionForm($numOptions);
    }
}

//
//
function insertOptions($connection, $arrayOfOptions)
{

    // get question ID
    $questionID = $_GET['questionID'];

    $query = "INSERT INTO questionoptions (questionID, optionName) VALUES ('$questionID', '$option')";
    $result = mysqli_query($connection, $query);

    if ($result) {
        echo "Options inserted successfully";
        $numOptionsInserted ++;
    } else {
        // show an unsuccessful signup message:
        echo "Query failed, please try again<br>";
    }
}

//
//
function displayOptionForm($numOptions)
{
    echo "<br>";
    echo "<form action='' method='post'>";

    for ($i = 0; $i < $numOptions; $i ++) {
        echo "Option: <input type='text' name='optionName[$i]' minlength='1' maxlength='32' required>";
        echo "<br>";
        echo "<br>";
    }

    echo "<input type='submit' value='Submit'>";
    echo "</form>";

    echo "<br>";
}

//
//
function getNumOptions($connection)
{
    $questionID = $_GET['questionID'];

    $query = "SELECT numOptions FROM questions WHERE questionID = '$questionID'";
    $result = mysqli_query($connection, $query);

    // if no data returned, we set result to true(success)/false(failure):
    if ($result) {

        $row = mysqli_fetch_row($result);

        return $row[0];
    } else {
        // show an unsuccessful signup message:
        echo "Query failed, please try again<br>";
    }
}

?>