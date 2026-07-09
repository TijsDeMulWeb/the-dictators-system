import {
  ActionRowBuilder,
  ButtonBuilder,
  ButtonStyle,
  MessageFlags,
  SlashCommandBuilder,
  UserSelectMenuBuilder,
} from 'discord.js';
import { createDraft } from '../session.js';
import { isLeaderOrSecretary } from '../lib/members.js';

export const data = new SlashCommandBuilder()
  .setName('new-game')
  .setDescription('Start a new game and create its private channel');

export async function execute(interaction) {
  if (! isLeaderOrSecretary(interaction)) {
    await interaction.reply({
      content: '⛔ Only Leaders (or the General Secretary) can start games.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  const token = createDraft({ creatorId: interaction.user.id, selectedPlayers: [] });

  const menu = new UserSelectMenuBuilder()
    .setCustomId(`newgame:users:${token}`)
    .setPlaceholder('Search and pick the players for this game…')
    .setMinValues(1)
    .setMaxValues(25);

  const buttons = new ActionRowBuilder().addComponents(
    new ButtonBuilder().setCustomId(`newgame:continue:${token}`).setLabel('Create game').setStyle(ButtonStyle.Success),
    new ButtonBuilder().setCustomId(`newgame:cancel:${token}`).setLabel('Cancel').setStyle(ButtonStyle.Secondary),
  );

  await interaction.reply({
    content: '**Pick the players for this game** (type to search), then press **Create game**.',
    components: [new ActionRowBuilder().addComponents(menu), buttons],
    flags: MessageFlags.Ephemeral,
  });
}
