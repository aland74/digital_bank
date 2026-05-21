<?php

return [
    // Card notifications
    'card_activated' => 'کارتەکەت لە جۆری :type :brand کە کۆتایی دێت بە :last4 چالاککراوە و ئامادەیە بۆ بەکارهێنان.',
    'card_frozen' => 'کارتەکەت کە کۆتایی دێت بە :last4 سڕکراوە.',
    'card_pin_changed' => 'پین کۆدی کارتەکەت کە کۆتایی دێت بە :last4 بە سەرکەوتوویی گۆڕدرا.',
    'card_pin_frozen' => 'کارتەکەت کە کۆتایی دێت بە :last4 سڕکراوە بەهۆی :attempts هەوڵی هەڵەی پین کۆد. تکایە پەیوەندی بە پاڵپشتییەوە بکە.',
    'card_created_inactive' => 'کارتەکەت دروستکراوە بەڵام ناچالاکە. تکایە پاسپۆرت و پێناسەی نیشتیمانیت باربکە بۆ چالاککردنی.',
    'card_activated_last4' => 'کارتەکەت کە کۆتایی دێت بە :last4 چالاککراوە و ئامادەیە بۆ بەکارهێنان.',

    // Loan notifications
    'loan_submitted' => 'داواکاری قەرزەکەت لە جۆری :type بە بڕی $:amount نێردراوە و لە ژێر پێداچوونەوەدایە.',
    'loan_approved' => 'قەرزەکەت لە جۆری :type بە بڕی $:amount پەسەندکرا!',
    'loan_rejected' => 'داواکاری قەرزەکەت لە جۆری :type ڕەتکرایەوە. هۆکار: :reason',
    'loan_rejected_reserves' => 'داواکاری قەرزەکەت بە بڕی $:amount لەم کاتەدا ناتوانرێت جێبەجێبکرێت. توانای قەرزدانی بانک بە کاتی گەیشتووەتە ئەوپەڕی. تکایە دواتر هەوڵبدەرەوە.',

    // Transfer notifications
    'transfer_pending' => ':name دەیەوێت $:amount بنێرێت بۆت. ئەم گواستنەوەیە پەسەند بکە یان ڕەتبکەرەوە.',
    'transfer_accepted' => ':name گواستنەوەکەی بە بڕی $:amount پەسەندکرد.',
    'transfer_declined' => ':name گواستنەوەکەی بە بڕی $:amount ڕەتکردەوە. پارەکە ئازادکراوە.',
    'transfer_cancelled' => ':name گواستنەوەکەی بە بڕی $:amount هەڵوەشاندەوە.',
    'transfer_expired' => 'گواستنەوە هەڵواسراوەکەت بە بڕی $:amount بۆ :name بەسەرچووە. پارەکە ئازادکراوە.',
    'transfer_received' => 'بڕی $:amount لەلایەن :nameوە پێگەیشت.',

    // KYC notifications
    'kyc_account_created' => 'هەژمارەکەت لە لقی :branch دروستکراوە. بۆ چالاککردنی هەموو تایبەتمەندییەکان، تکایە بەڵگەنامەکانی ناسنامەت باربکە.',
    'kyc_document_rejected' => 'بەڵگەنامەکەت (:type) ڕەتکرایەوە: :reason. تکایە دووبارە باری بکەرەوە.',
    'kyc_document_verified' => 'بەڵگەنامەکەت (:type) پەسەندکرا.',
    'kyc_passport_needed' => ' تکایە پاسپۆرتەکەشت باربکە.',
    'kyc_national_id_needed' => ' تکایە پێناسەی نیشتیمانیشت باربکە.',
    'kyc_both_needed' => ' تکایە پاسپۆرت و پێناسەی نیشتیمانیشت باربکە.',
    'kyc_identity_verified' => 'ناسنامەکەت پەسەندکرا و هەژمارەکەت ئێستا بە تەواوی چالاکە. هەموو تایبەتمەندییەکان کراونەتەوە.',
    'kyc_both_verified' => 'هەردوو پاسپۆرت و پێناسەی نیشتیمانیت پەسەندکراون. هەژمارەکەت ئێستا بە تەواوی چالاکە!',

    // Account notifications
    'account_status_changed' => 'هەژمارەکەت :status. :details',
    'account_activated' => 'هەژمارەکەت ئێستا بە تەواوی چالاکە.',

    // Cash/ATM notifications
    'cash_withdrawal' => 'بە سەرکەوتوویی بڕی $:amount لە هەژمارەکەت ڕاکێشا.',
    'cash_deposit' => 'بڕی $:amount وەک کاش خرایە سەر هەژمارەکەت.',
    'branch_withdrawal' => 'بڕی $:amount کاش لە لقەکەمان ڕاکێشرا.',
    'branch_deposit' => 'بڕی $:amount وەک کاش خرایە سەر هەژمارەکەت :account.',

    // Support notifications
    'ticket_staff_reply' => 'ستاف وەڵامی تیکەتەکەت :number داگراند: :subject',
    'ticket_status_updated' => 'دۆخی تیکەتەکەت :number لە :old بۆ :new گۆڕدرا.',

    // Status messages
    'status_active' => 'هەموو تایبەتمەندییەکان ئێستا بەردەستن.',
    'status_inactive' => 'تکایە پەیوەندی بە پاڵپشتییەوە بکە بۆ یارمەتی.',
    'status_suspended' => 'تکایە پەیوەندی بە پاڵپشتییەوە بکە بۆ زانیاری زیاتر.',
    'status_frozen' => 'تکایە پەیوەندی بە پاڵپشتییەوە بکە بۆ هۆکارەکانی ئاسایش.',
];
