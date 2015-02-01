<?php

require_once('db_conf.php');

function connect_my_db($key) {
    global $my_db;
    global $app_config;
    if ($key != $app_config['access_key']) {
        return NULL;
    }
    $host = $my_db['host'];
    $user = $my_db['user'];
    $password = $my_db['password'];
    $database = $my_db['database'];

    $mysqli = new mysqli($host, $user, $password, $database);
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", $mysqli->connect_error);
        die('Connect Error: ' . mysqli_connect_errno());
    }
    if (!$mysqli->set_charset("utf8")) {
        die("Error loading character set utf8\n");
    }
    return $mysqli;
}

function close_my_db($mysqli) {
    $mysqli->close();
}


function get_category($user_name, $key, $only_public, $mysqli)
{
    global $db_table_name;
    global $app_config;
    $my_data = array();

    if ($only_public) {
        $query_where = ' WHERE open = 1;';
    } else {
        $query_where = ';';
    }
    $query = 'SELECT category_id, category_name FROM ' . $db_table_name['category'] . $query_where;

    if ( ($mysqli != NULL) && ($key == $app_config['access_key']) && ($stmt = $mysqli->prepare($query)) ) {

        $stmt->execute();

        $category_id = $category_name = NULL;
        $stmt->bind_result($category_id, $category_name);
        while ($stmt->fetch()) {
            $my_item = array();
            $my_item['category_id'] = $category_id;
            $my_item['category_name'] = $category_name;
            array_push($my_data, $my_item);
        }
        $stmt->close();
    }
    return $my_data;
}

function get_category_info($user_name, $key, $only_public, $front_to_back, $mysqli)
{
    global $db_table_name;
    global $app_config;
    $my_data = array();
    $category_list = get_category($user_name, $key, $only_public, $mysqli);
    if ( ($mysqli == NULL) || ($key != $app_config['access_key']) ) {
        return;
    }

    foreach ($category_list as $category_item) {
        $my_item = array();
        $my_item['category_id'] = $category_item['category_id'];
        $my_item['category_name'] = $category_item['category_name'];
        $query = 'SELECT COUNT(*) FROM ' . $db_table_name['word'] . ' WHERE category_id = ' . $category_item['category_id'] . ';';
        if ($stmt = $mysqli->prepare($query)) {
            $result = $stmt->execute();
            if ($result == false){
                die("Error execute: " . $mysqli->error . "\n");
            }
            $word_count = NULL;
            $stmt->bind_result($word_count);
            if ($stmt->fetch()){
                $my_item['num_words'] = $word_count;
            }
            $stmt->close();
        } else {
            die("Error prepare: " . $mysqli->error . "\n");
        }

        $num_levels = get_num_levels($key, $mysqli);
        $max_lv = $num_levels - 1;
        $query = 'SELECT COUNT(*) FROM ' . $db_table_name['status'] . ' s INNER JOIN ' . $db_table_name['word'] . ' w ON s.id = w.id INNER JOIN ' . $db_table_name['user'] . ' u  ON s.user_id = u.user_id WHERE (s.level = ?)  AND (w.category_id = ' . $category_item['category_id']. ') AND (s.front_to_back = ?) AND (u.user_name = ?);';
        if ($stmt = $mysqli->prepare($query)) {
            $result = $stmt->bind_param('iis', $max_lv, $front_to_back, $user_name);
            if ($result == false){
                die("Error bind: " . $mysqli->error . "\n");
            }
            $result = $stmt->execute();
            if ($result == false){
                die("Error execute: " . $mysqli->error . "\n");
            }
            $count = NULL;
            $stmt->bind_result($count);
            if ($stmt->fetch()) {
                $my_item['num_words_finished'] = $count;
            }
            $stmt->close();
        } else {
            die("Error prepare: " . $mysqli->error . "\n");
        }

        $query = 'SELECT COUNT(*) FROM ' . $db_table_name['status'] . ' s INNER JOIN ' . $db_table_name['word'] . ' w ON s.id = w.id INNER JOIN ' . $db_table_name['user'] . ' u  ON s.user_id = u.user_id WHERE ((s.level = ?) OR (s.expiration_date >= NOW()))  AND (w.category_id = ' . $category_item['category_id']. ') AND (s.front_to_back = ?) AND  (u.user_name = ?);';
        if ($stmt = $mysqli->prepare($query)) {
            $result = $stmt->bind_param('iis', $max_lv, $front_to_back, $user_name);
            //$result = $stmt->bind_param('ii', $max_lv, $front_to_back);
            if ($result == false){
                die("Error bind: " . $mysqli->error . "\n");
            }
            $result = $stmt->execute();
            if ($result == false){
                die("Error execute: " . $mysqli->error . "\n");
            }
            $count = NULL;
            $stmt->bind_result($count);
            while ($stmt->fetch()) {
                $my_item['num_words_active'] = $word_count - $count;
            }
            $stmt->close();
        } else {
            die("Error prepare: " . $mysqli->error . "\n");
        }

        array_push($my_data, $my_item);
    }

    return $my_data;
}

function get_word_data($user_name, $key, $id, $mysqli)
{
    global $db_table_name;
    global $app_config;
    $my_data = array();

    $query = 'SELECT user_id, category_id, draft, reg_date, text_front, text_back, text_header, text_footer, comment FROM ' .
        $db_table_name['word'] . ' WHERE id = ?;';

    if ( ($mysqli != NULL) && ($key == $app_config['access_key']) && ($stmt = $mysqli->prepare($query)) ) {

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $user_id = $category_id = $draft = $reg_date = $text_front = $text_back = $text_header = $text_footer = $comment = NULL;
        $stmt->bind_result($user_id, $category_id, $draft, $reg_date, $text_front, $text_back, $text_header, $text_footer, $comment);
        if ($stmt->fetch()) {
            $my_item = array();
            $my_item['id'] = $id;
            $my_item['user_id'] = $user_id;
            $my_item['category_id'] = $category_id;
            $my_item['draft'] = $draft;
            $my_item['reg_date'] = $reg_date;
            $my_item['text_front'] = $text_front;
            $my_item['text_back'] = $text_back;
            $my_item['text_header'] = $text_header;
            $my_item['text_footer'] = $text_footer;
            $my_item['comment'] = $comment;
            array_push($my_data, $my_item);
        }
        $stmt->close();
    }
    return $my_data;
}

function connect_db_and_get_num_levels($key)
{
    $num_levels = 1;
    $mysqli = connect_my_db($key);

    if ($mysqli != NULL) {
        $num_levels = get_num_levels($key, $mysqli);
        close_my_db($mysqli);
    }

    return $num_levels;
}

function get_app_conf($item, $key, $mysqli)
{
    global $app_config;
    global $db_table_name;

    $ret_value = 0;

    $query = 'SELECT conf_item, conf_value FROM ' . $db_table_name['app_config'] . ';';

    if ( ($mysqli != NULL) && ($key == $app_config['access_key']) && ($stmt = $mysqli->prepare($query)) ) {

        $result = $stmt->execute();
        if ($result == false){
            echo "Error: SELECT\n";
            printf("Errormessage: %s\n", $mysqli->error);
        }

        $conf_item = $conf_value = NULL;
        $stmt->bind_result($conf_item, $conf_value);
        while ($stmt->fetch()) {
            if ($conf_item == $item) {
                $ret_value = intval($conf_value);
                break;
            }
        }
        $stmt->close();
    }

    return $ret_value;
}

function get_num_levels($key, $mysqli)
{
    return get_app_conf('num_levels', $key, $mysqli);
}

function get_front_to_back($key, $mysqli)
{
    return get_app_conf('front_to_back', $key, $mysqli);
}

function get_init_level($key, $mysqli)
{
    return get_app_conf('init_level', $key, $mysqli);
}

function check_table($user_name, $key) {
    global $my_db;
    global $app_config;
    global $db_table_name;
    global $db_app_config;
    global $db_user_list;
    global $db_category_list;
    global $db_time_span_list;

    if ($key != $app_config['access_key']) {
        return array();
    }
    $mysqli = connect_my_db($key);
    if ($mysqli == NULL) {
        return array();
    }

    $query_table = array();

    // app_config_list
    array_push($query_table,
        'CREATE TABLE IF NOT EXISTS `' . $db_table_name['app_config'] . '` (' .
        'conf_item CHAR(128) NOT NULL, ' .
        'conf_value CHAR(128) NOT NULL, ' .
        'PRIMARY KEY (conf_item) ' .
        ') DEFAULT CHARSET utf8 COLLATE utf8_general_ci;'
    );

    // user_list
    array_push($query_table,
        'CREATE TABLE IF NOT EXISTS `' . $db_table_name['user'] . '` (' .
        'user_id INT(11) NOT NULL AUTO_INCREMENT, ' .
        'user_name CHAR(32) NOT NULL, ' .
        'INDEX idx_user_name (user_name), ' .
        'PRIMARY KEY (user_id) ' .
        ') DEFAULT CHARSET utf8 COLLATE utf8_general_ci;'
    );

    // category_list
    array_push($query_table,
        'CREATE TABLE IF NOT EXISTS `' . $db_table_name['category'] . '` (' .
        'category_id INT(11) NOT NULL, ' .
        'open TINYINT(1) NOT NULL DEFAULT 1, ' .
        'category_name CHAR(128) NOT NULL, ' .
        'PRIMARY KEY (category_id) ' .
        ') DEFAULT CHARSET utf8 COLLATE utf8_general_ci;'
    );

    // time_span_list
    array_push($query_table,
        'CREATE TABLE IF NOT EXISTS `' . $db_table_name['time_span'] . '` (' .
        'level INT(11) NOT NULL, ' .
        'time TIME NOT NULL, ' .
        'date INT(11) NOT NULL, ' .
        'PRIMARY KEY (level) ' .
        ') DEFAULT CHARSET utf8 COLLATE utf8_general_ci;'
    );

    // word_list
    array_push($query_table,
        'CREATE TABLE IF NOT EXISTS `' . $db_table_name['word'] . '` (' .
        'id INT(11) NOT NULL AUTO_INCREMENT, ' .
        'user_id INT(11) NOT NULL, ' .
        'category_id INT(11) NOT NULL, ' .
        'draft TINYINT(1) NOT NULL DEFAULT 0, ' .
        'reg_date DATE NOT NULL DEFAULT \'2012-12-01\', ' .
        'text_front TEXT NOT NULL, ' .
        'text_back TEXT NOT NULL, ' .
        'text_header TEXT NOT NULL, ' .
        'text_footer TEXT NOT NULL, ' .
        'comment TEXT NOT NULL, ' .
        'INDEX idx_user_id(user_id), ' .
        'INDEX idx_category_id(category_id), ' .
        'PRIMARY KEY (id)' .
        ') DEFAULT CHARSET utf8 COLLATE utf8_general_ci;'
    );

    // status_list
    array_push($query_table,
        'CREATE TABLE IF NOT EXISTS `' . $db_table_name['status'] . '` (' .
        'id INT(11) NOT NULL, ' .
        'user_id INT(11) NOT NULL, ' .
        'front_to_back TINYINT(1) NOT NULL, ' .
        'level INT(11) NOT NULL DEFAULT 0, ' .
        'status_ng TINYINT(1) NOT NULL DEFAULT 0, ' .
        'update_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ' .
        'expiration_date TIMESTAMP NOT NULL DEFAULT \'2012-12-01 09:00:00\', ' .
        'num_ok INT(11) NOT NULL DEFAULT 0, ' .
        'num_even INT(11) NOT NULL DEFAULT 0, ' .
        'num_ng INT(11) NOT NULL DEFAULT 0, ' .
        'INDEX idx_user_id(user_id), ' .
        'PRIMARY KEY (id, user_id, front_to_back)' .
        ') DEFAULT CHARSET utf8 COLLATE utf8_general_ci;'
    );


    foreach ($query_table as $query) {
        if ($stmt = $mysqli->prepare($query)) {
            $result = $stmt->execute();
            if ($result == false){
                echo "Error: CREATE\n";
                printf("Errormessage: %s\n", $mysqli->error);
            }
            $stmt->close();
        }
    }

    // データの追加
    $query_select = 'SELECT count(*) FROM ' . $db_table_name['app_config'] . ';';
    $item_count = 0;
    if ($stmt = $mysqli->prepare($query_select)) {
        $result = $stmt->execute();
        if ($result == false){
            echo "Error: SELECT\n";
            printf("Errormessage: %s\n", $mysqli->error);
        }
        $stmt->bind_result($item_count);
        $stmt->fetch();
        $stmt->close();
    }
    if ($item_count == 0) {
        $query = 'INSERT INTO ' . $db_table_name['app_config'] . ' (conf_item, conf_value) VALUES (?, ?)';
        foreach ($db_app_config as $conf_item => $conf_value) {
            if ($stmt = $mysqli->prepare($query)) {
                $result = $stmt->bind_param('ss', $conf_item, $conf_value);
                if ($result == false){
                    echo "Error: INSERT bind\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $result = $stmt->execute();
                if ($result == false){
                    echo "Error: CREATE\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $stmt->close();
            }
        }

    }

    $query_select = 'SELECT count(*) FROM ' . $db_table_name['user'] . ';';
    $item_count = 0;
    if ($stmt = $mysqli->prepare($query_select)) {
        $result = $stmt->execute();
        if ($result == false){
            echo "Error: SELECT\n";
            printf("Errormessage: %s\n", $mysqli->error);
        }
        $stmt->bind_result($item_count);
        $stmt->fetch();
        $stmt->close();
    }
    if ($item_count == 0) {
        $query = 'INSERT INTO ' . $db_table_name['user'] . ' (user_name) VALUES (?)';
        foreach ($db_user_list as $user_name) {
            if ($stmt = $mysqli->prepare($query)) {
                $result = $stmt->bind_param('s', $user_name);
                if ($result == false){
                    echo "Error: INSERT bind\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $result = $stmt->execute();
                if ($result == false){
                    echo "Error: CREATE\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $stmt->close();
            }
        }
    }

    $query_select = 'SELECT count(*) FROM ' . $db_table_name['category'] . ';';
    $item_count = 0;
    if ($stmt = $mysqli->prepare($query_select)) {
        $result = $stmt->execute();
        if ($result == false){
            echo "Error: SELECT\n";
            printf("Errormessage: %s\n", $mysqli->error);
        }
        $stmt->bind_result($item_count);
        $stmt->fetch();
        $stmt->close();
    }
    if ($item_count == 0) {
        $query = 'INSERT INTO ' . $db_table_name['category']. ' (category_id, open, category_name) VALUES (?, 1, ?)';
        foreach ($db_category_list as $category_id => $category_name) {
            if ($stmt = $mysqli->prepare($query)) {
                $result = $stmt->bind_param('is', intval($category_id), $category_name);
                if ($result == false){
                    echo "Error: INSERT bind\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $result = $stmt->execute();
                if ($result == false){
                    echo "Error: CREATE\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $stmt->close();
            }
        }
    }

    $query_select = 'SELECT count(*) FROM ' . $db_table_name['time_span'] . ';';
    $item_count = 0;
    if ($stmt = $mysqli->prepare($query_select)) {
        $result = $stmt->execute();
        if ($result == false){
            echo "Error: SELECT\n";
            printf("Errormessage: %s\n", $mysqli->error);
        }
        $stmt->bind_result($item_count);
        $stmt->fetch();
        $stmt->close();
    }
    if ($item_count == 0) {
        $query = 'INSERT INTO ' . $db_table_name['time_span']. ' (level, time, date) VALUES (?, ?, ?)';
        foreach ($db_time_span_list as $item) {
            if ($stmt = $mysqli->prepare($query)) {
                $result = $stmt->bind_param('isi', $item['level'], $item['time'], $item['date'] );
                if ($result == false){
                    echo "Error: INSERT bind\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $result = $stmt->execute();
                if ($result == false){
                    echo "Error: CREATE\n";
                    printf("Errormessage: %s\n", $mysqli->error);
                }
                $stmt->close();
            }
        }
    }

    $summary_table = array();
    $summary_table['num_levels'] = get_num_levels($key, $mysqli);
    $front_to_back = get_front_to_back($key, $mysqli);
    $summary_table['front_to_back'] = $front_to_back;
    $summary_table['category_list'] = get_category_info($user_name, $key, true, $front_to_back, $mysqli);

    close_my_db($mysqli);

    return $summary_table;
}



?>
