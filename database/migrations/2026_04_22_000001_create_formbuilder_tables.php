<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('FORM.form_departments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        Schema::create('FORM.form_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('department_id')->nullable();
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('FORM.form_departments')
                ->nullOnDelete();
        });

        Schema::create('FORM.form_templates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department_id')->nullable();
            $table->boolean('published')->default(false);
            $table->string('prerequisite_form_id')->nullable();
            $table->json('approval_flow')->nullable();
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('FORM.form_departments')
                ->nullOnDelete();
        });

        // SQL Server is more reliable with self-referencing FK applied after create.
        Schema::table('FORM.form_templates', function (Blueprint $table) {
            $table->foreign('prerequisite_form_id')
                ->references('id')
                ->on('FORM.form_templates')
                ->restrictOnDelete();
        });

        Schema::create('FORM.form_fields', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('template_id');
            $table->string('type');
            $table->string('label')->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->text('formula')->nullable();
            $table->json('table_columns')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('template_id')
                ->references('id')
                ->on('FORM.form_templates')
                ->cascadeOnDelete();
        });

        Schema::create('FORM.form_submissions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('template_id')->nullable();
            $table->string('template_name');
            $table->string('department_id')->nullable();
            $table->string('employee_name');
            $table->string('employee_email');
            $table->json('data')->nullable();
            $table->json('approval_steps')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('template_id')
                ->references('id')
                ->on('FORM.form_templates')
                ->nullOnDelete();

            $table->foreign('department_id')
                ->references('id')
                ->on('FORM.form_departments')
                ->nullOnDelete();
        });

        $now = now();

        DB::table('FORM.form_departments')->insert([
            ['id' => 'dep-1', 'name' => 'Human Resources', 'code' => 'HR', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'dep-2', 'name' => 'Finance', 'code' => 'FIN', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'dep-3', 'name' => 'IT & Technology', 'code' => 'IT', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'dep-4', 'name' => 'Operations', 'code' => 'OPS', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('FORM.form_users')->insert([
            [
                'username' => 'superadmin',
                'password' => 'admin123',
                'role' => 'superadmin',
                'name' => 'Super Administrator',
                'email' => 'superadmin@company.com',
                'department_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'hr.admin',
                'password' => 'hr123',
                'role' => 'admin_department',
                'name' => 'HR Administrator',
                'email' => 'hr@company.com',
                'department_id' => 'dep-1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'fin.admin',
                'password' => 'fin123',
                'role' => 'admin_department',
                'name' => 'Finance Administrator',
                'email' => 'finance@company.com',
                'department_id' => 'dep-2',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'director1',
                'password' => 'dir123',
                'role' => 'direktur',
                'name' => 'Director Operations',
                'email' => 'director@company.com',
                'department_id' => 'dep-4',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'vp1',
                'password' => 'vp123',
                'role' => 'vice_presiden_direktur',
                'name' => 'VP Finance',
                'email' => 'vp@company.com',
                'department_id' => 'dep-2',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'gm1',
                'password' => 'gm123',
                'role' => 'group_manager',
                'name' => 'Group Manager IT',
                'email' => 'gm@company.com',
                'department_id' => 'dep-3',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'mgr1',
                'password' => 'mgr123',
                'role' => 'manager',
                'name' => 'Manager HR',
                'email' => 'mgr.hr@company.com',
                'department_id' => 'dep-1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'sup1',
                'password' => 'sup123',
                'role' => 'supervisor',
                'name' => 'Supervisor HR',
                'email' => 'sup.hr@company.com',
                'department_id' => 'dep-1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'staff1',
                'password' => 'staff123',
                'role' => 'non_admin',
                'name' => 'Staff HR',
                'email' => 'staff.hr@company.com',
                'department_id' => 'dep-1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('FORM.form_templates')->insert([
            [
                'id' => 'TPL-LEAVE',
                'name' => 'Leave Request',
                'description' => 'Employee leave request form',
                'department_id' => 'dep-1',
                'published' => true,
                'prerequisite_form_id' => null,
                'approval_flow' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'TPL-REIMB',
                'name' => 'Expense Reimbursement',
                'description' => 'Claim expense reimbursement',
                'department_id' => 'dep-2',
                'published' => true,
                'prerequisite_form_id' => null,
                'approval_flow' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('FORM.form_fields')->insert([
            ['id' => 'f1', 'template_id' => 'TPL-LEAVE', 'type' => 'date', 'label' => 'Start Date', 'required' => true, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f2', 'template_id' => 'TPL-LEAVE', 'type' => 'date', 'label' => 'End Date', 'required' => true, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f3', 'template_id' => 'TPL-LEAVE', 'type' => 'dropdown', 'label' => 'Leave Type', 'required' => true, 'options' => json_encode(['Annual', 'Sick', 'Emergency']), 'formula' => null, 'table_columns' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f4', 'template_id' => 'TPL-LEAVE', 'type' => 'textarea', 'label' => 'Reason', 'required' => true, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f5', 'template_id' => 'TPL-REIMB', 'type' => 'text', 'label' => 'Expense Title', 'required' => true, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f6', 'template_id' => 'TPL-REIMB', 'type' => 'number', 'label' => 'Amount', 'required' => true, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f7', 'template_id' => 'TPL-REIMB', 'type' => 'number', 'label' => 'Tax', 'required' => false, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f8', 'template_id' => 'TPL-REIMB', 'type' => 'calculation', 'label' => 'Total', 'required' => false, 'options' => null, 'formula' => '{Amount}+{Tax}', 'table_columns' => null, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'f9', 'template_id' => 'TPL-REIMB', 'type' => 'file', 'label' => 'Receipt', 'required' => true, 'options' => null, 'formula' => null, 'table_columns' => null, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('FORM.form_submissions');
        Schema::dropIfExists('FORM.form_fields');
        Schema::dropIfExists('FORM.form_templates');
        Schema::dropIfExists('FORM.form_users');
        Schema::dropIfExists('FORM.form_departments');
    }
};
