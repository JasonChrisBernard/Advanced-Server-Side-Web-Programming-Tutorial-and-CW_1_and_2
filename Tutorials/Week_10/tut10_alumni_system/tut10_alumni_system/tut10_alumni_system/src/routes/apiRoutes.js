const express = require('express');
const apiController = require('../controllers/apiController');
const { requireApiKey } = require('../middleware/apiKeyMiddleware');

const router = express.Router();

router.get('/analytics', requireApiKey(['read:analytics']), apiController.analytics);
router.get('/profiles', requireApiKey(['read:profiles']), apiController.profiles);
router.get('/bids', requireApiKey(['read:bids']), apiController.bids);

module.exports = router;
