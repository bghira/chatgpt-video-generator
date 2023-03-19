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

	public function generateSelfSignedCertificate(): void {
		$sslKeyConfig = [
		'private_key_bits' => 2048,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	];
		$sslKey = openssl_pkey_new($sslKeyConfig);

		$sslCertConfig = [
		'commonName' => $this->listenAddress,
		'privateKey' => $sslKey,
	];
		$sslCert = openssl_csr_new($sslCertConfig);

		$sslCert = openssl_csr_sign($sslCert, null, $sslKey, 365);

		$sslCertPath = __DIR__ . '/../certs/' . $this->listenAddress . '.pem';
		$sslKeyPath = __DIR__ . '/../certs/' . $this->listenAddress . '.key';

		openssl_x509_export($sslCert, $sslCertOutput);
		file_put_contents($sslCertPath, $sslCertOutput);

		openssl_pkey_export($sslKey, $sslKeyOutput);
		file_put_contents($sslKeyPath, $sslKeyOutput);

		$this->tlsLocalCert = $sslCertPath;
		$this->tlsLocalPk = $sslKeyPath;
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