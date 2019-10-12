<?php

session_start();

// DBの設定
$db = ['host' => 'localhost', 'user' => 'root', 'password' => 'root',
    'dbname' => 'password_spray'];

$errorMessage = '';
if (isset($_POST['email']) && isset($_POST['password'])) {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        $ret = false;
        $dsn = sprintf('mysql: host=%s; dbname=%s;', $db['host'], $db['dbname']);
        try {

            $pdo = new PDO($dsn, $db['user'], $db['password'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

            $stmt = $pdo->prepare('SELECT id,password,failed_count FROM users WHERE email=?');
            $stmt->execute([trim($_POST['email'])]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $now = date('Y-m-d H:i:s');
            if (empty($row)) {
                // 認証失敗 該当のメールアドレスを持っているユーザがいないケース
                $errorMessage = 'ユーザIDが間違っています';
            } else {

                if (passwordVerify($_POST['password'], $row['password'])) {
                    // 認証成功
                    $ret = true;

                    // 失敗回数を0に戻す
                    $stmt = $pdo->prepare('UPDATE users SET failed_count=0 where id=?');
                    $stmt->execute([$row['id']]);
                } else {
                    // 認証失敗　パスワードが間違っているケース
                    $stmt = $pdo->prepare('UPDATE users SET failed_count=? where id=?');
                    $stmt->execute([$row['failed_count'] + 1, $row['id']]);

                    $errorMessage = 'パスワードが間違っています';
                }
            }

            // 認証ログに書き込む
            $stmt = $pdo->prepare('INSERT INTO authentication_histories(email, status, ipaddress) VALUES(?, ?, ?)');
            $stmt->execute([$_POST['email'], $ret ? 1 : 0, $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            $errorMessage = '予期せぬエラーが発生しました';
            error_log($e->getMessage());
            $ret = false;
        }

        if ($ret) {
            header('Location: /mypage.php');
            exit;
        }
    }
    else {
        $errorMessage = 'メールアドレスとパスワードを入力してください';
    }
}

/**
 * 認証の関数　※いじらないでください
 * @param $input_password
 * @param $hash_password
 * @return bool
 */
function passwordVerify($input_password, $hash_password) {

    $tmp = explode('$', $hash_password);
    $pepper = hash( 'sha256', 'kagosec' );
    $pass = hash( 'sha256', $pepper . $input_password . $tmp[1] );
    if ($pass === $tmp[2]) {
        return true;
    }
    return false;
}
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ログイン</title>
</head>
<body>
    <?php if (!empty($errorMessage)): ?>
        <p style="color:red;"><strong><?php echo $errorMessage;?></strong></p>
    <?php endif ?>
    <form action="" method="post">
        ユーザID：<input type="email" name="email" value=""><br>
        パスワード：<input type="password" name="password" value=""><br>
        <input type="submit" value="ログイン">
    </form>
</body>
</html>


