<?php

namespace c00\wkhtmltopdf\test;

use c00\wkhtmltopdf\App;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase {
    const OUTPUT_FILE = 'test-doc.pdf';

    protected function tearDown(): void {
        parent::tearDown();

        $fullPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::OUTPUT_FILE;
        if (file_exists($fullPath)) unlink($fullPath);
    }

    public function testDomainRegex() {
        $good = [ 'domain.nl', 'sub.domain.com', '1.2.3.4.5.6.7.example.sometld', 'domain-with-dashes.eu' ];
        $bad = ['1.2.3.4', 'notadomain at all', 'asd@bla.com', 'domain with spaces.com', 'domain,withcomma.com'];

        foreach ( $good as $domain ) {
            $this->assertTrue((bool) preg_match(App::DOMAIN_REGEX, $domain), "$domain is a good domain.");
        }

        foreach ( $bad as $domain ) {
            $this->assertFalse((bool) preg_match(App::DOMAIN_REGEX, $domain), "$domain is not a valid domain.");
        }
    }

    public function testIpRegex() {
        $good = [ '1.2.3.4', '254.254.254.254', '127.0.0.1', '192.168.1.1', '300.1.1.1' ];
        $bad = [ '1.2.3', '1', 'a.b.c.d' ];

        foreach ( $good as $ip ) {
            $this->assertTrue((bool) preg_match(App::IP_REGEX, $ip), "$ip is a good IP.");
        }

        foreach ( $bad as $ip ) {
            $this->assertFalse((bool) preg_match(App::IP_REGEX, $ip), "$ip is not good.");
        }
    }

    public function testRun() {
        $app = new App();
        //Set settings
        $app->setUrl('https://duckduckgo.com');
        $app->outputFilename = self::OUTPUT_FILE;
        $outputFile = $app->run();

        $fullPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::OUTPUT_FILE;
        $this->assertFileEquals($fullPath, $outputFile, "Return value should be the same.");
        $this->assertFileExists($outputFile, "PDF should be created");
    }

    public function testRunWrongDomain() {
        $app = new App();
        $app->allowedDomains = ['duckduckgo.com', 'example.com', 'someothersite.com'];
        //Set settings
        $app->setUrl('https://google.com');
        $app->outputFilename = self::OUTPUT_FILE;

        $this->expectExceptionMessage("Target domain not white-listed.");
        $app->run();
    }

    public function testRunRightDomain() {
        $app = new App();
        $app->allowedDomains = ['duckduckgo.com', 'google.com', 'example.com', 'someothersite.com'];
        //Set settings
        $app->setUrl('https://google.com');
        $app->outputFilename = self::OUTPUT_FILE;
        $outputFile = $app->run();

        $fullPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::OUTPUT_FILE;
        $this->assertFileEquals($fullPath, $outputFile, "Return value should be the same.");
        $this->assertFileExists($outputFile, "PDF should be created");
    }

}
