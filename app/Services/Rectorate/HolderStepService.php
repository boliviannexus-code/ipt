<?php

namespace App\Services\Rectorate;

use App\Models\Campus;
use App\Models\Customer;
use App\Models\RectorateApplication;
use App\Models\User;
use App\Services\Parameters\CustomerService;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HolderStepService
{
    public function __construct(private readonly CustomerService $customers) {}

    public function create(User $user, array $data): RectorateApplication
    {
        return DB::transaction(function () use ($user, $data): RectorateApplication {
            $companyId = (int) CompanyContext::id($user);
            $campus = $this->campusFor($user, $companyId);
            $customer = $this->resolveCustomer($user, $companyId, $data);

            return RectorateApplication::create([
                'company_id' => $companyId,
                'campus_id' => $campus->id,
                'account_number' => null,
                'customer_id' => $customer->id,
                'identity_document' => $data['identity_document'],
                'first_name' => $this->name($data['first_name']),
                'paternal_surname' => $this->name($data['paternal_surname']),
                'maternal_surname' => filled($data['maternal_surname'] ?? null) ? $this->name($data['maternal_surname']) : null,
                'birth_date' => $data['birth_date'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'],
                'current_step' => 2,
                'status' => 'draft',
            ]);
        });
    }

    public function update(User $user, RectorateApplication $application, array $data): RectorateApplication
    {
        return DB::transaction(function () use ($user, $application, $data): RectorateApplication {
            $customer = $this->resolveCustomer($user, (int) $application->company_id, $data, $application->customer);
            $application->update([
                'customer_id' => $customer->id,
                'identity_document' => $data['identity_document'],
                'first_name' => $this->name($data['first_name']),
                'paternal_surname' => $this->name($data['paternal_surname']),
                'maternal_surname' => filled($data['maternal_surname'] ?? null) ? $this->name($data['maternal_surname']) : null,
                'birth_date' => $data['birth_date'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'],
            ]);

            return $application->refresh();
        });
    }

    private function resolveCustomer(User $user, int $companyId, array $data, ?Customer $current = null): Customer
    {
        $customerData = [
            'identity_document_type_code' => $data['identity_document_type_code'],
            'document_number' => $data['document_number'],
            'document_complement' => $data['document_complement'] ?? null,
            'name' => $data['legal_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_active' => true,
        ];
        $customer = Customer::withTrashed()->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('identity_document_type_code', $data['identity_document_type_code'])
            ->whereRaw('lower(document_number) = ?', [mb_strtolower($data['document_number'])])
            ->whereRaw("lower(coalesce(document_complement, '')) = ?", [mb_strtolower($data['document_complement'] ?? '')])
            ->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            return $this->customers->update($customer, $customerData);
        }

        if ($current) {
            return $this->customers->update($current, $customerData);
        }

        return $this->customers->create($user, $customerData);
    }

    private function campusFor(User $user, int $companyId): Campus
    {
        $campusId = $user->personnel?->campus_id;

        if ($campusId === null) {
            throw ValidationException::withMessages([
                'campus' => 'Tu usuario debe estar vinculado a personal con una sede asignada antes de iniciar una inscripción.',
            ]);
        }

        $campus = Campus::withoutGlobalScope('company')->where('company_id', $companyId)->find($campusId);

        if ($campus === null) {
            throw ValidationException::withMessages(['campus' => 'La sede asignada a tu usuario no pertenece a la empresa activa.']);
        }

        return $campus;
    }

    private function name(string $value): string
    {
        return Str::of($value)->squish()->lower()->title()->toString();
    }
}
