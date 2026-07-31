'use strict';

const express = require('express');

/**
 * Internal-only routes exposing the WhatsApp engine to Laravel. Every route
 * requires the X-Internal-Secret header (checked in server.js) — this API is
 * never meant to be reachable from the public internet.
 */
function buildRouter(engine) {
  const router = express.Router();

  router.get('/status', (req, res) => {
    res.json(engine.getStatus());
  });

  router.get('/qr', (req, res) => {
    const qr = engine.getQr();
    if (!qr) {
      return res.status(404).json({ error: 'No QR code available in the current state' });
    }
    res.json({ qr });
  });

  router.post('/disconnect', async (req, res) => {
    try {
      await engine.disconnect();
      res.json({ success: true });
    } catch (err) {
      res.status(500).json({ success: false, error: err.message });
    }
  });

  router.post('/send', async (req, res) => {
    const { to, text } = req.body || {};
    if (typeof to !== 'string' || !to.trim() || typeof text !== 'string' || !text.trim()) {
      return res.status(422).json({ error: 'Both "to" and "text" are required strings' });
    }
    try {
      const result = await engine.sendText(to, text);
      res.json({ success: true, id: result.id });
    } catch (err) {
      const status = err.code === 'NOT_READY' ? 503
        : err.code === 'NOT_ON_WHATSAPP' ? 422
        : 500;
      res.status(status).json({ success: false, error: err.message });
    }
  });

  return router;
}

module.exports = { buildRouter };
