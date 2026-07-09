import { Client, Events, GatewayIntentBits } from 'discord.js';
import { config } from './config.js';
import { commandMap } from './commands/index.js';
import { mapMember, syncGuildPlayers } from './lib/players.js';
import { deactivatePlayer, upsertPlayers } from './api.js';
import { closeBrowser } from './render.js';
import { handleModalSubmit } from './interactions/report-flow.js';
import {
  handleGameCancel,
  handleGameContinue,
  handleGameUserSelect,
} from './interactions/game-flow.js';
import {
  handleApprove,
  handleRejectButton,
  handleRejectSubmit,
} from './interactions/review.js';

const client = new Client({
  intents: [GatewayIntentBits.Guilds, GatewayIntentBits.GuildMembers],
});

client.once(Events.ClientReady, async (readyClient) => {
  console.log(`✅ Logged in as ${readyClient.user.tag}`);

  try {
    const guild = await client.guilds.fetch(config.guildId);
    const count = await syncGuildPlayers(guild);
    console.log(`🔄 Synced ${count} players on startup.`);
  } catch (error) {
    console.error('Startup player sync failed:', error.message);
  }
});

// Keep the player list current as members join, leave, or are renamed / (un)retired.
client.on(Events.GuildMemberAdd, async (member) => {
  if (member.guild.id !== config.guildId || member.user.bot) {
    return;
  }
  await upsertPlayers([mapMember(member)]).catch((error) =>
    console.error('GuildMemberAdd sync failed:', error.message),
  );
});

client.on(Events.GuildMemberUpdate, async (_oldMember, newMember) => {
  if (newMember.guild.id !== config.guildId || newMember.user.bot) {
    return;
  }
  await upsertPlayers([mapMember(newMember)]).catch((error) =>
    console.error('GuildMemberUpdate sync failed:', error.message),
  );
});

client.on(Events.GuildMemberRemove, async (member) => {
  if (member.guild?.id !== config.guildId) {
    return;
  }
  await deactivatePlayer(member.id).catch((error) =>
    console.error('GuildMemberRemove sync failed:', error.message),
  );
});

client.on(Events.InteractionCreate, async (interaction) => {
  try {
    if (interaction.isAutocomplete()) {
      const command = commandMap.get(interaction.commandName);
      if (command?.autocomplete) {
        await command.autocomplete(interaction);
      }
      return;
    }

    if (interaction.isChatInputCommand()) {
      const command = commandMap.get(interaction.commandName);
      if (command) {
        await command.execute(interaction);
      }
      return;
    }

    const [domain, action, token] = (interaction.customId ?? '').split(':');

    if (domain === 'report') {
      if (action === 'modal') {
        await handleModalSubmit(interaction, token);
      }
      return;
    }

    if (domain === 'newgame') {
      await routeNewGame(interaction, action, token);
      return;
    }

    if (domain === 'review') {
      await routeReview(interaction, action, token);
    }
  } catch (error) {
    console.error('Interaction error:', error);
    await safeError(interaction);
  }
});

async function routeNewGame(interaction, action, token) {
  switch (action) {
    case 'users':
      return handleGameUserSelect(interaction, token);
    case 'continue':
      return handleGameContinue(interaction, token);
    case 'cancel':
      return handleGameCancel(interaction, token);
  }
}

async function routeReview(interaction, action, reportId) {
  switch (action) {
    case 'approve':
      return handleApprove(interaction, reportId);
    case 'reject':
      return handleRejectButton(interaction, reportId);
    case 'rejectmodal':
      return handleRejectSubmit(interaction, reportId);
  }
}

async function safeError(interaction) {
  const payload = { content: '⚠️ Something went wrong handling that action.', flags: 64 };
  try {
    if (interaction.deferred || interaction.replied) {
      await interaction.followUp(payload);
    } else if (interaction.isRepliable()) {
      await interaction.reply(payload);
    }
  } catch {
    // ignore
  }
}

for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, async () => {
    console.log(`\n${signal} received, shutting down…`);
    await closeBrowser().catch(() => {});
    client.destroy();
    process.exit(0);
  });
}

client.login(config.token);
