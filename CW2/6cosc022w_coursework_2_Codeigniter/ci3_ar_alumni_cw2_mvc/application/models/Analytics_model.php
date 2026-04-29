<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_model extends CI_Model
{
    public function getSummaryStats()
    {
        $totalAlumni = $this->countRows("SELECT COUNT(*) AS total FROM users WHERE role = 'alumnus'");
        $totalCurrentJobs = $this->countRows("SELECT COUNT(DISTINCT user_id) AS total FROM employment_history WHERE is_current = 1");
        $averageCompletion = $this->averageRows("
            SELECT AVG(profile_completion_percent) AS average_value
            FROM profiles p
            INNER JOIN users u ON u.id = p.user_id
            WHERE u.role = 'alumnus'
        ");

        return [
            'total_alumni' => $totalAlumni,
            'total_programmes' => $this->countRows("SELECT COUNT(DISTINCT degree_name) AS total FROM profile_degrees"),
            'total_industries' => $this->countRows("SELECT COUNT(DISTINCT industry_sector) AS total FROM profiles WHERE industry_sector IS NOT NULL AND industry_sector != ''"),
            'total_certifications' => $this->countRows("SELECT COUNT(*) AS total FROM profile_certifications"),
            'total_courses' => $this->countRows("SELECT COUNT(*) AS total FROM profile_courses"),
            'total_current_jobs' => $totalCurrentJobs,
            'average_profile_completion' => $averageCompletion,
            'current_employment_rate' => $totalAlumni > 0 ? round(($totalCurrentJobs / $totalAlumni) * 100, 1) : 0
        ];
    }

    private function countRows($sql)
    {
        $stmt = $this->sqlitedb->query($sql);
        $row = $stmt->fetch();

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private function averageRows($sql)
    {
        $stmt = $this->sqlitedb->query($sql);
        $row = $stmt->fetch();

        return isset($row['average_value']) ? round((float) $row['average_value'], 1) : 0;
    }

    public function getDashboardDataset()
    {
        $stats = $this->getSummaryStats();
        $charts = [
            'programmes' => $this->getProgrammeCounts(),
            'industries' => $this->getIndustryCounts(),
            'graduation_years' => $this->getGraduationYearCounts(),
            'certifications' => $this->getTopCertificationCounts(),
            'courses' => $this->getTopCourseCounts(),
            'profile_completion' => $this->getProfileCompletionBuckets(),
            'employment_by_industry' => $this->getCurrentEmploymentByIndustry(),
            'top_employers' => $this->getTopEmployerCounts()
        ];

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'stats' => $stats,
            'charts' => $charts,
            'insights' => $this->buildInsights($stats, $charts)
        ];
    }

    private function buildInsights($stats, $charts)
    {
        $topProgramme = $this->topItem($charts['programmes']);
        $topIndustry = $this->topItem($charts['industries']);
        $topCertification = $this->topItem($charts['certifications']);

        return [
            [
                'label' => 'Leading Programme',
                'value' => $topProgramme ? $topProgramme['label'] : 'No data',
                'context' => $topProgramme ? (int) $topProgramme['total'] . ' alumni records' : 'Add alumni degrees to unlock this insight',
                'tone' => 'primary'
            ],
            [
                'label' => 'Top Industry',
                'value' => $topIndustry ? $topIndustry['label'] : 'No data',
                'context' => $topIndustry ? (int) $topIndustry['total'] . ' alumni in this sector' : 'Add industry data to unlock this insight',
                'tone' => 'success'
            ],
            [
                'label' => 'Average Completion',
                'value' => $stats['average_profile_completion'] . '%',
                'context' => 'Profile quality signal for AR-ready alumni records',
                'tone' => 'warning'
            ],
            [
                'label' => 'Employment Rate',
                'value' => $stats['current_employment_rate'] . '%',
                'context' => (int) $stats['total_current_jobs'] . ' alumni have a current role recorded',
                'tone' => 'info'
            ],
            [
                'label' => 'In-Demand Credential',
                'value' => $topCertification ? $topCertification['label'] : 'No data',
                'context' => $topCertification ? (int) $topCertification['total'] . ' alumni list this certification' : 'Add certifications to unlock this insight',
                'tone' => 'danger'
            ]
        ];
    }

    private function topItem($items)
    {
        return !empty($items) ? $items[0] : null;
    }

    public function getProgrammeCounts()
    {
        $sql = "
            SELECT 
                degree_name AS label,
                COUNT(DISTINCT user_id) AS total
            FROM profile_degrees
            GROUP BY degree_name
            ORDER BY total DESC, degree_name ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getIndustryCounts()
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN p.industry_sector IS NULL OR p.industry_sector = '' THEN 'Unspecified'
                    ELSE p.industry_sector
                END AS label,
                COUNT(DISTINCT p.user_id) AS total
            FROM profiles p
            INNER JOIN users u ON u.id = p.user_id
            WHERE u.role = 'alumnus'
            GROUP BY label
            ORDER BY total DESC, label ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getGraduationYearCounts()
    {
        $sql = "
            SELECT 
                strftime('%Y', completion_date) AS label,
                COUNT(DISTINCT user_id) AS total
            FROM profile_degrees
            WHERE completion_date IS NOT NULL
            AND completion_date != ''
            GROUP BY strftime('%Y', completion_date)
            ORDER BY label ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getTopCertificationCounts()
    {
        $sql = "
            SELECT 
                certification_name AS label,
                COUNT(DISTINCT user_id) AS total
            FROM profile_certifications
            GROUP BY certification_name
            ORDER BY total DESC, certification_name ASC
            LIMIT 10
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getTopCourseCounts()
    {
        $sql = "
            SELECT 
                course_name AS label,
                COUNT(DISTINCT user_id) AS total
            FROM profile_courses
            GROUP BY course_name
            ORDER BY total DESC, course_name ASC
            LIMIT 10
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getProfileCompletionBuckets()
    {
        $sql = "
            SELECT
                CASE
                    WHEN p.profile_completion_percent >= 90 THEN '90-100%'
                    WHEN p.profile_completion_percent >= 70 THEN '70-89%'
                    WHEN p.profile_completion_percent >= 50 THEN '50-69%'
                    ELSE '0-49%'
                END AS label,
                CASE
                    WHEN p.profile_completion_percent >= 90 THEN 4
                    WHEN p.profile_completion_percent >= 70 THEN 3
                    WHEN p.profile_completion_percent >= 50 THEN 2
                    ELSE 1
                END AS sort_order,
                COUNT(DISTINCT p.user_id) AS total
            FROM profiles p
            INNER JOIN users u ON u.id = p.user_id
            WHERE u.role = 'alumnus'
            GROUP BY label, sort_order
            ORDER BY sort_order ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getCurrentEmploymentByIndustry()
    {
        $sql = "
            SELECT
                CASE
                    WHEN p.industry_sector IS NULL OR p.industry_sector = '' THEN 'Unspecified'
                    ELSE p.industry_sector
                END AS label,
                COUNT(DISTINCT e.user_id) AS total
            FROM employment_history e
            INNER JOIN users u ON u.id = e.user_id
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.role = 'alumnus'
            AND e.is_current = 1
            GROUP BY label
            ORDER BY total DESC, label ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getTopEmployerCounts()
    {
        $sql = "
            SELECT
                company_name AS label,
                COUNT(DISTINCT user_id) AS total
            FROM employment_history
            WHERE company_name IS NOT NULL
            AND company_name != ''
            AND is_current = 1
            GROUP BY company_name
            ORDER BY total DESC, company_name ASC
            LIMIT 10
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getLatestAlumni($limit = 5)
    {
        $limit = (int) $limit;

        $sql = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                p.headline,
                p.industry_sector,
                d.degree_name,
                d.completion_date,
                e.company_name,
                e.job_title
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN profile_degrees d ON d.user_id = u.id
            LEFT JOIN employment_history e ON e.user_id = u.id AND e.is_current = 1
            WHERE u.role = 'alumnus'
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT $limit
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getProgrammeOptions()
    {
        $sql = "
            SELECT DISTINCT degree_name
            FROM profile_degrees
            WHERE degree_name IS NOT NULL AND degree_name != ''
            ORDER BY degree_name ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getGraduationYearOptions()
    {
        $sql = "
            SELECT DISTINCT strftime('%Y', completion_date) AS graduation_year
            FROM profile_degrees
            WHERE completion_date IS NOT NULL AND completion_date != ''
            ORDER BY graduation_year DESC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getIndustryOptions()
    {
        $sql = "
            SELECT DISTINCT industry_sector
            FROM profiles
            WHERE industry_sector IS NOT NULL AND industry_sector != ''
            ORDER BY industry_sector ASC
        ";

        return $this->sqlitedb->query($sql)->fetchAll();
    }

    public function getFilteredAlumni($filters = [])
    {
        $where = ["u.role = 'alumnus'"];
        $params = [];

        if (!empty($filters['programme'])) {
            $where[] = "d.degree_name = :programme";
            $params[':programme'] = $filters['programme'];
        }

        if (!empty($filters['graduation_year'])) {
            $where[] = "strftime('%Y', d.completion_date) = :graduation_year";
            $params[':graduation_year'] = $filters['graduation_year'];
        }

        if (!empty($filters['industry_sector'])) {
            $where[] = "p.industry_sector = :industry_sector";
            $params[':industry_sector'] = $filters['industry_sector'];
        }

        $sql = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                p.headline,
                p.industry_sector,
                p.profile_image_path,
                p.profile_completion_percent,
                d.degree_name,
                d.completion_date,
                e.company_name,
                e.job_title
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN profile_degrees d ON d.user_id = u.id
            LEFT JOIN employment_history e ON e.user_id = u.id AND e.is_current = 1
            WHERE " . implode(' AND ', $where) . "
            GROUP BY u.id
            ORDER BY d.completion_date DESC, u.full_name ASC
        ";

        return $this->sqlitedb->query($sql, $params)->fetchAll();
    }

    public function getAlumniDetail($id)
    {
        $sql = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                p.personal_info,
                p.headline,
                p.biography,
                p.linkedin_url,
                p.industry_sector,
                p.profile_image_path,
                p.profile_completion_percent
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.id = :id
            AND u.role = 'alumnus'
            LIMIT 1
        ";

        $alumni = $this->sqlitedb->query($sql, [
            ':id' => (int) $id
        ])->fetch();

        if (!$alumni) {
            return false;
        }

        $alumni['degrees'] = $this->getSection('profile_degrees', $id);
        $alumni['certifications'] = $this->getSection('profile_certifications', $id);
        $alumni['licences'] = $this->getSection('profile_licences', $id);
        $alumni['courses'] = $this->getSection('profile_courses', $id);
        $alumni['employment'] = $this->getSection('employment_history', $id);

        return $alumni;
    }

    private function getSection($table, $userId)
    {
        $sql = "
            SELECT *
            FROM $table
            WHERE user_id = :user_id
            ORDER BY id DESC
        ";

        return $this->sqlitedb->query($sql, [
            ':user_id' => (int) $userId
        ])->fetchAll();
    }
}
