<?php
require_once('db_conf.php');

if($_SERVER["REQUEST_METHOD"] != "POST"){
    header("HTTP/1.0 404 Not Found");
    exit;
}
header("Content-Type: text/html; charset=UTF-8");

header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_POST['user_name'])) {
    $user_name = $_POST['user_name'];
} else {
    $user_name = '';
}

if (isset($_POST['key'])) {
    $key = $_POST['key'];
} else {
    $key = '';
}

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $is_update = true;
} else {
    $id = 0;
    $is_update = false;
}

if (isset($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);
} else {
    $category_id = 0;
}

if (isset($_POST['text_front'])) {
    $text_front = trim(stripslashes($_POST['text_front']));
} else {
    $text_front = '';
}

if (isset($_POST['text_back'])) {
    $text_back = trim(stripslashes($_POST['text_back']));
} else {
    $text_back = '';
}

if (isset($_POST['text_header'])) {
    $text_header = trim(stripslashes($_POST['text_header']));
} else {
    $text_header = '';
}

if (isset($_POST['text_footer'])) {
    $text_footer = trim(stripslashes($_POST['text_footer']));
} else {
    $text_footer = '';
}

if (isset($_POST['comment'])) {
    $comment = trim(stripslashes($_POST['comment']));
} else {
    $comment = '';
}

if (isset($_POST['reg_date'])) {
    $reg_date = $_POST['reg_date'];
} else {
    $reg_date = '2001-01-01';
}

if (isset($_POST['draft'])) {
    if ( is_numeric($_POST['draft']) ) {
        $draft = intval($_POST['draft']);
    } else {
        if ( ($_POST['draft']==='on') || ($_POST['draft']==='ON') || ($_POST['draft']==='On') || ($_POST['draft']==='true')) {
            $draft = 1;
        } else {
            $draft = 0;
        }
    }
} else {
    $draft = 0;
}

$my_data = array();
if ($key != $app_config['access_key']) {
    die("Error: Access key not correct!\n");
}
if ($app_config['read_only'] == 'yes') {
    $my_data[] = 'Warning: Read only mode';
    echo json_encode($my_data);
    exit;
}

$host = $my_db['host'];
$db_user = $my_db['user'];
$password = $my_db['password'];
$database = $my_db['database'];

$mysqli = new mysqli($host, $db_user, $password, $database);
if (mysqli_connect_errno()) {
    die("Connect Error\n");
}
if (!$mysqli->set_charset("utf8")) {
    die("Error loading character set utf8\n");
}

if ($is_update) {
    $query = 'UPDATE '. $db_table_name['word'] . ' w SET w.user_id=(SELECT u.user_id FROM ' . $db_table_name['user'] . ' u WHERE u.user_name=?), w.category_id=?, w.draft=?, w.reg_date=?, w.text_front=?, w.text_back=?, w.text_header=?, w.text_footer=?, w.comment=? WHERE w.id = ?;';

    if ($stmt = $mysqli->prepare($query)) {

        $result = $stmt->bind_param('siissssssi', $user_name, $category_id, $draft, $reg_date, $text_front, $text_back, $text_header, $text_footer, $comment, $id);
        if ($result == false){
            die("Error BIND: " . $mysqli->error . "\n");
        }
        $result = $stmt->execute();
        if ($result == false){
            die("Error UPDATE: " . $mysqli->error . "\n");
        }
        $stmt->close();
    }
} else {
    $query = 'INSERT INTO ' . $db_table_name['word'] . ' (category_id, draft, reg_date, text_front, text_back, text_header, text_footer, comment, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, (SELECT user_id FROM '. $db_table_name['user'] . ' WHERE user_name = ?))';

    if ($stmt = $mysqli->prepare($query)) {

        $result = $stmt->bind_param('iisssssss', $category_id, $draft, $reg_date, $text_front, $text_back, $text_header, $text_footer, $comment, $user_name);
        if ($result == false){
            die("Error BIND: " . $mysqli->error . "\n");
        }
        $result = $stmt->execute();
        if ($result == false){
            die("Error INSERT: " . $mysqli->error . "\n");
        }
        $stmt->close();
    }
}
$mysqli->close();

echo json_encode( $my_data );


?>



