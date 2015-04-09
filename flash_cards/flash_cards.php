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

if (isset($_GET['start_category'])) {
    $start_category = intval($_GET['start_category']);
} else {
    $start_category = 0;
}

if (isset($_GET['end_category'])) {
    $end_category = intval($_GET['end_category']);
} else {
    $end_category = 65536;
}

if (isset($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
} else {
    $start_date = '2001-01-01';
}

if (isset($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
} else {
    $end_date = '2099-12-31';
}

if (isset($_GET['retrieval_type'])) {
    $retrieval_type = intval($_GET['retrieval_type']);
} else {
    $retrieval_type = 0;
}

if (isset($_GET['draft_type'])) {
    $draft_type = intval($_GET['draft_type']);
} else {
    $draft_type = 0;
}

if (isset($_GET['incl_max_lv'])) {
    $incl_max_lv = intval($_GET['incl_max_lv']);
} else {
    $incl_max_lv = 1;
}

if (isset($_GET['front_to_back'])) {
    $front_to_back = intval($_GET['front_to_back']);
    $layout = $front_to_back;
} else {
    $front_to_back = 1;
    $layout = 2;
}

if (isset($_GET['random'])) {
    $random = intval($_GET['random']);
} else {
    $random = 0;
}

if ($layout == 0) {
    $blind_front = 1;
    $blind_back = 0;
} else if ($layout == 1) {
    $blind_front = 0;
    $blind_back = 1;
} else {
    $blind_front = 0;
    $blind_back = 0;
}

$num_levels = connect_db_and_get_num_levels($key);

?>

<script>

var g_config_blind_front = <?php echo $blind_front; ?>;
var g_config_blind_back = <?php echo $blind_back; ?>;
var g_config_layout = <?php echo $layout; ?>;
var g_config_user_name = "<?php echo $user_name; ?>";
var g_config_key = "<?php echo $key; ?>";

var g_num_levels = <?php echo $num_levels; ?>;

var g_card_data = null;
var g_card_data_orig = null;
var g_card_index;

var g_front_to_back = <?php echo $front_to_back; ?>;
var g_front_visible = 0;
var g_back_visible = 0;

var g_result_table = null;
var g_diff_level_table = null; // 現状のresult(OK,NG)によるlevelの差分を示す．result訂正時に使用する
var g_current_status_ng_table = null;

const RESULT_NONE = -1;
const RESULT_NG = 0;
const RESULT_EVEN = 1;
const RESULT_OK = 2;

var g_page_lock = 0;

// 文字列中の改行コードを<br />に変換する
function nl2br(str) {
    return str.replace(/[\n\r]/g, "<br />");
}

// 指定レベルの四角に色をつける
function dispWordLevel(level, status_ng) {
<?php
    for ($i = 0; $i < $num_levels; $i++) {
        echo "    if (level == {$i}) {\n";
        echo "        if (status_ng) {";
        echo "            $(\"#lv{$i}\").css(\"background-color\", \"#BDBDBD\");\n";
        echo "        } else {";
        echo "            $(\"#lv{$i}\").css(\"background-color\", \"#FF9966\");\n";
        echo "        }";
        echo "    } else {\n";
        echo "        $(\"#lv{$i}\").css(\"background-color\", \"transparent\");\n";
        echo "    }\n";
    }
?>
}

// カードデータを初期化する（カードデータをグローバル変数に格納し，状態管理用配列を初期化する）
function setCardData(card_data) {
    if ( (card_data != null) && (card_data.length != null) && (card_data.length > 0) ) {
        g_card_data = card_data;
        g_result_table = new Array(card_data.length);
        g_diff_level_table = new Array(card_data.length);
        g_current_status_ng_table = new Array(card_data.length);
        //alert(card_data.length);
        for (var i=0 ; i<card_data.length ; i++) {
            g_result_table[i] = RESULT_NONE;
            g_diff_level_table[i] = 0;
            g_current_status_ng_table[i] = g_card_data[i].status_ng;
        }
    }
}

// 結果登録用のチェックボックスを更新する
function dispResult(result) {
    if (result == RESULT_OK) {
        $("#result-even").prop("checked", false).checkboxradio('refresh');
        $("#result-ng").prop("checked", false).checkboxradio('refresh');
        $("#result-ok").prop("checked", true).checkboxradio('refresh');
    } else if (result == RESULT_EVEN) {
        $("#result-ok").prop("checked", false).checkboxradio('refresh');
        $("#result-ng").prop("checked", false).checkboxradio('refresh');
        $("#result-even").prop("checked", true).checkboxradio('refresh');
    } else if (result == RESULT_NG) {
        $("#result-ok").prop("checked", false).checkboxradio('refresh');
        $("#result-even").prop("checked", false).checkboxradio('refresh');
        $("#result-ng").prop("checked", true).checkboxradio('refresh');
    } else {
        $("#result-ok").prop("checked", false).checkboxradio('refresh');
        $("#result-even").prop("checked", false).checkboxradio('refresh');
        $("#result-ng").prop("checked", false).checkboxradio('refresh');

    }
}

// 指定indexのカードデータを表示する
function setCardIndex(card_index) {
    if ( (card_index >= 0) && (g_card_data != null) && (g_card_data.length != null) &&
         (card_index < g_card_data.length) && (g_result_table != null) ) {

        g_card_index = card_index;

        if (g_config_blind_front == 0) {
            makeFrontVisible();
        } else {
            makeFrontUnvisible();
        }
        if (g_config_blind_back == 0) {
            makeBackVisible();
        } else {
            makeBackUnvisible();
        }
        $("#current_index").text("問" + (g_card_index+1) + " (全" + g_card_data.length + "問) →左スワイプで次問題");
        var text_header;
        if (g_card_data[g_card_index].text_header.length > 0) {
            text_header = nl2br(g_card_data[g_card_index].text_header) + ":<br />";
        } else {
            text_header = "";
        }
        var text_footer;
        if (g_card_data[g_card_index].text_footer.length > 0) {
            text_footer = "<br /><br />" + nl2br(g_card_data[g_card_index].text_footer);
        } else {
            text_footer = "";
        }
        var text_front = nl2br(g_card_data[g_card_index].text_front);
        var text_back = nl2br(g_card_data[g_card_index].text_back);
        if (g_config_layout == 0) {
            $("#front_side").html(text_front);
            $("#back_side").html(text_header + text_back + text_footer);
        } else if (g_config_layout == 1){
            $("#front_side").html(text_header + text_front + text_footer);
            $("#back_side").html(text_back);
        } else {
            $("#front_side").html(text_header + text_front);
            $("#back_side").html(text_back + text_footer);
        }
        dispWordLevel(g_card_data[card_index].level + g_diff_level_table[card_index], g_current_status_ng_table[card_index]);
        dispResult(g_result_table[card_index]);
        //alert(g_result_table[g_card_index]);

    }
}

function IncrementCardIndex() {
    if ( (g_card_index != null) && (g_card_data != null) && (g_card_data.length != null) ) {
        var card_index = (g_card_index + 1) % g_card_data.length;
        setCardIndex(card_index);
    }
}

function DecrementCardIndex() {
    if ( (g_card_index != null) && (g_card_data != null) && (g_card_data.length != null) ) {
        var card_index = (g_card_index + g_card_data.length - 1) % g_card_data.length;
        setCardIndex(card_index);
    }
}

function makeFrontVisible() {
    g_front_visible = 1;
    $("#front_side").css('color', 'black');
}

function makeFrontUnvisible() {
    if (g_config_blind_front == 1){
        g_front_visible = 0;
        $("#front_side").css('color', 'wheat');
    }
}

function makeFrontInvert() {
    if (g_front_visible == 1){
        makeFrontUnvisible();
    } else {
        makeFrontVisible();
    }
}

function makeBackVisible() {
    g_back_visible = 1;
    $("#back_side").css('color', 'black');
}

function makeBackUnvisible() {
    if (g_config_blind_back == 1){
        g_back_visible = 0;
        $("#back_side").css('color', 'wheat');
    }
}

function makeBackInvert() {
    if (g_back_visible == 1){
        makeBackUnvisible();
    } else {
        makeBackVisible();
    }
}

function updateCardStatus(card_index, level, status_ng, diff_ok, diff_even, diff_ng, force_expiration, post_func) {
    if (g_card_data == null) {
        return;
    }
    $("input[type='radio']").checkboxradio('disable');
    var param = {};
    param['user_name'] = g_config_user_name;
    param['key'] = g_config_key;
    param['id'] = g_card_data[card_index].id;
    param['front_to_back'] = g_front_to_back;
    param['inc_ok'] = diff_ok;
    param['inc_even'] = diff_even;
    param['inc_ng'] = diff_ng;
    param['force_expiration'] = force_expiration;
    param['level'] = level;
    param['status_ng'] = status_ng;
    //alert(param['level'] + " " + param['status_ng']);
    g_page_lock = 1;
    $.ajax({
        url: "db_update_status.php",
        data: param,
        dataType: "json",
        type: "POST",
        error: function(XMLHttpRequest, textStatus, errorThrown){
            $("input[type='radio']").checkboxradio('enable');
            dispResult(g_result_table[card_index]);
            g_page_lock = 0;
            //alert("old result: " + g_result_table[card_index]);
            alert(textStatus + ", " + errorThrown);
        },
        success: function(data, status, xhr) {
            $("input[type='radio']").checkboxradio('enable');
            g_page_lock = 0;
            if (post_func != null) {
                post_func();
            }
            dispWordLevel(param['level'], param['status_ng']);
            //alert("Level = " + param['level'] + ", status_ng= " +  param['status_ng']);
        }
    });
}

function procChangeResult(card_index, result){
    var current_result = g_result_table[card_index];
    var new_result = result;
    if ((result != current_result) && (result != RESULT_NONE)) {
        var diff_ok = 0;
        var diff_even = 0;
        var diff_ng = 0;
        var force_expiration = 0;
        var diff_level = 0;
        var status_ng = g_current_status_ng_table[card_index];
        if (current_result == RESULT_NG) {
            diff_ng -= 1;
        } else if (current_result == RESULT_EVEN) {
            diff_even -= 1;
        } else if (current_result == RESULT_OK) {
            diff_ok -= 1;
        }
        if (result == RESULT_NG) {
            diff_ng += 1;
            if (g_card_data[card_index].level > 0) {
                diff_level = -1;
            }
            level = g_card_data[card_index].level + diff_level;
            status_ng = 1;
            force_expiration = 1;
        } else if (result == RESULT_EVEN) {
            diff_even += 1;
            diff_level = 0;
            level = g_card_data[card_index].level + diff_level;
            status_ng = 1;
            force_expiration = 1;
        } else if (result == RESULT_OK) {
            diff_ok += 1;
            if ( (g_card_data[card_index].status_ng == 0) && (g_card_data[card_index].level < g_num_levels - 1)){
                diff_level = 1;
            }
            level = g_card_data[card_index].level + diff_level;
            status_ng = 0;
            force_expiration = 0;
        }
        updateCardStatus(card_index, level, status_ng, diff_ok, diff_even, diff_ng, force_expiration, function(){
            g_diff_level_table[card_index] = diff_level;
            g_current_status_ng_table[card_index] = status_ng;
            g_result_table[card_index] = new_result;
        });
    }
}

$(document).ready(function(){

    $("#front_side").bind("tap", function(){
        makeFrontInvert();
    });
    $("#back_side").bind("tap", function(){
        makeBackInvert();
    });
    $("#main_page").bind("swipeleft", function(){
        if (g_page_lock == 0){
            IncrementCardIndex();
        }
    });
    $("#main_page").bind("swiperight", function(){
        if (g_page_lock == 0){
            DecrementCardIndex();
        }
    });
    $("#result-ng").bind('click', function(){
        procChangeResult(g_card_index,RESULT_NG);
    });
    $("#result-even").bind('click', function(){
        procChangeResult(g_card_index,RESULT_EVEN);
    });
    $("#result-ok").bind('click', function(){
        procChangeResult(g_card_index,RESULT_OK);
    });

    $("#lv-up-btn").bind('click', function(){
        if (g_card_data[g_card_index].level + g_diff_level_table[g_card_index] < g_num_levels - 1){
            updateCardStatus(g_card_index, g_card_data[g_card_index].level + g_diff_level_table[g_card_index] + 1, g_card_data[g_card_index].status_ng,0,0,0,0,function(){
                g_card_data[g_card_index].level += 1;
            });
        }
    });

    $("#lv-down-btn").bind('click', function(){
        if (g_card_data[g_card_index].level + g_diff_level_table[g_card_index] > 0){
            updateCardStatus(g_card_index, g_card_data[g_card_index].level + g_diff_level_table[g_card_index] - 1, g_card_data[g_card_index].status_ng,0,0,0,0,function(){
                g_card_data[g_card_index].level -= 1;
            });
        }
    });

    $("#modify_word-btn").bind('click', function(){
        if ( (g_card_index != null) && (g_card_data != null) && (g_card_data.length != null) ) {
            //alert("url=" + "./register_one_word.php?id=" + g_card_data[g_card_index].id);
            location.href = "./register_one_word.php?user_name=<?php echo $user_name; ?>&key=<?php echo $key; ?>&id=" + g_card_data[g_card_index].id;
        }
    });

    makeFrontVisible();
    makeBackVisible();

    var param = {};
    param['user_name'] = g_config_user_name;
    param['key'] = g_config_key;
    param['front_to_back'] = g_config_blind_back;
    param['start_category'] = <?php echo $start_category; ?>;
    param['end_category'] = <?php echo $end_category; ?>;
    param['start_date'] = "<?php echo $start_date; ?>";
    param['end_date'] = "<?php echo $end_date; ?>";
    param['retrieval_type'] = <?php echo $retrieval_type; ?>;
    param['draft_type'] = <?php echo $draft_type; ?>;
    param['incl_max_lv'] = "<?php echo $incl_max_lv; ?>";
    param['random'] = <?php echo $random; ?>;
    $.post("db_retrieval.php", param, function(returnValue){
        setCardData(returnValue);
        if( (returnValue.length != null) && (returnValue.length != 0) ){
            setCardIndex(0);
        } else {
            alert('対象の項目はありません');
        }
        //alert(returnValue.length);
    },"json");

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
  <h1>Flash Cards</h1>
</div>

<!-- ********** Content ******************** -->
<div data-role="content">

  <p><div id="current_index"></div></p>
  <p><div style="background-color: wheat; padding: 20px; text-shadow:none" id="front_side">データ取得中</div></p>
  <table style="border: 1px solid #b4b4b4; padding: 0;">
    <tr>
<?php
        for ($i = 0; $i < $num_levels; $i++) {
            $lv_display = $i + 1;
            echo "        <td id=\"lv{$i}\" style=\"border: 1px solid #b4b4b4; padding: 10px 10px 10px 10px;\">{$lv_display}</td>\n";
        }
?>
    </tr>
  </table>
  <fieldset data-role="controlgroup" data-type="horizontal" data-role="fieldcontain" style="text-align:right;" id="test_result">
    <input type="radio" name="result" id="result-ng">
    <label for="result-ng">ＮＧ</label>
    <input type="radio" name="result" id="result-even">
    <label for="result-even">もう一回</label>
    <input type="radio" name="result" id="result-ok">
    <label for="result-ok">ＯＫ</label>
  </fieldset>

  <p><div style="background-color: wheat; padding: 20px; text-shadow:none" id="back_side">データ取得中</div></p>
  <div data-role="controlgroup" data-type="horizontal" data-mini="true">
    <a href="#" data-role="button" id="lv-down-btn" data-icon="arrow-d">Lv Down</a>
    <a href="#" data-role="button" id="lv-up-btn" data-icon="arrow-u">Lv Up</a>
  </div>
  <br /><br /><br /><a href="#" data-role="button" id="modify_word-btn">データを修正する</a>

</div>

<!-- ********** Footer ********************* -->
<div data-role="footer">
  <a href="javascript:window.close();">Close</a>
  <h1>Flash Cards</h1>
</div>

</div>


</body>
</html>



