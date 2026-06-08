<?php
$config = array(
    'digest_alg' => 'sha256',
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
);
$key = openssl_pkey_new($config);
openssl_pkey_export($key, $privateKey, 'c1079267067c003ae294163876871dc587f21b95051b4897cf884a694ffbe4a8');
file_put_contents('config/jwt/private.pem', $privateKey);
$publicKey = openssl_pkey_get_details($key)['key'];
file_put_contents('config/jwt/public.pem', $publicKey);
echo 'Clés JWT générées avec succès';
?>
