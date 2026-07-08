import { config } from '../config.js';
import { syncPlayers } from '../api.js';

/**
 * Map a single guild member to the Laravel player shape.
 */
export function mapMember(member) {
  return {
    discord_id: member.id,
    username: member.user.username,
    display_name: member.displayName,
    avatar_url: member.user.displayAvatarURL({ size: 128 }),
    is_retired: member.roles.cache.has(config.retiredRoleId),
  };
}

/**
 * Fetch every (non-bot) guild member and map them to the Laravel player shape,
 * flagging anyone with the Retired role.
 */
export async function collectGuildMembers(guild) {
  const members = await guild.members.fetch();

  return members.filter((member) => !member.user.bot).map(mapMember);
}

/**
 * Fetch guild members and push them to Laravel. Returns the count synced.
 */
export async function syncGuildPlayers(guild) {
  const members = await collectGuildMembers(guild);
  return syncPlayers(members);
}
