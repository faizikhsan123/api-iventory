<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $divisions = [
        'PMR' => 'I&C-PMR',
        'ER' => 'I&C-ER',
        'Gas' => 'Gas Analyzer',
    ];

    private array $statuses = [
        'draft' => 'drafting_timesheet',
        'submitted' => 'waiting_approved_timesheet',
        'verified' => 'waiting_service_receipt',
        'paid' => 'paid',
    ];

    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('title', 'service_name');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('service_description')->nullable()->after('service_name');
            $table->date('service_date')->nullable()->after('invoice_date');
            $table->index('service_date', 'invoices_service_date_index');
            $table->string('status')->default('drafting_timesheet')->change();
        });

        $this->remap('invoices', 'division', $this->divisions);
        $this->remap('invoices', 'status', $this->statuses);
        $this->remap('invoice_status_logs', 'status', $this->statuses);
    }

    public function down(): void
    {
        $this->remap('invoice_status_logs', 'status', array_flip($this->statuses));
        $this->remap('invoices', 'status', array_flip($this->statuses));
        $this->remap('invoices', 'division', array_flip($this->divisions));

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_service_date_index');
            $table->dropColumn(['service_description', 'service_date']);
            $table->string('status')->default('draft')->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('service_name', 'title');
        });
    }

    // Nilai lama dan baru tidak saling tumpang tindih (selain identitas paid), jadi aman diurut satu-satu.
    private function remap(string $table, string $column, array $map): void
    {
        foreach ($map as $from => $to) {
            if ($from !== $to) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }
    }
};
