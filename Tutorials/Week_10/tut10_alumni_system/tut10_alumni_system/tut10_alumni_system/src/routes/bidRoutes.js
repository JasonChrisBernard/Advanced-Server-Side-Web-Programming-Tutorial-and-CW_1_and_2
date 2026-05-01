const express = require('express');
const bidController = require('../controllers/bidController');
const {
  requireAuth,
  requireVerified,
  requireDeveloper,
  requireAlumni
} = require('../middleware/authMiddleware');

const router = express.Router();

router.use(requireAuth);

router.get('/', bidController.showBiddingPage);

router.post('/place', requireVerified, requireAlumni, bidController.placeBid);

// Only developer can manually run the Alumni of the Day selection
router.post('/run-selection', requireDeveloper, bidController.runManualSelection);

module.exports = router;