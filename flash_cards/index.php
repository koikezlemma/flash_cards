<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Cache-Control" content="no-cache" />
  <meta http-equiv="Expires" content="0" />

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Flash Cards</title>
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.css" />
  <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.js"></script>

  <style>
  .wordbreak{
    overflow: visible;
    white-space: normal;
  }
  </style>

<?php
require_once('db_utility.php');

if (isset($_GET['user_name'])) {
    $user_name = $_GET['user_name'];
} else {
    $user_name = '';
}

if (isset($_GET['key'])) {
    $key = $_GET['key'];
} else {
    $key = '';
}
$summary_table = check_table($user_name, $key);
$front_to_back = $summary_table['front_to_back'];

?>

</head>

<body>

<!-- *************************************** -->
<!-- ********** Main Page ****************** -->
<!-- *************************************** -->
<div data-role="page" id="index" data-theme="a">

<!-- ********** Header ********************* -->
<div data-role="header">
  <h1>Flash Cards</h1>
</div>

<!-- ********** Content ******************** -->
<div data-role="content">

<ul data-role="listview" data-inset="true">
  <li data-role="list-divider"><h3>単語帳</h3></li>
<?php
    foreach ( (array)$summary_table['category_list'] as $category_item) {  // cast for NULL check
        echo "  <li><a href=\"flash_cards.php?user_name={$user_name}&key={$key}&start_category=" . $category_item['category_id'] . "&end_category=" . $category_item['category_id'] . "&retrieval_type=1&front_to_back=" . $front_to_back . "&incl_max_lv=0\" target=\"_blank\">\n";
        echo "    <h3>" . $category_item['category_name'] . "</h3>\n";
        echo "    " . $category_item['num_words_finished'] . " / " . $category_item['num_words'] . " (active: " . $category_item['num_words_active'] . ")";
        echo "  </a></li>\n";
    }
?>
  <li data-role="list-divider"><h3>その他</h3></li>
  <li><a href="register_one_word.php?user_name=<?php echo $user_name; ?>&key=<?php echo $key; ?>" target="_blank">
    <h3>登録</h3>
    <p>新規に項目を登録する</p>
  </a></li>
  <li><a href="flash_cards.php?user_name=<?php echo $user_name; ?>&key=<?php echo $key; ?>&draft_type=2&layout=0" target="_blank">
    <h3>下書き項目表示</h3>
    <p>下書き中の項目を一覧表示する</p>
  </a></li>
</ul>


</div>


<!-- ********** Footer ********************* -->
<div data-role="footer">
    <h1>Flash Cards</h1>
</div>

</div>


</body>
</html>


