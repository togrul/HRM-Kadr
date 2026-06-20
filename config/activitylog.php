<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOG_ENABLED', env('ACTIVITY_LOGGER_ENABLED', true)),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 730),

    /*
     * Keep the automated clean-up opt-in. Suggested policy values:
     * 12 months = 365 days, 24 months = 730 days, 36 months = 1095 days.
     */
    'retention' => [
        'schedule_enabled' => env('ACTIVITY_LOG_RETENTION_SCHEDULE_ENABLED', false),
        'daily_at' => env('ACTIVITY_LOG_RETENTION_DAILY_AT', '02:30'),
    ],

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => true,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \App\Models\AuditActivity::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => env('ACTIVITY_LOG_TABLE_NAME', env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log')),

    /*
     * This is the database connection that will be used by the migration and
     * the Activity model shipped with this package. In case it's not set
     * Laravel's database.default will be used instead.
     */
    'database_connection' => env(
        'ACTIVITY_LOG_CONNECTION',
        env('ACTIVITY_LOGGER_DB_CONNECTION', env('DB_CONNECTION') === 'sqlite' ? 'sqlite' : 'audit')
    ),
];
