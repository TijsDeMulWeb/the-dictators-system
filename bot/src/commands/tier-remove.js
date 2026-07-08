import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { fetchTiers, removeTier } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('tier-remove')
  .setDescription('Remove a tier (secretary only)')
  .addStringOption((option) =>
    option
      .setName('tier')
      .setDescription('Tier to remove (type to search)')
      .setRequired(true)
      .setAutocomplete(true),
  );

export async function autocomplete(interaction) {
  const focused = interaction.options.getFocused().toLowerCase();
  const tiers = await fetchTiers().catch(() => []);

  const choices = tiers
    .filter((t) => t.name.toLowerCase().includes(focused))
    .slice(0, 25)
    .map((t) => ({ name: `${t.name} (+${t.points})`.slice(0, 100), value: String(t.id) }));

  await interaction.respond(choices);
}

export async function execute(interaction) {
  if (!interaction.member?.roles?.cache?.has(config.secretaryRoleId)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can manage tiers.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const value = interaction.options.getString('tier');
  const tiers = await fetchTiers().catch(() => []);
  const tier = tiers.find((t) => String(t.id) === value);

  if (!tier) {
    await interaction.editReply('❌ That tier no longer exists.');
    return;
  }

  try {
    await removeTier(tier.id);
    await interaction.editReply(
      `🗑️ Tier **${tier.name}** removed. Existing challenges keep their snapshotted points.`,
    );
  } catch (error) {
    await interaction.editReply(`❌ Could not remove: ${error?.response?.data?.message ?? error.message}`);
  }
}
