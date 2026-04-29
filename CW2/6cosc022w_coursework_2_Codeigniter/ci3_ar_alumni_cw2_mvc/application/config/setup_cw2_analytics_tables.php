<?php
if (!defined('BASEPATH') && !defined('CW2_ANALYTICS_SETUP_RUNNER')) {
    exit('No direct script access allowed');
}

$cw2_analytics_setup = [
    'timezone' => 'Asia/Colombo',
    'db_file' => __DIR__ . '/../data/ar_alumni.sqlite',

    'alters' => [
        "ALTER TABLE profiles ADD COLUMN industry_sector TEXT;"
    ],

    'indexes' => [
        "CREATE INDEX IF NOT EXISTS idx_profiles_industry_sector ON profiles(industry_sector);",
        "CREATE INDEX IF NOT EXISTS idx_profile_degrees_degree_name ON profile_degrees(degree_name);",
        "CREATE INDEX IF NOT EXISTS idx_profile_degrees_completion_date ON profile_degrees(completion_date);",
        "CREATE INDEX IF NOT EXISTS idx_employment_job_title ON employment_history(job_title);"
    ],

    'sample_alumni' => [
        [
            'full_name' => 'Alice Cloud',
            'email' => 'alice.cloud@eastminster.ac.uk',
            'headline' => 'Cloud Engineer at SkyStack Solutions',
            'personal_info' => 'Computer Science graduate focused on cloud infrastructure and DevOps.',
            'biography' => 'Alice works with AWS, Docker, Kubernetes, and CI/CD pipelines. Her profile demonstrates how graduates often gain cloud skills after graduation.',
            'linkedin_url' => 'https://www.linkedin.com/in/alice-cloud',
            'industry_sector' => 'Cloud Computing',
            'degree_name' => 'BSc Computer Science',
            'university_name' => 'University of Eastminster',
            'graduation_date' => '2024-07-15',
            'certification_name' => 'AWS Certified Cloud Practitioner',
            'cert_provider' => 'Amazon Web Services',
            'cert_url' => 'https://aws.amazon.com/certification/certified-cloud-practitioner/',
            'course_name' => 'Docker and Kubernetes Fundamentals',
            'course_provider' => 'Coursera',
            'course_url' => 'https://www.coursera.org/',
            'company_name' => 'SkyStack Solutions',
            'job_title' => 'Cloud Engineer'
        ],
        [
            'full_name' => 'Brian Data',
            'email' => 'brian.data@eastminster.ac.uk',
            'headline' => 'Data Analyst at InsightWorks',
            'personal_info' => 'Business Management graduate now working in data analytics.',
            'biography' => 'Brian moved into analytics after completing Python, SQL, and Tableau courses. This supports skills-gap analysis for business programmes.',
            'linkedin_url' => 'https://www.linkedin.com/in/brian-data',
            'industry_sector' => 'Data Analytics',
            'degree_name' => 'BA Business Management',
            'university_name' => 'University of Eastminster',
            'graduation_date' => '2023-07-20',
            'certification_name' => 'Google Data Analytics Professional Certificate',
            'cert_provider' => 'Google / Coursera',
            'cert_url' => 'https://www.coursera.org/professional-certificates/google-data-analytics',
            'course_name' => 'SQL for Data Analysis',
            'course_provider' => 'DataCamp',
            'course_url' => 'https://www.datacamp.com/',
            'company_name' => 'InsightWorks',
            'job_title' => 'Data Analyst'
        ],
        [
            'full_name' => 'Chloe Secure',
            'email' => 'chloe.secure@eastminster.ac.uk',
            'headline' => 'Cybersecurity Analyst at SecureGrid',
            'personal_info' => 'Software Engineering graduate working in defensive security.',
            'biography' => 'Chloe completed cybersecurity and cloud security certifications after graduation, showing demand for security-focused curriculum content.',
            'linkedin_url' => 'https://www.linkedin.com/in/chloe-secure',
            'industry_sector' => 'Cybersecurity',
            'degree_name' => 'BEng Software Engineering',
            'university_name' => 'University of Eastminster',
            'graduation_date' => '2025-07-12',
            'certification_name' => 'Google Cybersecurity Professional Certificate',
            'cert_provider' => 'Google / Coursera',
            'cert_url' => 'https://www.coursera.org/professional-certificates/google-cybersecurity',
            'course_name' => 'Introduction to Cyber Security',
            'course_provider' => 'Cisco Networking Academy',
            'course_url' => 'https://www.netacad.com/',
            'company_name' => 'SecureGrid',
            'job_title' => 'Cybersecurity Analyst'
        ],
        [
            'full_name' => 'Dilan Product',
            'email' => 'dilan.product@eastminster.ac.uk',
            'headline' => 'Product Analyst at AppForge',
            'personal_info' => 'Digital Business graduate working across product analytics and agile delivery.',
            'biography' => 'Dilan completed Agile, Scrum, and product analytics courses after graduation, showing workplace demand for agile methods.',
            'linkedin_url' => 'https://www.linkedin.com/in/dilan-product',
            'industry_sector' => 'Product Management',
            'degree_name' => 'BSc Digital Business',
            'university_name' => 'University of Eastminster',
            'graduation_date' => '2024-06-30',
            'certification_name' => 'Professional Scrum Master I',
            'cert_provider' => 'Scrum.org',
            'cert_url' => 'https://www.scrum.org/assessments/professional-scrum-master-i-certification',
            'course_name' => 'Agile Project Management',
            'course_provider' => 'Coursera',
            'course_url' => 'https://www.coursera.org/',
            'company_name' => 'AppForge',
            'job_title' => 'Product Analyst'
        ]
    ]
];