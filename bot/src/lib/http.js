import axios from 'axios';

/**
 * Fetch a remote image (Discord CDN or Laravel storage) into a Buffer so it can
 * be attached to a Discord message. discord.js will not fetch URL strings on
 * its own.
 *
 * @param {string} url
 * @returns {Promise<Buffer>}
 */
export async function fetchImageBuffer(url) {
  const { data } = await axios.get(url, { responseType: 'arraybuffer', timeout: 20000 });
  return Buffer.from(data);
}
