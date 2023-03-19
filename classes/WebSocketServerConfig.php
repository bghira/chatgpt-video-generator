<?php

class WebSocketServerConfig {
	private string $listenAddress;
	private int $listenPort;
	private bool $tlsEnabled;
	private string $tlsLocalCert;
	private string $tlsLocalPk;

	public function __construct(array $config) {
		$this->listenAddress = $config['listen_addr'] ?? '127.0.0.1';
		$this->listenPort = $config['listen_port'] ?? 9090;
		$this->tlsEnabled = $config['tls']['enabled'] ?? false;
		$this->tlsLocalCert = $config['tls']['local_cert'] ?? '';
		$this->tlsLocalPk = $config['tls']['local_pk'] ?? '';
	}
	public function getListenAddress(): string {
		return $this->listenAddress;
	}

	public function getListenPort(): int {
		return $this->listenPort;
	}

	public function isTlsEnabled(): bool {
		return $this->tlsEnabled;
	}

	public function hasCertificateFiles(): bool {
		return file_exists($this->tlsLocalCert) && file_exists($this->tlsLocalPk);
	}

    public function generateSelfSignedCertificate()
    {
        // Generate a new private key
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
    
        // Define the certificate subject
        $dn = [
            'countryName' => 'US',
            'stateOrProvinceName' => 'California',
            'localityName' => 'San Francisco',
            'organizationName' => 'My Organization',
            'organizationalUnitName' => 'My Organizational Unit',
            'commonName' => 'localhost',
            'emailAddress' => 'webmaster@example.com',
        ];
    
        // Generate a certificate signing request (CSR)
        $csr = openssl_csr_new($dn, $privateKey);
    
        // Generate a self-signed certificate
        $certificate = openssl_csr_sign($csr, null, $privateKey, 365);
    
        // Save the private key and certificate to files
        openssl_pkey_export_to_file($privateKey, $this->getTlsConfig()['local_pk']);
        openssl_x509_export_to_file($certificate, $this->getTlsConfig()['local_cert']);
    }
    
	public function toArray(): array {
		return [
		'listen_addr' => $this->listenAddress,
		'listen_port' => $this->listenPort,
		'tls' => [
			'enabled' => $this->tlsEnabled,
			'local_cert' => $this->tlsLocalCert,
			'local_pk' => $this->tlsLocalPk,
		],
	];
	}

	public function getTlsConfig(): array {
		return [
		'local_cert' => $this->tlsLocalCert,
		'local_pk' => $this->tlsLocalPk,
	];
	}
}