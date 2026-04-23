<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement("
                IF NOT EXISTS (SELECT 1 FROM sys.schemas WHERE name = 'FORM')
                EXEC('CREATE SCHEMA [FORM]')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep schema for safety; dropping it can fail if any object remains.
    }
};
