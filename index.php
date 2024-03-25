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

    // POSTリクエストの処理が完了したので、自身にリダイレクト
    if (isset($_SESSION['processing']) && $_SESSION['processing']) {
      header("Location: " . $_SERVER['PHP_SELF'] . "?encrypted=true");
      exit;
    }

    // リクエストの処理を開始
    $_SESSION['processing'] = true;

  

    // 公開鍵と秘密鍵をPEM形式で保存
    $timestamp = time();
    $publicKeyFile = "public/public_key_$timestamp.pem";
    $privateKeyFile = "private/private_key$timestamp.pem";
    file_put_contents($publicKeyFile, $publicKey);
    file_put_contents($privateKeyFile, $privateKey);

        // ディレクトリが存在しない場合は作成
  if (!file_exists('public')) {
    mkdir('public', 0777, true);
  }
  if (!file_exists('private')) {
    mkdir('private', 0777, true);
  }

   

    // 暗号化されたテキストをTXTファイルに保存
    $encryptedTextFile = "txt/encrypted_text_$timestamp.txt";

    // ディレクトリが存在しない場合は作成
  if (!file_exists('txt')) {
    mkdir('txt', 0777, true);
  }

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
$files = array($_FILES['file1'], $_FILES['file2'], $_FILES['file3']);
$publicKeyFile = null;
$privateKeyFile = null;
$encryptedTextFile = null;

foreach ($files as $file) {
  $content = file_get_contents($file['tmp_name']);
  if (strpos($content, '-----BEGIN PRIVATE KEY-----') === 0) {
    $privateKeyFile = $file;
  } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) === 'txt') {
    $encryptedTextFile = $file;
  } else {
    $publicKeyFile = $file;
  }
}

if ($publicKeyFile === null || $privateKeyFile === null || $encryptedTextFile === null) {
  $_SESSION['errorMessage'] = '必要なファイルがアップロードされていません。公開鍵、秘密鍵、暗号化されたテキストのファイルをアップロードしてください。';
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// ファイルから公開鍵、秘密鍵、暗号化されたテキストを読み込む
$publicKey = file_get_contents($publicKeyFile['tmp_name']);
$privateKey = file_get_contents($privateKeyFile['tmp_name']);
$encryptedText = file_get_contents($encryptedTextFile['tmp_name']);;

  // ファイル形式をチェック
  $allowedExtensions = array('pem', 'txt');
  if (!in_array(pathinfo($publicKeyFile['name'], PATHINFO_EXTENSION), $allowedExtensions) ||
      !in_array(pathinfo($privateKeyFile['name'], PATHINFO_EXTENSION), $allowedExtensions) ||
      !in_array(pathinfo($encryptedTextFile['name'], PATHINFO_EXTENSION), $allowedExtensions)) {
    $_SESSION['errorMessage'] = '使用できないファイル形式です。.pemまたは.txtファイルのみアップロードしてください。';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }

  
    // 復号化
    $decryptedText = decryptText($encryptedText, $privateKey);

    // 復号化後のメッセージをセッションに保存
    $_SESSION['decryptedText'] = $decryptedText;

    // POSTリクエストの処理が完了したので、自身にリダイレクト
    header("Location: " . $_SERVER['PHP_SELF'] . "?decrypted=true");
    exit;
  }

    
 // POSTリクエストの処理が完了したので、processingをリセット
 unset($_SESSION['processing']);

} else {
  // リロードが行われた場合にセッションをクリア
  if (isset($_GET['encrypted']) && empty($_POST)) {
    if (isset($_SESSION['reloaded'])) {
      session_unset();
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    } else {
      $_SESSION['reloaded'] = true;
    }
  } elseif (isset($_GET['decrypted']) && empty($_POST)) {
    if (isset($_SESSION['reloaded'])) {
      session_unset();
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    } else {
      $_SESSION['reloaded'] = true;
    }
  } else {
    unset($_SESSION['reloaded']);
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
<?php if (isset($_SESSION['errorMessage'])): ?>
  <div class="bg-red-600 text-white p-6 rounded shadow-md">
    <p><?php echo $_SESSION['errorMessage']; ?></p>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="block w-full py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 mt-2">元のページに戻る</a>
  </div>
<?php endif; ?>
<?php if (!empty($_SESSION['publicKeyFile']) && !empty($_SESSION['privateKeyFile']) && !empty($_SESSION['encryptedTextFile']) && isset($_GET['encrypted'])): ?>
  <div class="bg-white p-6 rounded shadow-md">
    <p><?php echo $_SESSION['encryptionCount']; ?>暗号化が完了しました。</p>
    <a href="<?php echo $_SESSION['publicKeyFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">公開鍵をダウンロード</a>
    <a href="<?php echo $_SESSION['privateKeyFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">秘密鍵をダウンロード</a>
    <a href="<?php echo $_SESSION['encryptedTextFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">暗号化されたテキストをダウンロード</a>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="block w-full py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 mt-2">元のページに戻る</a>
  </div>
<?php elseif (isset($_GET['decrypted'])): ?>
  <?php if (isset($_SESSION['decryptedText'])): ?>
    <div class="bg-white p-6 rounded shadow-md">
      <textarea readonly class="w-full p-2 border border-gray-300 rounded mb-4"><?php echo $_SESSION['decryptedText']; ?></textarea>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="block w-full py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 mt-2">元のページに戻る</a>
    </div>
  <?php else: ?>
    <div class="bg-red-600 text-white p-6 rounded shadow-md">
      <p>復号化後のテキストが見つかりませんでした。</p>
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="block w-full py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 mt-2">元のページに戻る</a>
    </div>
  <?php endif; ?>
<?php else: ?>
  <form action="" method="post" enctype="multipart/form-data" class="bg-white p-6 rounded shadow-md">
    <div class="flex items-center mb-4">
      <input type="radio" name="action" value="encrypt" checked class="mr-2" onclick="toggleInput(true)"> 暗号化
      <input type="radio" name="action" value="decrypt" class="ml-4 mr-2" onclick="toggleInput(false)"> 復号化
    </div>
    <div id="decryptMessage" style="display: none; color: red;">公開鍵、秘密鍵、暗号化されたテキストのファイルをアップロードしてください。
    <br>(pemファイル二つとtxtファイル一つ)</div>  
    <textarea name="text" id="text" placeholder="文章を入力してください" class="w-full p-2 border border-gray-300 rounded mb-4"></textarea>
    <input type="file" name="file1" id="file1" class="w-full p-2 border border-gray-300 rounded mb-4">
    <input type="file" name="file2" id="file2" class="w-full p-2 border border-gray-300 rounded mb-4">
    <input type="file" name="file3" id="file3" class="w-full p-2 border border-gray-300 rounded mb-4">
    <input type="submit" value="実行" class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mb-4">
  </form>
<?php endif; ?>
<script>
  window.onload = function() {
    var isEncrypt = document.querySelector('input[name="action"]:checked').value === 'encrypt';
    toggleInput(isEncrypt);
  };
  function toggleInput(isEncrypt) {
    document.getElementById('text').style.display = isEncrypt ? 'block' : 'none';
    document.getElementById('file1').style.display = isEncrypt ? 'none' : 'block';
    document.getElementById('file2').style.display = isEncrypt ? 'none' : 'block';
    document.getElementById('file3').style.display = isEncrypt ? 'none' : 'block';
    document.getElementById('decryptMessage').style.display = isEncrypt ? 'none' : 'block';
  }
</script>
</body>
</html>