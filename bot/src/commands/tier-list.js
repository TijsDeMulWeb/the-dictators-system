import { EmbedBuilder, MessageFlags, SlashCommandBuilder } from 'discord.js';
import { fetchTiers } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('tier-list')
  .setDescription('List the tiers and their bonus points');

export async function execute(interaction) {
  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const tiers = await fetchTiers().catch(() => []);

  if (tiers.length === 0) {
    await interaction.editReply('No tiers yet. A secretary can add one with `/tier-add`.');
    return;
  }

  const lines = tiers.map((t) => `**${t.name}** — +${t.points}`).join('\n');
  const embed = new EmbedBuilder().setTitle('🏆 Tiers').setColor(0xd08700).setDescription(lines);

  await interaction.editReply({ embeds: [embed] });
}
