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


    function writeFile($filePath, $content) {
      $retryCount = 0;
      $maxRetries = 5;
    
      while ($retryCount < $maxRetries) {
        if (file_put_contents($filePath, $content) !== false) {
          return true;
        }
        $retryCount++;
        sleep(1); // Wait for 1 second before retrying
      }
    
      return false;
    }

    // ディレクトリが存在しない場合は作成
    if (!file_exists('public')) {
      mkdir('public', 0777, true);
    }
    if (!file_exists('private')) {
      mkdir('private', 0777, true);
    }
    if (!file_exists('txt')) {
      mkdir('txt', 0777, true);
    }

    // 一意なIDを生成
    $uniqueId = uniqid();

    // ファイル名に一意なIDを使用
    $publicKeyFile = "public/public_key_$uniqueId.pem";
    $privateKeyFile = "private/private_key$uniqueId.pem";
    $encryptedTextFile = "txt/encrypted_text_$uniqueId.txt";

    if (file_put_contents($publicKeyFile, $publicKey) === false ||
        file_put_contents($privateKeyFile, $privateKey) === false ||
        file_put_contents($encryptedTextFile, $encryptedText) === false) {
      $_SESSION['errorMessage'] = 'ファイルの作成に失敗しました。';
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }

  


    file_put_contents($encryptedTextFile, $encryptedText);

    // ファイル名をセッションに保存
    $_SESSION['publicKeyFile'] = $publicKeyFile;
    $_SESSION['privateKeyFile'] = $privateKeyFile;
    $_SESSION['encryptedTextFile'] = $encryptedTextFile;


   

   // POSTリクエストの処理が完了したので、自身にリダイレクト
   header("Location: " . $_SERVER['PHP_SELF'] . "?encrypted=true");
   exit;

  } elseif ($action === 'decrypt') {
$files = array($_FILES['file1'], $_FILES['file2'], $_FILES['file3']);
$publicKeyFile = null;
$privateKeyFile = null;
$encryptedTextFile = null;

foreach ($files as $file) {
  // ファイルアップロードのエラーチェック
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['errorMessage'] = 'ファイルのアップロードに失敗しました。';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  }

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


 // 復号化のエラーチェック
 if ($decryptedText === false) {
  $_SESSION['errorMessage'] = 'テキストの復号化に失敗しました。';
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
     .radio {
      position: relative;
      display: inline-block;
      margin-right: 20px;
      cursor: pointer;
    }
    .radio input {
      position: absolute;
      opacity: 0;
    }
    .radio .radio__inner {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid #1f2937;
      display: inline-block;
    }
    .radio input:checked ~ .radio__inner {
      background-color: #10B981;
    }
     textarea {
      height: 150px;
    }
    body {
      background-color: #f3f4f6;
      color: #1f2937;
    }
    .card {
      background-color: #ffffff;
    }
    .btn {
      background-color: #3b82f6;
    }
    @media (prefers-color-scheme: dark) {
      body, div, form {
        background-color: #1f2937 !important;
        color: #f3f4f6 !important;
      }
      textarea {
        color: #000000 !important;
      }
      .card {
        background-color: #4b5563 !important;
      }
      .btn {
        background-color: #2563eb !important;
      }
      .radio .radio__inner {
        border-color: #f3f4f6;
      }
      .radio input:checked ~ .radio__inner {
        background-color: #10B981;
      }
    }
    @media (min-width: 768px) {
      #text {
        width: 70vw !important;
      }
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
    <p>>暗号化が完了しました。</p>
    <p style="color: red;">すべてのファイルをダウンロードをしないと複合化できません</p>
    <a href="<?php echo $_SESSION['publicKeyFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">公開鍵をダウンロード</a>
    <a href="<?php echo $_SESSION['privateKeyFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">秘密鍵をダウンロード</a>
    <a href="<?php echo $_SESSION['encryptedTextFile']; ?>" download class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mt-2">暗号化されたテキストをダウンロード</a>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="block w-full py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 mt-2">元のページに戻る</a>
  </div>
<?php elseif (isset($_GET['decrypted'])): ?>
  <?php if (isset($_SESSION['decryptedText'])): ?>
    <div class="bg-white p-6 rounded shadow-md">
      <textarea readonly class="w-full p-2 border border-gray-300 rounded mb-4" style="width: calc(100vw - 40px);"><?php echo $_SESSION['decryptedText']; ?></textarea>
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
      <label class="radio">
        <input type="radio" name="action" value="encrypt" checked class="mr-2" onclick="toggleInput(true)">
        <span class="radio__inner"></span>
        暗号化
      </label>
      <label class="radio">
        <input type="radio" name="action" value="decrypt" class="ml-4 mr-2" onclick="toggleInput(false)">
        <span class="radio__inner"></span>
        復号化
      </label>
    </div>
    <div id="decryptMessage" style="display: none; color: red;">公開鍵、秘密鍵、暗号化されたテキストのファイルをアップロードしてください。
    <br>(pemファイル二つとtxtファイル一つ)</div>  
<textarea name="text" id="text" placeholder="文章を入力してください" class="p-2 border border-gray-300 rounded mb-4" style="width: calc(100vw - 40px);" required></textarea>
    <input type="file" name="file1" id="file1" class="w-full p-2 border border-gray-300 rounded mb-4">
    <input type="file" name="file2" id="file2" class="w-full p-2 border border-gray-300 rounded mb-4">
    <input type="file" name="file3" id="file3" class="w-full p-2 border border-gray-300 rounded mb-4">
    <input type="submit" value="実行" class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 mb-4">
  </form>
<?php endif; ?>
<script>
  window.onload = function() {
    var actionInput = document.querySelector('input[name="action"]:checked');
    if (actionInput) {
      var isEncrypt = actionInput.value === 'encrypt';
      toggleInput(isEncrypt);
    }
  };
  function toggleInput(isEncrypt) {
    var textArea = document.getElementById('text');
    textArea.style.display = isEncrypt ? 'block' : 'none';
    if (isEncrypt) {
      textArea.setAttribute('required', '');
    } else {
      textArea.removeAttribute('required');
    }
    document.getElementById('file1').style.display = isEncrypt ? 'none' : 'block';
    document.getElementById('file2').style.display = isEncrypt ? 'none' : 'block';
    document.getElementById('file3').style.display = isEncrypt ? 'none' : 'block';
    document.getElementById('decryptMessage').style.display = isEncrypt ? 'none' : 'block';
  }

  
</script>
</body>
</html>