import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { isSecretary } from '../lib/members.js';
import { fetchChallenges, removeChallenge } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('challenge-remove')
  .setDescription('Remove a challenge (secretary only)')
  .addStringOption((option) =>
    option
      .setName('challenge')
      .setDescription('Challenge to remove (type to search)')
      .setRequired(true)
      .setAutocomplete(true),
  );

export async function autocomplete(interaction) {
  const focused = interaction.options.getFocused().toLowerCase();
  const challenges = await fetchChallenges().catch(() => []);

  const choices = challenges
    .filter((c) => c.name.toLowerCase().includes(focused))
    .slice(0, 25)
    .map((c) => ({ name: `${c.name} · ${c.tier} (+${c.points})`.slice(0, 100), value: String(c.id) }));

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

  const value = interaction.options.getString('challenge');
  const challenges = await fetchChallenges().catch(() => []);
  const challenge = challenges.find((c) => String(c.id) === value);

  if (!challenge) {
    await interaction.editReply('❌ That challenge no longer exists.');
    return;
  }

  try {
    await removeChallenge(challenge.id);
    await interaction.editReply(`🗑️ Challenge **${challenge.name}** removed (existing reports keep their bonus).`);
  } catch (error) {
    await interaction.editReply(`❌ Could not remove: ${error?.response?.data?.message ?? error.message}`);
  }
}
