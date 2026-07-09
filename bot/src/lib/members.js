/**
 * Whether a selected member has the given role. Handles both the full
 * GuildMember shape (roles.cache) and the resolved API shape (roles array).
 */
export function memberHasRole(member, roleId) {
  if (!member) {
    return false;
  }
  if (member.roles?.cache) {
    return member.roles.cache.has(roleId);
  }
  if (Array.isArray(member.roles)) {
    return member.roles.includes(roleId);
  }
  return false;
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
