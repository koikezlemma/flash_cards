<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Cache-Control" content="no-cache" />
  <meta http-equiv="Expires" content="0" />

  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>単語登録</title>
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.css" />
  <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.js"></script>


  <!-- Calender -->
  <link rel="stylesheet" type="text/css" href="http://dev.jtsage.com/cdn/datebox/latest/jqm-datebox.min.css" />
  <script type="text/javascript" src="http://dev.jtsage.com/cdn/datebox/latest/jqm-datebox.core.min.js"></script>
  <!-- 以下はCalBox modeの時に必要 -->
  <script type="text/javascript" src="http://dev.jtsage.com/cdn/datebox/latest/jqm-datebox.mode.calbox.min.js"></script>


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

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $is_update = true;
} else {
    $id = "";
    $is_update = false;
}

$mysqli = connect_my_db($key);
if ($is_update){
    $only_public = 0;
} else {
    $only_public = 1;
}
$category_list = get_category($user_name, $key, $only_public, $mysqli);
if ($is_update) {
    $word_item = get_word_data($user_name, $key, $id, $mysqli);
    if (count($word_item) == 0) {
        $is_update = 0;
    }
}
close_my_db($mysqli);

function get_current_timestamp_php() {
    $current_timestamp = getdate();
    $current_year = $current_timestamp['year'];
    $current_month = $current_timestamp['mon'];
    $current_day = $current_timestamp['mday'];

    if (intval($current_month) < 10) {
        $current_month = '0' . $current_month;
    }
    if (intval($current_day) < 10) {
        $current_day = '0' . $current_day;
    }
    return $current_year . '-' . $current_month . '-' . $current_day;
}
?>

<script>

function get_current_timestamp() {
    var d = new Date();
    var month  = d.getMonth() + 1;
    var day    = d.getDate();

    // 1桁を2桁に変換する
    if (month < 10) {month = "0" + month;}
    if (day < 10) {day = "0" + day;}

    // 整形して返却
    return d.getFullYear()  + "-" + month + "-" + day;
}


$(document).ready(function(){

    $("#submit_btn").bind('click', function(){
        $("#reg_result").text("登録中");
        $("input[name='submit_btn']").prop("disabled",true);
        var param = {};
        <?php if ($is_update) echo "        param['id'] = $id;\n"; ?>
        param['user_name'] = "<?php echo $user_name; ?>";
        param['key'] = "<?php echo $key; ?>";
        param['category_id'] = $('input[name="word_category"]:checked').val();
        param['text_front'] = $('#front_text_area').val();
        param['text_back'] = $('#back_text_area').val();
        <?php if ($is_update) echo "        param['text_header'] = $('#header_text_area').val();\n"; ?>
        param['text_footer'] = $('#footer_text_area').val();
        <?php if ($is_update) echo "        param['comment'] = $('#comment_text_area').val();\n"; ?>
        param['draft'] = $('#draft').prop('checked');
        param['reg_date'] = $('#reg_date').val();
        $.ajax({
            url: "db_insert_one_word.php",
            data: param,
            dataType: "json",
            type: "POST",
            error: function(XMLHttpRequest, textStatus, errorThrown){
                $("input[name='submit_btn']").prop("disabled",false);
                $("#reg_result").text("登録失敗");
                alert(textStatus + ", " + errorThrown);
            },
            success: function(data, status, xhr) {
                $("input[name='submit_btn']").prop("disabled",false);
                $("#reg_result").text("登録成功");
                $("#front_text_area").select();
                //alert("Status updated!");
            }
        });
    });
});

</script>

</head>

<body>

<!-- *************************************** -->
<!-- ********** Main Page ****************** -->
<!-- *************************************** -->
<div data-role="page" id="main_page" data-theme="a">

<!-- ********** Header ********************* -->
<div data-role="header">
    <a href="javascript:window.close();">Close</a>
    <h1>単語登録</h1>
</div>

<!-- ********** Content ******************** -->
<div data-role="content">

<?php
    if ($is_update) {
        echo "  <div data-role='fieldcontain'>\n";
        echo "    <label for='header_text_area'>ヘッダー</label>\n";
        echo "    <textarea name='header_text_area' id='header_text_area' title='ヘッダー'>" . $word_item[0]['text_header'] . "</textarea>\n";
        echo "  </div>\n";
    }
?>
  <div data-role="fieldcontain">
    <fieldset data-role="controlgroup" data-type="horizontal">
      <legend>カテゴリ</legend>
<?php
    $is_first_item = 1;
    foreach ($category_list as $category_item) {
        echo "<input type='radio' name='word_category' value='" . $category_item['category_id'] . "' id='" . $category_item['category_id'] . "'";
        if ($is_update) {
            if ($category_item['category_id'] == $word_item[0]['category_id']){
                echo " checked='checked' />\n";
            } else {
                echo "/>\n";
            }
        } else {
            if ($is_first_item == 1){
                echo " checked='checked' />\n";
            } else {
                echo "/>\n";
            }
        }
        echo "<label for='" . $category_item['category_id'] . "'>" . $category_item['category_name'] . "</label>\n";
        $is_first_item = 0;
    }
?>
    </fieldset>
  </div>
  <div data-role="fieldcontain">
    <label for="front_text_area">表面</label>
    <textarea name="front_text_area" id="front_text_area" title="表面"><?php
    if ($is_update) {
        echo $word_item[0]['text_front'];
    }
?></textarea>
  </div>
  <div data-role="fieldcontain">
    <label for="back_text_area">裏面</label>
    <textarea name="back_text_area" id="back_text_area" title="裏面"><?php
    if ($is_update) {
        echo $word_item[0]['text_back'];
    }
?></textarea>
  </div>
  <div data-role="fieldcontain">
    <label for="footer_text_area">フッター</label>
    <textarea name="footer_text_area" id="footer_text_area" title="フッター"><?php
    if ($is_update) {
        echo $word_item[0]['text_footer'];
    } else {
        echo get_current_timestamp_php() . ' ';
    }
?></textarea>
  </div>
  <div data-role="fieldcontain">
    <label for="draft">下書き</label>
<?php
    if ($is_update && $word_item[0]['draft']) {
        echo "  <input type='checkbox' name='draft' id='draft' checked='checked' />";
    } else {
        echo "  <input type='checkbox' name='draft' id='draft' />";
    }
?>
  </div>
  <div data-role="fieldcontain">
    <label for="reg_date">登録日</label>
<?php
    if ($is_update) {
        echo '  <input name="reg_date" id="reg_date" type="text" data-role="datebox" data-options=\'{"mode":"calbox"}\' value="' . $word_item[0]['reg_date'] . '" />';
    } else {
        echo '  <input name="reg_date" id="reg_date" type="text" data-role="datebox" data-options=\'{"mode":"calbox"}\' value="' . get_current_timestamp_php() . '" />';

    }
?>
  </div>
<?php
    if ($is_update) {
        echo "<div data-role='fieldcontain'>\n";
        echo "<label for='comment_text_area'>コメント</label>\n";
        echo "<textarea name='comment_text_area' id='comment_text_area' title='コメント'>" . $word_item[0]['comment'] . "</textarea>\n";
        echo "</div>\n";
    }
?>
  <div data-role="fieldcontain">
    <input id="submit_btn" name="submit_btn" type="submit" value="登録" data-inline="true" />
  </div>
  <p id="reg_result"></p>
</div>

<!-- ********** Footer ********************* -->
<div data-role="footer">
  <a href="javascript:window.close();">Close</a>
  <h1>単語登録</h1>
</div>

</div>



</body>
</html>



