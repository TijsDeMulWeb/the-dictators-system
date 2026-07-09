import { ChannelType, MessageFlags, PermissionFlagsBits } from 'discord.js';
import { config } from '../config.js';
import { createGame, setGameChannel, upsertPlayers } from '../api.js';
import { deleteDraft, getDraft, updateDraft } from '../session.js';
import { collectSelectedPlayers } from '../lib/members.js';
import { apiError } from './report-flow.js';

/**
 * Players picked in the /new-game user-select.
 */
export async function handleGameUserSelect(interaction, token) {
  const draft = getDraft(token);
  if (! draft) {
    await expired(interaction);
    return;
  }

  const { kept, droppedRetired } = collectSelectedPlayers(interaction, config.retiredRoleId);
  updateDraft(token, { selectedPlayers: kept, droppedRetired });

  await interaction.deferUpdate();
}

export async function handleGameCancel(interaction, token) {
  deleteDraft(token);
  await interaction.update({ content: '🗑️ Game creation cancelled.', components: [] });
}

/**
 * Create game pressed: reserve the number, create the private channel, ping.
 */
export async function handleGameContinue(interaction, token) {
  const draft = getDraft(token);
  if (! draft) {
    await expired(interaction);
    return;
  }

  const players = draft.selectedPlayers ?? [];
  if (players.length === 0) {
    await interaction.reply({
      content: draft.droppedRetired
        ? '❌ Everyone you picked is Retired. Pick at least one active player.'
        : '❌ Pick at least one player before continuing.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  await upsertPlayers(players).catch(() => {});

  let game;
  try {
    game = await createGame(players.map((p) => p.discord_id), draft.creatorId);
  } catch (error) {
    await interaction.editReply(`❌ Could not start the game: ${apiError(error)}`);
    return;
  }

  let channel;
  try {
    channel = await interaction.guild.channels.create({
      name: `❌-game-${game.number}`,
      type: ChannelType.GuildText,
      parent: config.gameCategoryId,
      permissionOverwrites: buildOverwrites(interaction, players, draft.creatorId),
    });
  } catch (error) {
    await interaction.editReply(
      `⚠️ Game **#${game.number}** is created, but I couldn't make the channel: ${error.message}. Does the bot have "Manage Channels"?`,
    );
    return;
  }

  await setGameChannel(game.id, channel.id).catch(() => {});

  const mentions = players.map((p) => `<@${p.discord_id}>`).join(' ');
  await channel
    .send(`🎮 **Game #${game.number}** — ${mentions}\nGood luck! When the game is done, run \`/report\` here.`)
    .catch(() => {});

  deleteDraft(token);
  await interaction.editReply(`✅ Game **#${game.number}** created: ${channel}`);
}

/**
 * Private channel: deny everyone, allow the picked players, the secretary role,
 * the creator and the bot.
 */
function buildOverwrites(interaction, players, creatorId) {
  const allow = [PermissionFlagsBits.ViewChannel, PermissionFlagsBits.SendMessages];

  const overwrites = [
    { id: interaction.guild.roles.everyone.id, deny: [PermissionFlagsBits.ViewChannel] },
    { id: interaction.client.user.id, allow },
    { id: config.secretaryRoleId, allow },
  ];

  const memberIds = new Set([creatorId, ...players.map((p) => p.discord_id)]);
  for (const id of memberIds) {
    overwrites.push({ id, allow });
  }

  return overwrites;
}

async function expired(interaction) {
  const payload = { content: '⌛ This session expired. Please start again with `/new-game`.', components: [] };
  if (interaction.deferred || interaction.replied) {
    await interaction.reply({ ...payload, flags: MessageFlags.Ephemeral }).catch(() => {});
  } else {
    await interaction.update(payload).catch(() => {});
  }
}
