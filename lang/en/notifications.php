<?php

return [
    // Card notifications
    'card_activated' => 'Your :type :brand card ending in :last4 has been activated and is ready to use.',
    'card_frozen' => 'Your card ending in :last4 has been frozen.',
    'card_pin_changed' => 'The PIN for your card ending in :last4 has been changed successfully.',
    'card_pin_frozen' => 'Your card ending in :last4 has been frozen due to :attempts incorrect PIN attempts. Please contact support.',
    'card_created_inactive' => 'Your card has been created but is inactive. Please upload your passport and national ID to activate it.',
    'card_activated_last4' => 'Your card ending in :last4 has been activated and is ready to use.',

    // Loan notifications
    'loan_submitted' => 'Your :type loan application for $:amount has been submitted and is under review.',
    'loan_approved' => 'Your :type loan of $:amount has been approved!',
    'loan_rejected' => 'Your :type loan application has been rejected. Reason: :reason',
    'loan_rejected_reserves' => 'Your loan application for $:amount could not be processed at this time. The bank\'s lending capacity has been temporarily reached. Please try again later.',

    // Transfer notifications
    'transfer_pending' => ':name wants to send you $:amount. Accept or decline this transfer.',
    'transfer_accepted' => ':name accepted your transfer of $:amount.',
    'transfer_declined' => ':name declined your transfer of $:amount. Funds have been released.',
    'transfer_cancelled' => ':name cancelled their transfer of $:amount.',
    'transfer_expired' => 'Your pending transfer of $:amount to :name has expired. Funds have been released.',
    'transfer_received' => 'You received $:amount from :name.',

    // KYC notifications
    'kyc_account_created' => 'Your account has been created at the :branch branch. To activate all features, please upload your identity documents.',
    'kyc_document_rejected' => 'Your :type was rejected: :reason. Please re-upload.',
    'kyc_document_verified' => 'Your :type has been verified.',
    'kyc_passport_needed' => ' Please also upload your Passport.',
    'kyc_national_id_needed' => ' Please also upload your National ID.',
    'kyc_both_needed' => ' Please also upload your Passport and National ID.',
    'kyc_identity_verified' => 'Your identity has been verified and your account is now fully active. All features are unlocked.',
    'kyc_both_verified' => 'Both your Passport and National ID have been verified. Your account is now fully active!',

    // Account notifications
    'account_status_changed' => 'Your account has been :status. :details',
    'account_activated' => 'Your account is now fully active.',

    // Cash/ATM notifications
    'cash_withdrawal' => 'You have successfully withdrawn $:amount from your account.',
    'cash_deposit' => 'A cash deposit of $:amount has been added to your account.',
    'branch_withdrawal' => 'A cash withdrawal of $:amount was processed at the branch.',
    'branch_deposit' => 'A deposit of $:amount was made to your account :account.',

    // Support notifications
    'ticket_staff_reply' => 'Staff responded to your ticket :number: :subject',
    'ticket_status_updated' => 'Your ticket :number status changed from :old to :new.',

    // Status messages
    'status_active' => 'All features are now available.',
    'status_inactive' => 'Please contact support for assistance.',
    'status_suspended' => 'Please contact support for more information.',
    'status_frozen' => 'Please contact support for security reasons.',
];
