<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rfq extends Model
{
    /** Sheet "Priority Code" di log RFQ: kode => [guide, PIC]. */
    public const PRIORITIES = [
        'A1' => ['guide' => 'PO received', 'pic' => 'BDM'],
        'A2' => ['guide' => 'Waiting PO release', 'pic' => 'BDM'],
        'A3' => ['guide' => 'Waiting quote to be submitted', 'pic' => 'BDM'],
        'A4' => ['guide' => 'Negotiating', 'pic' => 'BDM'],
        'A5' => ['guide' => 'Quote submitted', 'pic' => 'BDM'],
        'B1' => ['guide' => 'Waiting quote from engineering', 'pic' => 'Engineering'],
        'B2' => ['guide' => 'Waiting technical data', 'pic' => 'Engineering'],
        'C1' => ['guide' => 'Waiting approval quote', 'pic' => 'Management'],
        'D' => ['guide' => 'On hold', 'pic' => null],
        'E' => ['guide' => 'Loss - Decline - Rejected', 'pic' => null],
    ];

    public const STATUSES = ['pending', 'won', 'lost', 'on_hold'];

    protected $fillable = [
        'enquiry_no',
        'rfq_date',
        'type',
        'source',
        'area',
        'opportunity_name',
        'description',
        'customer_ref',
        'quote_no',
        'customer',
        'contact_name',
        'contact_phone',
        'supplier',
        'has_supplier_quote',
        'has_brochure',
        'has_drawing',
        'status',
        'priority_code',
        'po_received',
        'po_number',
        'current_pic',
        'action_plan',
        'deadline',
        'amount',
    ];

    protected $casts = [
        'rfq_date' => 'date:Y-m-d',
        'deadline' => 'date:Y-m-d',
        'amount' => 'float',
        'has_supplier_quote' => 'boolean',
        'has_brochure' => 'boolean',
        'has_drawing' => 'boolean',
        'po_received' => 'boolean',
    ];

    public function updates(): HasMany
    {
        return $this->hasMany(RfqUpdate::class)->orderByDesc('update_date')->orderByDesc('id');
    }

    // catatan progres terbaru (kolom "Status" di log), diambil dengan satu query untuk seluruh halaman
    public function latestUpdate(): HasOne
    {
        return $this->hasOne(RfqUpdate::class)->ofMany(['update_date' => 'max', 'id' => 'max']);
    }

    /** Nomor enquiry berikutnya untuk tanggal tertentu: RFQ{Ymd}-{nnn}, urut per tanggal. */
    public static function nextEnquiryNo(string $date): string
    {
        $prefix = 'RFQ'.date('Ymd', strtotime($date)).'-';

        $last = static::query()
            ->where('enquiry_no', 'like', $prefix.'%')
            ->orderByDesc('enquiry_no')
            ->value('enquiry_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
