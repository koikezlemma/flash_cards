<?php

// *****************************************
//   Loading Database Config.
// *****************************************

$my_db_host = $_ENV['DB_CARDS_HOST'];
$my_db_user = $_ENV['DB_CARDS_USER'];
$my_db_password = $_ENV['DB_CARDS_PASSWORD'];
$my_db_database = $_ENV['DB_CARDS_DATABASE'];
$my_db_access_key = $_ENV['DB_CARDS_ACCESS_KEY'];  // used by the application

// require_once('db_cards_conf_demo.php');
// $my_db_host = $db_cards['host'];
// $my_db_user = $db_cards['user'];
// $my_db_password = $db_cards['password'];
// $my_db_database = $db_cards['database'];
// $my_db_access_key = $db_cards['access_key'];  // used by the application


// *****************************************
//   Database Information
// *****************************************

$my_db = array(
    'host' => $my_db_host,
    'user' => $my_db_user,
    'password' => $my_db_password,
    'database' => $my_db_database,
);

// *****************************************
//   Application Config
// *****************************************

$app_config = array(
    'access_key' => $my_db_access_key,  // password used by the application
    'read_only' => 'no', // if 'yes', the application does not write anything to DB
 );

// Table names (this array is used for creating and accessing the tables)
$db_table_name = array(
    'word' => 'word_list',
    'status' => 'status_list',
    'app_config' => 'app_config_list',
    'user' => 'user_list',
    'category' => 'category_list',
    'time_span' => 'time_span_list',
);


// *****************************************
//   Config for Creating Database at the Beginning
// *****************************************

// Default users adding to the 'user' table
$db_user_list = array('guest');

// Categories to sort items
$db_category_list = array(
    '101' => '英単語',
    '201' => '英文',
);

// These settings are stored in the DB
$db_app_config = array(
    'num_levels' => '8',  // max level to achieve
    'init_level' => '0',  // initial level (min:0)
    'front_to_back' => '0',  // if '1', answer the back side from the front side
);

// After answering a question, we hide the question for a while.
// The number of list items should be $db_app_config['num_levels']
$db_time_span_list = array(
    array('level'=>0, 'time'=>'00:00:00', 'date'=>0),
    array('level'=>1, 'time'=>'01:00:00', 'date'=>0),
    array('level'=>2, 'time'=>'18:00:00', 'date'=>0),
    array('level'=>3, 'time'=>'18:00:00', 'date'=>2),
    array('level'=>4, 'time'=>'00:00:00', 'date'=>6),
    array('level'=>5, 'time'=>'00:00:00', 'date'=>20),
    array('level'=>6, 'time'=>'00:00:00', 'date'=>60),
    array('level'=>7, 'time'=>'00:00:00', 'date'=>80),
);


?>
