<?php

// Types of questions:

/*
 * Multiple Choice
 * Short Answer
 * Paragraph
 * Checkboxes
 * Dropdown
 * Dates
 * Times
 */

// execute the header script:
require_once "header.php";

if (! isset($_SESSION['loggedInSkeleton'])) {
    // user isn't logged in, display a message saying they must be:
    echo "You must be logged in to view this page.<br>";
} // the user must be signed-in, show them suitable page content
else {

    // only display the page content if this is the admin account (all other users get a "you don't have permission..." message):
    $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

    // if the connection fails, we need to know, so allow this exit:
    if (! $connection) {
        die("Connection failed: " . $mysqli_connect_error);
    }

    initNewSurvey($connection);
}

// finish of the HTML for this page:
require_once "footer.php";

//
//
function initNewSurvey($connection)
{
    $arrayOfSurveyErrors = array();
    initEmptyArray($arrayOfSurveyErrors, 4);

    $maxInstructionLength = (2 ** 16) - 2; // max varchar length = 2^16, deduct 2 just to be sure

    $title = "";
    $instructions = "";
    $numQuestions = null;
    $type = "";
    $topic = "";

    if (isset($_POST['title'])) {

        // SANITISATION (see helper.php for the function definition)
        $title = sanitise($_POST['title'], $connection);
        $instructions = sanitise($_POST['instructions'], $connection);
        $numQuestions = sanitise($_POST['noOfQuestions'], $connection);
        $type = sanitise($_POST['type'], $connection);
        $topic = sanitise($_POST['topic'], $connection);

        createSurvey($connection, $title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors);
    } else {
        displayCreateSurveyForm($title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors);
    }
}

//
//
function createSurvey($connection, $title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors)
{
    createArrayOfSurveyErrors($title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors);
    $errors = concatValidationMessages($arrayOfSurveyErrors);

    if ($errors == "") {

        // try to insert new survey:

        $currentUser = $_SESSION['username'];
        $surveyID = md5($currentUser . $title);

        $query = "INSERT INTO surveys (surveyID, username, title, instructions, numQuestions, type, topic) VALUES ('$surveyID', '$currentUser',  '$title' , '$instructions','$numQuestions', '$type', '$topic')";
        $result = mysqli_query($connection, $query);

        // if no data returned, we set result to true(success)/false(failure):
        if ($result) {
            // show a successful signup message:
            echo "Survey creation was successful";
            echo "<br>";
            echo "<a href = createQuestion.php?surveyID=$surveyID> Click to create some questions: </a>";
            echo "<br>";
        } else {
            // validation failed, show the form again with guidance:
            displayCreateSurveyForm($title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors);
            // show an unsuccessful signup message:
            echo "Survey creation failed, please try again<br>";
        }
    } else {
        // validation failed, show the form again with guidance:
        displayCreateSurveyForm($title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors);
    }
}

//
//
function displayCreateSurveyForm($title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, $arrayOfSurveyErrors)
{
    echo "Input survey details:";

    // max number of questions length to be compatible with MYSQL smallint max value

    echo <<<_END
    <form action="create_survey.php" method="post">
      Please fill in the following fields:<br>
      Title: <input type="text" name="title" minlength="3" maxlength="64" value="$title" required> $arrayOfSurveyErrors[0]
      <br>
      Instructions: <input type="text" name="instructions" minlength="2" maxlength="$maxInstructionLength" value="$instructions" required> $arrayOfSurveyErrors[1]
      <br>
      Number of questions: <input type="text" name="noOfQuestions" minlength="1" maxlength="512" value="$numQuestions" required> $arrayOfSurveyErrors[2]
      <br>
      Type of Survey: <input type="text" name="type" maxlength="64" value="$type"> $arrayOfSurveyErrors[3]
      <br>
      Survey Topic: <input type="text" name="topic" maxlength="32" value="$topic"> $arrayOfSurveyErrors[4]
      <br>
      <input type="submit" value="Submit">
    </form>
    _END;
}

//
//
function createArrayOfSurveyErrors($title, $instructions, $numQuestions, $type, $topic, $maxInstructionLength, &$arrayOfSurveyErrors)
{
    $arrayOfSurveyErrors[0] = validateStringLength($title, 4, 64);
    $arrayOfSurveyErrors[1] = validateStringLength($instructions, 1, $maxInstructionLength);
    $arrayOfSurveyErrors[2] = validateNumberOfQuestion($numQuestions, 1, 32);
    $arrayOfSurveyErrors[3] = validateStringLength($type, 0, 64);
    $arrayOfSurveyErrors[4] = validateStringLength($topic, 0, 32);
}

?>