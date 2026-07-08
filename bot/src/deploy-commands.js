import { REST, Routes } from 'discord.js';
import { config } from './config.js';
import { commands } from './commands/index.js';

const body = commands.map((command) => command.data.toJSON());

const rest = new REST({ version: '10' }).setToken(config.token);

try {
  console.log(`Registering ${body.length} guild commands…`);
  await rest.put(Routes.applicationGuildCommands(config.clientId, config.guildId), { body });
  console.log('✅ Commands registered for guild', config.guildId);
} catch (error) {
  console.error('❌ Failed to register commands:', error);
  process.exit(1);
}
