<?php
// Things to notice:
// You need to add code to this script to implement the admin functions and features
// Notice that the code not only checks whether the user is logged in, but also whether they are the admin, before it displays the page content
// When an admin user is verified, you can implement all the admin tools functionality from this script, or distribute them over multiple pages - your choice
// execute the header script:
require_once "header.php";

$newPassword = "";

// checks the session variable named 'loggedInSkeleton'
// take note that of the '!' (NOT operator) that precedes the 'isset' function
if (! isset($_SESSION['loggedInSkeleton'])) {
    // user isn't logged in, display a message saying they must be:
    echo "You must be logged in to view this page.<br>";
} // the user must be signed-in, show them suitable page content
else {
    // only display the page content if this is the admin account (all other users get a "you don't have permission..." message):
    $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    if ($_SESSION['username'] == "admin") {

        echo "Click to create a new account:";

        echo "<a href ={$_SERVER['REQUEST_URI']}?createAccount=true>Create user account</a>";
        echo "<br>";

        if (isset($_GET['createAccount'])) {
            createAccount($dbhost, $dbuser, $dbpass, $dbname);
        } else {

            // queries mysql table, outputs results to table
            // this is written by me:
            $query = "SELECT username FROM users"; // +
            $result = mysqli_query($connection, $query); // +

            echo "Or click a name from the table to view user's data:";
            echo "<br>";

            echo "<table border ='1'>";
            echo "<tr><td>username</td></tr>";

            while ($row = mysqli_fetch_assoc($result)) {
                // if row hyperlink is clicked, set superglobal with user's name
                echo "<tr><td><a href =?username={$row['username']}>{$row['username']}</a></td></tr>"; // turns row result into hyperlink
            }
            echo "</table>";

            // print user's data
            if (isset($_GET['username'])) {
                printUserData($dbhost, $dbuser, $dbpass, $dbname);
            }
            // //////////

            if (isset($_GET['changePassword'])) {
                changePassword($dbhost, $dbuser, $dbpass, $dbname);
            }

            if (isset($_GET['deleteAccount'])) {
                deleteAccount($dbhost, $dbuser, $dbpass, $dbname);
            }
            // //////////
        }
        mysqli_close($connection);
    } else {
        echo "You don't have permission to view this page...<br>";
    }
}
// finish off the HTML for this page:
require_once "footer.php";

// this function gets the username of the selected user from the session superglobal, gets all their information using an SQL query, displays it in a table
// then shows the options to either change the password or delete the account
// this function is written by me:
function printUserData($dbhost, $dbuser, $dbpass, $dbname)
{
    $username = $_GET["username"];

    echo "User selected: " . $username;
    echo "<br>";

    $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    $query = "SELECT * FROM users WHERE username = '$username'"; // +
    $result = mysqli_query($connection, $query); // +

    echo "User's details:";
    echo "<table border ='1'>";
    echo "<tr><td>username</td><td>firstname</td><td>surname</td><td>password</td><td>email</td><td>number</td><td>DOB</td></tr>";

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>{$row['username']}</td><td>{$row['firstname']}</td><td>{$row['surname']}</td><td>{$row['password']}</td><td>{$row['email']}</td><td>{$row['number']}</td><td>{$row['DOB']}</td></tr>";
    }
    echo "</table>";

    echo "<a href ={$_SERVER['REQUEST_URI']}&changePassword=true>Change password</a>";
    echo " ";

    echo "<a href ={$_SERVER['REQUEST_URI']}&deleteAccount=true>Delete user account</a>";
}

function createAccount($dbhost, $dbuser, $dbpass, $dbname)
{
    
    $currentURL = $_SERVER['REQUEST_URI'];
    
    
    // default values we show in the form:
    $username = "";
    $firstname = ""; // +
    $surname = ""; // +
    $password = "";
    $email = "";
    $number = ""; // +
    $DOB = ""; // +
    
    // global: +
    $todaysDate = date('Y-m-d'); // get current date: +
    
    // strings to hold any validation error messages:
    $username_val = "";
    $firstname_val = ""; // +
    $surname_val = ""; // +
    $password_val = "";
    $email_val = "";
    $number_val = ""; // +
    $DOB_val = ""; //+
    
    echo <<<_END
    <form action="$currentURL" method="post">
      Please fill in the following fields:<br>
      Username: <input type="text" name="username" minlength="3" maxlength="16" value="$username" required> $username_val
      <br>
      First name: <input type="text" name="firstname" minlength="2" maxlength="16" value="$firstname" required> $firstname_val
      <br>
      Surname: <input type="text" name="surname" minlength="2" maxlength="24" value="$surname" required> $surname_val
      <br>
      Password: <input type="password" name="password" maxlength="32" value="$password"> $password_val
      <br>
      Email: <input type="email" name="email" minlength="3" maxlength="64" value="$email" required> $email_val
      <br>
      Phone number: <input type="text" name="number" min="11" max="11" value="$number" required> $number_val
      <br>
      Date of birth: <input type="date" name="DOB" max="$todaysDate" value="$DOB" required> $DOB_val
      <br>
      <input type="submit" value="Submit">
    </form>
    _END;
    
    if(isset($_POST['username'])) {
        $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
        
        $username = sanitise($_POST['username'], $connection);
        $firstname = sanitise($_POST['firstname'], $connection);
        $surname = sanitise($_POST['surname'], $connection);
        $password = sanitise($_POST['password'], $connection);
        $email = sanitise($_POST['email'], $connection);
        $number = sanitise($_POST['number'], $connection);
        $DOB = sanitise($_POST['DOB'], $connection);
        
        $username_val = validateStringLength($username, 1, 20);
        $password_val = validatePassword($password, 12, 31);
        $email_val = validateStringLength($email, 1, 64);
        $firstname_val = validateString($firstname, 2, 16);
        $surname_val = validateString($surname, 2, 20);
        $number_val = validatePhoneNumber($number);
        $DOB_val = validateDate($DOB, $todaysDate);
        
        if($password_val == "Zero") {
            $password = generatePassword();
            $password = encryptInput($password);
            $password_val = "";
        }
        
        $errors = $username_val . $password_val . $email_val . $firstname_val . $surname_val . $number_val . $DOB_val;
        
        // check that all the validation tests passed before going to the database:
        if ($errors == "") {
            
            // try to insert the new details:
            $query = "INSERT INTO users (username, firstname, surname, password, email, number, DOB) VALUES ('$username','$firstname','$surname','$password','$email','$number', '$DOB')";
            $result = mysqli_query($connection, $query);
            
            // no data returned, we just test for true(success)/false(failure):
            if ($result) {
                // show a successful signup message:
                $message = "Signup was successful<br>";
            } else {
                // show the form:
                $show_signup_form = true;
                // show an unsuccessful signup message:
                $message = "Sign up failed, please try again<br>";
            }
        } else {
            // validation failed, show the form again with guidance:
            $show_signup_form = true;
            // show an unsuccessful signin message:
            $message = "Sign up failed, please check the errors shown above and try again<br>";
        }
    }
    
}

// this function gets the select user's username from the session superglobal, asks the admin to fill in a new password for the user
// then updates the user's password via an SQL query
// this function is written by me
function changePassword($dbhost, $dbuser, $dbpass, $dbname)
{
    $username = $_GET["username"];

    if ($username == "admin") {
        echo "The admin's password cannot be changed";
    } else {
        echo "<br>";

        // $password_val

        $currentURL = $_SERVER['REQUEST_URI'];

        echo <<<_END
        <form action="$currentURL" method="post">
          Please fill in the following fields:<br>
          Password: <input type="password" name="newPassword" minlength="12" maxlength="32">
          <br>
          <input type="submit" value="Submit">
        </form>
        _END;

        if (isset($_POST['newPassword'])) {
            $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

            $newPassword = sanitise($_POST['newPassword'], $connection);
            $newPassword_val = validatePassword($newPassword, 12, 31);

            if ($newPassword_val == "") {
                $newPassword = encryptInput($newPassword);
                $query = "UPDATE users SET password='$newPassword' WHERE username = '$username'";
                $result = mysqli_query($connection, $query); // +
            }
            if ($result) {
                echo "Password changed";
            } else {
                echo "Password failed to change";
            }
        } // end of isset
    } // end of admin if
}

// end of function

// this function gets the username of the selected user from the session superglobal, then deletes the account via an SQL query
// this function is written by me:
function deleteAccount($dbhost, $dbuser, $dbpass, $dbname)
{
    $username = $_GET["username"];

    if ($username == "admin") {
        echo "The admin account cannot be deleted";
    } else {
        echo "<br>";
        echo "are you sure you want to delete " . $username . "? ";
        echo "<a href ={$_SERVER['REQUEST_URI']}&confirmDeletion=true>Yes</a>";
        echo " ";
        echo "<a href ={$_SERVER['REQUEST_URI']}&confirmDeletion=false>Cancel</a>";

        $shouldDeleteAccount = ""; // required to fix undefined index error

        $shouldDeleteAccount = $_GET["confirmDeletion"];

        if ($shouldDeleteAccount == "true") {
            $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
            $query = "DELETE FROM users WHERE username = '$username'";
            $result = mysqli_query($connection, $query); // +

            echo "Account deleted";
        }
    }
}
?>