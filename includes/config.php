<?php
error_reporting(E_ALL);
date_default_timezone_set('Africa/Lagos');
session_start();
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "chat";

// Create connection
$con = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
 }
$website_code = '0X&KHJ22hast';
$user_table = "users";
$option_table = "options";
$template_table = "email_templates";
$chat_table = "chats";
$message_table = "messages";
$file_table = "files";
$read_table = "read_by";
$seen_table = "seen_by";
$activity_table = "activities";
$website_url = "http://localhost/chat";
$option = mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM $option_table WHERE website_code='$website_code'"));

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function ActivityLog($con,$detail,$id)
{
	$in = mysqli_query($con,"INSERT INTO activities(detail,add_date,user_id) VALUES('$detail',NOW(),'$id')");
}

function SendMail($from,$subject,$to,$message,$fromname='',$toname='')
	{
		require 'phpmail/PHPMailerAutoload.php';
		$error = "";
		$mail = new PHPMailer;
			try
				{
					$mail->setFrom($from,$fromname);
					$mail->addAddress($to,$toname);
					$mail->addReplyTo($from,'NoReply');
					$mail->isHTML(true);
					$mail->Subject = $subject;
					$mail->Body    = $message;
					$mail->send();
					$error = " Message sent";
						
				}
				catch (Exception $e)
				{
					$error .= 'Error: Message could not be sent to '.$to;
					$error .=  '<br>Mailer Error: '.$mail->ErrorInfo;
				}
				return $error;
	}