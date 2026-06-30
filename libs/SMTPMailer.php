<?php

/**
 * SMTPMailer - Professional SMTP Email Handler Class
 * 
 * Hỗ trợ:
 * - Template-based emails with variable replacement
 * - Multiple recipients (To, CC, BCC)
 * - File attachments
 * - Email queue for async sending
 * - Logging for debugging
 * 
 * @author CMSNT.CO
 * @version 1.0.0
 * @since 2024
 * @requires PHP 7.4+
 */

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SMTPMailer
{
    /** @var DB Database instance */
    private $db;

    /** @var PHPMailer PHPMailer instance */
    private $mailer;

    /** @var array SMTP Configuration */
    private $config = [];

    /** @var array Error messages */
    private $errors = [];

    /** @var bool Success status */
    private $success = false;

    /** @var string Last error message */
    private $lastError = '';

    /** @var bool Enable debug mode */
    private $debug = false;

    /**
     * Email template types (SHOPCLONE7 keys)
     */
    const TEMPLATE_BUY_ORDER = 'buy_order';
    const TEMPLATE_WARNING_LOGIN = 'warning_login';
    const TEMPLATE_OTP_MAIL = 'otp_mail';
    const TEMPLATE_FORGOT_PASSWORD = 'forgot_password';
    const TEMPLATE_CUSTOM = 'custom';

    /**
     * Maximum subject length (RFC 5322)
     */
    const MAX_SUBJECT_LENGTH = 998;

    /**
     * Maximum body size (5MB)
     */
    const MAX_BODY_SIZE = 5242880;

    /**
     * Constructor - Initialize SMTP configuration
     * 
     * @param DB|null $db Database instance (optional)
     */
    public function __construct($db = null)
    {
        $this->db = $db ? $db : new DB();
        $this->loadConfig();
        $this->initMailer();
    }

    /**
     * Load SMTP configuration from database
     */
    private function loadConfig()
    {
        $this->config = [
            'status'     => (int) $this->db->site('smtp_status'),
            'host'       => $this->db->site('smtp_host'),
            'port'       => (int) $this->db->site('smtp_port'),
            'username'   => $this->db->site('smtp_email'),
            'password'   => $this->db->site('smtp_password'),
            'encryption' => $this->db->site('smtp_encryption'),
            'from_email' => $this->db->site('smtp_from_email') ?: $this->db->site('smtp_email'),
            'from_name'  => $this->db->site('title'),
            'charset'    => 'UTF-8'
        ];
    }

    /**
     * Initialize PHPMailer instance
     */
    private function initMailer()
    {
        $this->mailer = new PHPMailer(true);

        try {
            $this->mailer->SMTPDebug = $this->debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
            $this->mailer->Debugoutput = 'html';
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = $this->config['encryption'];
            $this->mailer->Port = $this->config['port'];
            $this->mailer->CharSet = $this->config['charset'];
            $this->mailer->Encoding = 'base64';
            $this->mailer->Timeout = 30;
            $this->mailer->SMTPKeepAlive = true;
        } catch (Exception $e) {
            $this->addError('Failed to initialize mailer: ' . $e->getMessage());
        }
    }

    /**
     * Check if SMTP is enabled
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->config['status'] === 1;
    }

    /**
     * Enable debug mode
     * 
     * @param bool $enable
     * @return self
     */
    public function setDebug($enable = true)
    {
        $this->debug = $enable;
        $this->mailer->SMTPDebug = $enable ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        return $this;
    }

    /**
     * Reset mailer for new email
     * 
     * @return self
     */
    public function reset()
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearAttachments();
        $this->mailer->clearReplyTos();
        $this->mailer->Subject = '';
        $this->mailer->Body = '';
        $this->mailer->AltBody = '';
        $this->errors = [];
        $this->success = false;
        $this->lastError = '';

        return $this;
    }

    // =========================================================================
    // SECURITY HELPERS
    // =========================================================================

    /**
     * Sanitize name to prevent Email Header Injection
     * 
     * @param string $name
     * @return string
     */
    private function sanitizeName($name)
    {
        $name = str_replace(["\r", "\n", "\t"], '', $name);
        $name = preg_replace('/[<>"\'\x00-\x1f\x7f]/', '', $name);
        return substr(trim($name), 0, 100);
    }

    /**
     * Sanitize subject to prevent header injection
     * 
     * @param string $subject
     * @return string
     */
    private function sanitizeSubject($subject)
    {
        $subject = str_replace(["\r", "\n", "\t"], ' ', $subject);
        $subject = preg_replace('/[\x00-\x1f\x7f]/', '', $subject);
        return substr(trim($subject), 0, self::MAX_SUBJECT_LENGTH);
    }

    // =========================================================================
    // RECIPIENT & SENDER METHODS
    // =========================================================================

    /**
     * Set sender information
     * 
     * @param string $email
     * @param string $name
     * @return self
     */
    public function setFrom($email, $name = '')
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addError('Invalid sender email format');
                return $this;
            }
            $safeName = $this->sanitizeName($name ?: $this->config['from_name']);
            $this->mailer->setFrom($email, $safeName);
        } catch (Exception $e) {
            $this->addError('Invalid sender: ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Add recipient
     * 
     * @param string $email
     * @param string $name
     * @return self
     */
    public function addTo($email, $name = '')
    {
        try {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $safeName = $this->sanitizeName($name);
                $this->mailer->addAddress($email, $safeName);
            } else {
                $this->addError('Invalid email address: ' . htmlspecialchars($email));
            }
        } catch (Exception $e) {
            $this->addError('Failed to add recipient: ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Add CC recipient
     */
    public function addCc($email, $name = '')
    {
        try {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addCC($email, $this->sanitizeName($name));
            }
        } catch (Exception $e) {
            $this->addError('Failed to add CC: ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Add BCC recipient
     */
    public function addBcc($email, $name = '')
    {
        try {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addBCC($email, $this->sanitizeName($name));
            }
        } catch (Exception $e) {
            $this->addError('Failed to add BCC: ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Add Reply-To address
     */
    public function addReplyTo($email, $name = '')
    {
        try {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($email, $this->sanitizeName($name));
            }
        } catch (Exception $e) {
            $this->addError('Failed to add Reply-To: ' . $e->getMessage());
        }
        return $this;
    }

    // =========================================================================
    // ATTACHMENT METHODS
    // =========================================================================

    /**
     * Add attachment with security validation
     * 
     * @param string $path File path
     * @param string $name Custom filename (optional)
     * @return self
     */
    public function addAttachment($path, $name = '')
    {
        try {
            $realPath = realpath($path);

            if ($realPath === false) {
                $this->addError('Attachment file not found: ' . basename($path));
                return $this;
            }

            // Prevent path traversal
            $allowedDirs = [
                realpath(__DIR__ . '/../uploads'),
                realpath(__DIR__ . '/../tmp'),
                sys_get_temp_dir()
            ];

            $isAllowed = false;
            foreach ($allowedDirs as $allowedDir) {
                if ($allowedDir && strpos($realPath, $allowedDir) === 0) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $this->addError('Attachment path not allowed for security reasons');
                return $this;
            }

            // Check file size (max 10MB)
            if (filesize($realPath) > 10485760) {
                $this->addError('Attachment file too large (max 10MB)');
                return $this;
            }

            // Validate file extension
            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                $this->addError('Attachment file type not allowed: ' . $extension);
                return $this;
            }

            if (!empty($name)) {
                $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
            }

            $this->mailer->addAttachment($realPath, $name);
        } catch (Exception $e) {
            $this->addError('Failed to add attachment: ' . $e->getMessage());
        }
        return $this;
    }

    /**
     * Add attachment from string data (cho queue system)
     * 
     * @param string $data File content
     * @param string $filename Filename
     * @return self
     */
    public function addAttachmentFromString($data, $filename)
    {
        try {
            $this->mailer->addStringAttachment($data, $filename);
        } catch (Exception $e) {
            $this->addError('Failed to add attachment: ' . $e->getMessage());
        }
        return $this;
    }

    // =========================================================================
    // SUBJECT & BODY METHODS
    // =========================================================================

    /**
     * Set email subject
     * 
     * @param string $subject
     * @return self
     */
    public function setSubject($subject)
    {
        $this->mailer->Subject = $this->sanitizeSubject($subject);
        return $this;
    }

    /**
     * Set HTML body
     * 
     * @param string $body HTML content
     * @param string $altBody Plain text alternative
     * @return self
     */
    public function setBody($body, $altBody = '')
    {
        if (strlen($body) > self::MAX_BODY_SIZE) {
            $this->addError('Email body too large (max 5MB)');
            return $this;
        }

        $this->mailer->isHTML(true);
        $this->mailer->Body = $body;
        $this->mailer->AltBody = $altBody ?: strip_tags($body);
        return $this;
    }

    // =========================================================================
    // TEMPLATE METHODS
    // =========================================================================

    /**
     * Get email template from database
     * 
     * @param string $type Template type (SHOPCLONE7 keys: buy_order, warning_login, etc.)
     * @return array ['subject' => string, 'content' => string]
     */
    public function getTemplate($type)
    {
        $templates = [
            self::TEMPLATE_BUY_ORDER => [
                'subject_key' => 'email_temp_subject_buy_order',
                'content_key' => 'email_temp_content_buy_order'
            ],
            self::TEMPLATE_WARNING_LOGIN => [
                'subject_key' => 'email_temp_subject_warning_login',
                'content_key' => 'email_temp_content_warning_login'
            ],
            self::TEMPLATE_OTP_MAIL => [
                'subject_key' => 'email_temp_subject_otp_mail',
                'content_key' => 'email_temp_content_otp_mail'
            ],
            self::TEMPLATE_FORGOT_PASSWORD => [
                'subject_key' => 'email_temp_subject_forgot_password',
                'content_key' => 'email_temp_content_forgot_password'
            ]
        ];

        if (!isset($templates[$type])) {
            return ['subject' => '', 'content' => ''];
        }

        return [
            'subject' => $this->db->site($templates[$type]['subject_key']) ?: '',
            'content' => $this->db->site($templates[$type]['content_key']) ?: ''
        ];
    }

    /**
     * Replace template variables
     * 
     * @param string $content Template content
     * @param array $variables Variables to replace ['{key}' => 'value']
     * @return string
     */
    public function parseTemplate($content, $variables = [])
    {
        $defaultVars = [
            '{domain}' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '',
            '{title}' => $this->db->site('title') ?: '',
            '{time}' => date('Y-m-d H:i:s'),
            '{year}' => date('Y')
        ];

        $variables = array_merge($defaultVars, $variables);

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $content
        );
    }

    // =========================================================================
    // SEND METHODS
    // =========================================================================

    /**
     * Send email
     * 
     * @return bool
     */
    public function send()
    {
        if (!$this->isEnabled()) {
            $this->addError('SMTP is not enabled');
            return false;
        }

        if (empty($this->mailer->getToAddresses())) {
            $this->addError('No recipient specified');
            return false;
        }

        try {
            if (empty($this->mailer->From) || $this->mailer->From === 'root@localhost') {
                $this->setFrom($this->config['from_email'], $this->config['from_name']);
            }

            $this->success = $this->mailer->send();
            return $this->success;
        } catch (Exception $e) {
            $this->addError('Send failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Quick send email (all-in-one)
     * 
     * @param string $to Recipient email
     * @param string $name Recipient name
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $fromName From name (optional)
     * @param string $attachment Attachment path (optional)
     * @return bool
     */
    public function quickSend($to, $name, $subject, $body, $fromName = '', $attachment = '')
    {
        $this->reset();
        $this->setFrom($this->config['from_email'], $fromName ?: $this->config['from_name']);
        $this->addTo($to, $name);
        $this->addReplyTo($this->config['from_email'], $fromName ?: $this->config['from_name']);
        $this->setSubject($subject);
        $this->setBody($body);

        if (!empty($attachment) && file_exists($attachment)) {
            $this->addAttachment($attachment);
        }

        return $this->send();
    }

    /**
     * Send email using template
     * 
     * @param string $templateType Template type constant
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param array $variables Template variables
     * @return bool
     */
    public function sendTemplate($templateType, $toEmail, $toName, $variables = [])
    {
        $template = $this->getTemplate($templateType);

        if (empty($template['subject']) || empty($template['content'])) {
            $this->addError('Template not found or empty: ' . $templateType);
            return false;
        }

        if (!isset($variables['{username}'])) {
            $variables['{username}'] = $toName;
        }

        $subject = $this->parseTemplate($template['subject'], $variables);
        $content = $this->parseTemplate($template['content'], $variables);

        return $this->quickSend($toEmail, $toName, $subject, $content);
    }

    /**
     * Test SMTP connection
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection()
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'SMTP is not enabled in settings'
            ];
        }

        try {
            $this->mailer->SMTPDebug = SMTP::DEBUG_CONNECTION;

            if ($this->mailer->smtpConnect()) {
                $this->mailer->smtpClose();
                return [
                    'success' => true,
                    'message' => 'SMTP connection successful'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to connect to SMTP server'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        } finally {
            $this->mailer->SMTPDebug = $this->debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        }
    }

    /**
     * Static helper method for quick sending
     * 
     * @param string $to
     * @param string $name
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public static function sendMail($to, $name, $subject, $body)
    {
        $mailer = new self();
        return $mailer->quickSend($to, $name, $subject, $body);
    }

    // =========================================================================
    // EMAIL QUEUE METHODS - Async email sending
    // =========================================================================

    /**
     * Queue an email for later sending (non-blocking)
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param int $priority Priority (1=high, 5=low)
     * @param array $metadata Additional data to store
     * @param string $attachmentData File content to attach (optional)
     * @param string $attachmentName Attachment filename (optional)
     * @return int|false Queue ID or false on failure
     */
    public function queueEmail(
        $toEmail,
        $toName,
        $subject,
        $body,
        $priority = 3,
        $metadata = [],
        $attachmentData = '',
        $attachmentName = ''
    ) {
        // Skip if SMTP is disabled
        if ($this->db->site('smtp_status') != 1) {
            return false;
        }

        // Skip if subject is empty (template disabled)
        if (empty(trim($subject))) {
            return false;
        }

        // Validate email
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addError('Invalid email for queue: ' . $toEmail);
            return false;
        }

        // Sanitize inputs
        $toEmail = substr($toEmail, 0, 255);
        $toName = $this->sanitizeName($toName);
        $subject = $this->sanitizeSubject($subject);

        // Check body size
        if (strlen($body) > self::MAX_BODY_SIZE) {
            $this->addError('Email body too large for queue');
            return false;
        }

        try {
            $insertData = [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'body' => $body,
                'priority' => max(1, min(5, (int) $priority)),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => 3,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'attachment_data' => !empty($attachmentData) ? base64_encode($attachmentData) : null,
                'attachment_name' => !empty($attachmentName) ? $attachmentName : null,
                'created_at' => date('Y-m-d H:i:s'),
                'scheduled_at' => date('Y-m-d H:i:s')
            ];

            $queueId = $this->db->insert('email_queue', $insertData);
            return $queueId;
        } catch (\Exception $e) {
            $this->addError('Failed to queue email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue order email (buy_order template) with optional attachment
     * 
     * @param array $user User data (phải có email, username)
     * @param array $variables Template variables
     * @param string $attachmentData Nội dung file đính kèm (optional)
     * @param string $attachmentName Tên file đính kèm (optional)
     * @return int|false Queue ID or false
     */
    public function queueOrderEmail($user, $variables = [], $attachmentData = '', $attachmentName = '')
    {
        if (empty($user['email'])) {
            $this->addError('User email not found');
            return false;
        }

        $template = $this->getTemplate(self::TEMPLATE_BUY_ORDER);
        if (empty($template['subject']) || empty($template['content'])) {
            return false;
        }

        if (!isset($variables['{username}'])) {
            $variables['{username}'] = $user['username'];
        }

        $subject = $this->parseTemplate($template['subject'], $variables);
        $body = $this->parseTemplate($template['content'], $variables);

        return $this->queueEmail(
            $user['email'],
            $user['username'],
            $subject,
            $body,
            1, // High priority
            [
                'type' => 'buy_order',
                'user_id' => isset($user['id']) ? $user['id'] : null
            ],
            $attachmentData,
            $attachmentName
        );
    }

    /**
     * Queue warning login email
     * 
     * @param array $user User data
     * @param array $variables Template variables
     * @return int|false Queue ID or false
     */
    public function queueWarningLoginEmail($user, $variables = [])
    {
        if (empty($user['email'])) {
            $this->addError('User email not found');
            return false;
        }

        $template = $this->getTemplate(self::TEMPLATE_WARNING_LOGIN);
        if (empty($template['subject']) || empty($template['content'])) {
            return false;
        }

        if (!isset($variables['{username}'])) {
            $variables['{username}'] = $user['username'];
        }

        $subject = $this->parseTemplate($template['subject'], $variables);
        $body = $this->parseTemplate($template['content'], $variables);

        return $this->queueEmail(
            $user['email'],
            $user['username'],
            $subject,
            $body,
            2, // Medium-high priority
            [
                'type' => 'warning_login',
                'user_id' => isset($user['id']) ? $user['id'] : null
            ]
        );
    }

    /**
     * Process email queue (called by cron job)
     * 
     * @param int $limit Maximum emails to process
     * @return array Statistics ['processed' => int, 'success' => int, 'failed' => int]
     */
    public function processQueue($limit = 10)
    {
        $stats = ['processed' => 0, 'success' => 0, 'failed' => 0];

        if (!$this->isEnabled()) {
            return $stats;
        }

        try {
            $now = date('Y-m-d H:i:s');
            $emails = $this->db->get_list_safe(
                "SELECT * FROM `email_queue` 
                 WHERE `status` = 'pending' 
                 AND `scheduled_at` <= ?
                 AND `attempts` < `max_attempts`
                 ORDER BY `priority` ASC, `created_at` ASC 
                 LIMIT ?",
                [$now, $limit]
            );

            if (empty($emails)) {
                return $stats;
            }

            foreach ($emails as $email) {
                $stats['processed']++;

                // Mark as processing
                $this->db->update('email_queue', [
                    'status' => 'processing',
                    'attempts' => (int) $email['attempts'] + 1,
                    'last_attempt_at' => date('Y-m-d H:i:s')
                ], "`id` = ?", [(int) $email['id']]);

                // Reset mailer
                $this->reset();

                // Prepare & send
                $this->setFrom($this->config['from_email'], $this->config['from_name']);
                $this->addTo($email['to_email'], $email['to_name']);
                $this->addReplyTo($this->config['from_email'], $this->config['from_name']);
                $this->setSubject($email['subject']);
                $this->setBody($email['body']);

                // Handle attachment from queue data
                if (!empty($email['attachment_data']) && !empty($email['attachment_name'])) {
                    $attachmentContent = base64_decode($email['attachment_data']);
                    if ($attachmentContent !== false) {
                        $this->addAttachmentFromString($attachmentContent, $email['attachment_name']);
                    }
                }

                $sent = $this->send();

                if ($sent) {
                    $this->db->update('email_queue', [
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                        'error_message' => ''
                    ], "`id` = ?", [(int) $email['id']]);

                    $stats['success']++;
                } else {
                    $newAttempts = (int) $email['attempts'] + 1;
                    $newStatus = ($newAttempts >= (int) $email['max_attempts']) ? 'failed' : 'pending';

                    $this->db->update('email_queue', [
                        'status' => $newStatus,
                        'error_message' => $this->getLastError()
                    ], "`id` = ?", [(int) $email['id']]);

                    if ($newStatus === 'failed') {
                        $stats['failed']++;
                    }
                }

                // Delay giữa các email
                usleep(100000); // 0.1 giây
            }
        } catch (\Exception $e) {
            error_log('[SMTPMailer] Queue processing error: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get queue statistics
     * 
     * @return array
     */
    public function getQueueStats()
    {
        try {
            $stats = $this->db->get_row(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                 FROM `email_queue`"
            );

            if ($stats) {
                return $stats;
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }

    /**
     * Clean old queue entries
     * 
     * @param int $days Days to keep
     * @return int Number of deleted entries
     */
    public function cleanQueue($days = 30)
    {
        try {
            $days = max(1, min(365, intval($days)));
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            $this->db->remove(
                'email_queue',
                "`status` IN ('sent', 'failed') AND `created_at` < ?",
                [$cutoffDate]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // ERROR HANDLING
    // =========================================================================

    /**
     * Add error message
     * 
     * @param string $message
     */
    private function addError($message)
    {
        $this->errors[] = $message;
        $this->lastError = $message;
        error_log('[SMTPMailer] ' . $message);
    }

    /** @return array */
    public function getErrors()
    {
        return $this->errors;
    }

    /** @return string */
    public function getLastError()
    {
        return $this->lastError;
    }

    /** @return bool */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /** @return bool */
    public function isSuccess()
    {
        return $this->success;
    }
}
