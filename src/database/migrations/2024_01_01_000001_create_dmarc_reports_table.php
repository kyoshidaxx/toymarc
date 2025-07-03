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
        Schema::create('dmarc_reports', function (Blueprint $table) {
            $table->id();
            $table->string('org_name')->comment('組織名');
            $table->string('email')->comment('レポート送信元メールアドレス');
            $table->string('report_id')->unique()->comment('レポートID');
            $table->dateTime('begin_date')->comment('レポート期間開始日時');
            $table->dateTime('end_date')->comment('レポート期間終了日時');
            $table->string('policy_domain')->comment('ポリシードメイン');
            $table->enum('policy_p', ['none', 'quarantine', 'reject'])->comment('ポリシー設定');
            $table->integer('policy_pct')->comment('ポリシー適用率');
            $table->json('raw_data')->comment('生XMLデータ');
            $table->string('file_hash')->unique()->comment('ファイルハッシュ値（重複防止用）');
            $table->timestamps();

            $table->index(['begin_date', 'end_date']);
            $table->index('org_name');
            $table->index('policy_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_reports');
    }
}; 