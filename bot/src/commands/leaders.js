import { AttachmentBuilder, MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { renderLeaders } from '../render.js';

export const data = new SlashCommandBuilder()
  .setName('leaders')
  .setDescription('Show how many games each leader has led (secretary only)');

export async function execute(interaction) {
  if (!interaction.member?.roles?.cache?.has(config.secretaryRoleId)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can view leader stats.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply();

  const image = await renderLeaders();
  const file = new AttachmentBuilder(image, { name: 'leaders.png' });

  await interaction.editReply({ files: [file] });
}
