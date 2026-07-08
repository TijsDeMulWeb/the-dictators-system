import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import dotenv from 'dotenv';

const here = dirname(fileURLToPath(import.meta.url));

// Single source of truth: the Laravel root .env one level above bot/.
dotenv.config({ path: resolve(here, '../../.env') });

function required(key) {
  const value = process.env[key];
  if (!value) {
    throw new Error(`Missing required env var: ${key}`);
  }
  return value;
}

export const config = {
  token: required('DISCORD_BOT_TOKEN'),
  clientId: required('DISCORD_CLIENT_ID'),
  guildId: required('DISCORD_GUILD_ID'),
  reviewChannelId: required('DISCORD_REVIEW_CHANNEL_ID'),
  reportChannelId: required('DISCORD_REPORT_CHANNEL_ID'),
  // Optional: falls back to the report channel until a dedicated one is set.
  scoreboardChannelId: process.env.DISCORD_SCOREBOARD_CHANNEL_ID || required('DISCORD_REPORT_CHANNEL_ID'),
  secretaryRoleId: required('DISCORD_SECRETARY_ROLE_ID'),
  leaderRoleId: required('DISCORD_LEADER_ROLE_ID'),
  retiredRoleId: required('DISCORD_RETIRED_ROLE_ID'),
  apiUrl: (process.env.LARAVEL_API_URL || 'http://localhost:8000').replace(/\/$/, ''),
  apiSecret: required('INTERNAL_API_SECRET'),
};
