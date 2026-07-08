/**
 * PM2 process manager config — runs the Laravel API/render server and the
 * Discord bot together, with auto-restart. See README.md for usage.
 *
 *   pm2 start ecosystem.config.cjs
 *   pm2 logs
 *   pm2 save && pm2 startup   # keep running across reboots
 */
const path = require('node:path');

module.exports = {
  apps: [
    {
      name: 'dictators-web',
      cwd: __dirname,
      script: 'artisan',
      args: 'serve --host=127.0.0.1 --port=8000',
      interpreter: 'php',
      autorestart: true,
      max_restarts: 20,
      restart_delay: 2000,
    },
    {
      name: 'dictators-bot',
      cwd: path.join(__dirname, 'bot'),
      script: 'src/index.js',
      interpreter: 'node',
      autorestart: true,
      max_restarts: 20,
      restart_delay: 3000,
    },
  ],
};
