<?php

namespace App\Services\Reports;

use App\Models\RectorateApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentReportService
{
    public function query(array $filters): Builder
    {
        return RectorateApplication::query()
            ->with([
                'campus', 'program', 'plan', 'commercialOrigin', 'salesExecutive', 'student',
                'contract.charges' => fn ($query) => $query->where('status', '!=', 'cancelled'),
            ])
            ->whereBetween('created_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ])
            ->when($filters['campus_id'] ?? null, fn ($query, $id) => $query->where('campus_id', $id))
            ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id))
            ->when($filters['plan_id'] ?? null, fn ($query, $id) => $query->where('plan_id', $id))
            ->when($filters['sales_executive_id'] ?? null, fn ($query, $id) => $query->where('sales_executive_id', $id))
            ->when($filters['commercial_origin_id'] ?? null, fn ($query, $id) => $query->where('commercial_origin_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('created_at');
    }

    public function summary(array $filters): array
    {
        $query = $this->query($filters)->reorder();
        $applicationIds = (clone $query)->select('rectorate_applications.id');
        $economic = DB::table('account_charges')
            ->join('enrollment_contracts', 'enrollment_contracts.id', '=', 'account_charges.enrollment_contract_id')
            ->where('account_charges.status', '!=', 'cancelled')
            ->whereIn('enrollment_contracts.rectorate_application_id', $applicationIds)
            ->selectRaw('COALESCE(SUM(account_charges.amount), 0) AS charged')
            ->selectRaw('COALESCE(SUM(account_charges.paid_amount), 0) AS collected')
            ->first();
        $charged = (float) $economic->charged;
        $collected = (float) $economic->collected;

        return [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'campuses' => (clone $query)->distinct()->whereNotNull('campus_id')->count('campus_id'),
            'charged' => $charged,
            'collected' => $collected,
            'balance' => $charged - $collected,
        ];
    }
}
