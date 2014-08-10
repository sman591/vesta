<?php
// Init
error_reporting(NULL);
ob_start();
session_start();
$TAB = 'USER';

include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Check user
if ($_SESSION['user'] != 'admin') {
    header("Location: /list/user");
    exit;
}

// Check POST request
if (!empty($_POST['ok'])) {

    // Check empty fields
    if (empty($_POST['v_username'])) $errors[] = __('user');
    if (empty($_POST['v_password'])) $errors[] = __('password');
    if (empty($_POST['v_package'])) $errrors[] = __('package');
    if (empty($_POST['v_email'])) $errors[] = __('email');
    if (empty($_POST['v_fname'])) $errors[] = __('first name');
    if (empty($_POST['v_lname'])) $errors[] = __('last name');
    if (!empty($errors[0])) {
        foreach ($errors as $i => $error) {
            if ( $i == 0 ) {
                $error_msg = $error;
            } else {
                $error_msg = $error_msg.", ".$error;
            }
        }
        $_SESSION['error_msg'] = __('Field "%s" can not be blank.',$error_msg);
    }

    // Check username length
    if (empty($_SESSION['error_msg'])) {
        $username_len = strlen($_POST['v_username']);
        exec (VESTA_CMD."v-list-database-types", $output, $return_var);
        check_error($return_var);
        if (strpos($output, "mysql") !== false)
            $username_maxlen = 16 - 2;
        elseif (strpos($output, "postgresql") !== false)
            $username_maxlen = 63 - 2;
        else
            $username_maxlen = true; // Allow any length by default
        if ($username_len > $username_maxlen ) $_SESSION['error_msg'] = __('Username is too long.',$error_msg);
    }

    // Check password length
    if (empty($_SESSION['error_msg'])) {
        $pw_len = strlen($_POST['v_password']);
        if ($pw_len < 6 ) $_SESSION['error_msg'] = __('Password is too short.',$error_msg);
    }

    // Validate email
    if ((empty($_SESSION['error_msg'])) && (!filter_var($_POST['v_email'], FILTER_VALIDATE_EMAIL))) {
        $_SESSION['error_msg'] = __('Please enter valid email address.');
    }

    // Check password length
    if (empty($_SESSION['error_msg'])) {
        $pw_len = strlen($_POST['v_password']);
        if ($pw_len < 6 ) $_SESSION['error_msg'] = __('Password is too short.',$error_msg);
    }

    // Protect input
    $v_username = escapeshellarg($_POST['v_username']);
    $v_password = escapeshellarg($_POST['v_password']);
    $v_email = escapeshellarg($_POST['v_email']);
    $v_package = escapeshellarg($_POST['v_package']);
    $v_language = escapeshellarg($_POST['v_language']);
    $v_fname = escapeshellarg($_POST['v_fname']);
    $v_lname = escapeshellarg($_POST['v_lname']);
    $v_notify = $_POST['v_notify'];


    // Add user
    if (empty($_SESSION['error_msg'])) {
        exec (VESTA_CMD."v-add-user ".$v_username." ".$v_password." ".$v_email." ".$v_package." ".$v_fname." ".$v_lname, $output, $return_var);
        check_return_code($return_var,$output);
        unset($output);
    }

    // Set language
    if (empty($_SESSION['error_msg'])) {
        exec (VESTA_CMD."v-change-user-language ".$v_username." ".$v_language, $output, $return_var);
        check_return_code($return_var,$output);
        unset($output);
    }

    // Send email to the new user
    if ((empty($_SESSION['error_msg'])) && (!empty($v_notify))) {
        $to = $_POST['v_notify'];
        $subject = _translate($_POST['v_language'],"Welcome to Vesta Control Panel");
        $hostname = exec('hostname');
        unset($output);
        $from = _translate($_POST['v_language'],'MAIL_FROM',$hostname);
        if (!empty($_POST['v_fname'])) {
            $mailtext = _translate($_POST['v_language'],'GREETINGS_GORDON_FREEMAN',$_POST['v_fname'],$_POST['v_lname']);
        } else {
            $mailtext = _translate($_POST['v_language'],'GREETINGS');
        }
        $mailtext .= _translate($_POST['v_language'],'ACCOUNT_READY',$_SERVER['HTTP_HOST'],$_POST['v_username'],$_POST['v_password']);
        send_email($to, $subject, $mailtext, $from);
    }

    // Flush field values on success
    if (empty($_SESSION['error_msg'])) {
        $_SESSION['ok_msg'] = __('USER_CREATED_OK',$_POST['v_username'],$_POST['v_username']);
        $_SESSION['ok_msg'] .= " / <a href=/login/?loginas=".$_POST['v_username'].">" . __('login as') ." ".$_POST['v_username']. "</a>";
        unset($v_username);
        unset($v_password);
        unset($v_email);
        unset($v_fname);
        unset($v_lname);
        unset($v_notify);
    }
}


// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);

// List hosting packages
exec (VESTA_CMD."v-list-user-packages json", $output, $return_var);
check_error($return_var);
$data = json_decode(implode('', $output), true);
unset($output);

// List languages
exec (VESTA_CMD."v-list-sys-languages json", $output, $return_var);
$languages = json_decode(implode('', $output), true);
unset($output);

// Display body
include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_user.html');

// Flush session messages
unset($_SESSION['error_msg']);
unset($_SESSION['ok_msg']);

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
