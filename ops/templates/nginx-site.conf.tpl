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

    # Keep idle keep-alive connections open longer than browsers do (Chrome ~300 s), so the
    # browser always closes first. If nginx closed one first, Safari could send a POST on the
    # dead connection and fail it with "The network connection was lost" without retrying —
    # every Livewire update is a POST. (The app also retries such a failure once, client side.)
    keepalive_timeout 305s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_index index.php;
        fastcgi_pass unix:__PHP_FPM_SOCKET__;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
