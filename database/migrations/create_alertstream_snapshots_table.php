<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $table = config('alertstream.snapshots.table', 'alertstream_snapshots');

        Schema::create($table, function (Blueprint $table): void {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->string('title');
            $table->string('exception_class');
            $table->text('exception_message');
            $table->string('file');
            $table->unsignedInteger('line');
            $table->longText('trace');
            $table->json('context')->nullable();
            $table->string('fingerprint', 32)->nullable()->index();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $table = config('alertstream.snapshots.table', 'alertstream_snapshots');

        Schema::dropIfExists($table);
    }
};
