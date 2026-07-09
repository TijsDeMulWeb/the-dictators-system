import { config } from '../config.js';

/**
 * Whether a member has the given role. Prefers the raw role-id list
 * (member._roles on a GuildMember, or member.roles on a resolved API member)
 * because roles.cache depends on the guild's roles being cached, which is not
 * guaranteed and silently drops roles.
 */
export function memberHasRole(member, roleId) {
  if (!member) {
    return false;
  }
  if (Array.isArray(member._roles)) {
    return member._roles.includes(roleId);
  }
  if (Array.isArray(member.roles)) {
    return member.roles.includes(roleId);
  }
  if (member.roles?.cache) {
    return member.roles.cache.has(roleId);
  }
  return false;
}

/**
 * Whether the interaction's member is the General Secretary.
 */
export function isSecretary(interaction) {
  return memberHasRole(interaction.member, config.secretaryRoleId);
}

/**
 * Whether the interaction's member may submit reports / start games.
 */
export function isLeaderOrSecretary(interaction) {
  return (
    memberHasRole(interaction.member, config.leaderRoleId) ||
    memberHasRole(interaction.member, config.secretaryRoleId)
  );
}

export function displayNameOf(member, user) {
  return member?.displayName ?? member?.nick ?? user?.globalName ?? user?.username ?? 'Unknown';
}

/**
 * Turn a user-select interaction's picks into player records, dropping bots and
 * anyone with the Retired role.
 *
 * @returns {{ kept: Array<object>, droppedRetired: number }}
 */
export function collectSelectedPlayers(interaction, retiredRoleId) {
  const kept = [];
  let droppedRetired = 0;

  for (const userId of interaction.values) {
    const user = interaction.users.get(userId);
    const member = interaction.members.get(userId);

    if (user?.bot) {
      continue;
    }
    if (memberHasRole(member, retiredRoleId)) {
      droppedRetired += 1;
      continue;
    }

    kept.push({
      discord_id: userId,
      display_name: displayNameOf(member, user),
      username: user?.username ?? 'unknown',
      avatar_url: user?.displayAvatarURL?.({ size: 128 }) ?? null,
      is_retired: false,
    });
  }

  return { kept, droppedRetired };
}
