<?php

namespace App\Models;

use App\Enums\SiatEnvironment;
use App\Enums\SiatModality;
use App\Models\Concerns\AuditsCompanyChanges;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class SinInvoiceIssue extends Model implements Auditable
{
    use AuditsCompanyChanges, BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'customer_id',
        'sin_api_token_id',
        'sin_authorization_id',
        'sin_branch_id',
        'sin_point_of_sale_id',
        'sin_cuis_id',
        'sin_cufd_id',
        'tax_id',
        'environment_code',
        'modality_code',
        'emission_type_code',
        'document_sector_code',
        'invoice_document_type_code',
        'branch_code',
        'point_of_sale_code',
        'attempted_invoice_number',
        'invoice_number',
        'cuf',
        'cufd_code',
        'control_code',
        'reception_code',
        'status_code',
        'status_label',
        'transaccion',
        'xml_path',
        'gzip_path',
        'hash_file',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'taxable_amount',
        'payload',
        'response',
        'message',
        'duration_ms',
        'issued_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'environment_code' => SiatEnvironment::class,
            'modality_code' => SiatModality::class,
            'emission_type_code' => 'integer',
            'document_sector_code' => 'integer',
            'invoice_document_type_code' => 'integer',
            'branch_code' => 'integer',
            'point_of_sale_code' => 'integer',
            'attempted_invoice_number' => 'integer',
            'invoice_number' => 'integer',
            'status_code' => 'integer',
            'transaccion' => 'boolean',
            'subtotal_amount' => 'decimal:5',
            'discount_amount' => 'decimal:5',
            'total_amount' => 'decimal:5',
            'taxable_amount' => 'decimal:5',
            'payload' => 'array',
            'response' => 'array',
            'duration_ms' => 'integer',
            'issued_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function authorization(): BelongsTo
    {
        return $this->belongsTo(SinAuthorization::class, 'sin_authorization_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(SinBranch::class, 'sin_branch_id')->withoutGlobalScope('company');
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(SinPointOfSale::class, 'sin_point_of_sale_id')->withoutGlobalScope('company');
    }

    public function cuis(): BelongsTo
    {
        return $this->belongsTo(SinCuis::class, 'sin_cuis_id')->withoutGlobalScope('company');
    }

    public function cufd(): BelongsTo
    {
        return $this->belongsTo(SinCufd::class, 'sin_cufd_id')->withoutGlobalScope('company');
    }
}
