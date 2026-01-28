<?php
session_start();

// detect if this session belonged to an admin before destroying it
$wasAdmin = isset($_SESSION['admin']);

session_unset();  // removes all session variables
session_destroy(); // completely ends the session

// redirect admins to the admin login, regular users to the main site
if($wasAdmin){
	header("Location: admin_login.php");
} else {
	header("Location: index.php");
}
exit();
?>
