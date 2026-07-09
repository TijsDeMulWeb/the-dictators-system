# The Dictators System

A Discord-driven reporting & scoreboard system for The Dictators community.

- **Laravel** (this repo) — the brain: database, scoreboard/tier logic, report-card
  and scoreboard image rendering, and an internal API the bot talks to.
- **Discord bot** (`bot/`, Node.js + discord.js) — the face: slash commands,
  searchable player/challenge pickers, the review → approve flow, and posting the
  rendered images back to Discord.

Leaders submit game reports in Discord (`/report`); the General Secretary approves
them with a button; approved reports and the scoreboard are posted as images.

## Requirements

- PHP 8.3+ with the usual Laravel extensions
- Node.js 20+ (22 recommended)
- A Discord application/bot (see below)

## Scoreboard formula

```
Final Score = Avg Points × (ln(Games + 1))² × Win Rate
```

Challenge games add a fixed per-tier bonus (e.g. Gold +500) on top of each
participant's game points.

## First-time setup

```bash
# PHP side
composer install
cp .env.example .env        # then fill in the values below
php artisan key:generate
php artisan migrate
php artisan storage:link

# Bot side
cd bot && npm install && cd ..
```

### Discord application

1. https://discord.com/developers/applications → **New Application** → **Bot** →
   copy the token into `DISCORD_BOT_TOKEN`.
2. Enable **Server Members Intent** and **Message Content Intent** on the Bot page.
3. **OAuth2 → URL Generator**: scopes `bot` + `applications.commands`; permissions
   `Send Messages`, `Embed Links`, `Attach Files`, `Read Message History`,
   `Use Slash Commands`. Open the URL and add the bot to your server.
4. With Developer Mode on, copy the guild ID, channel IDs and role IDs into `.env`.
5. Make sure the bot has **Send Messages / Embed Links / Attach Files** in the
   review, report and scoreboard channels.

### Register the slash commands

```bash
cd bot && npm run deploy      # re-run whenever a command's definition changes
```

## Environment reference

| Variable | Meaning |
| --- | --- |
| `DISCORD_BOT_TOKEN` | Bot token (secret) |
| `DISCORD_CLIENT_ID` | Application (client) ID |
| `DISCORD_GUILD_ID` | The server the bot serves |
| `DISCORD_REVIEW_CHANNEL_ID` | Where pending reports appear for the secretary |
| `DISCORD_REPORT_CHANNEL_ID` | Where approved reports are posted |
| `DISCORD_SCOREBOARD_CHANNEL_ID` | Where `/scoreboard` posts (falls back to the report channel if empty) |
| `DISCORD_SECRETARY_ROLE_ID` | Role allowed to review/manage |
| `DISCORD_LEADER_ROLE_ID` | Role allowed to submit reports |
| `DISCORD_RETIRED_ROLE_ID` | Members with this role are excluded from selection |
| `DISCORD_GAME_CATEGORY_ID` | Category the `❌-game-<n>` channels are created under |
| `INTERNAL_API_SECRET` | Shared secret between the bot and Laravel |
| `LARAVEL_API_URL` | Base URL the bot uses to reach Laravel (default `http://localhost:8000`) |

## Running in production

The recommended setup uses [PM2](https://pm2.keymetrics.io/) to keep both
processes alive and restart them on crash or reboot.

```bash
npm install -g pm2
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup            # follow the printed command to enable boot startup
pm2 logs               # tail logs
```

This runs the Laravel server on `127.0.0.1:8000` (matching the default
`LARAVEL_API_URL`) and the bot together. If you serve Laravel another way
(nginx/php-fpm, Herd, Octane), set `LARAVEL_API_URL` to that URL and remove the
`dictators-web` app from `ecosystem.config.cjs`.

Optional Laravel production tuning (re-run after any `.env` change):

```bash
php artisan config:cache
php artisan route:cache
```

## Discord commands

| Command | Who | What |
| --- | --- | --- |
| `/new-game` | Leader / Secretary | Pick players and create a private `❌-game-<n>` channel |
| `/report` | Leader / Secretary | Submit the report **inside a game channel** (number + players auto-detected) |
| `/new-season` | Secretary | Start the next 100-block season and reset the standings |
| `/set-current-game` | Secretary | Set the next game number |
| `/scoreboard` | Secretary | Render & post the scoreboard image (current season) |
| `/leaders` | Secretary | Post the "games per leader" list (current season) |
| `/challenges` | Everyone | List the available challenges |
| `/challenge-add` · `/challenge-remove` | Secretary | Manage challenges |
| `/tier-list` | Everyone | List tiers and their bonus points |
| `/tier-add` · `/tier-remove` | Secretary | Manage tiers |
| `/sync-players` | Secretary | Force a full re-sync of the member list |

Games are numbered per **season** (blocks of 100, e.g. 500–599). Each game gets a
number; `/report` inside that game's channel produces a report with the same
number. When a season fills up, the secretary runs `/new-season` for the next
block, which resets the scoreboard. The bot needs the **Manage Channels**
permission to create game channels.

Players are synced automatically on startup and as members join, leave, or change
name/roles.

## Maintenance

- **Reset the bot token** in the Developer Portal if it ever leaks, then update
  `DISCORD_BOT_TOKEN` and restart (`pm2 restart dictators-bot`).
- **Start with a clean database**: `php artisan migrate:fresh` (this wipes all
  reports, players and challenges; tiers are re-seeded).
- **Tests**: `php artisan test`.
