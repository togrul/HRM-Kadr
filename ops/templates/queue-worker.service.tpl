[Unit]
Description=__APP_SLUG__ queue worker
After=network.target

[Service]
User=__APP_USER__
Group=__APP_GROUP__
Restart=always
RestartSec=5
WorkingDirectory=__APP_ROOT__
ExecStart=/usr/bin/php __APP_ROOT__/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --no-interaction
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
