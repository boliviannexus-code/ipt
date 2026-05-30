<?php

namespace App\Http\Requests\Spaces\Shared;

use App\Models\RoomService;
use App\Models\Space;
use App\Models\SpaceRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSharedRoomServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $space = $this->route('space');
        $room = $this->route('room');

        return $this->user()?->can('spaces.create') === true
            && $space instanceof Space
            && $room instanceof SpaceRoom
            && (int) $space->company_id === (int) $this->user()->company_id
            && (int) $room->space_id === (int) $space->id
            && $space->spaceMode?->slug === 'compartido';
    }

    public function rules(): array
    {
        return [
            'room_services' => ['nullable', 'array'],
            'room_services.*' => [
                'integer',
                Rule::exists((new RoomService)->getTable(), 'id')->where('is_active', true),
            ],
        ];
    }
}
