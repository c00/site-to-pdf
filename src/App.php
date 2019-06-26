<?php

namespace c00\wkhtmltopdf;

use Exception;
use mikehaertl\wkhtmlto\Pdf;

class App {
    const DOMAIN_REGEX = '/(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}$)/';
    const IP_REGEX = '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/';

    private $url;
    private $domain;
    private $cliMode = false;

    public $bin = 'xvfb-run wkhtmltopdf';
    public $allowedDomains = [];
    public $allowedIps = [];
    public $options = [];
    public $outputFilename = 'document.pdf';

    public function __construct() {
        $this->loadSettings();
    }

    public function run() {
        if (!$this->isDomainAllowed()) throw new Exception("Target domain not white-listed.");
        if (!$this->isIpAllowed()) throw new Exception("Request IP address not white-listed.");

        return $this->generate();
    }

    private function loadSettings() {
        $this->cliMode = php_sapi_name() === 'cli';

        //From Environment
        $binary = getenv('WKHTMLTOPDF_BIN');
        if ($binary !== false) $this->bin = $binary;

        $domainsRaw = getenv("ALLOWED_DOMAINS");
        if ($domainsRaw !== false) {
            $this->allowedDomains = explode(',', $domainsRaw);
        }
        $this->checkDomains();

        $ipsRaw = getenv("ALLOWED_IPS");
        if ($ipsRaw !== false) {
            $this->allowedIps = explode(',', $ipsRaw);
        }
        $this->checkIps();

        //Load wkhtmltopdf specific settings
        foreach ( $_ENV as $key => $value ) {
            if (substr($key, 0, 20) === 'WKHTMLTOPDF_OPTIONS_') {
                $this->addOption(substr($key, 20), $value);
            }
        }

        //From Request
        if (!$this->cliMode) {
            $url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
            $this->setUrl($url);

            $this->outputFilename = filter_var($_GET['verbose'], FILTER_SANITIZE_STRING);

            //todo load wkhtmltopdf settings from request
        }


    }
    private function addOption($key, $value, $convert = true) {
        if ($convert) {
            $key = strtolower($key);
            $key = str_replace('_', '-', $key);
        }

        $this->options[$key] = $value;
    }

    public function setUrl(string $url) {
        if (!$url) throw new Exception("No url");

        if (!preg_match("/^https?\:\/\//", $url)) {
            throw new Exception("URLs should start with 'http://' or 'http://', Found: {$url}");
        }
        $pieces = explode('/', $url);
        $this->domain = $pieces[2];
        $this->url = $url;
    }

    private function checkDomains() {
        foreach ( $this->allowedDomains as $domain ) {
            if (!preg_match(self::DOMAIN_REGEX, $domain)){
                throw new Exception("Allowed domain name invalid: $domain");
            }
        }
    }

    private function checkIps() {
        foreach ( $this->allowedIps as $ip ) {
            if (!preg_match(self::IP_REGEX, $ip)){
                throw new Exception("Allowed IP format invalid: $ip");
            }
        }
    }

    private function isDomainAllowed(): bool
    {
        if (empty($this->allowedDomains)) return true;

        return in_array($this->domain, $this->allowedDomains);
    }

    private function isIpAllowed(): bool
    {
        //From the CLI, it's always good.
        if ($this->cliMode) return true;

        if (empty($this->allowedIps)) return true;

        return in_array($this->getIp(), $this->allowedIps);
    }

    private function getIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    private function generate() {
        $fileName = bin2hex(random_bytes(10)) . '.pdf';

        //Create the PDF
        $pdf = new Pdf($this->url);
        $pdf->binary = $this->bin;
        $pdf->setOptions($this->options);
        $pdf->ignoreWarnings = true;

        $fullPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;
        $pdf->saveAs($fullPath);

        //Check if the PDF exists now.
        if (!file_exists($fullPath)) {
            http_response_code(500);
            echo "Error generating PDF: ";
            echo $pdf->getError();
            exit;
        }

        if (!$this->cliMode) {
            //Return it
            header('Content-Type: application/pdf');
            header('Content-disposition: attachment; filename="' . $this->outputFilename . '"');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            readfile($fullPath);

            //Delete temp file
            unlink ($fullPath);
            return null;
        } else {
            $newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->outputFilename;
            rename($fullPath, $newPath);
            return $newPath;
        }

    }

}