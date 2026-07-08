import puppeteer from 'puppeteer';
import { config } from './config.js';

let browserPromise = null;

async function getBrowser() {
  if (!browserPromise) {
    browserPromise = puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const browser = await browserPromise;
    // If the browser dies, drop the cached instance so the next call relaunches.
    browser.on('disconnected', () => {
      browserPromise = null;
    });

    return browser;
  }

  return browserPromise;
}

/**
 * Load a Laravel render route (authenticated with the bot secret) and
 * screenshot the given element into a PNG buffer.
 *
 * @param {string} path e.g. `/render/reports/12/card`
 * @param {string} selector element to capture
 * @returns {Promise<Buffer>}
 */
async function screenshotRoute(path, selector) {
  const browser = await getBrowser();
  const page = await browser.newPage();

  try {
    await page.setExtraHTTPHeaders({ Authorization: `Bearer ${config.apiSecret}` });
    await page.setViewport({ width: 1100, height: 900, deviceScaleFactor: 2 });

    const response = await page.goto(`${config.apiUrl}${path}`, {
      waitUntil: 'networkidle0',
      timeout: 20000,
    });

    if (!response || !response.ok()) {
      throw new Error(`Render route ${path} returned ${response ? response.status() : 'no response'}`);
    }

    const element = await page.$(selector);
    if (!element) {
      throw new Error(`Element ${selector} not found on ${path}`);
    }

    // puppeteer returns a Uint8Array; discord.js attachments require a Buffer.
    return Buffer.from(await element.screenshot({ type: 'png' }));
  } finally {
    await page.close();
  }
}

export function renderReportCard(reportId) {
  return screenshotRoute(`/render/reports/${reportId}/card`, '#card');
}

export function renderScoreboard() {
  return screenshotRoute('/render/scoreboard', '#board');
}

export function renderLeaders() {
  return screenshotRoute('/render/leaders', '#board');
}

export async function closeBrowser() {
  if (browserPromise) {
    const browser = await browserPromise;
    await browser.close();
    browserPromise = null;
  }
}
