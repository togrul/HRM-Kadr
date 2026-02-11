<?php

return [
    // Disable Boost browser console log watcher to avoid extra /_boost/browser-logs AJAX noise.
    'browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', false),
];

