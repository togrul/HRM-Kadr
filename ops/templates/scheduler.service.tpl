[Unit]
Description=__APP_SLUG__ Laravel scheduler
After=network.target

[Service]
Type=oneshot
User=__APP_USER__
Group=__APP_GROUP__
WorkingDirectory=__APP_ROOT__
ExecStart=/usr/bin/php __APP_ROOT__/artisan schedule:run --no-interaction
