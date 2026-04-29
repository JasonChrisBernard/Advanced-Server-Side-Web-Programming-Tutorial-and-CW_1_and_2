<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    private $universityDomain = '@eastminster.ac.uk';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Auth_model', 'authModel');
    }

    private function render($view, $data = [])
    {
        $this->load->view('templates/header', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    private function isPost()
    {
        return $this->input->method(TRUE) === 'POST';
    }

    private function requireLogin()
    {
        if (!$this->session->userdata('logged_in')) {
            redirect('login');
            exit;
        }
    }

    private function redirectIfLoggedIn()
    {
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
            exit;
        }
    }

    private function isUniversityEmail($email)
    {
        $email = strtolower(trim($email));
        $domain = strtolower($this->universityDomain);

        return substr($email, -strlen($domain)) === $domain;
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
        $this->redirectIfLoggedIn();

        $data = [
            'title' => 'Alumni Registration',
            'errors' => [],
            'old' => [
                'full_name' => '',
                'email' => ''
            ]
        ];

        if ($this->isPost()) {
            $fullName = trim($this->input->post('full_name', TRUE));
            $email = strtolower(trim($this->input->post('email', TRUE)));
            $password = $this->input->post('password', FALSE);
            $confirmPassword = $this->input->post('confirm_password', FALSE);

            $data['old'] = [
                'full_name' => $fullName,
                'email' => $email
            ];

            if ($fullName === '' || strlen($fullName) < 2) {
                $data['errors'][] = 'Full name is required and must be at least 2 characters.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['errors'][] = 'Please enter a valid email address.';
            }

            if (!$this->isUniversityEmail($email)) {
                $data['errors'][] = 'Only University of Eastminster email addresses are allowed. Use an email ending with ' . $this->universityDomain;
            }

            if ($password !== $confirmPassword) {
                $data['errors'][] = 'Password and confirm password do not match.';
            }

            $data['errors'] = array_merge($data['errors'], $this->passwordErrors($password));

            if ($this->authModel->getUserByEmail($email)) {
                $data['errors'][] = 'An account already exists with this email address.';
            }

            if (empty($data['errors'])) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $userId = $this->authModel->createUser(
                    $fullName,
                    $email,
                    $passwordHash,
                    'alumnus'
                );

                $token = $this->authModel->createAuthToken(
                    $userId,
                    'email_verification',
                    1440
                );

                $verificationLink = site_url('verify/' . $token);

                $emailBody =
                    "Hello " . $fullName . ",\n\n" .
                    "Thank you for registering as an alumni influencer.\n\n" .
                    "Please verify your email by opening this link:\n" .
                    $verificationLink . "\n\n" .
                    "This link expires in 24 hours.\n\n" .
                    "Regards,\nAR Alumni Platform";

                $this->authModel->saveEmailToOutbox(
                    $email,
                    'Verify your AR Alumni account',
                    $emailBody
                );

                $this->session->set_flashdata(
                    'success',
                    'Registration successful. Please verify your email before logging in.'
                );

                redirect('verify-sent');
                return;
            }
        }

        $this->render('auth/register', $data);
    }

    public function verify_sent()
    {
        $data = [
            'title' => 'Verification Email Sent'
        ];

        $this->render('auth/verify_sent', $data);
    }

    public function verify($token = '')
    {
        $this->redirectIfLoggedIn();

        if ($token === '') {
            $this->render('auth/message', [
                'title' => 'Invalid Verification Link',
                'heading' => 'Invalid verification link',
                'message' => 'The email verification link is missing or invalid.',
                'type' => 'danger'
            ]);
            return;
        }

        $tokenRow = $this->authModel->getValidToken($token, 'email_verification');

        if (!$tokenRow) {
            $this->render('auth/message', [
                'title' => 'Verification Failed',
                'heading' => 'Verification failed',
                'message' => 'This verification link is invalid, expired, or already used.',
                'type' => 'danger'
            ]);
            return;
        }

        $this->authModel->verifyUserEmail($tokenRow['user_id']);
        $this->authModel->markTokenUsed($tokenRow['id']);

        $this->render('auth/message', [
            'title' => 'Email Verified',
            'heading' => 'Email verified successfully',
            'message' => 'Your account is now verified. You can log in.',
            'type' => 'success'
        ]);
    }

    public function login()
    {
        $this->redirectIfLoggedIn();

        $data = [
            'title' => 'Login',
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
                $user = $this->authModel->getUserByEmail($email);

                if (!$user || !password_verify($password, $user['password_hash'])) {
                    $data['errors'][] = 'Invalid email or password.';
                } elseif ((int) $user['is_active'] !== 1) {
                    $data['errors'][] = 'This account is disabled.';
                } elseif ((int) $user['email_verified'] !== 1) {
                    $data['errors'][] = 'Please verify your email before logging in.';
                } else {
                    $this->session->sess_regenerate(TRUE);

                    $this->session->set_userdata([
                        'logged_in' => TRUE,
                        'user_id' => $user['id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]);

                    $this->authModel->updateLastLogin($user['id']);

                    redirect('dashboard');
                    return;
                }
            }
        }

        $this->render('auth/login', $data);
    }

    public function dashboard()
    {
        $this->requireLogin();

        $data = [
            'title' => 'Dashboard'
        ];

        $this->render('auth/dashboard', $data);
    }

    public function logout()
    {
        $this->session->unset_userdata([
            'logged_in',
            'user_id',
            'full_name',
            'email',
            'role'
        ]);

        $this->session->sess_destroy();

        redirect('login');
    }

    public function forgot_password()
    {
        $this->redirectIfLoggedIn();

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
                $user = $this->authModel->getUserByEmail($email);

                if ($user && (int) $user['is_active'] === 1) {
                    $token = $this->authModel->createAuthToken(
                        $user['id'],
                        'password_reset',
                        60
                    );

                    $resetLink = site_url('reset-password/' . $token);

                    $emailBody =
                        "Hello " . $user['full_name'] . ",\n\n" .
                        "A password reset request was made for your AR Alumni account.\n\n" .
                        "Open this link to reset your password:\n" .
                        $resetLink . "\n\n" .
                        "This link expires in 1 hour.\n\n" .
                        "If you did not request this, ignore this message.\n\n" .
                        "Regards,\nAR Alumni Platform";

                    $this->authModel->saveEmailToOutbox(
                        $user['email'],
                        'Reset your AR Alumni password',
                        $emailBody
                    );
                }

                $this->session->set_flashdata(
                    'success',
                    'If the email exists, a password reset link has been created.'
                );

                redirect('forgot-password');
                return;
            }
        }

        $this->render('auth/forgot_password', $data);
    }

    public function reset_password($token = '')
    {
        $this->redirectIfLoggedIn();

        if ($token === '') {
            $this->render('auth/message', [
                'title' => 'Invalid Reset Link',
                'heading' => 'Invalid reset link',
                'message' => 'The password reset link is missing or invalid.',
                'type' => 'danger'
            ]);
            return;
        }

        $tokenRow = $this->authModel->getValidToken($token, 'password_reset');

        if (!$tokenRow) {
            $this->render('auth/message', [
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

                $this->authModel->updatePassword($tokenRow['user_id'], $passwordHash);
                $this->authModel->markTokenUsed($tokenRow['id']);

                $this->render('auth/message', [
                    'title' => 'Password Reset Successful',
                    'heading' => 'Password reset successful',
                    'message' => 'Your password has been changed. You can now log in with your new password.',
                    'type' => 'success'
                ]);

                return;
            }
        }

        $this->render('auth/reset_password', $data);
    }
}