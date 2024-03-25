<?php
session_start();

function generateRSAKeys() {
  $config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
  );
  $res = openssl_pkey_new($config);
  if ($res === false) {
    $error = openssl_error_string();
    die("Failed to generate a new private key");
  }
  openssl_pkey_export($res, $privateKey);
  $publicKey = openssl_pkey_get_details($res);
  if ($publicKey === false) {
    die("Failed to get public key details");
  }
  $publicKey = $publicKey["key"];
  return array($publicKey, $privateKey);
}

function encryptText($text, $publicKey) {
  openssl_public_encrypt($text, $encryptedText, $publicKey);
  return base64_encode($encryptedText);
}

function decryptText($encryptedText, $privateKey) {
  openssl_private_decrypt(base64_decode($encryptedText), $decryptedText, $privateKey);
  return $decryptedText;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $text = $_POST['text'];
  $action = $_POST['action'];

  if ($action === 'encrypt') {
    list($publicKey, $privateKey) = generateRSAKeys();
    $encryptedText = encryptText($text, $publicKey);

    // 公開鍵と秘密鍵をPEM形式で保存
    $timestamp = time();
    $publicKeyFile = "public_key_$timestamp.pem";
    $privateKeyFile = "private_key_$timestamp.pem";
    file_put_contents($publicKeyFile, $publicKey);
    file_put_contents($privateKeyFile, $privateKey);

    // 暗号化されたテキストをTXTファイルに保存
    $encryptedTextFile = "encrypted_text_$timestamp.txt";
    file_put_contents($encryptedTextFile, $encryptedText);

    // ファイル名をセッションに保存
    $_SESSION['publicKeyFile'] = $publicKeyFile;
    $_SESSION['privateKeyFile'] = $privateKeyFile;
    $_SESSION['encryptedTextFile'] = $encryptedTextFile;

    // 暗号化の回数をカウント
    $_SESSION['encryptionCount'] = isset($_SESSION['encryptionCount']) ? $_SESSION['encryptionCount'] + 1 : 1;

   // POSTリクエストの処理が完了したので、自身にリダイレクト
   header("Location: " . $_SERVER['PHP_SELF'] . "?encrypted=true");
   exit;
 } elseif ($action === 'decrypt') {
   // 秘密鍵と公開鍵の読み込み（この例ではダミーの値を使用）
   $publicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0B...
-----END PUBLIC KEY-----';
   $privateKey = '-----BEGIN PRIVATE KEY-----
MIIJKAIBAAKCAgEA...
-----END PRIVATE KEY-----';

   $decryptedText = decryptText($text, $privateKey);
 }
} else {
  // リロードが行われた場合にセッションをクリア
  if (isset($_SESSION['encrypted'])) {
    session_unset();
    unset($_SESSION['encrypted']);
  } else if (!isset($_GET['encrypted'])) {
    $_SESSION['encrypted'] = true;
    header("Location: " . $_SERVER['PHP_SELF'] . "?encrypted=true");
    exit;
  }
}
 

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>暗号化・復号化ツール</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    textarea {
      height: 150px;
    }
  </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <form action="" method="post" class="bg-white p-6 rounded shadow-md">
    <textarea name="text" placeholder="文章を入力してください" class="w-full p-2 border border-gray-300 rounded mb-4"></textarea>
    <div class="flex items-center mb-4">
      <input type="radio" name="action" value="encrypt" checked class="mr-2"> 暗号化
      <input type="radio" name="action" value="decrypt" class="ml-4 mr-2"> 復号化
    </div>
    <input type="submit" value="実行" class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mb-4">
    <?php if (!empty($_SESSION['publicKeyFile']) && !empty($_SESSION['privateKeyFile']) && !empty($_SESSION['encryptedTextFile'])): ?>
      <div>
      <p><?php echo $_SESSION['encryptionCount']; ?>回目の暗号化が完了しました。</p>
        <a href="<?php echo $_SESSION['publicKeyFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">公開鍵をダウンロード</a>
        <a href="<?php echo $_SESSION['privateKeyFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">秘密鍵をダウンロード</a>
        <a href="<?php echo $_SESSION['encryptedTextFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">暗号化されたテキストをダウンロード</a>
      </div>
    <?php endif; ?>
  </form>
</body>
</html>