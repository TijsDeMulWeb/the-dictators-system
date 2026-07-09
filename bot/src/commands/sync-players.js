import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { isSecretary } from '../lib/members.js';
import { syncGuildPlayers } from '../lib/players.js';

export const data = new SlashCommandBuilder()
  .setName('sync-players')
  .setDescription('Sync the Discord members into the player list (secretary only)');

export async function execute(interaction) {
  if (!isSecretary(interaction)) {
    await interaction.reply({
      content: '⛔ Only the General Secretary can sync players.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  await interaction.deferReply({ flags: MessageFlags.Ephemeral });

  try {
    const count = await syncGuildPlayers(interaction.guild);
    await interaction.editReply(`✅ Synced **${count}** players (members with the Retired role are flagged out).`);
  } catch (error) {
    await interaction.editReply(`❌ Sync failed: ${error?.response?.data?.message ?? error.message}`);
  }
}
