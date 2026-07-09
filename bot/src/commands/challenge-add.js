import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { isSecretary } from '../lib/members.js';
import { addChallenge, fetchTiers } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('challenge-add')
  .setDescription('Add or update a challenge (secretary only)')
  .addStringOption((option) =>
    option.setName('name').setDescription('Challenge name').setRequired(true).setMaxLength(100),
  )
  .addStringOption((option) =>
    option
      .setName('tier')
      .setDescription('Tier (sets the bonus points) — type to search')
      .setRequired(true)
      .setAutocomplete(true),
  );

export async function autocomplete(interaction) {
  const focused = interaction.options.getFocused().toLowerCase();
  const tiers = await fetchTiers().catch(() => []);

  const choices = tiers
    .filter((t) => t.name.toLowerCase().includes(focused))
    .slice(0, 25)
    .map((t) => ({ name: `${t.name} (+${t.points})`.slice(0, 100), value: t.name }));

  await interaction.respond(choices);
}

export async function execute(interaction) {
  if (!isSecretary(interaction)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can manage challenges.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const name = interaction.options.getString('name').trim();
  const tier = interaction.options.getString('tier');

  try {
    const challenge = await addChallenge(name, tier);
    await interaction.editReply(
      `✅ Challenge **${challenge.name}** saved as **${challenge.tier}** (+${challenge.points} each).`,
    );
  } catch (error) {
    await interaction.editReply(`❌ Could not save challenge: ${error?.response?.data?.message ?? error.message}`);
  }
}
