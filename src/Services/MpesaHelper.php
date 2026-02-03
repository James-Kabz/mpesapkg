<?php
namespace JamesKabz\MpesaPkg\Services;

class MpesaHelper
{
    /**
     * Generate SecurityCredential from raw initiator password
     *
     * @param string|null $initiatorPassword If null, read from config
     * @return string Base64 encoded encrypted password
     * @throws \Exception
     */
    public static function generateSecurityCredential(? string $initiatorPassword=null)
    {
        $initiatorPassword = $initiatorPassword ?? config('mpesa.credentials.b2c.security_credential');
        if (empty($initiatorPassword)) {
            throw new \Exception("Initiator password is not set in config or parameter.");
        }

        // Determine certificate path based on environment
        $env = config('mpesa.env', 'sandbox'); // default to sandbox
        $certificatePath = $env === 'sandbox'
            ? config('mpesa.cert_paths.sandbox', storage_path('app/private/certs/SandboxCertificate.cer'))
            : config('mpesa.cert_paths.production', storage_path('app/private/certs/ProductionCertificate.cer'));

        if (!file_exists($certificatePath)) {
            throw new \Exception("Certificate file not found at {$certificatePath}");
        }

        $cert = file_get_contents($certificatePath);
        $publicKey = openssl_pkey_get_public($cert);

        if (!$publicKey) {
            throw new \Exception("Could not load public key from certificate.");
        }

        $encrypted = '';
        $success = openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);

        if (!$success) {
            throw new \Exception("Encryption failed.");
        }

        return base64_encode($encrypted);
    }
}
