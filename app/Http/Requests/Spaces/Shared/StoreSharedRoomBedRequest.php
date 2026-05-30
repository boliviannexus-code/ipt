<?php

namespace App\Http\Requests\Spaces\Shared;

use App\Models\BedType;
use App\Models\Space;
use App\Models\SpaceRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSharedRoomBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $space = $this->route('space');
        $room = $this->route('room');

        return $this->user()?->can('spaces.create') === true
            && $space instanceof Space
            && $room instanceof SpaceRoom
            && (int) $space->company_id === (int) $this->user()->company_id
            && (int) $room->company_id === (int) $space->company_id
            && (int) $room->space_id === (int) $space->id
            && $space->spaceMode?->slug === 'compartido';
    }

    public function rules(): array
    {
        return [
            'bed_type_id' => [
                'required',
                Rule::exists((new BedType)->getTable(), 'id')->where('is_active', true),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
