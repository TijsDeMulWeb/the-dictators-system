import * as challengeAdd from './challenge-add.js';
import * as challengeRemove from './challenge-remove.js';
import * as challenges from './challenges.js';
import * as leaders from './leaders.js';
import * as newGame from './new-game.js';
import * as newSeason from './new-season.js';
import * as report from './report.js';
import * as scoreboard from './scoreboard.js';
import * as setCurrentGame from './set-current-game.js';
import * as syncPlayers from './sync-players.js';
import * as tierAdd from './tier-add.js';
import * as tierList from './tier-list.js';
import * as tierRemove from './tier-remove.js';

export const commands = [
  report,
  newGame,
  newSeason,
  setCurrentGame,
  scoreboard,
  syncPlayers,
  leaders,
  challenges,
  challengeAdd,
  challengeRemove,
  tierList,
  tierAdd,
  tierRemove,
];

export const commandMap = new Map(commands.map((command) => [command.data.name, command]));
