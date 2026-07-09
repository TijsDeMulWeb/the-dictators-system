import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { isSecretary } from '../lib/members.js';
import { startNewSeason } from '../api.js';

export const data = new SlashCommandBuilder()
  .setName('new-season')
  .setDescription('Start the next season and reset the standings (secretary only)');

export async function execute(interaction) {
  if (! isSecretary(interaction)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can start a new season.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  try {
    const season = await startNewSeason();
    await interaction.editReply(
      `✅ New season started: games **${season.base}–${season.last_number}**. The scoreboard now starts fresh.`,
    );
  } catch (error) {
    await interaction.editReply(`❌ Could not start a new season: ${error?.response?.data?.message ?? error.message}`);
  }
}
