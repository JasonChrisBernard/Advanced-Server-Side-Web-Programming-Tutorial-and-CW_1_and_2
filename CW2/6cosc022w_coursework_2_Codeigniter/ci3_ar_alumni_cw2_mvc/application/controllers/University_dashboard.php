<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class University_dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Analytics_model', 'analyticsModel');
    }

    private function requireStaffLogin()
    {
        if (!$this->session->userdata('staff_logged_in')) {
            redirect('staff/login');
            exit;
        }
    }

    private function render($view, $data = [])
    {
        $this->load->view('cw2_templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('cw2_templates/footer');
    }

    public function index()
    {
        $this->requireStaffLogin();

        $data = [
            'title' => 'University Analytics Dashboard',
            'stats' => $this->analyticsModel->getSummaryStats(),
            'topCertifications' => $this->analyticsModel->getTopCertificationCounts(),
            'latestAlumni' => $this->analyticsModel->getLatestAlumni(6)
        ];

        $this->render('university_dashboard/index', $data);
    }

    public function graphs()
    {
        $this->requireStaffLogin();

        $data = [
            'title' => 'Analytics Graphs'
        ];

        $this->render('university_dashboard/graphs', $data);
    }

    public function chart_data()
    {
        $this->requireStaffLogin();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($this->analyticsModel->getDashboardDataset(), JSON_PRETTY_PRINT));
    }

    public function alumni()
    {
        $this->requireStaffLogin();

        $filters = $this->alumniFilters();

        $data = [
            'title' => 'View Alumni',
            'filters' => $filters,
            'programmeOptions' => $this->analyticsModel->getProgrammeOptions(),
            'graduationYearOptions' => $this->analyticsModel->getGraduationYearOptions(),
            'industryOptions' => $this->analyticsModel->getIndustryOptions(),
            'alumni' => $this->analyticsModel->getFilteredAlumni($filters)
        ];

        $this->render('university_dashboard/alumni', $data);
    }

    public function export_alumni()
    {
        $this->requireStaffLogin();

        $filters = $this->alumniFilters();
        $alumni = $this->analyticsModel->getFilteredAlumni($filters);
        $rows = [];

        foreach ($alumni as $item) {
            $rows[] = [
                $item['full_name'],
                $item['email'],
                $item['degree_name'] ?: '-',
                $item['completion_date'] ?: '-',
                $item['industry_sector'] ?: '-',
                trim(($item['job_title'] ?: '-') . ($item['company_name'] ? ' at ' . $item['company_name'] : '')),
                (int) ($item['profile_completion_percent'] ?? 0) . '%'
            ];
        }

        $this->downloadCsv(
            'alumni-report-' . date('Ymd-His') . '.csv',
            ['Name', 'Email', 'Programme', 'Graduation Date', 'Industry Sector', 'Current Role', 'Profile Completion'],
            $rows
        );
    }

    public function export_chart_data()
    {
        $this->requireStaffLogin();

        $dataset = $this->analyticsModel->getDashboardDataset();
        $rows = [];

        foreach ($dataset['charts'] as $chartName => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    ucwords(str_replace('_', ' ', $chartName)),
                    $item['label'],
                    (int) $item['total']
                ];
            }
        }

        $this->downloadCsv(
            'analytics-chart-data-' . date('Ymd-His') . '.csv',
            ['Chart', 'Label', 'Total'],
            $rows
        );
    }

    public function alumni_detail($id)
    {
        $this->requireStaffLogin();

        $alumni = $this->analyticsModel->getAlumniDetail($id);

        if (!$alumni) {
            show_404();
        }

        $data = [
            'title' => 'Alumni Detail',
            'alumni' => $alumni
        ];

        $this->render('university_dashboard/alumni_detail', $data);
    }

    private function alumniFilters()
    {
        return [
            'programme' => trim($this->input->get('programme', TRUE)),
            'graduation_year' => trim($this->input->get('graduation_year', TRUE)),
            'industry_sector' => trim($this->input->get('industry_sector', TRUE))
        ];
    }

    private function downloadCsv($filename, $headers, $rows)
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->output
            ->set_content_type('text/csv')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_output($csv);
    }
}
