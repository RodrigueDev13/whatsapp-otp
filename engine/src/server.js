'use strict';

require('dotenv').config();
const express = require('express');
const { WhatsAppEngine } = require('./whatsapp');
const { buildRouter } = require('./routes');

const PORT = process.env.ENGINE_PORT || 3001;
const INTERNAL_SECRET = process.env.ENGINE_INTERNAL_SECRET;
const DATA_PATH = process.env.WWEBJS_DATA_PATH || './.wwebjs_auth';
const HEADLESS = process.env.PUPPETEER_HEADLESS !== 'false';

if (!INTERNAL_SECRET) {
  console.error('[engine] ENGINE_INTERNAL_SECRET is not set — refusing to start. Copy .env.example to .env first.');
  process.exit(1);
}

const app = express();
app.use(express.json());

// Shared-secret guard. This API must never be exposed publicly, but this
// header check is a cheap defense-in-depth layer in case it ever is.
app.use((req, res, next) => {
  if (req.get('X-Internal-Secret') !== INTERNAL_SECRET) {
    return res.status(401).json({ error: 'Missing or invalid X-Internal-Secret header' });
  }
  next();
});

const engine = new WhatsAppEngine({ dataPath: DATA_PATH, headless: HEADLESS });
app.use('/', buildRouter(engine));

// Bind to all interfaces: in Docker this is a separate container reached by
// `app` over the compose network at http://engine:3001, so loopback-only
// would refuse those connections. Isolation instead comes from not
// publishing this port to the host (docker-compose.yml) plus the shared
// X-Internal-Secret check above.
app.listen(PORT, '0.0.0.0', () => {
  console.log(`[engine] listening on 0.0.0.0:${PORT} (internal only, not published to host)`);
});

engine.start();

function shutdown() {
  console.log('[engine] shutting down...');
  engine.client
    .destroy()
    .catch(() => {})
    .finally(() => process.exit(0));
}
process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
