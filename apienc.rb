require 'sinatra'
require 'json'
require 'openssl'
require 'base64'

# a.rbの関数を再利用します
def generate_random_text(length)
  charset = (' '..'~').to_a
  return Array.new(length) { charset.sample }.join
end

def encrypt_text(text, public_key)
  encrypted_text = public_key.public_encrypt(text)
  return Base64.encode64(encrypted_text)
end

def decrypt_text(encrypted_text, private_key)
  encrypted_text = Base64.decode64(encrypted_text)
  decrypted_text = private_key.private_decrypt(encrypted_text)
  return decrypted_text.force_encoding('UTF-8')
end

post '/encrypt' do
  params = JSON.parse(request.body.read)
  original_text = params['text']

  key_pair = OpenSSL::PKey::RSA.generate(2048)
  public_key = key_pair.public_key
  private_key = key_pair

  encrypted_text = encrypt_text(original_text, public_key)

  timestamp = Time.now.strftime("%Y%m%d%H%M%S")
  File.write("#{timestamp}_public_key.pem", public_key.to_pem)
  File.write("#{timestamp}_private_key.pem", private_key.to_pem)
  File.write("#{timestamp}_encrypted.txt", encrypted_text)

  { encrypted_text: encrypted_text }.to_json
end

post '/decrypt' do
  params = JSON.parse(request.body.read)
  public_key_path = params['public_key_path']
  private_key_path = params['private_key_path']
  encrypted_text_path = params['encrypted_text_path']

  public_key = OpenSSL::PKey::RSA.new(File.read(public_key_path))
  private_key = OpenSSL::PKey::RSA.new(File.read(private_key_path))
  encrypted_text = File.read(encrypted_text_path)

  decrypted_text = decrypt_text(encrypted_text, private_key)

  { decrypted_text: decrypted_text }.to_json
end
