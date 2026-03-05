<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'track_title' => 'required|string|max:255',
            'genre_id' => 'nullable|integer|exists:genres,genre_id',
            'genre_name' => 'required_without:genre_id|string|max:255',
            'bpm_value' => 'nullable|integer|min:1|max:999',
            'release_date' => 'nullable|date',
            'track_length_sec' => 'nullable|integer|min:1',
            'track_cover' => 'nullable|string|max:255',
            'track_path' => 'nullable|string|max:255',
            'artist_ids' => 'nullable|array',
            'artist_ids.*' => 'integer|exists:artists,artist_id',
            'artist_names' => 'nullable|array',
            'artist_names.*' => 'string|max:255',
        ];
    }
}
