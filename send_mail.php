<?php
/**
 * お問い合わせフォーム送信処理 (Lolipopサーバー用)
 */

// 文字コード設定（処理の最初に行う）
mb_internal_encoding("UTF-8");
mb_language("neutral");

// -------------------------------------------------------------------------
// 設定
// -------------------------------------------------------------------------

// 通知先メールアドレス (園側)
$to_nursery = "toiawase@suku2.jp"; // ここを実際のメールアドレスに変更してください

// メールの件名
$subject_nursery = "【瀬谷すくすく保育園】ホームページからのお問い合わせ";
$subject_user = "【瀬谷すくすく保育園】お問い合わせありがとうございます";

// 送信元メールアドレス
$from_email = "no-reply@suku2.jp";
$from_name = "瀬谷すくすく保育園";

// フォーム select の value。画面上の表示は「入園・見学について」
$admission_category = "見学について";
$category_values = array("見学について", "採用について", "その他");
$child_months_max = 24;
$name_max_length = 50;

function inquiry_input_error($message) {
    http_response_code(400);
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"ja\">\n<head>\n<meta charset=\"UTF-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    echo "<title>入力エラー | 瀬谷すくすく保育園</title>\n";
    echo "<link rel=\"stylesheet\" href=\"css/styles.css\">\n</head>\n<body>\n";
    echo "<div class=\"container\"><main class=\"page-content pt-top\">\n";
    echo "<h2 class=\"section-title\">入力内容のご確認</h2>\n";
    echo "<section class=\"card\"><p>" . $safe . "</p>\n";
    echo "<p><a href=\"contact.html\">お問い合わせフォームに戻る</a></p></section>\n";
    echo "</main></div>\n</body>\n</html>\n";
    exit;
}

function inquiry_post_string($key) {
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return "";
    }
    return $_POST[$key];
}

function inquiry_scrub($value) {
    if (!is_string($value)) {
        return "";
    }
    if (function_exists("mb_scrub")) {
        return mb_scrub($value, "UTF-8");
    }
    return $value;
}

// 1行の項目から、ヘッダーや本文を壊す制御文字を除く
function inquiry_single_line($value) {
    $value = inquiry_scrub($value);
    $stripped = preg_replace('/[\x00-\x1F\x7F\x{2028}\x{2029}]/u', '', $value);
    if (!is_string($stripped)) {
        return "";
    }
    return trim($stripped);
}

// お問い合わせ本文。改行は残し、NULL など本文を途中で切る文字だけ除く
function inquiry_message_text($value) {
    $value = inquiry_scrub($value);
    $stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{2028}\x{2029}]/u', '', $value);
    if (!is_string($stripped)) {
        return "";
    }
    return $stripped;
}

function inquiry_is_valid_email($email) {
    if (!is_string($email) || $email === "" || strlen($email) > 254) {
        return false;
    }
    // CR/LF/NUL・空白・制御文字。$ ではなく \z で末尾改行のすり抜けも防ぐ
    if (preg_match('/[\x00-\x20\x7F]/', $email)) {
        return false;
    }
    if (preg_match('/%0[ad]/i', $email)) {
        return false;
    }
    // ヘッダーを分割・追加できる文字、および引用符付きアドレスは受け付けない
    if (preg_match('/[<>\"\\\\,;:()\[\]{}]/', $email)) {
        return false;
    }
    if (!preg_match('/\A[A-Za-z0-9._%+\-\']+@[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?)+\z/', $email)) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function inquiry_require_email($raw) {
    if (!is_string($raw)) {
        inquiry_input_error("正しいメールアドレスの形式を入力してください。");
    }
    $email = trim($raw);
    if ($email === "") {
        inquiry_input_error("メールアドレスを入力してください。");
    }
    if (!inquiry_is_valid_email($email)) {
        inquiry_input_error("正しいメールアドレスの形式を入力してください。");
    }
    return $email;
}

// 自動返信の宛名に使う。改行やURL・宣伝文は受け付けない
function inquiry_require_name($raw) {
    global $name_max_length;
    if (!is_string($raw) || !mb_check_encoding($raw, "UTF-8")) {
        inquiry_input_error("お名前を入力してください。");
    }
    if (preg_match('/[\x00-\x1F\x7F\x{2028}\x{2029}]/u', $raw)) {
        inquiry_input_error("お名前は改行を含まない" . $name_max_length . "文字以内で入力してください。");
    }
    $name = trim($raw);
    if ($name === "") {
        inquiry_input_error("お名前を入力してください。");
    }
    if (mb_strlen($name, "UTF-8") > $name_max_length) {
        inquiry_input_error("お名前は" . $name_max_length . "文字以内で入力してください。");
    }
    if (inquiry_name_is_promo($name)) {
        inquiry_input_error("お名前にURLは入力できません。");
    }
    return $name;
}

function inquiry_name_is_promo($name) {
    $normalized = mb_convert_kana($name, "as", "UTF-8");
    $normalized = strtr($normalized, array(
        "．" => ".",
        "。" => ".",
        "：" => ":",
        "／" => "/",
        "＼" => "\\",
        "＠" => "@",
    ));
    $folded = mb_strtolower($normalized, "UTF-8");
    $compact = preg_replace('/\s+/u', "", $folded);
    if (!is_string($compact)) {
        return true;
    }
    if (mb_strpos($folded, "http", 0, "UTF-8") !== false || mb_strpos($compact, "http", 0, "UTF-8") !== false) {
        return true;
    }
    if (mb_strpos($folded, "www", 0, "UTF-8") !== false || mb_strpos($compact, "www", 0, "UTF-8") !== false) {
        return true;
    }
    if (strpos($compact, "://") !== false) {
        return true;
    }
    if (preg_match('/(?:[a-z0-9](?:[a-z0-9-]{0,62}[a-z0-9])?\.)+(?:com|net|org|jp|info|biz|xyz|top|online|site|link|click|ru|cn|uk|io|me|co|tv|app|dev|shop|email|page|cc|ly|gl|be|to|us|eu|de|fr|kr|tw|hk|sg|edu|gov|ne|or|ac|go)\b/i', $compact)) {
        return true;
    }
    return false;
}

// 画面に出さない罠欄。空以外（配列を含む）はスパムと同じ扱いにする
function inquiry_honeypot_filled() {
    if (!isset($_POST["website"])) {
        return false;
    }
    if (!is_string($_POST["website"])) {
        return true;
    }
    return $_POST["website"] !== "";
}

// -------------------------------------------------------------------------
// フォームデータの取得
// -------------------------------------------------------------------------

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    if (inquiry_honeypot_filled()) {
        header("Location: thanks.html");
        exit;
    }

    $category_raw = inquiry_single_line(inquiry_post_string("category"));
    if (!in_array($category_raw, $category_values, true)) {
        inquiry_input_error("お問い合わせの種類を選択してください。");
    }
    $phone_raw = inquiry_single_line(inquiry_post_string("phone"));
    $name_raw = inquiry_require_name(inquiry_post_string("name"));
    $message_raw = inquiry_message_text(inquiry_post_string("message"));

    $is_admission = ($category_raw === $admission_category);
    $child_months = null;
    if ($is_admission) {
        if ($phone_raw === "") {
            inquiry_input_error("入園・見学についてのお問い合わせでは、電話番号を入力してください。");
        }

        $child_months_raw = isset($_POST["child_months"]) && is_string($_POST["child_months"]) ? trim($_POST["child_months"]) : "";
        if (!preg_match('/^\d{1,3}$/', $child_months_raw)) {
            inquiry_input_error("お子様の月齢は0〜" . $child_months_max . "の整数（満2歳まで）で入力してください。");
        }
        $child_months = (int)$child_months_raw;
        if ($child_months > $child_months_max) {
            inquiry_input_error("お子様の月齢は0〜" . $child_months_max . "の整数（満2歳まで）で入力してください。");
        }
    }

    $email_raw = inquiry_require_email(inquiry_post_string("email"));
    if (trim(inquiry_post_string("email_confirm")) !== $email_raw) {
        inquiry_input_error("メールアドレスが一致しません。");
    }

    $category = htmlspecialchars($category_raw, ENT_QUOTES, 'UTF-8');
    $name     = htmlspecialchars($name_raw, ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($email_raw, ENT_QUOTES, 'UTF-8');
    $phone    = htmlspecialchars($phone_raw, ENT_QUOTES, 'UTF-8');
    $message  = htmlspecialchars($message_raw, ENT_QUOTES, 'UTF-8');

    // -------------------------------------------------------------------------
    // スパム判定
    // -------------------------------------------------------------------------
    $is_spam = false;

    // 1. 名前が特定のスパム名の場合
    if ($name === 'RobertsaurL') {
        $is_spam = true;
    }

    // 2. メッセージが全て半角英数字・記号のみの場合（日本語が含まれない）
    // ※正規の問い合わせであれば通常は日本語が含まれるため。
    if (!empty($message) && preg_match('/^[!-~ \s\r\n]+$/', $message)) {
        $is_spam = true;
    }

    if ($is_spam) {
        // スパム判定時は送信処理を行わずに完了ページへリダイレクト（スパム業者に成功したと思わせるため）
        header("Location: thanks.html");
        exit;
    }

    // ヘッダー。Reply-To には検証済みのアドレスだけを入れる
    $headers = "From: " . mb_encode_mimeheader($from_name, "UTF-8") . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $email_raw . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";

    // -------------------------------------------------------------------------
    // 1. 園側への通知メール
    // -------------------------------------------------------------------------
    $body_nursery = "ホームページからお問い合わせがありました。\n\n";
    $body_nursery .= "【お問い合わせ種類】: " . $category . "\n";
    $body_nursery .= "【お名前】: " . $name . "\n";
    $body_nursery .= "【メールアドレス】: " . $email . "\n";
    $body_nursery .= "【電話番号】: " . $phone . "\n";
    if ($is_admission) {
        $body_nursery .= "【お子様の月齢】: " . $child_months . "ヶ月\n";
    }
    $body_nursery .= "\n";
    $body_nursery .= "【お問い合わせ内容】:\n" . $message . "\n";

    $sent_nursery = mb_send_mail($to_nursery, $subject_nursery, $body_nursery, $headers);

    // -------------------------------------------------------------------------
    // 2. ユーザーへの自動返信メール
    // 宛名だけ名前を使う。お問い合わせ内容は載せない
    // -------------------------------------------------------------------------
    $headers_user = "From: " . mb_encode_mimeheader($from_name, "UTF-8") . " <" . $from_email . ">\r\n";
    $headers_user .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers_user .= "Content-Transfer-Encoding: 8bit\r\n";

    $body_user = $name . " 様\n\n";
    $body_user .= "お問い合わせありがとうございます。瀬谷すくすく保育園です。\n";
    $body_user .= "内容を確認の上、担当者より折り返しご連絡いたします。\n\n";
    $body_user .= "※本メールは自動送信されています。\n";
    $body_user .= "もし心当たりがない場合は、お手数ですが破棄してください。\n\n";
    $body_user .= "----------------------------\n";
    $body_user .= "瀬谷すくすく保育園\n";
    $body_user .= "〒246-0031 神奈川県横浜市瀬谷区瀬谷三丁目１９－２\n";
    $body_user .= "TEL: 045-442-7123\n";
    $body_user .= "URL: https://suku2.jp\n";
    $body_user .= "----------------------------\n";

    mb_send_mail($email_raw, $subject_user, $body_user, $headers_user);

    // 完了ページへリダイレクト
    header("Location: thanks.html");
    exit;
} else {
    // POST以外はトップへ
    header("Location: index.html");
    exit;
}

?>
