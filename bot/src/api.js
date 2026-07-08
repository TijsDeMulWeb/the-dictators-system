import axios from 'axios';
import { config } from './config.js';

/**
 * Axios client for the Laravel internal API, pre-authenticated with the
 * shared bot secret.
 */
export const api = axios.create({
  baseURL: `${config.apiUrl}/api`,
  headers: {
    Authorization: `Bearer ${config.apiSecret}`,
    Accept: 'application/json',
  },
  timeout: 20000,
});

export async function syncPlayers(members) {
  const { data } = await api.post('/players/sync', { members });
  return data.synced;
}

export async function fetchSelectablePlayers() {
  const { data } = await api.get('/players');
  return data.data;
}

export async function upsertPlayers(members) {
  if (members.length === 0) {
    return 0;
  }
  const { data } = await api.post('/players/upsert', { members });
  return data.upserted;
}

export async function deactivatePlayer(discordId) {
  await api.post('/players/deactivate', { discord_id: discordId });
}

export async function createReport(payload) {
  const { data } = await api.post('/reports', payload);
  return data.data;
}

export async function getReport(reportId) {
  const { data } = await api.get(`/reports/${reportId}`);
  return data.data;
}

export async function fetchChallenges() {
  const { data } = await api.get('/challenges');
  return data.data;
}

export async function addChallenge(name, tier) {
  const { data } = await api.post('/challenges', { name, tier });
  return data.data;
}

export async function removeChallenge(challengeId) {
  await api.delete(`/challenges/${challengeId}`);
}

export async function fetchTiers() {
  const { data } = await api.get('/tiers');
  return data.data;
}

export async function addTier(name, points) {
  const { data } = await api.post('/tiers', { name, points });
  return data.data;
}

export async function removeTier(tierId) {
  await api.delete(`/tiers/${tierId}`);
}

export async function approveReport(reportId, reviewerDiscordId, postedMessageId = null) {
  const { data } = await api.post(`/reports/${reportId}/approve`, {
    reviewer_discord_id: reviewerDiscordId,
    posted_message_id: postedMessageId,
  });
  return data.data;
}

export async function rejectReport(reportId, reviewerDiscordId, note = null) {
  const { data } = await api.post(`/reports/${reportId}/reject`, {
    reviewer_discord_id: reviewerDiscordId,
    note,
  });
  return data.data;
}
