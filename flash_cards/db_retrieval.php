<?php

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

require_once('db_conf.php');
require_once('db_utility.php');

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

if (isset($_POST['start_category'])) {
    $start_category = intval($_POST['start_category']);
} else {
    $start_category = '0';
}
if (isset($_POST['end_category'])) {
    $end_category = intval($_POST['end_category']);
} else {
    $end_category = '1000';
}

if (isset($_POST['start_date'])) {
    $start_date = $_POST['start_date'];
} else {
    $start_date = '2001-01-01';
}

if (isset($_POST['end_date'])) {
    $end_date = $_POST['end_date'];
} else {
    $end_date = '2099-12-31';
}

if (isset($_POST['retrieval_type'])) {
    $retrieval_type = intval($_POST['retrieval_type']);
} else {
    $retrieval_type = 0;
}

if (isset($_POST['draft_type'])) {
    $draft_type = intval($_POST['draft_type']);
} else {
    $draft_type = 0;
}

if (isset($_POST['incl_max_lv'])) {
    $incl_max_lv = intval($_POST['incl_max_lv']);
} else {
    $incl_max_lv = 1;
}

if (isset($_POST['random'])) {
    $random = intval($_POST['random']);
} else {
    $random = 0;
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

if ($draft_type == 1){
    $conf_draft = ' ';
} else if ($draft_type == 2){
    $conf_draft = ' AND (w.draft = 1) ';
} else {
    $conf_draft = ' AND (w.draft = 0) ';
}

if ($incl_max_lv == 1){
    $conf_incl_max_lv = ' ';
} else {
    $max_lv = get_num_levels($key, $mysqli) - 1;
    $conf_incl_max_lv = ' AND ((s.num_ok IS NULL) OR (s.level < ' . $max_lv . ')) ';
}

if ($retrieval_type == 0){
    $conf_expiration = '';
} else {
    $conf_expiration = ' AND ((s.num_ok IS NULL) OR (s.expiration_date < NOW( )))';
}

if ($random == 0){
    $conf_order = 's.status_ng DESC, w.id ASC';
} else {
    $conf_order = 'RAND()';
}

$query = 'SELECT w.id, c.category_id, c.category_name, w.draft, w.reg_date, s.level, s.status_ng, s.num_ok, s.num_even, s.num_ng, ' .
         '       w.text_front, w.text_back, w.text_header, w.text_footer, w.comment, CASE WHEN s.num_ok IS NULL THEN FALSE ELSE TRUE END has_status ' .
         'FROM ' . $db_table_name['word'] . ' w ' .
         'INNER JOIN ' . $db_table_name['category'] . ' c ON w.category_id = c.category_id ' .
         'LEFT OUTER JOIN ' .
         '    (SELECT ss.id, ss.user_id, ss.front_to_back, ss.level, ss.status_ng, ss.expiration_date, ss.num_ok, ss.num_even, ss.num_ng ' .
         '     FROM ' . $db_table_name['status'] . ' ss ' .
         '     INNER JOIN ' . $db_table_name['user'] . ' u ON ss.user_id = u.user_id ' .
         '     WHERE u.user_name = ? AND front_to_back = ?) s ' .
         '    ON w.id = s.id ' .
         'WHERE (c.category_id BETWEEN ? AND ?) AND (reg_date BETWEEN ? AND ?) ' . $conf_draft . $conf_incl_max_lv . $conf_expiration . ' ' .
         'ORDER BY ' . $conf_order . ' ' .
         'LIMIT 50';

if ($stmt = $mysqli->prepare($query)) {

    $init_level = get_init_level($key, $mysqli);

    $result = $stmt->bind_param('siiiss', $user_name, $front_to_back, $start_category, $end_category, $start_date, $end_date);
    if ($result == false){
        die("Error BIND" . $mysqli->error . "\n");
    }
    $result = $stmt->execute();
    if ($result == false){
        die("Error SELECT" . $mysqli->error . "\n");
    }

    $id = $category_id = $category_name = $draft = $reg_date = $level = $status_ng = $num_ok = $num_even = $num_ng = $text_front = $text_back = $text_header = $text_footer = $comment = $has_status = NULL;
    $stmt->bind_result($id, $category_id, $category_name, $draft, $reg_date, $level, $status_ng, $num_ok, $num_even, $num_ng, $text_front, $text_back, $text_header, $text_footer, $comment, $has_status);
    while ($stmt->fetch()) {
        $my_item = array();
        $my_item['id'] = $id;
        $my_item['category_id'] = $category_id;
        $my_item['category_name'] = $category_name;
        $my_item['draft'] = $draft;
        $my_item['reg_date'] = $reg_date;
        if ($has_status) {
            $my_item['level'] = $level;
            $my_item['status_ng'] = $status_ng;
            $my_item['num_ok'] = $num_ok;
            $my_item['num_even'] = $num_even;
            $my_item['num_ng'] = $num_ng;
            if ( ($num_ok + $num_even + $num_ng) == 0 ){
                $accuracy_rate = 0;
            } else {
               $accuracy_rate = 100 * ($num_ok + $num_even)/ (float)($num_ok + $num_even + $num_ng);
            }
            $my_item['accuracy_rate'] = number_format ( $accuracy_rate , 1 );
        } else {
            $my_item['level'] = $init_level;
            $my_item['status_ng'] = 0;
            $my_item['num_ok'] = 0;
            $my_item['num_even'] = 0;
            $my_item['num_ng'] = 0;
            $my_item['accuracy_rate'] = 0;
        }
        $my_item['text_front'] = $text_front;
        $my_item['text_back'] = $text_back;
        $my_item['text_header'] = $text_header;
        $my_item['text_footer'] = $text_footer;
        $my_item['comment'] = $comment;
        $my_item['has_status'] = $has_status;
        array_push($my_data, $my_item);
    }
    $stmt->close();
}
$mysqli->close();

echo json_encode( $my_data );
?>



