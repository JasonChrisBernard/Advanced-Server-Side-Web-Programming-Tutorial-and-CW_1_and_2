
const express = require('express');
const developerController = require('../controllers/developerController');
const { requireAuth, requireDeveloper } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(requireAuth);
router.use(requireDeveloper);

router.get('/api-docs', developerController.showApiDocs);
router.get('/api-keys', developerController.showApiKeys);
router.post('/api-keys', developerController.createApiKey);
router.post('/api-keys/:id/revoke', developerController.revokeApiKey);

module.exports = router;