<?php

namespace App\Services\Fiscal;

use NFePHP\Common\Certificate;
use NFePHP\Common\Certificate\CertificationChain;
use NFePHP\Common\Certificate\PrivateKey;
use NFePHP\Common\Certificate\PublicKey;
use NFePHP\Common\Exception\CertificateException;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CertificateLoader
{
    private const LEGACY_OPENSSL_ERROR = '0308010C';

    private ?string $opensslPath;

    /**
     * O caminho pode ser informado para instalações em que o OpenSSL não está no PATH.
     */
    public function __construct(?string $opensslPath = null)
    {
        $this->opensslPath = $opensslPath;
    }

    /**
     * Carrega um certificado A1 PKCS#12.
     *
     * Certificados emitidos por ACs ainda podem usar RC2, algoritmo legado que o
     * OpenSSL 3 não habilita por padrão. Nesse caso, o conteúdo é aberto
     * temporariamente com o provedor legacy e entregue ao NFePHP como chave e
     * certificados PEM, sem repackar o arquivo PFX.
     */
    public function load(string $content, string $password): Certificate
    {
        try {
            return Certificate::readPfx($content, $password);
        } catch (CertificateException $exception) {
            if (!str_contains($exception->getMessage(), self::LEGACY_OPENSSL_ERROR)) {
                throw $exception;
            }
        }

        $openssl = $this->resolveOpenSslPath();
        if ($openssl === null) {
            throw new RuntimeException(
                'O certificado A1 usa criptografia legada incompatível com o OpenSSL 3 e o executável openssl não está disponível no servidor para convertê-lo automaticamente.'
            );
        }

        return $this->readLegacyCertificate($content, $password, $openssl);
    }

    private function resolveOpenSslPath(): ?string
    {
        if ($this->opensslPath === null) {
            $this->opensslPath = (new ExecutableFinder())->find('openssl');
        }

        if (($this->opensslPath === null || $this->opensslPath === '') && PHP_OS_FAMILY === 'Windows') {
            $programFiles = getenv('ProgramFiles');
            $gitOpenSsl = $programFiles
                ? $programFiles . '\\Git\\mingw64\\bin\\openssl.exe'
                : 'C:\\Program Files\\Git\\mingw64\\bin\\openssl.exe';

            if (is_file($gitOpenSsl)) {
                $this->opensslPath = $gitOpenSsl;
            }
        }

        return $this->opensslPath !== '' ? $this->opensslPath : null;
    }

    private function readLegacyCertificate(string $content, string $password, string $openssl): Certificate
    {
        $temporaryFiles = [];

        try {
            $inputPath = $this->createTemporaryFile('nfe-pfx-original-', $temporaryFiles);
            $decryptedPath = $this->createTemporaryFile('nfe-pfx-decrypted-', $temporaryFiles);

            if (file_put_contents($inputPath, $content) === false) {
                throw new RuntimeException('Não foi possível preparar o certificado A1 temporário para leitura.');
            }

            $this->runOpenSsl(
                [
                    'pkcs12',
                    '-legacy',
                    '-in', $inputPath,
                    '-passin', 'stdin',
                    '-nodes',
                    '-out', $decryptedPath,
                ],
                $password,
                $openssl
            );

            $decrypted = file_get_contents($decryptedPath);
            if ($decrypted === false || $decrypted === '') {
                throw new RuntimeException('O OpenSSL não produziu os dados PEM do certificado A1.');
            }

            return $this->certificateFromPem($decrypted);
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }
        }
    }

    private function certificateFromPem(string $pem): Certificate
    {
        if (!preg_match(
            '/-----BEGIN ((?:RSA |EC )?PRIVATE KEY)-----.*?-----END \\1-----/s',
            $pem,
            $privateKeyMatch
        )) {
            throw new RuntimeException('O certificado A1 não contém uma chave privada válida.');
        }

        preg_match_all(
            '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
            $pem,
            $certificateMatches
        );

        if (empty($certificateMatches[0])) {
            throw new RuntimeException('O certificado A1 não contém um certificado digital válido.');
        }

        $privateKey = rtrim($privateKeyMatch[0]) . PHP_EOL;
        $certificates = array_map(
            static fn (string $certificate): string => rtrim($certificate) . PHP_EOL,
            $certificateMatches[0]
        );
        $leafCertificate = $certificates[0];
        $chainCertificates = array_slice($certificates, 1);
        $chain = implode('', $chainCertificates);

        return new Certificate(
            new PrivateKey($privateKey),
            new PublicKey($leafCertificate),
            new CertificationChain($chain)
        );
    }

    /**
     * @param  list<string>  $temporaryFiles
     */
    private function createTemporaryFile(string $prefix, array &$temporaryFiles): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if ($path === false) {
            throw new RuntimeException('Não foi possível criar um arquivo temporário para o certificado A1.');
        }

        if (function_exists('chmod')) {
            @chmod($path, 0600);
        }

        $temporaryFiles[] = $path;

        return $path;
    }

    /**
     * @param  list<string>  $arguments
     */
    private function runOpenSsl(array $arguments, string $password, string $openssl): void
    {
        $process = new Process(array_merge([$openssl], $arguments));
        $process->setInput($password . "\n");
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        $details = trim($process->getErrorOutput() ?: $process->getOutput());

        throw new RuntimeException(
            'Não foi possível abrir o certificado A1 usando o provedor legacy do OpenSSL 3. '
            . 'Verifique a senha e a disponibilidade do provedor legacy. Detalhes: '
            . ($details !== '' ? $details : 'OpenSSL encerrou sem informações adicionais.')
        );
    }
}
