<?php
/**
 * お問い合わせフォーム送信処理 (Lolipopサーバー用)
 */

// -------------------------------------------------------------------------
// 設定
// -------------------------------------------------------------------------

// 通知先メールアドレス (園側)
$to_nursery = "info@suku2.jp"; // ここを実際のメールアドレスに変更してください

// メールの件名
$subject_nursery = "【瀬谷すくすく保育園】ホームページからのお問い合わせ";
$subject_user = "【瀬谷すくすく保育園】お問い合わせありがとうございます";

// 送信元メールアドレス
$from_email = "no-reply@suku2.jp";
$from_name = "瀬谷すくすく保育園";

// -------------------------------------------------------------------------
// フォームデータの取得
// -------------------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = htmlspecialchars($_POST["category"], ENT_QUOTES, 'UTF-8');
    $name     = htmlspecialchars($_POST["name"], ENT_QUOTES, 'UTF-8');
    $email    = htmlspecialchars($_POST["email"], ENT_QUOTES, 'UTF-8');
    $phone    = htmlspecialchars($_POST["phone"], ENT_QUOTES, 'UTF-8');
    $message  = htmlspecialchars($_POST["message"], ENT_QUOTES, 'UTF-8');

    // 文字コード設定
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");

    // ヘッダー
    $headers = "From: " . mb_encode_header_name($from_name) . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // -------------------------------------------------------------------------
    // 1. 園側への通知メール
    // -------------------------------------------------------------------------
    $body_nursery = "ホームページからお問い合わせがありました。\n\n";
    $body_nursery .= "【お問い合わせ種類】: " . $category . "\n";
    $body_nursery .= "【お名前】: " . $name . "\n";
    $body_nursery .= "【メールアドレス】: " . $email . "\n";
    $body_nursery .= "【電話番号】: " . $phone . "\n\n";
    $body_nursery .= "【お問い合わせ内容】:\n" . $message . "\n";

    $sent_nursery = mb_send_mail($to_nursery, $subject_nursery, $body_nursery, $headers);

    // -------------------------------------------------------------------------
    // 2. ユーザーへの自動返信メール
    // -------------------------------------------------------------------------
    $headers_user = "From: " . mb_encode_header_name($from_name) . " <" . $from_email . ">\r\n";
    $headers_user .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $body_user = $name . " 様\n\n";
    $body_user .= "お問い合わせありがとうございます。瀬谷すくすく保育園です。\n";
    $body_user .= "以下の内容で承りました。内容を確認の上、担当者より折り返しご連絡いたします。\n\n";
    $body_user .= "----------\n";
    $body_user .= "【お問い合わせ種類】: " . $category . "\n";
    $body_user .= "【お名前】: " . $name . "\n";
    $body_user .= "【メールアドレス】: " . $email . "\n";
    $body_user .= "【電話番号】: " . $phone . "\n\n";
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

/**
 * 送信元の名前を正しくエンコードする
 */
function mb_encode_header_name($name) {
    return mb_encode_mimeheader($name, "UTF-8");
}
?>
