<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Staff_auth extends CI_Controller
{
    private $universityDomain = '@eastminster.ac.uk';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Staff_auth_model', 'staffAuthModel');
    }

    private function render($view, $data = [])
    {
        $this->load->view('cw2_templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('cw2_templates/footer');
    }

    private function isPost()
    {
        return $this->input->method(TRUE) === 'POST';
    }

    private function redirectIfStaffLoggedIn()
    {
        if ($this->session->userdata('staff_logged_in')) {
            redirect('staff/dashboard');
            exit;
        }
    }

    private function isUniversityEmail($email)
    {
        $email = strtolower(trim($email));
        return substr($email, -strlen($this->universityDomain)) === $this->universityDomain;
    }

    private function passwordErrors($password)
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must include at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must include at least one lowercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must include at least one number.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must include at least one special character.';
        }

        return $errors;
    }

    public function register()
    {
        $this->redirectIfStaffLoggedIn();

        $data = [
            'title' => 'Staff Registration',
            'errors' => [],
            'old' => [
                'full_name' => '',
                'email' => '',
                'department' => '',
                'job_title' => ''
            ]
        ];

        if ($this->isPost()) {
            $fullName = trim($this->input->post('full_name', TRUE));
            $email = strtolower(trim($this->input->post('email', TRUE)));
            $department = trim($this->input->post('department', TRUE));
            $jobTitle = trim($this->input->post('job_title', TRUE));
            $password = $this->input->post('password', FALSE);
            $confirmPassword = $this->input->post('confirm_password', FALSE);

            $data['old'] = [
                'full_name' => $fullName,
                'email' => $email,
                'department' => $department,
                'job_title' => $jobTitle
            ];

            if ($fullName === '' || strlen($fullName) < 2) {
                $data['errors'][] = 'Full name is required.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['errors'][] = 'Please enter a valid email address.';
            }

            if (!$this->isUniversityEmail($email)) {
                $data['errors'][] = 'Only University of Eastminster staff emails are allowed. Use an email ending with ' . $this->universityDomain;
            }

            if ($department === '') {
                $data['errors'][] = 'Department is required.';
            }

            if ($jobTitle === '') {
                $data['errors'][] = 'Job title is required.';
            }

            if ($password !== $confirmPassword) {
                $data['errors'][] = 'Password and confirm password do not match.';
            }

            $data['errors'] = array_merge($data['errors'], $this->passwordErrors($password));

            if ($this->staffAuthModel->getStaffByEmail($email)) {
                $data['errors'][] = 'A staff account already exists with this email address.';
            }

            if (empty($data['errors'])) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $staffUserId = $this->staffAuthModel->createStaffUser(
                    $fullName,
                    $email,
                    $passwordHash,
                    $department,
                    $jobTitle,
                    'analyst'
                );

                $token = $this->staffAuthModel->createAuthToken(
                    $staffUserId,
                    'email_verification',
                    1440
                );

                $verificationLink = site_url('staff/verify/' . $token);

                $emailBody =
                    "Hello " . $fullName . ",\n\n" .
                    "Thank you for registering for the University Analytics Dashboard.\n\n" .
                    "Please verify your staff email by opening this link:\n" .
                    $verificationLink . "\n\n" .
                    "This link expires in 24 hours.\n\n" .
                    "Regards,\nUniversity Analytics Dashboard";

                $this->staffAuthModel->saveEmailToOutbox(
                    $email,
                    'Verify your University Analytics Dashboard account',
                    $emailBody
                );

                $this->session->set_flashdata(
                    'success',
                    'Registration successful. Please verify your email before logging in.'
                );

                redirect('staff/verify-sent');
                return;
            }
        }

        $this->render('staff_auth/register', $data);
    }

    public function verify_sent()
    {
        $this->render('staff_auth/verify_sent', [
            'title' => 'Verification Email Sent'
        ]);
    }

    public function verify($token = '')
    {
        $this->redirectIfStaffLoggedIn();

        if ($token === '') {
            $this->render('staff_auth/message', [
                'title' => 'Invalid Verification Link',
                'heading' => 'Invalid verification link',
                'message' => 'The verification link is missing or invalid.',
                'type' => 'danger'
            ]);
            return;
        }

        $tokenRow = $this->staffAuthModel->getValidToken($token, 'email_verification');

        if (!$tokenRow) {
            $this->render('staff_auth/message', [
                'title' => 'Verification Failed',
                'heading' => 'Verification failed',
                'message' => 'This verification link is invalid, expired, or already used.',
                'type' => 'danger'
            ]);
            return;
        }

        $this->staffAuthModel->verifyEmail($tokenRow['staff_user_id']);
        $this->staffAuthModel->markTokenUsed($tokenRow['id']);

        $this->render('staff_auth/message', [
            'title' => 'Email Verified',
            'heading' => 'Email verified successfully',
            'message' => 'Your staff account is now verified. You can log in.',
            'type' => 'success'
        ]);
    }

    public function login()
    {
        $this->redirectIfStaffLoggedIn();

        $data = [
            'title' => 'Staff Login',
            'errors' => [],
            'old' => [
                'email' => ''
            ]
        ];

        if ($this->isPost()) {
            $email = strtolower(trim($this->input->post('email', TRUE)));
            $password = $this->input->post('password', FALSE);

            $data['old']['email'] = $email;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['errors'][] = 'Please enter a valid email address.';
            }

            if ($password === '') {
                $data['errors'][] = 'Password is required.';
            }

            if (empty($data['errors'])) {
                $staff = $this->staffAuthModel->getStaffByEmail($email);

                if (!$staff || !password_verify($password, $staff['password_hash'])) {
                    $data['errors'][] = 'Invalid email or password.';
                } elseif ((int) $staff['is_active'] !== 1) {
                    $data['errors'][] = 'This staff account is disabled.';
                } elseif ((int) $staff['email_verified'] !== 1) {
                    $data['errors'][] = 'Please verify your email before logging in.';
                } else {
                    $this->session->sess_regenerate(TRUE);

                    $this->session->set_userdata([
                        'staff_logged_in' => TRUE,
                        'staff_user_id' => $staff['id'],
                        'staff_full_name' => $staff['full_name'],
                        'staff_email' => $staff['email'],
                        'staff_role' => $staff['role'],
                        'staff_department' => $staff['department'],
                        'staff_job_title' => $staff['job_title']
                    ]);

                    $this->staffAuthModel->updateLastLogin($staff['id']);

                    redirect('staff/dashboard');
                    return;
                }
            }
        }

        $this->render('staff_auth/login', $data);
    }

    public function logout()
    {
        $this->session->unset_userdata([
            'staff_logged_in',
            'staff_user_id',
            'staff_full_name',
            'staff_email',
            'staff_role',
            'staff_department',
            'staff_job_title'
        ]);

        $this->session->sess_destroy();

        redirect('staff/login');
    }

    public function forgot_password()
    {
        $this->redirectIfStaffLoggedIn();

        $data = [
            'title' => 'Forgot Password',
            'errors' => [],
            'old' => [
                'email' => ''
            ]
        ];

        if ($this->isPost()) {
            $email = strtolower(trim($this->input->post('email', TRUE)));
            $data['old']['email'] = $email;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['errors'][] = 'Please enter a valid email address.';
            }

            if (empty($data['errors'])) {
                $staff = $this->staffAuthModel->getStaffByEmail($email);

                if ($staff && (int) $staff['is_active'] === 1) {
                    $token = $this->staffAuthModel->createAuthToken(
                        $staff['id'],
                        'password_reset',
                        60
                    );

                    $resetLink = site_url('staff/reset-password/' . $token);

                    $emailBody =
                        "Hello " . $staff['full_name'] . ",\n\n" .
                        "A password reset request was made for your University Analytics Dashboard account.\n\n" .
                        "Open this link to reset your password:\n" .
                        $resetLink . "\n\n" .
                        "This link expires in 1 hour.\n\n" .
                        "If you did not request this, ignore this message.\n\n" .
                        "Regards,\nUniversity Analytics Dashboard";

                    $this->staffAuthModel->saveEmailToOutbox(
                        $staff['email'],
                        'Reset your University Analytics Dashboard password',
                        $emailBody
                    );
                }

                $this->session->set_flashdata(
                    'success',
                    'If the email exists, a password reset link has been created.'
                );

                redirect('staff/forgot-password');
                return;
            }
        }

        $this->render('staff_auth/forgot_password', $data);
    }

    public function reset_password($token = '')
    {
        $this->redirectIfStaffLoggedIn();

        if ($token === '') {
            $this->render('staff_auth/message', [
                'title' => 'Invalid Reset Link',
                'heading' => 'Invalid reset link',
                'message' => 'The reset link is missing or invalid.',
                'type' => 'danger'
            ]);
            return;
        }

        $tokenRow = $this->staffAuthModel->getValidToken($token, 'password_reset');

        if (!$tokenRow) {
            $this->render('staff_auth/message', [
                'title' => 'Reset Link Failed',
                'heading' => 'Password reset failed',
                'message' => 'This reset link is invalid, expired, or already used.',
                'type' => 'danger'
            ]);
            return;
        }

        $data = [
            'title' => 'Reset Password',
            'errors' => [],
            'token' => $token
        ];

        if ($this->isPost()) {
            $password = $this->input->post('password', FALSE);
            $confirmPassword = $this->input->post('confirm_password', FALSE);

            if ($password !== $confirmPassword) {
                $data['errors'][] = 'Password and confirm password do not match.';
            }

            $data['errors'] = array_merge($data['errors'], $this->passwordErrors($password));

            if (empty($data['errors'])) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $this->staffAuthModel->updatePassword($tokenRow['staff_user_id'], $passwordHash);
                $this->staffAuthModel->markTokenUsed($tokenRow['id']);

                $this->render('staff_auth/message', [
                    'title' => 'Password Reset Successful',
                    'heading' => 'Password reset successful',
                    'message' => 'Your password has been changed. You can now log in.',
                    'type' => 'success'
                ]);

                return;
            }
        }

        $this->render('staff_auth/reset_password', $data);
    }
}