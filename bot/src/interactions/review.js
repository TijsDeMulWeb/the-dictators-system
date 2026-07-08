import {
  ActionRowBuilder,
  AttachmentBuilder,
  MessageFlags,
  ModalBuilder,
  TextInputBuilder,
  TextInputStyle,
} from 'discord.js';
import { config } from '../config.js';
import { approveReport, getReport, rejectReport } from '../api.js';
import { renderReportCard } from '../render.js';
import { officialEmbed, statusEmbedFrom } from '../lib/embeds.js';
import { fetchImageBuffer } from '../lib/http.js';

function isSecretary(interaction) {
  return interaction.member?.roles?.cache?.has(config.secretaryRoleId) ?? false;
}

async function denyNonSecretary(interaction) {
  await interaction.reply({
    content: '⛔ Only the General Secretary can review reports.',
    flags: MessageFlags.Ephemeral,
  });
}

export async function handleApprove(interaction, reportId) {
  if (!isSecretary(interaction)) {
    await denyNonSecretary(interaction);
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  let report;
  try {
    report = await getReport(reportId);
  } catch (error) {
    await interaction.editReply(`❌ Could not load report: ${apiError(error)}`);
    return;
  }

  if (report.status !== 'pending') {
    await interaction.editReply(`⚠️ Report #${report.report_number} is already ${report.status}.`);
    return;
  }

  // Post to the official channel FIRST; only mark approved if that succeeds,
  // so a permission/network failure leaves the report re-approvable.
  const cardName = `report-${report.report_number}.png`;
  const card = await renderReportCard(report.id);
  const files = [new AttachmentBuilder(card, { name: cardName })];
  if (report.ingame_screenshot_url) {
    try {
      files.push(new AttachmentBuilder(await fetchImageBuffer(report.ingame_screenshot_url), { name: 'ingame.png' }));
    } catch {
      // If the screenshot can't be fetched, still post the card.
    }
  }

  let posted;
  try {
    const reportChannel = await interaction.client.channels.fetch(config.reportChannelId);
    posted = await reportChannel.send({
      embeds: [officialEmbed(report).setImage(`attachment://${cardName}`)],
      files,
    });
  } catch (error) {
    await interaction.editReply(
      `❌ Could not post to the report channel: ${error.message}. The report is still pending — check the bot's channel permissions and try Approve again.`,
    );
    return;
  }

  await approveReport(reportId, interaction.user.id, posted.id).catch(() => {});

  await finalizeReviewMessage(interaction, 'approved', `<@${interaction.user.id}>`);
  await interaction.editReply(`✅ Report #${report.report_number} approved and posted to <#${config.reportChannelId}>.`);
}

export async function handleRejectButton(interaction, reportId) {
  if (!isSecretary(interaction)) {
    await denyNonSecretary(interaction);
    return;
  }

  const modal = new ModalBuilder()
    .setCustomId(`review:rejectmodal:${reportId}`)
    .setTitle('Reject report')
    .addComponents(
      new ActionRowBuilder().addComponents(
        new TextInputBuilder()
          .setCustomId('reason')
          .setLabel('Reason (optional)')
          .setStyle(TextInputStyle.Paragraph)
          .setRequired(false),
      ),
    );

  await interaction.showModal(modal);
}

export async function handleRejectSubmit(interaction, reportId) {
  if (!isSecretary(interaction)) {
    await denyNonSecretary(interaction);
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const reason = interaction.fields.getTextInputValue('reason')?.trim() || null;

  let report;
  try {
    report = await rejectReport(reportId, interaction.user.id, reason);
  } catch (error) {
    await interaction.editReply(`❌ Could not reject: ${apiError(error)}`);
    return;
  }

  await finalizeReviewMessage(interaction, 'rejected', `<@${interaction.user.id}>`, reason);
  await interaction.editReply(`❌ Report #${report.report_number} rejected.`);
}

/**
 * Disable the buttons on the original review message and recolor its embed.
 */
async function finalizeReviewMessage(interaction, status, reviewerTag, note) {
  const message = interaction.message;
  if (!message) {
    return;
  }

  const embed = message.embeds[0]
    ? statusEmbedFrom(message.embeds[0], status, reviewerTag, note)
    : undefined;

  await message
    .edit({ embeds: embed ? [embed] : message.embeds, components: [] })
    .catch(() => {});
}

function apiError(error) {
  return error?.response?.data?.message ?? error.message ?? 'unknown error';
}
