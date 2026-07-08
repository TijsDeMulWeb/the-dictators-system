<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bot & Guild
    |--------------------------------------------------------------------------
    |
    | These are consumed by the Node.js bot (bot/), but are mirrored here so
    | the Laravel side can reference the same values where needed.
    */
    'bot_token' => env('DISCORD_BOT_TOKEN'),
    'client_id' => env('DISCORD_CLIENT_ID'),
    'guild_id' => env('DISCORD_GUILD_ID'),

    /*
    |--------------------------------------------------------------------------
    | Channels & Roles
    |--------------------------------------------------------------------------
    */
    'review_channel_id' => env('DISCORD_REVIEW_CHANNEL_ID'),
    'report_channel_id' => env('DISCORD_REPORT_CHANNEL_ID'),
    'scoreboard_channel_id' => env('DISCORD_SCOREBOARD_CHANNEL_ID'),
    'secretary_role_id' => env('DISCORD_SECRETARY_ROLE_ID'),
    'leader_role_id' => env('DISCORD_LEADER_ROLE_ID'),
    'retired_role_id' => env('DISCORD_RETIRED_ROLE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Internal API
    |--------------------------------------------------------------------------
    |
    | Shared secret the bot uses to authenticate against the Laravel internal
    | API (routes/api.php, protected by the bot.auth middleware).
    */
    'internal_api_secret' => env('INTERNAL_API_SECRET'),
];
