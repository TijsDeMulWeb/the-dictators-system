import {
  ActionRowBuilder,
  AttachmentBuilder,
  ButtonBuilder,
  ButtonStyle,
  MessageFlags,
  ModalBuilder,
  TextInputBuilder,
  TextInputStyle,
} from 'discord.js';
import { config } from '../config.js';
import { createReport, upsertPlayers } from '../api.js';
import { deleteDraft, getDraft } from '../session.js';
import { renderReportCard } from '../render.js';
import { reviewEmbed } from '../lib/embeds.js';
import { fetchImageBuffer } from '../lib/http.js';

/**
 * Build the points modal, pre-filled with the game's players.
 */
export function buildPointsModal(token, title, selectedPlayers) {
  const prefill = selectedPlayers.map((p) => `${p.display_name} | | `).join('\n');

  return new ModalBuilder()
    .setCustomId(`report:modal:${token}`)
    .setTitle(title)
    .addComponents(
      new ActionRowBuilder().addComponents(
        new TextInputBuilder()
          .setCustomId('entries')
          .setLabel('Per line: Name | Country | Points')
          .setPlaceholder('Tijs | Dr. Congo | 1660')
          .setStyle(TextInputStyle.Paragraph)
          .setValue(prefill)
          .setRequired(true),
      ),
    );
}

/**
 * Points modal submitted: build the report, render the card, post for review.
 */
export async function handleModalSubmit(interaction, token) {
  const draft = getDraft(token);
  if (!draft) {
    await expired(interaction);
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const players = parseEntries(interaction.fields.getTextInputValue('entries'), draft.selectedPlayers);

  if (players.length === 0) {
    await interaction.editReply('❌ Could not read any player entries. Please try again.');
    return;
  }

  // Safety net: make sure every player (and the leader) exists in the DB before
  // the report references them, in case they were not synced yet.
  const leaderMember = interaction.member;
  const toUpsert = [...draft.selectedPlayers];
  if (leaderMember && ! toUpsert.some((p) => p.discord_id === draft.leaderId)) {
    toUpsert.push({
      discord_id: draft.leaderId,
      display_name: leaderMember.displayName ?? leaderMember.user?.username ?? 'Leader',
      username: leaderMember.user?.username ?? 'leader',
      avatar_url: leaderMember.user?.displayAvatarURL?.({ size: 128 }) ?? null,
      is_retired: false,
    });
  }
  await upsertPlayers(toUpsert).catch(() => {});

  let report;
  try {
    report = await createReport({
      leader_discord_id: draft.leaderId,
      game_id: draft.gameId,
      game: draft.game,
      day: draft.day,
      challenge_id: draft.challengeId,
      result: draft.result,
      ingame_screenshot_url: draft.screenshotUrl,
      players,
    });
  } catch (error) {
    await interaction.editReply(`❌ Failed to save the report: ${apiError(error)}`);
    return;
  }

  const cardName = `report-${report.report_number}.png`;
  const card = await renderReportCard(report.id);
  const cardFile = new AttachmentBuilder(card, { name: cardName });
  const ingame = new AttachmentBuilder(await fetchImageBuffer(draft.screenshotUrl), { name: 'ingame.png' });

  const reviewChannel = await interaction.client.channels.fetch(config.reviewChannelId);
  const buttons = new ActionRowBuilder().addComponents(
    new ButtonBuilder()
      .setCustomId(`review:approve:${report.id}`)
      .setLabel('Approve')
      .setStyle(ButtonStyle.Success),
    new ButtonBuilder()
      .setCustomId(`review:reject:${report.id}`)
      .setLabel('Reject')
      .setStyle(ButtonStyle.Danger),
  );

  const message = await reviewChannel.send({
    embeds: [reviewEmbed(report, `<@${draft.leaderId}>`).setImage(`attachment://${cardName}`)],
    files: [cardFile, ingame],
    components: [buttons],
  });

  deleteDraft(token);

  await interaction.editReply(
    `✅ Report **#${report.report_number}** submitted for review in <#${config.reviewChannelId}> (message ${message.id}).`,
  );
}

/**
 * Parse the textarea ("Name | Country | Points" per line), matching each line
 * to a player by its leading name (falling back to position).
 */
function parseEntries(raw, selectedPlayers) {
  const lines = raw
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean);

  const byName = new Map(selectedPlayers.map((p) => [p.display_name.toLowerCase(), p.discord_id]));

  return lines
    .map((line, index) => {
      const [namePart = '', countryPart = '', pointsPart = ''] = line.split('|').map((s) => s.trim());
      const discordId = byName.get(namePart.toLowerCase()) ?? selectedPlayers[index]?.discord_id;
      if (!discordId) {
        return null;
      }

      const digits = (pointsPart.match(/\d+/g) ?? []).join('');

      return {
        discord_id: discordId,
        country: countryPart || null,
        points: digits ? parseInt(digits, 10) : 0,
      };
    })
    .filter(Boolean);
}

export function apiError(error) {
  return error?.response?.data?.message ?? error.message ?? 'unknown error';
}

async function expired(interaction) {
  const payload = { content: '⌛ This report session expired. Please start again with `/report`.', components: [] };
  if (interaction.isModalSubmit() || interaction.deferred || interaction.replied) {
    await interaction.reply({ ...payload, flags: MessageFlags.Ephemeral }).catch(() => {});
  } else {
    await interaction.update(payload).catch(() => {});
  }
}
