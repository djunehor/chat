<?php
require 'includes/config.php';
require 'includes/class.validate_input.php';
$validate = new validateInput();
$file = new validateFile();
$type = $_GET['value'];
switch($type)
{
    case 'del_message':
        @$password= $_REQUEST['mid'];
        if(!is_int($password))
        {$error = "Invalid MessageID";}
        $fpy = mysqli_query($con,"UPDATE $message_table set abolished=1, abolished_by='".$_SESSION['ID']."' WHERE ID='$password'") or die("Error: ".mysqli_error($con));
        break;

    case 'del_chat':
        @$password= $_REQUEST['pid'];
        if(!is_int($password))
        {$error = "Invalid chatID";}

        $fpy = mysqli_query($con,"UPDATE $chat_table set abolished=1, updated_by='".$_SESSION['ID']."' WHERE ID='$password'") or die("Error: ".mysqli_error($con));

        //echo "Successfully deleted chat [".$password."]";
        break;

    case 'load_chat';
        $chatid = filter_input(INPUT_GET,'id',FILTER_SANITIZE_STRING);
        $senderid = filter_input(INPUT_GET,'senderid',FILTER_SANITIZE_STRING);
        $recipientid = filter_input(INPUT_GET,'recipientid',FILTER_SANITIZE_STRING);

//first we select chat and messages related to it
        $chat_log = mysqli_query($con,"
        SELECT m.user_id as user_id, f.path as file, m.body, m.created_at,
        m.ID as message_id, m.created_at as date_time,
        r.user_id as user_read, r.created_at as read_at,
        s.user_id as user_seen, s.created_at as seen_at
        FROM $chat_table c
        LEFT JOIN $message_table m
        ON c.ID = m.chat_id AND m.abolished = 0
        LEFT JOIN $file_table f
        ON m.ID = f.message_id AND f.abolished = 0
        LEFT JOIN $seen_table s
        ON m.ID = s.message_id
        LEFT JOIN $read_table r
        ON m.ID = r.message_id
        WHERE c.ID='$chatid' AND c.abolished= 0
        ORDER BY m.ID DESC
        LIMIT 20") or die("First select error: ".mysqli_error($con));

        $table = mysqli_query($con, "SELECT * FROM $user_table WHERE ID='$recipientid'") or die("Online error: " . mysqli_error($con));

        $recipient = mysqli_fetch_assoc($table);

        $status = (strtotime($recipient['last_seen']) >= (time()-60)) ? "Online" : "last seen ".time_elapsed_string('@'.strtotime($recipient['last_seen']));

        echo '<h6 class="card-body-title">Chat with '.$recipient['full_name'].'</h6><i>('.$status.')</i>';
        while($f = mysqli_fetch_array($chat_log))
        {
            if($f['user_id'] != $senderid && (strtotime($f['date_time'])+3)==time() && $status == "Online")
            {
                echo '<audio autoplay="autoplay" src="../uploads/beep1.wav" type="audio/wav"><embed src="../uploads/beep1.wav" hidden="true" autostart="true" loop="false" /></audio>';
            }

            echo '<div ';
            if($f['user_id'] == $senderid) {
                echo 'style="text-align:right;" class="alert alert-success" id="message'.$f['message_id'].'">
                <button class="btn btn-danger btn-icon rounded-circle mg-r-5 mg-b-10" style="float:right;" onclick="del_message(this.value)" name="btnDelete" value="'.$f['message_id'].'">Delete</button>';
            }
            else{
                echo 'class="alert alert-danger" id="message'.$f['message_id'].'">';
            }
            echo html_entity_decode(htmlspecialchars_decode($f['body']));
            if(!is_null($f['file'])) {echo '<br><a target="_blank" href="../'.$f['file'].'"><img src="../'.$f['file'].'" width="50px" height="50px">View/Download Image</a>';}

            try {
                echo '<small>'.time_elapsed_string('@'.strtotime($f['date_time'])).'</small>';
            } catch(\Exception $e) {

            }

            if($f['user_id'] != $senderid && $f['user_seen'] != $senderid && $f['seen_at'] != null) {
                //echo '<a title="Read '.time_elapsed_string('@'.strtotime($f['read_at'])).'"></a>';
                ?>
                <span class="checkmark">
                <div class="checkmark_circle"></div>
                 <div class="checkmark_stem"></div>
                <div class="checkmark_kick"></div>
                </span>
                <?php
            }
            if($f['user_id'] != $senderid && $f['user_read'] != $senderid && $f['read_at'] != null) {
                //echo '<a title="Read '.time_elapsed_string('@'.strtotime($f['read_at'])).'"></a>';
                ?>
                <span class="checkmark">
                <div class="checkmark_circle"></div>
                <div class="checkmark_stem"></div>
                <div class="checkmark_kick"></div>
                </span>
                <?php
            }
            echo '</div>';

//update seen
            if(mysqli_num_rows(mysqli_query($con, "SELECT * FROM $seen_table WHERE message_id='".$f['message_id']."' AND '$senderid'"))<1) {
                $seen = mysqli_query($con, "INSERT INTO $seen_table(message_id, user_id) VALUES ('".$f['message_id']."','$senderid')");
            }

            if(mysqli_num_rows(mysqli_query($con, "SELECT * FROM $read_table WHERE message_id='".$f['message_id']."' AND '$senderid'"))<1) {
                $read = mysqli_query($con, "INSERT INTO $read_table(message_id, user_id) VALUES ('".$f['message_id']."','$senderid')");
            }

        }

        $mum4 = mysqli_query($con,"UPDATE $user_table SET last_seen=NOW() WHERE ID='$senderid'");
        break;

    case 'add_chat':
        $array=array("chatid","recipientid","senderid","message");

        foreach($_REQUEST as $key=>$value){
            if(in_array($key,$array)){
                $$key=addslashes($value);
            }
        }

        if(empty($chatid) || empty($recipientid) || empty($senderid))
        {
            echo "Error: Some Required fields are missing!";
        }

        if(!is_numeric($recipientid) || !is_numeric($senderid))
        {
            echo "Error: Form contains invalid fields!";
        }

        else {
            $chatid = filter_var($chatid, FILTER_SANITIZE_STRING);
            $message = htmlentities(htmlspecialchars($message));
            $file = null;
            if (!empty($_FILES['fileToUpload']['name'])) {
                $uploaddir = 'uploads/';
                $uploadfile = $uploaddir . basename($_FILES['fileToUpload']['name']);
                $imageFileType = pathinfo($uploadfile, PATHINFO_EXTENSION);

                if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $uploadfile)) {
                    $file = $uploadfile;
                }
            }
            //$ss3  = mysqli_fetch_assoc(mysqli_query($con,"select employeeID from $employee_table where email='$recipient'"));
            $sql = mysqli_query($con, "INSERT INTO $message_table
          (chat_id, user_id, body) 
          VALUES('$chatid','$senderid','$message')") or die(mysqli_error($con));

            if (!is_null($file)) {
                $message_id = mysqli_insert_id($con);
                $sql2 = mysqli_query($con, "INSERT INTO $file_table 
                  (message_id, path) 
                  VALUES ('$message_id','$file')") or die("File Insert Error: " . mysqli_error($con));
            }

            //now let's check if recipient is offline
            $table = mysqli_query($con, "SELECT * FROM $user_table WHERE ID='$recipientid'") or die("Online error: " . mysqli_error($con));

            $recipient = mysqli_fetch_assoc($table);

            if (strtotime($recipient['last_seen']) < (time() - 60)) {

                //send welcome email
                $query = mysqli_query($con, "SELECT * FROM $template_table WHERE type = 2");
                $tempData = mysqli_fetch_assoc($query);

                //replace template var with value
                $token = array(
                    'SITE_URL' => 'http://' . $_SERVER['SERVER_NAME'],
                    'SITE_NAME' => $option['website_name'],
                    'USER_NAME' => $_SESSION['full_name'],
                    'MESSAGE' => $message,
                    'SITE_EMAIL' => $option['admin_email'],
                    'SENDER_NAME' => $recipient['full_name'],
                    'SEND_DATE' => date('D M Y g:i a', time())
                );
                $pattern = '[%s]';
                foreach ($token as $key => $val) {
                    $varMap[sprintf($pattern, $key)] = $val;
                }
                $emailContent = strtr($tempData['content'], $varMap);
                $from = 'noreply@' . $_SERVER['SERVER_NAME'];
                SendMail($from, $tempData['title'], $recipient['email'], $emailContent, $option['website_name'], $recipient['full_name']);
            }
        }
        break;

    case 'new_chat':
        $array=array("recipient","message","senderid");

        foreach($_REQUEST as $key=>$value){
            if(in_array($key,$array)){
                $$key=addslashes($value);
            }
        }

        $message = htmlentities(htmlspecialchars($message));
        if(empty($recipient) || (empty($message) && empty($_FILES['fileToUpload']['name'])))
        {
            echo "Recipient and Message Required!";
            break;
        }

        if(!is_numeric($senderid))
        {
            echo "A required field is missing!";
            break;
        }

        if(filter_var($recipient,FILTER_VALIDATE_EMAIL)===false)
        {
            echo "Please enter a valid email!";
            break;
        }

        $recipientQuery = mysqli_query($con,"select ID, email, full_name from $user_table where email='$recipient' AND abolished=0");
        if(mysqli_num_rows($recipientQuery)!=1)
        {
            echo 'Recipient Not Found!';
            break;
        }

        $rec =  mysqli_fetch_assoc($recipientQuery);
        $recipient_id =  $rec['ID'];
        $recipient_email =  $rec['email'];

        $query = mysqli_query($con,"select ID from $chat_table
    where 
    (user1 ='$recipient_id' AND user2='$senderid')
    OR (user1 = '$senderid' AND user2='$recipient_id')
    AND abolished = 0
    ") or die("Select Chat Error: ".mysqli_error($con));

        if(mysqli_num_rows($query)>0)
        {
            $thread = mysqli_fetch_assoc($query);

            echo 'You have an existing chat with this user! <a target="_blank" href="ViewChat?id='.$thread['ID'].'">Continue Chat</a>';
            break;
        }

        else
        {
            $uploadfile = null;
            if(!empty($_FILES['fileToUpload']['name']))
            {
                $uploaddir = 'uploads/';
                $uploadfile = $uploaddir . basename($_FILES['fileToUpload']['name']);
                $imageFileType = pathinfo($uploadfile,PATHINFO_EXTENSION);
                if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $uploadfile))
                {
                    echo "File <b>".$_FILES['fileToUpload']['name']."</b> successfully uploaded. ";
                }
            }

            //TODO: Send mail to recipient $ss3  = mysqli_fetch_assoc(mysqli_query($con,"select email from $employee_table where email='$recipient'"));
            $sql = mysqli_query($con,"INSERT INTO $chat_table(user1, user2) VALUES('$senderid', '$recipient_id')");

            if(!$sql) {
                echo "Start chat Error: ".mysqli_error($con);
                break;
            }
            $chat_id =  mysqli_insert_id($con);
            $sql2 = mysqli_query($con,"INSERT INTO $message_table(chat_id, user_id, body) VALUES('$chat_id','$senderid', '$message')");

            if(!$sql2) {
                echo "Create chat Error: ".mysqli_error($con);
                break;
            }

            if(!is_null($uploadfile)) {
                $message_id = mysqli_insert_id($con);
                $sql3 = mysqli_query($con,"INSERT INTO $file_table(message_id, path) VALUES('$message_id', '$uploadfile')");

                if(!$sql3) {
                    echo "Insert File Error: ".mysqli_error($con);
                    break;
                }
            }

            $detail = "New Chat with <b>".$recipient_email."</b> was started";

            ActivityLog($con,$detail,$_SESSION['ID']);

        }
        echo ' Chat Started Successfully. <a href="ViewChat?id='.$chat_id.'">Start Conversation</a>';
        break;

    case 'mprofile':
//error_reporting(E_WARNING);
        $array=array("fullname","phone","ID");
        foreach($_REQUEST as $key=>$value){
            if(in_array($key,$array)){
                $$key=addslashes($value);
            }
        }
        $fullname = filter_var($fullname, FILTER_SANITIZE_STRING);
        $error = $validate->number($ID,4);
        $error .= $validate->number($phone,4);
        if(strlen($_FILES['myphoto']['name'])>4) { $filearray = $_FILES['myphoto']; $error .= $file->photo($filearray,2); }
        if(!$error)
        {
            if(strlen($_FILES['myphoto']['name'])>4) {
                $name = $filearray['name'];
                $tmpName  = $filearray['tmp_name'];
                $uploaddir = 'uploads/';
                $uploadfile = $uploaddir . basename($name);
                $url = '../'.$uploadfile;
                if (move_uploaded_file($tmpName, $uploadfile))
                {
                    $result = "File <b>".$name."</b> was successfully uploaded!";
                }
                else {$error = "File upload failed!";}
            }
            $uptime = time();
            $update = mysqli_query($con,"UPDATE $user_table SET 
full_name = '$fullname',
phone = '$phone',
photo = '$url' 
WHERE ID = '$ID'");

            if(!$update)
            {
                $error = 'Update Error - '.mysqli_error($con);
            }
            else
            {
                $detail = "Account <b>".$fullname."</b> was updated";
                ActivityLog($con,$detail,$ID);
                $result .= ' Profile Updated successfully';
            }
        }
        if(isset($error) && strlen($error)>10) { echo "Error: ".$error; } else{echo $result;}
        break;

    case 'login':
        $array=array("email","password","remember");
        foreach($_REQUEST as $key=>$value){
            if(in_array($key,$array)){
                $$key=addslashes($value);
            }
        }

        $uemail = filter_var($email, FILTER_SANITIZE_STRING);
        $pasword = filter_var($password, FILTER_SANITIZE_STRING);
        $pword = md5($pasword);

        if(mysqli_num_rows(mysqli_query($con,"SELECT * FROM $user_table WHERE email='$uemail' AND password='$pword'"))!=1)
        {
            $error="Email and password does not match!";
        }

        else
        {
            $login = mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM $user_table WHERE email='$uemail' AND password='$pword'"));
            session_regenerate_id(true);
            //session_start();
            $_SESSION['ID'] = $login['ID'];
            $_SESSION['full_name'] = $login['full_name'];
            $update_login = mysqli_query($con,"UPDATE $user_table SET lastLogin=NOW() where ID='".$_SESSION['ID']."'");
            $detail = "New Login from <b>".$_SERVER['HTTP_USER_AGENT']."</b>";
            ActivityLog($con,$detail,$_SESSION['ID']);
            if($remember==1)
            {
                $cookie_name = "ID";
                $cookie_value = $login['ID'];
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day
            }
            if(is_null($login['last_login'])) {$result = 'Login Successful. Redirecting to profile page in 3 seconds. <script>window.setTimeout(function(){ window.location = "'.$website_url.'/user/EditProfile"; },3000)</script>';}
            else{$result = 'Login Successful. Redirecting in 5 seconds. <script>window.setTimeout(function(){ window.location = "'.$website_url.'/user/AllChats"; },5000)</script>';}
        }
        if(isset($error)) { echo "Error: ".$error; } else{echo $result;}
        break;

    case 'register':
//error_reporting(E_ALL);
        $array=array("email","password","confpass","fullname");
        foreach($_REQUEST as $key=>$value){
            if(in_array($key,$array)){
                $$key=addslashes($value);
            }
        }
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $password = filter_var($password, FILTER_SANITIZE_STRING);
        $confpass = filter_var($confpass, FILTER_SANITIZE_STRING);
        $fullname = filter_var($fullname, FILTER_SANITIZE_STRING);

        if(empty($email) || empty($password) || empty($confpass))
        {
            $error = 'All fields are required!';
        }

        else if(($validate->email($email,false))===false){
            $error = 'Enter a valid Email';
        }

        else if(strlen($validate->password($password))>5) {
            $error = $validate->password($password);
        }

        else if($password!=$confpass){
            $error = 'Passwords do not match!';
        }

        else if(mysqli_num_rows(mysqli_query($con,"select * from $user_table where email='$email'"))>0){
            $error = 'Email Already Exist';
        }

        else
        {
            $pword = md5($password);
            $insert = mysqli_query($con,"INSERT INTO $user_table (email,password,fullname,addDate) VALUES ('$email','$pword','$fullname',NOW())");
            if(!$insert)
            {
                $error = 'Insert Error: '.mysqli_error($con);
            }
            else
            {
                $result = "Registration Successful.";

                //send welcome email
                        $query = mysqli_query($con,"SELECT * FROM $template_table WHERE type=1");
                        $tempData = mysqli_fetch_assoc($query);

                        //replace template var with value
                        //$actlink = 'https://'.$_SERVER['SERVER_NAME'].'/Manager/EmailVerification?a='.$k['ID'].'&b='.$k['email_code'];
                        $fullname="";
                        $token = array(
                    'SITE_URL'  => 'http://'.$_SERVER['SERVER_NAME'],
                    'SITE_NAME' => $option['website_name'],
                    'USER_NAME' => $fullame,
                    'USER_EMAIL'=> $email,
                    'SEND_DATE'=> date('D M Y g:i a',time())
                  //  'ACTIVE_LINK'=> $actlink
                );
                $pattern = '[%s]';
                foreach($token as $key=>$val){
                    $varMap[sprintf($pattern,$key)] = $val;
                }
                $emailContent = strtr($tempData['content'],$varMap);
                        $from = 'noreply@'.$_SERVER['SERVER_NAME'];
                SendMail($from,$tempData['title'],$email,$emailContent,$option['website_name'],$fullname);
            }
        }

        if(isset($error)) { echo "Error: ".$error; } else{echo $result;}
        break;

    default:
        $error = '';
        $result = '';
        break;
}