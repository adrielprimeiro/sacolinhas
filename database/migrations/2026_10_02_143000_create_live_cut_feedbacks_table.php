<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('live_cut_feedbacks')) {
            Schema::create('live_cut_feedbacks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('live_id')->nullable()->index();
                $table->unsignedBigInteger('live_item_id')->nullable()->index();
                $table->unsignedBigInteger('item_id')->nullable()->index();
                $table->integer('codigo_live')->nullable();
                $table->string('product_name')->nullable();
                $table->decimal('product_price', 10, 2)->nullable();
                $table->double('cut_start_sec', 8, 2);
                $table->double('cut_end_sec', 8, 2);
                $table->text('start_sentence_snippet')->nullable();
                $table->text('end_sentence_snippet')->nullable();
                $table->text('full_transcription_snippet')->nullable();
                $table->string('feedback_type')->default('human_adjusted'); // 'human_approved', 'human_adjusted'
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('live_items', function (Blueprint $table) {
            if (!Schema::hasColumn('live_items', 'is_reviewed')) {
                $table->boolean('is_reviewed')->default(false)->after('cut_end_sec');
            }
            if (!Schema::hasColumn('live_items', 'review_quality')) {
                $table->string('review_quality')->nullable()->after('is_reviewed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_cut_feedbacks');
        
        Schema::table('live_items', function (Blueprint $table) {
            if (Schema::hasColumn('live_items', 'is_reviewed')) {
                $table->dropColumn('is_reviewed');
            }
            if (Schema::hasColumn('live_items', 'review_quality')) {
                $table->dropColumn('review_quality');
            }
        });
    }
};
