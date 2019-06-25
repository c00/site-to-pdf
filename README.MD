# Convert HTML pages to PDF

Converts HTML pages in PDFs by visiting the URL. It uses [wkhtmltopdf](https://wkhtmltopdf.org/) to achieve this.
It also uses the package [mikehaertl/phpwkhtmltopdf](https://github.com/mikehaertl/phpwkhtmltopdf).

## Configuration

It takes its configuration from environment variables. The following variables are used:

*ALLOWED_DOMAINS* - Used to limit from which domain(s) pages can be converted. Multiple domains can be separated by commas.
Don't include protocol or path. If empty or doesn't exist, no limit is set. WARNING: not setting this will allow users to
convert any site on the internet to PDF, which can obviously be exploited.
Example value: `www.mydomain.com,mydomain.com,something.example.com`

*ALLOWED_IPS* - Used to limit who gets to request conversions. Multiple IPs can be separated by commas.
WARNING: not setting this will allow anyone to use this service, which can obviously be exploited.
Example value: `127.0.0.1,8.8.4.4,1.2.3.4`

*WKHTMLTOPDF_BIN* - The location of the wkhtmltopdf binary. Defaults to `xvfb-run wkhtmltopdf`

*WKHTMLTOPDF_OPTIONS_[option]* - Any options you want to pass to wkhtmltopdf. Some examples:

```
WKHTMLTOPDF_OPTIONS_MARGIN_TOP=0mm
WKHTMLTOPDF_OPTIONS_DISABLE_SMART_SHRINKING=
WKHTMLTOPDF_OPTIONS_DPI=175
WKHTMLTOPDF_OPTIONS_ZOOM=1
WKHTMLTOPDF_OPTIONS_FOOTER_HTML="http://example.com/assets/footer.html"
```

# Todo

Write tests
Make dockerfile
Upload docker image to docker hub
