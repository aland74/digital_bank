<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates MySQL database-level triggers on the `cards` table to enforce KYC at the
 * database layer. This is a defence-in-depth measure — the application layer also
 * enforces KYC in the model, controller, and UI.
 *
 * The triggers fire BEFORE INSERT and BEFORE UPDATE. If someone tries to create or
 * activate a card with status='active' for a user who does NOT have both a verified
 * passport AND a verified national_id in `kyc_documents`, the query is rejected with
 * a descriptive SIGNAL error.
 *
 * NOTE: This migration is skipped for SQLite (used in testing) since SQLite does not
 * support the same SIGNAL/RESIGNAL syntax.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only create triggers for MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop triggers if they exist (idempotent)
        DB::unprepared('DROP TRIGGER IF EXISTS trg_card_kyc_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_card_kyc_before_update');

        // BEFORE INSERT trigger: block active card creation without KYC
        DB::unprepared("
            CREATE TRIGGER trg_card_kyc_before_insert
            BEFORE INSERT ON cards
            FOR EACH ROW
            BEGIN
                DECLARE passport_count INT DEFAULT 0;
                DECLARE natid_count   INT DEFAULT 0;

                IF NEW.status = 'active' THEN
                    SELECT COUNT(*) INTO passport_count
                    FROM kyc_documents
                    WHERE user_id       = NEW.user_id
                      AND document_type = 'passport'
                      AND status        = 'verified'
                    LIMIT 1;

                    SELECT COUNT(*) INTO natid_count
                    FROM kyc_documents
                    WHERE user_id       = NEW.user_id
                      AND document_type = 'national_id'
                      AND status        = 'verified'
                    LIMIT 1;

                    IF passport_count = 0 OR natid_count = 0 THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'KYC_REQUIRED: Cannot create an active card without verified Passport and National ID.';
                    END IF;
                END IF;
            END
        ");

        // BEFORE UPDATE trigger: block activating an existing card without KYC
        DB::unprepared("
            CREATE TRIGGER trg_card_kyc_before_update
            BEFORE UPDATE ON cards
            FOR EACH ROW
            BEGIN
                DECLARE passport_count INT DEFAULT 0;
                DECLARE natid_count   INT DEFAULT 0;

                IF NEW.status = 'active' AND OLD.status != 'active' THEN
                    SELECT COUNT(*) INTO passport_count
                    FROM kyc_documents
                    WHERE user_id       = NEW.user_id
                      AND document_type = 'passport'
                      AND status        = 'verified'
                    LIMIT 1;

                    SELECT COUNT(*) INTO natid_count
                    FROM kyc_documents
                    WHERE user_id       = NEW.user_id
                      AND document_type = 'national_id'
                      AND status        = 'verified'
                    LIMIT 1;

                    IF passport_count = 0 OR natid_count = 0 THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'KYC_REQUIRED: Cannot activate a card without verified Passport and National ID.';
                    END IF;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trg_card_kyc_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_card_kyc_before_update');
    }
};
