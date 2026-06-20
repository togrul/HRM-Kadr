[Unit]
Description=Run __APP_SLUG__ Laravel scheduler every minute

[Timer]
OnCalendar=*-*-* *:*:00
AccuracySec=1s
Persistent=true
Unit=__APP_SLUG__-scheduler.service

[Install]
WantedBy=timers.target
