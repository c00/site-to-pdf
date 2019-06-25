<?php

namespace c00\wkhtmltopdf;

use Exception;
use mikehaertl\wkhtmlto\Pdf;

class App {
    const DOMAIN_REGEX = '(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}$)';
    const IP_REGEX = '\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b';

    public $bin = 'xvfb-run wkhtmltopdf';
    public $allowedDomains = [];
    public $allowedIps = [];
    public $options = [];

    public $url;
    public $domain;
    public $verbose = false;

    public function run() {
        $this->loadSettings();

        if (!$this->isDomainAllowed()) throw new \Exception("Target domain not white-listed.");
        if (!$this->isIpAllowed()) throw new \Exception("Request IP address not white-listed.");

        $this->generate();
    }

    private function loadSettings() {
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
        //todo check if decoding is done automatically
        $this->url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
        $this->checkUrl();

        $this->verbose = isset($_GET['verbose']);

        //todo load wkhtmltopdf settings from request

    }
    private function addOption($key, $value, $convert = true) {
        if ($convert) {
            $key = strtolower($key);
            $key = str_replace('_', '-', $key);
        }

        $this->options[$key] = $value;
    }

    private function checkUrl() {
        if (!$this->url) throw new Exception("No url");

        if (!preg_match("^https?\:\/\/", $this->url)) {
            throw new Exception("URLs should start with 'http://' or 'http://', Found: {$this->url}");
        }
        $pieces = explode('/', $this->url);
        $this->domain = $pieces[2];
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

        $fullPath = sys_get_temp_dir().'/'.$fileName;
        $pdf->saveAs($fullPath);

        //Check if the PDF exists now.
        if (!file_exists($fullPath)) {
            http_response_code(500);
            echo "Error generating PDF: ";
            echo $pdf->getError();
            exit;
        }

        //Return it
        header('Content-Type: application/pdf');
        header('Content-disposition: attachment; filename="Netques Export.pdf"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        readfile($fullPath);

        //Delete temp file
        unlink ($fullPath);
    }

}