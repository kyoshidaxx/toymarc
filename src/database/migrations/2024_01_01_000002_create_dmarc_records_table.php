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
        Schema::create('dmarc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dmarc_report_id')->constrained()->onDelete('cascade')->comment('DMARCレポートID');
            $table->string('source_ip')->comment('送信元IPアドレス');
            $table->integer('count')->comment('メール数');
            $table->string('disposition')->comment('処理結果');
            $table->boolean('dkim_aligned')->comment('DKIMアライメント');
            $table->string('dkim_result')->comment('DKIM結果');
            $table->boolean('spf_aligned')->comment('SPFアライメント');
            $table->string('spf_result')->comment('SPF結果');
            $table->timestamps();

            $table->index('source_ip');
            $table->index(['dkim_aligned', 'spf_aligned']);
            $table->index('disposition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_records');
    }
}; 