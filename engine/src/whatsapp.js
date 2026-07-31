'use strict';

const fs = require('fs');
const path = require('path');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');

// Chromium's SingletonLock/-Cookie/-Socket files live at the root of the
// LocalAuth profile dir (dataPath/session — no clientId is used here) and
// normally get cleaned up on a graceful exit. If this process is killed
// abruptly (container crash, OOM, power loss) they're left behind, and
// Chromium then refuses to launch on the next start ("profile appears to be
// in use by another Chromium process"). This container is the only process
// that ever touches this profile, so any lock found at startup is always
// stale — safe to clear unconditionally before every initialize().
const SINGLETON_LOCK_FILES = ['SingletonLock', 'SingletonCookie', 'SingletonSocket'];

function clearStaleChromiumLock(dataPath) {
  const profileDir = path.join(path.resolve(dataPath), 'session');
  for (const file of SINGLETON_LOCK_FILES) {
    try {
      fs.rmSync(path.join(profileDir, file), { force: true });
    } catch (err) {
      console.warn(`[whatsapp] could not clear stale lock file ${file}`, err.message);
    }
  }
}

const STATUS = {
  DISCONNECTED: 'DISCONNECTED',
  INITIALIZING: 'INITIALIZING',
  QR_READY: 'QR_READY',
  AUTHENTICATING: 'AUTHENTICATING',
  READY: 'READY',
  FAILED: 'FAILED',
};

class WhatsAppEngine {
  constructor({ dataPath, headless }) {
    this.dataPath = dataPath;
    this.headless = headless;
    this.status = STATUS.DISCONNECTED;
    this.qrDataUrl = null;
    this.phoneNumber = null;
    this.lastError = null;
    this.disconnecting = false;
    this.client = this._buildClient();
    this._attachEvents();
  }

  _buildClient() {
    return new Client({
      authStrategy: new LocalAuth({ dataPath: this.dataPath }),
      puppeteer: {
        headless: this.headless,
        args: [
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--disable-dev-shm-usage',
          '--disable-accelerated-2d-canvas',
          '--no-first-run',
          '--no-zygote',
          '--disable-gpu',
        ],
        // Set by the Docker image to point at the distro-provided Chromium
        // instead of downloading one; unset in local/native dev, where
        // puppeteer's own bundled Chromium is used.
        ...(process.env.PUPPETEER_EXECUTABLE_PATH
          ? { executablePath: process.env.PUPPETEER_EXECUTABLE_PATH }
          : {}),
      },
    });
  }

  _attachEvents() {
    this.client.on('qr', async (qr) => {
      try {
        this.qrDataUrl = await qrcode.toDataURL(qr);
        this.status = STATUS.QR_READY;
      } catch (err) {
        console.error('[whatsapp] failed to render QR code', err);
      }
    });

    this.client.on('authenticated', () => {
      this.status = STATUS.AUTHENTICATING;
      this.qrDataUrl = null;
    });

    this.client.on('ready', () => {
      this.status = STATUS.READY;
      this.phoneNumber = this.client.info?.wid?.user || null;
      this.qrDataUrl = null;
      this.lastError = null;
      console.log(`[whatsapp] session ready, linked number: ${this.phoneNumber}`);
    });

    this.client.on('disconnected', (reason) => {
      console.warn(`[whatsapp] disconnected: ${reason}`);
      // A manual disconnect() drives its own status/reconnect flow below;
      // don't let this event (which it also triggers) fight with that.
      if (this.disconnecting) return;
      this.status = STATUS.DISCONNECTED;
      this.phoneNumber = null;
    });

    this.client.on('auth_failure', (message) => {
      console.error(`[whatsapp] authentication failure: ${message}`);
      this.status = STATUS.FAILED;
      this.lastError = message || 'Authentication failed';
    });
  }

  async start() {
    this.status = STATUS.INITIALIZING;
    clearStaleChromiumLock(this.dataPath);
    try {
      await this.client.initialize();
    } catch (err) {
      this.status = STATUS.FAILED;
      this.lastError = err.message;
      console.error('[whatsapp] initialize() failed', err);
    }
  }

  getStatus() {
    return {
      status: this.status,
      phone: this.phoneNumber,
      error: this.status === STATUS.FAILED ? this.lastError : undefined,
    };
  }

  getQr() {
    if (this.status !== STATUS.QR_READY || !this.qrDataUrl) {
      return null;
    }
    return this.qrDataUrl;
  }

  /**
   * Unlinks the currently connected WhatsApp account (invalidates the
   * session on the phone, like removing it from "Linked devices") and boots
   * a fresh client so a new QR code is generated — ready to link a
   * different account.
   */
  async disconnect() {
    if (this.disconnecting) {
      return;
    }
    this.disconnecting = true;
    this.status = STATUS.INITIALIZING;
    this.qrDataUrl = null;
    this.phoneNumber = null;

    try {
      await this.client.logout();
    } catch (err) {
      console.warn('[whatsapp] logout() failed, proceeding to rebuild the client anyway', err.message);
    }
    try {
      await this.client.destroy();
    } catch (err) {
      // Already torn down by logout() in most cases — safe to ignore.
    }

    this.client = this._buildClient();
    this._attachEvents();
    this.disconnecting = false;

    // Not awaited: initialize() can take several seconds (fresh Chromium
    // launch) — the caller (the HTTP route) returns as soon as the old
    // session is torn down, and the dashboard picks up QR_READY via its
    // usual status polling once the new client boots.
    this.start();
  }

  async sendText(phone, text) {
    if (this.status !== STATUS.READY) {
      const err = new Error(`WhatsApp session is not ready (status: ${this.status})`);
      err.code = 'NOT_READY';
      throw err;
    }

    // Manually building "<digits>@c.us" breaks for numbers WhatsApp has
    // migrated to its newer "LID" identity system ("No LID for user"
    // error). getNumberId() resolves the correct serialized id (whether
    // classic @c.us or @lid) the same way the WhatsApp Web UI itself does.
    const digits = String(phone).replace(/[^\d]/g, '');
    const numberId = await this.client.getNumberId(digits);
    if (!numberId) {
      const err = new Error(`${phone} is not registered on WhatsApp`);
      err.code = 'NOT_ON_WHATSAPP';
      throw err;
    }

    // The message is genuinely delivered even when this resolves with no
    // usable id (observed on some WhatsApp Web/Chrome combinations) — the
    // id is informational only here, so don't fail the request over it.
    const message = await this.client.sendMessage(numberId._serialized, text);
    return { id: message?.id?._serialized ?? null };
  }
}

module.exports = { WhatsAppEngine, STATUS };
