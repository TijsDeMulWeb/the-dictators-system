/**
 * Tiny in-memory session store for in-progress report drafts, keyed by a
 * short token embedded in component customIds. Entries auto-expire.
 */
const store = new Map();
const TTL_MS = 15 * 60 * 1000;

export function createDraft(data) {
  const token = Math.random().toString(36).slice(2, 10);
  store.set(token, { ...data, expiresAt: Date.now() + TTL_MS });
  return token;
}

export function getDraft(token) {
  const draft = store.get(token);
  if (!draft) {
    return null;
  }
  if (draft.expiresAt < Date.now()) {
    store.delete(token);
    return null;
  }
  return draft;
}

export function updateDraft(token, patch) {
  const draft = getDraft(token);
  if (!draft) {
    return null;
  }
  const next = { ...draft, ...patch, expiresAt: Date.now() + TTL_MS };
  store.set(token, next);
  return next;
}

export function deleteDraft(token) {
  store.delete(token);
}

// Periodic sweep of expired drafts.
setInterval(() => {
  const now = Date.now();
  for (const [token, draft] of store.entries()) {
    if (draft.expiresAt < now) {
      store.delete(token);
    }
  }
}, TTL_MS).unref();
