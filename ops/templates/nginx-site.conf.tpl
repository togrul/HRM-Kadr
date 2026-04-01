server {
    listen __APP_PORT__;
    listen [::]:__APP_PORT__;
    server_name __APP_DOMAIN__;

    root __APP_ROOT__/public;
    index index.php index.html;
    client_max_body_size __CLIENT_MAX_BODY_SIZE__;

    access_log /var/log/nginx/__APP_SLUG__-access.log;
    error_log /var/log/nginx/__APP_SLUG__-error.log warn;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:__PHP_FPM_SOCKET__;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
