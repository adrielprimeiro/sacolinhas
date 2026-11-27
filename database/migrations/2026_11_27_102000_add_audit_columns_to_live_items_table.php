<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('live_items')) {
            return;
        }

        Schema::table('live_items', function (Blueprint $table) {
            if (!Schema::hasColumn('live_items', 'audit_status')) {
                $table->string('audit_status', 24)->nullable()->index()->after('review_quality');
            }
            if (!Schema::hasColumn('live_items', 'audit_score')) {
                $table->unsignedTinyInteger('audit_score')->nullable()->after('audit_status');
            }
            if (!Schema::hasColumn('live_items', 'audit_reason')) {
                $table->text('audit_reason')->nullable()->after('audit_score');
            }
            if (!Schema::hasColumn('live_items', 'audit_detected_code')) {
                $table->string('audit_detected_code', 32)->nullable()->after('audit_reason');
            }
            if (!Schema::hasColumn('live_items', 'audit_method')) {
                $table->string('audit_method', 32)->nullable()->after('audit_detected_code');
            }
            if (!Schema::hasColumn('live_items', 'audited_at')) {
                $table->timestamp('audited_at')->nullable()->after('audit_method');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('live_items')) {
            return;
        }

        Schema::table('live_items', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('live_items', 'audit_status') ? 'audit_status' : null,
                Schema::hasColumn('live_items', 'audit_score') ? 'audit_score' : null,
                Schema::hasColumn('live_items', 'audit_reason') ? 'audit_reason' : null,
                Schema::hasColumn('live_items', 'audit_detected_code') ? 'audit_detected_code' : null,
                Schema::hasColumn('live_items', 'audit_method') ? 'audit_method' : null,
                Schema::hasColumn('live_items', 'audited_at') ? 'audited_at' : null,
            ]);
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
