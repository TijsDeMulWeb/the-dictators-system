import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { isSecretary } from '../lib/members.js';
import { addTier } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('tier-add')
  .setDescription('Add or update a tier and its bonus points (secretary only)')
  .addStringOption((option) =>
    option.setName('name').setDescription('Tier name (e.g. Diamond)').setRequired(true).setMaxLength(50),
  )
  .addIntegerOption((option) =>
    option.setName('points').setDescription('Bonus points for this tier').setRequired(true).setMinValue(0),
  );

export async function execute(interaction) {
  if (!isSecretary(interaction)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can manage tiers.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const name = interaction.options.getString('name').trim();
  const points = interaction.options.getInteger('points');

  try {
    const tier = await addTier(name, points);
    await interaction.editReply(`✅ Tier **${tier.name}** saved (+${tier.points} each).`);
  } catch (error) {
    await interaction.editReply(`❌ Could not save tier: ${error?.response?.data?.message ?? error.message}`);
  }
}
