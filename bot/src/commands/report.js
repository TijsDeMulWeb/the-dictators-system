import { MessageFlags, SlashCommandBuilder } from 'discord.js';
import { config } from '../config.js';
import { fetchChallenges, getGameByChannel } from '../api.js';
import { createDraft } from '../session.js';
import { buildPointsModal } from '../interactions/report-flow.js';

export const data = new SlashCommandBuilder()
  .setName('report')
  .setDescription('Submit the report for this game (run inside the game channel)')
  .addAttachmentOption((option) =>
    option.setName('screenshot').setDescription('In-game result screenshot').setRequired(true),
  )
  .addStringOption((option) =>
    option
      .setName('result')
      .setDescription('Did the team win or lose?')
      .setRequired(true)
      .addChoices({ name: 'Win', value: 'win' }, { name: 'Loss', value: 'loss' }),
  )
  .addIntegerOption((option) =>
    option.setName('day').setDescription('In-game day number').setMinValue(0),
  )
  .addStringOption((option) =>
    option.setName('map').setDescription('Map / theatre (default: Asia)'),
  )
  .addStringOption((option) =>
    option
      .setName('challenge')
      .setDescription('This game was a challenge (type to search)')
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
  const roles = interaction.member?.roles?.cache;
  if (! (roles?.has(config.leaderRoleId) || roles?.has(config.secretaryRoleId))) {
    await interaction.reply({
      content: '⛔ Only Leaders (or the General Secretary) can submit reports.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  // /report only works inside a game channel created by /new-game.
  const game = await getGameByChannel(interaction.channelId).catch(() => null);
  if (! game) {
    await interaction.reply({
      content: '❌ Use `/report` inside a game channel created with `/new-game`.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }
  if (game.has_report) {
    await interaction.reply({
      content: `❌ Game #${game.number} already has a report.`,
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  const attachment = interaction.options.getAttachment('screenshot');
  if (! attachment.contentType?.startsWith('image/')) {
    await interaction.reply({
      content: '❌ The screenshot must be an image file.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  // Resolve the optional challenge (autocomplete value is its id).
  let challenge = null;
  const challengeInput = interaction.options.getString('challenge');
  if (challengeInput) {
    const challenges = await fetchChallenges().catch(() => []);
    challenge =
      challenges.find((c) => String(c.id) === challengeInput) ??
      challenges.find((c) => c.name.toLowerCase() === challengeInput.toLowerCase()) ??
      null;

    if (! challenge) {
      await interaction.reply({
        content: `❌ Unknown challenge "${challengeInput}". Pick one from the list or ask a secretary to add it.`,
        flags: MessageFlags.Ephemeral,
      });
      return;
    }
  }

  const result = interaction.options.getString('result');
  const token = createDraft({
    leaderId: interaction.user.id,
    gameId: game.id,
    screenshotUrl: attachment.url,
    result,
    day: interaction.options.getInteger('day'),
    game: interaction.options.getString('map') ?? 'Asia',
    challengeId: challenge?.id ?? null,
    selectedPlayers: game.players,
  });

  const title = `Report #${game.number} — ${result === 'win' ? 'Win' : 'Loss'}`;
  await interaction.showModal(buildPointsModal(token, title, game.players));
}
