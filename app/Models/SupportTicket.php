<?php

namespace App\Models;

use App\Events\SupportTicketCreated;
use App\Models\Erp\ChatCategoryItem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SupportTicket extends BaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $connection = 'mmbcnerp';

    protected $table = 'support_tickets';

    public function __construct()
    {
        $this->table = DB::connection($this->connection)->getDatabaseName().'.'.$this->getTable();
    }

    protected $fillable = [
        'unit_id',
        'case_generated_no',
        'chat_category_item_id',
        'content',
        'platform_identifier',
        'category',
        'status',
        'submitted_by',
        'assigned_to',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => SupportTicketCreated::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'attachment_url',
        'attachment_urls',
    ];

    public function getMediaModel(): string
    {
        return MmbcnErpMedia::class;
    }

    /**
     * Get the user that assigned to the SupportTicket.
     *
     * @return BelongsTo
     */
    public function assignedTo(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class, 'assigned_to', 'id');
    }

    /**
     * Get the user that owns the SupportTicket.
     *
     * @return BelongsTo
     */
    public function submittedBy(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(User::class, 'submitted_by', 'id');
    }

    /**
     * Get the unit that owns the SupportTicket.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->setConnection('mysql')->belongsTo(Unit::class);
    }

    /**
     * Get the chat category item associated with the support ticket.
     *
     * @return BelongsTo
     */
    public function chatCategoryItem()
    {
        return $this->belongsTo(ChatCategoryItem::class);
    }

    /**
     * Get all of the support ticket's comments.
     *
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(SupportTicketComment::class, 'commentable')->where('commentable_type', 'App\Models\SupportTicket');
    }

    /**
     * Interact with the support ticket's file url.
     *
     * @return Attribute
     */
    protected function attachmentUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $mediaItems = $this->getMedia('support_ticket_attachment');
                $url = count($mediaItems) > 0 ? $mediaItems[0]->getFullUrl() : 'https://dashboard.mymooban.co.th/images/no-image.png';

                return $url;
            }
        );
    }

    /**
     * Interact with the support ticket's file urls.
     */
    protected function attachmentUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getMedia('support_ticket_attachment')
                ->map(fn ($media) => $media->getFullUrl())
                ->whenEmpty(fn () => collect(['https://dashboard.mymooban.co.th/images/no-image.png']))
                ->toArray()
        );
    }
}
