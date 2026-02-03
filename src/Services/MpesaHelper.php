<?php
namespace JamesKabz\MpesaPkg\Services;

class MpesaHelper
{
    protected MpesaConfig $config;

    public function __construct(MpesaConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate SecurityCredential from raw initiator password
     *
     * @param string|null $initiatorPassword If null, read from config
     * @return string Base64 encoded encrypted password
     * @throws \Exception
     */
    public function generateSecurityCredential(? string $initiatorPassword = null): string
    {
        $initiatorPassword = $initiatorPassword ?? $this->config->b2cSecurityCredential();
        if (empty($initiatorPassword)) {
            throw new \Exception("Initiator password is not set in config or parameter.");
        }

        // Determine certificate path based on environment
        $certificatePath = $this->config->certificatePath();

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
