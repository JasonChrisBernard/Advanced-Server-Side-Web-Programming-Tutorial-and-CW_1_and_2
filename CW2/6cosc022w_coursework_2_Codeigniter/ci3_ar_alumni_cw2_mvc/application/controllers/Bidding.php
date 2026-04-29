<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bidding extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bidding_model', 'biddingModel');
    }

    private function render($view, $data = [])
    {
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    private function requireLogin()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            exit;
        }
    }

    private function requireAlumnus()
    {
        $this->requireLogin();

        if ($this->session->userdata('role') !== 'alumnus') {
            show_error('Only alumni users can access the bidding system.', 403);
        }
    }

    private function requireDeveloper()
    {
        $this->requireLogin();

        if ($this->session->userdata('role') !== 'developer') {
            show_error('Only developer users can run winner selection for testing.', 403);
        }
    }

    private function userId()
    {
        return (int) $this->session->userdata('user_id');
    }

    private function isPost()
    {
        return $this->input->method(TRUE) === 'POST';
    }

    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function defaultFeatureDate()
    {
        return date('Y-m-d', strtotime('+1 day'));
    }

    public function index()
    {
        $this->requireAlumnus();

        $featureDate = $this->input->get('feature_date', TRUE);

        if (!$featureDate || !$this->isValidDate($featureDate)) {
            $featureDate = $this->defaultFeatureDate();
        }

        $userId = $this->userId();

        $data = [
            'title' => 'Blind Bidding System',
            'featureDate' => $featureDate,
            'existingBid' => $this->biddingModel->getBidByUserAndDate($userId, $featureDate),
            'blindStatus' => $this->biddingModel->getBlindStatus($userId, $featureDate),
            'monthlyWinCount' => $this->biddingModel->getMonthlyWinCount($userId, $featureDate),
            'dateAlreadySelected' => $this->biddingModel->isDateAlreadySelected($featureDate),
            'bids' => $this->biddingModel->getUserBids($userId),
            'todayFeatured' => $this->biddingModel->getTodayFeaturedAlumni()
        ];

        $this->render('bidding/index', $data);
    }

    public function place()
    {
        $this->requireAlumnus();

        if (!$this->isPost()) {
            redirect('bidding');
            return;
        }

        $userId = $this->userId();
        $featureDate = trim($this->input->post('feature_date', TRUE));
        $amount = trim($this->input->post('bid_amount', TRUE));

        $errors = [];

        if (!$this->isValidDate($featureDate)) {
            $errors[] = 'Feature date must be valid.';
        }

        if (!is_numeric($amount) || (float) $amount <= 0) {
            $errors[] = 'Bid amount must be greater than 0.';
        }

        if ($this->isValidDate($featureDate) && $this->biddingModel->isDateAlreadySelected($featureDate)) {
            $errors[] = 'Winner has already been selected for this feature date.';
        }

        if ($this->isValidDate($featureDate) && $this->biddingModel->hasMonthlyLimitReached($userId, $featureDate)) {
            $errors[] = 'You have already reached the monthly limit of 3 featured wins.';
        }

        $existingBid = null;

        if ($this->isValidDate($featureDate)) {
            $existingBid = $this->biddingModel->getBidByUserAndDate($userId, $featureDate);
        }

        if ($existingBid && $existingBid['status'] !== 'active') {
            $errors[] = 'This bid can no longer be updated because it is already ' . $existingBid['status'] . '.';
        }

        if ($existingBid && (float) $amount <= (float) $existingBid['bid_amount']) {
            $errors[] = 'You can only update a bid by increasing the amount.';
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('errors', $errors);
            redirect('bidding?feature_date=' . urlencode($featureDate));
            return;
        }

        if ($existingBid) {
            $this->biddingModel->increaseBid($existingBid['id'], $userId, $amount);
            $this->session->set_flashdata('success', 'Bid increased successfully.');
        } else {
            $this->biddingModel->createBid($userId, $featureDate, $amount);
            $this->session->set_flashdata('success', 'Bid placed successfully.');
        }

        redirect('bidding?feature_date=' . urlencode($featureDate));
    }

    public function cancel($bidId)
    {
        $this->requireAlumnus();

        if (!$this->isPost()) {
            show_error('Cancel request must use POST.', 405);
        }

        $bid = $this->biddingModel->getBidById($bidId, $this->userId());

        if (!$bid) {
            show_404();
        }

        if ($this->biddingModel->isDateAlreadySelected($bid['feature_date'])) {
            $this->session->set_flashdata('errors', ['This bid cannot be cancelled because winner selection has already happened.']);
            redirect('bidding');
            return;
        }

        $this->biddingModel->cancelBid($bidId, $this->userId());

        $this->session->set_flashdata('success', 'Bid cancelled successfully.');
        redirect('bidding');
    }

    public function run_selection()
    {
        $this->requireDeveloper();

        $featureDate = $this->input->post('feature_date', TRUE);

        if (!$featureDate || !$this->isValidDate($featureDate)) {
            $featureDate = date('Y-m-d');
        }

        $result = null;

        if ($this->isPost()) {
            $result = $this->biddingModel->runWinnerSelection($featureDate);
        }

        $data = [
            'title' => 'Run Winner Selection',
            'featureDate' => $featureDate,
            'result' => $result
        ];

        $this->render('bidding/run_selection', $data);
    }

    public function featured_today()
    {
        $data = [
            'title' => 'Featured Alumni Today',
            'featured' => $this->biddingModel->getTodayFeaturedAlumni()
        ];

        $this->render('bidding/featured_today', $data);
    }
}