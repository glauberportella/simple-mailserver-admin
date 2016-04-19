# Simple Mailserver Admin

A simple Postfix + Dovecot + MySQL interface to manage mailboxes.

The e-mail system must be based on [this Linode article](https://www.linode.com/docs/email/email-with-postfix-dovecot-and-mysql)

# Functions

Actually only change email password.

# Web Server config

## Apache

    <IfModule mod_rewrite.c>
        Options -MultiViews

        RewriteEngine On
        #RewriteBase /path/to/app
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [QSA,L]
    </IfModule>

Alternatively, if you use Apache 2.2.16 or higher, you can use the FallbackResource directive to make your .htaccess even easier:

    FallbackResource /index.php

## Nginx

Minimun config for Nginx:

    server {
        server_name domain.tld www.domain.tld;
        root /var/www/project/web;

        location / {
            # try to serve file directly, fallback to front controller
            try_files $uri /index.php$is_args$args;
        }

        # If you have 2 front controllers for dev|prod use the following line instead
        # location ~ ^/(index|index_dev)\.php(/|$) {
        location ~ ^/index\.php(/|$) {
            # the ubuntu default
            fastcgi_pass   unix:/var/run/php5-fpm.sock;
            # for running on centos
            #fastcgi_pass   unix:/var/run/php-fpm/www.sock;

            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS off;

            # Prevents URIs that include the front controller. This will 404:
            # http://domain.tld/index.php/some-path
            # Enable the internal directive to disable URIs like this
            # internal;
        }

        #return 404 for all php files as we do have a front controller
        location ~ \.php$ {
            return 404;
        }

        error_log /var/www/project/error.log;
        #access_log /var/www/project/access.log;
    }

# License

## The MIT License (MIT)

Copyright (c) 2016 Glauber Portella <glauberportella@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.