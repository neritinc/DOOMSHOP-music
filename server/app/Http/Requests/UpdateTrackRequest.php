<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UpdateTrackRequest extends FormRequest
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
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'integer|exists:genres,genre_id',
            'genre_name' => 'nullable|string|max:255',
            'genre_names' => 'nullable|array',
            'genre_names.*' => 'string|max:255',
            'bpm_value' => 'nullable|integer|min:1|max:999',
            'release_date' => 'nullable|date',
            'track_length_sec' => 'nullable|integer|min:1',
            'track_cover' => 'nullable|string|max:255',
            'track_cover_file' => 'nullable|image|max:5120',
            'track_path' => 'nullable|string|max:255',
            'track_audio' => 'nullable|file|max:51200',
            'preview_start_at' => 'required|integer|min:0',
            'preview_end_at' => 'required|integer|gt:preview_start_at',
            'artist_ids' => 'nullable|array',
            'artist_ids.*' => 'integer|exists:artists,artist_id',
            'artist_names' => 'nullable|array',
            'artist_names.*' => 'string|max:255',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = (int) $this->input('preview_start_at', 0);
            $end = (int) $this->input('preview_end_at', 30);

            if (($end - $start) > 30) {
                $validator->errors()->add('preview_end_at', 'Preview duration can be at most 30 seconds.');
            }

            $hasLegacyGenreId = (bool) $this->input('genre_id');
            $hasLegacyGenreName = trim((string) $this->input('genre_name', '')) !== '';
            $hasGenreIds = is_array($this->input('genre_ids')) && count(array_filter($this->input('genre_ids'))) > 0;
            $hasGenreNames = is_array($this->input('genre_names')) && count(array_filter(array_map(
                static fn ($x) => trim((string) $x),
                $this->input('genre_names', [])
            ))) > 0;

            if (! $hasLegacyGenreId && ! $hasLegacyGenreName && ! $hasGenreIds && ! $hasGenreNames) {
                $validator->errors()->add('genre_name', 'At least one genre is required.');
            }

            /** @var UploadedFile|null $audio */
            $audio = $this->file('track_audio');
            if ($audio instanceof UploadedFile) {
                $ext = strtolower((string) $audio->getClientOriginalExtension());
                $allowed = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];
                if ($ext === '' || ! in_array($ext, $allowed, true)) {
                    $validator->errors()->add('track_audio', 'The track audio field must be a file of type: mp3, wav, ogg, m4a, flac.');
                }
            }
        });
    }
}
