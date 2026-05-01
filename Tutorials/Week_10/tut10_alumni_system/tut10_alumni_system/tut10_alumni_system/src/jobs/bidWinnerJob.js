const cron = require('node-cron');
const { selectMidnightWinner } = require('../services/bidService');

function startBidWinnerJob() {
  cron.schedule(
    '0 0 * * *',
    async () => {
      try {
        const result = await selectMidnightWinner(new Date());
        console.log('[Bid Winner Job]', result);
      } catch (error) {
        console.error('[Bid Winner Job Error]', error);
      }
    },
    { timezone: 'Asia/Colombo' }
  );
}

module.exports = { startBidWinnerJob };
