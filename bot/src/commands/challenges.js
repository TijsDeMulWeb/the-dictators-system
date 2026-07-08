import { EmbedBuilder, MessageFlags, SlashCommandBuilder } from 'discord.js';
import { fetchChallenges } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('challenges')
  .setDescription('List the available challenges');

export async function execute(interaction) {
  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const challenges = await fetchChallenges().catch(() => []);

  if (challenges.length === 0) {
    await interaction.editReply('No challenges yet. A secretary can add one with `/challenge-add`.');
    return;
  }

  // Group by tier, ordered by tier points (highest first).
  const tiers = new Map();
  for (const challenge of challenges) {
    if (!tiers.has(challenge.tier)) {
      tiers.set(challenge.tier, { points: challenge.points, names: [] });
    }
    tiers.get(challenge.tier).names.push(challenge.name);
  }

  const embed = new EmbedBuilder().setTitle('🏅 Challenges').setColor(0xd08700);

  const ordered = [...tiers.entries()].sort((a, b) => b[1].points - a[1].points);
  for (const [tier, info] of ordered) {
    embed.addFields({
      name: `${tier[0].toUpperCase()}${tier.slice(1)} (+${info.points})`,
      value: info.names.map((n) => `• ${n}`).join('\n'),
      inline: false,
    });
  }

  await interaction.editReply({ embeds: [embed] });
}
