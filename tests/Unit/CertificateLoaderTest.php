<?php

namespace Tests\Unit;

use App\Services\Fiscal\CertificateLoader;
use PHPUnit\Framework\TestCase;

class CertificateLoaderTest extends TestCase
{
    public function test_carrega_pfx_moderno_sem_depender_do_executavel_openssl(): void
    {
        $fixture = dirname(__DIR__, 2)
            . '/vendor/nfephp-org/sped-common/tests/fixtures/certs/certificado_teste.pfx';

        if (!is_file($fixture)) {
            $this->markTestSkipped('Fixture de certificado A1 moderno não encontrado no pacote NFePHP.');
        }

        $certificate = (new CertificateLoader('/caminho/openssl/inexistente'))->load(
            file_get_contents($fixture),
            'associacao'
        );

        $this->assertNotSame('', $certificate->getCompanyName());
    }

    public function test_abre_e_carrega_certificado_pfx_legado(): void
    {
        $fixture = dirname(__DIR__, 2)
            . '/vendor/nfephp-org/sped-common/tests/fixtures/certs/expected.pfx';

        if (!is_file($fixture)) {
            $this->markTestSkipped('Fixture de certificado A1 legado não encontrada no pacote NFePHP.');
        }

        $certificate = (new CertificateLoader())->load(
            file_get_contents($fixture),
            'associacao'
        );

        $signature = $certificate->sign('nfephp-openssl3');

        $this->assertNotSame('', $certificate->getCompanyName());
        $this->assertTrue($certificate->verify('nfephp-openssl3', $signature));
        $this->assertNotEmpty($certificate->chainKeys->listChain());

        $combinedPem = (string) $certificate->privateKey . (string) $certificate;
        $this->assertNotFalse(openssl_pkey_get_private($combinedPem));
        $this->assertNotFalse(openssl_x509_read($combinedPem));
    }
}
