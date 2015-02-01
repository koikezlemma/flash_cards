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
} else {
    $id = 0;
}

if (isset($_POST['level'])) {
    $level = intval($_POST['level']);
} else {
    $level = 0;
}

if (isset($_POST['status_ng'])) {
    $status_ng = intval($_POST['status_ng']);
} else {
    $status_ng = 0;
}

if (isset($_POST['inc_ok'])) {
    $inc_ok = intval($_POST['inc_ok']);
} else {
    $inc_ok = 0;
}

if (isset($_POST['inc_even'])) {
    $inc_even = intval($_POST['inc_even']);;
} else {
    $inc_even = 0;
}

if (isset($_POST['inc_ng'])) {
    $inc_ng = intval($_POST['inc_ng']);;
} else {
    $inc_ng = 0;
}

if (isset($_POST['force_expiration'])) {
    $force_expiration = intval($_POST['force_expiration']);
} else {
    $force_expiration = 0;
}

if (isset($_POST['front_to_back'])) {
    $front_to_back = intval($_POST['front_to_back']);
} else {
    $front_to_back = 0;
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
$user = $my_db['user'];
$password = $my_db['password'];
$database = $my_db['database'];

$mysqli = new mysqli($host, $user, $password, $database);
if (mysqli_connect_errno()) {
    die("Connect Error\n");
}
if (!$mysqli->set_charset("utf8")) {
    die("Error loading character set utf8\n");
}

$query_select = 'SELECT count(*) ' .
                'FROM ' . $db_table_name['status'] . ' s ' .
                'INNER JOIN ' . $db_table_name['user'] . ' u ON s.user_id = u.user_id ' .
                'WHERE (s.id = ?) AND (u.user_name = ?) AND (s.front_to_back = ?);';
$has_item = false;
if ($stmt = $mysqli->prepare($query_select)) {

    $result = $stmt->bind_param('isi', $id, $user_name, $front_to_back);
    if ($result == false){
        die("Error BIND: " . $mysqli->error . "\n");
    }
    $result = $stmt->execute();
    if ($result == false){
        die("Error SELECT: " . $mysqli->error . "\n");
    }

    $count = NULL;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
}


if ( $force_expiration == 1 ){
    $str_expiration = 'NOW()';
} else {
    $str_expiration = 'DATE_ADD(ADDTIME(NOW(), t.time), INTERVAL t.date DAY)';
}
if ($count > 0) {
    $query = 'UPDATE ' . $db_table_name['status'] . ' s ' .
             'INNER JOIN ' . $db_table_name['time_span'] . ' t ON s.level = t.level ' .
             'INNER JOIN ' . $db_table_name['user'] . ' u ON s.user_id = u.user_id ' .
             'SET s.expiration_date = ' . $str_expiration . ', ' .
             '    s.level = ?, ' .
             '    s.status_ng = ?, ' .
             '    s.update_date = NOW(), ' .
             '    s.num_ok = s.num_ok+?, ' .
             '    s.num_even = s.num_even+?, ' .
             '    s.num_ng = s.num_ng+? ' .
             'WHERE (s.id = ?) AND (u.user_name = ?) AND (s.front_to_back = ?);';

    if ($stmt = $mysqli->prepare($query)) {
        $result = $stmt->bind_param('iiiiiisi', $level, $status_ng, $inc_ok, $inc_even, $inc_ng, $id, $user_name, $front_to_back);
        if ($result == false){
            die("Error UPDATE: " . $mysqli->error . "\n");
        }
        $result = $stmt->execute();
        if ($result == false){
            die("Error UPDATE: " . $mysqli->error . "\n");
        }
        $stmt->close();
    } else {
        die("Error UPDATE prepare: " . $mysqli->error . "\n");
    }
} else {
    if ( $force_expiration == 1 ){
        $query = 'INSERT INTO ' . $db_table_name['status'] . ' (id, user_id, front_to_back, level, status_ng, update_date, expiration_date, num_ok, num_even, num_ng) ' .
                 'VALUES (?, ' .
                 '        (SELECT user_id FROM ' . $db_table_name['user'] . ' WHERE user_name = ?), ' .
                 '        ?, ?, ?, NOW(), NOW(), ?, ?, ?)';
    } else {
        $query = 'INSERT INTO ' . $db_table_name['status'] . ' (id, user_id, front_to_back, level, status_ng, update_date, expiration_date, num_ok, num_even, num_ng) ' .
                 'VALUES (?, ' .
                 '        (SELECT user_id FROM ' . $db_table_name['user'] . ' WHERE user_name = ?), ' .
                 '        ?, ?, ?, NOW(), ' .
                 '        DATE_ADD(ADDTIME(NOW(), (SELECT time FROM ' . $db_table_name['time_span'] . ' WHERE level = ?)), ' .
                 '                 INTERVAL (SELECT date FROM ' . $db_table_name['time_span'] . ' WHERE level = ?) DAY), ' .
                 '        ?, ?, ?)';
    }

    if ($stmt = $mysqli->prepare($query)) {

        $num_ok = ($inc_ok >= 0) ? $inc_ok: 0;
        $num_even = ($inc_even >= 0) ? $inc_even: 0;
        $num_ng = ($inc_ng >= 0) ? $inc_ng: 0;
        if ( $force_expiration == 1 ){
            $result = $stmt->bind_param('isiiiiii', $id, $user_name, $front_to_back, $level, $status_ng, $num_ok, $num_even, $num_ng);
        } else {
            $result = $stmt->bind_param('isiiiiiiii', $id, $user_name, $front_to_back, $level, $status_ng, $level, $level, $num_ok, $num_even, $num_ng);
        }
        if ($result == false){
            die("Error INSERT: " . $mysqli->error . "\n");
        }
        $result = $stmt->execute();
        if ($result == false){
            die("Error INSERT: " . $mysqli->error . "\n");
        }
        $stmt->close();
    } else {
        die("Error INSERT prepare: " . $mysqli->error . "\n");
    }
}
$mysqli->close();


echo json_encode( $my_data );
?>



