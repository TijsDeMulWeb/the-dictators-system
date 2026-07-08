import { AttachmentBuilder, MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { renderScoreboard } from '../render.js';

export const data = new SlashCommandBuilder()
  .setName('scoreboard')
  .setDescription('Render and post the latest scoreboard (secretary only)');

export async function execute(interaction) {
  if (!interaction.member?.roles?.cache?.has(config.secretaryRoleId)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can post the scoreboard.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const image = await renderScoreboard();
  const file = new AttachmentBuilder(image, { name: 'scoreboard.png' });

  const scoreboardChannel = await interaction.client.channels.fetch(config.scoreboardChannelId);
  await scoreboardChannel.send({ files: [file] });

  await interaction.editReply(`✅ Scoreboard posted to <#${config.scoreboardChannelId}>.`);
}
