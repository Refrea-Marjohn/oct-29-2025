<?php
require_once 'session_manager.php';
validateUserAccess('client');
require_once 'config.php';
require_once 'audit_logger.php';
require_once 'action_logger_helper.php';
$client_id = $_SESSION['user_id'];

// Check if client has an approved request
$stmt = $conn->prepare("SELECT id, status, review_notes, reviewed_at FROM client_request_form WHERE client_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$res = $stmt->get_result();
$request_status = $res->fetch_assoc();

// Set flag to show request access page instead of redirecting
$show_request_access = (!$request_status || $request_status['status'] !== 'Approved');

// Determine if client can submit a new request
$can_submit_request = true;
if ($request_status) {
    // Client can only submit a new request if:
    // 1. No existing request, OR
    // 2. Previous request was rejected
    $can_submit_request = ($request_status['status'] === 'Rejected');
}

// Check if this is a new approval (approved today)
$is_new_approval = false;
if ($request_status && isset($request_status['reviewed_at']) && $request_status['reviewed_at']) {
    $is_new_approval = (date('Y-m-d', strtotime($request_status['reviewed_at'])) === date('Y-m-d'));
}

$stmt = $conn->prepare("SELECT profile_image FROM user_form WHERE id=?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$res = $stmt->get_result();
$profile_image = '';
if ($res && $row = $res->fetch_assoc()) {
    $profile_image = $row['profile_image'];
}
if (!$profile_image || !file_exists($profile_image)) {
        $profile_image = 'images/default-avatar.jpg';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Generation - Opiña Law Office</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/document-styles.css?v=<?= time() ?>">
    <style>
        /* Profile Modal Override - ULTRA STRONG CSS to force height reduction */
        .modal#editProfileModal .modal-content {
            max-height: none !important;
            height: auto !important;
            min-height: auto !important;
            overflow-y: visible !important;
            overflow-x: visible !important;
            margin: 2% auto !important;
            width: 98% !important;
            max-width: 800px !important;
        }
        
        .modal#passwordVerificationModal .modal-content {
            max-height: none !important;
            height: auto !important;
            min-height: auto !important;
            overflow-y: visible !important;
            overflow-x: visible !important;
            margin: 2% auto !important;
            width: 98% !important;
            max-width: 800px !important;
        }
        
        /* Force modal body to be compact */
        .modal#editProfileModal .modal-body {
            max-height: none !important;
            height: auto !important;
            min-height: auto !important;
            overflow-y: visible !important;
            overflow-x: visible !important;
            padding: 12px !important;
        }
        
        .modal#passwordVerificationModal .modal-body {
            max-height: none !important;
            height: auto !important;
            min-height: auto !important;
            overflow-y: visible !important;
            overflow-x: visible !important;
            padding: 12px !important;
        }
        
        /* Override ALL possible conflicting styles */
        .modal#editProfileModal,
        .modal#passwordVerificationModal {
            z-index: 2000 !important;
        }
        
        .modal#editProfileModal .modal-content,
        .modal#passwordVerificationModal .modal-content {
            animation: none !important;
            transform: none !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            border-radius: 12px !important;
        }
        
        /* Force profile form to be compact */
        .modal#editProfileModal .profile-form,
        .modal#editProfileModal .form-section {
            margin-bottom: 6px !important;
            padding: 0 !important;
        }
        
        .modal#editProfileModal .form-group {
            margin-bottom: 4px !important;
        }
        
        /* Reduce element sizes for more compact modal */
        .modal#editProfileModal .modal-header h2 {
            font-size: 1.1rem !important;
            padding: 8px 12px !important;
        }
        
        .modal#editProfileModal .modal-header {
            padding: 8px 12px !important;
        }
        
        .modal#editProfileModal .form-section h3 {
            font-size: 0.9rem !important;
            margin-bottom: 6px !important;
            padding-bottom: 2px !important;
        }
        
        .modal#editProfileModal .form-group label {
            font-size: 0.75rem !important;
            margin-bottom: 2px !important;
        }
        
        .modal#editProfileModal .form-group input {
            padding: 4px 6px !important;
            font-size: 0.8rem !important;
            border-radius: 4px !important;
        }
        
        .modal#editProfileModal .upload-btn {
            padding: 4px 8px !important;
            font-size: 0.7rem !important;
        }
        
        .modal#editProfileModal .upload-hint {
            font-size: 0.65rem !important;
        }
        
        .modal#editProfileModal .current-profile-image {
            width: 50px !important;
            height: 50px !important;
        }
        
        .modal#editProfileModal .form-actions button {
            padding: 4px 8px !important;
            font-size: 0.75rem !important;
        }
        
        .modal#editProfileModal small {
            font-size: 0.6rem !important;
        }
        
        /* Data Preview Styles */
        .data-preview {
            display: none;
        }
        
        .data-preview-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .data-preview-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .data-preview-content {
            display: grid;
            gap: 15px;
        }
        
        .data-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .data-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .data-value {
            color: #333;
            font-size: 0.95rem;
            line-height: 1.4;
            word-wrap: break-word;
            background: white;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        
        .data-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        /* Document Status Section Styles */
        .section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .section-header h2 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .section-header p {
            margin: 0;
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .document-status-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .document-status-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border-color: #d1d5db;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-approved { 
            background: #10b981;
        }
        .status-rejected { 
            background: #ef4444;
        }
        .status-pending { 
            background: #f59e0b;
        }
        
        .document-meta {
            background: #f9fafb;
            border-radius: 8px;
            padding: 16px 20px;
            margin-top: 20px;
            border: 1px solid #f3f4f6;
        }
        
        .document-meta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
        }
        
        .document-meta-label {
            color: #6b7280;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .document-meta-value {
            color: #374151;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .rejection-reason {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        /* Request Access Page Styles - Matching System Theme */
        .request-access-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 40px 20px;
        }
        
        .request-access-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(93, 14, 38, 0.15);
            border: 2px solid rgba(93, 14, 38, 0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .request-icon {
            font-size: 4rem;
            color: #5D0E26;
            margin-bottom: 20px;
        }
        
        .request-access-content p {
            color: #666;
            margin: 0 0 30px 0;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .status-info {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid;
        }
        
        .status-info.pending {
            border-color: #8B1538;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        }
        
        .status-info.rejected {
            border-color: #e74c3c;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }
        
        .status-info i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .status-info.pending i {
            color: #8B1538;
        }
        
        .status-info.rejected i {
            color: #e74c3c;
        }
        
        .status-info h3 {
            color: #5D0E26;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            font-family: "Playfair Display", serif;
        }
        
        .status-info p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        .review-notes {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(93, 14, 38, 0.2);
        }
        
        .review-notes strong {
            color: #5D0E26;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .review-notes p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }
        
        .request-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        
        .request-actions .btn {
            min-width: 200px;
            padding: 15px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        
        .request-actions .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(93, 14, 38, 0.3);
        }
        
        .request-actions .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(93, 14, 38, 0.4);
        }
        
        .request-actions .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #5D0E26;
            border: 2px solid rgba(93, 14, 38, 0.2);
        }
        
        .request-actions .btn-secondary:hover {
            background: rgba(93, 14, 38, 0.05);
            border-color: rgba(93, 14, 38, 0.3);
            color: #4A0B1E;
        }
        
        .info-message {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
            border: 2px solid rgba(93, 14, 38, 0.2);
            border-radius: 12px;
            padding: 15px 20px;
            color: #5D0E26;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }
        
        .info-message i {
            font-size: 1.1rem;
            color: #8B1538;
        }
        
        .request-actions .btn-success {
            background: var(--success-color);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .request-actions .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }
        
        .request-actions .btn-warning {
            background: var(--warning-color);
            color: white;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }
        
        .request-actions .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.4);
        }
        
        .request-actions .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .request-status-info {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 15px 20px;
            margin-top: 15px;
            border: 1px solid rgba(93, 14, 38, 0.1);
            text-align: left;
        }
        
        .request-status-info small {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .request-status-info strong {
            color: #5D0E26;
            font-weight: 600;
        }
        
        .request-status-info em {
            color: #666;
            font-style: italic;
        }
        
        .rejection-details {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(93, 14, 38, 0.2);
            text-align: left;
        }
        
        .rejection-details strong {
            color: #5D0E26;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .rejection-notes {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }
        
        /* Request Form Styles */
        .request-form-content {
            max-height: 70vh;
            overflow-y: auto;
            padding: 20px 0;
        }
        
        .form-section {
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(93, 14, 38, 0.1);
        }
        
        .form-section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(93, 14, 38, 0.1);
            color: #5D0E26;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: "Playfair Display", serif;
        }
        
        .form-section-title i {
            color: #8B1538;
            font-size: 1.2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        
        .form-group.full-width-field {
            margin-bottom: 15px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #5D0E26;
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label::before {
            content: '';
            width: 3px;
            height: 14px;
            background: linear-gradient(135deg, #5D0E26 0%, #8B1538 100%);
            border-radius: 2px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid rgba(93, 14, 38, 0.1);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.95);
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #5D0E26;
            box-shadow: 0 0 0 3px rgba(93, 14, 38, 0.1);
            background: white;
            transform: translateY(-1px);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            line-height: 1.6;
        }
        
        .field-help {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 8px;
            padding: 10px 12px;
            background: rgba(93, 14, 38, 0.05);
            border-radius: 8px;
            border-left: 3px solid #8B1538;
        }
        
        .field-help i {
            color: #8B1538;
            font-size: 0.9rem;
            margin-top: 2px;
        }
        
        .field-help span {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .file-upload-container {
            position: relative;
        }
        
        .file-upload-container input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(93, 14, 38, 0.05);
            border: 2px dashed rgba(93, 14, 38, 0.2);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            color: #5D0E26;
            font-weight: 500;
        }
        
        .file-upload-label:hover {
            background: rgba(93, 14, 38, 0.1);
            border-color: rgba(93, 14, 38, 0.3);
        }
        
        .file-upload-label i {
            color: #8B1538;
        }
        
        .file-info {
            margin-top: 8px;
        }
        
        .file-info small {
            color: #666;
            font-size: 0.8rem;
        }
        
        .privacy-checkbox-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 20px;
            background: rgba(93, 14, 38, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(93, 14, 38, 0.1);
        }
        
        .privacy-checkbox-container input[type="checkbox"] {
            margin: 0;
            width: 18px;
            height: 18px;
            accent-color: #5D0E26;
        }
        
        .privacy-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #5D0E26;
            font-weight: 500;
            cursor: pointer;
            margin: 0;
        }
        
        .privacy-label::before {
            display: none;
        }
        
        .privacy-label i {
            color: #8B1538;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(93, 14, 38, 0.1);
        }
        
        .form-actions .btn {
            min-width: 120px;
        }
        
        /* Child Entry Styles */
        .child-entry {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .child-entry input {
            flex: 1;
        }

        #childrenContainer {
            margin-bottom: 10px;
        }
        
        /* Radio Group Styles */
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
            background: rgba(93, 14, 38, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(93, 14, 38, 0.1);
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            cursor: pointer;
            color: var(--text-dark);
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .radio-group label:hover {
            background: rgba(93, 14, 38, 0.1);
        }

        .radio-group input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            margin: 0;
        }
        
        /* Button Enhancements */
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.4);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar client-sidebar">
        <div class="sidebar-header">
            <img src="images/logo.jpg" alt="Logo">
            <h2>Opiña Law Office</h2>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="client_dashboard.php" title="View your case overview, statistics, and recent activities">
                    <div class="button-content">
                        <i class="fas fa-home"></i>
                        <div class="text-content">
                            <span>Dashboard</span>
                            <small>Overview & Statistics</small>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="client_cases.php" title="Track your legal cases, view case details, and upload documents">
                    <div class="button-content">
                        <i class="fas fa-gavel"></i>
                        <div class="text-content">
                            <span>My Cases</span>
                            <small>Track Legal Cases</small>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="client_schedule.php" title="View your upcoming appointments, hearings, and court schedules">
                    <div class="button-content">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="text-content">
                            <span>My Schedule</span>
                            <small>Appointments & Hearings</small>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="client_documents.php" class="active" title="Generate legal documents like affidavits and sworn statements">
                    <div class="button-content">
                        <i class="fas fa-file-alt"></i>
                        <div class="text-content">
                            <span>Document Generation</span>
                            <small>Create Legal Documents</small>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="client_messages.php" class="has-badge" title="Communicate with your attorney and legal team">
                    <div class="button-content">
                        <i class="fas fa-envelope"></i>
                        <div class="text-content">
                            <span>Messages</span>
                            <small>Chat with Attorney</small>
                        </div>
                    </div>
                    <span class="unread-message-badge hidden" id="unreadMessageBadge">0</span>
                </a>
            </li>
            <li>
                <a href="client_about.php" title="Learn more about Opiña Law Office and our team">
                    <div class="button-content">
                        <i class="fas fa-info-circle"></i>
                        <div class="text-content">
                            <span>About Us</span>
                            <small>Our Story & Team</small>
                        </div>
                    </div>
                </a>
            </li>
        </ul>
    </div>

    <!-- Solo Parent Modal -->
    <div id="soloParentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> Sworn Affidavit of Solo Parent</h2>
                <span class="close" onclick="closeSoloParentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="soloParentForm" class="modal-form">
                    <div class="form-group">
                        <label for="soloFullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="soloFullName" name="fullName" required placeholder="Enter your complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="soloCompleteAddress">Complete Address <span class="required">*</span></label>
                        <input type="text" id="soloCompleteAddress" name="completeAddress" required placeholder="Enter your complete address">
                    </div>
                    
                    <div class="form-group">
                        <label for="childrenNames">Children Names <span class="required">*</span></label>
                        <div id="childrenContainer">
                            <div class="child-entry">
                                <input type="text" name="childrenNames[]" placeholder="Child's Name" required>
                                <input type="number" name="childrenAges[]" placeholder="Age" min="0" max="120" required>
                                <button type="button" onclick="removeChild(this)" class="btn btn-danger btn-sm">Remove</button>
                        </div>
                    </div>
                        <button type="button" onclick="addChild()" class="btn btn-secondary btn-sm">Add Child</button>
                    </div>
                    
                    <div class="form-group">
                        <label for="yearsUnderCase">Years Under Case <span class="required">*</span></label>
                        <input type="number" id="yearsUnderCase" name="yearsUnderCase" required placeholder="Number of years" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Reason Section <span class="required">*</span></label>
                        <div class="radio-group">
                            <label><input type="radio" name="reasonSection" value="Left the family home and abandoned us" onchange="toggleOtherReason()" required> Left the family home and abandoned us</label>
                            <label><input type="radio" name="reasonSection" value="Died last" onchange="toggleOtherReason()" required> Died last</label>
                            <label><input type="radio" name="reasonSection" value="Other reason, please state" onchange="toggleOtherReason()" required> Other reason, please state</label>
                        </div>
                        <div id="otherReasonContainer" style="display: none; margin-top: 10px;">
                            <input type="text" id="otherReason" name="otherReason" placeholder="Please specify other reason" style="padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; background: white; width: 100%;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Employment Status <span class="required">*</span></label>
                        <div class="radio-group">
                            <label><input type="radio" name="employmentStatus" value="Employee and earning" onchange="toggleEmploymentFields()" required> Employee and earning</label>
                            <label><input type="radio" name="employmentStatus" value="Self-employed and earning" onchange="toggleEmploymentFields()" required> Self-employed and earning</label>
                            <label><input type="radio" name="employmentStatus" value="Un-employed and dependent upon" onchange="toggleEmploymentFields()" required> Un-employed and dependent upon</label>
                            </div>
                        <div id="employeeAmountContainer" style="display: none; margin-top: 10px;">
                            <input type="text" id="employeeAmount" name="employeeAmount" placeholder="Monthly amount" style="padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; background: white; width: 100%;">
                            </div>
                        <div id="selfEmployedAmountContainer" style="display: none; margin-top: 10px;">
                            <input type="text" id="selfEmployedAmount" name="selfEmployedAmount" placeholder="Monthly amount" style="padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; background: white; width: 100%;">
                            </div>
                        <div id="unemployedDependentContainer" style="display: none; margin-top: 10px;">
                            <input type="text" id="unemployedDependent" name="unemployedDependent" placeholder="Dependent upon" style="padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; background: white; width: 100%;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="soloDateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="soloDateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeSoloParentModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="saveSoloParent()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewSoloParentData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="soloParentDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-user"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">Full Name</label>
                            <div class="data-value" id="previewSoloFullName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Complete Address</label>
                            <div class="data-value" id="previewSoloCompleteAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Children</label>
                            <div class="data-value" id="previewChildren">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Years Under Case</label>
                            <div class="data-value" id="previewYearsUnderCase">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Reason</label>
                            <div class="data-value" id="previewReason">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Employment Status</label>
                            <div class="data-value" id="previewEmployment">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewSoloDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="saHideData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="saSend()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Senior ID Loss Modal -->
    <div id="seniorIDLossModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-id-card"></i> Affidavit of Loss (Senior ID)</h2>
                <span class="close" onclick="closeSeniorIDLossModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="seniorIDLossForm" class="modal-form">
                    <div class="form-group">
                        <label for="seniorFullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="seniorFullName" name="fullName" required placeholder="Enter your complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="seniorCompleteAddress">Complete Address <span class="required">*</span></label>
                        <textarea id="seniorCompleteAddress" name="completeAddress" required placeholder="Enter your complete address including street, barangay, city, province" style="resize: vertical; min-height: 60px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="seniorRelationship">Relationship to Senior Citizen <span class="required">*</span></label>
                        <input type="text" id="seniorRelationship" name="relationship" required placeholder="e.g., Son, Daughter, Spouse, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="seniorCitizenName">Senior Citizen's Full Name <span class="required">*</span></label>
                        <input type="text" id="seniorCitizenName" name="seniorCitizenName" required placeholder="Enter the senior citizen's complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="seniorDetailsOfLoss">Details of Loss <span class="required">*</span></label>
                        <textarea id="seniorDetailsOfLoss" name="detailsOfLoss" required placeholder="Describe the circumstances of how the Senior ID was lost" style="resize: vertical; min-height: 80px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                        <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;">Please provide detailed information about when, where, and how the Senior ID was lost</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="seniorDateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="seniorDateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeSeniorIDLossModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="saveSeniorIDLoss()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewSeniorIDLossData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="seniorIDLossDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-id-card"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">Full Name</label>
                            <div class="data-value" id="previewSeniorFullName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Complete Address</label>
                            <div class="data-value" id="previewSeniorCompleteAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Relationship to Senior Citizen</label>
                            <div class="data-value" id="previewSeniorRelationship">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Senior Citizen's Full Name</label>
                            <div class="data-value" id="previewSeniorCitizenName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Details of Loss</label>
                            <div class="data-value" id="previewSeniorDetailsOfLoss">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewSeniorDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="hideSeniorIDLossData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="sendSeniorIDLoss()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <?php 
        $page_title = 'Document Generation';
        $page_subtitle = 'Generate and manage your document storage';
        include 'components/profile_header.php'; 
        ?>

        <?php if ($show_request_access): ?>
            <!-- Request Access Page -->
            <div class="request-access-container">
                <div class="request-access-card">
                    <div class="request-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Access Required</h2>
                    <p>To generate legal documents, you need to request access first. This helps us verify your identity and provide better service.</p>
                    
                    <?php if ($request_status): ?>
                        <?php if ($request_status['status'] === 'Pending'): ?>
                            <div class="status-info pending">
                                <i class="fas fa-clock"></i>
                                <h3>Request Under Review</h3>
                                <p>Your request is currently being reviewed by our team. You will be notified once it's approved.</p>
                            </div>
                        <?php elseif ($request_status['status'] === 'Rejected'): ?>
                            <div class="status-info rejected">
                                <i class="fas fa-times-circle"></i>
                                <h3>Previous Request Rejected</h3>
                                <p>Your previous request was rejected. Please submit a new request with updated information.</p>
                                <?php if ($request_status['review_notes']): ?>
                                    <div class="rejection-details">
                                        <strong>Rejection Reason:</strong><br>
                                        <div class="rejection-notes">
                                            <?= nl2br(htmlspecialchars($request_status['review_notes'])) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="request-actions">
                        <?php if ($can_submit_request): ?>
                            <button onclick="openDocumentRequestModal()" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Request Access
                            </button>
                        <?php else: ?>
                            <?php if ($request_status['status'] === 'Pending'): ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-clock"></i>
                                    Request Pending Review
                                </button>
                                <div class="request-status-info">
                                    <small>Your request is currently being reviewed. You will be notified once a decision is made.</small>
                                </div>
                            <?php elseif ($request_status['status'] === 'Approved'): ?>
                                <button class="btn btn-success" disabled>
                                    <i class="fas fa-check-circle"></i>
                                    Request Approved
                                </button>
                                <div class="request-status-info">
                                    <small>Your request has been approved. You can now access the document generation system.</small>
                                </div>
                            <?php elseif ($request_status['status'] === 'Rejected'): ?>
                                <button onclick="openDocumentRequestModal()" class="btn btn-warning">
                                    <i class="fas fa-redo"></i>
                                    Submit New Request
                                </button>
                                <div class="request-status-info">
                                    <small>Your previous request was rejected. You can submit a new request with updated information.</small>
                                    <?php if (!empty($request_status['review_notes'])): ?>
                                        <br><br>
                                        <strong>Review Notes:</strong><br>
                                        <em><?= nl2br(htmlspecialchars($request_status['review_notes'])) ?></em>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-info-circle"></i>
                                    Request Status: <?= htmlspecialchars($request_status['status']) ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Document Generation Grid -->
            <div class="document-grid">
            <!-- Row 1 -->
            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Affidavit of Loss</h3>
                <p>Generate affidavit of loss document</p>
                <button onclick="openAffidavitLossModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <h3>Affidavit of Loss<br><span style="font-size: 0.9em; font-weight: 500;">(Senior ID)</span></h3>
                <p>Generate affidavit of loss for senior ID</p>
                <button onclick="openSeniorIDLossModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h3>Sworn Affidavit of Solo Parent</h3>
                <p>Generate sworn affidavit of solo parent</p>
                <button onclick="openSoloParentModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <!-- Row 2 -->
            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-female"></i>
                </div>
                <h3>Sworn Affidavit of Mother</h3>
                <p>Generate sworn affidavit of mother</p>
                <button onclick="openSwornAffidavitMotherModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-wheelchair"></i>
                </div>
                <h3>Affidavit of Loss<br><span style="font-size: 0.9em; font-weight: 500;">(PWD ID)</span></h3>
                <p>Generate affidavit of loss for PWD ID</p>
                <button onclick="openPWDLossModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Affidavit of Loss (Boticab Booklet/ID)</h3>
                <p>Generate affidavit of loss for Boticab booklet/ID</p>
                <button onclick="openBoticabLossModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <!-- Row 3 -->
            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Joint Affidavit (Two Disinterested Person)</h3>
                <p>Generate joint affidavit of two disinterested person</p>
                <button onclick="openJointAffidavitModal()" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </button>
            </div>

            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Joint Affidavit of Two Disinterested Person (Solo Parent)</h3>
                <p>Generate joint affidavit of two disinterested person (solo parent)</p>
                <a href="files-generation/generate_joint_affidavit_solo_parent.php" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </a>
            </div>

            <div class="document-box">
                <div class="document-icon">
                    <i class="fas fa-file-signature"></i>
                </div>
                <h3>Sworn Affidavit (Solo Parent)</h3>
                <p>Generate sworn affidavit for solo parent</p>
                <a href="files-generation/generate_sworn_affidavit_of_solo_parent.php" class="btn btn-primary generate-btn">
                    <i class="fas fa-edit"></i> Fill Up
                </a>
            </div>
        </div>

        <!-- Document Status Section -->
        <div class="section" style="margin-top: 30px;">
            <div class="section-header">
                <h2><i class="fas fa-file-check"></i> My Document Submissions</h2>
                <p>Track the status of your submitted documents</p>
            </div>
            
            <div id="documentStatusContainer">
                <div class="loading-state" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                    <p>Loading your document submissions...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Affidavit of Loss Modal -->
    <div id="affidavitLossModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Affidavit of Loss</h2>
                <span class="close" onclick="closeAffidavitLossModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="affidavitLossForm" class="modal-form">
                    <div class="form-group">
                        <label for="fullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="fullName" name="fullName" required placeholder="Enter your complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="completeAddress">Complete Address <span class="required">*</span></label>
                        <input type="text" id="completeAddress" name="completeAddress" required placeholder="Enter your complete address">
                    </div>
                    
                    <div class="form-group">
                        <label for="specifyItemLost">Specify Item Lost <span class="required">*</span></label>
                        <input type="text" id="specifyItemLost" name="specifyItemLost" required placeholder="e.g., Driver's License, Passport, ID Card">
                    </div>
                    
                    <div class="form-group">
                        <label for="itemLost">Item Lost <span class="required">*</span></label>
                        <input type="text" id="itemLost" name="itemLost" required placeholder="Describe the specific item that was lost">
                    </div>
                    
                    <div class="form-group">
                        <label for="itemDetails">Item Details <span class="required">*</span></label>
                        <input type="text" id="itemDetails" name="itemDetails" required placeholder="Provide detailed description of the lost item">
                    </div>
                    
                    <div class="form-group">
                        <label for="dateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="dateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeAffidavitLossModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="saveAffidavitLoss()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewAffidavitLossData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="affidavitLossDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">Full Name</label>
                            <div class="data-value" id="previewFullName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Complete Address</label>
                            <div class="data-value" id="previewCompleteAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Specify Item Lost</label>
                            <div class="data-value" id="previewSpecifyItemLost">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Item Lost</label>
                            <div class="data-value" id="previewItemLost">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Item Details</label>
                            <div class="data-value" id="previewItemDetails">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="hideAffidavitLossData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="sendAffidavitLoss()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- PWD ID Loss Modal -->
    <div id="pwdLossModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-wheelchair"></i> Affidavit of Loss (PWD ID)</h2>
                <span class="close" onclick="closePWDLossModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="pwdLossForm" class="modal-form">
                    <div class="form-group">
                        <label for="pwdFullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="pwdFullName" name="fullName" required placeholder="Enter your complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="pwdFullAddress">Full Address <span class="required">*</span></label>
                        <textarea id="pwdFullAddress" name="fullAddress" required placeholder="Enter your complete address including street, barangay, city, province" style="resize: vertical; min-height: 60px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="pwdDetailsOfLoss">Details of Loss <span class="required">*</span></label>
                        <textarea id="pwdDetailsOfLoss" name="detailsOfLoss" required placeholder="Describe the circumstances of how the PWD ID was lost" style="resize: vertical; min-height: 80px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                        <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;">Please provide detailed information about when, where, and how the PWD ID was lost</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pwdDateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="pwdDateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closePWDLossModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="savePWDLoss()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewPWDLossData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="pwdLossDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-wheelchair"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">Full Name</label>
                            <div class="data-value" id="previewPwdFullName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Full Address</label>
                            <div class="data-value" id="previewPwdFullAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Details of Loss</label>
                            <div class="data-value" id="previewPwdDetailsOfLoss">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewPwdDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="hidePWDLossData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="sendPWDLoss()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Boticab Booklet/ID Loss Modal -->
    <div id="boticabLossModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-book"></i> Affidavit of Loss (Boticab Booklet/ID)</h2>
                <span class="close" onclick="closeBoticabLossModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="boticabLossForm" class="modal-form">
                    <div class="form-group">
                        <label for="boticabFullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="boticabFullName" name="fullName" required placeholder="Enter your complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="boticabFullAddress">Full Address <span class="required">*</span></label>
                        <textarea id="boticabFullAddress" name="fullAddress" required placeholder="Enter your complete address including street, barangay, city, province" style="resize: vertical; min-height: 60px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="boticabDetailsOfLoss">Details of Loss <span class="required">*</span></label>
                        <textarea id="boticabDetailsOfLoss" name="detailsOfLoss" required placeholder="Describe the circumstances of how the Boticab booklet/ID was lost" style="resize: vertical; min-height: 80px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                        <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;">Please provide detailed information about when, where, and how the Boticab booklet/ID was lost</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="boticabDateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="boticabDateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeBoticabLossModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="saveBoticabLoss()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewBoticabLossData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="boticabLossDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-book"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">Full Name</label>
                            <div class="data-value" id="previewBoticabFullName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Full Address</label>
                            <div class="data-value" id="previewBoticabFullAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Details of Loss</label>
                            <div class="data-value" id="previewBoticabDetailsOfLoss">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewBoticabDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="hideBoticabLossData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="sendBoticabLoss()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Joint Affidavit (Two Disinterested Person) Modal -->
    <div id="jointAffidavitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-users"></i> Joint Affidavit (Two Disinterested Person)</h2>
                <span class="close" onclick="closeJointAffidavitModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="jointAffidavitForm" class="modal-form">
                    <div class="form-group">
                        <label for="firstPersonName">First Person Full Name <span class="required">*</span></label>
                        <input type="text" id="firstPersonName" name="firstPersonName" required placeholder="Enter first person's complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="secondPersonName">Second Person Full Name <span class="required">*</span></label>
                        <input type="text" id="secondPersonName" name="secondPersonName" required placeholder="Enter second person's complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="firstPersonAddress">First Person Complete Address <span class="required">*</span></label>
                        <textarea id="firstPersonAddress" name="firstPersonAddress" required placeholder="Enter first person's complete address including street, barangay, city, province" style="resize: vertical; min-height: 60px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="secondPersonAddress">Second Person Complete Address <span class="required">*</span></label>
                        <textarea id="secondPersonAddress" name="secondPersonAddress" required placeholder="Enter second person's complete address including street, barangay, city, province" style="resize: vertical; min-height: 60px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="childName">Name of Child <span class="required">*</span></label>
                        <input type="text" id="childName" name="childName" required placeholder="Enter child's full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="placeOfBirth">Place of Birth <span class="required">*</span></label>
                        <input type="text" id="placeOfBirth" name="placeOfBirth" required placeholder="Enter place of birth">
                    </div>
                    
                    <div class="form-group">
                        <label for="fatherName">Father's Full Name <span class="required">*</span></label>
                        <input type="text" id="fatherName" name="fatherName" required placeholder="Enter father's complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="motherName">Mother's Full Name <span class="required">*</span></label>
                        <input type="text" id="motherName" name="motherName" required placeholder="Enter mother's complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="childNameNumber4">Name of Child (for number 4) <span class="required">*</span></label>
                        <input type="text" id="childNameNumber4" name="childNameNumber4" required placeholder="Enter child's name for late registration">
                    </div>
                    
                    <div class="form-group">
                        <label for="jointDateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="jointDateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeJointAffidavitModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="saveJointAffidavit()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewJointAffidavitData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="jointAffidavitDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-users"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">First Person Full Name</label>
                            <div class="data-value" id="previewFirstPersonName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Second Person Full Name</label>
                            <div class="data-value" id="previewSecondPersonName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">First Person Complete Address</label>
                            <div class="data-value" id="previewFirstPersonAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Second Person Complete Address</label>
                            <div class="data-value" id="previewSecondPersonAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Name of Child</label>
                            <div class="data-value" id="previewChildName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Birth</label>
                            <div class="data-value" id="previewDateOfBirth">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Place of Birth</label>
                            <div class="data-value" id="previewPlaceOfBirth">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Father's Full Name</label>
                            <div class="data-value" id="previewFatherName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Mother's Full Name</label>
                            <div class="data-value" id="previewMotherName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Name of Child (for number 4)</label>
                            <div class="data-value" id="previewChildNameNumber4">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewJointDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="hideJointAffidavitData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="sendJointAffidavit()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sworn Affidavit of Mother Modal -->
    <div id="swornAffidavitMotherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-female"></i> Sworn Affidavit of Mother</h2>
                <span class="close" onclick="closeSwornAffidavitMotherModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="swornAffidavitMotherForm" class="modal-form">
                    <div class="form-group">
                        <label for="swornMotherFullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="swornMotherFullName" name="fullName" required placeholder="Enter your complete name">
                    </div>
                    
                    <div class="form-group">
                        <label for="swornMotherCompleteAddress">Complete Address <span class="required">*</span></label>
                        <textarea id="swornMotherCompleteAddress" name="completeAddress" required placeholder="Enter your complete address including street, barangay, city, province" style="resize: vertical; min-height: 60px; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; transition: all 0.3s ease; background: white; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="swornMotherChildName">Name of Child <span class="required">*</span></label>
                        <input type="text" id="swornMotherChildName" name="childName" required placeholder="Enter child's full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="swornMotherBirthDate">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="swornMotherBirthDate" name="birthDate" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="swornMotherBirthPlace">Place of Birth <span class="required">*</span></label>
                        <input type="text" id="swornMotherBirthPlace" name="birthPlace" required placeholder="Enter place of birth">
                    </div>
                    
                    <div class="form-group">
                        <label for="swornMotherDateOfNotary">Date of Notary <span class="required">*</span></label>
                        <input type="date" id="swornMotherDateOfNotary" name="dateOfNotary" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" onclick="closeSwornAffidavitMotherModal()" class="btn btn-secondary">Cancel</button>
                        <button type="button" onclick="saveSwornAffidavitMother()" class="btn btn-primary">Save</button>
                        <button type="button" onclick="viewSwornAffidavitMotherData()" class="btn btn-primary" style="background: #28a745;">View Data</button>
                    </div>
                </form>
                
                <!-- Data Preview Section -->
                <div id="swornAffidavitMotherDataPreview" class="data-preview">
                    <div class="data-preview-header">
                        <h3><i class="fas fa-female"></i> Data Preview</h3>
                    </div>
                    <div class="data-preview-content">
                        <div class="data-item">
                            <label class="data-label">Full Name</label>
                            <div class="data-value" id="previewSwornMotherFullName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Complete Address</label>
                            <div class="data-value" id="previewSwornMotherCompleteAddress">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Name of Child</label>
                            <div class="data-value" id="previewSwornMotherChildName">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Birth</label>
                            <div class="data-value" id="previewSwornMotherBirthDate">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Place of Birth</label>
                            <div class="data-value" id="previewSwornMotherBirthPlace">-</div>
                        </div>
                        <div class="data-item">
                            <label class="data-label">Date of Notary</label>
                            <div class="data-value" id="previewSwornMotherDateOfNotary">-</div>
                        </div>
                    </div>
                    <div class="data-actions">
                        <button type="button" onclick="hideSwornAffidavitMotherData()" class="btn btn-secondary">Edit</button>
                        <button type="button" onclick="sendSwornAffidavitMother()" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Access Modal -->
    <div id="requestAccessModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-lock"></i> Access Required</h2>
                <span class="close" onclick="closeRequestAccessModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Request Status View -->
                <div id="requestStatusView" class="request-access-content" style="display: <?= $can_submit_request ? 'none' : 'block' ?>;">
                    <div class="request-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <p>To generate legal documents, you need to request access first. This helps us verify your identity and provide better service.</p>
                    
                    <?php if ($request_status): ?>
                        <?php if ($request_status['status'] === 'Pending'): ?>
                            <div class="status-info pending">
                                <i class="fas fa-clock"></i>
                                <h3>Request Under Review</h3>
                                <p>Your request is currently being reviewed by our team. You will be notified once it's approved.</p>
                            </div>
                        <?php elseif ($request_status['status'] === 'Rejected'): ?>
                            <div class="status-info rejected">
                                <i class="fas fa-times-circle"></i>
                                <h3>Request Rejected</h3>
                                <p>Your request was not approved. You can submit a new request with updated information.</p>
                                <?php if ($request_status['review_notes']): ?>
                                    <div class="review-notes">
                                        <strong>Review Notes:</strong>
                                        <p><?= htmlspecialchars($request_status['review_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="request-actions">
                        <?php if ($can_submit_request): ?>
                            <button onclick="showRequestForm()" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Request Access
                            </button>
                        <?php else: ?>
                            <?php if ($request_status['status'] === 'Pending'): ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-clock"></i>
                                    Request Pending Review
                                </button>
                                <div class="request-status-info">
                                    <small>Your request is currently being reviewed. You will be notified once a decision is made.</small>
                                </div>
                            <?php elseif ($request_status['status'] === 'Approved'): ?>
                                <button class="btn btn-success" disabled>
                                    <i class="fas fa-check-circle"></i>
                                    Request Approved
                                </button>
                                <div class="request-status-info">
                                    <small>Your request has been approved. You can now access the document generation system.</small>
                                </div>
                            <?php elseif ($request_status['status'] === 'Rejected'): ?>
                                <button onclick="showRequestForm()" class="btn btn-warning">
                                    <i class="fas fa-redo"></i>
                                    Submit New Request
                                </button>
                                <div class="request-status-info">
                                    <small>Your previous request was rejected. You can submit a new request with updated information.</small>
                                    <?php if (!empty($request_status['review_notes'])): ?>
                                        <br><br>
                                        <strong>Review Notes:</strong><br>
                                        <em><?= nl2br(htmlspecialchars($request_status['review_notes'])) ?></em>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-info-circle"></i>
                                    Request Status: <?= htmlspecialchars($request_status['status']) ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <button onclick="closeRequestAccessModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Close
                        </button>
                    </div>
                </div>

                <!-- Request Form View -->
                <div id="requestFormView" class="request-form-content" style="display: <?= $can_submit_request ? 'block' : 'none' ?>;">
                    <form id="requestAccessForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="submit_request">
                        
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-user"></i>
                                Personal Information
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">Full Name *</label>
                                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($_SESSION['client_name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sex">Sex *</label>
                                    <select id="sex" name="sex" required>
                                        <option value="">Select Sex</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Contact Information Section -->
                            <div class="form-group">
                                <label for="address">Address *</label>
                                <textarea id="address" name="address" rows="4" placeholder="Enter your complete address" required></textarea>
                            </div>
                        </div>
                        
                        <!-- Legal Concern Section -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-gavel"></i>
                                Legal Concern
                            </div>
                            <div class="form-group">
                                <label for="concern_description">Legal Concern/Issue *</label>
                                <textarea id="concern_description" name="concern_description" rows="6" placeholder="Please describe your legal concern or issue in detail. Include relevant facts, dates, and any specific questions you have. The more information you provide, the better we can assist you." required></textarea>
                                <div class="field-help">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Include key details such as: What happened? When did it occur? Who is involved? What outcome are you seeking?</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Document Upload Section -->
                        <div class="form-group full-width-field">
                            <div class="form-section-title">
                                <i class="fas fa-file-upload"></i>
                                Government ID Documents
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="valid_id_front">Government ID Front *</label>
                            <div class="file-upload-container">
                                <input type="file" id="valid_id_front" name="valid_id_front" accept="image/jpeg,image/jpg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf" required>
                                <label for="valid_id_front" class="file-upload-label">
                                    <i class="fas fa-upload"></i>
                                    <span>Choose Front Image</span>
                                </label>
                                <div class="file-info">
                                    <small>Accepted formats: JPG, PNG, PDF (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="valid_id_back">Government ID Back *</label>
                            <div class="file-upload-container">
                                <input type="file" id="valid_id_back" name="valid_id_back" accept="image/jpeg,image/jpg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf" required>
                                <label for="valid_id_back" class="file-upload-label">
                                    <i class="fas fa-upload"></i>
                                    <span>Choose Back Image</span>
                                </label>
                                <div class="file-info">
                                    <small>Accepted formats: JPG, PNG, PDF (Max: 5MB)</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Privacy Consent -->
                        <div class="form-group full-width-field">
                            <div class="form-section-title">
                                <i class="fas fa-shield-alt"></i>
                                Privacy Consent
                            </div>
                            
                            <div class="privacy-checkbox-container">
                                <input type="checkbox" id="privacy_consent" name="privacy_consent" required>
                                <label for="privacy_consent" class="privacy-label">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>I agree to the Data Privacy Act (Philippines - RA 10173)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" onclick="showRequestStatus()" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <script src="assets/js/modal-functions.js?v=<?= time() ?>"></script>
    <script src="assets/js/form-handlers.js?v=<?= time() ?>"></script>
    <script src="assets/js/document-actions.js?v=<?= time() ?>"></script>
    <script src="assets/js/document-viewer.js?v=<?= time() ?>"></script>
    
    <script>
        // Data Preview Functions
        function viewAffidavitLossData() {
            const form = document.getElementById('affidavitLossForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewCompleteAddress').textContent = formData.get('completeAddress') || '-';
            document.getElementById('previewSpecifyItemLost').textContent = formData.get('specifyItemLost') || '-';
            document.getElementById('previewItemLost').textContent = formData.get('itemLost') || '-';
            document.getElementById('previewItemDetails').textContent = formData.get('itemDetails') || '-';
            document.getElementById('previewDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('affidavitLossDataPreview').style.display = 'block';
        }

        function hideAffidavitLossData() {
            document.getElementById('affidavitLossForm').style.display = 'block';
            document.getElementById('affidavitLossDataPreview').style.display = 'none';
        }

        // Sworn Affidavit (Solo Parent) Modal Functions
        function openSoloParentModal() {
            document.getElementById('soloParentModal').style.display = 'block';
        }
        function closeSoloParentModal() {
            document.getElementById('soloParentModal').style.display = 'none';
        }
        
        function addChild() {
            const container = document.getElementById('childrenContainer');
            const childEntry = document.createElement('div');
            childEntry.className = 'child-entry';
            childEntry.innerHTML = `
                <input type="text" name="childrenNames[]" placeholder="Child's Name" required>
                <input type="number" name="childrenAges[]" placeholder="Age" min="0" max="120" required>
                <button type="button" onclick="removeChild(this)" class="btn btn-danger btn-sm">Remove</button>
            `;
            container.appendChild(childEntry);
        }
        
        function removeChild(button) {
            button.parentElement.remove();
        }
        
        function saveSoloParent() {
            alert('Data saved successfully!');
        }
        
        function viewSoloParentData() {
            const form = document.getElementById('soloParentForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewSoloFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewSoloCompleteAddress').textContent = formData.get('completeAddress') || '-';
            
            // Handle children
            const names = formData.getAll('childrenNames[]');
            const ages = formData.getAll('childrenAges[]');
            const children = names.map((name, index) => {
                const age = ages[index] || '';
                return name ? (age ? `${name} (${age})` : name) : null;
            }).filter(Boolean);
            document.getElementById('previewChildren').textContent = children.length ? children.join(', ') : '-';
            
            document.getElementById('previewYearsUnderCase').textContent = formData.get('yearsUnderCase') || '-';
            
            // Handle reason with conditional field
            let reasonText = formData.get('reasonSection') || '-';
            if (reasonText === 'Other reason, please state' && formData.get('otherReason')) {
                reasonText = `${reasonText}: ${formData.get('otherReason')}`;
            }
            document.getElementById('previewReason').textContent = reasonText;
            
            // Handle employment status with conditional fields
            let employmentText = formData.get('employmentStatus') || '-';
            if (employmentText === 'Employee and earning' && formData.get('employeeAmount')) {
                employmentText = `${employmentText} Php ${formData.get('employeeAmount')}`;
            } else if (employmentText === 'Self-employed and earning' && formData.get('selfEmployedAmount')) {
                employmentText = `${employmentText} Php ${formData.get('selfEmployedAmount')}`;
            } else if (employmentText === 'Un-employed and dependent upon' && formData.get('unemployedDependent')) {
                employmentText = `${employmentText} ${formData.get('unemployedDependent')}`;
            }
            document.getElementById('previewEmployment').textContent = employmentText;
            
            document.getElementById('previewSoloDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('soloParentDataPreview').style.display = 'block';
        }
        
        function saHideData() {
            document.getElementById('soloParentForm').style.display = 'block';
            document.getElementById('soloParentDataPreview').style.display = 'none';
        }
        
        function saSend() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('soloParentForm');
            const formData = new FormData(form);
                
            const data = {
                fullName: formData.get('fullName'),
                completeAddress: formData.get('completeAddress'),
                childrenNames: formData.getAll('childrenNames[]'),
                childrenAges: formData.getAll('childrenAges[]'),
                yearsUnderCase: formData.get('yearsUnderCase'),
                reasonSection: formData.get('reasonSection'),
                otherReason: formData.get('otherReason'),
                employmentStatus: formData.get('employmentStatus'),
                employeeAmount: formData.get('employeeAmount'),
                selfEmployedAmount: formData.get('selfEmployedAmount'),
                unemployedDependent: formData.get('unemployedDependent'),
                dateOfNotary: formData.get('dateOfNotary')
            };

            sendDocumentToEmployee('soloParent', data);
            }
        }
        
        function toggleOtherReason() {
            const otherReasonContainer = document.getElementById('otherReasonContainer');
            const selectedReason = document.querySelector('input[name="reasonSection"]:checked');
            
            if (selectedReason && selectedReason.value === 'Other reason, please state') {
                otherReasonContainer.style.display = 'block';
            } else {
                otherReasonContainer.style.display = 'none';
            }
        }
        
        function toggleEmploymentFields() {
            const employeeContainer = document.getElementById('employeeAmountContainer');
            const selfEmployedContainer = document.getElementById('selfEmployedAmountContainer');
            const unemployedContainer = document.getElementById('unemployedDependentContainer');
            const selectedEmployment = document.querySelector('input[name="employmentStatus"]:checked');
            
            // Hide all containers first
            employeeContainer.style.display = 'none';
            selfEmployedContainer.style.display = 'none';
            unemployedContainer.style.display = 'none';
            
            // Show relevant container based on selection
            if (selectedEmployment) {
                if (selectedEmployment.value === 'Employee and earning') {
                    employeeContainer.style.display = 'block';
                } else if (selectedEmployment.value === 'Self-employed and earning') {
                    selfEmployedContainer.style.display = 'block';
                } else if (selectedEmployment.value === 'Un-employed and dependent upon') {
                    unemployedContainer.style.display = 'block';
                }
            }
        }

        function viewPWDLossData() {
            const form = document.getElementById('pwdLossForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewPwdFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewPwdFullAddress').textContent = formData.get('fullAddress') || '-';
            document.getElementById('previewPwdDetailsOfLoss').textContent = formData.get('detailsOfLoss') || '-';
            document.getElementById('previewPwdDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('pwdLossDataPreview').style.display = 'block';
        }

        function hidePWDLossData() {
            document.getElementById('pwdLossForm').style.display = 'block';
            document.getElementById('pwdLossDataPreview').style.display = 'none';
        }

        function viewBoticabLossData() {
            const form = document.getElementById('boticabLossForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewBoticabFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewBoticabFullAddress').textContent = formData.get('fullAddress') || '-';
            document.getElementById('previewBoticabDetailsOfLoss').textContent = formData.get('detailsOfLoss') || '-';
            document.getElementById('previewBoticabDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('boticabLossDataPreview').style.display = 'block';
        }

        function hideBoticabLossData() {
            document.getElementById('boticabLossForm').style.display = 'block';
            document.getElementById('boticabLossDataPreview').style.display = 'none';
        }

        // Joint Affidavit Modal Functions
        function openJointAffidavitModal() {
            document.getElementById('jointAffidavitModal').style.display = 'block';
        }

        function closeJointAffidavitModal() {
            document.getElementById('jointAffidavitModal').style.display = 'none';
        }

        function saveJointAffidavit() {
            // Save functionality - can be implemented later if needed
            alert('Data saved successfully!');
        }

        function viewJointAffidavitData() {
            const form = document.getElementById('jointAffidavitForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewFirstPersonName').textContent = formData.get('firstPersonName') || '-';
            document.getElementById('previewSecondPersonName').textContent = formData.get('secondPersonName') || '-';
            document.getElementById('previewFirstPersonAddress').textContent = formData.get('firstPersonAddress') || '-';
            document.getElementById('previewSecondPersonAddress').textContent = formData.get('secondPersonAddress') || '-';
            document.getElementById('previewChildName').textContent = formData.get('childName') || '-';
            document.getElementById('previewDateOfBirth').textContent = formData.get('dateOfBirth') || '-';
            document.getElementById('previewPlaceOfBirth').textContent = formData.get('placeOfBirth') || '-';
            document.getElementById('previewFatherName').textContent = formData.get('fatherName') || '-';
            document.getElementById('previewMotherName').textContent = formData.get('motherName') || '-';
            document.getElementById('previewChildNameNumber4').textContent = formData.get('childNameNumber4') || '-';
            document.getElementById('previewJointDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('jointAffidavitDataPreview').style.display = 'block';
        }

        function hideJointAffidavitData() {
            document.getElementById('jointAffidavitForm').style.display = 'block';
            document.getElementById('jointAffidavitDataPreview').style.display = 'none';
        }

        function sendJointAffidavit() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('jointAffidavitForm');
                const formData = new FormData(form);
                
                const data = {
                    firstPersonName: formData.get('firstPersonName'),
                    secondPersonName: formData.get('secondPersonName'),
                    firstPersonAddress: formData.get('firstPersonAddress'),
                    secondPersonAddress: formData.get('secondPersonAddress'),
                    childName: formData.get('childName'),
                    dateOfBirth: formData.get('dateOfBirth'),
                    placeOfBirth: formData.get('placeOfBirth'),
                    fatherName: formData.get('fatherName'),
                    motherName: formData.get('motherName'),
                    childNameNumber4: formData.get('childNameNumber4'),
                    dateOfNotary: formData.get('dateOfNotary')
                };

                sendDocumentToEmployee('jointAffidavit', data);
            }
        }

        // Sworn Affidavit of Mother Modal Functions
        function openSwornAffidavitMotherModal() {
            document.getElementById('swornAffidavitMotherModal').style.display = 'block';
        }

        function closeSwornAffidavitMotherModal() {
            document.getElementById('swornAffidavitMotherModal').style.display = 'none';
        }

        function saveSwornAffidavitMother() {
            // Save functionality - can be implemented later if needed
            alert('Data saved successfully!');
        }

        function viewSwornAffidavitMotherData() {
            const form = document.getElementById('swornAffidavitMotherForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewSwornMotherFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewSwornMotherCompleteAddress').textContent = formData.get('completeAddress') || '-';
            document.getElementById('previewSwornMotherChildName').textContent = formData.get('childName') || '-';
            document.getElementById('previewSwornMotherBirthDate').textContent = formData.get('birthDate') || '-';
            document.getElementById('previewSwornMotherBirthPlace').textContent = formData.get('birthPlace') || '-';
            document.getElementById('previewSwornMotherDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('swornAffidavitMotherDataPreview').style.display = 'block';
        }

        function hideSwornAffidavitMotherData() {
            document.getElementById('swornAffidavitMotherForm').style.display = 'block';
            document.getElementById('swornAffidavitMotherDataPreview').style.display = 'none';
        }

        function sendSwornAffidavitMother() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('swornAffidavitMotherForm');
                const formData = new FormData(form);
                
                const data = {
                    fullName: formData.get('fullName'),
                    completeAddress: formData.get('completeAddress'),
                    childName: formData.get('childName'),
                    birthDate: formData.get('birthDate'),
                    birthPlace: formData.get('birthPlace'),
                    dateOfNotary: formData.get('dateOfNotary')
                };

                sendDocumentToEmployee('swornAffidavitMother', data);
            }
        }

        // Senior ID Loss Modal Functions
        function openSeniorIDLossModal() {
            document.getElementById('seniorIDLossModal').style.display = 'block';
        }

        function closeSeniorIDLossModal() {
            document.getElementById('seniorIDLossModal').style.display = 'none';
        }

        function saveSeniorIDLoss() {
            const form = document.getElementById('seniorIDLossForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewSeniorFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewSeniorCompleteAddress').textContent = formData.get('completeAddress') || '-';
            document.getElementById('previewSeniorRelationship').textContent = formData.get('relationship') || '-';
            document.getElementById('previewSeniorCitizenName').textContent = formData.get('seniorCitizenName') || '-';
            document.getElementById('previewSeniorDetailsOfLoss').textContent = formData.get('detailsOfLoss') || '-';
            document.getElementById('previewSeniorDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('seniorIDLossDataPreview').style.display = 'block';
            
            alert('Data saved successfully!');
        }

        function viewSeniorIDLossData() {
            const form = document.getElementById('seniorIDLossForm');
            const formData = new FormData(form);
            
            // Update preview values
            document.getElementById('previewSeniorFullName').textContent = formData.get('fullName') || '-';
            document.getElementById('previewSeniorCompleteAddress').textContent = formData.get('completeAddress') || '-';
            document.getElementById('previewSeniorRelationship').textContent = formData.get('relationship') || '-';
            document.getElementById('previewSeniorCitizenName').textContent = formData.get('seniorCitizenName') || '-';
            document.getElementById('previewSeniorDetailsOfLoss').textContent = formData.get('detailsOfLoss') || '-';
            document.getElementById('previewSeniorDateOfNotary').textContent = formData.get('dateOfNotary') || '-';
            
            // Show preview, hide form
            form.style.display = 'none';
            document.getElementById('seniorIDLossDataPreview').style.display = 'block';
        }

        function hideSeniorIDLossData() {
            document.getElementById('seniorIDLossForm').style.display = 'block';
            document.getElementById('seniorIDLossDataPreview').style.display = 'none';
        }

        function sendSeniorIDLoss() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('seniorIDLossForm');
                const formData = new FormData(form);
                
                const data = {
                    fullName: formData.get('fullName'),
                    completeAddress: formData.get('completeAddress'),
                    relationship: formData.get('relationship'),
                    seniorCitizenName: formData.get('seniorCitizenName'),
                    detailsOfLoss: formData.get('detailsOfLoss'),
                    dateOfNotary: formData.get('dateOfNotary')
                };

                sendDocumentToEmployee('seniorIDLoss', data);
            }
        }


        // Send Document Functions
        function sendAffidavitLoss() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('affidavitLossForm');
                const formData = new FormData(form);
                
                const data = {
                    fullName: formData.get('fullName'),
                    completeAddress: formData.get('completeAddress'),
                    specifyItemLost: formData.get('specifyItemLost'),
                    itemLost: formData.get('itemLost'),
                    itemDetails: formData.get('itemDetails'),
                    dateOfNotary: formData.get('dateOfNotary')
                };

                sendDocumentToEmployee('affidavitLoss', data);
            }
        }

        // sendSoloParent removed - direct generation now

        function sendPWDLoss() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('pwdLossForm');
                const formData = new FormData(form);
                
                const data = {
                    fullName: formData.get('fullName'),
                    fullAddress: formData.get('fullAddress'),
                    detailsOfLoss: formData.get('detailsOfLoss'),
                    dateOfNotary: formData.get('dateOfNotary')
                };

                sendDocumentToEmployee('pwdLoss', data);
            }
        }

        function sendBoticabLoss() {
            if (confirm('Are you sure you want to send this document to the employee? This action cannot be undone.')) {
                const form = document.getElementById('boticabLossForm');
                const formData = new FormData(form);
                
                const data = {
                    fullName: formData.get('fullName'),
                    fullAddress: formData.get('fullAddress'),
                    detailsOfLoss: formData.get('detailsOfLoss'),
                    dateOfNotary: formData.get('dateOfNotary')
                };

                sendDocumentToEmployee('boticabLoss', data);
            }
        }

        // Generic function to send document to employee
        function sendDocumentToEmployee(formType, formData) {
            // Get the send button and prevent double-clicking
            const sendBtn = event.target;
            if (sendBtn.disabled) {
                return; // Already processing
            }
            
            const originalText = sendBtn.innerHTML;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            sendBtn.disabled = true;

            // Send data to server
            fetch('send_document_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'form_type=' + encodeURIComponent(formType) + '&form_data=' + encodeURIComponent(JSON.stringify(formData))
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                });
            })
            .then(result => {
                sendBtn.innerHTML = originalText;
                sendBtn.disabled = false;
                
                if (result.status === 'success') {
                    alert('Document sent successfully to the employee!');
                    // Close the modal
                    const modal = document.querySelector('.modal[style*="block"]');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                    // Refresh notifications to show any new ones
                    loadNotifications();
                } else {
                    alert('Error: ' + result.message);
                    console.error('Error details:', result.debug_info);
                }
            })
            .catch(error => {
                sendBtn.innerHTML = originalText;
                sendBtn.disabled = false;
                console.error('Error:', error);
                alert('Error sending document: ' + error.message);
            });
        }

        // Notifications functionality
        let notificationsVisible = false;

        // Close notifications when clicking outside
        document.addEventListener('click', function(event) {
            const notificationsBtn = document.getElementById('notificationsBtn');
            const dropdown = document.getElementById('notificationsDropdown');
            
            if (notificationsBtn && dropdown && !notificationsBtn.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
                notificationsVisible = false;
            }
        });

        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    updateNotificationBadge(data.unread_count);
                    displayNotifications(data.notifications);
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notificationBadge');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        function displayNotifications(notifications) {
            const container = document.getElementById('notificationsList');
            
            if (notifications.length === 0) {
                container.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">No notifications</div>';
                return;
            }
            
            container.innerHTML = notifications.map(notification => `
                <div style="padding: 12px; border-bottom: 1px solid #f3f4f6; ${!notification.is_read ? 'background: #f0f8ff;' : ''}">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 14px; color: #1a202c; margin-bottom: 4px;">${notification.title}</div>
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">${notification.message}</div>
                            <div style="font-size: 11px; color: #9ca3af;">${formatTime(notification.created_at)}</div>
                        </div>
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: ${getNotificationColor(notification.type)}; margin-left: 8px; ${notification.is_read ? 'display: none;' : ''}"></div>
                    </div>
                </div>
            `).join('');
        }

        function getNotificationColor(type) {
            switch (type) {
                case 'success': return '#10b981';
                case 'warning': return '#f59e0b';
                case 'error': return '#ef4444';
                default: return '#3b82f6';
            }
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
            return date.toLocaleDateString();
        }

        function markAllAsRead() {
            fetch('get_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_read=true'
            })
            .then(() => {
                loadNotifications();
            })
            .catch(error => console.error('Error marking notifications as read:', error));
        }

        // Load document status
        function loadDocumentStatus() {
            fetch('get_client_document_status.php')
                .then(response => response.json())
                .then(data => {
                    displayDocumentStatus(data.documents);
                })
                .catch(error => {
                    console.error('Error loading document status:', error);
                    document.getElementById('documentStatusContainer').innerHTML = 
                        '<div class="error-state" style="text-align: center; padding: 40px; color: #dc3545;">' +
                        '<i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>' +
                        '<p>Error loading document status. Please try again later.</p>' +
                        '</div>';
                });
        }

        function displayDocumentStatus(documents) {
            const container = document.getElementById('documentStatusContainer');
            
            if (!documents || documents.length === 0) {
                container.innerHTML = 
                    '<div class="empty-state" style="text-align: center; padding: 40px; color: #6c757d;">' +
                    '<i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 15px;"></i>' +
                    '<h3>No Document Submissions</h3>' +
                    '<p>You haven\'t submitted any documents yet. Use the forms above to generate and submit your legal documents.</p>' +
                    '</div>';
                return;
            }
            
            const documentsHtml = documents.map(doc => {
                const statusClass = doc.status.toLowerCase();
                
                // Format document type for display
                const documentTypeDisplay = doc.document_type ? 
                    doc.document_type.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase()) : 
                    'Unknown Document Type';
                
                return `
                    <div class="document-status-item">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 1.1rem; font-weight: 700; letter-spacing: -0.025em;">${documentTypeDisplay}</h3>
                                <p style="margin: 0; color: #6b7280; font-size: 0.9rem; font-weight: 500;">Request ID: <span style="color: #374151; font-family: 'Courier New', monospace;">${doc.request_id}</span></p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 8px 16px; background: rgba(255,255,255,0.8); border-radius: 20px; border: 1px solid #e5e7eb;">
                                <span class="status-indicator status-${statusClass}"></span>
                                <span style="color: #374151; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">${doc.status}</span>
                            </div>
                        </div>
                        
                        <div class="document-meta">
                            <div class="document-meta-item">
                                <span class="document-meta-label">Submitted</span>
                                <span class="document-meta-value">${formatDate(doc.submitted_at)}</span>
                            </div>
                            ${doc.reviewed_at ? `
                            <div class="document-meta-item">
                                <span class="document-meta-label">Reviewed</span>
                                <span class="document-meta-value">${formatDate(doc.reviewed_at)}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${doc.rejection_reason ? `
                        <div class="rejection-reason">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <i class="fas fa-exclamation-triangle" style="color: #ef4444; font-size: 0.9rem;"></i>
                                <span style="color: #991b1b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Rejection Reason</span>
                            </div>
                            <p style="margin: 0; color: #7f1d1d; font-size: 0.9rem; line-height: 1.5; font-weight: 500;">${doc.rejection_reason}</p>
                        </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            container.innerHTML = documentsHtml;
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Request Access Modal Functions
        function openDocumentRequestModal() {
            document.getElementById('requestAccessModal').style.display = 'block';
        }
        
        function closeRequestAccessModal() {
            document.getElementById('requestAccessModal').style.display = 'none';
        }
        
        function showRequestForm() {
            document.getElementById('requestStatusView').style.display = 'none';
            document.getElementById('requestFormView').style.display = 'block';
        }
        
        function showRequestStatus() {
            document.getElementById('requestFormView').style.display = 'none';
            document.getElementById('requestStatusView').style.display = 'block';
        }
        
        // Handle form submission
        document.getElementById('requestAccessForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            fetch('client_request_access.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Check if submission was successful by looking for success indicators
                if (data.includes('success') || data.includes('Request submitted successfully')) {
                    alert('Request submitted successfully! You will be notified once it\'s reviewed.');
                    closeRequestAccessModal();
                    // Reload the page to update the status
                    location.reload();
                } else {
                    alert('Error submitting request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting request. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            loadDocumentStatus();
            
            // Refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);
            
            // Notifications button click handler
            const notificationsBtn = document.getElementById('notificationsBtn');
            if (notificationsBtn) {
                notificationsBtn.addEventListener('click', function() {
                    const dropdown = document.getElementById('notificationsDropdown');
                    const isVisible = dropdown.style.display === 'block';
                    dropdown.style.display = isVisible ? 'none' : 'block';
                    
                    if (!isVisible) {
                        loadNotifications();
                    }
                });
            }
        });
    </script>


</body>
</html> 