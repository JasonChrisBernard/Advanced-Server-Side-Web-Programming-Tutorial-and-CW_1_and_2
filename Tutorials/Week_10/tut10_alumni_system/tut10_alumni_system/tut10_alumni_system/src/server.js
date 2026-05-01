const app = require('./app');
const pool = require('./config/db');
const { startBidWinnerJob } = require('./jobs/bidWinnerJob');
require('dotenv').config();

const PORT = Number(process.env.PORT || 3000);

async function startServer() {
  try {
    await pool.query('SELECT 1');
    console.log('MySQL connected successfully.');
    startBidWinnerJob();
    app.listen(PORT, () => {
      console.log(`Alumni platform running at http://localhost:${PORT}`);
    });
  } catch (error) {
    console.error('Failed to start server:', error.message);
    process.exit(1);
  }
}

startServer();
