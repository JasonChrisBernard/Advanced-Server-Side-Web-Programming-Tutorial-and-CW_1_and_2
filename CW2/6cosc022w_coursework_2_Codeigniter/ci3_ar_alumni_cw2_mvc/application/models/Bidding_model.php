<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bidding_model extends CI_Model
{
    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    public function getBidByUserAndDate($userId, $featureDate)
    {
        $sql = "
            SELECT *
            FROM bids
            WHERE user_id = :user_id
            AND feature_date = :feature_date
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId,
            ':feature_date' => $featureDate
        ])->fetch();
    }

    public function getBidById($bidId, $userId)
    {
        $sql = "
            SELECT *
            FROM bids
            WHERE id = :id
            AND user_id = :user_id
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':id' => (int) $bidId,
            ':user_id' => (int) $userId
        ])->fetch();
    }

    public function createBid($userId, $featureDate, $amount)
    {
        $now = $this->now();

        $sql = "
            INSERT INTO bids
            (
                user_id,
                feature_date,
                bid_amount,
                status,
                created_at,
                updated_at
            )
            VALUES
            (
                :user_id,
                :feature_date,
                :bid_amount,
                'active',
                :created_at,
                :updated_at
            )
        ";

        $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId,
            ':feature_date' => $featureDate,
            ':bid_amount' => (float) $amount,
            ':created_at' => $now,
            ':updated_at' => $now
        ]);
    }

    public function increaseBid($bidId, $userId, $newAmount)
    {
        $sql = "
            UPDATE bids
            SET bid_amount = :bid_amount,
                updated_at = :updated_at
            WHERE id = :id
            AND user_id = :user_id
            AND status = 'active'
        ";

        $this->sqlitedb->query($sql, [
            ':bid_amount' => (float) $newAmount,
            ':updated_at' => $this->now(),
            ':id' => (int) $bidId,
            ':user_id' => (int) $userId
        ]);
    }

    public function cancelBid($bidId, $userId)
    {
        $sql = "
            UPDATE bids
            SET status = 'cancelled',
                updated_at = :updated_at
            WHERE id = :id
            AND user_id = :user_id
            AND status = 'active'
        ";

        $this->sqlitedb->query($sql, [
            ':updated_at' => $this->now(),
            ':id' => (int) $bidId,
            ':user_id' => (int) $userId
        ]);
    }

    public function getUserBids($userId)
    {
        $sql = "
            SELECT *
            FROM bids
            WHERE user_id = :user_id
            ORDER BY feature_date DESC, id DESC
        ";

        return $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ])->fetchAll();
    }

    public function getMonthlyWinCount($userId, $featureDate)
    {
        $monthStart = date('Y-m-01', strtotime($featureDate));
        $monthEnd = date('Y-m-t', strtotime($featureDate));

        $sql = "
            SELECT COUNT(*) AS total
            FROM featured_alumni
            WHERE winner_user_id = :user_id
            AND feature_date BETWEEN :month_start AND :month_end
        ";

        $row = $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId,
            ':month_start' => $monthStart,
            ':month_end' => $monthEnd
        ])->fetch();

        return (int) $row['total'];
    }

    public function hasMonthlyLimitReached($userId, $featureDate)
    {
        return $this->getMonthlyWinCount($userId, $featureDate) >= 3;
    }

    public function isDateAlreadySelected($featureDate)
    {
        $sql = "
            SELECT id
            FROM featured_alumni
            WHERE feature_date = :feature_date
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':feature_date' => $featureDate
        ])->fetch() ? true : false;
    }

    public function getTopActiveBid($featureDate)
    {
        $sql = "
            SELECT *
            FROM bids
            WHERE feature_date = :feature_date
            AND status = 'active'
            ORDER BY bid_amount DESC, created_at ASC, id ASC
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':feature_date' => $featureDate
        ])->fetch();
    }

    public function getBlindStatus($userId, $featureDate)
    {
        $ownBid = $this->getBidByUserAndDate($userId, $featureDate);

        if (!$ownBid) {
            return 'No bid placed for this date.';
        }

        if ($ownBid['status'] === 'won') {
            return 'You won this featured slot.';
        }

        if ($ownBid['status'] === 'lost') {
            return 'You did not win this featured slot.';
        }

        if ($ownBid['status'] === 'cancelled') {
            return 'Your bid was cancelled.';
        }

        $featured = $this->getFeaturedByDate($featureDate);

        if ($featured) {
            return ((int) $featured['winner_user_id'] === (int) $userId)
                ? 'You won this featured slot.'
                : 'You did not win this featured slot.';
        }

        $topBid = $this->getTopActiveBid($featureDate);

        if (!$topBid) {
            return 'No active bids for this date.';
        }

        return ((int) $topBid['user_id'] === (int) $userId)
            ? 'You are currently winning.'
            : 'You are currently not winning.';
    }

    public function getActiveBidsForDate($featureDate)
    {
        $sql = "
            SELECT b.*, u.full_name, u.email
            FROM bids b
            INNER JOIN users u ON u.id = b.user_id
            WHERE b.feature_date = :feature_date
            AND b.status = 'active'
            ORDER BY b.bid_amount DESC, b.created_at ASC, b.id ASC
        ";

        return $this->sqlitedb->query($sql, [
            ':feature_date' => $featureDate
        ])->fetchAll();
    }

    public function getFeaturedByDate($featureDate)
    {
        $sql = "
            SELECT 
                fa.*,
                u.full_name,
                u.email,
                p.headline,
                p.biography,
                p.linkedin_url,
                p.profile_image_path,
                p.profile_completion_percent
            FROM featured_alumni fa
            INNER JOIN users u ON u.id = fa.winner_user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE fa.feature_date = :feature_date
            LIMIT 1
        ";

        return $this->sqlitedb->query($sql, [
            ':feature_date' => $featureDate
        ])->fetch();
    }

    public function getTodayFeaturedAlumni()
    {
        return $this->getFeaturedByDate(date('Y-m-d'));
    }

    public function runWinnerSelection($featureDate)
    {
        if ($this->isDateAlreadySelected($featureDate)) {
            return [
                'status' => 'already_selected',
                'message' => 'Winner has already been selected for this date.',
                'winner' => $this->getFeaturedByDate($featureDate)
            ];
        }

        $activeBids = $this->getActiveBidsForDate($featureDate);

        if (empty($activeBids)) {
            return [
                'status' => 'no_bids',
                'message' => 'No active bids found for this date.',
                'winner' => null
            ];
        }

        $winnerBid = null;

        foreach ($activeBids as $bid) {
            if (!$this->hasMonthlyLimitReached($bid['user_id'], $featureDate)) {
                $winnerBid = $bid;
                break;
            }
        }

        if (!$winnerBid) {
            return [
                'status' => 'limit_blocked',
                'message' => 'No eligible winner found because all active bidders reached the monthly limit.',
                'winner' => null
            ];
        }

        $now = $this->now();

        $this->sqlitedb->query("
            INSERT INTO featured_alumni
            (
                feature_date,
                winner_user_id,
                winning_bid_id,
                winning_amount,
                selected_at
            )
            VALUES
            (
                :feature_date,
                :winner_user_id,
                :winning_bid_id,
                :winning_amount,
                :selected_at
            )
        ", [
            ':feature_date' => $featureDate,
            ':winner_user_id' => (int) $winnerBid['user_id'],
            ':winning_bid_id' => (int) $winnerBid['id'],
            ':winning_amount' => (float) $winnerBid['bid_amount'],
            ':selected_at' => $now
        ]);

        $this->sqlitedb->query("
            UPDATE bids
            SET status = 'won',
                updated_at = :updated_at
            WHERE id = :id
        ", [
            ':updated_at' => $now,
            ':id' => (int) $winnerBid['id']
        ]);

        $this->sqlitedb->query("
            UPDATE bids
            SET status = 'lost',
                updated_at = :updated_at
            WHERE feature_date = :feature_date
            AND status = 'active'
            AND id != :winner_bid_id
        ", [
            ':updated_at' => $now,
            ':feature_date' => $featureDate,
            ':winner_bid_id' => (int) $winnerBid['id']
        ]);

        $month = date('Y-m', strtotime($featureDate));

        $this->sqlitedb->query("
            UPDATE profiles
            SET appearance_count_month = appearance_count_month + 1,
                appearance_month = :appearance_month,
                updated_at = :updated_at
            WHERE user_id = :user_id
        ", [
            ':appearance_month' => $month,
            ':updated_at' => $now,
            ':user_id' => (int) $winnerBid['user_id']
        ]);

        return [
            'status' => 'selected',
            'message' => 'Winner selected successfully.',
            'winner' => $this->getFeaturedByDate($featureDate)
        ];
    }
}