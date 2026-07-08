import {
  ActionRowBuilder,
  ButtonBuilder,
  ButtonStyle,
  MessageFlags,
  SlashCommandBuilder,
  UserSelectMenuBuilder,
} from 'discord.js';
import { config } from '../config.js';
import { fetchChallenges } from '../api.js';
import { createDraft } from '../session.js';

export const data = new SlashCommandBuilder()
  .setName('report')
  .setDescription('Submit a game report for secretary review')
  .addAttachmentOption((option) =>
    option.setName('screenshot').setDescription('In-game result screenshot').setRequired(true),
  )
  .addStringOption((option) =>
    option
      .setName('result')
      .setDescription('Did you win or lose?')
      .setRequired(true)
      .addChoices({ name: 'Win', value: 'win' }, { name: 'Loss', value: 'loss' }),
  )
  .addIntegerOption((option) =>
    option.setName('day').setDescription('In-game day number').setMinValue(0),
  )
  .addStringOption((option) =>
    option.setName('game').setDescription('Game / map (default: Asia)'),
  )
  .addStringOption((option) =>
    option
      .setName('challenge')
      .setDescription('Play a challenge instead of a normal game (type to search)')
      .setAutocomplete(true),
  );

/**
 * Autocomplete the `challenge` option from the active challenge list.
 */
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
  const roles = interaction.member?.roles?.cache;
  const allowed = roles?.has(config.leaderRoleId) || roles?.has(config.secretaryRoleId);

  if (!allowed) {
    await interaction.reply({
      content: '⛔ Only Leaders (or the General Secretary) can submit reports.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  const attachment = interaction.options.getAttachment('screenshot');

  if (!attachment.contentType?.startsWith('image/')) {
    await interaction.reply({
      content: '❌ The screenshot must be an image file.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  // Resolve the optional challenge (autocomplete value is its id; a free-typed
  // name is matched too).
  let challenge = null;
  const challengeInput = interaction.options.getString('challenge');
  if (challengeInput) {
    const challenges = await fetchChallenges().catch(() => []);
    challenge =
      challenges.find((c) => String(c.id) === challengeInput) ??
      challenges.find((c) => c.name.toLowerCase() === challengeInput.toLowerCase()) ??
      null;

    if (!challenge) {
      await interaction.reply({
        content: `❌ Unknown challenge "${challengeInput}". Pick one from the list or ask a secretary to add it.`,
        flags: MessageFlags.Ephemeral,
      });
      return;
    }
  }

  const token = createDraft({
    leaderId: interaction.user.id,
    screenshotUrl: attachment.url,
    result: interaction.options.getString('result'),
    day: interaction.options.getInteger('day'),
    game: interaction.options.getString('game') ?? 'Asia',
    challengeId: challenge?.id ?? null,
    challengeLabel: challenge ? `${challenge.name} (+${challenge.points})` : null,
    selectedPlayers: [],
  });

  const menu = new UserSelectMenuBuilder()
    .setCustomId(`report:users:${token}`)
    .setPlaceholder('Search and pick the players who took part…')
    .setMinValues(1)
    .setMaxValues(25);

  const buttons = new ActionRowBuilder().addComponents(
    new ButtonBuilder().setCustomId(`report:continue:${token}`).setLabel('Continue').setStyle(ButtonStyle.Success),
    new ButtonBuilder().setCustomId(`report:cancel:${token}`).setLabel('Cancel').setStyle(ButtonStyle.Secondary),
  );

  const header = challenge
    ? `🏅 **Challenge: ${challenge.name}** (+${challenge.points} each)\n`
    : '';

  await interaction.reply({
    content: `${header}**Pick the players who took part** (type to search), then press **Continue**.`,
    components: [new ActionRowBuilder().addComponents(menu), buttons],
    flags: MessageFlags.Ephemeral,
  });
}
