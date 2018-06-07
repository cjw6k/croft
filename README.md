# a6a
Also known as: a -six letters- a  
Also known as: aetheria

## Hacking Aetheria
##### Note: shell access is required (e.g. ssh, terminal, etc.)

1. Clone this repo into a suitable location.
1. Install required libs with composer: `composer install`
1. Copy the `./behat.yml` configuration to `./behat.custom.yml`
1. Adjust the `./behat.custom.yml` to point to your URL.
1. Setup your webserver (apache, nginx, lighthttp, etc.) to point the public document root at the `./public` folder.
1. Run the tests: `./run-tests.sh`

Example `behat.custom.yml` (replace *a6a.example.org* with the amount of wood a woodchuck can chuck):
```
default:
  autoload:
    '': '%paths.base%/tests'
  extensions:
    Behat\MinkExtension:
      base_url: 'http://a6a.example.org/'
      goutte: ~
  suites:
    public:
      paths: [ '%paths.base%/tests/features/public' ]
      contexts:
       - PublicContext:
           base_url: 'http://a6a.example.org/'
```

Example www-server/nginx config  (replace *a6a.example.org* with the airspeed velocity of a (European) unladen swallow):

```NGINX
server {
        listen [::]:80;
        listen 80;
        server_name a6a.example.org;

        root /var/www/a6a.example.org/public;

        index index.php index.html index.htm;

        error_log /var/www/a6a.example.org/logs/error.log warn;
        access_log /var/www/a6a.example.org/logs/access.log;

        charset utf-8;

        # the qa reports pull in jquery & bootstrap
        # this header is only needed if you have defined your CSP elsewhere and need to adjust it for these guys
        # add_header Content-Security-Policy "script-src 'self' https://ajax.googleapis.com https://maxcdn.bootstrapcdn.com 'unsafe-inline'; object-src 'self'";

        # the qa reports use iframes for fancy flavour
        # this header is only needed if you haved your X-Frame-Options to DENY elsewhere and need to adjust it for phpqa
        #location /docs/ {
        #        add_header X-Frame-Options SAMEORIGIN;
        #}

        location / {
                try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
                include /etc/nginx/fastcgi.conf;
                fastcgi_pass unix:/var/run/php-fpm.sock;
        }
}

```

## Code Documentation
To generate the documentation, run phpdox with `./vendor/bin/phpdox`. This generates and copies the documentation into `./public/docs/`.

**Note:** Running the tests also generates updated documentation, but only if the behat & phpspec tests all pass.  
**Note:** If you don't use `./public` for your public www root, adjust the `./run-tests.sh` script to suit your setup.

## Testing
To run all the tests, use `./run-tests.sh`. This copies the reports into `./public/docs/qa`.

**Note:** If you don't use `./public` for your public www root, adjust the `./run-tests.sh` script to suit your setup.



-----



#### Required hack for tests toolchain to work as of 2018-06-07
There is a limitation with using tabs as the indentation method and phpcs. I don't try to understand it, i just fix it:
```shell
$ mv vendor/bin/phpcs vendor/bin/phpcs-default
```
and create the replacement `./vendor/bin/phpcs` with contents:
```shell
#!/bin/sh

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
$DIR/phpcs-default "$@" --tab-width=4
```

## Acknowledgements
Everyone at [#IndieWebCamp](https://indieweb.org/)! 

## License

See the [LICENSE](LICENSE.md) file for license rights and limitations (MIT).