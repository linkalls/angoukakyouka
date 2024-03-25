require 'optparse'
require 'openssl'
require 'base64'

# ランダムな文章を生成する関数を定義します
def generate_random_text(length)
  charset = (' '..'~').to_a
  return Array.new(length) { charset.sample }.join
end

# 文章を暗号化する関数を定義します
def encrypt_text(text, public_key)
  encrypted_text = public_key.public_encrypt(text)
  return Base64.encode64(encrypted_text)
end

# 文章を復号化する関数を定義します
def decrypt_text(encrypted_text, private_key)
  encrypted_text = Base64.decode64(encrypted_text)
  return private_key.private_decrypt(encrypted_text)
end

# 暗号化の処理
def perform_encryption
  puts "文章を入力してください："
  original_text = gets.chomp

  # RSAキーペアの生成
  key_pair = OpenSSL::PKey::RSA.generate(2048)
  public_key = key_pair.public_key
  private_key = key_pair

  # 元の文章を暗号化します
  encrypted_text = encrypt_text(original_text, public_key)

  puts "暗号化された文章: #{encrypted_text}"
  puts "公開鍵と秘密鍵のファイルパスを保存してください。"
  File.write('public_key.pem', public_key.to_pem)
  File.write('private_key.pem', private_key.to_pem)

  # 暗号化された文章をtxtファイルとして保存します
  File.write('encrypted_text.txt', encrypted_text)
end

def perform_decryption(public_key_path, private_key_path, encrypted_text_path)
  public_key = OpenSSL::PKey::RSA.new(File.read(public_key_path.gsub('"', '')))
  private_key = OpenSSL::PKey::RSA.new(File.read(private_key_path.gsub('"', '')))

  # 暗号化された文章をtxtファイルから読み込みます
  encrypted_text = File.read(encrypted_text_path.gsub('"', ''))

  # 暗号化された文章を復号化します
  decrypted_text = decrypt_text(encrypted_text, private_key)

  puts "復号化された文章: #{decrypted_text}"
end

# オプションパーサーをセットアップします
options = {}
OptionParser.new do |opts|
  opts.banner = "Usage: ruby script.rb [options]"
  opts.on("-e", "--encrypt TEXT", "暗号化する文章を指定します") do |text|
    options[:action] = :encrypt
    options[:text] = text
  end
  opts.on("-d", "--decrypt TEXT", "復号化する文章を指定します") do |text|
    options[:action] = :decrypt
    options[:text] = text
  end
  opts.on("--public_key PUBLIC_KEY_PATH", "公開鍵のファイルパスを指定します") do |public_key_path|
    options[:public_key] = public_key_path
  end
  opts.on("--private_key PRIVATE_KEY_PATH", "秘密鍵のファイルパスを指定します") do |private_key_path|
    options[:private_key] = private_key_path
  end
end.parse!

# 暗号化か復号化のアクションを選択します
case options[:action]
when :encrypt
  perform_encryption
when :decrypt
  if options[:public_key].nil? || options[:private_key].nil?
    puts "エラー: 公開鍵と秘密鍵のファイルパスを指定してください。"
    exit
  end

  perform_decryption(options[:public_key], options[:private_key])
else
  puts "1. 文章を暗号化する"
  puts "2. 文章を複合化する"
  print "選択してください（1または2）: "
  choice = gets.chomp.to_i

  case choice
  when 1
    perform_encryption
  when 2
    puts "公開鍵のファイルパスを指定してください："
    public_key_path = gets.chomp
    puts "秘密鍵のファイルパスを指定してください："
    private_key_path = gets.chomp

    puts "暗号化された文章が保存されているtxtファイルのパスを入力してください："
    encrypted_text_path = gets.chomp

    begin
      perform_decryption(public_key_path, private_key_path, encrypted_text_path)
    rescue => e
      puts "エラー：復号化に失敗しました。公開鍵と秘密鍵のファイルパスが正しいか確認してください。"
      puts "詳細: #{e.message}"
    end
  else
    puts "無効な選択です。"
  end
end
