import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { isSecretary } from '../lib/members.js';
import { setCurrentGameNumber } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('set-current-game')
  .setDescription('Set the next game number (secretary only)')
  .addIntegerOption((option) =>
    option.setName('number').setDescription('The number the next game should get').setRequired(true).setMinValue(1),
  );

export async function execute(interaction) {
  if (! isSecretary(interaction)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can change the game number.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  const number = interaction.options.getInteger('number');

  try {
    const season = await setCurrentGameNumber(number);
    await interaction.editReply(
      `✅ Next game will be **#${season.next_number}** (season ${season.base}–${season.last_number}).`,
    );
  } catch (error) {
    await interaction.editReply(`❌ Could not set the number: ${error?.response?.data?.message ?? error.message}`);
  }
}
