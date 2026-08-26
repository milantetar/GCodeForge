<?php

namespace App\Http\Resources;

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversion */
class ConversionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'options' => $this->options,
            'warnings' => $this->warnings ?? [],
            'error_message' => $this->error_message,
            'metrics' => [
                'input_lines' => $this->input_lines,
                'output_lines' => $this->output_lines,
                'duration_ms' => $this->duration_ms,
            ],
            'project' => new ProjectResource($this->whenLoaded('project')),
            'machine_profile' => new MachineProfileResource($this->whenLoaded('machineProfile')),
            'download_url' => $this->status === ConversionStatus::Completed
                ? route('api.v1.conversions.download', $this)
                : null,
            'queued_at' => $this->queued_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
