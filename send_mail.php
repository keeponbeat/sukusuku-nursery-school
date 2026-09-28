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
$child_months_max = 72;

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

// -------------------------------------------------------------------------
// フォームデータの取得
// -------------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_raw = isset($_POST["category"]) && is_string($_POST["category"]) ? trim($_POST["category"]) : "";
    $phone_raw = isset($_POST["phone"]) && is_string($_POST["phone"]) ? trim($_POST["phone"]) : "";
    $category = htmlspecialchars($category_raw, ENT_QUOTES, 'UTF-8');
    $name     = htmlspecialchars($_POST["name"], ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($_POST["email"], ENT_QUOTES, 'UTF-8');
    $phone    = htmlspecialchars($phone_raw, ENT_QUOTES, 'UTF-8');
    $message  = htmlspecialchars($_POST["message"], ENT_QUOTES, 'UTF-8');

    $is_admission = ($category_raw === $admission_category);
    $child_months = null;
    if ($is_admission) {
        if ($phone_raw === "") {
            inquiry_input_error("入園・見学についてのお問い合わせでは、電話番号を入力してください。");
        }

        $child_months_raw = isset($_POST["child_months"]) && is_string($_POST["child_months"]) ? trim($_POST["child_months"]) : "";
        if (!preg_match('/^\d{1,3}$/', $child_months_raw)) {
            inquiry_input_error("お子様の月齢は0〜" . $child_months_max . "の整数で入力してください。");
        }
        $child_months = (int)$child_months_raw;
        if ($child_months > $child_months_max) {
            inquiry_input_error("お子様の月齢は0〜" . $child_months_max . "の整数で入力してください。");
        }
    }

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

    // ヘッダー
    $headers = "From: " . mb_encode_mimeheader($from_name, "UTF-8") . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
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
    // -------------------------------------------------------------------------
    $headers_user = "From: " . mb_encode_mimeheader($from_name, "UTF-8") . " <" . $from_email . ">\r\n";
    $headers_user .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers_user .= "Content-Transfer-Encoding: 8bit\r\n";

    $body_user = $name . " 様\n\n";
    $body_user .= "お問い合わせありがとうございます。瀬谷すくすく保育園です。\n";
    $body_user .= "以下の内容で承りました。内容を確認の上、担当者より折り返しご連絡いたします。\n\n";
    $body_user .= "----------\n";
    $body_user .= "【お問い合わせ種類】: " . $category . "\n";
    $body_user .= "【お名前】: " . $name . "\n";
    $body_user .= "【メールアドレス】: " . $email . "\n";
    $body_user .= "【電話番号】: " . $phone . "\n";
    if ($is_admission) {
        $body_user .= "【お子様の月齢】: " . $child_months . "ヶ月\n";
    }
    $body_user .= "\n";
    $body_user .= "【お問い合わせ内容】:\n" . $message . "\n";
    $body_user .= "----------\n\n";
    $body_user .= "※本メールは自動送信されています。\n";
    $body_user .= "もし心当たりがない場合は、お手数ですが破棄してください。\n\n";
    $body_user .= "----------------------------\n";
    $body_user .= "瀬谷すくすく保育園\n";
    $body_user .= "〒246-0031 神奈川県横浜市瀬谷区瀬谷三丁目１９－２\n";
    $body_user .= "TEL: 045-442-7123\n";
    $body_user .= "URL: https://suku2.jp\n";
    $body_user .= "----------------------------\n";

    mb_send_mail($email, $subject_user, $body_user, $headers_user);

    // 完了ページへリダイレクト
    header("Location: thanks.html");
    exit;
} else {
    // POST以外はトップへ
    header("Location: index.html");
    exit;
}

?>
